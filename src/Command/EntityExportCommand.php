<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Command;

use Throwable;
use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;

class EntityExportCommand extends Command
{
    public function __construct(private readonly DefinitionInstanceRegistry $definitionRegistry)
    {
        parent::__construct(); 
    }
   
    public function configure()
    {
        $this->setName('torq:entity-exporter')
            ->setDescription('Export entities');
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
            $ids = array_key_exists("ids",$entityConfig) ? $entityConfig["ids"] : [];
            $associations = array_key_exists("associations",$entityConfig) ? $entityConfig["associations"] : [];
            $excludeFields = array_key_exists("excludeFields",$entityConfig) ? $entityConfig["excludeFields"] : [];
            $extraCriteria = array_key_exists("criteria",$entityConfig) ? $entityConfig["criteria"] : [];
            $repo = $this->definitionRegistry->getRepository($entity);

            //if ids are set, add to the criteria plus add any associations and extra search criteria
            $criteria = empty($ids) ? new Criteria() : new Criteria($ids);
            $criteria = $this->addAssociations($criteria, $associations);    
            $criteria = $this->addCriteria($criteria, $extraCriteria);

            $elements = $repo->search($criteria, Context::createDefaultContext())->getEntities()->getElements();  

            $jsonOutput = $this->processUnwritableFields($repo,$elements,$associations,$excludeFields);

            $file = '/var/www/html/custom/data/' . $entity . '.json';
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
            switch($type){
                case 'EqualsAny':
                    $criteria->addFilter(new EqualsAnyFilter($field,$values));
                    break;
            }
        }
        return $criteria;
    }

    private function processUnwritableFields($repo, $elements, $associations, $excludeFields) {

        $json = json_decode(json_encode($elements, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR),true);

        $fields = $repo->getDefinition()->getFields();            
        //Process unwriteable fields at the top level
        $unwritableFields = [];
        $unwritableFields = array_merge($unwritableFields, $excludeFields);
        foreach($fields as $field){
            if($field->getFlag(WriteProtected::class) || $field instanceof ManyToOneAssociationField){
                $unwritableFields[] = $field->getPropertyName();
            }
        }
        //Process unwriteable fields for the associations
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
                    unset($element[$value]);
                }else{
                    $element = $this->removeKeyFromArray($values, $element);
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