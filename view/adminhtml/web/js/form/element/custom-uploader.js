define([
    'Magento_Ui/js/form/element/image-uploader',
    'Magento_Ui/js/modal/modal',
    'jquery',
    'mage/url',
    'mage/translate',
    'mage/loader'
], function (ImageUploader, modal, $, urlBuilder, $t) {
    'use strict';

    return ImageUploader.extend({
        defaults: {
            configUrl: '',
            generateUrl: '',
            categoryId: null
        },

        initialize: function () {
            this._super();
            this.initAiButton();
            this.categoryId = this.source.data.entity_id;

            return this;
        },

        /** Initialize AI Button Click **/
        initAiButton: function () {
            let self = this;

            $(document).on('click', '.ai-generate-button', function () {
                self.openAiModal();
            });
        },

        openAiModal: function () {
            let self = this;

            $('body').loader('show');

            $.ajax({
                url: urlBuilder.build(this.configUrl),
                type: 'POST',
                data: { 
                    category_id: this.categoryId
                },
                success: function (response) {
                    if (response.success) {
                        self.default_prompt = response.default_prompt || '';
                        self.products = response.products || [];

                        self.renderAiModal();
                    } else {
                        alert($t('Failed to load AI configuration.'));
                    }
                },
                error: function () {
                    alert($t('Error loading AI configuration.'));
                },
                complete: function () {
                    $('body').loader('hide');
                }
            });
        },

        /** Render the Modal **/
        renderAiModal: function () {
            let self = this;

            if ($('#ai-generate-modal').length) {
                $('#ai-generate-modal').modal('openModal');
                return;
            }

            // Build product selection HTML
            let productsHtml = '';
            if (self.products && self.products.length > 0) {
                productsHtml = '<div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin-bottom: 15px;">';
                productsHtml += '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px;">';
                
                self.products.forEach(function(product) {
                    let imageHtml = product.image_url ? 
                        `<img src="${product.image_url}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;" />` :
                        '<div style="width: 60px; height: 60px; background: #f5f5f5; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #999;">No Image</div>';
                    
                    productsHtml += `
                        <div style="border: 1px solid #ddd; border-radius: 4px; padding: 8px; cursor: pointer; transition: all 0.2s;" 
                             class="product-item" data-product-id="${product.id}">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" class="product-checkbox" value="${product.id}" style="margin: 0;" />
                                ${imageHtml}
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-weight: bold; font-size: 12px; margin-bottom: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${product.name}">${product.name}</div>
                                    <div style="font-size: 11px; color: #666; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${product.sku}">${product.sku}</div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                productsHtml += '</div></div>';
            } else {
                productsHtml = '<div style="padding: 20px; text-align: center; color: #666; border: 1px solid #ddd; margin-bottom: 15px;">' + $t('No products with images found in this category.') + '</div>';
            }

            let modalHtml = `
                <div id="ai-generate-modal" style="width: 800px;">
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">${$t('Select Products')}:</label>
                        <div style="margin-bottom: 10px;">
                            <button type="button" id="select-all-products" class="action-secondary" style="margin-right: 10px;">${$t('Select All')}</button>
                            <button type="button" id="deselect-all-products" class="action-secondary">${$t('Deselect All')}</button>
                            <span style="margin-left: 15px; color: #666; font-size: 12px;" id="selected-count">0 ${$t('products selected')}</span>
                        </div>
                        ${productsHtml}
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label for="ai-prompt" style="display: block; margin-bottom: 5px; font-weight: bold;">${$t('Image Description')}:</label>
                        <textarea id="ai-prompt" placeholder="${$t('Enter image description...')}"
                        style="width: 100%; margin-bottom: 10px;" rows="6">${self.default_prompt}</textarea>
                    </div>
                    <button id="ai-generate-button" class="action-primary">${$t('Generate')}</button>
                    <button id="ai-refresh-prompt" class="action-secondary" style="margin-left: 10px;">${$t('Refresh Prompt')}</button>
                </div>
            `;

            $('body').append(modalHtml);

            let options = {
                type: 'popup',
                title: $t('Generate Image with AI'),
                buttons: false
            };

            modal(options, $('#ai-generate-modal'));
            $('#ai-generate-modal').modal('openModal');

            // Initialize event handlers
            self.initModalEventHandlers();
        },

        /** Initialize modal event handlers **/
        initModalEventHandlers: function () {
            let self = this;

            // Product selection handlers
            $(document).on('change', '.product-checkbox', function () {
                self.updateSelectedCount();
                self.updateProductItemStyle($(this));
            });

            $(document).on('click', '.product-item', function (e) {
                if (e.target.type !== 'checkbox') {
                    let checkbox = $(this).find('.product-checkbox');
                    checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
                }
            });

            // Select/Deselect all buttons
            $('#select-all-products').on('click', function () {
                $('.product-checkbox').prop('checked', true).trigger('change');
            });

            $('#deselect-all-products').on('click', function () {
                $('.product-checkbox').prop('checked', false).trigger('change');
            });

            // Generate button
            $('#ai-generate-button').on('click', function () {
                let prompt = $('#ai-prompt').val();
                let selectedProducts = $('.product-checkbox:checked').map(function() {
                    return parseInt($(this).val());
                }).get();
                
                if (prompt.trim() === '') {
                    alert($t('Please enter a prompt.'));
                    return;
                }
                
                if (selectedProducts.length === 0) {
                    alert($t('Please select at least one product.'));
                    return;
                }
                
                self.generateAiImage(prompt, selectedProducts);
            });

            // Refresh prompt button
            $('#ai-refresh-prompt').on('click', function () {
                let selectedProducts = $('.product-checkbox:checked').map(function() {
                    return parseInt($(this).val());
                }).get();
                
                if (selectedProducts.length === 0) {
                    alert($t('Please select at least one product to refresh the prompt.'));
                    return;
                }
                
                self.refreshPrompt(selectedProducts);
            });

            // Initial count update
            self.updateSelectedCount();
        },

        /** Update selected products count **/
        updateSelectedCount: function () {
            let count = $('.product-checkbox:checked').length;
            $('#selected-count').text(count + ' ' + $t('products selected'));
        },

        /** Update product item visual style **/
        updateProductItemStyle: function (checkbox) {
            let productItem = checkbox.closest('.product-item');
            if (checkbox.prop('checked')) {
                productItem.css({
                    'border-color': '#007cba',
                    'background-color': '#f0f8ff'
                });
            } else {
                productItem.css({
                    'border-color': '#ddd',
                    'background-color': 'white'
                });
            }
        },

        /** Generate AI Image using Configured URL **/
        generateAiImage: function (prompt, productIds) {
            let self = this;
            $('body').loader('show');

            $.ajax({
                url: self.generateUrl,
                type: 'POST',
                data: { 
                    prompt: prompt, 
                    category_id: this.categoryId,
                    product_ids: productIds
                },
                success: function (response) {
                    if (response.success && response.image) {
                        self.addGeneratedImage(response.image);
                        $('#ai-generate-modal').modal('closeModal');
                    } else {
                        alert($t('Image generation failed.'));
                    }
                },
                error: function () {
                    alert($t('An error occurred while generating the image.'));
                },
                complete: function () {
                    $('body').loader('hide');
                }
            });
        },

        /** Refresh prompt with selected products **/
        refreshPrompt: function (productIds) {
            let self = this;
            $('body').loader('show');

            // Use the selected products to generate a new prompt
            $.ajax({
                url: self.generateUrl.replace('/generator', '/config'),
                type: 'POST',
                data: { 
                    category_id: this.categoryId,
                    product_ids: productIds
                },
                success: function (response) {
                    if (response.success) {
                        $('#ai-prompt').val(response.default_prompt || '');
                    } else {
                        alert($t('Failed to refresh prompt.'));
                    }
                },
                error: function () {
                    alert($t('Error refreshing prompt.'));
                },
                complete: function () {
                    $('body').loader('hide');
                }
            });
        },

        addGeneratedImage: function (imageData) {
            this.value(imageData);
        }
    });
});
