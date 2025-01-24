<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Customer\Rule;

use Cicada\Core\Checkout\CheckoutRuleScope;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\Rule\IsCompanyRule;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(IsCompanyRule::class)]
class IsCompanyCustomerRuleTest extends TestCase
{
    public function testThatNonExistingCustomerDoesNotMatch(): void
    {
        $this->matchRuleWithCustomer(new IsCompanyRule(true), null, false);
        $this->matchRuleWithCustomer(new IsCompanyRule(false), null, false);
    }

    public function testThatCustomerWithCompanyMatchesCorrectly(): void
    {
        $customer = new CustomerEntity();
        $customer->setCompany('cicada AG');

        $this->matchRuleWithCustomer(new IsCompanyRule(true), $customer, true);
        $this->matchRuleWithCustomer(new IsCompanyRule(false), $customer, false);
    }

    public function testThatCustomerWithoutCompanyMatchesCorrectly(): void
    {
        $customer = new CustomerEntity();

        $this->matchRuleWithCustomer(new IsCompanyRule(true), $customer, false);
        $this->matchRuleWithCustomer(new IsCompanyRule(false), $customer, true);
    }

    public function testThatCustomerWithEmptyStringCompanyMatchesCorrectly(): void
    {
        $customer = new CustomerEntity();
        $customer->setCompany('');

        $this->matchRuleWithCustomer(new IsCompanyRule(true), $customer, false);
        $this->matchRuleWithCustomer(new IsCompanyRule(false), $customer, true);
    }

    private function matchRuleWithCustomer(IsCompanyRule $isCompanyRule, ?CustomerEntity $customer, bool $isMatchExpected): void
    {
        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext->method('getCustomer')
            ->willReturn($customer);

        $scope = new CheckoutRuleScope($salesChannelContext);

        static::assertSame($isCompanyRule->match($scope), $isMatchExpected);
    }
}
