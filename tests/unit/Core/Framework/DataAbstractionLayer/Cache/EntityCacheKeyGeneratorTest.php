<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DataAbstractionLayer\Cache;

use Cicada\Core\Checkout\Cart\Delivery\Struct\ShippingLocation;
use Cicada\Core\Checkout\Cart\Price\Struct\CartPrice;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Payment\PaymentMethodEntity;
use Cicada\Core\Checkout\Shipping\ShippingMethodEntity;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Api\Context\SalesChannelApiSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Cache\EntityCacheKeyGenerator;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\PrefixFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\SuffixFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cicada\Core\System\Country\CountryEntity;
use Cicada\Core\System\Currency\CurrencyEntity;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use Cicada\Core\System\Tax\TaxCollection;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(EntityCacheKeyGenerator::class)]
class EntityCacheKeyGeneratorTest extends TestCase
{
    public function testBuildCmsTag(): void
    {
        static::assertSame('cms-page-foo', EntityCacheKeyGenerator::buildCmsTag('foo'));
    }

    public function testBuildProductTag(): void
    {
        static::assertSame('product-foo', EntityCacheKeyGenerator::buildProductTag('foo'));
    }

    public function testBuildStreamTag(): void
    {
        static::assertSame('product-stream-foo', EntityCacheKeyGenerator::buildStreamTag('foo'));
    }

    #[DataProvider('criteriaHashProvider')]
    public function testCriteriaHash(Criteria $criteria, string $hash): void
    {
        $generator = new EntityCacheKeyGenerator();

        static::assertSame($hash, $generator->getCriteriaHash($criteria));
    }

    public static function criteriaHashProvider(): \Generator
    {
        yield 'empty' => [
            new Criteria(),
            '6f1868158423d60724dd3071c2d6f525',
        ];

        yield 'prefix-filter' => [
            (new Criteria())->addFilter(new PrefixFilter('foo', 'bar')),
            '559e5a06c04697c7d064e81531956363',
        ];

        // this has a different hash because of a different filter type used
        yield 'suffix-filter' => [
            (new Criteria())->addFilter(new SuffixFilter('foo', 'bar')),
            '2b1d8b3e3b6d1ce6521574844ea1d392',
        ];

        yield 'filter+sort' => [
            (new Criteria())->addFilter(new PrefixFilter('foo', 'bar'))->addSorting(new FieldSorting('foo')),
            'bce330a74230b1c2523251868d747aa3',
        ];

        yield 'filter+sort+sort-desc' => [
            (new Criteria())->addFilter(new PrefixFilter('foo', 'bar'))->addSorting(new FieldSorting('foo', FieldSorting::DESCENDING)),
            '5f3843ddae7caa08863d66b58f5c3236',
        ];

        yield 'filter+agg' => [
            (new Criteria())->addFilter(new PrefixFilter('foo', 'bar'))->addAggregation(new TermsAggregation('foo', 'foo')),
            'eeb2f29872d1f81a7ed7511069d91fa0',
        ];
    }

    #[DataProvider('contextHashProvider')]
    public function testContextHash(SalesChannelContext $compared): void
    {
        $generator = new EntityCacheKeyGenerator();

        static::assertNotEquals(
            $generator->getSalesChannelContextHash(new DummyContext(), ['test']),
            $generator->getSalesChannelContextHash($compared, ['test'])
        );
    }

    public static function contextHashProvider(): \Generator
    {
        yield 'tax state considered for hash' => [
            (new DummyContext())->setTaxStateFluent(CartPrice::TAX_STATE_NET),
        ];

        yield 'currency id considered for hash' => [
            (new DummyContext())->setCurrencyId('foo'),
        ];

        yield 'sales channel id considered for hash' => [
            (new DummyContext())->setSalesChannelId('foo'),
        ];

        yield 'language id chain considered for hash' => [
            (new DummyContext())->setLanguageChain(['foo']),
        ];

        yield 'version considered for hash' => [
            (new DummyContext())->setVersionId('foo'),
        ];

        yield 'rounding mode considered for hash' => [
            (new DummyContext())->setItemRoundingFluent(new CashRoundingConfig(2, 0.5, true)),
        ];

        yield 'rules considered for hash' => [
            (new DummyContext())->setAreaRuleIdsFluent(['test' => ['foo']]),
        ];
    }
}

/**
 * @internal
 */
class DummyContext extends SalesChannelContext
{
    public function __construct()
    {
        $source = new SalesChannelApiSource(TestDefaults::SALES_CHANNEL);

        parent::__construct(
            new Context($source, [], Defaults::CURRENCY, [Defaults::LANGUAGE_SYSTEM], Defaults::LIVE_VERSION, 1.0, true, CartPrice::TAX_STATE_GROSS),
            'token',
            'domain-id',
            (new SalesChannelEntity())->assign(['id' => TestDefaults::SALES_CHANNEL]),
            (new CurrencyEntity())->assign(['id' => Defaults::CURRENCY]),
            new CustomerGroupEntity(),
            new TaxCollection(),
            new PaymentMethodEntity(),
            new ShippingMethodEntity(),
            new ShippingLocation(new CountryEntity(), null, null),
            new CustomerEntity(),
            new CashRoundingConfig(2, 0.01, true),
            new CashRoundingConfig(2, 0.01, true),
            []
        );
    }

    public function setSalesChannelId(string $salesChannelId): self
    {
        $this->salesChannel = (new SalesChannelEntity())->assign(['id' => $salesChannelId]);

        return $this;
    }

    public function setCurrencyId(string $currencyId): self
    {
        $this->currency = (new CurrencyEntity())->assign(['id' => $currencyId]);

        return $this;
    }

    /**
     * @param list<string> $chain
     */
    public function setLanguageChain(array $chain): self
    {
        $this->context->assign(['languageIdChain' => $chain]);

        return $this;
    }

    public function setVersionId(string $versionId): self
    {
        $this->context->assign(['versionId' => $versionId]);

        return $this;
    }

    public function setTaxStateFluent(string $taxState): self
    {
        $this->context->setTaxState($taxState);

        return $this;
    }

    /**
     * @param array<string, string[]> $rules
     */
    public function setAreaRuleIdsFluent(array $rules): self
    {
        $this->setAreaRuleIds($rules);

        return $this;
    }

    public function setItemRoundingFluent(CashRoundingConfig $rounding): self
    {
        $this->itemRounding = $rounding;

        return $this;
    }
}
