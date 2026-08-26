<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Subscriber;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * MySQL 8.4's restrict_fk_on_non_standard_key guard can reject an unrelated ALTER TABLE
 * on a parent table when a child has a foreign key against a non-standard key (MySQL bug
 * #118151). Several Shopware 6.7 core migrations (e.g. Migration1772007509ProductMainCategoryInheritance,
 * Migration1774345867AddProductOpenGraphFields) hit this on `product`. Shopware's own fix
 * (shopware/shopware#18631) hasn't shipped in a release yet, and this environment's managed
 * MySQL doesn't allow disabling the guard GLOBALLY (SYSTEM_VARIABLES_ADMIN isn't granted).
 * Disabling it for just the migration command's own session needs no elevated privilege.
 * Remove this once Shopware ships the real fix.
 */
class DisableNonStandardKeyForeignKeyGuardSubscriber implements EventSubscriberInterface
{
    private const AFFECTED_COMMANDS = ['database:migrate', 'system:install'];

    public function __construct(private readonly Connection $connection)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => 'onConsoleCommand',
        ];
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        if (!\in_array($event->getCommand()?->getName(), self::AFFECTED_COMMANDS, true)) {
            return;
        }

        try {
            $this->connection->executeStatement('SET SESSION restrict_fk_on_non_standard_key = OFF');
        } catch (\Throwable) {
            // Variable doesn't exist before MySQL 8.4 (or on MariaDB) — nothing to disable.
        }
    }
}
