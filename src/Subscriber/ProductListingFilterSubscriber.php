<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Subscriber;

use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Content\Product\SalesChannel\Listing\Filter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Content\Product\Events\ProductListingCollectFilterEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\MaxAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\EntityAggregation;

class ProductListingFilterSubscriber implements EventSubscriberInterface
{
    public const CATEGORY_FILTER    = 'category';
    public const INSTOCK_FILTER     = 'instock';

    public function __construct(private SystemConfigService $systemConfigService)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductListingCollectFilterEvent::class => 'addFilter'
        ];
    }

    public function addFilter(ProductListingCollectFilterEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();

        //Category filter
        $categoryFilterActive = $this->systemConfigService->get('TorqShopwareCommon.config.categoryFilter', $salesChannelId);;
        if($categoryFilterActive)
            $this->addCategoryFilter($event);

        //In Stock filter
        $instockFilterActive = $this->systemConfigService->get('TorqShopwareCommon.config.instockFilter', $salesChannelId);;
        if($instockFilterActive)
            $this->addInstockFilter($event);
    }

    private function addCategoryFilter(ProductListingCollectFilterEvent $event): void {
        // fetch existing filters
        $filters = $event->getFilters();
        $request = $event->getRequest();

        $categoryIds = $this->getCategoryIds($request);

        $filter = new Filter(
            // unique name of the filter
            self::CATEGORY_FILTER,

            // defines if this filter is active
            !empty($categoryIds),

            // Defines aggregations behind a filter. A filter can contain multiple aggregations like properties
            [new EntityAggregation('category', 'product.categoryIds', 'category')],

            // defines the DAL filter which should be added to the criteria   
            new EqualsAnyFilter('product.categoryTree', $categoryIds),

            // defines the values which will be added as currentFilter to the result
            $categoryIds
        );

        // Add your custom filter
        $filters->add($filter);
    }

    private function addInstockFilter(ProductListingCollectFilterEvent $event): void {
        // fetch existing filters
        $filters = $event->getFilters();
        $request = $event->getRequest();

        //Check if filter is selected or not
        $isInStockFiltered = $request->query->get(self::INSTOCK_FILTER);
        if ($request->isMethod(Request::METHOD_POST)) {
            $isInStockFiltered = $request->request->get(self::INSTOCK_FILTER);
        }
        $isInStockFiltered = $isInStockFiltered && $isInStockFiltered === "1" ? true:false;

        $inStockFilter = new Filter(
            'instock',
            $isInStockFiltered,
            [
                new FilterAggregation(
                    'instockFilter',
                    new MaxAggregation('instock', 'product.stock'),
                    [
                        new RangeFilter('product.stock', [RangeFilter::GT => 0])
                    ]
                ),
            ],
            new RangeFilter('product.stock', [RangeFilter::GT => 0]),
            $isInStockFiltered
        );

        $filters->add($inStockFilter);
    }

    /**
     * @return list<string>
     */
    private function getCategoryIds(Request $request): array
    {
        $ids = $request->query->get(self::CATEGORY_FILTER, '');
        if ($request->isMethod(Request::METHOD_POST)) {
            $ids = $request->request->get(self::CATEGORY_FILTER, '');
        }

        if (\is_string($ids)) {
            $ids = explode('|', $ids);
        }

        /** @var list<string> $ids */
        $ids = array_filter((array) $ids);

        return $ids;
    }

}