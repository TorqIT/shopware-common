<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Elasticsearch;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntityAggregatorInterface;
use Shopware\Core\Framework\Log\Package;
use Torq\Shopware\Common\Core\Content\Product\SalesChannel\Listing\CountMapResult;

/**
 * Decorator that extracts cardinality counts from raw Elasticsearch responses
 * and adds them as CountMapResult aggregations.
 */
#[Package('torq-common')]
class ElasticsearchEntityAggregatorDecorator implements EntityAggregatorInterface
{
    private const AGGREGATION_NAMES = [
        'properties',
        'options',
        'manufacturer',
    ];

    /** @var array|null Stores raw ES response temporarily */
    private ?array $rawResponse = null;

    public function __construct(
        private readonly EntityAggregatorInterface $decorated
    ) {
    }

    public function aggregate(EntityDefinition $definition, Criteria $criteria, Context $context): AggregationResultCollection
    {
        // Call the decorated aggregator
        $result = $this->decorated->aggregate($definition, $criteria, $context);

        // If this was loaded by Elasticsearch (not fallback), parse cardinality
        if ($result->has('raw-elasticsearch-result')) {
            $this->parseCardinalityFromRaw($result);
        }

        return $result;
    }

    /**
     * Extract cardinality values from stored raw response
     */
    private function parseCardinalityFromRaw(AggregationResultCollection $result): void
    {
        if ($this->rawResponse === null || !isset($this->rawResponse['aggregations'])) {
            return;
        }

        $aggregations = $this->rawResponse['aggregations'];

        foreach (self::AGGREGATION_NAMES as $aggregationName) {
            $counts = [];

            // Check if this aggregation exists in the response
            if (!isset($aggregations[$aggregationName])) {
                continue;
            }

            $aggData = $aggregations[$aggregationName];

            // Handle NestedAggregation - the actual terms buckets are in the nested result
            if (isset($aggData[$aggregationName]['buckets'])) {
                // Nested aggregation: properties.properties.buckets
                $buckets = $aggData[$aggregationName]['buckets'];
            } elseif (isset($aggData['buckets'])) {
                // Direct TermsAggregation: manufacturer.buckets
                $buckets = $aggData['buckets'];
            } else {
                continue;
            }

            // Extract cardinality from each bucket
            foreach ($buckets as $bucket) {
                $key = $bucket['key'];
                $cardinalityName = $aggregationName . '_parent_count';

                if (isset($bucket[$cardinalityName]['value'])) {
                    $counts[$key] = (int) $bucket[$cardinalityName]['value'];
                } else {
                    // Fallback to doc_count
                    $counts[$key] = $bucket['doc_count'];
                }
            }

            if (!empty($counts)) {
                $result->add(new CountMapResult($aggregationName . '-counts', $counts));
            }
        }
    }

    /**
     * Called by the ElasticsearchEntityAggregator to store the raw response
     */
    public function setRawResponse(array $response): void
    {
        $this->rawResponse = $response;
    }
}
