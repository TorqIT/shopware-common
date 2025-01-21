import './component';
import './preview';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'custom-page',
    category: 'html',
    label: 'sw-cms.blocks.html.customPage.label',
    component: 'sw-cms-block-custom-page',
    previewComponent: 'sw-cms-preview-custom-page',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px',
        sizingMode: 'full_width',
    },
    slots: {
        heading: {
            type: 'text'
        },
        datatable: {
            type: 'custom-page'
        }
    }
});