<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Document\Renderer;

use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
final class DocumentRendererConfig
{
    public string $deepLinkCode = '';
}
