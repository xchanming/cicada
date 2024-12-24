<?php
declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;

#[Package('core')]
class ImpossibleWriteOrderException extends CicadaHttpException
{
    public function __construct(array $remaining)
    {
        parent::__construct(
            'Can not resolve write order for provided data. Remaining write order classes: {{ classesString }}',
            ['classes' => $remaining, 'classesString' => implode(', ', $remaining)]
        );
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__IMPOSSIBLE_WRITE_ORDER';
    }
}
