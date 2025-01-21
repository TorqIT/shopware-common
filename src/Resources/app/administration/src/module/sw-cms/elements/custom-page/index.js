import './component';
import './config';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'custom-page',
    label: 'sw-cms.elements.customPage.label',
    component: 'sw-cms-el-custom-page',
    configComponent: 'sw-cms-el-config-custom-page',
    previewComponent: 'sw-cms-el-preview-custom-page',
    removable: false,
    hidden: false,
    defaultConfig: {
        routeName: {
            source: 'static',
            value: ''
        },
        routeParam: {
            source: 'static',
            value: ''
        }
    }
});
