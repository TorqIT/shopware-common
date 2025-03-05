<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Command;

use Exception;
use Throwable;
use InvalidArgumentException;
use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\DefinitionNotFoundException;
use Torq\Shopware\Common\Entity\EntityImporterExporterIdHasher;

class EntityExportCommand extends Command
{
    public function __construct(
        private readonly DefinitionInstanceRegistry $definitionRegistry,
        private readonly EntityImporterExporterIdHasher $idHasher
    )
    {
        parent::__construct(); 
    }
   
    public function configure()
    {
        $this->setName('torq:entity-exporter')
            ->setDescription('Export entities based on the configuration in _config.json')            
            ->addOption('configFile', null, InputOption::VALUE_REQUIRED, 'Config file for the export','/var/www/html/custom/data/_config.json')
            ->addOption('dataFolder', null, InputOption::VALUE_REQUIRED, 'Folder to export the data to','/var/www/html/custom/data/');
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
            //The search is currently an "AND" between the ids and the extraCriteria section
            $ids = array_key_exists("ids",$entityConfig) ? $entityConfig["ids"] : [];
            $extraCriteria = array_key_exists("criteria",$entityConfig) ? $entityConfig["criteria"] : [];
            //add any extra data to the entity
            $associations = array_key_exists("associations",$entityConfig) ? $entityConfig["associations"] : [];
            //remove any extra fields that we don't want
            $excludeFields = array_key_exists("excludeFields",$entityConfig) ? $entityConfig["excludeFields"] : [];
            $repo = $this->definitionRegistry->getRepository($entity);

            //if ids are set, add to the criteria and then add any associations and extra search criteria 
            if(!empty($ids)){
                $ids = $this->idHasher->hashIdsForExport($ids);
            }
            
            $criteria = empty($ids) ? new Criteria() : new Criteria($ids);
            $criteria = $this->addAssociations($criteria, $associations);    
            $criteria = $this->addCriteria($criteria, $extraCriteria);

            $elements = $repo->search($criteria, Context::createDefaultContext())->getEntities()->getElements();  

            $jsonOutput = $this->processUnwritableFields($repo,$elements,$associations,$excludeFields);

            $file = $input->getOption('dataFolder') . $entity . '.json';
            // Write JSON data to the file, overwriting if it already exists
            file_put_contents($file, $jsonOutput);

            $io->write("Success exporting entity - " . $entity, true);
        }
        return Command::SUCCESS;
    }

    private function addAssociations($criteria, $associations){
        foreach($associations as $association){
            $criteria->addAssociation($association);
        }
        return $criteria;
    }

    private function addCriteria($criteria, $extraCriteria){
        foreach($extraCriteria as $crit){
            $type = $crit["type"];
            $field = $crit["field"];
            $values = $crit["values"]; //array
            //can add more types in the future
            switch($type){
                case 'EqualsAny':
                    $criteria->addFilter(new EqualsAnyFilter($field,$values));
                    break;
            }
        }
        return $criteria;
    }

    /**
     * Loops through the json and removes any fields that are unwriteable back to shopware. 
     * All based on the shopware entity configuration.
     * 
     * @param mixed $repo 
     * @param mixed $elements 
     * @param mixed $associations 
     * @param mixed $excludeFields 
     * @return string|false 
     * @throws DefinitionNotFoundException 
     * @throws ServiceCircularReferenceException 
     * @throws ServiceNotFoundException 
     * @throws InvalidArgumentException 
     * @throws Exception 
     */
    private function processUnwritableFields($repo, $elements, $associations, $excludeFields) {

        $json = json_decode(json_encode($elements, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR),true);

        //Process the top level entity fields
        $fields = $repo->getDefinition()->getFields();            
        $unwritableFields = [];
        foreach($fields as $field){
            if($field->getFlag(WriteProtected::class) || $field instanceof ManyToOneAssociationField){
                $unwritableFields[] = $field->getPropertyName();
            }
        }

        //add in any fields from the configuration that should be excluded
        $unwritableFields = array_merge($unwritableFields, $excludeFields);

        //Process unwriteable fields for the defined associations
        foreach($associations as $association){
            $entityAssoc = explode(".",$association);
            $entityDef = $repo->getDefinition(); // start with the main def
            $breadCrumb = '';
            foreach($entityAssoc as $a){
                $breadCrumb = (empty($breadCrumb) ? $a : $breadCrumb . '.' . $a);
                $entityElements = $entityDef->getFields()->getElements();
                $entityElementField = $entityElements[$a];
                if($entityElementField instanceof OneToManyAssociationField){
                    $entityAssocDef = $entityElementField->getReferenceDefinition();
                    $entityFields = $entityAssocDef->getFields();
                    foreach($entityFields as $entityField){
                        if($entityField->getFlag(WriteProtected::class) || $entityField instanceof ManyToOneAssociationField){
                            $unwritableFields[] = $breadCrumb . '.' . $entityField->getPropertyName();
                        }
                    }
                    $entityDef = $entityAssocDef; // set up for the next nested association
                }
            }
        }

        $arrayElements = [];          
        foreach($json as $element){
            foreach($unwritableFields as $value){
                $values = explode(".",$value);//children removals will have at least one "." in the value
                if(count($values) == 1){
                    unset($element[$value]);// top level entity removal
                }else{
                    $element = $this->removeKeyFromArray($values, $element); //child entity removal
                }
            }
            $arrayElements[] = $element;
        }        

        return json_encode($arrayElements, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }

    /**
     * Recursive function to remove unwriteable fields from the json.
     * 
     * @param array $values 
     * @param array $data 
     * @return array 
     */
    private function removeKeyFromArray(array $keys, array $data): array {        
        $firstKey = array_shift($keys); //pop off the first value

        //if any values left keep going deeper
        if(count($keys) > 0){     
            $nextData = $data[$firstKey];
            for($i=0; $i<count($nextData);$i++){
                $data[$firstKey][$i] = $this->removeKeyFromArray($keys, $nextData[$i]);
            }
        }else{
            //only do an unset if there was only one key so we don't remove the parents
            unset($data[$firstKey]);
        }

        return $data;
     }
  }
