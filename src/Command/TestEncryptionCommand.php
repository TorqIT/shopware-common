<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Torq\Shopware\Common\Security\Encryption\EncryptionHandler;

class TestEncryptionCommand extends Command
{
    public function __construct(private EncryptionHandler $encryptionHandler) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('torq:encryption:test')
            ->setDescription('Test encryption and decryption');

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $e = $this->encryptionHandler->encrypt('Hello, World!');
        $d = $this->encryptionHandler->decrypt($e);

        $output->writeln([$e, $d]);

        return self::SUCCESS; 
    }

}
