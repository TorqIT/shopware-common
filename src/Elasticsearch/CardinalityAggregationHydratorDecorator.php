<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Elasticsearch;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Elasticsearch\Framework\DataAbstractionLayer\AbstractElasticsearchAggregationHydrator;
use Torq\Shopware\Common\Core\Content\Product\SalesChannel\Listing\CountMapResult;
use Torq\Shopware\Common\Subscriber\ElasticsearchCardinalitySubscriber;

/**
 * Hydrator decorator that extracts cardinality counts from raw Elasticsearch aggregation responses
 * and adds them as CountMapResult aggregations.
 */
#[Package('torq-common')]
class CardinalityAggregationHydratorDecorator extends AbstractElasticsearchAggregationHydrator
{
    private const AGGREGATION_NAMES = [
        'properties',
        'options',
        'manufacturer',
    ];

    public function __construct(
        private readonly AbstractElasticsearchAggregationHydrator $decorated
    ) {
    }

    public function getDecorated(): AbstractElasticsearchAggregationHydrator
    {
        return $this->decorated;
    }

    public function hydrate(
        EntityDefinition $definition,
        Criteria $criteria,
        Context $context,
        array $result
    ): AggregationResultCollection {
        // Let the decorated hydrator do its normal work
        $collection = $this->decorated->hydrate($definition, $criteria, $context, $result);

        // Only parse cardinality for product aggregations
        if ($definition->getEntityName() === 'product') {
            // Parse cardinality values from the raw response and add CountMapResults
            $this->parseCardinalityValues($result, $collection);
        }

        return $collection;
    }

    private function parseCardinalityValues(array $result, AggregationResultCollection $collection): void
    {
        if (!isset($result['aggregations'])) {
            return;
        }

        $aggregations = $result['aggregations'];

        foreach (self::AGGREGATION_NAMES as $aggregationName) {
            $counts = [];

            if (!isset($aggregations[$aggregationName])) {
                continue;
            }

            $aggData = $aggregations[$aggregationName];

            // Handle different nesting patterns for buckets
            // When reduce-aggregations is active, Shopware wraps aggregations in filter aggregations
            // causing an extra level of nesting for nested aggregations
            if (isset($aggData[$aggregationName][$aggregationName]['buckets'])) {
                // Triple-nested: reduce-aggregations with nested aggregation
                $buckets = $aggData[$aggregationName][$aggregationName]['buckets'];
            } elseif (isset($aggData[$aggregationName]['buckets'])) {
                // Double-nested: normal nested aggregation
                $buckets = $aggData[$aggregationName]['buckets'];
            } elseif (isset($aggData['buckets'])) {
                // Direct: direct aggregation (manufacturer)
                $buckets = $aggData['buckets'];
            } else {
                continue;
            }

            // Extract cardinality from each bucket
            $cardinalityName = $aggregationName . '_parent_count';
            foreach ($buckets as $bucket) {
                $key = $bucket['key'];

                // For nested aggregations, cardinality is inside reverse nested agg (see ElasticsearchCardinalitySubscriber::TO_PARENT_AGGREGATION_NAME)
                if (isset($bucket[ElasticsearchCardinalitySubscriber::TO_PARENT_AGGREGATION_NAME][$cardinalityName]['value'])) {
                    $counts[$key] = (int) $bucket[ElasticsearchCardinalitySubscriber::TO_PARENT_AGGREGATION_NAME][$cardinalityName]['value'];
                }
                // For direct aggregations (manufacturer), cardinality is directly in bucket
                elseif (isset($bucket[$cardinalityName]['value'])) {
                    $counts[$key] = (int) $bucket[$cardinalityName]['value'];
                }
            }

            if (!empty($counts)) {
                $countMapName = $aggregationName . '-counts';
                // Only add if it doesn't already exist (prevent duplicates)
                if (!$collection->has($countMapName)) {
                    $collection->add(new CountMapResult($countMapName, $counts));
                }
            }
        }
    }
}
