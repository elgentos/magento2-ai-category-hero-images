# Demo: Product Selection Feature for AI Category Hero Images

This document demonstrates the new functionality that allows administrators to select the number of products to use when generating AI hero images for categories.

## New Features Added

### 1. Number of Products Input Field
- Added a number input field in the AI generation modal
- Range: 1-20 products
- Default: 3 products
- Includes helpful tooltip text

### 2. Refresh Prompt Button
- New "Refresh Prompt" button in the modal
- Allows regenerating the prompt with a different number of products
- Updates the prompt text dynamically without closing the modal

### 3. Enhanced Backend Support
- Fixed typo in parameter name (`num_poducts` → `num_products`)
- Updated both Config and Generator controllers to accept the parameter
- Proper type casting and validation

## How to Use

### Admin Interface
1. Navigate to **Catalog > Categories**
2. Edit any category
3. In the "Category Image" section, click **"Generate With AI"**
4. In the modal that opens:
   - **Number of Products**: Set how many products to use (1-20)
   - **Refresh Prompt**: Click to regenerate prompt with new product count
   - **Image Description**: Modify the generated prompt if needed
   - **Generate**: Create the hero image

### Console Command
The console command already supported this feature:
```bash
bin/magento elgentos:aicategoryhero:generate [category_id] --num-products=5
```

## Technical Implementation

### Files Modified
1. `Controller/Adminhtml/Image/Generator.php` - Fixed typo and added parameter handling
2. `Controller/Adminhtml/Image/Config.php` - Added num_products parameter support
3. `view/adminhtml/web/js/form/element/custom-uploader.js` - Enhanced UI and functionality
4. `README.md` - Updated documentation

### Key Changes
- **Parameter Validation**: Proper type casting with fallback to default value (3)
- **UI Enhancement**: Clean, labeled input field with validation
- **Dynamic Prompt Generation**: Refresh functionality without modal reload
- **Backward Compatibility**: All existing functionality preserved

## Benefits
- **Flexibility**: Administrators can fine-tune image generation based on category size
- **User Experience**: Intuitive interface with clear labels and tooltips
- **Performance**: Option to use fewer products for faster generation
- **Quality**: Option to use more products for richer image content

## Example Scenarios
- **Small Categories**: Use 1-3 products for focused, clean images
- **Large Categories**: Use 5-10 products for diverse, comprehensive images
- **Specialty Categories**: Use 15-20 products for maximum variety and richness