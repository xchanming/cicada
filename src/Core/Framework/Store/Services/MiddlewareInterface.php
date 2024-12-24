<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Store\Services;

use Psr\Http\Message\ResponseInterface;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('checkout')]
interface MiddlewareInterface
{
    public function __invoke(ResponseInterface $response): ResponseInterface;
}
