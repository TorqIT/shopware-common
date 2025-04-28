import { Application } from 'src/core/shopware';

Shopware.Application.addServiceProviderDecorator('productStreamConditionService', (productStreamConditionService) => {
    // Add parentId to the allowed properties for product entity
    productStreamConditionService.addToEntityAllowList('product', ['parentId']);

    return productStreamConditionService;
}); 