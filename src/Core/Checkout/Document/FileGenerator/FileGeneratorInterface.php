<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Document\FileGenerator;

use Cicada\Core\Checkout\Document\Renderer\RenderedDocument;
use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
interface FileGeneratorInterface
{
    public function supports(): string;

    public function generate(RenderedDocument $html): string;

    public function getExtension(): string;

    public function getContentType(): string;
}
