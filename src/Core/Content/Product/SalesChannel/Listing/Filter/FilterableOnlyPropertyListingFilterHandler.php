<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Core\Content\Product\SalesChannel\Listing\Filter;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Product\SalesChannel\Listing\Filter;
use Shopware\Core\Content\Product\SalesChannel\Listing\Filter\AbstractListingFilterHandler;
use Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Bucket\TermsResult;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

/**
 * Drops options of non-filterable property groups from the listing's option id aggregations
 * before core's PropertyListingFilterHandler loads them.
 *
 * Core loads every option id found in the listing (with media, in chunks of 1000) and only
 * afterwards discards the groups that are not filterable. A large non-filterable group (e.g.
 * thousands of part types on a parts category) then gets hydrated on every listing request just
 * to be thrown away. One indexed lookup up front avoids that; the rendered filters are unchanged.
 */
class FilterableOnlyPropertyListingFilterHandler extends AbstractListingFilterHandler
{
    private const OPTION_ID_AGGREGATIONS = ['properties', 'options'];

    public function __construct(
        private readonly AbstractListingFilterHandler $decorated,
        private readonly Connection $connection
    ) {
    }

    public function getDecorated(): AbstractListingFilterHandler
    {
        return $this->decorated;
    }

    public function create(Request $request, SalesChannelContext $context): ?Filter
    {
        return $this->decorated->create($request, $context);
    }

    public function process(Request $request, ProductListingResult $result, SalesChannelContext $context): void
    {
        $this->removeNonFilterableOptionIds($result);

        $this->decorated->process($request, $result, $context);
    }

    private function removeNonFilterableOptionIds(ProductListingResult $result): void
    {
        $aggregations = $result->getAggregations();

        $termsResults = [];
        $optionIds = [];
        foreach (self::OPTION_ID_AGGREGATIONS as $name) {
            $termsResult = $aggregations->get($name);
            if (!$termsResult instanceof TermsResult) {
                continue;
            }

            $termsResults[$name] = $termsResult;
            foreach ($termsResult->getKeys() as $key) {
                $optionIds[$key] = true;
            }
        }

        $optionIds = array_keys(array_filter($optionIds, static fn ($key) => Uuid::isValid((string) $key), \ARRAY_FILTER_USE_KEY));
        if ($optionIds === []) {
            return;
        }

        $filterable = array_flip($this->connection->fetchFirstColumn(
            'SELECT LOWER(HEX(property_group_option.id))
             FROM property_group_option
             INNER JOIN property_group ON property_group.id = property_group_option.property_group_id
             WHERE property_group_option.id IN (:ids)
               AND property_group.filterable = 1',
            ['ids' => Uuid::fromHexToBytesList($optionIds)],
            ['ids' => ArrayParameterType::BINARY]
        ));

        if (\count($filterable) === \count($optionIds)) {
            return;
        }

        foreach ($termsResults as $name => $termsResult) {
            $buckets = array_values(array_filter(
                $termsResult->getBuckets(),
                static fn ($bucket) => isset($filterable[strtolower((string) $bucket->getKey())])
            ));

            $aggregations->remove($name);
            $aggregations->add(new TermsResult($name, $buckets));
        }
    }
}
