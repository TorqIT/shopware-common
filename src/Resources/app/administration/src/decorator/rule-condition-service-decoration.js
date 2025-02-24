import '../component/rule/condition-type/torq-condition-customer-address-custom-field';

Shopware.Application.addServiceProviderDecorator('ruleConditionDataProviderService', (ruleConditionService) => {
    ruleConditionService.addCondition('customerAddressCustomField', {
        component: 'torq-condition-customer-address-custom-field',
        label: 'Customer address with custom field',
        scopes: ['global'],
        group: 'customer'
    });

    return ruleConditionService;
});