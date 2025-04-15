import template from './sw-customer-employee-imitate-employee.html.twig';

import './sw-customer-employee-imitate-employee.scss';

const { ShopwareError } = Shopware.Classes;
const { Mixin, EntityDefinition } = Shopware;
const { Criteria } = Shopware.Data;

Shopware.Component.override('sw-customer-employee-detail', {
    template,

    inject: [
        'contextStoreService',
    ],

    data() {
        return {};
    },

    computed: {
        currentUser() {
            return Shopware.State.get('session').currentUser;
        },
    },

    methods: {

        async onImitateEmployee() {
            let sc = '018f62369c3e708cba4ee6f85819a4af'; //todo get this correctly
            this.contextStoreService
                .generateImitateCustomerToken(this.entity.businessPartnerCustomerId, sc)
                .then((response) => {
                    const handledResponse = this.handleResponse(response);

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `http://wp.localhost.torq:9401/account/login/imitate-employee`;
                    form.target = '_blank';
                    document.body.appendChild(form);
                    
                    this.createHiddenInput(form, 'token', handledResponse.token);
                    this.createHiddenInput(form, 'customerId', this.entity.businessPartnerCustomerId);
                    this.createHiddenInput(form, 'employeeId', this.entity.id);
                    this.createHiddenInput(form, 'userId', this.currentUser?.id);
            
                    form.submit();
                    form.remove();
                })
                .catch((error) => {
                    console.error(error);
                });
        },

        createHiddenInput(form, name, value) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        },

        handleResponse(response) {
            if (response.data === null || response.data === undefined) {
                return response;
            }

            const headers = response.headers;

            if (typeof headers === 'object' && headers !== null && headers['content-type'] === 'application/vnd.api+json') {
                //return ApiService.parseJsonApiData<ApiResponse<T>>(response.data);
                console.log(response.data);
            }

            return response.data;
        }
    }
});