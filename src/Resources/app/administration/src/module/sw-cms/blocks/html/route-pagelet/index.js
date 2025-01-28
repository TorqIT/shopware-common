import './component';
import './preview';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'route-pagelet',
    category: 'html',
    label: 'sw-cms.blocks.html.routePagelet.label',
    component: 'sw-cms-block-route-pagelet',
    previewComponent: 'sw-cms-preview-route-pagelet',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px',
        sizingMode: 'full_width',
    },
    slots: {
        pagelet: {
            type: 'route-pagelet'
        }
    }
});