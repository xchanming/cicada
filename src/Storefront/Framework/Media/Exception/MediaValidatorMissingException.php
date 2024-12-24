<?php
declare(strict_types=1);

namespace Cicada\Storefront\Framework\Media\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;

#[Package('buyers-experience')]
class MediaValidatorMissingException extends CicadaHttpException
{
    public function __construct(string $type)
    {
        parent::__construct('No validator for {{ type }} was found.', ['type' => $type]);
    }

    public function getErrorCode(): string
    {
        return 'STOREFRONT__MEDIA_VALIDATOR_MISSING';
    }
}
