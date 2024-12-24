<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Lifecycle\Registration;

use Cicada\Core\Framework\Log\Package;
use Psr\Http\Message\RequestInterface;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
interface AppHandshakeInterface
{
    public function assembleRequest(): RequestInterface;

    public function fetchAppProof(): string;
}
