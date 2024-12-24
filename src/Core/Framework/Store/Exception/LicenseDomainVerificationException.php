<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Store\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
class LicenseDomainVerificationException extends CicadaHttpException
{
    public function __construct(
        string $domain,
        string $reason = ''
    ) {
        $reason = $reason ? (' ' . $reason) : '';
        $message = 'License host verification failed for domain "{{ domain }}.{{ reason }}"';
        parent::__construct($message, ['domain' => $domain, 'reason' => $reason]);
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__STORE_LICENSE_DOMAIN_VALIDATION_FAILED';
    }
}
