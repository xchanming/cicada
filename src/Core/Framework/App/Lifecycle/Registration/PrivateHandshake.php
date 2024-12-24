<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Lifecycle\Registration;

use Cicada\Core\Framework\Log\Package;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
class PrivateHandshake implements AppHandshakeInterface
{
    public function __construct(
        private readonly string $shopUrl,
        private readonly string $secret,
        private readonly string $appEndpoint,
        private readonly string $appName,
        private readonly string $shopId,
        private readonly string $cicadaVersion
    ) {
    }

    public function assembleRequest(): RequestInterface
    {
        $date = new \DateTime();
        $uri = new Uri($this->appEndpoint);

        $uri = Uri::withQueryValues($uri, [
            'shop-id' => $this->shopId,
            'shop-url' => $this->shopUrl,
            'timestamp' => (string) $date->getTimestamp(),
        ]);

        $signature = hash_hmac('sha256', $uri->getQuery(), $this->secret);

        return new Request(
            'GET',
            $uri,
            [
                'cicada-app-signature' => $signature,
                'sw-version' => $this->cicadaVersion,
            ]
        );
    }

    public function fetchAppProof(): string
    {
        return hash_hmac('sha256', $this->shopId . $this->shopUrl . $this->appName, $this->secret);
    }
}
