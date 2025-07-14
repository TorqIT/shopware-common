export default {
    methods: {
        async downloadAllAsCsv(repository, criteria, columns, batchSize = 500) {
            // Emit loading start
            if (this.$emit) this.$emit('csv-download-loading', true);
            const { cloneDeep } = Shopware.Utils.object;
            if (typeof this.createNotificationInfo === 'function') {
                this.createNotificationInfo({
                    title: 'CSV Download',
                    message: 'Your download is being prepared. This may take some time for large datasets.'
                });
            }
            function cleanCriteriaForExport(criteria) {
                if (criteria.hasOwnProperty('aggregations')) {
                    criteria.aggregations = [];
                }
                if (criteria.hasOwnProperty('associations')) {
                    criteria.associations = [];
                }
                // Do NOT remove filters or sortings anymore
                return criteria;
            }
            let allData = [];
            let page = 1;
            let total = 0;
            let firstBatch = true;
            let totalCount = null;
            let batch = [];
            try {
                do {
                    let batchCriteria = cloneDeep(criteria);
                    batchCriteria = cleanCriteriaForExport(batchCriteria);
                    batchCriteria.limit = batchSize;
                    batchCriteria.page = page;
                    const response = await repository.search(batchCriteria);
                    batch = Array.isArray(response) ? response : (response.items || response);
                    allData = allData.concat(batch);
                    if (firstBatch) {
                        totalCount = response.total || batch.length;
                        firstBatch = false;
                    }
                    total += batch.length;
                    page++;
                } while (total < totalCount && batch.length > 0);
                this.downloadAsCsv(allData, columns);
                if (typeof this.createNotificationSuccess === 'function') {
                    this.createNotificationSuccess({
                        title: 'CSV Download',
                        message: 'Your CSV download is ready.'
                    });
                }
            } finally {
                // Emit loading end
                if (this.$emit) this.$emit('csv-download-loading', false);
            }
        },
        downloadAsCsv(data, columns) {
            if (!data || !columns) {
                if (typeof this.createNotificationWarning === 'function') {
                    this.createNotificationWarning({
                        title: 'CSV Download',
                        message: 'No data available to download.'
                    });
                } else {
                    // fallback: alert
                    alert('No data available to download.');
                }
                return;
            }
            columns = columns.filter(col => col.property);
            const header = columns.map(col => '"' + col.label + '"').join(',');
            const rows = data.map(row =>
                columns.map(col => '"' + (row[col.property] !== undefined ? row[col.property] : '') + '"').join(',')
            );
            const csvContent = [header, ...rows].join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.setAttribute('download', this.csvFileName);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    },
    computed: {
        csvFileName() {
            return 'data-grid-export-' + new Date().toISOString().slice(0, 10) + '.csv';
        }
    }
}; 