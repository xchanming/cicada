<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Store\InAppPurchase\Services;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\StoreException;
use Cicada\Core\Framework\Struct\Collection;
use Cicada\Core\Framework\Validation\ValidatorFactory;

/**
 * @internal
 *
 * @template-extends Collection<DecodedPurchaseStruct>
 */
#[Package('checkout')]
class DecodedPurchasesCollectionStruct extends Collection
{
    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $elements['elements'] = \array_map(static function (array $element): DecodedPurchaseStruct {
            $dto = ValidatorFactory::create($element, DecodedPurchaseStruct::class);
            if (!$dto instanceof DecodedPurchaseStruct) {
                throw StoreException::invalidType(DecodedPurchaseStruct::class, $dto::class);
            }

            return $dto;
        }, $data);

        return (new self())->assign($elements);
    }
}
