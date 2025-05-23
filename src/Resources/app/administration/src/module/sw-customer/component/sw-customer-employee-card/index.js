import template from './sw-customer-employee-card.html.twig';

const { ShopwareError } = Shopware.Classes;
const { Mixin, EntityDefinition } = Shopware;
const { Criteria } = Shopware.Data;

Shopware.Component.override('sw-customer-employee-card', {
    template,

    inject: [
        'acl',
        'contextStoreService',
    ],

    mixins: [
        Mixin.getByName('notification'),
    ],

    data() {
        return {
            showImitateEmployeeModal: false,
        };
    },

    computed: {
        canUseEmployeeImitation() {
            /*
                Relies on the api_proxy_imitate-customer permission for now.
                We could create an employee one to keep things seperate.
            */
            return this.acl.can('api_proxy_imitate-customer');
        },

        hasSingleBoundSalesChannelUrl() {
            return false; s//this.businessPartnerCustomer.boundSalesChannel?.domains?.length === 1;
        },
        currentUser() {
            return Shopware.State.get('session').currentUser;
        },
    },

    methods: {

        async onImitateEmployee(item) {
            /*
                hasSingleBoundSalesChannelUrl will return false for employees as the 
                boundSalesChannel data is not currently included in the businessPartnerCustomer object.
                Going to leave as is and let the modal always open.
            */
           /*  if (this.hasSingleBoundSalesChannelUrl) {
                let sc = this.businessPartnerCustomer.boundSalesChannel.id;
                let scUrl = `${this.businessPartnerCustomer.boundSalesChannel.domains.first().url}/account/login/imitate-employee`;

                this.contextStoreService
                    .generateImitateCustomerToken(this.entity.businessPartnerCustomerId, sc)
                    .then((response) => {
                        const handledResponse = this.handleResponse(response);

                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = scUrl;
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
                        this.createNotificationError({
                            message: this.$tc('employee.notificationImitateEmployeeErrorMessage'),
                        });
                    });
                    return;
            } */

            this.selectedItem = item;
            //console.log(item.id);
            this.showImitateEmployeeModal = true;
        },
        /* createHiddenInput(form, name, value) {
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
            return response.data;
        }, */
        onCloseImitateEmployeeModal() {
            this.showImitateEmployeeModal = false;
        },
    }
});
