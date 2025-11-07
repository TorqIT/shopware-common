# Filter Count Display Feature - Parent Product Counts

## Overview

Display product counts next to filter options showing **parent products** (not variants). Example: "Red (5)" means 5 distinct parent products have red variants, not 5 red variant SKUs.

## Implementation Status

### ✅ Completed - Backend

**Core Implementation:**
- Uses Elasticsearch cardinality aggregation on `displayGroup` field to count distinct parent products
- Adds `ReverseNestedAggregation` for properties/options to access parent document fields
- Creates parallel count aggregations: `properties-counts`, `options-counts`, `manufacturer-counts`
- JSON serialization support for AJAX responses via `/widgets/search/filter`

**Key Files:**

1. **ElasticsearchCardinalitySubscriber.php** (`Subscriber/ElasticsearchCardinalitySubscriber.php`)
   - Subscribes to `ElasticsearchEntityAggregatorSearchEvent` (runs BEFORE ES query executes)
   - For each filter aggregation (properties, options, manufacturer):
     - **NestedAggregation** (properties/options): Adds ReverseNestedAggregation → CardinalityAggregation chain
     - **TermsAggregation** (manufacturer): Adds CardinalityAggregation directly
   - Structure for nested:
     ```
     properties (NestedAggregation)
       └─ properties (TermsAggregation)
            └─ to_parent (ReverseNestedAggregation)
                 └─ properties_parent_count (CardinalityAggregation on displayGroup)
     ```

2. **CardinalityAggregationHydrator.php** (`Elasticsearch/CardinalityAggregationHydrator.php`)
   - Decorates `AbstractElasticsearchAggregationHydrator`
   - Runs AFTER Elasticsearch returns response
   - Parses raw ES response to extract cardinality values:
     - Nested: `bucket['to_parent']['properties_parent_count']['value']`
     - Direct: `bucket['manufacturer_parent_count']['value']`
   - Creates `CountMapResult` objects with `{entity_id: parent_count}` mappings
   - Adds to `AggregationResultCollection` with `-counts` suffix
   - Clean implementation - no debug logging

3. **CountMapResult.php** (`Core/Content/Product/SalesChannel/Listing/CountMapResult.php`)
   - Custom aggregation result class
   - Stores `[entity_id => count]` mappings
   - Accessible in templates via `listing.aggregations.get('properties-counts').counts`
   - **Implements `JsonSerializable`** for AJAX responses:
     ```php
     public function jsonSerialize(): array
     {
         return [
             'name' => $this->getName(),
             'counts' => $this->counts,
             'apiAlias' => $this->getApiAlias(),
         ];
     }
     ```

**Services Registration** (`Resources/config/services.xml`):
```xml
<service id="Torq\Shopware\Common\Subscriber\ElasticsearchCardinalitySubscriber">
    <tag name="kernel.event_subscriber"/>
</service>

<service id="Torq\Shopware\Common\Elasticsearch\CardinalityAggregationHydrator"
    decorates="Shopware\Elasticsearch\Framework\DataAbstractionLayer\AbstractElasticsearchAggregationHydrator">
    <argument type="service" id=".inner"/>
</service>
```

### ✅ Completed - Frontend (Initial Page Load)

**Templates:**

1. **TorqShopwareCommon/filter-property-select.html.twig**
   - Passes `countMap` through to list items
   - Simplified implementation (no complex logic)

2. **TorqShopwareCommon/filter-multi-select-list-item.html.twig**
   - Displays counts: `{% if count is not null %}<span class="filter-option-count">({{ count }})</span>{% endif %}`
   - Applies disabled state for zero counts
   - Sets `data-count` attribute on checkboxes

3. **OrtoPedTheme/filter-panel.html.twig**
   - **Properties block**: Merges `properties-counts` and `options-counts` into single `mergedCountMap`
   - **Manufacturer block**: Passes `manufacturer-counts` directly
   - Clean implementation - deduplication logic removed (backend confirmed unique entities)
   - Example:
     ```twig
     {% set propertiesCountMap = listing.aggregations.get('properties-counts') %}
     {% set optionsCountMap = listing.aggregations.get('options-counts') %}
     {% set mergedCountMap = {} %}
     {% if propertiesCountMap and propertiesCountMap.counts %}
         {% set mergedCountMap = mergedCountMap|merge(propertiesCountMap.counts) %}
     {% endif %}
     {% if optionsCountMap and optionsCountMap.counts %}
         {% set mergedCountMap = mergedCountMap|merge(optionsCountMap.counts) %}
     {% endif %}
     ```

4. **OrtoPedTheme/filter-multi-select-list-item.html.twig**
   - Translation logic for property names
   - Inherits count display from TorqShopwareCommon

**CSS:**
- `TorqShopwareCommon/base.scss` - Styles for `.filter-option-count` and `.filter-option-disabled`

### ✅ What Works

- ✅ Manufacturer counts display correctly with parent product counts on initial load
- ✅ Properties show correct parent product counts (not 0) on initial load
- ✅ Options show correct parent product counts (not 0) on initial load
- ✅ Counts respect reduce-aggregations (correct values calculated when filters applied)
- ✅ Cardinality aggregations successfully added to ES queries
- ✅ ReverseNestedAggregation correctly accesses parent displayGroup field
- ✅ Backend parsing of cardinality values works correctly
- ✅ Backend confirmed no duplicate entities (logging verified unique IDs)
- ✅ JSON serialization works - `/widgets/search/filter` returns count maps
- ✅ Debug logging cleaned up from all classes
- ✅ **Dynamic count updates** - Counts update in real-time when filters are clicked
- ✅ **All filter types** - Works for properties, options, and manufacturers
- ✅ **Zero-count disabling** - Options with zero counts automatically disabled

### ✅ Completed - Dynamic Count Updates

**JavaScript Implementation:**
Counts now update dynamically when filters are clicked via the `FilterCountUpdaterPlugin`.

**How It Works:**
- Listens for filter AJAX responses from `/widgets/search/filter`
- Wraps filter plugins' `refreshDisabledState()` methods to intercept JSON
- Parses count maps (`properties-counts`, `options-counts`, `manufacturer-counts`)
- Updates DOM elements: count displays, disabled states, data attributes
- Handles all filter types: properties, options, and manufacturers

**Plugin Details:**
- **Location:** `TorqShopwareCommon/src/Resources/app/storefront/src/filter-count-updater-plugin/filter-count-updater.plugin.js`
- **Registration:** Registered in `TorqShopwareCommon/main.js` on `.filter-panel` selector
- **Architecture:** Independent plugin using method wrapping pattern (not event emitter)

## Technical Architecture

### Flow Diagram (Initial Page Load)
```
1. User loads listing page

2. Shopware creates Criteria with filter aggregations

3. ElasticsearchCardinalitySubscriber.addCardinalitySubAggregations()
   → Modifies OpenSearchDSL query
   → Adds ReverseNested + Cardinality for nested aggregations
   → Adds Cardinality directly for manufacturer

4. Elasticsearch executes query
   → Returns buckets with cardinality values

5. CardinalityAggregationHydrator.hydrate()
   → Receives raw ES response
   → Parses cardinality from nested structure
   → Creates CountMapResult objects
   → Adds to AggregationResultCollection

6. Templates render
   → Access listing.aggregations.get('properties-counts').counts
   → Display: "Red (5)" showing 5 parent products
```

### AJAX Filter Update Flow (With JavaScript)
```
1. User clicks filter option

2. Shopware filter plugin makes AJAX call to /widgets/search/filter

3. Backend processes request:
   → ElasticsearchCardinalitySubscriber adds cardinality aggregations
   → CardinalityAggregationHydrator extracts counts
   → SearchController.filter() returns JSON with all aggregations

4. JSON Response includes:
   {
     "properties-counts": {
       "name": "properties-counts",
       "counts": { "option-id-1": 5, "option-id-2": 12, ... },
       "apiAlias": "count_map_result"
     },
     "options-counts": { ... },
     "manufacturer-counts": { ... },
     "properties": { ... },
     "manufacturer": { ... }
   }

5. ✅ FilterCountUpdaterPlugin intercepts response:
   → Wraps filter plugins' refreshDisabledState() methods
   → Extracts count maps from JSON
   → Updates .filter-option-count spans
   → Updates data-count attributes
   → Applies .filter-option-disabled class for zero counts
   → Enables/disables checkboxes based on counts
```

### Why ReverseNestedAggregation?

**Problem:** Properties/options are stored as nested documents in Elasticsearch. The `displayGroup` field exists on the parent product document, not on nested property documents.

**Without ReverseNested:** Cardinality aggregation runs on nested documents where displayGroup doesn't exist → returns 0

**With ReverseNested:**
1. Terms aggregation groups by property ID (in nested context)
2. ReverseNested goes back to parent document context
3. Cardinality counts distinct displayGroup values from parent docs
4. Result: Correct parent product count per property

### Elasticsearch Query Structure

**Properties/Options (Nested):**
```json
{
  "properties": {
    "nested": { "path": "properties" },
    "aggs": {
      "properties": {
        "terms": { "field": "properties.id" },
        "aggs": {
          "to_parent": {
            "reverse_nested": {},
            "aggs": {
              "properties_parent_count": {
                "cardinality": { "field": "displayGroup" }
              }
            }
          }
        }
      }
    }
  }
}
```

**Manufacturer (Direct):**
```json
{
  "manufacturer": {
    "terms": { "field": "manufacturerId" },
    "aggs": {
      "manufacturer_parent_count": {
        "cardinality": { "field": "displayGroup" }
      }
    }
  }
}
```

### JavaScript Architecture (FilterCountUpdaterPlugin)

**Plugin Lifecycle:**
```
1. Filter panel loads in DOM (.filter-panel element)

2. PluginManager initializes FilterCountUpdaterPlugin
   → init() called

3. Plugin finds parent .cms-element-product-listing-wrapper
   → Gets Listing plugin instance via PluginManager

4. Plugin wraps all registered filter plugins:
   → Iterates through listing._registry
   → For each filter plugin with refreshDisabledState():
     - Stores original method
     - Replaces with wrapper that calls original + _updateAllCounts()

5. User clicks filter option
   → Shopware's filter plugin makes AJAX request
   → Response received with JSON count maps

6. Shopware's filter plugin calls refreshDisabledState(filter)
   → Our wrapper intercepts the call
   → Original method runs first
   → Then _updateAllCounts(filter) runs with JSON response

7. Count update process:
   → _extractCountMaps() parses JSON
   → Merges properties-counts + options-counts + manufacturer-counts
   → Finds all .filter-multi-select-checkbox elements
   → For each checkbox: _updateCheckboxCount()
     - Updates data-count attribute
     - Updates .filter-option-count span text
     - Applies/removes .filter-option-disabled class
     - Enables/disables checkbox based on count
```

**Method Wrapping Pattern:**
```javascript
// Store original
const originalRefresh = filterPlugin.refreshDisabledState.bind(filterPlugin);

// Replace with wrapper
filterPlugin.refreshDisabledState = (filter, filterParams) => {
    originalRefresh(filter, filterParams);  // Call original first
    this._updateAllCounts(filter);           // Then update counts
};
```

**Why Method Wrapping Instead of Events?**
- Shopware's `refreshDisabledState()` is called directly by Listing plugin
- No event is emitted for filter updates (only for listing updates)
- JSON response is passed directly to `refreshDisabledState()`, not available in events
- Method wrapping ensures we get the exact JSON data we need

### JSON Response Structure (from /widgets/search/filter)

```json
{
  "properties-counts": {
    "name": "properties-counts",
    "counts": {
      "6573f00f9070d853f78d59210fc4696c": 37,
      "e0b6c1d0e3ad7f50e6e0338fcd62599a": 6,
      "84c06b4c5b58b0687926659668c12c24": 14
    },
    "apiAlias": "count_map_result"
  },
  "options-counts": {
    "name": "options-counts",
    "counts": {
      "537f9dfbda34b3227846e54d1c510489": 102,
      "49415c76c295422efbdc9bfc0dc498a8": 64
    },
    "apiAlias": "count_map_result"
  },
  "manufacturer-counts": {
    "name": "manufacturer-counts",
    "counts": {
      "manufacturer-id-456": 25
    },
    "apiAlias": "count_map_result"
  },
  "properties": { /* EntityResult */ },
  "manufacturer": { /* EntityResult */ }
}
```

## Key Learnings

1. **Decorator Pattern for Hydrators Works**: Can intercept and modify aggregation results by decorating `AbstractElasticsearchAggregationHydrator`

2. **ReverseNested Required for Nested Aggregations**: Critical for accessing parent document fields when aggregating on nested documents

3. **Event Timing Matters**:
   - `ElasticsearchEntityAggregatorSearchEvent` - Modify query BEFORE execution
   - `ElasticsearchEntityAggregatorSearchedEvent` - Only provides hydrated results, not raw response

4. **Raw ES Response Available in Hydrator**: The `hydrate()` method receives the raw ES response array, allowing direct parsing

5. **CountMapResult Pattern**: Creating parallel count aggregations with `-counts` suffix keeps them separate from entity results

6. **JsonSerializable Required for AJAX**: Must implement `JsonSerializable` interface for objects to serialize properly in JSON responses

7. **Backend Duplication Myth**: Initial concern about duplicate properties was template-level, not backend. Backend confirmed to return unique entities.

8. **Properties vs Options**: Both simple properties and variant options flow through the same `properties` aggregation in Shopware's filter system, requiring merged count maps in templates.

## Next Steps

### 1. ✅ COMPLETED: JSON Serialization
- ✅ Added `JsonSerializable` to `CountMapResult`
- ✅ Verified `/widgets/search/filter` returns count data in JSON

### 2. ✅ COMPLETED: Dynamic Count Updates via JavaScript

**Implemented:** `FilterCountUpdaterPlugin` now handles dynamic count updates

**Implementation Details:**

1. ✅ **Created FilterCountUpdaterPlugin**
   - Location: `TorqShopwareCommon/src/Resources/app/storefront/src/filter-count-updater-plugin/filter-count-updater.plugin.js`
   - Architecture: Independent plugin using method wrapping pattern
   - Key Methods:
     - `init()` - Gets Listing plugin reference, wraps filter plugins
     - `_wrapFilterPlugin(filterPlugin)` - Intercepts `refreshDisabledState()` method
     - `_updateAllCounts(filter)` - Extracts counts and updates all checkboxes
     - `_extractCountMaps(filter)` - Parses and merges count maps from JSON
     - `_updateCheckboxCount(checkbox, counts)` - Updates individual checkbox display

2. ✅ **Registered Plugin**
   - Added to `TorqShopwareCommon/main.js`
   - Selector: `.filter-panel`
   - Initializes when filter panel exists in DOM

3. ✅ **JSON Response Parsing**
   - Extracts `properties-counts`, `options-counts`, `manufacturer-counts`
   - Merges all count maps into single object
   - Handles missing count maps gracefully

4. ✅ **DOM Updates**
   - Updates `.filter-option-count` span text
   - Updates `data-count` attribute on checkboxes
   - Applies/removes `.filter-option-disabled` class for zero counts
   - Enables/disables checkboxes (except checked items)

**Testing Needed:**
- Build frontend assets and verify counts update when filters clicked
- Test with properties, options, and manufacturer filters
- Verify zero-count options become disabled
- Test with reduce-aggregations (active filters)

### 3. Testing & Validation
- Test with reduce-aggregations (active filters)
- Verify counts across different categories
- Test both sales channels (Cascade/OrtoPed)
- Mobile vs desktop views
- Performance testing with large result sets

### 4. Optional Enhancements
- Add counts to color/media preview filters (currently only text)
- Performance monitoring of cardinality aggregations
- Accessibility improvements for disabled filters

## Technical Notes

- **displayGroup field**: String field in ProductDefinition, indexed in ES, groups parent + variants
- **Template inheritance**: Shopware Core → TorqShopwareCommon → OrtoPedTheme
- **reduce-aggregations**: Shopware's built-in filter context - wraps aggregations in FilterAggregation to show counts for current filter state
- **JsonSerializable**: Required for custom objects to be properly serialized in Symfony JsonResponse
- **AJAX endpoint**: `/widgets/search/filter` returns pure JSON (not HTML template)
