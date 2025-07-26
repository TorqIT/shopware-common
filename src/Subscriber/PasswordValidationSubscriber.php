<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Subscriber;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Framework\Validation\BuildValidationEvent;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Component\Validator\Constraints\Regex;
use Torq\Shopware\Common\Constants\ConfigConstants;
use Torq\Shopware\Common\Constants\PasswordConstants;

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
        
        $configuredPasswordStrength = $this->systemConfigService->getInt(ConfigConstants::PASSWORD_STRENGTH);

        if($configuredPasswordStrength === ConfigConstants::PASSWORD_STRENGTH_REGEX){
            $passwordStrength = new Regex($this->systemConfigService->getString(ConfigConstants::PASSWORD_REGEX));
        }
        else{
            $passwordStrength = new PasswordStrength([], $configuredPasswordStrength);
        }

        $definition->add($fieldName, $passwordStrength);
    }
}