<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Template;

use Cicada\Core\Framework\App\AppException;
use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\App\Source\SourceResolver;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\Finder\Finder;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
class TemplateLoader extends AbstractTemplateLoader
{
    private const TEMPLATE_DIR = '/Resources/views';

    private const ALLOWED_TEMPLATE_DIRS = [
        'storefront',
    ];

    private const ALLOWED_FILE_EXTENSIONS = '*.twig';

    public function __construct(private readonly SourceResolver $sourceResolver)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplatePathsForApp(Manifest $app): array
    {
        $fs = $this->sourceResolver->filesystemForManifest($app);

        if (!$fs->has(self::TEMPLATE_DIR)) {
            return [];
        }

        $viewDirectory = $fs->path(self::TEMPLATE_DIR);

        $finder = new Finder();
        $finder->files()
            ->in($viewDirectory)
            ->name(self::ALLOWED_FILE_EXTENSIONS)
            ->path(self::ALLOWED_TEMPLATE_DIRS)
            ->ignoreUnreadableDirs();

        return array_values(array_map(static fn (\SplFileInfo $file): string => ltrim(mb_substr($file->getPathname(), mb_strlen($viewDirectory)), '/'), iterator_to_array($finder)));
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateContent(string $path, Manifest $app): string
    {
        $fs = $this->sourceResolver->filesystemForManifest($app);

        if (!$fs->has(self::TEMPLATE_DIR, $path)) {
            throw AppException::cannotReadFile($fs->path(self::TEMPLATE_DIR, $path));
        }

        return $fs->read(self::TEMPLATE_DIR, $path);
    }
}
