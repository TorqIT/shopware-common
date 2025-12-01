<?php

declare(strict_types=1);

namespace Torq\Shopware\Common\Setup;

use Exception;
use Shopware\Core\Defaults;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Uuid\Uuid;

class CustomizeShopware
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function setDefaultCurrency(string $currency): void
    {
        $currentCurrencyIso = $this->connection->executeQuery(
            'SELECT iso_code FROM currency WHERE id = ?',
            [Uuid::fromHexToBytes(Defaults::CURRENCY)]
        )->fetchOne();

        if (!$currentCurrencyIso) {
            throw new \RuntimeException('Default currency not found');
        }

        echo ("currentCurrencyISO - " . $currentCurrencyIso);
        echo ("currency - " . $currency);
        if (mb_strtoupper($currentCurrencyIso) === mb_strtoupper($currency)) {
            return;
        }

        $newDefaultCurrencyId = $this->getCurrencyId($currency);

        // assign new uuid to old DEFAULT
        $this->connection->executeStatement(
            'UPDATE currency SET id = :newId WHERE id = :oldId',
            [
                'newId' => Uuid::randomBytes(),
                'oldId' => Uuid::fromHexToBytes(Defaults::CURRENCY),
            ]
        );

        // change id to DEFAULT
        $this->connection->executeStatement(
            'UPDATE currency SET id = :newId WHERE id = :oldId',
            [
                'newId' => Uuid::fromHexToBytes(Defaults::CURRENCY),
                'oldId' => $newDefaultCurrencyId,
            ]
        );

        $this->connection->executeStatement(
            'SET @fixFactor = (SELECT 1/factor FROM currency WHERE iso_code = :newDefault);
             UPDATE currency
             SET factor = IF(iso_code = :newDefault, 1, factor * @fixFactor);',
            ['newDefault' => $currency]
        );
    }

    private function getCurrencyId(string $currencyName): string
    {
        $fetchCurrencyId = $this->connection->executeQuery(
            'SELECT id FROM currency WHERE LOWER(iso_code) = LOWER(?)',
            [$currencyName]
        )->fetchOne();

        if (!$fetchCurrencyId) {
            throw new \RuntimeException('Currency with iso-code ' . $currencyName . ' not found');
        }

        return (string) $fetchCurrencyId;
    }

    public function setDefaultLanguage(string $locale): void
    {
        $currentLocale = $this->connection->executeQuery(
            'SELECT locale.id, locale.code
             FROM language
             INNER JOIN locale ON translation_code_id = locale.id
             WHERE language.id = ?',
            [Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM)]
        )->fetchAssociative();

        if (!$currentLocale) {
            echo ("No current locale\n");
            throw new \RuntimeException('Default language locale not found');
        }

        $currentLocaleId = $currentLocale['id'];

        $newDefaultLocaleId = $this->getLocaleId($locale);

        // locales match -> do nothing.
        if ($currentLocaleId === $newDefaultLocaleId) {
            echo ("Locales match\n");
            return;
        }

        $newDefaultLanguageId = $this->getLanguageId($locale);

        if (!$newDefaultLanguageId) {
            echo ("Creating new language\n");
            $newDefaultLanguageId = $this->createNewLanguageEntry($locale);
        }

        if ($locale === 'de-DE' && $currentLocale['code'] === 'en-GB') {
            $this->swapDefaultLanguageId($newDefaultLanguageId);
        } else {
            $this->changeDefaultLanguageData($newDefaultLanguageId, $currentLocale, $locale);
        }
    }

    private function getLocaleId(string $iso): string
    {
        $id = $this->connection->executeQuery(
            'SELECT locale.id FROM locale WHERE LOWER(locale.code) = LOWER(?)',
            [$iso]
        )->fetchOne();

        if (!$id) {
            throw new \RuntimeException('Locale with iso-code ' . $iso . ' not found');
        }

        return (string) $id;
    }

    private function getLanguageId(string $iso): ?string
    {
        return $this->connection->executeQuery(
            'SELECT language.id
             FROM `language`
             INNER JOIN locale ON locale.id = language.translation_code_id
             WHERE LOWER(locale.code) = LOWER(?)',
            [$iso]
        )->fetchOne() ?: null;
    }

    private function createNewLanguageEntry(string $iso)
    {
        $id = Uuid::randomBytes();

        $localeId = $this->getLocaleId($iso);

        //Always use the English name since we dont have the name in the language itself
        if ($iso == 'en-CA') {
            $name = 'English';
        } else {
            $englishId = $this->connection->executeQuery(
                'SELECT LOWER(language.id)
                 FROM `language`
                 WHERE LOWER(language.name) = LOWER(?)',
                ['english']
            )->fetchOne();
            $name = $this->connection->executeQuery(
                'SELECT locale_translation.name
                 FROM `locale_translation`
                 WHERE LOWER(HEX(locale_id)) = ?
                 AND LOWER(language_id) = ?',
                [$localeId, $englishId]
            )->fetchOne();
            if (!$name) {
                throw new Exception("locale_translation.name for iso: '" . $iso . "', localeId: '" . $localeId . "' not found!");
            }
        }

        $this->createLanguage($id, $name, $iso, $localeId);

        return $id;
    }

    public function createLanguage($id, $name, $iso, $localeId)
    {
        if (!$localeId) {
            $localeId = $this->getLocaleId($iso);
        }

        $this->connection->executeStatement(
            'INSERT INTO `language`
             (id,name,locale_id,translation_code_id,created_at)
             VALUES
             (UNHEX(?),?,?,?,?)',
            [$id, $name, $localeId, $localeId, (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]
        );
    }

    private function swapDefaultLanguageId(string $newLanguageId): void
    {
        // assign new uuid to old DEFAULT
        $this->connection->executeStatement(
            'UPDATE language SET id = :newId WHERE id = :oldId',
            [
                'newId' => Uuid::randomBytes(),
                'oldId' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM),
            ]
        );

        // change id to DEFAULT
        $this->connection->executeStatement(
            'UPDATE language SET id = :newId WHERE id = :oldId',
            [
                'newId' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM),
                'oldId' => $newLanguageId,
            ]
        );
    }

    private function changeDefaultLanguageData(string $newDefaultLanguageId, array $currentLocaleData, string $locale): void
    {
        $enGbLanguageId = $this->getLanguageId('en-GB');
        $currentLocaleId = $currentLocaleData['id'];
        $name = $locale;

        $newDefaultLocaleId = $this->getLocaleId($locale);

        if (!$newDefaultLanguageId && $enGbLanguageId) {
            $name = $this->connection->executeQuery(
                'SELECT name FROM locale_translation
                 WHERE language_id = :language_id
                 AND locale_id = :locale_id',
                ['language_id' => $enGbLanguageId, 'locale_id' => $newDefaultLocaleId]
            )->fetchOne();
        }

        // swap locale.code
        $this->connection->executeStatement(
            'UPDATE locale SET code = :code WHERE id = :locale_id',
            ['code' => 'x-' . $locale . '_tmp', 'locale_id' => $currentLocaleId]
        );
        $this->connection->executeStatement(
            'UPDATE locale SET code = :code WHERE id = :locale_id',
            ['code' => $currentLocaleData['code'], 'locale_id' => $newDefaultLocaleId]
        );
        $this->connection->executeStatement(
            'UPDATE locale SET code = :code WHERE id = :locale_id',
            ['code' => $locale, 'locale_id' => $currentLocaleId]
        );

        // swap locale_translation.{name,territory}
        $currentTrans = $this->getLocaleTranslations($currentLocaleId);
        $newDefTrans = $this->getLocaleTranslations($newDefaultLocaleId);

        foreach ($currentTrans as $trans) {
            $trans['locale_id'] = $newDefaultLocaleId;
            $this->connection->executeStatement(
                'UPDATE locale_translation
                 SET name = :name, territory = :territory
                 WHERE locale_id = :locale_id AND language_id = :language_id',
                $trans
            );
        }
        foreach ($newDefTrans as $trans) {
            $trans['locale_id'] = $currentLocaleId;
            $this->connection->executeStatement(
                'UPDATE locale_translation
                 SET name = :name, territory = :territory
                 WHERE locale_id = :locale_id AND language_id = :language_id',
                $trans
            );
        }

        // new default language does not exist -> just set to name
        if (!$newDefaultLanguageId) {
            $this->connection->executeStatement(
                'UPDATE language SET name = :name WHERE id = :language_id',
                ['name' => $name, 'language_id' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM)]
            );

            return;
        }

        $current = $this->connection->executeQuery(
            'SELECT name FROM language WHERE id = :language_id',
            ['language_id' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM)]
        )->fetchOne();

        $new = $this->connection->executeQuery(
            'SELECT name FROM language WHERE id = :language_id',
            ['language_id' => $newDefaultLanguageId]
        )->fetchOne();

        // swap name
        $this->connection->executeStatement(
            'UPDATE language SET name = :name WHERE id = :language_id',
            ['name' => $new, 'language_id' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM)]
        );
        $this->connection->executeStatement(
            'UPDATE language SET name = :name WHERE id = :language_id',
            ['name' => $current, 'language_id' => $newDefaultLanguageId]
        );
    }

    private function getLocaleTranslations(string $localeId): array
    {
        return $this->connection->executeQuery(
            'SELECT locale_id, language_id, name, territory
             FROM locale_translation
             WHERE locale_id = :locale_id',
            ['locale_id' => $localeId]
        )->fetchAllAssociative();
    }

    public function updateSalesChannelDomainSnippet($salesChannelId, $snippetId)
    {
        $this->connection->executeStatement(
            'UPDATE `sales_channel_domain` SET snippet_set_id = UNHEX(?) WHERE sales_channel_id = UNHEX(?)',
            [$snippetId, $salesChannelId]
        );
    }

    public function updateDefaultLanguageDetails($name, $localeIso)
    {
        $localeId = $this->getLocaleId($localeIso);

        $this->connection->executeStatement(
            'UPDATE `language` SET name = ?, locale_id = ?, translation_code_id = ? WHERE id = UNHEX(?)',
            [$name, $localeId, $localeId, Defaults::LANGUAGE_SYSTEM]
        );
    }

    public function updateUSDCurrencyId(string $currencyId)
    {
        $this->connection->executeStatement(
            'UPDATE `currency` SET id = UNHEX(?) WHERE iso_code = ?',
            [$currencyId, 'USD']
        );
    }

    public function createSnippetSet($id, $name, $baseFile, $iso)
    {
        $this->connection->executeStatement(
            'INSERT INTO `snippet_set` (id,name,base_file,iso,created_at)
             VALUES(UNHEX(?),?,?,?,?)',
            [$id, $name, $baseFile, $iso, (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]
        );
    }

    public function createNewCADCurrencyEntry()
    {
        $iso = 'CAD';
        $iso_name = 'Canadian Dollar';
        $id = '96e279eb6fd80697f676865f964a2458'; //Uuid::randomBytes();

        $currId = $this->connection->executeQuery(
            'SELECT LOWER (HEX(currency.id))
             FROM `currency`
             WHERE LOWER(currency.iso_code) = LOWER(?)',
            [$iso]
        )->fetchOne();

        if ($currId) {
            return $currId;
        } else {
            try {
                echo ("before create currency row");
                $this->connection->executeStatement(
                    'INSERT INTO `currency`
                     (`id`,`iso_code`,`factor`,`symbol`,`position`,`item_rounding`,`total_rounding`,`tax_free_from`,created_at)
                     VALUES
                     (UNHEX(?),?,1,"$",1,"{\"decimals\": \"2\", \"interval\": 0.01, \"roundForNet\": true}",
                     "{\"decimals\": \"2\", \"interval\": 0.01, \"roundForNet\": true}",0,?)',
                    [$id, $iso, (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]
                );
                echo ("before create currency translation row");
                $this->connection->executeStatement(
                    'INSERT INTO `currency_translation`
                     (`currency_id`,`language_id`,`short_name`,`name`,created_at)
                     VALUES
                     (UNHEX(?),UNHEX(?),?,?,?)',
                    [$id, Defaults::LANGUAGE_SYSTEM, $iso, $iso_name, (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]
                );
            } catch (\Exception $e) {
                echo ($e->getMessage());
            }

            return $id;
        }
    }

    public function updateHomeCategoryId($newId)
    {
        if (is_null($newId))
            return;

        $oldId = $this->connection->executeQuery(
            'SELECT lower(hex(category_id)) FROM category_translation where name = ? limit 1',
            ['Home']
        )->fetchOne();

        if ($newId == $oldId) {
            return;
        } else {
            $this->connection->executeStatement(
                'UPDATE category set id = UNHEX(?) where id = UNHEX(?)',
                [$newId, $oldId]
            );

            $this->connection->executeStatement(
                'UPDATE category_translation set category_id = UNHEX(?) where category_id = UNHEX(?)',
                [$newId, $oldId]
            );
        }
    }

    public function updateStandardTaxId($newId)
    {
        if (is_null($newId))
            return;

        $oldId = $this->connection->executeQuery(
            'SELECT lower(hex(id)) FROM tax where name = ? limit 1',
            ['Standard rate']
        )->fetchOne();

        //var_dump($newId);
        //var_dump($oldId);
        if ($newId == $oldId) {
            return;
        } else {

            //duplcate the record and associate with the newid
            echo ("Duplicate\n");
            $this->connection->executeStatement(
                'insert into tax 
                 select unhex(?),tax_rate, name, position, custom_fields,created_at,updated_at 
                 from tax where id = unhex(?)',
                [$newId, $oldId]
            );
            // update the tax rules to point to the new id
            echo ("update tax rule\n");
            $this->connection->executeStatement(
                'UPDATE tax_rule set tax_id = UNHEX(?) where tax_id = UNHEX(?)',
                [$newId, $oldId]
            );
            //remove the old tax row
            echo ("remove old tax\n");
            $this->connection->executeStatement(
                'DELETE from tax where id = UNHEX(?)',
                [$oldId]
            );
        }
    }

    public function updateProductMediaId($newId)
    {
        if (is_null($newId))
            return;

        $oldId = $this->connection->executeQuery(
            'SELECT lower(hex(id)) FROM media_folder where name = ? limit 1',
            ['Product Media']
        )->fetchOne();

        if ($newId == $oldId) {
            return;
        } else {
            $this->connection->executeStatement(
                'UPDATE media_folder set id = UNHEX(?) where id = UNHEX(?)',
                [$newId, $oldId]
            );
        }
    }

    public function deleteLanaguage($name)
    {
        $this->connection->executeStatement(
            'delete from language where name = ?',
            [$name]
        );
    }
}
