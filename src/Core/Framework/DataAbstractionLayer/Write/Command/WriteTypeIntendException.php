<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Write\Command;

use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('core')]
class WriteTypeIntendException extends CicadaHttpException
{
    public function __construct(
        EntityDefinition $definition,
        string $expectedClass,
        string $actualClass
    ) {
        $hint = match ([$expectedClass, $actualClass]) {
            [UpdateCommand::class, InsertCommand::class] => 'Hint: Use POST method to create new entities.',
            default => '',
        };
        parent::__construct(
            'Expected command for "{{ definition }}" to be "{{ expectedClass }}". (Got: {{ actualClass }})' . $hint,
            ['definition' => $definition->getEntityName(), 'expectedClass' => $expectedClass, 'actualClass' => $actualClass]
        );
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__WRITE_TYPE_INTEND_ERROR';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
