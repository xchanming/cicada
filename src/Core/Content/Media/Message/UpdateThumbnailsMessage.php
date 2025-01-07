<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\Message;

use Cicada\Core\Framework\Log\Package;

#[Package('discovery')]
class UpdateThumbnailsMessage extends GenerateThumbnailsMessage
{
    private bool $isStrict = false;

    public function isStrict(): bool
    {
        return $this->isStrict;
    }

    public function setIsStrict(bool $isStrict): void
    {
        $this->isStrict = $isStrict;
    }
}
