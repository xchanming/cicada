<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Rule\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;

#[Package('services-settings')]
class InvalidConditionException extends CicadaHttpException
{
    public function __construct(string $conditionName)
    {
        parent::__construct('The condition "{{ condition }}" is invalid.', ['condition' => $conditionName]);
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__INVALID_CONDITION_ERROR';
    }
}
