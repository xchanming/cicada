<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class ApiProtectionException extends CicadaHttpException
{
    public function __construct(string $accessor)
    {
        parent::__construct(
            'Accessor {{ accessor }} is not allowed in this api scope',
            ['accessor' => $accessor]
        );
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__ACCESSOR_NOT_ALLOWED';
    }
}
