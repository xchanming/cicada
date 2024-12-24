<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Address;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Address\AddressValidator;
use Cicada\Core\Checkout\Cart\Address\Error\ShippingAddressBlockedError;
use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\Delivery\Struct\ShippingLocation;
use Cicada\Core\Checkout\Cart\Error\ErrorCollection;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Country\CountryEntity;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(AddressValidator::class)]
class AddressValidator2Test extends TestCase
{
    #[DataProvider('validationProvider')]
    public function testValidation(bool $active, bool $shippingAvailable, bool $assigned): void
    {
        $id = Uuid::randomHex();

        // should not assigned to the sales channel?
        $result = $this->getSearchResultStub($assigned, $id);

        // fake database query
        $repository = $this->getRepositoryMock($result);

        $validator = new AddressValidator($repository);

        // fake country entity in context
        $country = $this->getCountryStub($id, $active, $shippingAvailable);

        $location = new ShippingLocation($country, null, null);

        $context = $this->getContextMock($location);

        $cart = new Cart('test');
        $errors = new ErrorCollection();

        $validator->validate($cart, $errors, $context);

        $shouldBeValid = $assigned && $shippingAvailable && $active;
        if ($shouldBeValid) {
            static::assertCount(0, $errors);

            return;
        }

        static::assertCount(1, $errors);
        $error = $errors->first();
        static::assertInstanceOf(ShippingAddressBlockedError::class, $error);
    }

    public static function validationProvider(): \Generator
    {
        yield 'test not active' => [false, true, true];
        yield 'test not shipping available' => [true, false, true];
        yield 'test not assigned for sales channel' => [true, true, false];
        yield 'test not active and not shipping available' => [false, false, true];
        yield 'test not active, not shipping available, not assigned' => [false, false, false];
        yield 'test is valid' => [true, true, true];
    }

    private function getSearchResultStub(?bool $assigned = true, ?string $id = null): IdSearchResult
    {
        if ($assigned) {
            return new IdSearchResult(1, [['primaryKey' => $id ?? Uuid::randomHex(), 'data' => []]], new Criteria(), Context::createDefaultContext());
        }

        return new IdSearchResult(0, [], new Criteria(), Context::createDefaultContext());
    }

    private function getRepositoryMock(?IdSearchResult $result): EntityRepository&MockObject
    {
        $repository = $this->createMock(EntityRepository::class);

        $repository->method('searchIds')
            ->willReturn($result);

        return $repository;
    }

    private function getCountryStub(?string $id = null, ?bool $active = true, ?bool $shippingAvailable = true): CountryEntity
    {
        $country = new CountryEntity();

        $country->setId($id ?? Uuid::randomHex());
        $country->setActive((bool) $active);
        $country->addTranslated('name', 'test');
        $country->setShippingAvailable((bool) $shippingAvailable);

        return $country;
    }

    private function getContextMock(?ShippingLocation $shippingLocation = null): MockObject&SalesChannelContext
    {
        $context = $this->createMock(SalesChannelContext::class);

        $context->method('getShippingLocation')
            ->willReturn($shippingLocation);
        $context->method('getSalesChannelId')
            ->willReturn(Uuid::randomHex());

        return $context;
    }
}
