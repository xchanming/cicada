<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Payload;

use Cicada\Core\Framework\Api\Serializer\JsonEntityEncoder;
use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\AppException;
use Cicada\Core\Framework\App\Exception\AppUrlChangeDetectedException;
use Cicada\Core\Framework\App\Hmac\Guzzle\AuthMiddleware;
use Cicada\Core\Framework\App\ShopId\ShopIdProvider;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\InAppPurchase;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
class AppPayloadServiceHelper
{
    /**
     * @internal
     */
    public function __construct(
        private readonly DefinitionInstanceRegistry $definitionRegistry,
        private readonly JsonEntityEncoder $entityEncoder,
        private readonly ShopIdProvider $shopIdProvider,
        private readonly InAppPurchase $inAppPurchase,
        private readonly string $shopUrl,
    ) {
    }

    /**
     * @throws AppUrlChangeDetectedException
     */
    public function buildSource(string $appVersion, string $appName): Source
    {
        return new Source(
            $this->shopUrl,
            $this->shopIdProvider->getShopId(),
            $appVersion,
            $this->inAppPurchase->getJWTByExtension($appName),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function encode(SourcedPayloadInterface $payload): array
    {
        $array = $payload->jsonSerialize();

        foreach ($array as $propertyName => $property) {
            if ($property instanceof SalesChannelContext) {
                $salesChannelContext = $property->jsonSerialize();

                foreach ($salesChannelContext as $subPropertyName => $subProperty) {
                    if (!$subProperty instanceof Entity) {
                        continue;
                    }

                    $salesChannelContext[$subPropertyName] = $this->encodeEntity($subProperty);
                }

                $array[$propertyName] = $salesChannelContext;
            }

            if (!$property instanceof Entity) {
                continue;
            }

            $array[$propertyName] = $this->encodeEntity($property);
        }

        return $array;
    }

    /**
     * @param array{timeout?: int} $additionalOptions
     */
    public function createRequestOptions(
        SourcedPayloadInterface $payload,
        AppEntity $app,
        Context $context,
        array $additionalOptions = []
    ): AppPayloadStruct {
        if (!$app->getAppSecret()) {
            throw AppException::registrationFailed($app->getName(), 'App secret is missing');
        }

        $defaultOptions = [
            AuthMiddleware::APP_REQUEST_CONTEXT => $context,
            AuthMiddleware::APP_REQUEST_TYPE => [
                AuthMiddleware::APP_SECRET => $app->getAppSecret(),
                AuthMiddleware::VALIDATED_RESPONSE => true,
            ],
            'headers' => ['Content-Type' => 'application/json'],
            'body' => $this->buildPayload($payload, $app),
        ];

        return new AppPayloadStruct(\array_merge($defaultOptions, $additionalOptions));
    }

    private function buildPayload(SourcedPayloadInterface $payload, AppEntity $app): string
    {
        $payload->setSource($this->buildSource($app->getVersion(), $app->getName()));
        $encoded = $this->encode($payload);

        return \json_encode($encoded, \JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, mixed>
     */
    private function encodeEntity(Entity $entity): array
    {
        $definition = $this->definitionRegistry->getByEntityName($entity->getApiAlias());

        return $this->entityEncoder->encode(
            new Criteria(),
            $definition,
            $entity,
            '/api'
        );
    }
}
