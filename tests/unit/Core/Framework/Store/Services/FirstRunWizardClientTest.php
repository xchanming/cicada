<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Store\Services;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Authentication\AbstractStoreRequestOptionsProvider;
use Cicada\Core\Framework\Store\Services\FirstRunWizardClient;
use Cicada\Core\Framework\Store\Services\InstanceService;
use Cicada\Core\Framework\Uuid\Uuid;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(FirstRunWizardClient::class)]
class FirstRunWizardClientTest extends TestCase
{
    private Context $context;

    protected function setUp(): void
    {
        $this->context = new Context(new AdminApiSource(Uuid::randomHex()));
    }

    public function testFrwLogin(): void
    {
        $firstRunWizardUserToken = [
            'firstRunWizardUserToken' => [
                'token' => 'frw-us3r-t0k3n',
                'expirationDate' => (new \DateTimeImmutable('2021-01-01 00:00:00'))->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ],
        ];

        $frwClient = $this->createFrwClient(
            [
                Request::METHOD_POST,
                '/swplatform/firstrunwizard/login',
                [
                    'json' => [
                        'cicadaId' => 'j.doe@xchanming.com',
                        'password' => 'p4ssw0rd',
                    ],
                    'query' => [],
                ],
            ],
            $firstRunWizardUserToken
        );

        static::assertEquals(
            $firstRunWizardUserToken,
            $frwClient->frwLogin('j.doe@xchanming.com', 'p4ssw0rd', $this->context)
        );
    }

    public function testFrwLoginFailsIfContextSourceIsNotAdminApi(): void
    {
        $context = Context::createDefaultContext();

        $client = $this->createMock(ClientInterface::class);
        $client->expects(static::never())
            ->method('request');

        $frwClient = new FirstRunWizardClient(
            $client,
            $this->createMock(AbstractStoreRequestOptionsProvider::class),
            $this->createMock(InstanceService::class)
        );

        $this->expectException(\RuntimeException::class);
        $frwClient->frwLogin('cicadaId', 'password', $context);
    }

    public function testFrwLoginFailsIfAdminApiSourceHasNoUserId(): void
    {
        $context = Context::createDefaultContext(
            new AdminApiSource(null),
        );

        $client = $this->createMock(ClientInterface::class);
        $client->expects(static::never())
            ->method('request');

        $frwClient = new FirstRunWizardClient(
            $client,
            $this->createMock(AbstractStoreRequestOptionsProvider::class),
            $this->createMock(InstanceService::class)
        );

        $this->expectException(\RuntimeException::class);
        $frwClient->frwLogin('cicadaId', 'password', $context);
    }

    public function testUpgradeAccessTokenFailsIfUserIsNotLoggedIn(): void
    {
        $context = Context::createDefaultContext();

        $client = $this->createMock(ClientInterface::class);
        $client->expects(static::never())
            ->method('request');

        $frwClient = new FirstRunWizardClient(
            $client,
            $this->createMock(AbstractStoreRequestOptionsProvider::class),
            $this->createMock(InstanceService::class)
        );

        $this->expectException(\RuntimeException::class);
        $frwClient->upgradeAccessToken($context);
    }

    public function testUpgradeAccessToken(): void
    {
        $shopUserToken = [
            'shopUserToken' => [
                'token' => 'store-us3r-t0k3n',
                'expirationDate' => (new \DateTimeImmutable('2021-01-01 00:00:00'))->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ],
        ];

        static::assertInstanceOf(AdminApiSource::class, $this->context->getSource());

        $frwClient = $this->createFrwClient(
            [
                Request::METHOD_POST,
                '/swplatform/login/upgrade',
                [
                    'json' => [
                        'cicadaUserId' => $this->context->getSource()->getUserId(),
                    ],
                    'query' => [],
                    'headers' => [],
                ],
            ],
            $shopUserToken
        );

        static::assertEquals(
            $shopUserToken,
            $frwClient->upgradeAccessToken($this->context)
        );
    }

    public function testGetRecommendationRegions(): void
    {
        $regions = [
            'regions' => [
                'DE',
                'US',
            ],
        ];

        $frwClient = $this->createFrwClient(
            [
                Request::METHOD_GET,
                '/swplatform/firstrunwizard/categories',
                [
                    'query' => [],
                ],
            ],
            $regions
        );

        static::assertEquals(
            $regions,
            $frwClient->getRecommendationRegions($this->context)
        );
    }

    public function testGetRecommendations(): void
    {
        $recommendations = [
            'iconPath' => 'https://icon.path',
            'id' => 123456,
            'isCategoryLead' => false,
            'localizedInfo' => [
                'name' => 'SwagLanguagePack',
                'label' => 'Cicada Language Pack',
            ],
            'name' => 'SwagLanguagePack',
            'priority' => 1,
            'producer' => [
                'name' => 'cicada AG',
            ],
        ];

        $frwClient = $this->createFrwClient(
            [
                Request::METHOD_GET,
                '/swplatform/firstrunwizard/plugins',
                [
                    'query' => [
                        'market' => 'us-west',
                        'category' => 'payment',
                    ],
                ],
            ],
            $recommendations
        );

        static::assertEquals(
            $recommendations,
            $frwClient->getRecommendations('us-west', 'payment', $this->context)
        );
    }

    public function testGetLanguagePlugins(): void
    {
        $languagePlugins = [
            [
                'id' => 123456,
                'name' => 'SwagLanguagePack',
            ],
        ];

        $frwClient = $this->createFrwClient(
            [
                Request::METHOD_GET,
                '/swplatform/firstrunwizard/localizations',
                [
                    'query' => [],
                ],
            ],
            $languagePlugins
        );

        static::assertEquals(
            $languagePlugins,
            $frwClient->getLanguagePlugins($this->context)
        );
    }

    public function testGetDemodataPlugins(): void
    {
        $languagePlugins = [
            [
                'id' => 123456,
                'name' => 'SwagLanguagePack',
            ],
        ];

        $frwClient = $this->createFrwClient(
            [
                Request::METHOD_GET,
                '/swplatform/firstrunwizard/demodataplugins',
                [
                    'query' => [],
                ],
            ],
            $languagePlugins
        );

        static::assertEquals(
            $languagePlugins,
            $frwClient->getDemoDataPlugins($this->context)
        );
    }

    public function testGetLicenseDomains(): void
    {
        $licenseDomains = [
            [
                'id' => 123456,
                'domain' => 'cicada.swag',
            ],
        ];

        $frwClient = $this->createFrwClient(
            [
                Request::METHOD_GET,
                '/swplatform/firstrunwizard/shops',
                [
                    'query' => [],
                    'headers' => [],
                ],
            ],
            $licenseDomains
        );

        static::assertEquals(
            $licenseDomains,
            $frwClient->getLicenseDomains($this->context)
        );
    }

    public function testCheckVerificationSecret(): void
    {
        $frwClient = $this->createFrwClient(
            [
                Request::METHOD_POST,
                '/swplatform/firstrunwizard/shops',
                [
                    'headers' => [],
                    'json' => [
                        'domain' => 'cicada.swag',
                        'cicadaVersion' => '',
                        'testEnvironment' => true,
                    ],
                ],
            ],
            []
        );

        $frwClient->checkVerificationSecret('cicada.swag', $this->context, true);
    }

    public function testFetchVerificationInfo(): void
    {
        $verificationInfo = [
            [
                'content' => 'sw-v3rific4t0n-h4sh',
                'fileName' => 'sw-domain-hash.html',
            ],
        ];

        $frwClient = $this->createFrwClient(
            [
                Request::METHOD_POST,
                '/swplatform/firstrunwizard/shopdomainverificationhash',
                [
                    'headers' => [],
                    'json' => [
                        'domain' => 'cicada.swag',
                    ],
                    'query' => [],
                ],
            ],
            $verificationInfo
        );

        static::assertEquals(
            $verificationInfo,
            $frwClient->fetchVerificationInfo('cicada.swag', $this->context)
        );
    }

    /**
     * @param array{string, string, array{headers?: array<string, string>, query?: array<string, string>, json?: array<mixed>}} $requestParams
     * @param array<mixed> $responseBody
     */
    private function createFrwClient(array $requestParams, array $responseBody): FirstRunWizardClient
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects(static::once())
            ->method('request')
            ->with(...$requestParams)
            ->willReturn(new Response(body: json_encode($responseBody, \JSON_THROW_ON_ERROR)));

        return new FirstRunWizardClient(
            $client,
            $this->createMock(AbstractStoreRequestOptionsProvider::class),
            $this->createMock(InstanceService::class),
        );
    }
}
