// No import needed - Shopware is available globally
Shopware.Application.addServiceProviderDecorator('productStreamConditionService', (productStreamConditionService) => {
    // Add parentId to the allowed properties for product entity
    productStreamConditionService.addToEntityAllowList('product', ['parentId']);

    return productStreamConditionService;
}); 