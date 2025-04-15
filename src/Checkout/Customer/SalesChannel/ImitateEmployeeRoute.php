<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Checkout\Customer\SalesChannel;

use Shopware\Core\Framework\Context;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Validation\DataValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Shopware\Core\Framework\Validation\Constraint\Uuid;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\Context\CartRestorer;
use Shopware\Core\System\SalesChannel\ContextTokenResponse;
use Shopware\Core\Framework\Validation\BuildValidationEvent;
use Shopware\Core\Checkout\Customer\Event\CustomerLoginEvent;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Shopware\Core\Checkout\Customer\SalesChannel\AccountService;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Checkout\Customer\ImitateCustomerTokenGenerator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractLogoutRoute;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\DataAbstractionLayer\Validation\EntityExists;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Employee\EmployeeEntity;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Commercial\B2B\EmployeeManagement\Domain\Login\EmployeeCartRestorer;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Torq\Shopware\Common\Checkout\Customer\SalesChannel\AbstractImitateEmployeeRoute;

#[Route(defaults: ['_routeScope' => ['store-api'], '_contextTokenRequired' => false])]
class ImitateEmployeeRoute extends AbstractImitateEmployeeRoute
{
    final public const TOKEN = 'token';
    final public const CUSTOMER_ID = 'customerId';
    final public const EMPLOYEE_ID = 'employeeId';
    final public const USER_ID = 'userId';

    /**
     * @internal
     */
    public function __construct(
        private readonly AccountService $accountService,
        private readonly ImitateCustomerTokenGenerator $imitateCustomerTokenGenerator,
        private readonly AbstractLogoutRoute $logoutRoute,
        private readonly AbstractSalesChannelContextFactory $salesChannelContextFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly DataValidator $validator,
        private readonly EntityRepository $employeeRepository,
        private readonly SalesChannelContextPersister $salesChannelContextPersister,
        private readonly EmployeeCartRestorer $employeeCartRestorer,
        private readonly CartRestorer $restorer        
    ) {
    }

    public function getDecorated(): AbstractImitateEmployeeRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/account/login/imitate-employee', name: 'store-api.account.imitate-employee-login', methods: ['POST'])]
    public function imitateEmployeeLogin(RequestDataBag $requestDataBag, SalesChannelContext $context): ContextTokenResponse
    {
        $this->validateRequestDataFields($requestDataBag, $context->getContext());

        // TODO - just log out the employee
        $customerId = $requestDataBag->getString(self::CUSTOMER_ID);
        if ($context->getCustomerId() === $customerId) {
            return new ContextTokenResponse($context->getToken());
        }

        $token = $requestDataBag->getString(self::TOKEN);

        //who we are imitating
        $employeeId = $requestDataBag->getString(self::EMPLOYEE_ID);
        $userId = $requestDataBag->getString(self::USER_ID);

        $this->imitateCustomerTokenGenerator->validate($token, $context->getSalesChannelId(), $customerId, $userId);

        if ($context->getCustomer()) {
            $newTokenResponse = $this->logoutRoute->logout($context, new RequestDataBag());
            $context = $this->salesChannelContextFactory->create($newTokenResponse->getToken(), $context->getSalesChannelId());
        }

        $employee = $this->getCustomerByEmployeeIdLogin($employeeId, $context);

        if ($employee === null) {
            return new ContextTokenResponse($context->getToken());
        }

        /** @var CustomerEntity $customer */
        $customer = $employee->getBusinessPartnerCustomer();
        $context->setImitatingUserId($customer->getId());

        $b2bToken = $this->employeeCartRestorer->loadEmployeeToken($customer->getId(), $employee->getId(), $context);
        $context = $this->restorer->restoreByToken($b2bToken, $customer->getId(), $context);
        $newToken = $context->getToken();

        $event = new CustomerLoginEvent($context, $customer, $newToken);
        $this->eventDispatcher->dispatch($event);

        $contextTokenResponse = new ContextTokenResponse($newToken);
        $this->salesChannelContextPersister->save(
            $contextTokenResponse->getToken(),
            ['employeeId' => $employee->getId()],
            $context->getSalesChannelId()
        );

        return new ContextTokenResponse($newToken);
    }

    private function getCustomerByEmployeeIdLogin(string $id, SalesChannelContext $context): ?EmployeeEntity
    {
        $criteria = new Criteria([$id]);
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
            new EqualsFilter('businessPartnerCustomer.boundSalesChannelId', $context->getSalesChannelId()),
            new EqualsFilter('businessPartnerCustomer.boundSalesChannelId', null),
        ]));
        $criteria->addAssociation('businessPartnerCustomer');

        /** @var EmployeeEntity|null $employee */
        $employee = $this->employeeRepository->search($criteria, $context->getContext())->first();

        return $employee;
    }

    /**
     * @throws ConstraintViolationException
     */
    private function validateRequestDataFields(DataBag $data, Context $context): void
    {
        $definition = new DataValidationDefinition('impersonation.login');

        $definition
            ->add(self::TOKEN, new NotBlank())
            ->add(self::CUSTOMER_ID, new Uuid(), new EntityExists(['entity' => 'customer', 'context' => $context]))
            ->add(self::USER_ID, new Uuid(), new EntityExists(['entity' => 'user', 'context' => $context]))
            ->add(self::EMPLOYEE_ID, new Uuid(), new EntityExists(['entity' => 'b2b_employee', 'context' => $context]));

        $validationEvent = new BuildValidationEvent($definition, $data, $context);
        $this->eventDispatcher->dispatch($validationEvent, $validationEvent->getName());

        $this->validator->validate($data->all(), $definition);
    }
}
