<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Framework\HealthCheck;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\SystemCheck\Check\Status;
use Cicada\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Cicada\Core\Kernel;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Storefront\Framework\SystemCheck\SaleChannelsReadinessCheck;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[CoversClass(SaleChannelsReadinessCheck::class)]
class SaleChannelsReadinessCheckTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;
    use SalesChannelApiTestBehaviour;

    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = static::getContainer()->get(Connection::class);
    }

    public function testWhereAllChannelsAreReturningHealthy(): void
    {
        $this->createSalesChannels();
        $check = $this->createCheck();
        $result = $check->run();

        static::assertTrue($result->healthy);
        static::assertSame(Status::OK, $result->status);
    }

    public function testWhereOneChannelIsReturningHealthyWithMocks(): void
    {
        $this->createSalesChannels();
        $kernel = $this->createMock(Kernel::class);
        $kernel->expects(static::exactly(2))
            ->method('handle')
            ->willReturnOnConsecutiveCalls(
                new Response(),
                new Response(null, Response::HTTP_BAD_REQUEST)
            );

        $check = $this->createCheck($kernel);
        $result = $check->run();

        static::assertFalse($result->healthy);
        static::assertSame(Status::ERROR, $result->status);
    }

    public function testWhenAllAreReturningErrorWithMocks(): void
    {
        $this->createSalesChannels();
        $kernel = $this->createMock(Kernel::class);
        $kernel->expects(static::exactly(2))
            ->method('handle')
            ->willReturnOnConsecutiveCalls(
                new Response(null, Response::HTTP_BAD_REQUEST),
                new Response(null, Response::HTTP_BAD_REQUEST)
            );

        $check = $this->createCheck($kernel);
        $result = $check->run();

        static::assertFalse($result->healthy);
        static::assertSame(Status::FAILURE, $result->status);
    }

    public function testTrustedHostsAreTheSameBeforeAndAfterCheck(): void
    {
        // empty test state, if this assertion fails, some other test is leaking.
        static::assertEmpty(Request::getTrustedHosts());
        Request::setTrustedHosts(['foo.bar', 'test.com']);
        $trustedHostsBefore = Request::getTrustedHosts();
        $check = $this->createCheck();
        $check->run();

        static::assertSame($trustedHostsBefore, Request::getTrustedHosts());
        // reset the trusted hosts to avoid leaking state
        Request::setTrustedHosts([]);
    }

    private function createCheck((MockObject&Kernel)|null $kernel = null): SaleChannelsReadinessCheck
    {
        return new SaleChannelsReadinessCheck(
            $kernel ?? static::getContainer()->get('kernel'),
            static::getContainer()->get('router'),
            $this->connection,
            static::getContainer()->get('request_stack')
        );
    }

    private function createSalesChannels(): void
    {
        $this->connection->executeStatement('DELETE FROM `sales_channel_domain`');
        $ids = new IdsCollection();
        $this->createSalesChannel([
            'id' => $ids->create('sales-channel-1'),
            'domains' => [
                [
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'currencyId' => Defaults::CURRENCY,
                    'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                    'url' => 'https://test.to',
                ],
            ],
        ]);
        $this->createSalesChannel([
            'id' => $ids->create('sales-channel-2'),
            'domains' => [
                [
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'currencyId' => Defaults::CURRENCY,
                    'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                    'url' => 'https://foo.to',
                ],
            ],
        ]);
    }
}
