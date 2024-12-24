<?php declare(strict_types=1);

namespace Cicada\Storefront\Theme\Event;

use Cicada\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('storefront')]
class ThemeAssignedEvent extends Event
{
    public function __construct(
        private readonly string $themeId,
        private readonly string $salesChannelId
    ) {
    }

    public function getThemeId(): string
    {
        return $this->themeId;
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }
}
