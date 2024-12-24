<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Cart\Tax;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Delivery\Struct\ShippingLocation;
use Cicada\Core\Checkout\Cart\Tax\TaxDetector;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\TaxFreeConfig;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\System\Country\CountryCollection;
use Cicada\Core\System\Country\CountryEntity;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 */
class TaxDetectorTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testUseGrossPrices(): void
    {
        $context = $this->createMock(SalesChannelContext::class);
        $customerGroup = new CustomerGroupEntity();
        $customerGroup->setDisplayGross(true);
        $context->expects(static::once())->method('getCurrentCustomerGroup')->willReturn($customerGroup);

        $detector = static::getContainer()->get(TaxDetector::class);
        static::assertTrue($detector->useGross($context));
    }

    public function testDoNotUseGrossPrices(): void
    {
        $context = $this->createMock(SalesChannelContext::class);
        $customerGroup = new CustomerGroupEntity();
        $customerGroup->setDisplayGross(false);
        $context->expects(static::once())->method('getCurrentCustomerGroup')->willReturn($customerGroup);

        $detector = static::getContainer()->get(TaxDetector::class);
        static::assertFalse($detector->useGross($context));
    }

    public function testIsNetDelivery(): void
    {
        $context = $this->createMock(SalesChannelContext::class);

        $country = new CountryEntity();

        $country->setCustomerTax(new TaxFreeConfig(true, Defaults::CURRENCY, 0));
        $country->setCompanyTax(new TaxFreeConfig(true, Defaults::CURRENCY, 0));

        $context->expects(static::once())->method('getShippingLocation')->willReturn(
            ShippingLocation::createFromCountry($country)
        );

        $detector = static::getContainer()->get(TaxDetector::class);
        static::assertTrue($detector->isNetDelivery($context));
    }

    public function testIsNetDeliveryWithCompanyFreeTax(): void
    {
        $context = $this->createMock(SalesChannelContext::class);

        /** @var EntityRepository<CountryCollection> $countryRepository */
        $countryRepository = static::getContainer()->get('country.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', 'DE'));
        $criteria->setLimit(1);

        $country = $countryRepository->search($criteria, Context::createDefaultContext())->getEntities()->first();
        static::assertNotNull($country);
        $data = [
            'id' => $country->getId(),
            'customerTax' => [
                'enabled' => false,
                'currencyId' => Defaults::CURRENCY,
                'amount' => 0,
            ],
            'companyTax' => [
                'enabled' => true,
                'currencyId' => Defaults::CURRENCY,
                'amount' => 0,
            ],
            'vatIdPattern' => '(DE)?[0-9]{9}',
        ];

        $countryRepository->update([$data], Context::createDefaultContext());
        $country = $countryRepository->search($criteria, Context::createDefaultContext())->getEntities()->first();
        static::assertNotNull($country);

        $customer = new CustomerEntity();
        $customer->setCompany('ABC Company');
        $customer->setVatIds(['DE123123123']);

        $context->expects(static::once())->method('getShippingLocation')->willReturn(
            ShippingLocation::createFromCountry($country)
        );

        $context->expects(static::once())->method('getCustomer')->willReturn(
            $customer
        );

        $taxDetector = static::getContainer()->get(TaxDetector::class);

        static::assertTrue($taxDetector->isNetDelivery($context));
    }

    public function testIsNotNetDeliveryWithCompanyFreeTax(): void
    {
        $context = $this->createMock(SalesChannelContext::class);

        /** @var EntityRepository<CountryCollection> $countryRepository */
        $countryRepository = static::getContainer()->get('country.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', 'DE'));
        $criteria->setLimit(1);

        $country = $countryRepository->search($criteria, Context::createDefaultContext())->getEntities()->first();
        static::assertNotNull($country);
        $countryRepository->update([
            [
                'id' => $country->getId(),
                'taxFree' => false,
                'companyTaxFree' => false,
                'vatIdPattern' => '(DE)?[0-9]{9}',
                'checkVatIdPattern' => false,
            ],
        ], Context::createDefaultContext());
        $country = $countryRepository->search($criteria, Context::createDefaultContext())->getEntities()->first();
        static::assertNotNull($country);

        $context->expects(static::once())->method('getShippingLocation')->willReturn(
            ShippingLocation::createFromCountry($country)
        );

        $detector = static::getContainer()->get(TaxDetector::class);
        static::assertFalse($detector->isNetDelivery($context));
    }

    public function testIsNotNetDeliveryWithCompanyFreeTaxAndWrongVatIdPattern(): void
    {
        $context = $this->createMock(SalesChannelContext::class);

        /** @var EntityRepository<CountryCollection> $countryRepository */
        $countryRepository = static::getContainer()->get('country.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', 'DE'));
        $criteria->setLimit(1);

        $deCountry = $countryRepository->search($criteria, Context::createDefaultContext())->getEntities()->first();
        static::assertNotNull($deCountry);
        $data = [
            'id' => $deCountry->getId(),
            'customerTax' => [
                'enabled' => false,
                'currencyId' => Defaults::CURRENCY,
                'amount' => 0,
            ],
            'companyTax' => [
                'enabled' => true,
                'currencyId' => Defaults::CURRENCY,
                'amount' => 0,
            ],
            'vatIdPattern' => '(DE)?[0-9]{9}',
            'checkVatIdPattern' => true,
        ];

        $countryRepository->update([$data], Context::createDefaultContext());
        $deCountry = $countryRepository->search($criteria, Context::createDefaultContext())->getEntities()->first();
        static::assertNotNull($deCountry);

        $customer = new CustomerEntity();
        $customer->setCompany('ABC Company');
        $customer->setVatIds(['VN123123']);

        $context->expects(static::once())->method('getShippingLocation')->willReturn(
            ShippingLocation::createFromCountry($deCountry)
        );

        $context->expects(static::once())->method('getCustomer')->willReturn(
            $customer
        );

        $detector = static::getContainer()->get(TaxDetector::class);
        static::assertFalse($detector->isNetDelivery($context));
    }

    public function testIsNotNetDeliveryWithCompanyFreeTaxAndNullVatId(): void
    {
        $context = $this->createMock(SalesChannelContext::class);

        $country = (new CountryEntity())->assign([
            'customerTax' => new TaxFreeConfig(false),
            'companyTax' => new TaxFreeConfig(true),
            'vatIdPattern' => '...',
            'checkVatIdPattern' => true,
        ]);

        $customer = (new CustomerEntity())->assign([
            'company' => 'ABC Compay',
            'vatIds' => [null],
        ]);

        $context->expects(static::once())->method('getShippingLocation')->willReturn(
            ShippingLocation::createFromCountry($country)
        );

        $context->expects(static::once())->method('getCustomer')->willReturn(
            $customer
        );

        $detector = static::getContainer()->get(TaxDetector::class);
        static::assertFalse($detector->isNetDelivery($context));
    }

    public function testIsNetDeliveryWithCompanyFreeTaxAndWrongVatIdButVatIdCheckDisabled(): void
    {
        $context = $this->createMock(SalesChannelContext::class);

        /** @var EntityRepository<CountryCollection> $countryRepository */
        $countryRepository = static::getContainer()->get('country.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', 'DE'));
        $criteria->setLimit(1);

        $deCountry = $countryRepository->search($criteria, Context::createDefaultContext())->getEntities()->first();
        static::assertNotNull($deCountry);
        $data = [
            'id' => $deCountry->getId(),
            'customerTax' => [
                'enabled' => false,
                'currencyId' => Defaults::CURRENCY,
                'amount' => 0,
            ],
            'companyTax' => [
                'enabled' => true,
                'currencyId' => Defaults::CURRENCY,
                'amount' => 0,
            ],
            'vatIdPattern' => '(DE)?[0-9]{9}',
            'checkVatIdPattern' => false,
        ];

        $countryRepository->update([$data], Context::createDefaultContext());
        $deCountry = $countryRepository->search($criteria, Context::createDefaultContext())->getEntities()->first();
        static::assertNotNull($deCountry);

        $customer = new CustomerEntity();
        $customer->setCompany('ABC Company');
        $customer->setVatIds(['VN123123']);

        $context->expects(static::once())->method('getShippingLocation')->willReturn(
            ShippingLocation::createFromCountry($deCountry)
        );

        $context->expects(static::once())->method('getCustomer')->willReturn(
            $customer
        );

        $detector = static::getContainer()->get(TaxDetector::class);
        static::assertTrue($detector->isNetDelivery($context));
    }

    public function testIsNotNetDelivery(): void
    {
        $context = $this->createMock(SalesChannelContext::class);

        /** @var EntityRepository<CountryCollection> $countryRepository */
        $countryRepository = static::getContainer()->get('country.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', 'DE'));
        $criteria->setLimit(1);

        $country = $countryRepository->search($criteria, Context::createDefaultContext())->getEntities()->first();
        static::assertNotNull($country);
        $countryRepository->update([
            [
                'id' => $country->getId(),
                'taxFree' => false,
                'companyTaxFree' => false,
                'vatIdPattern' => '(DE)?[0-9]{9}',
                'checkVatIdPattern' => false,
            ],
        ], Context::createDefaultContext());
        $country = $countryRepository->search($criteria, Context::createDefaultContext())->getEntities()->first();
        static::assertNotNull($country);

        $context->expects(static::once())->method('getShippingLocation')->willReturn(
            ShippingLocation::createFromCountry($country)
        );

        $detector = static::getContainer()->get(TaxDetector::class);
        static::assertFalse($detector->isNetDelivery($context));
    }
}
