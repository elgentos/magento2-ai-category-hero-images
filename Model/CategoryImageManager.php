<?php

declare(strict_types=1);

namespace Elgentos\AiCategoryHeroImages\Model;

use Exception;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Math\Random;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class CategoryImageManager
{
    private WriteInterface $mediaDirectory;
    private const CATEGORY_IMAGE_PATH = 'catalog/category/';
    private const PRODUCT_IMAGE_PATH = 'catalog/product';

    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly ImageGenerator $imageGenerator,
        private readonly Filesystem $filesystem,
        private readonly Random $random,
        private readonly LoggerInterface $logger,
        private readonly StoreManagerInterface $storeManager,
    ) {
        try {
            $this->mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        } catch (FileSystemException $e) {
            $this->logger->error('Error initializing media directory: ' . $e->getMessage());
        }
    }

    /**
     * Get a category by ID
     *
     * @param int $categoryId
     * @return CategoryInterface|null
     */
    public function getCategory(int $categoryId): ?CategoryInterface
    {
        try {
            return $this->categoryRepository->get($categoryId);
        } catch (NoSuchEntityException $e) {
            $this->logger->error('Category not found: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get random products from a category
     *
     * @param int $categoryId
     * @param int $count
     * @return ProductCollection
     */
    public function getRandomProductsFromCategory(int $categoryId, int $count = 3): ProductCollection
    {
        /** @var ProductCollection $collection */
        $collection = $this->productCollectionFactory->create();
        $collection->addCategoriesFilter(['eq' => $categoryId])
            ->addAttributeToSelect(['name', 'image'])
            ->addAttributeToFilter('image', ['notnull' => true])
            ->addAttributeToFilter('image', ['neq' => 'no_selection'])
            ->setPageSize($count) // Get more products than needed to ensure we have enough with images
            ->load();

        // Randomize the collection
        $items = $collection->getItems();
        if (count($items) <= $count) {
            return $collection;
        }

        // Shuffle and slice to get random products
        shuffle($items);
        $randomItems = array_slice($items, 0, $count);

        $productIds = [];
        foreach ($randomItems as $item) {
            $productIds[] = $item->getId();
        }

        // Filter down
        $collection->setPageSize($count)->addAttributeToFilter('entity_id', ['in' => $productIds]);

        return $collection;
    }

    /**
     * Get all products from a category for selection
     *
     * @param int $categoryId
     * @return ProductCollection
     */
    public function getProductsFromCategory(int $categoryId): ProductCollection
    {
        /** @var ProductCollection $collection */
        $collection = $this->productCollectionFactory->create();
        $collection->addCategoriesFilter(['eq' => $categoryId])
            ->addAttributeToSelect(['name', 'image', 'small_image', 'thumbnail', 'sku'])
            ->addAttributeToFilter('image', ['notnull' => true])
            ->addAttributeToFilter('image', ['neq' => 'no_selection'])
            ->setPageSize(50) // Limit to 50 products for UI performance
            ->load();

        return $collection;
    }

    /**
     * Get specific products by IDs
     *
     * @param array $productIds
     * @return ProductCollection
     */
    public function getProductsByIds(array $productIds): ProductCollection
    {
        /** @var ProductCollection $collection */
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'image'])
            ->addAttributeToFilter('entity_id', ['in' => $productIds])
            ->addAttributeToFilter('image', ['notnull' => true])
            ->addAttributeToFilter('image', ['neq' => 'no_selection'])
            ->load();

        return $collection;
    }

    /**
     * Get product data for UI display
     *
     * @param int $categoryId
     * @return array
     */
    public function getProductDataForUI(int $categoryId): array
    {
        $products = $this->getProductsFromCategory($categoryId);
        $productData = [];
        $mediaBaseUrl = $this->getMediaBaseUrl();

        foreach ($products as $product) {
            $imageUrl = '';
            if ($product->getImage() && $product->getImage() !== 'no_selection') {
                $imageUrl = $mediaBaseUrl . self::PRODUCT_IMAGE_PATH . $product->getImage();
            }

            $productData[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'image_url' => $imageUrl,
                'sku' => $product->getSku()
            ];
        }

        return $productData;
    }

    /**
     * Get product image paths
     *
     * @param ProductCollection $products
     * @param int $limit Maximum number of images to return
     * @return array
     */
    public function getProductImagePaths(ProductCollection $products, int $limit = 3): array
    {
        $imagePaths = [];
        foreach ($products as $product) {
            if ($product->getImage() && $product->getImage() !== 'no_selection') {
                $imagePath = $this->mediaDirectory->getAbsolutePath(self::PRODUCT_IMAGE_PATH . $product->getImage());
                if (file_exists($imagePath)) {
                    $imagePaths[] = $imagePath;
                    if (count($imagePaths) >= $limit) {
                        break;
                    }
                }
            }
        }
        return $imagePaths;
    }

    /**
     * Generate a prompt for the AI based on product information
     *
     * @param ProductCollection $products
     * @param CategoryInterface $category
     * @return string
     */
    public function generatePrompt(ProductCollection $products, CategoryInterface $category): string
    {
        $productNames = [];

        foreach ($products as $product) {
            $productNames[] = $product->getName();
        }

        $productList = implode(", ", $productNames);

        $prompt = "Generate a photorealistic hero image for an e-commerce category page. Use the reference pictures and combine them into one photo with a clean backdrop. Make sure you contain all the items in the reference pictures, without altering what they look like.
Do not add any text or labels in any way, but keep the text that is in the reference pictures exactly as is.";

        return $prompt;
    }

    /**
     * Save the generated image and set it as the category image
     *
     * @param string $base64Image
     * @param null|CategoryInterface $category
     * @return null|array
     */
    public function saveCategoryImage(string $base64Image, ?CategoryInterface $category = null): ?array
    {
        try {
            $imageData = base64_decode($base64Image);
            $fileName = 'ai_hero_' . $this->random->getRandomString(10) . '.png';
            $filePath = self::CATEGORY_IMAGE_PATH . $fileName;

            $this->mediaDirectory->writeFile($filePath, $imageData);

            if ($category) {
                /** @var Category $category */
                $category->setImage($fileName);
                $this->categoryRepository->save($category);
            }

            $mediaBaseUrl = $this->getMediaBaseUrl();
            $absolutePath = $this->mediaDirectory->getAbsolutePath($filePath);

            return [
                [
                    'name' => $fileName,
                    'url'  => $mediaBaseUrl . self::CATEGORY_IMAGE_PATH . $fileName,
                    'size' => filesize($absolutePath),
                    'previewType' => 'image',
                    'previewHeight' => 1024, //todo - change image size
                    'previewWidth' => 1024, //todo - change image size
                    'type' => 'image/png'
                ]
            ];
        } catch (Exception $e) {
            $this->logger->error('Error saving category image: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            return null;
        }
    }

    /**
     * Get base media URL (web accessible)
     *
     * @return string
     */
    private function getMediaBaseUrl(): string
    {
        try {
            return $this->storeManager
                ->getStore()
                ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        } catch (\Exception $e) {
            $this->logger->error('Error getting media base URL: ' . $e->getMessage());
            return '';
        }
    }
}
