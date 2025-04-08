<?php

namespace Torq\Shopware\Common\Storefront\Controller;

use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class QuickAddController extends StorefrontController
{
    public function __construct(
        private SalesChannelRepository $salesChannelProductRepository,
        private SystemConfigService $systemConfigService)
    {
    }

    #[Route(
        path: '/checkout/cart/quickadd/autocomplete',
        name: 'frontend.quickadd.autocomplete',
        methods: ['GET'],
        defaults: ['XmlHttpRequest' => true]
    )]  
    public function getResults(Request $request, SalesChannelContext $context): Response
    {
        $stackableEnabled = $this->systemConfigService->getBool('TorqShopwareCommon.config.quickAddStackableEnabled', $context->getSalesChannelId());
        $term = $request->get('term');
        $criteria = new Criteria();
        $criteria->setLimit(20);
        $criteria->addFilter(new ContainsFilter('productNumber', $term));
        $criteria->addFilter(new MultiFilter(
            MultiFilter::CONNECTION_OR,
            [
                new EqualsFilter('childCount', 0),
                new EqualsFilter('childCount', null),
            ]
        ));

        $products = $this->salesChannelProductRepository->search($criteria, $context);
        
        $results = array_map(fn(SalesChannelProductEntity $x) => [
            'id' => $x->getId(),
            'name' => $x->getTranslated()['name'],
            'productNumber' => $x->getProductNumber(),
            'lineItemId' => $stackableEnabled ? $x->getId() : Uuid::randomHex(),
            'stackable'=> intval($stackableEnabled)
        ], $products->getEntities()->getElements());

        return $this->renderStorefront('@Storefront/storefront/component/checkout/quick-add-autocomplete.html.twig', ['results' => $results]);
    }
}
