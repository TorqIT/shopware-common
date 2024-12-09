<?php declare(strict_types=1);

namespace Torq\Setup;

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
        $stmt = $this->connection->prepare('SELECT iso_code FROM currency WHERE id = ?');
        $currentCurrencyIso = $stmt->executeQuery([Uuid::fromHexToBytes(Defaults::CURRENCY)])->fetchOne();
        
        if (!$currentCurrencyIso) {
            throw new \RuntimeException('Default currency not found');
        }

        echo("currentCurrencyISO - " . $currentCurrencyIso);
        echo("currency - " . $currency);
        if (mb_strtoupper($currentCurrencyIso) === mb_strtoupper($currency)) {
            return;
        }

        $newDefaultCurrencyId = $this->getCurrencyId($currency);

        $stmt = $this->connection->prepare('UPDATE currency SET id = :newId WHERE id = :oldId');

        // assign new uuid to old DEFAULT
        $stmt->executeStatement([
            'newId' => Uuid::randomBytes(),
            'oldId' => Uuid::fromHexToBytes(Defaults::CURRENCY),
        ]);

        // change id to DEFAULT
        $stmt->executeStatement([
            'newId' => Uuid::fromHexToBytes(Defaults::CURRENCY),
            'oldId' => $newDefaultCurrencyId,
        ]);

        $stmt = $this->connection->prepare(
            'SET @fixFactor = (SELECT 1/factor FROM currency WHERE iso_code = :newDefault);
             UPDATE currency
             SET factor = IF(iso_code = :newDefault, 1, factor * @fixFactor);'
        );
        $stmt->executeStatement(['newDefault' => $currency]);
    }

    private function getCurrencyId(string $currencyName): string
    {
        $stmt = $this->connection->prepare(
            'SELECT id FROM currency WHERE LOWER(iso_code) = LOWER(?)'
        );
        $fetchCurrencyId = $stmt->executeQuery([$currencyName])->fetchOne();
        
        if (!$fetchCurrencyId) {
            throw new \RuntimeException('Currency with iso-code ' . $currencyName . ' not found');
        }

        return (string) $fetchCurrencyId;
    }

    public function setDefaultLanguage(string $locale): void
    {
        $currentLocaleStmt = $this->connection->prepare(
            'SELECT locale.id, locale.code
             FROM language
             INNER JOIN locale ON translation_code_id = locale.id
             WHERE language.id = ?'
        );
        $currentLocale = $currentLocaleStmt->executeQuery([Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM)])->fetchAssociative();

        if (!$currentLocale) {
            echo("No current locale\n");
            throw new \RuntimeException('Default language locale not found');
        }

        $currentLocaleId = $currentLocale['id'];

        $newDefaultLocaleId = $this->getLocaleId($locale);
        
        // locales match -> do nothing.
        if ($currentLocaleId === $newDefaultLocaleId) {
            echo("Locales match\n");
            return;
        }

        $newDefaultLanguageId = $this->getLanguageId($locale);
  
        if (!$newDefaultLanguageId) {
            echo("Creating new language\n");
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
        $stmt = $this->connection->prepare('SELECT locale.id FROM  locale WHERE LOWER(locale.code) = LOWER(?)');
        $id = $stmt->executeQuery([$iso])->fetchOne();

        if (!$id) {
            throw new \RuntimeException('Locale with iso-code ' . $iso . ' not found');
        }

        return (string) $id;
    }

    private function getLanguageId(string $iso): ?string
    {
        $stmt = $this->connection->prepare(
            'SELECT language.id
             FROM `language`
             INNER JOIN locale ON locale.id = language.translation_code_id
             WHERE LOWER(locale.code) = LOWER(?)'
        );
        return $stmt->executeQuery([$iso])->fetchOne() ?: null;
    }

    private function createNewLanguageEntry(string $iso)
    {
        $id = Uuid::randomBytes();

        $localeId = $this->getLocaleId($iso);
        
        //Always use the English name since we dont have the name in the language itself
        if($iso == 'en-CA'){
            $name = 'English'; //$stmt->executeQuery([$localeId, $englishId])->fetchOne();
        }else{            
            $stmt = $this->connection->prepare(
                '
                SELECT LOWER(language.id)
                FROM `language`
                WHERE LOWER(language.name) = LOWER(?)'
            );
            $englishId = $stmt->executeQuery(['english'])->fetchOne(); 
            $name = $stmt->executeQuery([$localeId, $englishId])->fetchOne();
            $stmt = $this->connection->prepare(
                '
                SELECT locale_translation.name
                FROM `locale_translation`
                WHERE LOWER(HEX(locale_id)) = ?
                AND LOWER(language_id) = ?'
            );
            if (!$name) {
                throw new Exception("locale_translation.name for iso: '" . $iso . "', localeId: '" . $localeId . "' not found!");
            }
        }

        $this->createLanguage($id, $name, $iso, $localeId);
        
        return $id;
    }

    public function createLanguage($id, $name, $iso, $localeId){
        if(!$localeId){
            $localeId = $this->getLocaleId($iso);
        }

        $stmt = $this->connection->prepare(
            '
            INSERT INTO `language`
            (id,name,locale_id,translation_code_id,created_at)
            VALUES
            (UNHEX(?),?,?,?,?)'
        );
        $stmt->executeStatement([$id, $name, $localeId, $localeId,(new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]);
    }

    private function swapDefaultLanguageId(string $newLanguageId): void
    {
        $stmt = $this->connection->prepare(
            'UPDATE language
             SET id = :newId
             WHERE id = :oldId'
        );

        // assign new uuid to old DEFAULT
        $stmt->executeStatement([
            'newId' => Uuid::randomBytes(),
            'oldId' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM),
        ]);

        // change id to DEFAULT
        $stmt->executeStatement([
            'newId' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM),
            'oldId' => $newLanguageId,
        ]);
    }

    private function changeDefaultLanguageData(string $newDefaultLanguageId, array $currentLocaleData, string $locale): void
    {
        $enGbLanguageId = $this->getLanguageId('en-GB');
        $currentLocaleId = $currentLocaleData['id'];
        $name = $locale;

        $newDefaultLocaleId = $this->getLocaleId($locale);

        if (!$newDefaultLanguageId && $enGbLanguageId) {
            $stmt = $this->connection->prepare(
                'SELECT name FROM locale_translation
                 WHERE language_id = :language_id
                 AND locale_id = :locale_id'
            );
            $name = $stmt->executeQuery(['language_id' => $enGbLanguageId, 'locale_id' => $newDefaultLocaleId])->fetchOne();
        }

        // swap locale.code
        $stmt = $this->connection->prepare(
            'UPDATE locale SET code = :code WHERE id = :locale_id'
        );
        $stmt->executeStatement(['code' => 'x-' . $locale . '_tmp', 'locale_id' => $currentLocaleId]);
        $stmt->executeStatement(['code' => $currentLocaleData['code'], 'locale_id' => $newDefaultLocaleId]);
        $stmt->executeStatement(['code' => $locale, 'locale_id' => $currentLocaleId]);

        // swap locale_translation.{name,territory}
        $setTrans = $this->connection->prepare(
            'UPDATE locale_translation
             SET name = :name, territory = :territory
             WHERE locale_id = :locale_id AND language_id = :language_id'
        );

        $currentTrans = $this->getLocaleTranslations($currentLocaleId);
        $newDefTrans = $this->getLocaleTranslations($newDefaultLocaleId);

        foreach ($currentTrans as $trans) {
            $trans['locale_id'] = $newDefaultLocaleId;
            $setTrans->executeStatement($trans);
        }
        foreach ($newDefTrans as $trans) {
            $trans['locale_id'] = $currentLocaleId;
            $setTrans->executeStatement($trans);
        }

        $updLang = $this->connection->prepare('UPDATE language SET name = :name WHERE id = :language_id');

        // new default language does not exist -> just set to name
        if (!$newDefaultLanguageId) {
            $updLang->executeStatement(['name' => $name, 'language_id' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM)]);

            return;
        }

        $langName = $this->connection->prepare('SELECT name FROM language WHERE id = :language_id');
        $current = $langName->executeQuery(['language_id' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM)])->fetchOne();
        
        $new = $langName->executeQuery(['language_id' => $newDefaultLanguageId])->fetchOne();

        // swap name
        $updLang->executeStatement(['name' => $new, 'language_id' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM)]);
        $updLang->executeStatement(['name' => $current, 'language_id' => $newDefaultLanguageId]);
    }

    private function getLocaleTranslations(string $localeId): array
    {
        $stmt = $this->connection->prepare(
            'SELECT locale_id, language_id, name, territory
             FROM locale_translation
             WHERE locale_id = :locale_id'
        );
        return $stmt->executeQuery(['locale_id' => $localeId])->fetchAllAssociative();
    }

    public function updateSalesChannelDomainSnippet($salesChannelId, $snippetId){
        $stmt = $this->connection->prepare(
            'UPDATE `sales_channel_domain` SET snippet_set_id = UNHEX(?) WHERE sales_channel_id = UNHEX(?)'
        );
        $stmt->executeQuery([$snippetId,$salesChannelId]);
    }

    public function updateDefaultLanguageDetails($name, $localeIso){
        $localeId = $this->getLocaleId($localeIso);

        $stmt = $this->connection->prepare(
            'UPDATE `language` SET name = ?, locale_id = ?, translation_code_id = ? WHERE id = UNHEX(?)'
        );
        $stmt->executeQuery([$name,$localeId,$localeId,Defaults::LANGUAGE_SYSTEM]);
    }

    public function updateUSDCurrencyId(string $currencyId)
    {
        $stmt = $this->connection->prepare(
            'UPDATE `currency` SET id = UNHEX(?) WHERE iso_code = ?'
        );
        $stmt->executeQuery([$currencyId,'USD']);
    }

    public function createSnippetSet($id, $name, $baseFile, $iso){
        $stmt = $this->connection->prepare(
            '
            INSERT INTO `snippet_set` (id,name,base_file,iso,created_at)
            VALUES(UNHEX(?),?,?,?,?)
            '
        );
        $stmt->executeQuery([$id, $name,$baseFile,$iso,(new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]);
    }

    public function createNewCADCurrencyEntry()
    {
        $iso = 'CAD';
        $iso_name = 'Canadian Dollar';
        $id = '96e279eb6fd80697f676865f964a2458'; //Uuid::randomBytes();

        $stmt = $this->connection->prepare(
            '
            SELECT LOWER (HEX(currency.id))
            FROM `currency`
            WHERE LOWER(currency.iso_code) = LOWER(?)'
        );
        $currId = $stmt->executeQuery([$iso])->fetchOne();

        if($currId){
            return $currId;
        }else{
            $stmt = $this->connection->prepare(
                '
                INSERT INTO `currency`
                (`id`,`iso_code`,`factor`,`symbol`,`position`,`item_rounding`,`total_rounding`,`tax_free_from`,created_at)
                VALUES
                (UNHEX(?),?,1,"$",1,"{\"decimals\": \"2\", \"interval\": 0.01, \"roundForNet\": true}",
                "{\"decimals\": \"2\", \"interval\": 0.01, \"roundForNet\": true}",0,?)
                '
            );

            try{
                echo("before create currency row");
                $stmt->executeStatement([$id, $iso,(new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]);
                $stmt = $this->connection->prepare(
                    '
                    INSERT INTO `currency_translation`
                    (`currency_id`,`language_id`,`short_name`,`name`,created_at)
                    VALUES
                    (UNHEX(?),UNHEX(?),?,?,?)
                    '
                );
                echo("before create currency translation row");
                $stmt->executeStatement([$id,Defaults::LANGUAGE_SYSTEM,$iso,$iso_name,(new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)]);
            }catch(\Exception $e){
                echo($e->getMessage());
            }

            return $id;
        }
    }

    public function updateHomeCategoryId($newId)
    {
        if(is_null($newId))
            return;

        $stmt = $this->connection->prepare(
            'SELECT lower(hex(category_id)) FROM category_translation where name = ? limit 1'
        );
        $oldId = $stmt->executeQuery(['Home'])->fetchOne();

        if($newId == $oldId){
            return;
        }else{
            $stmt = $this->connection->prepare(
                'UPDATE category set id = UNHEX(?) where id = UNHEX(?)'
            );
            $stmt->executeStatement([$newId,$oldId]);

            $stmt = $this->connection->prepare(
                'UPDATE category_translation set category_id = UNHEX(?) where category_id = UNHEX(?)'
            );
            $stmt->executeStatement([$newId,$oldId]);
        }
    }

    public function updateStandardTaxId($newId)
    {
        if(is_null($newId))
            return;

        $stmt = $this->connection->prepare(
            'SELECT lower(hex(id)) FROM tax where name = ? limit 1'
        );
        $oldId = $stmt->executeQuery(['Standard rate'])->fetchOne();

        //var_dump($newId);
        //var_dump($oldId);
        if($newId == $oldId){
            return;
        }else{

            //duplcate the record and associate with the newid
            echo("Duplicate\n");
            $stmt = $this->connection->prepare(
                '
                insert into tax 
                select unhex(?),tax_rate, name, position, custom_fields,created_at,updated_at 
                from tax where id = unhex(?)'
            );
            $stmt->executeStatement([$newId,$oldId]);
            // update the tax rules to point to the new id
            echo("update tax rule\n");
            $stmt = $this->connection->prepare(
                'UPDATE tax_rule set tax_id = UNHEX(?) where tax_id = UNHEX(?)'
            );
            $stmt->executeStatement([$newId,$oldId]);
            //remove the old tax row
            echo("remove old tax\n");
            $stmt = $this->connection->prepare(
                'DELETE from tax where id = UNHEX(?)'
            );
            $stmt->executeStatement([$oldId]);
        }
    }

    public function updateProductMediaId($newId)
    {
        if(is_null($newId))
            return;

        $stmt = $this->connection->prepare(
            'SELECT lower(hex(id)) FROM media_folder where name = ? limit 1'
        );
        $oldId = $stmt->executeQuery(['Product Media'])->fetchOne();

        if($newId == $oldId){
            return;
        }else{
            $stmt = $this->connection->prepare(
                'UPDATE media_folder set id = UNHEX(?) where id = UNHEX(?)'
            );
            $stmt->executeStatement([$newId,$oldId]);
        }
    }

    public function deleteLanaguage($name){
        $stmt = $this->connection->prepare(
            'delete from shopware.language where name = ?'
        );
        $stmt->executeStatement([$name]);
    }
}