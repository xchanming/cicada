<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Lifecycle\Registration;

use Cicada\Core\Framework\App\AppCollection;
use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\AppException;
use Cicada\Core\Framework\App\Exception\AppRegistrationException;
use Cicada\Core\Framework\App\Exception\AppUrlChangeDetectedException;
use Cicada\Core\Framework\App\Hmac\Guzzle\AuthMiddleware;
use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\App\ShopId\ShopIdProvider;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
class AppRegistrationService
{
    /**
     * @param EntityRepository<AppCollection> $appRepository
     */
    public function __construct(
        private readonly HandshakeFactory $handshakeFactory,
        private readonly Client $httpClient,
        private readonly EntityRepository $appRepository,
        private readonly string $shopUrl,
        private readonly ShopIdProvider $shopIdProvider,
        private readonly string $cicadaVersion
    ) {
    }

    public function registerApp(Manifest $manifest, string $id, string $secretAccessKey, Context $context): void
    {
        if (!$manifest->getSetup()) {
            return;
        }

        try {
            $appName = $manifest->getMetadata()->getName();
            $appResponse = $this->registerWithApp($manifest, $context);

            $secret = $appResponse['secret'];
            $confirmationUrl = $appResponse['confirmation_url'];

            $this->saveAppSecret($id, $context, $secret);

            $this->confirmRegistration($id, $context, $secret, $secretAccessKey, $confirmationUrl);
        } catch (RequestException $e) {
            if ($e->hasResponse() && $e->getResponse() !== null) {
                $response = $e->getResponse();
                $data = json_decode($response->getBody()->getContents(), true);

                if (isset($data['error']) && \is_string($data['error'])) {
                    throw AppException::registrationFailed($appName, $data['error']);
                }
            }

            throw AppException::registrationFailed($appName, $e->getMessage(), $e);
        } catch (GuzzleException $e) {
            throw AppException::registrationFailed($appName, $e->getMessage(), $e);
        }
    }

    /**
     * @throws GuzzleException
     *
     * @return array<string, string>
     */
    private function registerWithApp(Manifest $manifest, Context $context): array
    {
        $handshake = $this->handshakeFactory->create($manifest);

        $request = $handshake->assembleRequest();
        $response = $this->httpClient->send($request, [AuthMiddleware::APP_REQUEST_CONTEXT => $context]);

        return $this->parseResponse($manifest->getMetadata()->getName(), $handshake, $response);
    }

    private function saveAppSecret(string $id, Context $context, string $secret): void
    {
        $update = ['id' => $id, 'appSecret' => $secret];

        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($update): void {
            $this->appRepository->update([$update], $context);
        });
    }

    private function confirmRegistration(
        string $id,
        Context $context,
        string $secret,
        string $secretAccessKey,
        string $confirmationUrl
    ): void {
        $payload = $this->getConfirmationPayload($id, $secretAccessKey, $context);

        $signature = $this->signPayload($payload, $secret);

        $this->httpClient->post($confirmationUrl, [
            'headers' => [
                'cicada-shop-signature' => $signature,
                'sw-version' => $this->cicadaVersion,
            ],
            AuthMiddleware::APP_REQUEST_CONTEXT => $context,
            'json' => $payload,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function parseResponse(
        string $appName,
        AppHandshakeInterface $handshake,
        ResponseInterface $response
    ): array {
        try {
            $data = json_decode($response->getBody()->getContents(), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw AppException::registrationFailed($appName, 'JSON response could not be decoded', $e);
        }

        if (isset($data['error']) && \is_string($data['error'])) {
            throw AppException::registrationFailed($appName, $data['error']);
        }

        $proof = $data['proof'] ?? '';

        if (!\is_string($proof)) {
            throw AppException::registrationFailed($appName, 'The app server provided no proof');
        }

        if (!hash_equals($handshake->fetchAppProof(), trim($proof))) {
            throw AppException::registrationFailed($appName, 'The app server provided an invalid proof');
        }

        return $data;
    }

    /**
     * @return array<string, string>
     */
    private function getConfirmationPayload(string $id, string $secretAccessKey, Context $context): array
    {
        $app = $this->getApp($id, $context);

        try {
            $shopId = $this->shopIdProvider->getShopId();
        } catch (AppUrlChangeDetectedException) {
            throw AppRegistrationException::registrationFailed(
                $app->getName(),
                'The app url changed. Please resolve how the apps should handle this change.'
            );
        }

        // We can safely assume that the app has an integration because it is created together with the app
        // and explicitly fetched in the ::getApp() method below.
        $integration = $app->getIntegration();
        \assert($integration !== null);

        return [
            'apiKey' => $integration->getAccessKey(),
            'secretKey' => $secretAccessKey,
            'timestamp' => (string) (new \DateTime())->getTimestamp(),
            'shopUrl' => $this->shopUrl,
            'shopId' => $shopId,
        ];
    }

    /**
     * @param array<string, string> $body
     */
    private function signPayload(array $body, string $secret): string
    {
        return hash_hmac('sha256', (string) json_encode($body, \JSON_THROW_ON_ERROR), $secret);
    }

    private function getApp(string $id, Context $context): AppEntity
    {
        $criteria = new Criteria([$id]);
        $criteria->addAssociation('integration');

        $app = $this->appRepository->search($criteria, $context)->getEntities()->first();
        \assert($app !== null);

        return $app;
    }
}
