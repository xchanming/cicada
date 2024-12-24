<?php declare(strict_types=1);

namespace Cicada\Administration\Framework\Routing\KnownIps;

use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Request;

#[Package('administration')]
interface KnownIpsCollectorInterface
{
    /**
     * @return array<string, string>
     */
    public function collectIps(Request $request): array;
}
