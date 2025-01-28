import template from './sw-cms-el-route-pagelet.html.twig';
import './sw-cms-el-route-pagelet.scss';

Shopware.Component.register('sw-cms-el-route-pagelet', {
    template,

    mixins: [
        'cms-element'
    ],

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('route-pagelet');
        }
    }
});
