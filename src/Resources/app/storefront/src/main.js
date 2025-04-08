import QuickAddAutocompletePlugin from "./quick-add-autocomplete-plugin/quick-add-autocomplete.plugin";

PluginManager.register(
    'QuickAddAutocompletePlugin', 
    QuickAddAutocompletePlugin,
    '[data-quick-add-autocomplete-plugin]'
);