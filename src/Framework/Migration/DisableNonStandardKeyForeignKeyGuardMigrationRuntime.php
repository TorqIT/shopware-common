<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Framework\Migration;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Migration\MigrationRuntime;
use Shopware\Core\Framework\Migration\MigrationSource;

/**
 * MySQL 8.4's restrict_fk_on_non_standard_key guard can reject an unrelated ALTER TABLE
 * on a parent table when a child has a foreign key against a non-standard key (MySQL bug
 * #118151). Several Shopware 6.7 core migrations (e.g. Migration1772007509ProductMainCategoryInheritance,
 * Migration1774345867AddProductOpenGraphFields) hit this on `product`. Shopware's own fix
 * (shopware/shopware#18631) hasn't shipped in a release yet, and this environment's managed
 * MySQL doesn't allow disabling the guard GLOBALLY (SYSTEM_VARIABLES_ADMIN isn't granted).
 * Disabling it for just the migration run's own connection needs no elevated privilege.
 *
 * Decorating MigrationRuntime (rather than listening on ConsoleEvents::COMMAND) guarantees
 * this runs regardless of whether migrations are invoked directly (`database:migrate`) or
 * internally (`system:install` calling the migrate command via Application::doRun()).
 *
 * Remove this once Shopware ships the real fix.
 */
class DisableNonStandardKeyForeignKeyGuardMigrationRuntime extends MigrationRuntime
{
    public function __construct(private readonly Connection $connection, LoggerInterface $logger)
    {
        parent::__construct($connection, $logger);
    }

    public function migrate(MigrationSource $source, ?int $until = null, ?int $limit = null): \Generator
    {
        $this->disableForeignKeyGuard();

        yield from parent::migrate($source, $until, $limit);
    }

    public function migrateDestructive(MigrationSource $source, ?int $until = null, ?int $limit = null): \Generator
    {
        $this->disableForeignKeyGuard();

        yield from parent::migrateDestructive($source, $until, $limit);
    }

    private function disableForeignKeyGuard(): void
    {
        try {
            $this->connection->executeStatement('SET SESSION restrict_fk_on_non_standard_key = OFF');
        } catch (\Throwable) {
            // Variable doesn't exist before MySQL 8.4 (or on MariaDB) — nothing to disable.
        }
    }
}
