<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Checkout\Customer\SalesChannel;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\Routing\Attribute\Route;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\EqualTo;
use Symfony\Component\Validator\ConstraintViolation;
use Shopware\Core\Framework\Validation\DataValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Symfony\Component\Validator\ConstraintViolationList;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\System\SalesChannel\ContextTokenResponse;
use Shopware\Core\Framework\Validation\BuildValidationEvent;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Checkout\Customer\SalesChannel\ChangePasswordRoute;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\Checkout\Customer\Validation\Constraint\CustomerPasswordMatches;
use Torq\Shopware\Common\Checkout\Customer\Validation\Constraint\EmployeePasswordMatches;

#[Route(defaults: ['_routeScope' => ['store-api'], '_contextTokenRequired' => true])]
#[Package('checkout')]
class ChangePasswordRouteDecorator extends ChangePasswordRoute
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $customerRepository,
        private readonly EntityRepository $employeeRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly SystemConfigService $systemConfigService,
        private readonly DataValidator $validator
    ) {
    }

    #[Route(path: '/store-api/account/change-password', name: 'store-api.account.change-password', methods: ['POST'], defaults: ['_loginRequired' => true])]
    public function change(RequestDataBag $requestDataBag, SalesChannelContext $context, CustomerEntity $customer): ContextTokenResponse
    {
        /** @var EmployeeEntity $b2bEmployee */
        $b2bEmployee = $context->getExtension("b2bEmployee");
        $isEmployee =  $b2bEmployee ? true:false;

        $this->validatePasswordFields($requestDataBag, $context, $isEmployee);

        $customerData = [
            'id' => $isEmployee ? $b2bEmployee->getId():$customer->getId(),
            'password' => $requestDataBag->get('newPassword'),
        ];

        if($isEmployee){
            $this->employeeRepository->update([$customerData], $context->getContext());
        }else{
            $this->customerRepository->update([$customerData], $context->getContext());
        }

        return new ContextTokenResponse($context->getToken());
    }

    private function dispatchValidationEvent(DataValidationDefinition $definition, DataBag $data, Context $context): void
    {
        $validationEvent = new BuildValidationEvent($definition, $data, $context);
        $this->eventDispatcher->dispatch($validationEvent, $validationEvent->getName());
    }

    /**
     * @throws ConstraintViolationException
     */
    private function validatePasswordFields(DataBag $data, SalesChannelContext $context, bool $isEmployee): void
    {
        $definition = new DataValidationDefinition('customer.password.update');

        $minPasswordLength = $this->systemConfigService->get('core.loginRegistration.passwordMinLength', $context->getSalesChannel()->getId());

        $definition
            ->add('newPassword', new NotBlank(), new Length(['min' => $minPasswordLength]), new EqualTo(['propertyPath' => 'newPasswordConfirm']))
            ->add('password', $isEmployee ? new EmployeePasswordMatches(['context' => $context]) : new CustomerPasswordMatches(['context' => $context]));

        $this->dispatchValidationEvent($definition, $data, $context->getContext());

        $this->validator->validate($data->all(), $definition);

        $this->tryValidateEqualtoConstraint($data->all(), 'newPassword', $definition);
    }

    /**
     * @param mixed[] $data
     */
    private function tryValidateEqualtoConstraint(array $data, string $field, DataValidationDefinition $validation): void
    {
        $validations = $validation->getProperties();

        if (!\array_key_exists($field, $validations)) {
            return;
        }

        $fieldValidations = $validations[$field];

        /** @var EqualTo|null $equalityValidation */
        $equalityValidation = null;

        foreach ($fieldValidations as $emailValidation) {
            if ($emailValidation instanceof EqualTo) {
                $equalityValidation = $emailValidation;

                break;
            }
        }

        if (!$equalityValidation instanceof EqualTo) {
            return;
        }

        $compareValue = $data[$equalityValidation->propertyPath] ?? null;
        if ($data[$field] === $compareValue) {
            return;
        }

        $message = str_replace('{{ compared_value }}', $compareValue, (string) $equalityValidation->message);

        $violations = new ConstraintViolationList();
        $violations->add(new ConstraintViolation($message, $equalityValidation->message, [], '', $field, $data[$field]));

        throw new ConstraintViolationException($violations, $data);
    }
}
