<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Write;

use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommandQueue;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('core')]
interface EntityWriteGatewayInterface
{
    public function prefetchExistences(WriteParameterBag $parameterBag): void;

    /**
     * @param array<string, string> $primaryKey
     * @param array<string, mixed> $data
     */
    public function getExistence(EntityDefinition $definition, array $primaryKey, array $data, WriteCommandQueue $commandQueue): EntityExistence;

    /**
     * @param list<WriteCommand> $commands
     */
    public function execute(array $commands, WriteContext $context): void;
}
