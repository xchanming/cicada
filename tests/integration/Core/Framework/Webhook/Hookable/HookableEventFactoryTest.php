<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Webhook\Hookable;

use Cicada\Core\Checkout\Customer\Event\CustomerBeforeLoginEvent;
use Cicada\Core\Content\Flow\Dispatching\FlowFactory;
use Cicada\Core\Content\Flow\Dispatching\FlowState;
use Cicada\Core\Content\Test\Flow\TestFlowBusinessEvent;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Webhook\Hookable\HookableBusinessEvent;
use Cicada\Core\Framework\Webhook\Hookable\HookableEventFactory;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(HookableEventFactory::class)]
class HookableEventFactoryTest extends TestCase
{
    use IntegrationTestBehaviour;

    private HookableEventFactory $hookableEventFactory;

    protected function setUp(): void
    {
        $this->hookableEventFactory = static::getContainer()->get(HookableEventFactory::class);
    }

    public function testDoesNotCreateEventForConcreteBusinessEvent(): void
    {
        $factory = static::getContainer()->get(FlowFactory::class);
        $event = $factory->create(new CustomerBeforeLoginEvent(
            static::getContainer()->get(SalesChannelContextFactory::class)->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL),
            'test@example.com'
        ));
        $event->setFlowState(new FlowState());
        $hookables = $this->hookableEventFactory->createHookablesFor($event);

        static::assertEmpty($hookables);
    }

    public function testDoesCreateHookableBusinessEvent(): void
    {
        $hookables = $this->hookableEventFactory->createHookablesFor(
            new TestFlowBusinessEvent(Context::createDefaultContext())
        );

        static::assertCount(1, $hookables);
        static::assertInstanceOf(HookableBusinessEvent::class, $hookables[0]);
    }

    public function testCreatesHookableEntityInsert(): void
    {
        $id = Uuid::randomHex();

        /** @var EntityRepository $productRepository */
        $productRepository = static::getContainer()->get('product.repository');
        $writtenEvent = $this->insertProduct($id, $productRepository);

        $hookables = $this->hookableEventFactory->createHookablesFor($writtenEvent);

        static::assertCount(1, $hookables);
        $event = $hookables[0];
        static::assertEquals('product.written', $event->getName());

        $payload = $event->getWebhookPayload();
        static::assertCount(1, $payload);
        $actualUpdatedFields = $payload[0]['updatedFields'];
        unset($payload[0]['updatedFields']);

        static::assertEquals([[
            'entity' => 'product',
            'operation' => 'insert',
            'primaryKey' => $id,
            'versionId' => Defaults::LIVE_VERSION,
        ]], $payload);

        $expectedUpdatedFields = [
            'versionId',
            'id',
            'parentVersionId',
            'manufacturerId',
            'productManufacturerVersionId',
            'productMediaVersionId',
            'taxId',
            'stock',
            'price',
            'productNumber',
            'isCloseout',
            'purchaseSteps',
            'minPurchase',
            'shippingFree',
            'restockTime',
            'createdAt',
            'name',
        ];

        foreach ($expectedUpdatedFields as $field) {
            static::assertContains($field, $actualUpdatedFields);
        }
    }

    public function testCreatesHookableEntityUpdate(): void
    {
        $id = Uuid::randomHex();

        /** @var EntityRepository $productRepository */
        $productRepository = static::getContainer()->get('product.repository');
        $this->insertProduct($id, $productRepository);

        $writtenEvent = $productRepository->upsert([
            [
                'id' => $id,
                'stock' => 99,
                'price' => [
                    [
                        'gross' => 200,
                        'net' => 250,
                        'linked' => false,
                        'currencyId' => Defaults::CURRENCY,
                    ],
                ],
            ],
        ], Context::createDefaultContext());

        $hookables = $this->hookableEventFactory->createHookablesFor($writtenEvent);

        static::assertCount(1, $hookables);
        $event = $hookables[0];
        static::assertEquals('product.written', $event->getName());

        $payload = $event->getWebhookPayload();
        $actualUpdatedFields = $payload[0]['updatedFields'];
        unset($payload[0]['updatedFields']);

        static::assertEquals([[
            'entity' => 'product',
            'operation' => 'update',
            'primaryKey' => $id,
            'versionId' => Defaults::LIVE_VERSION,
        ]], $payload);

        $expectedUpdatedFields = [
            'stock',
            'price',
            'updatedAt',
            'id',
            'versionId',
        ];

        foreach ($expectedUpdatedFields as $field) {
            static::assertContains($field, $actualUpdatedFields);
        }
    }

    public function testCreatesHookableEntityDelete(): void
    {
        $id = Uuid::randomHex();

        /** @var EntityRepository $productRepository */
        $productRepository = static::getContainer()->get('product.repository');
        $this->insertProduct($id, $productRepository);

        $writtenEvent = $productRepository->delete([['id' => $id]], Context::createDefaultContext());

        $hookables = $this->hookableEventFactory->createHookablesFor($writtenEvent);

        static::assertCount(1, $hookables);
        $event = $hookables[0];
        static::assertEquals('product.deleted', $event->getName());
        static::assertEquals([[
            'entity' => 'product',
            'operation' => 'delete',
            'primaryKey' => $id,
            'versionId' => Defaults::LIVE_VERSION,
        ]], $event->getWebhookPayload());
    }

    public function testDoesNotCreateHookableNotHookableEntity(): void
    {
        $id = Uuid::randomHex();
        /** @var EntityRepository $taxRepository */
        $taxRepository = static::getContainer()->get('tax.repository');

        $createdEvent = $taxRepository->upsert([
            [
                'id' => $id,
                'name' => 'luxury',
                'taxRate' => '25',
            ],
        ], Context::createDefaultContext());

        static::assertEmpty(
            $this->hookableEventFactory->createHookablesFor($createdEvent)
        );

        $updatedEvent = $taxRepository->upsert([
            [
                'id' => $id,
                'name' => 'test update',
            ],
        ], Context::createDefaultContext());

        static::assertEmpty(
            $this->hookableEventFactory->createHookablesFor($updatedEvent)
        );

        $deletedEvent = $taxRepository->delete([['id' => $id]], Context::createDefaultContext());

        static::assertEmpty(
            $this->hookableEventFactory->createHookablesFor($deletedEvent)
        );
    }

    public function testCreatesEntityWriteForTranslationUpdate(): void
    {
        $id = Uuid::randomHex();

        /** @var EntityRepository $productRepository */
        $productRepository = static::getContainer()->get('product.repository');
        $this->insertProduct($id, $productRepository);

        $writtenEvent = $productRepository->upsert([
            [
                'id' => $id,
                'name' => 'a new name',
                'description' => 'a fancy description.',
            ],
        ], Context::createDefaultContext());

        $hookables = $this->hookableEventFactory->createHookablesFor($writtenEvent);

        static::assertCount(1, $hookables);
        $event = $hookables[0];
        static::assertEquals('product.written', $event->getName());

        static::assertEquals([[
            'entity' => 'product',
            'operation' => 'update',
            'primaryKey' => $id,
            'updatedFields' => [
                'versionId',
                'parentVersionId',
                'productManufacturerVersionId',
                'productMediaVersionId',
                'canonicalProductVersionId',
                'cmsPageVersionId',
                'updatedAt',
                'id',
                'name',
                'description',
            ],
            'versionId' => Defaults::LIVE_VERSION,
        ]], $event->getWebhookPayload());
    }

    public function testCreatesMultipleHookables(): void
    {
        $id = Uuid::randomHex();
        $productPriceId = Uuid::randomHex();

        /** @var EntityRepository $productRepository */
        $productRepository = static::getContainer()->get('product.repository');
        $this->insertProduct($id, $productRepository);

        $ruleRepository = static::getContainer()->get('rule.repository');
        $ruleId = $ruleRepository->searchIds(new Criteria(), Context::createDefaultContext())->firstId();

        $writtenEvent = $productRepository->upsert([
            [
                'id' => $id,
                'name' => 'a new name',
                'description' => 'a fancy description.',
                'prices' => [
                    [
                        'id' => $productPriceId,
                        'ruleId' => $ruleId,
                        'quantityStart' => 1,
                        'price' => [
                            [
                                'gross' => 100,
                                'net' => 200,
                                'linked' => false,
                                'currencyId' => Defaults::CURRENCY,
                            ],
                        ],
                    ],
                ],
            ],
        ], Context::createDefaultContext());

        $hookables = $this->hookableEventFactory->createHookablesFor($writtenEvent);

        static::assertCount(2, $hookables);
        $event = $hookables[0];
        static::assertEquals('product.written', $event->getName());

        static::assertEquals([[
            'entity' => 'product',
            'operation' => 'update',
            'primaryKey' => $id,
            'updatedFields' => [
                'versionId',
                'parentVersionId',
                'productManufacturerVersionId',
                'productMediaVersionId',
                'canonicalProductVersionId',
                'cmsPageVersionId',
                'updatedAt',
                'id',
                'name',
                'description',
            ],
            'versionId' => Defaults::LIVE_VERSION,
        ]], $event->getWebhookPayload());

        $event = $hookables[1];
        static::assertEquals('product_price.written', $event->getName());
        static::assertEquals([[
            'entity' => 'product_price',
            'operation' => 'insert',
            'primaryKey' => $productPriceId,
            'updatedFields' => [
                'id',
                'versionId',
                'productId',
                'productVersionId',
                'ruleId',
                'price',
                'quantityStart',
                'createdAt',
            ],
            'versionId' => Defaults::LIVE_VERSION,
        ]], $event->getWebhookPayload());
    }

    public function testDoesNotCreateMultipleHookablesForEmptyEvents(): void
    {
        $id = Uuid::randomHex();

        /** @var EntityRepository $productRepository */
        $productRepository = static::getContainer()->get('product.repository');
        $this->insertProduct($id, $productRepository);

        $ruleRepository = static::getContainer()->get('rule.repository');
        $ruleId = $ruleRepository->searchIds(new Criteria(), Context::createDefaultContext())->firstId();

        /** @var EntityRepository $productPriceRepository */
        $productPriceRepository = static::getContainer()->get('product_price.repository');
        $writtenEvent = $productPriceRepository->upsert([
            [
                'id' => $id,
                'productId' => $id,
                'ruleId' => $ruleId,
                'quantityStart' => 1,
                'price' => [
                    [
                        'gross' => 100,
                        'net' => 200,
                        'linked' => false,
                        'currencyId' => Defaults::CURRENCY,
                    ],
                ],
            ],
        ], Context::createDefaultContext());

        $hookables = $this->hookableEventFactory->createHookablesFor($writtenEvent);

        static::assertCount(1, $hookables);

        $event = $hookables[0];
        static::assertEquals('product_price.written', $event->getName());
        static::assertEquals([[
            'entity' => 'product_price',
            'operation' => 'insert',
            'primaryKey' => $id,
            'updatedFields' => [
                'id',
                'versionId',
                'productId',
                'productVersionId',
                'ruleId',
                'price',
                'quantityStart',
                'createdAt',
            ],
            'versionId' => Defaults::LIVE_VERSION,
        ]], $event->getWebhookPayload());
    }

    public function testCreatesHookableEntityInsertWithoutVersionId(): void
    {
        $id = Uuid::randomHex();

        /** @var EntityRepository $salesChannelDomainRepository */
        $salesChannelDomainRepository = static::getContainer()->get('sales_channel_domain.repository');
        $writtenEvent = $this->insertSalesChannelDomain($id, $salesChannelDomainRepository);

        $hookables = $this->hookableEventFactory->createHookablesFor($writtenEvent);

        static::assertCount(1, $hookables);
        $event = $hookables[0];
        static::assertEquals('sales_channel_domain.written', $event->getName());

        $payload = $event->getWebhookPayload();
        static::assertCount(1, $payload);
        $actualUpdatedFields = $payload[0]['updatedFields'];
        unset($payload[0]['updatedFields']);

        static::assertEquals([[
            'entity' => 'sales_channel_domain',
            'operation' => 'insert',
            'primaryKey' => $id,
        ]], $payload);

        $expectedUpdatedFields = [
            'id',
            'salesChannelId',
            'url',
            'languageId',
            'currencyId',
            'snippetSetId',
        ];

        foreach ($expectedUpdatedFields as $field) {
            static::assertContains($field, $actualUpdatedFields);
        }
    }

    private function insertProduct(string $id, EntityRepository $productRepository): EntityWrittenContainerEvent
    {
        return $productRepository->upsert([
            [
                'id' => $id,
                'name' => 'testProduct',
                'productNumber' => 'SWC-1000',
                'stock' => 100,
                'manufacturer' => [
                    'name' => 'app creator',
                ],
                'price' => [
                    [
                        'gross' => 100,
                        'net' => 200,
                        'linked' => false,
                        'currencyId' => Defaults::CURRENCY,
                    ],
                ],
                'tax' => [
                    'name' => 'luxury',
                    'taxRate' => '25',
                ],
            ],
        ], Context::createDefaultContext());
    }

    private function insertSalesChannelDomain(
        string $id,
        EntityRepository $salesChannelDomainRepository
    ): EntityWrittenContainerEvent {
        return $salesChannelDomainRepository->upsert([
            [
                'id' => $id,
                'salesChannelId' => TestDefaults::SALES_CHANNEL,
                'url' => 'http://test.com',
                'languageId' => Defaults::LANGUAGE_SYSTEM,
                'currencyId' => Defaults::CURRENCY,
                'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
            ],
        ], Context::createDefaultContext());
    }
}
