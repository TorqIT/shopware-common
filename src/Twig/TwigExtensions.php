<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Twig;

use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
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
            new TwigFunction('getCategoryTree', [$this, 'getCategoryTree'])
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
    public function getCategoryTree(string $categoryId, Context $context){

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('parentId', $categoryId));
        $criteria->addAssociations(['children', 'children.children', 'children.children.children']);
        $categories = $this->categoryRepository->search($criteria, $context);

        return $categories;
    }
    
}