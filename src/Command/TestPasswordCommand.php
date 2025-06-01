<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Torq\Shopware\Common\Security\Encryption\EncryptionHandler;
use Symfony\Component\Validator\Constraints\PasswordStrengthValidator;

class TestPasswordCommand extends Command
{
    
    protected function configure(): void
    {
        $this
            ->setName('torq:password:test')
            ->setDescription('Test password strength')
            ->addArgument('password', InputArgument::REQUIRED, 'The password to test');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $password = $input->getArgument('password');

        $b = PasswordStrengthValidator::estimateStrength($password);

        $output->writeln([
            'Password: ' . $password,
            'Length: ' . strlen($password),
            'Strength: ' . $b,
        ]);
        
        return self::SUCCESS; 
    }

}
