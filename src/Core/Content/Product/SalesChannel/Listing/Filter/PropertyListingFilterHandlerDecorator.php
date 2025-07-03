<?php

namespace Torq\Shopware\Common\Core\Content\Product\SalesChannel\Listing\Filter;

use Doctrine\DBAL\ArrayParameterType;
use Shopware\Core\Content\Product\SalesChannel\Listing\Filter;
use Shopware\Core\Content\Product\SalesChannel\Listing\Filter\PropertyListingFilterHandler;
use Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingResult;
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
        private readonly PropertyListingFilterHandler $decorated
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

        $this->productIds = $result->getIds();
        
        $this->decorated->process($request, $result, $context);

        $this->productIds = [];
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
            HEX(product_option.property_group_option_id) AS id
        FROM 
            product_option
        WHERE 
            product_option.product_id IN (:productIds)

        UNION 

        SELECT 
            HEX(product_property.property_group_option_id) AS id
        FROM 
            product_property
        WHERE 
            product_property.product_id IN (:productIds)
        SQL;

        $stmt = $this->connection->executeQuery($sql, ['productIds' => $productIdsHex], ['productIds' => ArrayParameterType::BINARY]);
        return $stmt->fetchFirstColumn();
    }

}