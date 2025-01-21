import template from './sw-cms-el-custom-page.html.twig';
import './sw-cms-el-custom-page.scss';

Shopware.Component.register('sw-cms-el-custom-page', {
    template,

    mixins: [
        'cms-element'
    ],

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('custom-page');
        }
    }
});
