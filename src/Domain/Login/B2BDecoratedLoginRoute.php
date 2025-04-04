<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Domain\Login;

use Shopware\Commercial\B2B\EmployeeManagement\Entity\Employee\EmployeeEntity;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractLoginRoute;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\CartRestorer;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Shopware\Core\System\SalesChannel\ContextTokenResponse;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Torq\Shopware\Common\SalesChannel\Context\SalesChannelContextPersisterDecorator;

/**
 * @internal
 */
#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('buyers-experience')]
class B2BDecoratedLoginRoute extends AbstractLoginRoute
{
    /**
     * @internal
     */
    public function __construct(
        private readonly AbstractLoginRoute $decorated,
        private readonly EntityRepository $employeeRepository,
        private readonly CartRestorer $restorer,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly SalesChannelContextPersister $contextPersister
    ) {
    }

    public function getDecorated(): AbstractLoginRoute
    {
        return $this->decorated;
    }

    #[Route(path: '/store-api/account/login', name: 'store-api.account.login', methods: ['POST'])]
    public function login(RequestDataBag $data, SalesChannelContext $context): ContextTokenResponse
    {
        $email = $data->get('email', $data->get('username'));
        $employee = $this->getEmployee($context, $email);

        if($employee != null){
            SalesChannelContextPersisterDecorator::setEmployee($employee);
        }
        else{
            return $this->decorated->login($data, $context);
        }

        $token = $this->decorated->login($data, $context);

        $this->contextPersister->save($token->getToken(), ['employeeId' => $employee->getId()], $context->getSalesChannel()->getId());
        return $token;
    }

    private function getEmployee(SalesChannelContext $context, string $email): ?EmployeeEntity{
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('email', $email));
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
            new EqualsFilter('businessPartnerCustomer.boundSalesChannelId', $context->getSalesChannelId()),
            new EqualsFilter('businessPartnerCustomer.boundSalesChannelId', null),
        ]));

        $criteria->addAssociation('businessPartnerCustomer');

        /** @var EmployeeEntity|null $employee */
        $employee = $this->employeeRepository->search($criteria, $context->getContext())->first();

        return $employee;
    }

}