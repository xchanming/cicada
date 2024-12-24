<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Store\Struct;

use Cicada\Core\Framework\Log\Package;

/**
 * @codeCoverageIgnore
 *
 * @template-extends StoreCollection<ReviewStruct>
 */
#[Package('checkout')]
class ReviewCollection extends StoreCollection
{
    protected function getExpectedClass(): ?string
    {
        return ReviewStruct::class;
    }

    protected function getElementFromArray(array $element): StoreStruct
    {
        return ReviewStruct::fromArray($element);
    }
}
