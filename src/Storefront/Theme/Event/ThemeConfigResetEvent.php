<?php declare(strict_types=1);

namespace Cicada\Storefront\Theme\Event;

use Cicada\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('framework')]
class ThemeConfigResetEvent extends Event
{
    public function __construct(private readonly string $themeId)
    {
    }

    public function getThemeId(): string
    {
        return $this->themeId;
    }
}
