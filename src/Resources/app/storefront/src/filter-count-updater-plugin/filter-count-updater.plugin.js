import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';

/**
 * FilterCountUpdaterPlugin
 *
 * Dynamically updates filter option counts when filters are applied via AJAX.
 *
 * This plugin intercepts the filter update responses from /widgets/search/filter
 * and updates the count displays next to filter options. It handles all filter types:
 * - Properties (product attributes)
 * - Options (variant options)
 * - Manufacturers
 *
 * The backend returns count maps in the JSON response which contain parent product
 * counts (not variant counts) calculated via Elasticsearch cardinality aggregations.
 *
 * @see TorqShopwareCommon/.ai/SearchFilterAggregates.md for full documentation
 */
export default class FilterCountUpdaterPlugin extends Plugin {

    /**
     * Initialize the plugin
     * Get reference to Listing plugin and wrap filter plugin methods
     */
    init() {
        try {
            // Find the parent listing wrapper element
            const parentElement = DomAccess.querySelector(
                document,
                '.cms-element-product-listing-wrapper',
                false
            );

            if (!parentElement) {
                return;
            }

            // Get the Listing plugin instance
            this.listing = window.PluginManager.getPluginInstanceFromElement(
                parentElement,
                'Listing'
            );

            if (!this.listing) {
                return;
            }

            // Wrap all registered filter plugins to intercept their refreshDisabledState calls
            this._registerEventListeners();

        } catch (error) {
            console.error('FilterCountUpdaterPlugin initialization failed:', error);
        }
    }

    /**
     * Register event listeners by wrapping filter plugin methods
     * This intercepts the refreshDisabledState method which receives the JSON response
     *
     * @private
     */
    _registerEventListeners() {
        if (!this.listing._registry) {
            return;
        }

        // Iterate through all registered filter plugins
        this.listing._registry.forEach((filterPlugin) => {
            this._wrapFilterPlugin(filterPlugin);
        });
    }

    /**
     * Wrap a filter plugin's refreshDisabledState method
     * This allows us to intercept the JSON response from /widgets/search/filter
     *
     * @param {Plugin} filterPlugin - The filter plugin instance to wrap
     * @private
     */
    _wrapFilterPlugin(filterPlugin) {
        // Check if the plugin has the refreshDisabledState method
        if (typeof filterPlugin.refreshDisabledState !== 'function') {
            return;
        }

        // Store the original method
        const originalRefresh = filterPlugin.refreshDisabledState.bind(filterPlugin);

        // Replace with our wrapper
        filterPlugin.refreshDisabledState = (filter, filterParams) => {
            // Call the original method first
            originalRefresh(filter, filterParams);

            // Then update our counts
            this._updateAllCounts(filter);
        };
    }

    /**
     * Update all filter counts from the JSON response
     * Extracts count maps and updates all checkboxes in the DOM
     *
     * @param {Object} filter - JSON response from /widgets/search/filter
     * @private
     */
    _updateAllCounts(filter) {
        // Extract and merge all count maps
        const counts = this._extractCountMaps(filter);

        // Find all filter checkboxes in the DOM
        const checkboxes = document.querySelectorAll('.filter-multi-select-checkbox');

        // Update each checkbox with its count
        checkboxes.forEach(checkbox => {
            this._updateCheckboxCount(checkbox, counts);
        });
    }

    /**
     * Extract and merge count maps from the JSON response
     *
     * The backend returns separate count maps for:
     * - properties-counts: Product property counts
     * - options-counts: Variant option counts
     * - manufacturer-counts: Manufacturer counts
     *
     * @param {Object} filter - JSON response from /widgets/search/filter
     * @returns {Object} Merged object with all counts {optionId: count}
     * @private
     */
    _extractCountMaps(filter) {
        const propertiesCounts = filter['properties-counts']?.counts || {};
        const optionsCounts = filter['options-counts']?.counts || {};
        const manufacturerCounts = filter['manufacturer-counts']?.counts || {};

        // Merge all count maps into a single object
        const merged = {
            ...propertiesCounts,
            ...optionsCounts,
            ...manufacturerCounts
        };

        return merged;
    }

    /**
     * Update a single checkbox's count display
     *
     * Updates:
     * - data-count attribute
     * - .filter-option-count span text
     * - .filter-option-disabled class for zero counts
     * - disabled state of checkbox
     *
     * @param {HTMLInputElement} checkbox - The checkbox element
     * @param {Object} counts - Merged count maps {optionId: count}
     * @private
     */
    _updateCheckboxCount(checkbox, counts) {
        // Get the option ID from checkbox ID attribute
        const optionId = checkbox.id;
        // Treat undefined/missing counts as 0
        const count = counts[optionId] ?? 0;

        // Update data attribute
        checkbox.dataset.count = count;

        // Find the parent list item and count display span
        const listItem = checkbox.closest('.form-check');
        if (!listItem) {
            return;
        }

        const countSpan = listItem.querySelector('.filter-option-count');

        if (countSpan) {
            // Update the count display - show empty string for 0, otherwise show count
            countSpan.textContent = count === 0 ? '' : `(${count})`;
        }

        // Handle disabled state for zero counts
        // Don't disable if the checkbox is already checked (user has this filter active)
        if (count === 0 && !checkbox.checked) {
            listItem.classList.add('filter-option-disabled');
            checkbox.disabled = true;
        } else if (!checkbox.checked) {
            // Only modify disabled state for unchecked items
            // Let the parent plugin handle checked items
            listItem.classList.remove('filter-option-disabled');
            checkbox.disabled = false;
        }
    }
}
