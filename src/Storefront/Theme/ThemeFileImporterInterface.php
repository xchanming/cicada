<?php declare(strict_types=1);

namespace Cicada\Storefront\Theme;

use Cicada\Core\Framework\Adapter\Filesystem\Plugin\CopyBatchInput;
use Cicada\Core\Framework\Log\Package;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\File;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfiguration;

/**
 * @deprecated tag:v6.7.0 Will be removed.
 */
#[Package('framework')]
interface ThemeFileImporterInterface
{
    public function fileExists(string $filePath): bool;

    public function getRealPath(string $filePath): string;

    public function getConcatenableStylePath(File $file, StorefrontPluginConfiguration $configuration): string;

    public function getConcatenableScriptPath(File $file, StorefrontPluginConfiguration $configuration): string;

    /**
     * @return CopyBatchInput[]
     */
    public function getCopyBatchInputsForAssets(string $assetPath, string $outputPath, StorefrontPluginConfiguration $configuration): array;
}
