<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\PasswordStrengthValidator;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Torq\Shopware\Common\Constants\ConfigConstants;

class TestPasswordCommand extends Command
{
    public function __construct(private SystemConfigService $systemConfigService)
    {
        parent::__construct();
    }
    
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

        $configuredPasswordStrength = $this->systemConfigService->getInt(ConfigConstants::PASSWORD_STRENGTH);

        if($configuredPasswordStrength === ConfigConstants::PASSWORD_STRENGTH_REGEX){
            $output->writeln('Password validation is configured to use regex pattern');
            
            $regex = $this->systemConfigService->getString(ConfigConstants::PASSWORD_REGEX);
            $output->writeln('Testing against pattern: ' . $regex . ' with password: ' . $password);
            
            $table = new \Symfony\Component\Console\Helper\Table($output);
            $table->setHeaders(['Test', 'Result']);
            
            $matches = preg_match($regex, $password);
            $table->addRow(['Regex Match', $matches ? '<info>PASS</info>' : '<error>FAIL</error>']);
            
            $table->render();
        }
        else{
            $strengthLabels = [
                1 => 'Weak',
                2 => 'Medium', 
                3 => 'Strong',
                4 => 'Very Strong'
            ];
            
            $output->writeln('Required password strength: ' . $strengthLabels[$configuredPasswordStrength]);
            
            $table = new \Symfony\Component\Console\Helper\Table($output);
            $table->setHeaders(['Test', 'Result']);
            
            $strengthLabel = match(true) {
                $b >= 4 => 'Very Strong',
                $b >= 3 => 'Strong',
                $b >= 2 => 'Medium',
                default => 'Weak'
            };
            
            $table->addRow(['Password Strength', $strengthLabel]);
            $table->addRow(['Meets Configuration', $b >= $configuredPasswordStrength ? '<info>PASS</info>' : '<error>FAIL</error>']);
            
            $table->render();
        }
       
        
        return self::SUCCESS; 
    }

}
