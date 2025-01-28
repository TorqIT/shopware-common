import template from './sw-cms-el-config-route-pagelet.html.twig';
import './sw-cms-el-config-route-pagelet.scss';

const { Mixin, Component } = Shopware;

Component.register('sw-cms-el-config-route-pagelet',  {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('cms-element'),
    ],

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('route-pagelet');
            this.initElementData('route-pagelet');
        }
    },
});
