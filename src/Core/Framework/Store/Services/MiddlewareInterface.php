<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Store\Services;

use Cicada\Core\Framework\Log\Package;
use Psr\Http\Message\ResponseInterface;

/**
 * @internal
 */
#[Package('checkout')]
interface MiddlewareInterface
{
    public function __invoke(ResponseInterface $response): ResponseInterface;
}
