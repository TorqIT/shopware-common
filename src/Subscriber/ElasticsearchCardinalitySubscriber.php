<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Subscriber;

use OpenSearchDSL\Aggregation\Bucketing\FilterAggregation;
use OpenSearchDSL\Aggregation\Bucketing\NestedAggregation;
use OpenSearchDSL\Aggregation\Bucketing\ReverseNestedAggregation;
use OpenSearchDSL\Aggregation\Bucketing\TermsAggregation;
use OpenSearchDSL\Aggregation\Metric\CardinalityAggregation;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Log\Package;
use Shopware\Elasticsearch\Framework\DataAbstractionLayer\Event\ElasticsearchEntityAggregatorSearchEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds cardinality sub-aggregations to terms aggregations in the raw Elasticsearch query.
 * This counts distinct displayGroup values (parent products) instead of document counts (variants).
 */
#[Package('torq-common')]
class ElasticsearchCardinalitySubscriber implements EventSubscriberInterface
{
    /**
     * Shopware core field that groups parent products with their variants.
     * All variants share the same displayGroup as their parent, making it perfect for counting distinct parent products.
     * Stable across all variant display modes (grouped, expanded, main variant).
     */
    private const DISPLAY_GROUP_FIELD = 'displayGroup';

    /**
     * Arbitrary name for the ReverseNestedAggregation that returns from nested property context to parent document context.
     * This name is used to access the aggregation results in the response JSON.
     * Must match the reference in CardinalityAggregationHydratorDecorator.
     */
    public const TO_PARENT_AGGREGATION_NAME = 'to_parent';

    private const AGGREGATION_NAMES = [
        'properties',
        'options',
        'manufacturer',
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            ElasticsearchEntityAggregatorSearchEvent::class => 'addCardinalitySubAggregations',
        ];
    }

    public function addCardinalitySubAggregations(ElasticsearchEntityAggregatorSearchEvent $event): void
    {
        // Only process product aggregations
        if (!$event->getDefinition() instanceof ProductDefinition) {
            return;
        }

        $search = $event->getSearch();
        $aggregations = $search->getAggregations();

        foreach (self::AGGREGATION_NAMES as $aggregationName) {
            // Find the aggregation in the collection
            $aggregation = null;
            foreach ($aggregations as $agg) {
                if ($agg->getName() === $aggregationName) {
                    $aggregation = $agg;
                    break;
                }
            }

            if ($aggregation === null) {
                continue;
            }

            // Handle FilterAggregation (used when reduce-aggregations is active)
            // FilterAggregation wraps the actual aggregation, so we need to unwrap it
            if ($aggregation instanceof FilterAggregation) {
                $innerAggs = $aggregation->getAggregations();

                foreach ($innerAggs as $innerAgg) {
                    if ($innerAgg->getName() === $aggregationName) {
                        // Recursively handle the unwrapped aggregation
                        $aggregation = $innerAgg;
                        break;
                    }
                }
            }

            // Handle both NestedAggregation (for properties/options) and TermsAggregation (for manufacturer)
            if ($aggregation instanceof NestedAggregation) {
                // For NestedAggregation, we need to find the inner TermsAggregation and add cardinality to it
                // The inner aggregation is typically named the same as the outer one
                $innerAggregations = $aggregation->getAggregations();

                foreach ($innerAggregations as $innerAgg) {
                    if ($innerAgg instanceof TermsAggregation && $innerAgg->getName() === $aggregationName) {
                        // For nested aggregations, we need ReverseNested to get back to parent docs
                        $reverseNested = new ReverseNestedAggregation(self::TO_PARENT_AGGREGATION_NAME);
                        $cardinalityAgg = new CardinalityAggregation($aggregationName . '_parent_count');
                        $cardinalityAgg->setField(self::DISPLAY_GROUP_FIELD);
                        $reverseNested->addAggregation($cardinalityAgg);
                        $innerAgg->addAggregation($reverseNested);
                        break;
                    }
                }
            } elseif ($aggregation instanceof TermsAggregation) {
                // Add cardinality sub-aggregation directly to the existing aggregation
                $cardinalityAgg = new CardinalityAggregation($aggregationName . '_parent_count');
                $cardinalityAgg->setField(self::DISPLAY_GROUP_FIELD);
                $aggregation->addAggregation($cardinalityAgg);
            }
        }
    }
}
