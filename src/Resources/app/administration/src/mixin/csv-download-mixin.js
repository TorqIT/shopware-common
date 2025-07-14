export default {
    methods: {
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