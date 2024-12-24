<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Promotion\Exception;

use Cicada\Core\Checkout\Promotion\Aggregate\PromotionDiscount\PromotionDiscountEntity;
use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

/**
 * @deprecated tag:v6.7.0 - Will be removed. Use PromotionException::unknownPromotionDiscountType instead
 */
#[Package('buyers-experience')]
class UnknownPromotionDiscountTypeException extends CicadaHttpException
{
    public function __construct(PromotionDiscountEntity $discount)
    {
        parent::__construct(
            'Unknown promotion discount type detected: {{ type }}',
            ['type' => $discount->getType()]
        );
    }

    public function getErrorCode(): string
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', 'PromotionException::unknownPromotionDiscountType')
        );

        return 'CHECKOUT__UNKNOWN_PROMOTION_DISCOUNT_TYPE';
    }

    public function getStatusCode(): int
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', 'PromotionException::unknownPromotionDiscountType')
        );

        return Response::HTTP_BAD_REQUEST;
    }
}
