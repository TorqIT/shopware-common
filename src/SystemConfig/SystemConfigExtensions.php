<?php

namespace Torq\Shopware\Common\SystemConfig;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Torq\Shopware\Common\Security\Encryption\EncryptionHandler;

class SystemConfigExtensions 
{
    public function __construct(private SystemConfigService $systemConfigService, private EncryptionHandler $encryptionHandler)
    {
    }

    public function getEncrypted(string $key, ?string $salesChannelId = null): mixed
    {
        $val = $this->systemConfigService->get($key, $salesChannelId);
        
        if (!$val) {
            return null;
        }

        $decrypted = json_decode($this->encryptionHandler->decrypt($val));
        return $decrypted;
    }

    public function setEncrypted(string $key, mixed $value, ?string $salesChannelId = null): void
    {
        $encrypted = $this->encryptionHandler->encrypt(json_encode($value));

        $this->systemConfigService->set($key, $encrypted, $salesChannelId);
    }

    public function deleteKey(string $key, ?string $salesChannelId = null): void
    {
        $this->systemConfigService->delete($key, $salesChannelId);
    }
}