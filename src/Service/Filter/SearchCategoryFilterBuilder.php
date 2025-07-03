<?php

namespace Torq\Shopware\Common\Service\Filter;

use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Torq\Shopware\Common\Model\Filter\NestedLinkCategories;

class SearchCategoryFilterBuilder
{
    public function __construct(private readonly EntityRepository $categoryRepository)
    {
    }

    public function build(?string $categoryId, array $categoryIds, SalesChannelContext $context): NestedLinkCategories
    {        
        if($categoryId == null){
            return $this->buildNullCategory($categoryIds, $context);
        }

        $criteria = new Criteria([$categoryId]);
        $criteria->setTitle('Nested Link Categories');
        $criteria->addAssociations(['children']);
        $childrenAssociation = $criteria->getAssociation('children');
        $childrenAssociation->addFilter(new EqualsAnyFilter('id', array_values($categoryIds)));
        
        /** @var CategoryEntity $category */
        $category = $this->categoryRepository->search($criteria, $context->getContext())->first();

        $parents = [];

        $parentId = $category->getParentId();
        
        while($parentId){
            /** @var CategoryEntity $parent */
            $parent = $this->categoryRepository->search(new Criteria([$parentId]), $context->getContext())->first();
            $parents[] = $parent;
            $parentId = $parent->getParentId();
        }

        $parents = array_slice(array_reverse($parents),1);

        $parentCollection = new CategoryCollection();
        $parentCollection->fill($parents);

        $children = $category->getChildren();

        return new NestedLinkCategories($category, $parentCollection, $children);
    }

    private function buildNullCategory(array $categoryIds, SalesChannelContext $context): NestedLinkCategories
    {
        $salesChannel = $context->getSalesChannel();
        $navId = $salesChannel->getNavigationCategoryId();
        
        $criteria = new Criteria([$navId]);
        $criteria->addAssociation('children');
        $childrenAssociation = $criteria->getAssociation('children');
        $childrenAssociation->addFilter(new EqualsAnyFilter('id', array_values($categoryIds)));
        
        $categories = $this->categoryRepository->search($criteria, $context->getContext());

        /** @var CategoryEntity $category */
        $category = $categories->first();

        return new NestedLinkCategories(null, $category->getChildren(), null);
    }
}