import QuickAddAutocompletePlugin from "./quick-add-autocomplete-plugin/quick-add-autocomplete.plugin";
import CategoryFilterNestedLinkPlugin from "./category-filter-nested-link-plugin/category-filter-nested-link-plugin";
import SearchCategoryFilterPlugin from "./category-search-filter-plugin/category-search-filter-plugin";
import FilterCountUpdaterPlugin from "./filter-count-updater-plugin/filter-count-updater.plugin";

PluginManager.register(
    'QuickAddAutocompletePlugin', 
    QuickAddAutocompletePlugin,
    '[data-quick-add-autocomplete-plugin]'
);

PluginManager.register(
    'CategoryFilterNestedLinkPlugin', 
    CategoryFilterNestedLinkPlugin,
    '[data-category-filter-nested-link-plugin]'
);

PluginManager.register(
    'SearchCategoryFilterPlugin',
    SearchCategoryFilterPlugin,
    '[data-search-category-filter-plugin]'
);

// Register FilterCountUpdaterPlugin for both multi-select and property-select filters
// Since it extends FilterMultiSelectPlugin, it works for both types
PluginManager.register(
    'FilterMultiSelectCount',
    FilterCountUpdaterPlugin,
    '[data-filter-multi-select]'
);

PluginManager.register(
    'FilterPropertySelectCount',
    FilterCountUpdaterPlugin,
    '[data-filter-property-select]'
);

PluginManager.override(
    'FilterPropertySelect',
    () => import('./disable-filter-options-patch-plugin/disable-filter-options-patch.plugin'),
    '[data-filter-property-select]'
);