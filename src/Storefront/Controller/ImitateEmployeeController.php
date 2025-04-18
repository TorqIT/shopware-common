<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Storefront\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Checkout\Customer\Exception\CustomerNotFoundByIdException;
use Shopware\Core\Checkout\Customer\Exception\InvalidImitateCustomerTokenException;
use Torq\Shopware\Common\Checkout\Customer\SalesChannel\AbstractImitateEmployeeRoute;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class ImitateEmployeeController extends StorefrontController
{
    /**
     * @internal
     */
    public function __construct(private readonly AbstractImitateEmployeeRoute $imitateEmployeeRoute) {
    }

    #[Route(path: '/account/login/imitate-employee', name: 'frontend.account.login.imitate-employee', methods: ['POST'])]
    public function imitateEmployeeLogin(RequestDataBag $data, SalesChannelContext $context): Response
    {
        try {
            $this->imitateEmployeeRoute->imitateEmployeeLogin($data, $context);

            return $this->redirectToRoute('frontend.account.home.page');
        } catch (InvalidImitateCustomerTokenException|CustomerNotFoundByIdException) {
            return $this->forwardToRoute(
                'frontend.account.login.page',
                [
                    'loginError' => true,
                ]
            );
        }
    }
    
}
