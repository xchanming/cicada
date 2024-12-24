<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Promotion\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

/**
 * @deprecated tag:v6.7.0 - Will be removed. Use PromotionException::promotionCodeNotFound instead
 */
#[Package('buyers-experience')]
class PromotionCodeNotFoundException extends CicadaHttpException
{
    public function __construct(string $code)
    {
        parent::__construct('Promotion Code "{{ code }}" has not been found!', ['code' => $code]);
    }

    public function getErrorCode(): string
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', 'use PromotionException::promotionCodeNotFound instead')
        );

        return 'CHECKOUT__CODE_NOT_FOUND';
    }

    public function getStatusCode(): int
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', 'use PromotionException::promotionCodeNotFound instead')
        );

        return Response::HTTP_BAD_REQUEST;
    }
}
