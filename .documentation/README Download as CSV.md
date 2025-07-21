# Download as CSV

This feature can be added to pages using the Shopware sw-data-grid component.  To use the feature, update your custom page similar to the following example.  

To add to a Shopware page you will need to override/extend the page first.

## Twig template

```
<sw-page class="">
    <template #smart-bar-header>
        ...
    </template>

    <template #smart-bar-actions>
        ...        
    </template>

    <template #content>
        <div class="sw-order-list__content">
            <div v-if="csvExportLoading" style="position: fixed; z-index: 9999; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(255,255,255,0.5); display: flex; align-items: center; justify-content: center;">
                <sw-loader />
            </div>
            <sw-data-grid ... >            
                ...
            </sw-data-grid>
        </div>
    </template>

    <template #sidebar>
        <sw-sidebar>
            
            ...

            <sw-sidebar-item
                icon="regular-download"
                :title="$tc('Download as CSV')"
                @click="onDownloadCsv"
            />

            ...

        </sw-sidebar>
    </template>
</sw-page>
```

## JS file

```
import template from '...';

const { Mixin } = Shopware;
const { Criteria } = Shopware.Data;

export default {
    template,

    mixins: [
        ...
        Mixin.getByName('csv-download-mixin'),
        Mixin.getByName('notification'),
    ],

    data() {
        return {
            ...
            csvExportLoading: false,
        };
    },
    
    ...

    methods: {
        ...

        async onDownloadCsv() {
            this.csvExportLoading = true;
            await this.$nextTick();
            setTimeout(async () => {
                try {
                    let criteria = await Shopware.Service('filterService')
                        .mergeWithStoredFilters(this.storeKey, this.orderCriteria);
                    criteria = await this.addQueryScores(this.term, criteria);
                    await this.downloadAllAsCsv(
                        <your repo here>,
                        criteria,
                        this.orderColumns
                    );
                } finally {
                    this.csvExportLoading = false;
                }
            }, 50);
        },
    },
};

```