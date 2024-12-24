<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Processing\Mapping;

use Cicada\Core\Content\ImportExport\Struct\Config;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\AssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;

#[Package('services-settings')]
class CriteriaBuilder
{
    public function __construct(private readonly EntityDefinition $definition)
    {
    }

    public function enrichCriteria(Config $config, Criteria $criteria): Criteria
    {
        foreach ($config->getMapping() as $mapping) {
            $tmpDefinition = $this->definition;
            $parts = explode('.', $mapping->getKey());

            $prefix = '';

            foreach ($parts as $assoc) {
                if ($assoc === 'extensions') {
                    continue; // extension associations must also be joined if the field is in the mapping
                }

                $field = $tmpDefinition->getField($assoc);
                if (!$field || !$field instanceof AssociationField) {
                    break;
                }
                $criteria->addAssociation($prefix . $assoc);
                $prefix .= $assoc . '.';
                $tmpDefinition = $field->getReferenceDefinition();
            }
        }

        return $criteria;
    }
}
