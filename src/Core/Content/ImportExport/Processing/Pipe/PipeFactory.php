<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Processing\Pipe;

use Cicada\Core\Content\ImportExport\Aggregate\ImportExportLog\ImportExportLogEntity;
use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\PrimaryKeyResolver;
use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\SerializerRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\Log\Package;

#[Package('services-settings')]
class PipeFactory extends AbstractPipeFactory
{
    /**
     * @internal
     */
    public function __construct(
        private readonly DefinitionInstanceRegistry $definitionInstanceRegistry,
        private readonly SerializerRegistry $serializerRegistry,
        private readonly PrimaryKeyResolver $primaryKeyResolver
    ) {
    }

    public function create(ImportExportLogEntity $logEntity): AbstractPipe
    {
        $pipe = new ChainPipe([
            new EntityPipe(
                $this->definitionInstanceRegistry,
                $this->serializerRegistry,
                null,
                null,
                $this->primaryKeyResolver
            ),
            new KeyMappingPipe(),
        ]);

        return $pipe;
    }

    public function supports(ImportExportLogEntity $logEntity): bool
    {
        return true;
    }
}
