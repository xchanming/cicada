<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\System\SalesChannel\Context;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartPersister;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\LineItem\LineItemCollection;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Cicada\Core\Framework\Util\Random;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use Cicada\Core\Test\Generator;
use Cicada\Core\Test\TestDefaults;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 */
#[Package('framework')]
class SalesChannelContextPersisterTest extends TestCase
{
    use IntegrationTestBehaviour;
    use SalesChannelApiTestBehaviour;

    private Connection $connection;

    private SalesChannelContextPersister $contextPersister;

    protected function setUp(): void
    {
        $this->connection = static::getContainer()->get(Connection::class);
        $eventDispatcher = new EventDispatcher();
        $this->contextPersister = new SalesChannelContextPersister($this->connection, $eventDispatcher, static::getContainer()->get(CartPersister::class));
    }

    public function testLoad(): void
    {
        $token = Random::getAlphanumericString(32);
        $expected = [
            'key' => 'value',
            'token' => $token,
            'expired' => false,
        ];

        $this->connection->insert('sales_channel_api_context', [
            'token' => $token,
            'payload' => json_encode($expected),
            'sales_channel_id' => Uuid::fromHexToBytes(TestDefaults::SALES_CHANNEL),
        ]);

        static::assertSame($expected, $this->contextPersister->load($token, TestDefaults::SALES_CHANNEL));
    }

    public function testLoadByCustomerId(): void
    {
        $token = Uuid::randomHex();
        $customerId = $this->createCustomer();
        $this->contextPersister->save($token, [], TestDefaults::SALES_CHANNEL, $customerId);

        static::assertNotEmpty($result = $this->contextPersister->load($token, TestDefaults::SALES_CHANNEL, $customerId));
        static::assertEquals($token, $result['token']);
    }

    public function testLoadNotExisting(): void
    {
        $token = Random::getAlphanumericString(32);

        static::assertSame([], $this->contextPersister->load($token, TestDefaults::SALES_CHANNEL));
    }

    public function testLoadCustomerNotExisting(): void
    {
        $customerId = Uuid::randomHex();
        $token = Random::getAlphanumericString(32);

        static::assertSame([], $this->contextPersister->load($token, TestDefaults::SALES_CHANNEL, $customerId));
    }

    public function testSaveWithoutExistingContext(): void
    {
        $token = Random::getAlphanumericString(32);
        $expected = [
            'key' => 'value',
            'expired' => false,
            'token' => $token,
        ];

        $this->contextPersister->save($token, $expected, TestDefaults::SALES_CHANNEL);

        static::assertSame($expected, $this->contextPersister->load($token, TestDefaults::SALES_CHANNEL));
    }

    public function testSaveNewCustomerContextWithoutExistingCustomer(): void
    {
        $token = Random::getAlphanumericString(32);
        $expected = [
            'key' => 'value',
            'token' => $token,
            'expired' => false,
        ];

        $customerId = $this->createCustomer();

        $this->contextPersister->save($token, $expected, TestDefaults::SALES_CHANNEL, $customerId);

        $result = $this->contextPersister->load($token, TestDefaults::SALES_CHANNEL, $customerId);

        static::assertNotEmpty($result);

        static::assertEquals($expected, $result);
        static::assertEquals($token, $result['token']);
    }

    public function testSaveMergesWithExisting(): void
    {
        $token = Random::getAlphanumericString(32);

        $this->connection->insert('sales_channel_api_context', [
            'token' => $token,
            'payload' => json_encode([
                'first' => 'test',
                'second' => 'second test',
            ]),
            'sales_channel_id' => Uuid::fromHexToBytes(TestDefaults::SALES_CHANNEL),
        ]);

        $this->contextPersister->save(
            $token,
            [
                'second' => 'overwritten',
                'third' => 'third test',
            ],
            TestDefaults::SALES_CHANNEL
        );

        $expected = [
            'expired' => false,
            'first' => 'test',
            'second' => 'overwritten',
            'third' => 'third test',
            'token' => $token,
        ];

        $actual = $this->contextPersister->load($token, TestDefaults::SALES_CHANNEL);
        ksort($actual);

        static::assertSame($expected, $actual);
    }

    public function testSaveCustomerContextMergesWithExisting(): void
    {
        $token = Random::getAlphanumericString(32);

        $customerId = $this->createCustomer();

        $this->connection->insert('sales_channel_api_context', [
            'token' => $token,
            'payload' => json_encode([
                'first' => 'test',
                'second' => 'second test',
            ]),
            'sales_channel_id' => Uuid::fromHexToBytes(TestDefaults::SALES_CHANNEL),
            'customer_id' => Uuid::fromHexToBytes($customerId),
        ]);

        $this->contextPersister->save($token, [
            'second' => 'overwritten',
            'third' => 'third test',
        ], TestDefaults::SALES_CHANNEL, $customerId);

        $expected = [
            'expired' => false,
            'first' => 'test',
            'second' => 'overwritten',
            'third' => 'third test',
            'token' => $token,
        ];
        $actual = $this->contextPersister->load($token, TestDefaults::SALES_CHANNEL, $customerId);
        ksort($actual);

        static::assertSame($expected, $actual);
    }

    public function testLoadSameCustomerOnDifferentSalesChannel(): void
    {
        $customerId = $this->createCustomer();

        $salesChannel1 = $this->createSalesChannel([
            'id' => Uuid::randomHex(),
            'domains' => [
                [
                    'url' => 'http://test.en',
                    'currencyId' => Defaults::CURRENCY,
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'snippetSetId' => $this->getRandomId('snippet_set'),
                ],
            ],
        ]);

        $salesChannel2 = $this->createSalesChannel([
            'id' => Uuid::randomHex(),
            'domains' => [
                [
                    'url' => 'http://test.de',
                    'currencyId' => Defaults::CURRENCY,
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'snippetSetId' => $this->getRandomId('snippet_set'),
                ],
            ],
        ]);

        $token1 = Uuid::randomHex();
        $token2 = Uuid::randomHex();

        $this->contextPersister->save($token1, [], $salesChannel1['id'], $customerId);
        $this->contextPersister->save($token2, [], $salesChannel2['id'], $customerId);

        // Without saved context sales channel
        static::assertEmpty($this->contextPersister->load($token1, TestDefaults::SALES_CHANNEL, $customerId));
        static::assertEmpty($this->contextPersister->load($token2, TestDefaults::SALES_CHANNEL, $customerId));

        $contextPayload1 = $this->contextPersister->load(Uuid::randomHex(), $salesChannel1['id'], $customerId);
        static::assertNotEmpty($contextPayload1);
        static::assertEquals($token1, $contextPayload1['token']);

        $contextPayload2 = $this->contextPersister->load(Uuid::randomHex(), $salesChannel2['id'], $customerId);

        static::assertNotEmpty($contextPayload2);
        static::assertEquals($token2, $contextPayload2['token']);
    }

    public function testReplaceWithoutExistingContext(): void
    {
        $token = Random::getAlphanumericString(32);

        $context = Generator::generateSalesChannelContext(overrides: ['customer' => null]);
        $newToken = $this->contextPersister->replace($token, $context);

        static::assertTrue($this->contextExists($newToken));
        static::assertFalse($this->contextExists($token));
    }

    public function testSaveReplaceWithExistingContext(): void
    {
        $token = Random::getAlphanumericString(32);

        $this->connection->insert('sales_channel_api_context', [
            'token' => $token,
            'payload' => json_encode([
                'first' => 'test',
                'second' => 'second test',
            ]),
            'sales_channel_id' => Uuid::fromHexToBytes(TestDefaults::SALES_CHANNEL),
        ]);

        $context = $this->createMock(SalesChannelContext::class);
        $salesChannel = (new SalesChannelEntity())->assign(['id' => TestDefaults::SALES_CHANNEL]);
        $context->method('getSalesChannel')->willReturn($salesChannel);

        $newToken = $this->contextPersister->replace($token, $context);

        static::assertTrue($this->contextExists($newToken));
        static::assertFalse($this->contextExists($token));
    }

    public function testReplaceUpdatesCartTokenToo(): void
    {
        $token = Random::getAlphanumericString(32);

        $context = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create($token, TestDefaults::SALES_CHANNEL);

        $cart = new Cart($token);
        $cart->addLineItems(new LineItemCollection([new LineItem('test', 'test', Uuid::randomHex(), 10)]));
        static::getContainer()->get(CartPersister::class)->save($cart, $context);

        static::assertTrue($this->cartExists($token));

        $newToken = $this->contextPersister->replace($token, $context);

        static::assertTrue($this->cartExists($newToken));
        static::assertFalse($this->cartExists($token));
    }

    public function testCustomerIdColumnIsBeingUsed(): void
    {
        $customerId = $this->createCustomer();
        $token = Random::getAlphanumericString(32);

        $this->connection->insert('sales_channel_api_context', [
            'token' => $token,
            'payload' => json_encode(['foo' => 'bar']),
            'customer_id' => Uuid::fromHexToBytes($customerId),
            'sales_channel_id' => Uuid::fromHexToBytes(TestDefaults::SALES_CHANNEL),
        ]);

        $this->contextPersister->revokeAllCustomerTokens($customerId);

        static::assertNull($this->connection->fetchOne('SELECT customer_id FROM sales_channel_api_context'));
    }

    public static function tokenExpiringDataProvider(): \Generator
    {
        yield [0, 'P2D', false];
        yield [1, 'P2D', false];
        yield [3, 'P2D', true];
        yield [0, 'P1D', false];
        yield [2, 'P1D', true];
    }

    #[DataProvider('tokenExpiringDataProvider')]
    public function testTokenExpiring(int $tokenAgeInDays, string $lifeTimeInterval, bool $expectedExpired): void
    {
        $connection = static::getContainer()->get(Connection::class);
        $persister = new SalesChannelContextPersister(
            $connection,
            $this->createMock(EventDispatcher::class),
            static::getContainer()->get(CartPersister::class),
            $lifeTimeInterval
        );
        $token = Uuid::randomHex();

        $customerId = $this->createCustomer();
        $persister->save($token, [], TestDefaults::SALES_CHANNEL, $customerId);

        if ($tokenAgeInDays !== 0) {
            // change age
            $connection->executeStatement(
                'UPDATE sales_channel_api_context
                SET updated_at = DATE_ADD(updated_at, INTERVAL :intervalInDays DAY)',
                ['intervalInDays' => -$tokenAgeInDays]
            );
        }

        $result = $persister->load($token, TestDefaults::SALES_CHANNEL, $customerId);

        static::assertSame($result['expired'], $expectedExpired);
        static::assertArrayNotHasKey(SalesChannelContextService::CUSTOMER_ID, $result);
    }

    #[DataProvider('testRevokeTokensDataProvider')]
    public function testRevokeTokens(string $token, ?string $preserveToken): void
    {
        $customerId = $this->createCustomer();
        $this->contextPersister->save($token, [], TestDefaults::SALES_CHANNEL, $customerId);

        // check token is valid here
        static::assertNotEmpty($result = $this->contextPersister->load($token, TestDefaults::SALES_CHANNEL, $customerId));
        static::assertEquals($token, $result['token']);

        if ($preserveToken) {
            $this->contextPersister->revokeAllCustomerTokens($customerId, $preserveToken);
        } else {
            $this->contextPersister->revokeAllCustomerTokens($customerId);
        }

        if ($preserveToken) {
            static::assertNotNull($this->connection->fetchOne('SELECT customer_id FROM sales_channel_api_context'));
        } else {
            static::assertNull($this->connection->fetchOne('SELECT customer_id FROM sales_channel_api_context'));
        }
    }

    public static function testRevokeTokensDataProvider(): \Generator
    {
        yield [Uuid::randomHex(), ''];
        yield [$token = Uuid::randomHex(), $token];
    }

    private function cartExists(string $token): bool
    {
        $result = (int) $this->connection->executeQuery(
            'SELECT COUNT(*) FROM cart WHERE `token` = :token',
            [
                'token' => $token,
            ]
        )->fetchOne();

        return $result > 0;
    }

    private function contextExists(string $token): bool
    {
        $result = (int) $this->connection->executeQuery(
            'SELECT COUNT(*) FROM sales_channel_api_context WHERE `token` = :token',
            [
                'token' => $token,
            ]
        )->fetchOne();

        return $result > 0;
    }
}
