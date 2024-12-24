<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\Infrastructure\Path;

use League\Flysystem\FilesystemOperator;
use Cicada\Core\Content\Media\Core\Application\AbstractMediaUrlGenerator;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal Concrete implementations of this class should not be extended or used as a base class/type hint.
 */
#[Package('buyers-experience')]
class MediaUrlGenerator extends AbstractMediaUrlGenerator
{
    public function __construct(private readonly FilesystemOperator $filesystem)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $paths): array
    {
        $urls = [];
        foreach ($paths as $key => $value) {
            if (str_starts_with($value->path, 'http')) {
                $url = $value->path;
            } else {
                $url = $this->filesystem->publicUrl($value->path);
            }

            if ($value->updatedAt !== null) {
                $url .= '?ts=' . $value->updatedAt->getTimestamp();
            }

            $urls[$key] = $url;
        }

        return $urls;
    }
}
