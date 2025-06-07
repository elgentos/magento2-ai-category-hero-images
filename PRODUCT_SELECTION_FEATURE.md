# Product Selection Feature for AI Category Hero Images

## Overview

This enhancement adds the ability for administrators to select specific products from a category to use as input for generating AI hero images, replacing the previous approach of using a random selection of products.

## Key Features

### 1. Visual Product Selection Interface
- **Product Grid**: Displays products in a responsive grid layout with thumbnails
- **Product Information**: Shows product name, SKU, and image for each product
- **Checkbox Selection**: Click on products or checkboxes to select/deselect
- **Visual Feedback**: Selected products are highlighted with blue border and background

### 2. Selection Management
- **Select All Button**: Quickly select all available products
- **Deselect All Button**: Clear all selections
- **Selection Counter**: Real-time display of how many products are selected
- **Validation**: Ensures at least one product is selected before generation

### 3. Dynamic Prompt Generation
- **Refresh Prompt Button**: Regenerates the AI prompt based on currently selected products
- **Context-Aware**: Prompt changes based on the specific products chosen
- **Editable**: Users can still manually edit the generated prompt

## Technical Implementation

### Backend Changes

#### Model/CategoryImageManager.php
- Added `getProductsFromCategory()` method to fetch all products for UI display
- Added `getProductsByIds()` method to fetch specific products by their IDs
- Added `getProductDataForUI()` method to format product data for the frontend
- Enhanced product collections to include SKU and image attributes

#### Controller/Adminhtml/Image/Config.php
- Modified to return product data for the selection interface
- Added support for generating prompts based on specific product IDs
- Handles both initial load (returns products) and refresh requests (returns prompt)

#### Controller/Adminhtml/Image/Generator.php
- Updated to accept `product_ids` parameter instead of `num_products`
- Added validation to ensure products are selected
- Uses specific products instead of random selection for image generation

### Frontend Changes

#### view/adminhtml/web/js/form/element/custom-uploader.js
- Complete rewrite of the modal interface
- Added product grid rendering with responsive design
- Implemented checkbox selection with visual feedback
- Added event handlers for selection management
- Updated AJAX calls to send product IDs instead of counts
- Enhanced error handling and user feedback

### UI/UX Improvements

#### Modal Design
- **Wider Modal**: Increased width to 800px to accommodate product grid
- **Responsive Grid**: Auto-fitting columns based on available space
- **Product Cards**: Clean card design with image, name, and SKU
- **Hover Effects**: Visual feedback on product interaction
- **Loading States**: Proper loading indicators during AJAX requests

#### User Experience
- **Intuitive Selection**: Click anywhere on product card to select
- **Clear Visual Feedback**: Selected products are clearly highlighted
- **Bulk Operations**: Easy select/deselect all functionality
- **Real-time Updates**: Selection count updates immediately
- **Validation Messages**: Clear error messages for invalid states

## Benefits

### For Administrators
- **Precise Control**: Choose exactly which products to feature in hero images
- **Visual Selection**: See product images before making selections
- **Flexible Workflow**: Can select any combination of products
- **Quality Assurance**: Ensure only desired products are included

### For Image Quality
- **Curated Content**: Use best-selling or featured products
- **Brand Consistency**: Select products that align with brand messaging
- **Seasonal Relevance**: Choose products appropriate for current campaigns
- **Visual Harmony**: Select products that work well together visually

## Usage Examples

### Scenario 1: Featured Products
Select 3-5 best-selling products from a category to create a hero image that showcases the most popular items.

### Scenario 2: New Arrivals
Choose recently added products to create a hero image that highlights new inventory.

### Scenario 3: Seasonal Campaign
Select products relevant to current season or promotional campaign for targeted marketing.

### Scenario 4: Brand Showcase
Choose products from specific brands or with particular visual characteristics for brand-focused hero images.

## Backward Compatibility

- Console command functionality remains unchanged
- Existing API endpoints continue to work
- Default behavior (random selection) is preserved for console usage
- All existing configuration options are maintained

## Performance Considerations

- Product grid limited to 50 products for optimal UI performance
- Efficient AJAX loading with proper error handling
- Minimal DOM manipulation for smooth user experience
- Responsive design works well on various screen sizes

## Future Enhancements

Potential future improvements could include:
- Search/filter functionality within the product grid
- Pagination for categories with many products
- Drag-and-drop reordering of selected products
- Preview of selected products before generation
- Saved selection templates for quick reuse