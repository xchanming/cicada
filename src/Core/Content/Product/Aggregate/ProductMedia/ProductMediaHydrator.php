<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\Aggregate\ProductMedia;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\EntityHydrator;
use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;

#[Package('inventory')]
class ProductMediaHydrator extends EntityHydrator
{
    protected function assign(EntityDefinition $definition, Entity $entity, string $root, array $row, Context $context): Entity
    {
        if (isset($row[$root . '.id'])) {
            $entity->id = Uuid::fromBytesToHex($row[$root . '.id']);
        }
        if (isset($row[$root . '.versionId'])) {
            $entity->versionId = Uuid::fromBytesToHex($row[$root . '.versionId']);
        }
        if (isset($row[$root . '.productId'])) {
            $entity->productId = Uuid::fromBytesToHex($row[$root . '.productId']);
        }
        if (isset($row[$root . '.mediaId'])) {
            $entity->mediaId = Uuid::fromBytesToHex($row[$root . '.mediaId']);
        }
        if (isset($row[$root . '.position'])) {
            $entity->position = (int) $row[$root . '.position'];
        }
        if (\array_key_exists($root . '.customFields', $row)) {
            $entity->customFields = $definition->decode('customFields', self::value($row, $root, 'customFields'));
        }
        if (isset($row[$root . '.createdAt'])) {
            $entity->createdAt = new \DateTimeImmutable($row[$root . '.createdAt']);
        }
        if (isset($row[$root . '.updatedAt'])) {
            $entity->updatedAt = new \DateTimeImmutable($row[$root . '.updatedAt']);
        }
        $entity->product = $this->manyToOne($row, $root, $definition->getField('product'), $context);
        $entity->media = $this->manyToOne($row, $root, $definition->getField('media'), $context);

        $this->translate($definition, $entity, $row, $root, $context, $definition->getTranslatedFields());
        $this->hydrateFields($definition, $entity, $root, $row, $context, $definition->getExtensionFields());
        $this->customFields($definition, $row, $root, $entity, $definition->getField('customFields'), $context);

        return $entity;
    }
}
