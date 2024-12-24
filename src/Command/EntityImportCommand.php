<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Command;

use Exception;
use Throwable;
use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;

class EntityImportCommand extends Command
{
    public function __construct(private readonly DefinitionInstanceRegistry $definitionRegistry)
    {
        parent::__construct(); 
    }
   
    public function configure()
    {
        $this->setName('torq:entity-importer')
            ->setDescription('Import entities');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $configFile = '/var/www/html/custom/data/_config.json';        
        try{
            $config = json_decode(file_get_contents($configFile),true);            
        }catch(Throwable $e){
            $io->getErrorStyle()->warning("Error loading config file",true);
            return Command::FAILURE;
        }   
        
        foreach($config as $entityConfig){
            $entity = $entityConfig["entity"];
            $repo = $this->definitionRegistry->getRepository($entity);
            
            $file = '/var/www/html/custom/data/' . $entity . '.json';        
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
