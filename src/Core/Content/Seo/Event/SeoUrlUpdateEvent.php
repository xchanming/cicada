<?php declare(strict_types=1);

namespace Cicada\Core\Content\Seo\Event;

use Cicada\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('buyers-experience')]
class SeoUrlUpdateEvent extends Event
{
    public function __construct(protected array $seoUrls)
    {
    }

    public function getSeoUrls(): array
    {
        return $this->seoUrls;
    }
}
