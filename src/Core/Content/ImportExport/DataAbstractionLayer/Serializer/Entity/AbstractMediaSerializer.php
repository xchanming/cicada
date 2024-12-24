<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Entity;

use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
abstract class AbstractMediaSerializer extends EntitySerializer
{
    abstract public function persistMedia(EntityWrittenEvent $event): void;
}
