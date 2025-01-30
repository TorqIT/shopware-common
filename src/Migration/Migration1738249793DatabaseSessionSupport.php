<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

/**
 * @internal
 */
class Migration1738249793DatabaseSessionSupport extends MigrationStep
{
    public function __construct()
    {
    }   

    public function getCreationTimestamp(): int
    {        
        return 1738249793;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
            CREATE TABLE `sessions` (
            `sess_id` VARBINARY(128) NOT NULL PRIMARY KEY,
            `sess_data` BLOB NOT NULL,
            `sess_lifetime` INTEGER UNSIGNED NOT NULL,
            `sess_time` INTEGER UNSIGNED NOT NULL,
            INDEX `sessions_sess_lifetime_idx` (`sess_lifetime`)
        ) COLLATE utf8mb4_bin, ENGINE = InnoDB;

        SQL;

        $connection->executeStatement($sql);
    }
}
