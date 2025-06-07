<?php

declare(strict_types=1);

namespace Elgentos\AiCategoryHeroImages\Controller\Adminhtml\Image;

use Elgentos\AiCategoryHeroImages\Model\CategoryImageManager;
use Magento\Backend\App\Action;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class Config extends Action implements HttpPostActionInterface
{
    /** @var string */
    const ADMIN_RESOURCE = 'Magento_Catalog::categories';  // Adjust ACL as needed

    /**
     * @param \Elgentos\AiCategoryHeroImages\Model\CategoryImageManager $categoryImageManager
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Catalog\Model\CategoryRepository $categoryRepository
     */
    public function __construct(
        protected CategoryImageManager $categoryImageManager,
        protected Action\Context $context,
        protected JsonFactory $resultJsonFactory,
        private readonly CategoryRepository $categoryRepository,
    ) {
        parent::__construct($context);
    }

    /**
     * Return AI configuration as JSON
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();
        $categoryId = (int) $this->_request->getParam('category_id');
        $productIds = $this->_request->getParam('product_ids', []);

        try {
            // If product IDs are provided, generate prompt for those specific products
            if (!empty($productIds) && is_array($productIds)) {
                $products = $this->categoryImageManager->getProductsByIds($productIds);
                $defaultPrompt = $this->categoryImageManager->generatePrompt(
                    $products,
                    $this->categoryRepository->get($categoryId)
                );

                return $resultJson->setData([
                    'success'        => true,
                    'default_prompt' => $defaultPrompt,
                ]);
            }

            // Otherwise, get products for selection UI and default prompt
            $productData = $this->categoryImageManager->getProductDataForUI($categoryId);
            
            // Get default prompt with random products
            $randomProducts = $this->categoryImageManager->getRandomProductsFromCategory($categoryId, 3);
            $defaultPrompt = $this->categoryImageManager->generatePrompt(
                $randomProducts,
                $this->categoryRepository->get($categoryId)
            );

            return $resultJson->setData([
                'success'        => true,
                'products'       => $productData,
                'default_prompt' => $defaultPrompt,
            ]);

        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Failed to load configuration: %1', $e->getMessage())
            ]);
        }
    }
}
