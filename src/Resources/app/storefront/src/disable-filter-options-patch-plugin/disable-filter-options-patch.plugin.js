import FilterPropertySelectPlugin from 'src/plugin/listing/filter-property-select.plugin';

//This plugin extends the FilterPropertySelectPlugin to disable inactive filter options (checkboxes) based on the available options in the current listing.
//This appears to be a bug in the original plugin where it does not disable inactive options correctly. If this is patched in core this plugin can be removed.
export default class DisableFilterOptionsPatchPlugin extends FilterPropertySelectPlugin {

    init() {
        super.init();
    }

    refreshDisabledState(filter) {
        
        super.refreshDisabledState(filter);
        
        // Prevent disabling if propertyName is not set correctly
        if (this.options.propertyName === '') {
            return;
        }

        const activeItems = [];
        const properties = filter[this.options.name];
        const entities = properties.entities ?? [];

        const property = entities.find(entity => entity.translated.name === this.options.propertyName);

        if (property) {
            activeItems.push(...property.options);
        } 

        this._disableInactiveFilterOptions(activeItems.map(entity => entity.id));
    }
}