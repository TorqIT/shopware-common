<?php

namespace Torq\Shopware\Common\Security\Encryption;

use Defuse\Crypto\Key;
use Defuse\Crypto\Crypto;

class EncryptionHandler
{
    private Key $key;
    public function __construct(?string $key) {
        if ($key) {
            $this->key = Key::loadFromAsciiSafeString($key);
        }
    }

    public function encrypt(string $data): string
    {
        return Crypto::encrypt($data, $this->key);   
    }

    public function decrypt(string $data): string
    {
        return Crypto::decrypt($data, $this->key);
    }
}