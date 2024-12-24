<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Administration\RateLimiter;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Test\RateLimiter\DisableRateLimiterCompilerPass;
use Cicada\Core\Framework\Test\TestCaseBase\AdminApiTestBehaviour;
use Cicada\Core\Test\Integration\Traits\CustomerTestTrait;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Group('slow')]
class RateLimiterTest extends TestCase
{
    use AdminApiTestBehaviour;
    use CustomerTestTrait;

    private Context $context;

    private EntityRepository $appRepository;

    public static function setUpBeforeClass(): void
    {
        DisableRateLimiterCompilerPass::disableNoLimit();
    }

    public static function tearDownAfterClass(): void
    {
        DisableRateLimiterCompilerPass::enableNoLimit();
    }

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        $this->appRepository = static::getContainer()->get('app.repository');
    }

    protected function tearDown(): void
    {
        DisableRateLimiterCompilerPass::enableNoLimit();
    }

    public function testRateLimitNotificationRoute(): void
    {
        $ids = new IdsCollection();
        $integrationId = $ids->create('integration');
        $client = $this->getBrowserAuthenticatedWithIntegration($integrationId);

        $this->createApp($integrationId);
        $url = '/api/notification';
        $data = [
            'status' => 'success',
            'message' => 'This is a notification',
        ];

        for ($i = 0; $i <= 10; ++$i) {
            $client->request('POST', $url, [], [], [], (string) json_encode($data));

            $response = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

            if ($i >= 10) {
                static::assertArrayHasKey('errors', $response);
                static::assertEquals(429, $response['errors'][0]['status']);
                static::assertEquals('FRAMEWORK__NOTIFICATION_THROTTLED', $response['errors'][0]['code']);
            } else {
                static::assertEquals(200, $client->getResponse()->getStatusCode());
            }
        }
    }

    private function createApp(string $integrationId): void
    {
        $payload = [
            'name' => 'TestNotification',
            'active' => true,
            'path' => __DIR__ . '/Manifest/_fixtures/test',
            'version' => '0.0.1',
            'label' => 'Test notification',
            'accessToken' => 'test',
            'appSecret' => 's3cr3t',
            'mainModule' => [
                'source' => 'http://main-module-1',
            ],
            'integrationId' => $integrationId,
            'aclRole' => [
                'name' => 'TestNotification',
                'privileges' => [
                    'notification:create',
                ],
            ],
        ];

        $this->appRepository->create([$payload], $this->context);
    }
}
