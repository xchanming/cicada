<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Document;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\LineItemFactoryHandler\ProductLineItemFactory;
use Cicada\Core\Checkout\Cart\PriceDefinitionFactory;
use Cicada\Core\Checkout\Cart\SalesChannel\CartService;
use Cicada\Core\Checkout\Document\Aggregate\DocumentBaseConfig\DocumentBaseConfigEntity;
use Cicada\Core\Checkout\Document\DocumentIdCollection;
use Cicada\Core\Checkout\Document\FileGenerator\FileTypes;
use Cicada\Core\Checkout\Document\Service\DocumentGenerator;
use Cicada\Core\Checkout\Document\Struct\DocumentGenerateOperation;
use Cicada\Core\Content\Test\Product\ProductBuilder;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\TaxAddToSalesChannelTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('checkout')]
trait DocumentTrait
{
    use IntegrationTestBehaviour;
    use TaxAddToSalesChannelTestBehaviour;

    private function persistCart(Cart $cart): string
    {
        return static::getContainer()->get(CartService::class)->order($cart, $this->salesChannelContext, new RequestDataBag());
    }

    /**
     * @param array<string, string> $options
     */
    private function createCustomer(array $options = []): string
    {
        $customerId = Uuid::randomHex();
        $addressId = Uuid::randomHex();

        $customer = [
            'id' => $customerId,
            'number' => '1337',
            'salutationId' => $this->getValidSalutationId(),
            'name' => 'Max',
            'customerNumber' => '1337',
            'languageId' => Defaults::LANGUAGE_SYSTEM,
            'email' => Uuid::randomHex() . '@example.com',
            'password' => TestDefaults::HASHED_PASSWORD,
            'groupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'defaultBillingAddressId' => $addressId,
            'defaultShippingAddressId' => $addressId,
            'addresses' => [
                [
                    'id' => $addressId,
                    'customerId' => $customerId,
                    'countryId' => $this->getValidCountryId(),
                    'salutationId' => $this->getValidSalutationId(),
                    'name' => 'Max',
                    'street' => 'Ebbinghoff 10',
                    'zipcode' => '48624',
                    'city' => 'Schöppingen',
                ],
            ],
        ];

        if (!Feature::isActive('v6.7.0.0')) {
            $customer['defaultPaymentMethodId'] = $this->getAvailablePaymentMethod()->getId();
        }

        $customer = array_merge($customer, $options);

        static::getContainer()->get('customer.repository')->upsert([$customer], $this->context);

        return $customerId;
    }

    private function generateDemoCart(int $lineItemCount): Cart
    {
        $cartService = static::getContainer()->get(CartService::class);

        $cart = $cartService->createNew('a-b-c');

        $keywords = ['awesome', 'epic', 'high quality'];

        $products = [];

        $factory = new ProductLineItemFactory(new PriceDefinitionFactory());

        $ids = new IdsCollection();

        $lineItems = [];

        for ($i = 0; $i < $lineItemCount; ++$i) {
            $price = random_int(100, 200000) / 100.0;

            shuffle($keywords);
            $name = ucfirst(implode(' ', $keywords) . ' product');

            $number = Uuid::randomHex();

            $product = (new ProductBuilder($ids, $number))
                ->price($price)
                ->name($name)
                ->active(true)
                ->tax('test-' . Uuid::randomHex(), 7)
                ->visibility()
                ->build();

            $products[] = $product;

            $lineItems[] = $factory->create(['id' => $ids->get($number), 'referencedId' => $ids->get($number)], $this->salesChannelContext);
            $this->addTaxDataToSalesChannel($this->salesChannelContext, $product['tax']);
        }

        static::getContainer()->get('product.repository')->create($products, Context::createDefaultContext());

        return $cartService->add($cart, $lineItems, $this->salesChannelContext);
    }

    private function getBaseConfig(string $documentType, ?string $salesChannelId = null): ?DocumentBaseConfigEntity
    {
        /** @var EntityRepository $documentTypeRepository */
        $documentTypeRepository = static::getContainer()->get('document_type.repository');
        $documentTypeId = $documentTypeRepository->searchIds(
            (new Criteria())->addFilter(new EqualsFilter('technicalName', $documentType)),
            Context::createDefaultContext()
        )->firstId();

        /** @var EntityRepository $documentBaseConfigRepository */
        $documentBaseConfigRepository = static::getContainer()->get('document_base_config.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('documentTypeId', $documentTypeId));
        $criteria->addFilter(new EqualsFilter('global', true));

        if ($salesChannelId !== null) {
            $criteria->addFilter(new EqualsFilter('salesChannels.salesChannelId', $salesChannelId));
            $criteria->addFilter(new EqualsFilter('salesChannels.documentTypeId', $documentTypeId));
        }

        $config = $documentBaseConfigRepository->search($criteria, Context::createDefaultContext())->first();

        if ($config === null) {
            return null;
        }

        static::assertInstanceOf(DocumentBaseConfigEntity::class, $config);

        return $config;
    }

    /**
     * @param array<string, array<string, string>|string> $config
     */
    private function createDocument(string $documentType, string $orderId, array $config, Context $context): DocumentIdCollection
    {
        $operations = [];
        $operation = new DocumentGenerateOperation($orderId, FileTypes::PDF, $config);
        $operations[$orderId] = $operation;

        return static::getContainer()->get(DocumentGenerator::class)->generate($documentType, $operations, $context)->getSuccess();
    }

    /**
     * @param array<string|bool, string|bool|int|array<int, string>> $config
     */
    private function upsertBaseConfig(array $config, string $documentType, ?string $salesChannelId = null): void
    {
        $baseConfig = $this->getBaseConfig($documentType, $salesChannelId);

        /** @var EntityRepository $documentTypeRepository */
        $documentTypeRepository = static::getContainer()->get('document_type.repository');
        $documentTypeId = $documentTypeRepository->searchIds(
            (new Criteria())->addFilter(new EqualsFilter('technicalName', $documentType)),
            Context::createDefaultContext()
        )->firstId();

        if ($baseConfig === null) {
            $documentConfigId = Uuid::randomHex();
        } else {
            $documentConfigId = $baseConfig->getId();
        }

        $data = [
            'id' => $documentConfigId,
            'typeId' => $documentTypeId,
            'documentTypeId' => $documentTypeId,
            'config' => $config,
        ];
        if ($baseConfig === null) {
            $data['name'] = $documentConfigId;
        }
        if ($salesChannelId !== null) {
            $data['salesChannels'] = [
                [
                    'documentBaseConfigId' => $documentConfigId,
                    'documentTypeId' => $documentTypeId,
                    'salesChannelId' => $salesChannelId,
                ],
            ];
        }

        /** @var EntityRepository $documentBaseConfigRepository */
        $documentBaseConfigRepository = static::getContainer()->get('document_base_config.repository');
        $documentBaseConfigRepository->upsert([$data], Context::createDefaultContext());
    }

    private function orderVersionExists(string $orderId, string $orderVersionId): bool
    {
        return (bool) static::getContainer()->get(Connection::class)->fetchOne('
            SELECT 1 FROM `order` WHERE `id` = :id AND `version_id` = :versionId
        ', [
            'id' => Uuid::fromHexToBytes($orderId),
            'versionId' => Uuid::fromHexToBytes($orderVersionId),
        ]);
    }
}
