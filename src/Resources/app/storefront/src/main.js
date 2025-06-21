import QuickAddAutocompletePlugin from "./quick-add-autocomplete-plugin/quick-add-autocomplete.plugin";
import CategoryFilterNestedLinkPlugin from "./category-filter-nested-link-plugin/category-filter-nested-link-plugin";

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