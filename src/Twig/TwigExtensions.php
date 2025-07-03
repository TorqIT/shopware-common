<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Twig;

use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Torq\Shopware\Common\Model\Filter\NestedLinkCategories;
use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;

class TwigExtensions extends AbstractExtension
{
    public function __construct(private EntityRepository $categoryRepository)
    {
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('json_decode', [$this, 'jsonDecode']),
            new TwigFunction('getCategoryTree', [$this, 'getCategoryTree']),
            new TwigFunction('getNestedLinkCategories', [$this, 'getNestedLinkCategories']),
            new TwigFunction('getNestedLinkCategoriesForSearch', [$this, 'getNestedLinkCategoriesForSearch'])
        ];
    }

    /**
     * Uses PHP json_decode to create a an array from a json string
     * 
     * @param string $str 
     * @return mixed 
     */
    public function jsonDecode(string $str){
        $arr = json_decode($str,true);
        return $arr;
    }

    /**
     * Nest categories by their path
     * 
     * @param CategoryCollection $categories
     * @param string $navigationCategoryId - Make sure we only nest categories that are under the navigation category
     * @return array
     */
    public function getCategoryTree(?string $categoryId, Context $context){

        if(!$categoryId){
            return new CategoryCollection();
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('parentId', $categoryId));
        $criteria->addAssociations(['children', 'children.children', 'children.children.children']);
        $categories = $this->categoryRepository->search($criteria, $context);

        return $categories;
    }

    public function getNestedLinkCategories(string $categoryId, Context $context): NestedLinkCategories{

        $criteria = new Criteria([$categoryId]);
        $criteria->setTitle('Nested Link Categories');
        $criteria->addAssociations(['children']);
        
        /** @var CategoryEntity $category */
        $category = $this->categoryRepository->search($criteria, $context)->first();


        $parents = [];

        $parentId = $category->getParentId();
        
        while($parentId){
            /** @var CategoryEntity $parent */
            $parent = $this->categoryRepository->search(new Criteria([$parentId]), $context)->first();
            $parents[] = $parent;
            $parentId = $parent->getParentId();
        }

        $parents = array_slice(array_reverse($parents),1);

        $parentCollection = new CategoryCollection();
        $parentCollection->fill($parents);

        $children = $category->getChildren();
        

        return new NestedLinkCategories($category, $parentCollection, $children);
    }

    public function getNestedLinkCategoriesForSearch(?string $filteredCat, ?array $categoryIds, SalesChannelContext $context): array{

        $salesChannel = $context->getSalesChannel();
        $navId = $salesChannel->getNavigationCategoryId();

        //get all leaf categories that are under the navigation category
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('id', array_values($categoryIds)));
        $criteria->addFilter(new ContainsFilter('path', $navId));

        $categories = $this->categoryRepository->search($criteria, $context->getContext());

        //get all 2nd level categories that are ancestors of the leaf categories
        $secondLevelIds = [];
        $thirdLevelIds = [];

        /** @var CategoryEntity $category */
        foreach($categories as $category){
            $path = $category->getPath();
            $pathParts = explode('|', $path);
            if(count($pathParts) > 2 && Uuid::isValid($pathParts[2])){
                $secondLevelIds[] = $pathParts[2];
            }
            if(count($pathParts) > 3 && Uuid::isValid($pathParts[3])){
                $thirdLevelIds[] = $pathParts[3];
            }
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('id', $secondLevelIds));
        $criteria->addAssociations(['children']);
        $childrenAssociation = $criteria->getAssociation('children');
        $childrenAssociation->addFilter(new EqualsAnyFilter('id', $thirdLevelIds));

        $categories = $this->categoryRepository->search($criteria, $context->getContext());

        return $categories->getEntities()->getElements();
    }
}