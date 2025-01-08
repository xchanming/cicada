<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\SalesChannel\Context;

use Cicada\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupCollection;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupDefinition;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Cicada\Core\Checkout\Payment\PaymentMethodCollection;
use Cicada\Core\Checkout\Payment\PaymentMethodDefinition;
use Cicada\Core\Checkout\Payment\PaymentMethodEntity;
use Cicada\Core\Checkout\Shipping\ShippingMethodCollection;
use Cicada\Core\Checkout\Shipping\ShippingMethodDefinition;
use Cicada\Core\Checkout\Shipping\ShippingMethodEntity;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Country\Aggregate\CountryState\CountryStateCollection;
use Cicada\Core\System\Country\Aggregate\CountryState\CountryStateDefinition;
use Cicada\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Cicada\Core\System\Country\CountryCollection;
use Cicada\Core\System\Country\CountryDefinition;
use Cicada\Core\System\Country\CountryEntity;
use Cicada\Core\System\Currency\Aggregate\CurrencyCountryRounding\CurrencyCountryRoundingCollection;
use Cicada\Core\System\Currency\Aggregate\CurrencyCountryRounding\CurrencyCountryRoundingDefinition;
use Cicada\Core\System\Currency\CurrencyCollection;
use Cicada\Core\System\Currency\CurrencyDefinition;
use Cicada\Core\System\Currency\CurrencyEntity;
use Cicada\Core\System\Language\LanguageCollection;
use Cicada\Core\System\Language\LanguageEntity;
use Cicada\Core\System\Locale\LocaleEntity;
use Cicada\Core\System\SalesChannel\Context\BaseContextFactory;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\SalesChannelCollection;
use Cicada\Core\System\SalesChannel\SalesChannelDefinition;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use Cicada\Core\System\Tax\TaxCollection;
use Cicada\Core\System\Tax\TaxDefinition;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Cicada\Core\Test\TestDefaults;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQL80Platform;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('discovery')]
#[CoversClass(BaseContextFactory::class)]
class BaseContextFactoryTest extends TestCase
{
    /**
     * @param array<string, mixed> $options
     * @param array<string, array<mixed>> $entitySearchResult
     * @param false|array<string, mixed> $fetchDataResult
     */
    #[DataProvider('factoryCreationDataProvider')]
    public function testCreate(
        array $options,
        false|array $fetchDataResult,
        false|string $fetchParentLanguageResult,
        array $entitySearchResult,
        ?string $exceptionMessage = null
    ): void {
        if ($exceptionMessage !== null) {
            $this->expectExceptionMessage($exceptionMessage);
        }

        /** @var StaticEntityRepository<CurrencyCollection> $currencyRepository */
        $currencyRepository = new StaticEntityRepository([new CurrencyCollection($entitySearchResult[CurrencyDefinition::ENTITY_NAME] ?? [])]);
        /** @var StaticEntityRepository<CustomerGroupCollection> $customerGroupRepository */
        $customerGroupRepository = new StaticEntityRepository([new CustomerGroupCollection($entitySearchResult[CustomerGroupDefinition::ENTITY_NAME] ?? [])]);
        /** @var StaticEntityRepository<CountryCollection> $countryRepository */
        $countryRepository = new StaticEntityRepository([new CountryCollection($entitySearchResult[CountryDefinition::ENTITY_NAME] ?? [])]);
        /** @var StaticEntityRepository<TaxCollection> $taxRepository */
        $taxRepository = new StaticEntityRepository([new TaxCollection($entitySearchResult[TaxDefinition::ENTITY_NAME] ?? [])]);
        /** @var StaticEntityRepository<PaymentMethodCollection> $paymentMethodRepository */
        $paymentMethodRepository = new StaticEntityRepository([new PaymentMethodCollection($entitySearchResult[PaymentMethodDefinition::ENTITY_NAME] ?? [])]);
        /** @var StaticEntityRepository<ShippingMethodCollection> $shippingMethodRepository */
        $shippingMethodRepository = new StaticEntityRepository([new ShippingMethodCollection($entitySearchResult[ShippingMethodDefinition::ENTITY_NAME] ?? [])]);
        /** @var StaticEntityRepository<SalesChannelCollection> $salesChannelRepository */
        $salesChannelRepository = new StaticEntityRepository([new SalesChannelCollection($entitySearchResult[SalesChannelDefinition::ENTITY_NAME] ?? [])]);
        /** @var StaticEntityRepository<CountryStateCollection> $countryStateRepository */
        $countryStateRepository = new StaticEntityRepository([new CountryStateCollection($entitySearchResult[CountryStateDefinition::ENTITY_NAME] ?? [])]);
        /** @var StaticEntityRepository<CurrencyCountryRoundingCollection> $currencyCountryRepository */
        $currencyCountryRepository = new StaticEntityRepository([new CurrencyCountryRoundingCollection($entitySearchResult[CurrencyCountryRoundingDefinition::ENTITY_NAME] ?? [])]);

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn(new MySQL80Platform());
        $connection->expects(static::once())->method('fetchAssociative')->willReturn($fetchDataResult);

        if ($fetchDataResult === false) {
            $connection->expects(static::never())->method('createQueryBuilder');
        }

        if ($fetchParentLanguageResult !== false) {
            $result = $this->createMock(Result::class);
            $result->expects(static::once())->method('fetchOne')->willReturn($fetchParentLanguageResult);
            $connection->expects(static::once())->method('executeQuery')->willReturn($result);
            $connection->expects(static::atMost(1))->method('createQueryBuilder')->willReturn(new QueryBuilder($connection));
        } else {
            $result = $this->createMock(Result::class);
            $result->expects(static::atMost(1))->method('fetchOne')->willReturn(false);
            $connection->expects(static::atMost(1))->method('executeQuery')->willReturn($result);
            $connection->expects(static::atMost(1))->method('createQueryBuilder')->willReturn(new QueryBuilder($connection));
        }

        $factory = new BaseContextFactory(
            $salesChannelRepository,
            $currencyRepository,
            $customerGroupRepository,
            $countryRepository,
            $taxRepository,
            $paymentMethodRepository,
            $shippingMethodRepository,
            $connection,
            $countryStateRepository,
            $currencyCountryRepository
        );

        $factory->create(TestDefaults::SALES_CHANNEL, $options);
    }

    /**
     * @return iterable<string, array<string, mixed>>
     */
    public static function factoryCreationDataProvider(): iterable
    {
        $invalidSalesChannelId = Uuid::randomHex();
        $paymentMethodId = Uuid::randomHex();
        $customerGroupId = Uuid::randomHex();
        $shippingMethodId = Uuid::randomHex();
        $currencyId = Uuid::randomHex();
        $countryStateId = Uuid::randomHex();
        $countryId = Uuid::randomHex();
        $anotherLanguageId = Uuid::randomHex();

        $locale = new LocaleEntity();
        $locale->setCode('en-GB');

        $language = new LanguageEntity();
        $language->setId(Defaults::LANGUAGE_SYSTEM);
        $language->setUniqueIdentifier(Defaults::LANGUAGE_SYSTEM);
        $language->setName('English');
        $language->setLocale($locale);
        $language->setTranslationCode($locale);

        $salesChannelEntity = new SalesChannelEntity();
        $salesChannelEntity->setUniqueIdentifier(TestDefaults::SALES_CHANNEL);
        $salesChannelEntity->setCustomerGroupId($customerGroupId);
        $salesChannelEntity->setPaymentMethodId($paymentMethodId);
        $salesChannelEntity->setShippingMethodId($shippingMethodId);
        $salesChannelEntity->setLanguages(new LanguageCollection([$language]));
        $salesChannelEntity->setCurrencyId(Defaults::CURRENCY);

        $currency = new CurrencyEntity();
        $rounding = new CashRoundingConfig(1, 1, true);
        $currency->setUniqueIdentifier($currencyId);
        $currency->setTotalRounding($rounding);
        $currency->setItemRounding($rounding);
        $currency->setId($currencyId);
        $currency->setFactor(1);

        $country = new CountryEntity();
        $country->setUniqueIdentifier($countryId);
        $country->setId($countryId);

        $countryState = new CountryStateEntity();
        $countryState->setUniqueIdentifier($countryStateId);
        $countryState->setCountryId($countryId);

        $paymentMethod = new PaymentMethodEntity();
        $paymentMethod->setUniqueIdentifier($paymentMethodId);

        $shippingMethod = new ShippingMethodEntity();
        $shippingMethod->setUniqueIdentifier($shippingMethodId);
        $salesChannelEntity->setShippingMethod($shippingMethod);

        $customerGroup = new CustomerGroupEntity();
        $customerGroup->setUniqueIdentifier($customerGroupId);

        yield 'no context data' => [
            'options' => [],
            'fetchDataResult' => false,
            'fetchParentLanguageResult' => false,
            'entitySearchResult' => [],
            'exceptionMessage' => \sprintf('No context data found for SalesChannel "%s"', TestDefaults::SALES_CHANNEL),
        ];

        yield 'provided language not available' => [
            'options' => [
                SalesChannelContextService::LANGUAGE_ID => $invalidSalesChannelId,
            ],
            'fetchDataResult' => [
                'sales_channel_default_language_id' => Uuid::randomBytes(),
                'sales_channel_language_ids' => Defaults::LANGUAGE_SYSTEM,
            ],
            'fetchParentLanguageResult' => false,
            'entitySearchResult' => [],
            'exceptionMessage' => \sprintf('Provided language "%s" is not in list of available languages: %s', $invalidSalesChannelId, Defaults::LANGUAGE_SYSTEM),
        ];

        yield 'language id is not uuid' => [
            'options' => [
                SalesChannelContextService::LANGUAGE_ID => 'not-an-uuid',
            ],
            'fetchDataResult' => [
                'sales_channel_default_language_id' => Uuid::randomBytes(),
                'sales_channel_language_ids' => '3ebb5fe2e29a4d70aa5854ce7ce3e20b,' . Defaults::LANGUAGE_SYSTEM,
            ],
            'fetchParentLanguageResult' => false,
            'entitySearchResult' => [],
            'exceptionMessage' => 'Provided language ID is not a valid UUID',
        ];

        yield 'language id not found' => [
            'options' => [
                SalesChannelContextService::LANGUAGE_ID => '3ebb5fe2e29a4d70aa5854ce7ce3e20b',
            ],
            'fetchDataResult' => [
                'sales_channel_default_language_id' => Uuid::randomBytes(),
                'sales_channel_language_ids' => '3ebb5fe2e29a4d70aa5854ce7ce3e20b,' . Defaults::LANGUAGE_SYSTEM,
            ],
            'fetchParentLanguageResult' => false,
            'entitySearchResult' => [],
            'exceptionMessage' => 'Could not find language with id "3ebb5fe2e29a4d70aa5854ce7ce3e20b"',
        ];

        yield 'sales channel not found' => [
            'options' => [
                SalesChannelContextService::LANGUAGE_ID => '3ebb5fe2e29a4d70aa5854ce7ce3e20b',
            ],
            'fetchDataResult' => [
                'sales_channel_default_language_id' => Uuid::randomBytes(),
                'sales_channel_currency_factor' => 1,
                'sales_channel_currency_id' => Uuid::randomBytes(),
                'sales_channel_language_ids' => '3ebb5fe2e29a4d70aa5854ce7ce3e20b,' . Defaults::LANGUAGE_SYSTEM,
            ],
            'fetchParentLanguageResult' => Uuid::randomHex(),
            'entitySearchResult' => [],
            'exceptionMessage' => \sprintf('Sales channel with id "%s" not found or not valid!.', TestDefaults::SALES_CHANNEL),
        ];

        yield 'currency id is not uuid' => [
            'options' => [
                SalesChannelContextService::LANGUAGE_ID => Defaults::LANGUAGE_SYSTEM,
                SalesChannelContextService::CURRENCY_ID => 'not-an-uuid',
            ],
            'fetchDataResult' => [
                'sales_channel_default_language_id' => Uuid::randomBytes(),
                'sales_channel_currency_factor' => 1,
                'sales_channel_currency_id' => Uuid::randomBytes(),
                'sales_channel_language_ids' => Defaults::LANGUAGE_SYSTEM,
            ],
            'fetchParentLanguageResult' => false,
            'entitySearchResult' => [
                SalesChannelDefinition::ENTITY_NAME => [
                    TestDefaults::SALES_CHANNEL => $salesChannelEntity,
                ],
            ],
            'exceptionMessage' => 'Provided currency ID is not a valid UUID',
        ];

        yield 'currency not found' => [
            'options' => [
                SalesChannelContextService::LANGUAGE_ID => Defaults::LANGUAGE_SYSTEM,
                SalesChannelContextService::CURRENCY_ID => '3ebb5fe2e29a4d70aa5854ce7ce3e20b',
            ],
            'fetchDataResult' => [
                'sales_channel_default_language_id' => Uuid::randomBytes(),
                'sales_channel_currency_factor' => 1,
                'sales_channel_currency_id' => Uuid::randomBytes(),
                'sales_channel_language_ids' => Defaults::LANGUAGE_SYSTEM,
            ],
            'fetchParentLanguageResult' => false,
            'entitySearchResult' => [
                SalesChannelDefinition::ENTITY_NAME => [
                    TestDefaults::SALES_CHANNEL => $salesChannelEntity,
                ],
            ],
            'exceptionMessage' => 'Could not find currency with id "3ebb5fe2e29a4d70aa5854ce7ce3e20b"',
        ];

        yield 'currency not set in options and not in sales channel' => [
            'options' => [
                SalesChannelContextService::LANGUAGE_ID => Defaults::LANGUAGE_SYSTEM,
            ],
            'fetchDataResult' => [
                'sales_channel_default_language_id' => Uuid::randomBytes(),
                'sales_channel_currency_factor' => 1,
                'sales_channel_currency_id' => Uuid::randomBytes(),
                'sales_channel_language_ids' => Defaults::LANGUAGE_SYSTEM,
            ],
            'fetchParentLanguageResult' => false,
            'entitySearchResult' => [
                SalesChannelDefinition::ENTITY_NAME => [
                    TestDefaults::SALES_CHANNEL => $salesChannelEntity,
                ],
            ],
            'exceptionMessage' => 'Could not find currency with id "b7d2554b0ce847cd82f3ac9bd1c0dfca"',
        ];

        yield 'customer group not found' => [
            'options' => [
                SalesChannelContextService::LANGUAGE_ID => Defaults::LANGUAGE_SYSTEM,
                SalesChannelContextService::CURRENCY_ID => $currencyId,
                SalesChannelContextService::COUNTRY_ID => $countryId,
            ],
            'fetchDataResult' => [
                'sales_channel_default_language_id' => Uuid::randomBytes(),
                'sales_channel_currency_factor' => 1,
                'sales_channel_currency_id' => Uuid::randomBytes(),
                'sales_channel_language_ids' => Defaults::LANGUAGE_SYSTEM,
            ],
            'fetchParentLanguageResult' => false,
            'entitySearchResult' => [
                SalesChannelDefinition::ENTITY_NAME => [
                    TestDefaults::SALES_CHANNEL => $salesChannelEntity,
                ],
                CurrencyDefinition::ENTITY_NAME => [
                    $currencyId => $currency,
                ],
                CountryDefinition::ENTITY_NAME => [
                    $countryId => $country,
                ],
            ],
            'exceptionMessage' => \sprintf('Could not find customer group with id "%s"', $customerGroupId),
        ];

        yield 'country state id is not uuid' => [
            'options' => [
                SalesChannelContextService::LANGUAGE_ID => Defaults::LANGUAGE_SYSTEM,
                SalesChannelContextService::CURRENCY_ID => $currencyId,
                SalesChannelContextService::COUNTRY_STATE_ID => 'not-an-uuid',
            ],
            'fetchDataResult' => [
                'sales_channel_default_language_id' => Uuid::randomBytes(),
                'sales_channel_currency_factor' => 1,
                'sales_channel_currency_id' => Uuid::randomBytes(),
                'sales_channel_language_ids' => Defaults::LANGUAGE_SYSTEM,
            ],
            'fetchParentLanguageResult' => false,
            'entitySearchResult' => [
                SalesChannelDefinition::ENTITY_NAME => [
                    TestDefaults::SALES_CHANNEL => $salesChannelEntity,
                ],
                CurrencyDefinition::ENTITY_NAME => [
                    $currencyId => $currency,
                ],
            ],
            'exceptionMessage' => 'Provided country state ID is not a valid UUID',
        ];

        yield 'country state not found' => [
            'options' => [
                SalesChannelContextService::LANGUAGE_ID => Defaults::LANGUAGE_SYSTEM,
                SalesChannelContextService::CURRENCY_ID => $currencyId,
                SalesChannelContextService::COUNTRY_STATE_ID => $countryStateId,
            ],
            'fetchDataResult' => [
                'sales_channel_default_language_id' => Uuid::randomBytes(),
                'sales_channel_currency_factor' => 1,
                'sales_channel_currency_id' => Uuid::randomBytes(),
                'sales_channel_language_ids' => Defaults::LANGUAGE_SYSTEM,
            ],
            'fetchParentLanguageResult' => false,
            'entitySearchResult' => [
                SalesChannelDefinition::ENTITY_NAME => [
                    TestDefaults::SALES_CHANNEL => $salesChannelEntity,
                ],
                CurrencyDefinition::ENTITY_NAME => [
                    $currencyId => $currency,
                ],
            ],
            'exceptionMessage' => \sprintf('Could not find country state with id "%s"', $countryStateId),
        ];

        yield 'country not found if country state ID is given' => [
            'options' => [
                SalesChannelContextService::LANGUAGE_ID => Defaults::LANGUAGE_SYSTEM,
                SalesChannelContextService::CURRENCY_ID => $currencyId,
                SalesChannelContextService::COUNTRY_STATE_ID => $countryStateId,
            ],
            'fetchDataResult' => [
                'sales_channel_default_language_id' => Uuid::randomBytes(),
                'sales_channel_currency_factor' => 1,
                'sales_channel_currency_id' => Uuid::randomBytes(),
                'sales_channel_language_ids' => Defaults::LANGUAGE_SYSTEM,
            ],
            'fetchParentLanguageResult' => false,
            'entitySearchResult' => [
                SalesChannelDefinition::ENTITY_NAME => [
                    TestDefaults::SALES_CHANNEL => $salesChannelEntity,
                ],
                CurrencyDefinition::ENTITY_NAME => [
                    $currencyId => $currency,
                ],
                CountryStateDefinition::ENTITY_NAME => [
                    $countryStateId => $countryState,
                ],
            ],
            'exceptionMessage' => \sprintf('Could not find country with id "%s"', $countryId),
        ];

        yield 'country id is not uuid' => [
            'options' => [
                SalesChannelContextService::LANGUAGE_ID => Defaults::LANGUAGE_SYSTEM,
                SalesChannelContextService::CURRENCY_ID => $currencyId,
                SalesChannelContextService::COUNTRY_ID => 'not-an-uuid',
            ],
            'fetchDataResult' => [
                'sales_channel_default_language_id' => Uuid::randomBytes(),
                'sales_channel_currency_factor' => 1,
                'sales_channel_currency_id' => Uuid::randomBytes(),
                'sales_channel_language_ids' => Defaults::LANGUAGE_SYSTEM,
            ],
            'fetchParentLanguageResult' => false,
            'entitySearchResult' => [
                SalesChannelDefinition::ENTITY_NAME => [
                    TestDefaults::SALES_CHANNEL => $salesChannelEntity,
                ],
                CurrencyDefinition::ENTITY_NAME => [
                    $currencyId => $currency,
                ],
            ],
            'exceptionMessage' => 'Provided country ID is not a valid UUID',
        ];

        yield 'country not found' => [
            'options' => [
                SalesChannelContextService::LANGUAGE_ID => Defaults::LANGUAGE_SYSTEM,
                SalesChannelContextService::CURRENCY_ID => $currencyId,
                SalesChannelContextService::COUNTRY_ID => $countryId,
            ],
            'fetchDataResult' => [
                'sales_channel_default_language_id' => Uuid::randomBytes(),
                'sales_channel_currency_factor' => 1,
                'sales_channel_currency_id' => Uuid::randomBytes(),
                'sales_channel_language_ids' => Defaults::LANGUAGE_SYSTEM,
            ],
            'fetchParentLanguageResult' => false,
            'entitySearchResult' => [
                SalesChannelDefinition::ENTITY_NAME => [
                    TestDefaults::SALES_CHANNEL => $salesChannelEntity,
                ],
                CurrencyDefinition::ENTITY_NAME => [
                    $currencyId => $currency,
                ],
            ],
            'exceptionMessage' => \sprintf('Could not find country with id "%s"', $countryId),
        ];

        yield 'payment method not found' => [
            'options' => [
                SalesChannelContextService::LANGUAGE_ID => Defaults::LANGUAGE_SYSTEM,
                SalesChannelContextService::CURRENCY_ID => $currencyId,
                SalesChannelContextService::COUNTRY_ID => $countryId,
            ],
            'fetchDataResult' => [
                'sales_channel_default_language_id' => Uuid::randomBytes(),
                'sales_channel_currency_factor' => 1,
                'sales_channel_currency_id' => Uuid::randomBytes(),
                'sales_channel_language_ids' => Defaults::LANGUAGE_SYSTEM,
            ],
            'fetchParentLanguageResult' => false,
            'entitySearchResult' => [
                SalesChannelDefinition::ENTITY_NAME => [
                    TestDefaults::SALES_CHANNEL => $salesChannelEntity,
                ],
                CurrencyDefinition::ENTITY_NAME => [
                    $currencyId => $currency,
                ],
                CountryDefinition::ENTITY_NAME => [
                    $countryId => $country,
                ],
                CustomerGroupDefinition::ENTITY_NAME => [
                    $customerGroupId => $customerGroup,
                ],
            ],
            'exceptionMessage' => \sprintf('Could not find payment method with id "%s"', $paymentMethodId),
        ];

        yield 'shipping method not found' => [
            'options' => [
                SalesChannelContextService::LANGUAGE_ID => Defaults::LANGUAGE_SYSTEM,
                SalesChannelContextService::CURRENCY_ID => $currencyId,
                SalesChannelContextService::COUNTRY_ID => $countryId,
            ],
            'fetchDataResult' => [
                'sales_channel_default_language_id' => Uuid::randomBytes(),
                'sales_channel_currency_factor' => 1,
                'sales_channel_currency_id' => Uuid::randomBytes(),
                'sales_channel_language_ids' => Defaults::LANGUAGE_SYSTEM,
            ],
            'fetchParentLanguageResult' => false,
            'entitySearchResult' => [
                SalesChannelDefinition::ENTITY_NAME => [
                    TestDefaults::SALES_CHANNEL => $salesChannelEntity,
                ],
                CurrencyDefinition::ENTITY_NAME => [
                    $currencyId => $currency,
                ],
                CountryDefinition::ENTITY_NAME => [
                    $countryId => $country,
                ],
                PaymentMethodDefinition::ENTITY_NAME => [
                    $paymentMethodId => $paymentMethod,
                ],
                CustomerGroupDefinition::ENTITY_NAME => [
                    $customerGroupId => $customerGroup,
                ],
            ],
            'exceptionMessage' => \sprintf('Could not find shipping method with id "%s"', $shippingMethodId),
        ];

        yield 'missing sales channel language' => [
            'options' => [
                SalesChannelContextService::LANGUAGE_ID => $anotherLanguageId,
                SalesChannelContextService::CURRENCY_ID => $currencyId,
                SalesChannelContextService::COUNTRY_ID => $countryId,
            ],
            'fetchDataResult' => [
                'sales_channel_default_language_id' => Uuid::randomBytes(),
                'sales_channel_currency_factor' => 1,
                'sales_channel_currency_id' => Uuid::randomBytes(),
                'sales_channel_language_ids' => Defaults::LANGUAGE_SYSTEM . ',' . $anotherLanguageId,
            ],
            'fetchParentLanguageResult' => Defaults::LANGUAGE_SYSTEM,
            'entitySearchResult' => [
                SalesChannelDefinition::ENTITY_NAME => [
                    TestDefaults::SALES_CHANNEL => $salesChannelEntity,
                ],
                CurrencyDefinition::ENTITY_NAME => [
                    $currencyId => $currency,
                ],
                CountryDefinition::ENTITY_NAME => [
                    $countryId => $country,
                ],
                PaymentMethodDefinition::ENTITY_NAME => [
                    $paymentMethodId => $paymentMethod,
                ],
                ShippingMethodDefinition::ENTITY_NAME => [
                    $shippingMethodId => $shippingMethod,
                ],
                CustomerGroupDefinition::ENTITY_NAME => [
                    $customerGroupId => $customerGroup,
                ],
            ],
            'exceptionMessage' => \sprintf('Could not find language with id "%s"', $anotherLanguageId),
        ];

        yield 'create base context successfully' => [
            'options' => [
                SalesChannelContextService::LANGUAGE_ID => Defaults::LANGUAGE_SYSTEM,
                SalesChannelContextService::CURRENCY_ID => $currencyId,
                SalesChannelContextService::COUNTRY_ID => $countryId,
            ],
            'fetchDataResult' => [
                'sales_channel_default_language_id' => Uuid::randomBytes(),
                'sales_channel_currency_factor' => 1,
                'sales_channel_currency_id' => Uuid::randomBytes(),
                'sales_channel_language_ids' => Defaults::LANGUAGE_SYSTEM,
            ],
            'fetchParentLanguageResult' => false,
            'entitySearchResult' => [
                SalesChannelDefinition::ENTITY_NAME => [
                    TestDefaults::SALES_CHANNEL => $salesChannelEntity,
                ],
                CurrencyDefinition::ENTITY_NAME => [
                    $currencyId => $currency,
                ],
                CountryDefinition::ENTITY_NAME => [
                    $countryId => $country,
                ],
                PaymentMethodDefinition::ENTITY_NAME => [
                    $paymentMethodId => $paymentMethod,
                ],
                ShippingMethodDefinition::ENTITY_NAME => [
                    $shippingMethodId => $shippingMethod,
                ],
                CustomerGroupDefinition::ENTITY_NAME => [
                    $customerGroupId => $customerGroup,
                ],
            ],
            'exceptionMessage' => null,
        ];
    }
}
