<?php

namespace Torq\Shopware\Common\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class TorqShopwareCommonExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = [];
        // let resources override the previous set value
        foreach ($configs as $subConfig) {
            $config = array_merge($config, $subConfig);
        }

        $encryptionSecret = $config['encryption']['secret'] ?? null;

        if(!$encryptionSecret){
            echo 'Torq Shopware Common: Encryption secret is not set in config, Torq\Shopware\Common\Security\Encryption\EncryptionHandler will not function!';
        }

        if (!$container->hasParameter('torq_shopware_common.encryption.secret')) {
            $container->setParameter('torq_shopware_common.encryption.secret', $encryptionSecret);
        }
    }
}