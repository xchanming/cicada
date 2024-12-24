<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Api\ApiDefinition\Generator\OpenApi;

use Cicada\Core\Framework\Log\Package;
use OpenApi\Analysis;

#[Package('core')]
class DeactivateValidationAnalysis extends Analysis
{
    public function validate(): bool
    {
        return false;
        // deactivate Validitation
    }
}
