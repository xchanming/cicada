<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Checkout\Customer\ImitateCustomerTokenGenerator;
use Cicada\Core\Checkout\Customer\SalesChannel\AccountService;
use Cicada\Core\Checkout\Customer\SalesChannel\ImitateCustomerRoute;
use Cicada\Core\Checkout\Customer\SalesChannel\LogoutRoute;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\Framework\Validation\DataValidator;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(ImitateCustomerRoute::class)]
class ImitateCustomerRouteTest extends TestCase
{
    public function testImitateCustomer(): void
    {
        $customerId = Uuid::randomHex();
        $userId = Uuid::randomHex();

        $imitateCustomerTokenGenerator = new ImitateCustomerTokenGenerator('testAppSecret');

        $token = $imitateCustomerTokenGenerator->generate(
            TestDefaults::SALES_CHANNEL,
            $customerId,
            $userId
        );

        $accountService = $this->createMock(AccountService::class);
        $accountService->method('loginById')->willReturn('newToken');

        $route = new ImitateCustomerRoute(
            $accountService,
            $imitateCustomerTokenGenerator,
            $this->createMock(LogoutRoute::class),
            $this->createMock(SalesChannelContextFactory::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(DataValidator::class),
        );

        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext->method('getSalesChannelId')->willReturn(TestDefaults::SALES_CHANNEL);

        $dataBag = new RequestDataBag([
            ImitateCustomerRoute::TOKEN => $token,
            ImitateCustomerRoute::CUSTOMER_ID => $customerId,
            ImitateCustomerRoute::USER_ID => $userId,
        ]);

        $response = $route->imitateCustomerLogin($dataBag, $salesChannelContext);

        static::assertEquals('newToken', $response->getToken());
    }
}
