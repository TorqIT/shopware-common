<?php

namespace Torq\Shopware\Common\Core\Content\Product\SalesChannel\Listing\Filter;

use Doctrine\DBAL\ArrayParameterType;
use Shopware\Core\Content\Product\SalesChannel\Listing\Filter;
use Shopware\Core\Content\Product\SalesChannel\Listing\Filter\PropertyListingFilterHandler;
use Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingResult;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntitySearchedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Uuid\Uuid;

class PropertyListingFilterHandlerDecorator extends PropertyListingFilterHandler implements EventSubscriberInterface
{
    private array $productIds = [];

    private const CRITERIA_TITLE = 'product-listing::property-filter';

    public function __construct(
        private readonly Connection $connection,
        private readonly SystemConfigService $systemConfigService,
        private readonly PropertyListingFilterHandler $decorated,
        private readonly EntityRepository $productRepository
    ) {
    }

    public static function getSubscribedEvents() { 
        return [
            EntitySearchedEvent::class => 'processEntitySearchedEvent',
        ];
    }
    
    public function getDecorated(): PropertyListingFilterHandler
    {
        return $this->decorated;
    }

    public function create(Request $request, SalesChannelContext $context): ?Filter
    {
        return $this->decorated->create($request, $context);
    }

    public function process(Request $request, ProductListingResult $result, SalesChannelContext $context): void
    {
        if(!$this->systemConfigService->getBool('TorqShopwareCommon.config.restrictPropertiesOnListing')) {
            $this->decorated->process($request, $result, $context);
            return;
        }

        // $result only holds the current page of products (EntitySearchResult wraps the
        // paginated entities, not the full matching total), so restricting against
        // $result->getIds() would shrink/change the available properties as the shopper
        // paginates. Re-run the listing's own criteria without pagination to get every
        // product id matching the current category/filters instead.
        $this->productIds = $this->getMatchingProductIds($result, $context);

        $this->decorated->process($request, $result, $context);

        $this->productIds = [];
    }

    private function getMatchingProductIds(ProductListingResult $result, SalesChannelContext $context): array
    {
        $criteria = clone $result->getCriteria();
        $criteria->setLimit(null);
        $criteria->setOffset(0);
        $criteria->resetSorting();

        return $this->productRepository->searchIds($criteria, $context->getContext())->getIds();
    }

    public function processEntitySearchedEvent(EntitySearchedEvent $event): void
    {
        if(!$this->systemConfigService->getBool('TorqShopwareCommon.config.restrictPropertiesOnListing')) {
            return;
        }

        $criteria = $event->getCriteria();

        if($criteria->getTitle() !== self::CRITERIA_TITLE) {
            return;
        }

        //had to opt for raw SQL for performance reasons
        $ids = $this->getOptionIds(array_values($this->productIds));
        $criteria->addFilter(new EqualsAnyFilter('id', $ids));
    }

    private function getOptionIds(array $productIds): array
    {
        $productIdsHex = array_map(fn($id) => Uuid::fromHexToBytes($id), $productIds);
        
        $sql = <<<SQL

        SELECT
            LOWER(HEX(product_option.property_group_option_id)) AS id
        FROM
            product_option
        JOIN
            property_group_option
        ON
            product_option.property_group_option_id = property_group_option.id
        JOIN
            property_group
        ON
            property_group.id = property_group_option.property_group_id
        WHERE 
            product_option.product_id IN (:productIds)
            AND
            property_group.filterable = 1

        UNION 

        SELECT
            LOWER(HEX(product_property.property_group_option_id)) AS id
        FROM
            product_property
        JOIN
            property_group_option
        ON
            product_property.property_group_option_id = property_group_option.id
        JOIN
            property_group 
        ON
            property_group.id = property_group_option.property_group_id
        WHERE 
            product_property.product_id IN (:productIds)
            AND
            property_group.filterable = 1
        SQL;

        $stmt = $this->connection->executeQuery($sql, ['productIds' => $productIdsHex], ['productIds' => ArrayParameterType::BINARY]);
        return $stmt->fetchFirstColumn();
    }

}