<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Routing;

use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Request;

#[Package('core')]
interface RequestTransformerInterface
{
    public function transform(Request $request): Request;

    /**
     * Return only attributes that should be inherited by subrequests
     *
     * @return array<string, mixed>
     */
    public function extractInheritableAttributes(Request $sourceRequest): array;
}
