<?php

declare(strict_types=1);

namespace Torq\Shopware\Common\Elasticsearch\Product;

use OpenSearchDSL\BuilderInterface;
use OpenSearchDSL\Query\Compound\BoolQuery;
use OpenSearchDSL\Query\Compound\DisMaxQuery;
use OpenSearchDSL\Query\Compound\FunctionScoreQuery;
use OpenSearchDSL\Query\FullText\MatchQuery;
use OpenSearchDSL\Query\Nested\NestedQuery;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Elasticsearch\Framework\AbstractElasticsearchDefinition;
use Symfony\Component\HttpFoundation\RequestStack;
use Torq\Shopware\Common\Constants\ConfigConstants;

class FuzzySearchElasticsearchProductDecorator extends AbstractElasticsearchDefinition
{
    private ?string $salesChannelId = null;

    public function __construct(
        private readonly AbstractElasticsearchDefinition $inner,
        private readonly SystemConfigService $systemConfigService,
        private readonly RequestStack $requestStack
    ) {
    }

    public function getEntityDefinition(): EntityDefinition
    {
        return $this->inner->getEntityDefinition();
    }

    public function getMapping(Context $context): array
    {
        return $this->inner->getMapping($context);
    }

    public function fetch(array $ids, Context $context): array
    {
        return $this->inner->fetch($ids, $context);
    }

    public function buildTermQuery(Context $context, Criteria $criteria): BuilderInterface
    {
        $query = $this->inner->buildTermQuery($context, $criteria);

        // Get the fuzzy search configuration
        $salesChannelId = $this->resolveSalesChannelId($context);
        $fuzzinessConfig = $this->systemConfigService->getString(
            ConfigConstants::SEARCH_FUZZINESS,
            $salesChannelId
        );

        // If no config is set, use the default (disabled)
        if ($fuzzinessConfig === null || $fuzzinessConfig === '') {
            $fuzzinessConfig = ConfigConstants::SEARCH_FUZZINESS_DISABLED;
        }

        // Modify the query based on configuration
        $modifiedQuery = $this->modifyQueryFuzziness($query, $fuzzinessConfig);

        return $modifiedQuery ?? $query;
    }

    /**
     * Recursively modify fuzziness in query by working with array representation
     */
    private function modifyQueryFuzziness(BuilderInterface $query, string $fuzzinessConfig): BuilderInterface
    {
        // Get the array representation
        $queryArray = $query->toArray();

        // Modify the array
        $modifiedArray = $this->modifyArrayFuzziness($queryArray, $fuzzinessConfig);

        // If nothing changed, return original query
        if ($modifiedArray === $queryArray) {
            return $query;
        }

        // Try to reconstruct the query from the modified array
        // For most queries, we can just return the original with modified internals
        // The query will serialize the modified array when toArray() is called again
        return new class($modifiedArray) implements BuilderInterface {
            public function __construct(private array $queryArray) {}

            public function toArray(): array
            {
                return $this->queryArray;
            }

            public function getType(): string
            {
                return array_key_first($this->queryArray) ?? 'unknown';
            }
        };
    }

    /**
     * Recursively modify fuzziness parameters in the query array
     */
    private function modifyArrayFuzziness(array $queryArray, string $fuzzinessConfig): array
    {
        foreach ($queryArray as $key => &$value) {
            if (is_array($value)) {
                // Check if this is a match query with fuzziness
                if ($key === 'match' && isset($value)) {
                    foreach ($value as $field => &$params) {
                        if (is_array($params) && isset($params['fuzziness'])) {
                            if ($fuzzinessConfig === ConfigConstants::SEARCH_FUZZINESS_DISABLED) {
                                // Remove fuzziness parameter
                                unset($params['fuzziness']);
                            } else {
                                // Set fuzziness to configured value
                                $params['fuzziness'] = (int) $fuzzinessConfig;
                            }
                        }
                    }
                } else {
                    // Recursively process nested arrays
                    $value = $this->modifyArrayFuzziness($value, $fuzzinessConfig);
                }
            }
        }

        return $queryArray;
    }

    /**
     * Resolve the sales channel ID from context or request
     */
    private function resolveSalesChannelId(Context $context): ?string
    {
        // Try to get from context if it's a SalesChannelContext
        if ($context instanceof SalesChannelContext) {
            return $context->getSalesChannelId();
        }

        // Try to get from request
        $request = $this->requestStack->getCurrentRequest();
        if ($request !== null) {
            return $request->get('salesChannelId');
        }

        return null;
    }
}
