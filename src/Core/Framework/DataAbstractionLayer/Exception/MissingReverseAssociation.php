<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;

#[Package('core')]
class MissingReverseAssociation extends CicadaHttpException
{
    public function __construct(
        string $source,
        string $target
    ) {
        parent::__construct(
            'Can not find reverse association in entity {{ source }} which should have an association to entity {{ target }}',
            ['source' => $source, 'target' => $target]
        );
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__MISSING_REVERSE_ASSOCIATION';
    }
}
