<?php declare(strict_types=1);

namespace Cicada\Core\Test\Stub\DataAbstractionLayer;

use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommandQueue;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityWriteGatewayInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;

/**
 * @final
 */
class StaticEntityWriterGateway implements EntityWriteGatewayInterface
{
    public function prefetchExistences(WriteParameterBag $parameterBag): void
    {
    }

    public function getExistence(EntityDefinition $definition, array $primaryKey, array $data, WriteCommandQueue $commandQueue): EntityExistence
    {
        return new EntityExistence($definition->getEntityName(), $primaryKey, false, false, false, []);
    }

    public function execute(array $commands, WriteContext $context): void
    {
    }
}
