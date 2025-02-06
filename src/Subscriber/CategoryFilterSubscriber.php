<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Subscriber;

use Shopware\Core\Content\Product\Events\ProductListingCollectFilterEvent;
use Shopware\Core\Content\Product\SalesChannel\Listing\Filter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\EntityAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

class CategoryFilterSubscriber implements EventSubscriberInterface
{
    public const CATEGORY_FILTER = 'category';

    public static function getSubscribedEvents(): array
    {
        return [
            ProductListingCollectFilterEvent::class => 'addFilter'
        ];
    }

    public function addFilter(ProductListingCollectFilterEvent $event): void
    {
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
            new EqualsAnyFilter('product.categoryIds', $categoryIds),

            // defines the values which will be added as currentFilter to the result
            $categoryIds
        );

        // Add your custom filter
        $filters->add($filter);
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