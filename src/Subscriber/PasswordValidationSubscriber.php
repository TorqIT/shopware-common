<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Subscriber;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Framework\Validation\BuildValidationEvent;
use Symfony\Component\Validator\Constraints\PasswordStrength;

class PasswordValidationSubscriber implements EventSubscriberInterface
{

    public function __construct(private SystemConfigService $systemConfigService)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            "framework.validation.customer.password.update" => 'validatePassword',
            "framework.validation.customer.create" => 'validatePassword',
            "framework.validation.employee.password.update" => 'validatePassword',
        ];
    }

    public function validatePassword(BuildValidationEvent $event): void
    {
        $definition = $event->getDefinition();
        
        $fieldName = 'newPassword';
        if($event->getName() === "framework.validation.customer.create" ){
            $fieldName = 'password';
        }
        
        $passwordStrength = new PasswordStrength([], $this->systemConfigService->getInt('TorqShopwareCommon.config.passwordStrength'));
        $definition->add($fieldName, $passwordStrength);
    }
}