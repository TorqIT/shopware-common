import FilterMultiSelectPlugin from 'src/plugin/listing/filter-multi-select.plugin';

/**
 * FilterCountUpdaterPlugin
 *
 * Dynamically updates filter option counts when filters are applied via AJAX.
 *
 * Extends FilterMultiSelectPlugin to add count display updates alongside Shopware's
 * built-in disabled state management. Works for all multi-select filters including:
 * - Properties (product attributes)
 * - Options (variant options)
 * - Manufacturers
 *
 * The backend returns count maps in the JSON response which contain parent product
 * counts (not variant counts) calculated via Elasticsearch cardinality aggregations.
 *
 * @see TorqShopwareCommon/.ai/SearchFilterAggregates.md for full documentation
 */
export default class FilterCountUpdaterPlugin extends FilterMultiSelectPlugin {

    /**
     * Override refreshDisabledState to add count updates
     * Parent handles disabled state, we add count display updates
     *
     * @param {Object} filter - JSON response from /widgets/search/filter
     * @param {Object} filterParams - Additional filter parameters
     * @public
     */
    refreshDisabledState(filter, filterParams) {
        // // Call parent to handle disabled state management
        // super.refreshDisabledState(filter, filterParams);

        // Then update our counts
        this._updateAllCounts(filter);
    }

    /**
     * Update all filter counts from the JSON response
     * Extracts count maps and updates all checkboxes within this filter instance
     *
     * @param {Object} filter - JSON response from /widgets/search/filter
     * @private
     */
    _updateAllCounts(filter) {
        // Extract and merge all count maps
        const counts = this._extractCountMaps(filter);

        // Find all filter checkboxes within THIS filter instance
        const checkboxes = this.el.querySelectorAll(this.options.checkboxSelector);

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
     *
     * Note: Disabled state management is handled by parent FilterMultiSelectPlugin
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
    }
}
