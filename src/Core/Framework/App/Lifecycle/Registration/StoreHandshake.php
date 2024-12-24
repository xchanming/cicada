<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Lifecycle\Registration;

use Cicada\Core\Framework\App\AppException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Services\StoreClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
class StoreHandshake implements AppHandshakeInterface
{
    private const SBP_EXCEPTION_UNAUTHORIZED = 'CicadaPlatformException-1';

    private const SBP_EXCEPTION_NO_LICENSE = 'CicadaPlatformException-16';

    public function __construct(
        private readonly string $shopUrl,
        private readonly string $appEndpoint,
        private readonly string $appName,
        private readonly string $shopId,
        private readonly StoreClient $storeClient,
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

        $signature = $this->signPayload($uri->getQuery());

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
        $proof = $this->shopId . $this->shopUrl . $this->appName;

        return $this->storeClient->signPayloadWithAppSecret($proof, $this->appName);
    }

    private function signPayload(string $payload): string
    {
        try {
            return $this->storeClient->signPayloadWithAppSecret($payload, $this->appName);
        } catch (\Exception $e) {
            if ($e instanceof ClientException) {
                $response = \json_decode($e->getResponse()->getBody()->getContents(), true, \JSON_THROW_ON_ERROR, \JSON_THROW_ON_ERROR);

                if ($response['code'] === self::SBP_EXCEPTION_UNAUTHORIZED || $response['code'] === self::SBP_EXCEPTION_NO_LICENSE) {
                    throw AppException::licenseCouldNotBeVerified($this->appName, $e);
                }
            }

            throw AppException::registrationFailed(
                $this->appName,
                'Could not sign payload with store secret',
                $e
            );
        }
    }
}
