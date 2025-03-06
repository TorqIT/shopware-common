<?php declare(strict_types=1);

namespace Torq\Shopware\Common\SalesChannel\Context;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Cart\AbstractCartPersister;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Util\Random;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Event\SalesChannelContextTokenChangeEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Employee\EmployeeEntity;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister;

#[Package('core')]
class SalesChannelContextPersisterDecorator extends SalesChannelContextPersister
{
    
    private static ?EmployeeEntity $employee = null;
    private readonly string $lifetimeInterval;

    /**
     * @internal
     */
    public function __construct(
        private SalesChannelContextPersister $decorated,
        private readonly Connection $connection,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AbstractCartPersister $cartPersister,
        ?string $lifetimeInterval = 'P1D'
    ) {
        $this->lifetimeInterval = $lifetimeInterval ?? 'P1D';

        parent::__construct($connection, $eventDispatcher, $cartPersister, $lifetimeInterval);
    }


    /**
     * @return array<string, mixed>
     */
    public function load(string $token, string $salesChannelId, ?string $customerId = null): array
    {
        if(self::$employee == null){
            return $this->decorated->load($token, $salesChannelId, $customerId);
        }

        $qb = $this->connection->createQueryBuilder();

        $qb->select('*');
        $qb->from('sales_channel_api_context');

        $qb->where('sales_channel_id = :salesChannelId');
        $qb->setParameter('salesChannelId', Uuid::fromHexToBytes($salesChannelId));
        $qb->andWhere('(token = :token OR JSON_EXTRACT(payload, "$.employeeId") = :employeeId)');
        $qb->setParameter('token', $token);
        $qb->setParameter('employeeId', self::$employee->getId());
        $qb->orderBy('updated_at', 'DESC');
        $qb->setMaxResults(1);

        $data = $qb->executeQuery()->fetchAllAssociative();

        if (empty($data)) {
            return [];
        }

        $context = array_shift($data);

        $updatedAt = new \DateTimeImmutable($context['updated_at']);
        $expiredTime = $updatedAt->add(new \DateInterval($this->lifetimeInterval));

        $payload = array_filter(json_decode((string) $context['payload'], true, 512, \JSON_THROW_ON_ERROR));
        $now = new \DateTimeImmutable();
        if ($expiredTime < $now) {
            // context is expired
            $payload = ['expired' => true];
        } else {
            $payload['expired'] = false;
        }

        $payload['token'] = $context['token'];

        return $payload;
    }

    public static function setEmployee(EmployeeEntity $employee){
        self::$employee = $employee;
    }
}
