<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Checkout\Customer\Validation\Constraint;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\MissingOptionsException;

class EmployeePasswordMatches extends Constraint
{
    final public const CUSTOMER_PASSWORD_NOT_CORRECT = 'fe2faa88-34d9-4c3b-99b3-8158b1ed8dc7';

    protected const ERROR_NAMES = [
        self::CUSTOMER_PASSWORD_NOT_CORRECT => 'CUSTOMER_PASSWORD_NOT_CORRECT',
    ];

    public $message = 'Your password is wrong';
    protected $context;

    public function __construct($options = null)
    {
        $options = array_merge(
            ['context' => null],
            $options
        );

        parent::__construct($options);

        if (!$this->context instanceof SalesChannelContext) {
            throw new MissingOptionsException(\sprintf('Option "context" must be given for constraint %s', self::class), ['context']);
        }
    }

    public function getContext(): SalesChannelContext
    {
        return $this->context;
    }
}
