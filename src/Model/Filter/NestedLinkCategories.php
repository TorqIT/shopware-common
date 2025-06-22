<?php

namespace Torq\Shopware\Common\Model\Filter;

use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryEntity;

class NestedLinkCategories
{
    public function __construct(
        private readonly CategoryEntity $category,
        private readonly CategoryCollection $parentCategories,
        private readonly ?CategoryCollection $childCategories,
    ) {

    }
    public function getCategory(): CategoryEntity
    {
        return $this->category;
    }

    public function getParentCategories(): CategoryCollection
    {
        return $this->parentCategories;
    }

    public function getChildCategories(): ?CategoryCollection
    {
        return $this->childCategories;
    }

    public function setCategory(CategoryEntity $category): void
    {
        $this->category = $category;
    }

    public function setParentCategories(CategoryCollection $parentCategories): void
    {
        $this->parentCategories = $parentCategories;
    }

    public function setChildCategories(?CategoryCollection $childCategories): void
    {
        $this->childCategories = $childCategories;
    }    
    
}