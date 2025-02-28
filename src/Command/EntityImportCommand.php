<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Command;

use Throwable;
use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;

class EntityImportCommand extends Command
{
    public function __construct(private readonly DefinitionInstanceRegistry $definitionRegistry)
    {
        parent::__construct(); 
    }
   
    public function configure()
    {
        $this->setName('torq:entity-importer')
            ->setDescription('Import entities based on the configuration in _config.json')
            ->addOption('configFile', null, InputOption::VALUE_REQUIRED, 'Config file for the import','custom/data/_config.json')
            ->addOption('dataFolder', null, InputOption::VALUE_REQUIRED, 'Folder to import the data from','custom/data/');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        //Retrieve the configuration
        $configFile = $input->getOption('configFile');        
        try{
            $config = json_decode(file_get_contents($configFile),true);            
        }catch(Throwable $e){
            $io->getErrorStyle()->warning("Error loading config file",true);
            return Command::FAILURE;
        }   
        
        //Loop each entity configuration
        foreach($config as $entityConfig){
            $entity = $entityConfig["entity"];
            $repo = $this->definitionRegistry->getRepository($entity);
            
            $file = $input->getOption('dataFolder') . $entity . '.json';        
            try{
                $data = json_decode(file_get_contents($file),true);
                try{
                    $repo->upsert($data,Context::createDefaultContext());
                    $io->write("Success importing entity - " . $entity, true);
                }catch(Throwable $e){
                    //svar_dump($data);
                    //print_r($data);
                    throw $e;
                }
            }catch(Throwable $e){
                $io->getErrorStyle()->warning("Error importing entity - " . $entity . ".\n" . $e->getMessage(),true);
            }            
        }
        return Command::SUCCESS;
    }
  }