<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Adapter\Cache\Event;

use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class AddCacheTagEvent
{
    /**
     * @var string[]
     */
    public array $tags;

    public function __construct(string ...$tags)
    {
        $this->tags = $tags;
    }

    public function add(string ...$tags): self
    {
        $this->tags = array_merge($this->tags, $tags);

        return $this;
    }
}
