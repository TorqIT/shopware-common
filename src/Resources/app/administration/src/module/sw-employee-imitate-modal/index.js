
import template from './sw-employee-imitate-modal.html.twig';

Shopware.Component.extend('sw-employee-imitate-modal', 'sw-customer-imitate-customer-modal', {
    template,

    props: {
        employee: {
            type: Object,
            required: true,
        },
    },

    computed: {
        modalTitle() {
            //this.$super('modalTitle');
            return this.$tc('sw-customer.imitateCustomerModal.modalTitle', {
                firstname: this.employee.firstName,
                lastname: this.employee.lastName,
            });
        },

        modalDescription() {
            return this.$tc('sw-customer.imitateCustomerModal.modalDescription', {
                firstname: this.employee.firstName,
                lastname: this.employee.lastName,
            });
        }
    },

    methods: {
        async onSalesChannelDomainMenuItemClick(salesChannelId, salesChannelDomainUrl) {
            let sc = salesChannelId; 
            let scUrl = `${salesChannelDomainUrl}/account/login/imitate-employee`;

            this.contextStoreService
                .generateImitateCustomerToken(this.customer.id, sc)
                .then((response) => {
                    const handledResponse = this.handleResponse(response);

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = scUrl;
                    form.target = '_blank';
                    document.body.appendChild(form);
                    
                    this.createHiddenInput(form, 'token', handledResponse.token);
                    this.createHiddenInput(form, 'customerId', this.customer.id);
                    this.createHiddenInput(form, 'employeeId', this.employee.id);
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
            return response.data;
        },
    }
});