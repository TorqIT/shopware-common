import './component';
import './config';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'route-pagelet',
    label: 'sw-cms.elements.routePagelet.label',
    component: 'sw-cms-el-route-pagelet',
    configComponent: 'sw-cms-el-config-route-pagelet',
    previewComponent: 'sw-cms-el-preview-route-pagelet',
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
