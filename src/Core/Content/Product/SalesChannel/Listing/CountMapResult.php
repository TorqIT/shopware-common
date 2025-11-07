<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Core\Content\Product\SalesChannel\Listing;

use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResult;
use Shopware\Core\Framework\Log\Package;

/**
 * Aggregation result that stores product counts for filter options.
 * This is used alongside EntityResult to preserve count information
 * from ElasticSearch bucket aggregations.
 */
#[Package('torq-common')]
class CountMapResult extends AggregationResult implements \JsonSerializable
{
    /**
     * @param string $name Aggregation name
     * @param array<string, int> $counts Map of entity ID to product count
     */
    public function __construct(string $name, protected array $counts)
    {
        parent::__construct($name);
    }

    /**
     * Get all counts as a map of entity ID => count
     *
     * @return array<string, int>
     */
    public function getCounts(): array
    {
        return $this->counts;
    }

    /**
     * Get count for a specific entity ID
     *
     * @param string $id Entity ID (e.g., property option ID, manufacturer ID)
     * @return int Product count, or 0 if not found
     */
    public function getCount(string $id): int
    {
        return $this->counts[$id] ?? 0;
    }

    /**
     * Check if an entity ID has a count
     *
     * @param string $id Entity ID
     * @return bool True if count exists
     */
    public function has(string $id): bool
    {
        return isset($this->counts[$id]);
    }

    /**
     * Get all entity IDs that have counts
     *
     * @return array<string>
     */
    public function getIds(): array
    {
        return array_keys($this->counts);
    }

    /**
     * Get total count across all entities
     *
     * @return int Sum of all counts
     */
    public function getTotalCount(): int
    {
        return array_sum($this->counts);
    }

    public function getApiAlias(): string
    {
        return 'count_map_result';
    }

    /**
     * Serialize to JSON for AJAX responses
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->getName(),
            'counts' => $this->counts,
            'apiAlias' => $this->getApiAlias(),
        ];
    }
}
