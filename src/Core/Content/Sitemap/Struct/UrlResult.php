<?php declare(strict_types=1);

namespace Cicada\Core\Content\Sitemap\Struct;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;

#[Package('services-settings')]
class UrlResult extends Struct
{
    /**
     * @param Url[] $urls
     */
    public function __construct(
        private readonly array $urls,
        private readonly ?int $nextOffset
    ) {
    }

    /**
     * @return Url[]
     */
    public function getUrls(): array
    {
        return $this->urls;
    }

    public function getNextOffset(): ?int
    {
        return $this->nextOffset;
    }

    public function getApiAlias(): string
    {
        return 'sitemap_url_result';
    }
}
