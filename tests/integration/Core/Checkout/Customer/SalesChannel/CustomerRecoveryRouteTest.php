<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Customer\SalesChannel;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Customer\Exception\CustomerNotFoundByHashException;
use Cicada\Core\Checkout\Customer\SalesChannel\CustomerRecoveryIsExpiredRoute;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Util\Random;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\Framework\Validation\Exception\ConstraintViolationException;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\Test\Integration\Traits\CustomerTestTrait;
use Cicada\Core\Test\TestDefaults;

/**
 * @internal
 */
#[Package('checkout')]
#[Group('store-api')]
class CustomerRecoveryRouteTest extends TestCase
{
    use CustomerTestTrait;
    use IntegrationTestBehaviour;

    private string $hash;

    private string $hashId;

    protected function setUp(): void
    {
        $email = Uuid::randomHex() . '@example.com';
        $customerId = $this->createCustomer($email);

        $this->hash = Random::getAlphanumericString(32);
        $this->hashId = Uuid::randomHex();

        static::getContainer()->get('customer_recovery.repository')->create([
            [
                'id' => $this->hashId,
                'customerId' => $customerId,
                'hash' => $this->hash,
            ],
        ], Context::createDefaultContext());
    }

    public function testNotDecorated(): void
    {
        $customerRecoveryRoute = static::getContainer()->get(CustomerRecoveryIsExpiredRoute::class);

        static::expectException(DecorationPatternException::class);
        $customerRecoveryRoute->getDecorated();
    }

    public function testGetCustomerRecoveryNotFound(): void
    {
        $customerRecoveryRoute = static::getContainer()->get(CustomerRecoveryIsExpiredRoute::class);

        $token = Uuid::randomHex();

        $context = static::getContainer()->get(SalesChannelContextFactory::class)->create($token, TestDefaults::SALES_CHANNEL);

        static::expectException(CustomerNotFoundByHashException::class);
        $customerRecoveryRoute->load(new RequestDataBag(['hash' => Random::getAlphanumericString(32)]), $context);
    }

    public function testGetCustomerRecoveryInvalidHash(): void
    {
        $customerRecoveryRoute = static::getContainer()->get(CustomerRecoveryIsExpiredRoute::class);

        $token = Uuid::randomHex();

        $context = static::getContainer()->get(SalesChannelContextFactory::class)->create($token, TestDefaults::SALES_CHANNEL);

        static::expectException(ConstraintViolationException::class);
        $customerRecoveryRoute->load(new RequestDataBag(['hash' => 'ThisIsAWrongHash']), $context);
    }

    public function testGetCustomerRecovery(): void
    {
        $customerRecoveryRoute = static::getContainer()->get(CustomerRecoveryIsExpiredRoute::class);

        $token = Uuid::randomHex();

        $context = static::getContainer()->get(SalesChannelContextFactory::class)->create($token, TestDefaults::SALES_CHANNEL);

        $customerRecoveryResponse = $customerRecoveryRoute->load(new RequestDataBag(['hash' => $this->hash]), $context);

        static::assertFalse($customerRecoveryResponse->isExpired());
    }

    public function testGetCustomerRecoveryExpired(): void
    {
        $customerRecoveryRoute = static::getContainer()->get(CustomerRecoveryIsExpiredRoute::class);

        $token = Uuid::randomHex();

        $context = static::getContainer()->get(SalesChannelContextFactory::class)->create($token, TestDefaults::SALES_CHANNEL);

        static::getContainer()->get(Connection::class)->update(
            'customer_recovery',
            [
                'created_at' => (new \DateTime())->sub(new \DateInterval('PT3H'))->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ],
            [
                'id' => Uuid::fromHexToBytes($this->hashId),
            ]
        );

        $customerRecoveryResponse = $customerRecoveryRoute->load(new RequestDataBag(['hash' => $this->hash]), $context);

        static::assertTrue($customerRecoveryResponse->isExpired());
    }
}
