<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart;

use Cicada\Core\Checkout\Cart\AbstractCartPersister;
use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartBehavior;
use Cicada\Core\Checkout\Cart\CartException;
use Cicada\Core\Checkout\Cart\CartFactory;
use Cicada\Core\Checkout\Cart\CartRuleLoader;
use Cicada\Core\Checkout\Cart\Processor;
use Cicada\Core\Checkout\Cart\Rule\AlwaysValidRule;
use Cicada\Core\Checkout\Cart\RuleLoader;
use Cicada\Core\Checkout\Cart\Tax\TaxDetector;
use Cicada\Core\Content\Rule\RuleCollection;
use Cicada\Core\Content\Rule\RuleEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\RuleAreas;
use Cicada\Core\Framework\DataAbstractionLayer\TaxFreeConfig;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Country\CountryEntity;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Generator;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * @internal
 */
#[CoversClass(CartRuleLoader::class)]
class CartRuleLoaderTest extends TestCase
{
    public function testLoadByTokenCreatesNewCart(): void
    {
        $newCart = new Cart('test');
        $factory = $this->createMock(CartFactory::class);
        $factory
            ->expects(static::once())
            ->method('createNew')
            ->with('test')
            ->willReturn($newCart);

        $persister = $this->createMock(AbstractCartPersister::class);
        $persister
            ->expects(static::once())
            ->method('load')
            ->with('test')
            ->willThrowException(CartException::tokenNotFound('test'));

        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext
            ->expects(static::once())
            ->method('getToken')
            ->willReturn('test');
        $salesChannelContext
            ->expects(static::exactly(2))
            ->method('getContext')
            ->willReturn(Context::createDefaultContext());

        $calculatedCart = new Cart('calculated');
        $processor = $this->createMock(Processor::class);
        $processor
            ->expects(static::exactly(3))
            ->method('process')
            ->with(static::isInstanceOf(Cart::class), $salesChannelContext, static::isInstanceOf(CartBehavior::class))
            ->willReturn($calculatedCart);

        $ruleLoader = $this->createMock(RuleLoader::class);
        $ruleLoader
            ->expects(static::once())
            ->method('load')
            ->with($salesChannelContext->getContext())
            ->willReturn(new RuleCollection());

        $cartRuleLoader = new CartRuleLoader(
            $persister,
            $processor,
            new NullLogger(),
            $this->createMock(CacheInterface::class),
            $ruleLoader,
            $this->createMock(TaxDetector::class),
            $this->createMock(Connection::class),
            $factory,
        );

        static::assertSame($calculatedCart, $cartRuleLoader->loadByToken($salesChannelContext, $salesChannelContext->getToken())->getCart());
    }

    public function testProcessorHasCorrectRuleIds(): void
    {
        $country = new CountryEntity();
        $country->setId(Generator::COUNTRY);
        $country->setCustomerTax(new TaxFreeConfig());

        $salesChannelContext = Generator::generateSalesChannelContext(country: $country);

        $rule1 = new RuleEntity();
        $rule1->setId(Uuid::randomHex());
        $rule1->setName($rule1->getId());
        $rule1->setPriority(1);
        $rule1->setAreas([RuleAreas::PRODUCT_AREA, RuleAreas::PAYMENT_AREA]);
        $rule1->setPayload(new AlwaysValidRule());

        $rule2 = new RuleEntity();
        $rule2->setId(Uuid::randomHex());
        $rule2->setName($rule2->getId());
        $rule2->setPriority(2);
        $rule2->setAreas([RuleAreas::PRODUCT_AREA]);
        $rule2->setPayload(new AlwaysValidRule());

        $rule3 = new RuleEntity();
        $rule3->setId(Uuid::randomHex());
        $rule3->setName($rule3->getId());
        $rule3->setPriority(3);
        $rule3->setAreas([RuleAreas::PAYMENT_AREA, RuleAreas::PAYMENT_AREA]);
        $rule3->setPayload(new AlwaysValidRule());

        $ruleIds = [$rule1->getId(), $rule2->getId(), $rule3->getId()];
        $areaRuleIds = [
            RuleAreas::PRODUCT_AREA => [$rule1->getId(), $rule2->getId()],
            RuleAreas::PAYMENT_AREA => [$rule1->getId(), $rule3->getId()],
        ];

        $ruleLoader = $this->createMock(RuleLoader::class);
        $ruleLoader
            ->expects(static::once())
            ->method('load')
            ->with($salesChannelContext->getContext())
            ->willReturn(new RuleCollection([$rule1, $rule2, $rule3]))
        ;

        $processor = $this->createMock(Processor::class);
        $processor
            ->expects(static::exactly(3))
            ->method('process')
            ->with(static::isInstanceOf(Cart::class), static::callback(function (SalesChannelContext $context) use ($ruleIds, $areaRuleIds) {
                static::assertEquals($ruleIds, $context->getRuleIds());
                static::assertEquals($areaRuleIds, $context->getAreaRuleIds());

                return true;
            }), static::isInstanceOf(CartBehavior::class))
        ;

        $cartRuleLoader = new CartRuleLoader(
            $this->createMock(AbstractCartPersister::class),
            $processor,
            new NullLogger(),
            $this->createMock(CacheInterface::class),
            $ruleLoader,
            $this->createMock(TaxDetector::class),
            $this->createMock(Connection::class),
            $this->createMock(CartFactory::class),
        );

        $cart = new Cart('test');
        $cart->setRuleIds($ruleIds);
        $cartRuleLoader->loadByCart($salesChannelContext, $cart, new CartBehavior());
    }
}
