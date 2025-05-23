<?php

namespace Torq\Shopware\Common\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;

class EntityImporterExporterIdHasher
{
    
    public function __construct(private readonly DefinitionInstanceRegistry $definitionRegistry){

    }

    public function hashIdsForImport(string $entityName, array $data): array
    {
        $definition = $this->definitionRegistry->getByEntityName($entityName);

        $fields = $definition->getFields();

        foreach($data as $key => $value){
            foreach($fields as $field){

                if (
                    (
                        $field instanceof IdField
                        ||
                        (
                            $field instanceof FkField
                            &&
                            $field->getReferenceDefinition()->getField($field->getReferenceField()) instanceof IdField
                        )
                    ) 
                    &&
                    array_key_exists($field->getPropertyName(),$data[$key])
                    &&
                    $data[$key][$field->getPropertyName()] !== null
                    &&
                    !Uuid::isValid($data[$key][$field->getPropertyName()] ?? '')
                ){
                   $data[$key][$field->getPropertyName()] = md5($data[$key][$field->getPropertyName()]);
                }
            }
        }
        
        return $data;
    }

    public function hashIdsForExport(array $ids): array
    {
        return array_map(function($id){
            if(!Uuid::isValid($id)){
                return md5($id);
            }

            return $id;
        },$ids);
    }
}
 
 