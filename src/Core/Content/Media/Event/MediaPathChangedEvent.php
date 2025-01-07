<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('discovery')]
class MediaPathChangedEvent extends Event
{
    /**
     * @var array<array{mediaId: string, thumbnailId: ?string, path: string}>
     */
    public array $changed = [];

    public function __construct(public Context $context)
    {
    }

    public function media(string $mediaId, string $path): void
    {
        $this->changed[] = [
            'mediaId' => $mediaId,
            'thumbnailId' => null,
            'path' => $path,
        ];
    }

    public function thumbnail(string $mediaId, string $thumbnailId, string $path): void
    {
        $this->changed[] = [
            'mediaId' => $mediaId,
            'thumbnailId' => $thumbnailId,
            'path' => $path,
        ];
    }
}
