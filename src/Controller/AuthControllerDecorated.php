<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Controller;

use Shopware\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Shopware\Storefront\Controller\AuthController;
use Shopware\Core\Framework\Routing\RoutingException;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Framework\Routing\RequestTransformer;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Storefront\Page\Account\Login\AccountLoginPageLoader;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractLoginRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractLogoutRoute;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Shopware\Core\Checkout\Customer\Exception\BadCredentialsException;
use Shopware\Storefront\Page\Account\Login\AccountLoginPageLoadedHook;
use Shopware\Core\Checkout\Customer\Exception\CustomerNotFoundException;
use Shopware\Storefront\Checkout\Cart\SalesChannel\StorefrontCartFacade;
use Shopware\Storefront\Page\Account\Login\AccountGuestLoginPageLoadedHook;
use Shopware\Core\Checkout\Customer\Exception\CustomerNotFoundByIdException;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractResetPasswordRoute;
use Shopware\Core\Checkout\Customer\Exception\CustomerAuthThrottledException;
use Shopware\Core\Framework\RateLimiter\Exception\RateLimitExceededException;
use Shopware\Core\Checkout\Customer\Exception\CustomerNotFoundByHashException;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractImitateCustomerRoute;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\Checkout\Customer\Exception\PasswordPoliciesUpdatedException;
use Shopware\Core\Checkout\Customer\Exception\CustomerOptinNotCompletedException;
use Shopware\Core\Checkout\Customer\Exception\CustomerRecoveryHashExpiredException;
use Shopware\Core\Checkout\Customer\Exception\InvalidImitateCustomerTokenException;
use Shopware\Storefront\Page\Account\RecoverPassword\AccountRecoverPasswordPageLoader;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractSendPasswordRecoveryMailRoute;
use Shopware\Storefront\Page\Account\RecoverPassword\AccountRecoverPasswordPageLoadedHook;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;

/**
 * @internal
 * Do not use direct or indirect repository calls in a controller. Always use a store-api route to get or put data
 */
#[Route(defaults: ['_routeScope' => ['storefront']])]
#[Package('storefront')]
class AuthControllerDecorated extends AuthController
{
    /**
     * @internal
     */
    public function __construct(
        private readonly AuthController $decorated,
        private readonly AccountLoginPageLoader $loginPageLoader,
        private readonly AbstractSendPasswordRecoveryMailRoute $sendPasswordRecoveryMailRoute,
        private readonly AbstractResetPasswordRoute $resetPasswordRoute,
        private readonly AbstractLoginRoute $loginRoute,
        private readonly AbstractLogoutRoute $logoutRoute,
        private readonly AbstractImitateCustomerRoute $imitateCustomerRoute,
        private readonly StorefrontCartFacade $cartFacade,
        private readonly AccountRecoverPasswordPageLoader $recoverPasswordPageLoader
    ) {
        parent::__construct($loginPageLoader,$sendPasswordRecoveryMailRoute,$resetPasswordRoute,
                            $loginRoute,$logoutRoute,$imitateCustomerRoute,$cartFacade,$recoverPasswordPageLoader);
    }

    #[Route(path: '/account/login', name: 'frontend.account.login.page', defaults: ['_noStore' => true], methods: ['GET'])]
    public function loginPage(Request $request, RequestDataBag $data, SalesChannelContext $context): Response
    {
        $customer = $context->getCustomer();
        /*
            This is a work around so when an employee logs in with an invalid password (loginError = true) they will go back to the login screen as expected.
            For some reason the Customer and Employee get set in the Context even though the login failed.
        */
        if ($customer !== null && $customer->getGuest() === false && $request->attributes->get("loginError")) {
            $context->getCustomer()->setGuest(true); 
        }

        return $this->decorated->loginPage($request,$data,$context);
    }
}
