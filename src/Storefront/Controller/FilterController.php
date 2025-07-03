<?php

namespace Torq\Shopware\Common\Storefront\Controller;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Torq\Shopware\Common\Service\Filter\SearchCategoryFilterBuilder;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class FilterController extends StorefrontController
{
    public function __construct(private readonly SearchCategoryFilterBuilder $searchCategoryFilterBuilder)
    {
    }

    #[Route(path: '/filter/search-category-filter', name: 'frontend.filter.search-category-filter', methods: ['POST'], defaults: ['XmlHttpRequest' => true])]
    public function searchCategoryFilter(Request $request, SalesChannelContext $context): Response
    {
        $categoryId = $request->request->get('categoryId');
        $categoryIds = $request->request->all('categoryIds');
        $categoryLinks = $this->searchCategoryFilterBuilder->build($categoryId, $categoryIds, $context);

        return $this->renderStorefront('@TorqShopwareCommon/storefront/component/listing/search-category-filter.html.twig', [
            'categoryLinks' => $categoryLinks,
        ]);
    }
}