<?php declare(strict_types=1);

namespace Cicada\Core\Service;

use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\Exception\AppUrlChangeDetectedException;
use Cicada\Core\Framework\App\Hmac\Guzzle\AuthMiddleware;
use Cicada\Core\Framework\App\Payload\AppPayloadServiceHelper;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[Package('core')]
class ServiceClientFactory
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly ServiceRegistryClient $serviceRegistryClient,
        private readonly string $cicadaVersion,
        private readonly AuthMiddleware $authMiddleware,
        private readonly AppPayloadServiceHelper $appPayloadServiceHelper,
    ) {
    }

    public function newFor(ServiceRegistryEntry $entry): ServiceClient
    {
        return new ServiceClient(
            $this->client->withOptions([
                'base_uri' => $entry->host,
            ]),
            $this->cicadaVersion,
            $entry,
            new Filesystem()
        );
    }

    /**
     * @throws AppUrlChangeDetectedException
     */
    public function newAuthenticatedFor(ServiceRegistryEntry $entry, AppEntity $app, Context $context): AuthenticatedServiceClient
    {
        if (!$app->getAppSecret()) {
            throw ServiceException::missingAppSecretInfo($app->getId());
        }

        $stack = HandlerStack::create();
        $stack->push($this->authMiddleware);

        $authClient = new Client([
            'base_uri' => $entry->host,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            AuthMiddleware::APP_REQUEST_CONTEXT => $context,
            AuthMiddleware::APP_REQUEST_TYPE => [
                AuthMiddleware::APP_SECRET => $app->getAppSecret(),
            ],
            'handler' => $stack,
        ]);

        return new AuthenticatedServiceClient(
            $authClient,
            $entry,
            $this->appPayloadServiceHelper->buildSource($app->getVersion(), $app->getName())
        );
    }

    public function fromName(string $name): ServiceClient
    {
        return $this->newFor(
            $this->serviceRegistryClient->get($name)
        );
    }
}
