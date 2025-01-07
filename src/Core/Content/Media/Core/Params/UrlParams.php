<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\Core\Params;

use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;

#[Package('discovery')]
class UrlParams extends Struct
{
    public function __construct(
        public readonly string $id,
        public readonly UrlParamsSource $source,
        public readonly string $path,
        public readonly ?\DateTimeInterface $updatedAt = null
    ) {
    }

    public static function fromMedia(Entity $entity): self
    {
        return new self(
            id: $entity->getUniqueIdentifier(),
            source: UrlParamsSource::MEDIA,
            path: $entity->get('path'),
            updatedAt: $entity->get('updatedAt') ?? $entity->get('createdAt')
        );
    }

    public static function fromThumbnail(Entity $entity): self
    {
        return new self(
            id: $entity->getUniqueIdentifier(),
            source: UrlParamsSource::THUMBNAIL,
            path: $entity->get('path'),
            updatedAt: $entity->get('updatedAt') ?? $entity->get('createdAt')
        );
    }
}
