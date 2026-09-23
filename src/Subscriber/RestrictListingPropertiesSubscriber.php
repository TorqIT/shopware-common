<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Subscriber;

use Shopware\Core\Content\Product\Events\ProductListingCollectFilterEvent;
use Shopware\Core\Content\Product\SalesChannel\Listing\Filter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Aggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Restricts the property filter options on a listing to the ones found on products matching
 * the currently active filters, so options that would yield no results are hidden.
 *
 * The property/option aggregations already run over every product in the listing (not just the
 * current page); active filters are only applied as post filters, which aggregations ignore.
 * Wrapping those aggregations in a FilterAggregation carrying the active filters lets
 * Elasticsearch/MySQL compute the restricted option set within the listing query itself,
 * instead of loading every matching product id and resolving options separately.
 */
class RestrictListingPropertiesSubscriber implements EventSubscriberInterface
{
    private const PROPERTY_FILTER = 'properties';

    public function __construct(private readonly SystemConfigService $systemConfigService)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // run last so filters added by other subscribers are taken into account
            ProductListingCollectFilterEvent::class => ['restrictPropertyAggregations', -1000],
        ];
    }

    public function restrictPropertyAggregations(ProductListingCollectFilterEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();
        if (!$this->systemConfigService->getBool('TorqShopwareCommon.config.restrictPropertiesOnListing', $salesChannelId)) {
            return;
        }

        $filters = $event->getFilters();

        $propertyFilter = $filters->get(self::PROPERTY_FILTER);
        if (!$propertyFilter instanceof Filter) {
            return;
        }

        $activeFilters = $filters->filtered()->getFilters();
        if (empty($activeFilters)) {
            return;
        }

        $aggregations = array_map(
            fn (Aggregation $aggregation) => $this->restrictAggregation($aggregation, $activeFilters),
            $propertyFilter->getAggregations()
        );

        $filters->add(new Filter(
            $propertyFilter->getName(),
            $propertyFilter->isFiltered(),
            $aggregations,
            $propertyFilter->getFilter(),
            $propertyFilter->getValues(),
            $propertyFilter->exclude()
        ));
    }

    private function restrictAggregation(Aggregation $aggregation, array $activeFilters): Aggregation
    {
        if ($aggregation instanceof FilterAggregation) {
            $aggregation->addFilters($activeFilters);

            return $aggregation;
        }

        return new FilterAggregation($aggregation->getName(), $aggregation, $activeFilters);
    }
}
