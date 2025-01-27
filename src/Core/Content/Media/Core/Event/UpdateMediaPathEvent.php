<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\Core\Event;

use Cicada\Core\Framework\Log\Package;

/**
 * @implements \IteratorAggregate<array-key, string>
 *
 * This event can be dispatch, to generate the path for a media afterward and store it in the database.
 * The `MediaSubscriber` will listen to this event and generate the path for the media.
 */
#[Package('discovery')]
class UpdateMediaPathEvent implements \IteratorAggregate
{
    /**
     * @param array<string> $ids
     */
    public function __construct(public readonly array $ids)
    {
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->ids);
    }
}
