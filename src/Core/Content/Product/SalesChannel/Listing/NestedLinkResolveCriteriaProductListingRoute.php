<?php

namespace Torq\Shopware\Common\Core\Content\Product\SalesChannel\Listing;

use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerEntity;
use Shopware\Core\Content\Product\SalesChannel\Listing\ResolveCriteriaProductListingRoute;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingRouteResponse;
use Shopware\Core\Content\Property\PropertyGroupEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\EntityResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Torq\Shopware\Common\Constants\FilterConstants;

class NestedLinkResolveCriteriaProductListingRoute extends ResolveCriteriaProductListingRoute {

    public function __construct(
        private readonly ResolveCriteriaProductListingRoute $decorated,
        private readonly SystemConfigService $systemConfigService,
        private readonly EntityRepository $propertyGroupRepository,
        private readonly EntityRepository $manufacturerRepository,
    ) {
    }

    public function getDecorated(): ResolveCriteriaProductListingRoute
    {
        return $this->decorated;
    }

    public function load(string $categoryId, Request $request, SalesChannelContext $context, Criteria $criteria): ProductListingRouteResponse
    {
        $result = $this->decorated->load($categoryId, $request, $context, $criteria);

        if($this->systemConfigService->get('TorqShopwareCommon.config.categoryFilterMode', $context->getSalesChannelId()) == FilterConstants::CATEGORY_FILTER_MODE_NESTED_LINKS){
            $this->mergeMissingProperties($request, $result, $context);
            $this->mergeMissingManufacturers($request, $result, $context);
        }

        return $result;
    }

    private function mergeMissingProperties(Request $request, ProductListingRouteResponse $result, SalesChannelContext $context): void
    {
        $properties = $request->query->get('properties', null);

        if(!$properties){
            return;
        }

        $propertyIds = explode('|', $properties);

        foreach($propertyIds as $prop){
            if(!Uuid::isValid($prop)){
                return;
            }
        }

        $productListingResult = $result->getResult();
        
        $propertyAgg = $productListingResult->getAggregations()->get('properties');
        
        if(!$propertyAgg || !$propertyAgg instanceof EntityResult) {
            $collection = new EntityCollection();
            $propertyAgg = new EntityResult('properties', $collection);
            $productListingResult->getAggregations()->remove('properties');
            $productListingResult->getAggregations()->add($propertyAgg);
        }

        $missingPropertyIds = array_diff($propertyIds, $propertyAgg->getEntities()->getIds());

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('options.id', $missingPropertyIds));
        $criteria->addAssociation('options');
        $criteria->getAssociation('options')
            ->addFilter(new EqualsAnyFilter('id', $missingPropertyIds))
            ->addSorting(new FieldSorting('position', FieldSorting::ASCENDING));
    

        $properties = $this->propertyGroupRepository->search($criteria, $context->getContext())->getEntities();

        $propertyAgg->getEntities()->merge($properties);

        $propertyAgg->getEntities()->sort(function(PropertyGroupEntity $a, PropertyGroupEntity $b){
            return $a->getPosition() ?? PHP_INT_MAX <=> $b->getPosition() ?? PHP_INT_MAX;
        });

    }

    private function mergeMissingManufacturers(Request $request, ProductListingRouteResponse $result, SalesChannelContext $context): void
    {
        $manufacturers = $request->query->get('manufacturer', null);

        if(!$manufacturers){
            return;
        }
        
        $manufacturerIds = explode('|', $manufacturers);

        foreach($manufacturerIds as $man){
            if(!Uuid::isValid($man)){
                return;
            }
        }

        $manufacturerAgg = $result->getResult()->getAggregations()->get('manufacturer');
        \assert($manufacturerAgg instanceof EntityResult || $manufacturerAgg === null);
        if(!$manufacturerAgg){
            return;
        }

        $missingManufacturerIds = array_diff($manufacturerIds, $manufacturerAgg->getEntities()->getIds());

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('id', $missingManufacturerIds));
        $criteria->addSorting(new FieldSorting('name', FieldSorting::ASCENDING));

        $manufacturers = $this->manufacturerRepository->search($criteria, $context->getContext())->getEntities();

        $manufacturerAgg->getEntities()->merge($manufacturers);

        $manufacturerAgg->getEntities()->sort(function(ProductManufacturerEntity $a, ProductManufacturerEntity $b){
            return $a->getName() <=> $b->getName();
        });
    }

}