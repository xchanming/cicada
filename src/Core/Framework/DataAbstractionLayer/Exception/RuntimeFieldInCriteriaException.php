<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;

#[Package('core')]
class RuntimeFieldInCriteriaException extends CicadaHttpException
{
    public function __construct(string $field)
    {
        parent::__construct(
            'Field {{ field }} is a Runtime field and cannot be used in a criteria',
            ['field' => $field]
        );
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__RUNTIME_FIELD_IN_CRITERIA';
    }
}
