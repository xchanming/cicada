<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Store\Api;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Api\FirstRunWizardController;
use Cicada\Core\Framework\Store\Authentication\StoreRequestOptionsProvider;
use Cicada\Core\Framework\Store\Event\FirstRunWizardFinishedEvent;
use Cicada\Core\Framework\Store\Event\FirstRunWizardStartedEvent;
use Cicada\Core\Framework\Store\Services\FirstRunWizardService;
use Cicada\Core\Framework\Test\Store\StoreClientBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\EventDispatcherBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\DataBag\QueryDataBag;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Doctrine\DBAL\Connection;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
class FirstRunWizardControllerTest extends TestCase
{
    use EventDispatcherBehaviour;
    use IntegrationTestBehaviour;
    use StoreClientBehaviour;

    private FirstRunWizardController $frwController;

    protected function setUp(): void
    {
        $this->frwController = static::getContainer()->get(FirstRunWizardController::class);
    }

    public function testFrwStartFiresTrackingEventAndDispatchesStartedEvent(): void
    {
        $dispatchedEvent = null;

        // Response for request of TrackingEventClient::fireTrackingEvent()
        $this->getStoreRequestHandler()->append(new Response());

        $this->addEventListener(
            static::getContainer()->get('event_dispatcher'),
            FirstRunWizardStartedEvent::class,
            function (FirstRunWizardStartedEvent $event) use (&$dispatchedEvent): void {
                $dispatchedEvent = $event;
            }
        );

        $this->frwController->frwStart(Context::createDefaultContext());

        static::assertInstanceOf(FirstRunWizardStartedEvent::class, $dispatchedEvent);

        $lastRequest = $this->getStoreRequestHandler()->getLastRequest();
        static::assertInstanceOf(Request::class, $lastRequest);
        static::assertEquals('POST', $lastRequest->getMethod());
        static::assertEquals('/swplatform/tracking/events', $lastRequest->getUri()->getPath());
    }

    public function testFrwLoginStoresFrwUserToken(): void
    {
        $frwUserToken = 'frw-us3r-t0k3n';
        $expirationDate = new \DateTimeImmutable('2022-12-15');
        $context = $this->createAdminStoreContext();

        // Response for request of FirstRunWizardClient::frwLogin()
        $this->getFrwRequestHandler()->append(new Response(
            body: json_encode([
                'firstRunWizardUserToken' => [
                    'token' => $frwUserToken,
                    'expirationDate' => $expirationDate->format(Defaults::STORAGE_DATE_FORMAT),
                ],
            ], \JSON_THROW_ON_ERROR),
        ));

        $this->frwController->frwLogin(
            new RequestDataBag([
                'cicadaId' => 'cicada-id',
                'password' => 'p4ssw0rd',
            ]),
            $context
        );

        static::assertEquals(
            $frwUserToken,
            $this->fetchUserConfig(FirstRunWizardService::USER_CONFIG_KEY_FRW_USER_TOKEN, FirstRunWizardService::USER_CONFIG_VALUE_FRW_USER_TOKEN)
        );
    }

    public function testUpgradesFrwTokenToStoreTokenOnSuccessfulFrwFinish(): void
    {
        $dispatchedEvent = null;
        $shopUserToken = 'sh0p-us3r-t0k3n';
        $shopSecret = 'sh0p-s3cr3t';
        $context = $this->createAdminStoreContext();

        $this->setFrwUserToken($context, 'frw-us3r-t0k3n');

        $this->addEventListener(
            static::getContainer()->get('event_dispatcher'),
            FirstRunWizardFinishedEvent::class,
            function (FirstRunWizardFinishedEvent $event) use (&$dispatchedEvent): void {
                $dispatchedEvent = $event;
                $event->stopPropagation();
            },
            99999,
        );

        // Response for request of TrackEventClient::fireTrackingEvent()
        $this->getStoreRequestHandler()->append(new Response());

        // Response for request of FirstRunWizardClient::upgradeAccessToken()
        $this->getFrwRequestHandler()->append(new Response(
            body: json_encode([
                'shopUserToken' => [
                    'token' => $shopUserToken,
                    'expirationDate' => (new \DateTimeImmutable('2022-12-15'))->format(Defaults::STORAGE_DATE_FORMAT),
                ],
                'shopSecret' => $shopSecret,
            ], \JSON_THROW_ON_ERROR),
        ));

        $this->frwController->frwFinish(
            new QueryDataBag([
                'failed' => false,
            ]),
            $context
        );

        static::assertInstanceOf(FirstRunWizardFinishedEvent::class, $dispatchedEvent);
        static::assertNull(
            $this->fetchUserConfig(FirstRunWizardService::USER_CONFIG_KEY_FRW_USER_TOKEN, FirstRunWizardService::USER_CONFIG_VALUE_FRW_USER_TOKEN)
        );
        static::assertEquals(
            $shopUserToken,
            $this->fetchStoreToken($context)
        );
        static::assertEquals(
            $shopSecret,
            static::getContainer()->get(SystemConfigService::class)->getString(StoreRequestOptionsProvider::CONFIG_KEY_STORE_SHOP_SECRET)
        );
    }

    public function testSetsLicenseHostAndShopSecretONSuccessfulDomainVerification(): void
    {
        $context = $this->createAdminStoreContext();

        // Response for first request of FirstRunWizardClient::getLicenseDomains()
        $this->getFrwRequestHandler()->append(new Response(
            body: json_encode([], \JSON_THROW_ON_ERROR),
        ));

        // Response for request of FirstRunWizardClient::fetchVerificationInfo()
        $this->getFrwRequestHandler()->append(new Response(
            body: json_encode([
                'fileName' => 'sw-verification-hash.html',
                'content' => 'sw-v3rific4ti0n-h4sh',
            ], \JSON_THROW_ON_ERROR),
        ));

        // Response for request of FirstRunWizardClient::checkVerificationSecret()
        $this->getFrwRequestHandler()->append(new Response());

        // Response for second request of FirstRunWizardClient::getLicenseDomains()
        $this->getFrwRequestHandler()->append(new Response(
            body: json_encode([
                [
                    'id' => 123456,
                    'domain' => 'cicada.swag',
                    'verified' => true,
                    'edition' => [
                        'name' => 'Community Edition',
                        'label' => 'Community Edition',
                    ],
                ],
            ], \JSON_THROW_ON_ERROR),
        ));

        $this->frwController->verifyDomain(
            new QueryDataBag([
                'domain' => 'cicada.swag',
                'testEnvironment' => true,
            ]),
            $context,
        );

        static::assertEquals(
            'cicada.swag',
            static::getContainer()->get(SystemConfigService::class)->getString(StoreRequestOptionsProvider::CONFIG_KEY_STORE_LICENSE_DOMAIN)
        );
    }

    private function fetchUserConfig(string $configKey, string $valueKey): ?string
    {
        $value = static::getContainer()->get(Connection::class)->executeQuery(
            'SELECT value FROM user_config WHERE `key` = :key',
            ['key' => $configKey]
        )->fetchOne();

        return $value ? json_decode($value, true, flags: \JSON_THROW_ON_ERROR)[$valueKey] : null;
    }

    private function fetchStoreToken(Context $context): ?string
    {
        $source = $context->getSource();
        static::assertInstanceOf(AdminApiSource::class, $source);

        $userId = $source->getUserId();
        static::assertIsString($userId);

        $storeToken = static::getContainer()->get(Connection::class)->executeQuery(
            'SELECT store_token FROM user WHERE `id` = :userId',
            ['userId' => Uuid::fromHexToBytes($userId)]
        )->fetchOne();

        return $storeToken ?: null;
    }
}
