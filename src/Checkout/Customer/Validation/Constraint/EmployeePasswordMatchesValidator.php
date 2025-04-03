<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Checkout\Customer\Validation\Constraint;

use Shopware\Core\Framework\Log\Package;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Checkout\Customer\Exception\BadCredentialsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Employee\EmployeeEntity;
use Shopware\Commercial\B2B\EmployeeManagement\Exception\EmployeeManagementException;

#[Package('checkout')]
class EmployeePasswordMatchesValidator extends ConstraintValidator
{
    /**
     * @internal
     */
    public function __construct(private readonly EntityRepository $employeeRepository)
    {
    }

    public function validate(mixed $password, Constraint $constraint): void
    {
        if (!$constraint instanceof EmployeePasswordMatches) {
            return;
        }

        $context = $constraint->getContext();

        try {
            /** @var EmployeeEntity $b2bEmployee */
            $b2bEmployee = $context->getExtension("b2bEmployee");
            $email = $b2bEmployee->getEmail();

            //Get the employee from the db and verify the password matches
            $dbEmployee = $this->getEmployee($context, $email);

            if ($dbEmployee->getPassword() === null
                || !password_verify($password, $dbEmployee->getPassword())) {
                throw EmployeeManagementException::badCredentials();
            }

            return;
        } catch (BadCredentialsException) {
            $this->context->buildViolation($constraint->message)
                ->setCode(EmployeePasswordMatches::CUSTOMER_PASSWORD_NOT_CORRECT)
                ->addViolation();
        }
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
        return $this->employeeRepository->search($criteria, $context->getContext())->first();
    }
}
