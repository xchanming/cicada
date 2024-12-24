<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Store\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;

/**
 * @deprecated tag:v6.7.0 - unused class
 */
#[Package('checkout')]
class StoreSignatureValidationException extends CicadaHttpException
{
    public function __construct(string $reason)
    {
        parent::__construct(
            'Store signature validation failed. Error: {{ error }}',
            ['error' => $reason]
        );
    }

    public function getErrorCode(): string
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', Feature::deprecatedClassMessage(self::class, 'v6.7.0.0'));

        return 'FRAMEWORK__STORE_SIGNATURE_INVALID';
    }
}
