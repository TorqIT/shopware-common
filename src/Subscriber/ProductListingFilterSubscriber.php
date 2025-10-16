<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Subscriber;

use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Content\Product\SalesChannel\Listing\Filter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Content\Product\Events\ProductListingCollectFilterEvent;
use Shopware\Core\Content\Product\Events\ProductListingResultEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
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
            ProductListingCollectFilterEvent::class => 'addFilter',
            ProductListingResultEvent::class => 'filterResult'
        ];
    }

    public function addFilter(ProductListingCollectFilterEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();

        //Category filter
        $categoryFilterActive = $this->systemConfigService->get('TorqShopwareCommon.config.categoryFilter', $salesChannelId);;
        if($categoryFilterActive){
            $this->addCategoryFilter($event);
        }
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
            [new EntityAggregation('category', 'product.categoryTree', 'category')],

            // defines the DAL filter which should be added to the criteria   
            new EqualsAnyFilter('product.categoryTree', $categoryIds),

            // defines the values which will be added as currentFilter to the result
            $categoryIds
        );

        // Add your custom filter
        $filters->add($filter);
    }

    public function filterResult(ProductListingResultEvent $event): void
    {
        $request = $event->getRequest();

        $isInStockFiltered = $request->get(self::INSTOCK_FILTER) === "1";
        if (!$isInStockFiltered) {
            return;
        }

        $result = $event->getResult();
        $products = $result->getElements();
        $entities = $result->getEntities();

        // Filter elements and collect IDs of in-stock products in one pass
        $filteredElements = [];
        $inStockIds = [];
        
        foreach ($products as $key => $product) {
            /** @var \Shopware\Core\Content\Product\ProductEntity $product */
            if ($product->getStock() > 0) {
                $filteredElements[$key] = $product;
                $inStockIds[] = $product->getId();
            }
        }

        // Filter entities using the collected ids
        $filteredEntities = $entities->filter(function ($entity) use ($inStockIds) {
            /** @var \Shopware\Core\Content\Product\ProductEntity $entity */
            return in_array($entity->getId(), $inStockIds, true);
        });

        //update the products and entities along with the total
        $result->assign([
            'elements' => $filteredElements, 
            'entities' => $filteredEntities,
            'total' => count($filteredElements)
        ]);
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