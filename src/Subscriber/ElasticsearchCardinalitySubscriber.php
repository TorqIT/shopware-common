<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Subscriber;

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

            // Handle both NestedAggregation (for properties/options) and TermsAggregation (for manufacturer)
            if ($aggregation instanceof NestedAggregation) {
                // For NestedAggregation, we need to find the inner TermsAggregation and add cardinality to it
                // The inner aggregation is typically named the same as the outer one
                $innerAggregations = $aggregation->getAggregations();

                foreach ($innerAggregations as $innerAgg) {
                    if ($innerAgg instanceof TermsAggregation && $innerAgg->getName() === $aggregationName) {
                        // For nested aggregations, we need ReverseNested to get back to parent docs
                        $reverseNested = new ReverseNestedAggregation('to_parent');
                        $cardinalityAgg = new CardinalityAggregation($aggregationName . '_parent_count');
                        $cardinalityAgg->setField('displayGroup');
                        $reverseNested->addAggregation($cardinalityAgg);
                        $innerAgg->addAggregation($reverseNested);
                        break;
                    }
                }
            } elseif ($aggregation instanceof TermsAggregation) {
                // Add cardinality sub-aggregation directly to the existing aggregation
                $cardinalityAgg = new CardinalityAggregation($aggregationName . '_parent_count');
                $cardinalityAgg->setField('displayGroup');
                $aggregation->addAggregation($cardinalityAgg);
            }
        }
    }
}
