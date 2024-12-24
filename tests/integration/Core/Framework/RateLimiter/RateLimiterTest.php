<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\RateLimiter;

use Cicada\Core\Checkout\Customer\SalesChannel\AccountService;
use Cicada\Core\Checkout\Customer\SalesChannel\LoginRoute;
use Cicada\Core\Content\Newsletter\NewsletterException;
use Cicada\Core\Framework\Api\Controller\AuthController as AdminAuthController;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\RateLimiter\RateLimiter;
use Cicada\Core\Framework\RateLimiter\RateLimiterFactory;
use Cicada\Core\Framework\Test\RateLimiter\DisableRateLimiterCompilerPass;
use Cicada\Core\Framework\Test\RateLimiter\RateLimiterTestTrait;
use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\System\User\Api\UserRecoveryController;
use Cicada\Core\System\User\Recovery\UserRecoveryService;
use Cicada\Core\System\User\UserEntity;
use Cicada\Core\Test\Integration\Traits\CustomerTestTrait;
use Cicada\Core\Test\Integration\Traits\OrderFixture;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use League\OAuth2\Server\AuthorizationServer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\RateLimiter\Policy\NoLimiter;
use Symfony\Component\RateLimiter\Storage\CacheStorage;

/**
 * @internal
 */
#[CoversClass(RateLimiter::class)]
#[Group('slow')]
class RateLimiterTest extends TestCase
{
    use CustomerTestTrait;
    use OrderFixture;
    use RateLimiterTestTrait;

    private Context $context;

    private IdsCollection $ids;

    private KernelBrowser $browser;

    private AbstractSalesChannelContextFactory $salesChannelContextFactory;

    public static function setUpBeforeClass(): void
    {
        DisableRateLimiterCompilerPass::disableNoLimit();
        KernelLifecycleManager::bootKernel(true, Uuid::randomHex());
    }

    public static function tearDownAfterClass(): void
    {
        DisableRateLimiterCompilerPass::enableNoLimit();
        KernelLifecycleManager::bootKernel(true, Uuid::randomHex());
    }

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        $this->ids = new IdsCollection();

        $this->browser = $this->createCustomSalesChannelBrowser([
            'id' => $this->ids->create('sales-channel'),
        ]);
        $this->assignSalesChannelContext($this->browser);

        $this->salesChannelContextFactory = static::getContainer()->get(SalesChannelContextFactory::class)->getDecorated();

        $this->clearCache();
    }

    protected function tearDown(): void
    {
        DisableRateLimiterCompilerPass::enableNoLimit();
    }

    public function testRateLimitLoginRoute(): void
    {
        $email = Uuid::randomHex() . '@example.com';
        $password = 'wrongPassword';
        $this->createCustomer($email);

        for ($i = 0; $i <= 10; ++$i) {
            $this->browser
                ->request(
                    'POST',
                    '/store-api/account/login',
                    [
                        'email' => $email,
                        'password' => $password,
                    ]
                );

            $response = $this->browser->getResponse()->getContent();
            $response = json_decode((string) $response, true, 512, \JSON_THROW_ON_ERROR);

            static::assertArrayHasKey('errors', $response);

            if ($i >= 10) {
                static::assertEquals(429, $response['errors'][0]['status']);
                static::assertEquals('CHECKOUT__CUSTOMER_AUTH_THROTTLED', $response['errors'][0]['code']);
            } else {
                static::assertEquals(401, $response['errors'][0]['status']);
                static::assertEquals('Unauthorized', $response['errors'][0]['title']);
            }
        }
    }

    public function testResetRateLimitLoginRoute(): void
    {
        $route = new LoginRoute(
            static::getContainer()->get(AccountService::class),
            static::getContainer()->get('request_stack'),
            $this->mockResetLimiter([
                RateLimiter::LOGIN_ROUTE => 1,
            ])
        );

        $this->createCustomer('loginTest@example.com');

        static::getContainer()->get('request_stack')->push(new Request([
            'email' => 'loginTest@example.com',
            'password' => 'cicada',
        ]));

        $route->login(new RequestDataBag([
            'email' => 'loginTest@example.com',
            'password' => 'cicada',
        ]), $this->salesChannelContextFactory->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL));
    }

    public function testRateLimitOauth(): void
    {
        for ($i = 0; $i <= 10; ++$i) {
            $this->browser
                ->request(
                    'POST',
                    '/api/oauth/token',
                    [
                        'grant_type' => 'password',
                        'client_id' => 'administration',
                        'username' => 'admin',
                        'password' => 'bla',
                    ]
                );

            $response = $this->browser->getResponse()->getContent();
            $response = json_decode((string) $response, true, 512, \JSON_THROW_ON_ERROR);

            static::assertArrayHasKey('errors', $response);

            if ($i >= 10) {
                static::assertEquals(429, $response['errors'][0]['status']);
                static::assertEquals('FRAMEWORK__AUTH_THROTTLED', $response['errors'][0]['code']);
            } else {
                static::assertEquals(400, $response['errors'][0]['status']);
                static::assertEquals(6, $response['errors'][0]['code']);
            }
        }
    }

    public function testResetRateLimitOauth(): void
    {
        $psrFactory = $this->createMock(PsrHttpFactory::class);
        $psrFactory->method('createRequest')->willReturn($this->createMock(ServerRequest::class));
        $psrFactory->method('createResponse')->willReturn($this->createMock(ResponseInterface::class));

        $authorizationServer = $this->createMock(AuthorizationServer::class);
        $authorizationServer->method('respondToAccessTokenRequest')->willReturn(new Response());

        $controller = new AdminAuthController(
            $authorizationServer,
            $psrFactory,
            $this->mockResetLimiter([
                RateLimiter::OAUTH => 1,
            ])
        );

        $controller->token(new Request());
    }

    public function testRateLimitContactForm(): void
    {
        for ($i = 0; $i <= 3; ++$i) {
            $this->browser
                ->request(
                    'POST',
                    '/store-api/contact-form',
                    [
                        'salutationId' => $this->getValidSalutationId(),
                        'firstName' => 'John',
                        'lastName' => 'Doe',
                        'email' => 'test@example.com',
                        'phone' => '+49123456789',
                        'subject' => 'Test contact request',
                        'comment' => 'Hello, this is my test request.',
                    ]
                );

            $response = $this->browser->getResponse()->getContent();
            $response = json_decode((string) $response, true, 512, \JSON_THROW_ON_ERROR);

            if ($i >= 3) {
                static::assertArrayHasKey('errors', $response, print_r($response, true));
                static::assertEquals(429, $response['errors'][0]['status']);
                static::assertEquals('FRAMEWORK__RATE_LIMIT_EXCEEDED', $response['errors'][0]['code']);
            } else {
                static::assertEquals(200, $this->browser->getResponse()->getStatusCode());
            }
        }
    }

    public function testRateLimitUserRecovery(): void
    {
        for ($i = 0; $i <= 3; ++$i) {
            $this->browser
                ->request(
                    'POST',
                    '/api/_action/user/user-recovery',
                    [
                        'email' => 'test@example.com',
                    ]
                );

            $response = $this->browser->getResponse()->getContent();

            if ($i >= 3) {
                static::assertJson((string) $response, (string) $response);
                $response = json_decode((string) $response, true, 512, \JSON_THROW_ON_ERROR);
                static::assertIsArray($response);
                static::assertArrayHasKey('errors', $response);
                static::assertEquals(429, $response['errors'][0]['status']);
                static::assertEquals('FRAMEWORK__RATE_LIMIT_EXCEEDED', $response['errors'][0]['code']);
            } else {
                static::assertEquals(200, $this->browser->getResponse()->getStatusCode());
            }
        }
    }

    public function testResetRateLimtitUserRecovery(): void
    {
        $recoveryService = $this->createMock(UserRecoveryService::class);
        $userEntity = new UserEntity();
        $userEntity->setUsername('admin');
        $userEntity->setEmail('test@test.de');
        $recoveryService->method('getUserByHash')->willReturn($userEntity);
        $recoveryService->method('updatePassword')->willReturn(true);

        $controller = new UserRecoveryController(
            $recoveryService,
            $this->mockResetLimiter([
                RateLimiter::OAUTH => 1,
                RateLimiter::USER_RECOVERY => 1,
            ]),
        );

        $controller->updateUserPassword(new Request(), $this->context);
    }

    public function testItThrowsExceptionOnInvalidRoute(): void
    {
        $rateLimiter = new RateLimiter();

        $this->expectException(\RuntimeException::class);
        $rateLimiter->reset('test', 'test-key');
    }

    public function testIgnoreLimitWhenDisabled(): void
    {
        $config = [
            'enabled' => false,
            'id' => 'test_limit',
            'policy' => 'time_backoff',
            'reset' => '5 minutes',
            'limits' => [
                [
                    'limit' => 3,
                    'interval' => '10 seconds',
                ],
            ],
        ];

        $factory = new RateLimiterFactory(
            $config,
            new CacheStorage(new ArrayAdapter()),
            $this->createMock(SystemConfigService::class),
            $this->createMock(LockFactory::class),
        );

        static::assertInstanceOf(NoLimiter::class, $factory->create('example'));
    }

    public function testRateLimitNewsletterForm(): void
    {
        for ($i = 0; $i <= 3; ++$i) {
            $this->browser
                ->request(
                    'POST',
                    '/store-api/newsletter/subscribe',
                    [
                        'email' => 'test@example.com',
                        'option' => 'subscribe',
                        'storefrontUrl' => 'http://localhost',
                    ]
                );

            $response = $this->browser->getResponse()->getContent();

            if ($i >= 3) {
                static::assertJson((string) $response);
                $response = json_decode((string) $response, true, 512, \JSON_THROW_ON_ERROR);

                static::assertArrayHasKey('errors', $response);
                static::assertEquals(429, $response['errors'][0]['status']);
                static::assertEquals(NewsletterException::NEWSLETTER_RECIPIENT_THROTTLED, $response['errors'][0]['code']);
            } else {
                static::assertEquals(204, $this->browser->getResponse()->getStatusCode());
            }
        }
    }
}
