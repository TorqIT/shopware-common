<?php

namespace Torq\Shopware\Common\Storefront\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Content\Product\SalesChannel\Search\AbstractProductSearchRoute;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class QuickAddController extends StorefrontController
{
    public function __construct(
        private SalesChannelRepository $salesChannelProductRepository,
        private SystemConfigService $systemConfigService,
        private AbstractProductSearchRoute $productSearchRoute
    ) {
    }

    #[Route(
        path: '/checkout/cart/quickadd/autocomplete',
        name: 'frontend.quickadd.autocomplete',
        methods: ['GET'],
        defaults: ['XmlHttpRequest' => true]
    )]
    public function getResults(Request $request, SalesChannelContext $context): Response
    {
        $advancedSearchEnabled = $this->systemConfigService->getBool('TorqShopwareCommon.config.quickAddAdvancedSearchEnabled', $context->getSalesChannelId());
        $term = $request->get('term');

        $criteria = new Criteria();
        $criteria->setLimit(20);
        $criteria->addFilter(new MultiFilter(
            MultiFilter::CONNECTION_OR,
            [
                new EqualsFilter('childCount', 0),
                new EqualsFilter('childCount', null),
            ]
        ));

        if ($advancedSearchEnabled) {
            $searchRequest = clone $request;
            $searchRequest->query->set('search', $term);
            $products = $this->productSearchRoute->load($searchRequest, $context, $criteria)->getListingResult()->getEntities();
        } else {
            $criteria->addFilter(new ContainsFilter('productNumber', $term));
            $products = $this->salesChannelProductRepository->search($criteria, $context)->getEntities();
        }

        return $this->renderStorefront('@Storefront/storefront/component/checkout/quick-add-autocomplete.html.twig', ['products' => $products->getElements()]);
    }
}
