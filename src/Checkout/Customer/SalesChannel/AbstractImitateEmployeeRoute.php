<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Checkout\Customer\SalesChannel;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\ContextTokenResponse;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

abstract class AbstractImitateEmployeeRoute
{
    abstract public function getDecorated(): AbstractImitateEmployeeRoute;

    abstract public function imitateEmployeeLogin(RequestDataBag $data, SalesChannelContext $context): ContextTokenResponse;
}
