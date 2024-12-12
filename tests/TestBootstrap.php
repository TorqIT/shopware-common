<?php declare(strict_types=1);

use Shopware\Core\TestBootstrapper;

$loader = (new TestBootstrapper())
    ->addCallingPlugin()
    ->addActivePlugins('ShopwareCommon')
    ->setForceInstallPlugins(true)
    ->bootstrap()
    ->getClassLoader();

$loader->addPsr4('Torq\\Shopware\\Common\\Tests\\', __DIR__);
