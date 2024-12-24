<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Api\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;

#[Package('core')]
class NoEntityClonedException extends CicadaHttpException
{
    public function __construct(
        string $entity,
        string $id
    ) {
        parent::__construct(
            'Could not clone entity {{ entity }} with id {{ id }}.',
            ['entity' => $entity, 'id' => $id]
        );
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__NO_ENTITIY_CLONED_ERROR';
    }
}
