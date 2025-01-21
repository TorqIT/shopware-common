import template from './sw-cms-el-config-custom-page.html.twig';
import './sw-cms-el-config-custom-page.scss';

const { Mixin, Component } = Shopware;

Component.register('sw-cms-el-config-custom-page',  {
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
            this.initElementConfig('custom-page');
            this.initElementData('custom-page');
        }
    },
});
