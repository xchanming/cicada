<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Hmac;

use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\AppException;
use Cicada\Core\Framework\App\Hmac\Guzzle\AuthMiddleware;
use Cicada\Core\Framework\App\ShopId\ShopIdProvider;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Authentication\LocaleProvider;
use Cicada\Core\Framework\Store\InAppPurchase;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
class QuerySigner
{
    public function __construct(
        private readonly string $shopUrl,
        private readonly string $cicadaVersion,
        private readonly LocaleProvider $localeProvider,
        private readonly ShopIdProvider $shopIdProvider,
        private readonly InAppPurchase $inAppPurchase,
    ) {
    }

    public function signUri(string $uri, AppEntity $app, Context $context): UriInterface
    {
        $secret = $app->getAppSecret();
        if ($secret === null) {
            throw AppException::appSecretMissing($app->getName());
        }

        $uri = Uri::withQueryValues(new Uri($uri), [
            'shop-id' => $this->shopIdProvider->getShopId(),
            'shop-url' => $this->shopUrl,
            'timestamp' => (string) (new \DateTime())->getTimestamp(),
            'sw-version' => $this->cicadaVersion,
            'in-app-purchases' => \urlencode($this->inAppPurchase->getJWTByExtension($app->getName()) ?? ''),
            AuthMiddleware::CICADA_CONTEXT_LANGUAGE => $context->getLanguageId(),
            AuthMiddleware::CICADA_USER_LANGUAGE => $this->localeProvider->getLocaleFromContext($context),
        ]);

        return Uri::withQueryValue(
            $uri,
            'cicada-shop-signature',
            (new RequestSigner())->signPayload($uri->getQuery(), $secret)
        );
    }
}
