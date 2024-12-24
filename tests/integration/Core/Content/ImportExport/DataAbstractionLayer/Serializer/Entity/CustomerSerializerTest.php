<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Entity;

use Cicada\Core\Checkout\Customer\CustomerDefinition;
use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Entity\CustomerSerializer;
use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\SerializerRegistry;
use Cicada\Core\Content\ImportExport\Struct\Config;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(CustomerSerializer::class)]
class CustomerSerializerTest extends TestCase
{
    use IntegrationTestBehaviour;
    use SalesChannelApiTestBehaviour;

    private EntityRepository $customerGroupRepository;

    private EntityRepository $paymentMethodRepository;

    private EntityRepository $salesChannelRepository;

    private EntityRepository $customerRepository;

    private CustomerSerializer $serializer;

    private string $customerGroupId = 'a536fe4ef675470f8cddfcc7f8360e4b';

    private string $paymentMethodId = '733530bc28f74bfbb43c32b595ac9fa0';

    protected function setUp(): void
    {
        $this->customerGroupRepository = static::getContainer()->get('customer_group.repository');
        $this->paymentMethodRepository = static::getContainer()->get('payment_method.repository');
        $this->salesChannelRepository = static::getContainer()->get('sales_channel.repository');
        $this->customerRepository = static::getContainer()->get('customer.repository');
        $serializerRegistry = static::getContainer()->get(SerializerRegistry::class);

        $this->serializer = new CustomerSerializer(
            $this->customerGroupRepository,
            $this->paymentMethodRepository,
            $this->salesChannelRepository
        );
        $this->serializer->setRegistry($serializerRegistry);
    }

    public function testSimple(): void
    {
        $salesChannel = $this->createSalesChannel();
        $this->createCustomerGroup();
        $this->createPaymentMethod();

        $config = new Config([], [], []);
        $customer = [
            'group' => [
                'translations' => [
                    'DEFAULT' => [
                        'name' => 'test customer group',
                    ],
                ],
            ],
            'defaultPaymentMethod' => [
                'translations' => [
                    'DEFAULT' => [
                        'name' => 'test payment method',
                    ],
                ],
            ],
            'salesChannel' => [
                'translations' => [
                    'DEFAULT' => [
                        'name' => $salesChannel['name'],
                    ],
                ],
            ],
            'boundSalesChannel' => [
                'translations' => [
                    'DEFAULT' => [
                        'name' => $salesChannel['name'],
                    ],
                ],
            ],
        ];

        $deserialized = $this->serializer->deserialize($config, $this->customerRepository->getDefinition(), $customer);

        static::assertIsNotArray($deserialized);

        $deserialized = \iterator_to_array($deserialized);

        static::assertSame($this->customerGroupId, $deserialized['group']['id']);
        if (!Feature::isActive('v6.7.0.0')) {
            static::assertSame($this->paymentMethodId, $deserialized['defaultPaymentMethod']['id']);
        }
        static::assertSame($salesChannel['id'], $deserialized['salesChannel']['id']);
        static::assertSame($salesChannel['id'], $deserialized['boundSalesChannel']['id']);
    }

    public function testSupportsOnlyCountry(): void
    {
        $serializer = new CustomerSerializer(
            $this->customerGroupRepository,
            $this->paymentMethodRepository,
            $this->salesChannelRepository
        );

        $definitionRegistry = static::getContainer()->get(DefinitionInstanceRegistry::class);
        foreach ($definitionRegistry->getDefinitions() as $definition) {
            $entity = $definition->getEntityName();

            if ($entity === CustomerDefinition::ENTITY_NAME) {
                static::assertTrue($serializer->supports($entity));
            } else {
                static::assertFalse(
                    $serializer->supports($entity),
                    CustomerDefinition::class . ' should not support ' . $entity
                );
            }
        }
    }

    private function createCustomerGroup(): void
    {
        $this->customerGroupRepository->upsert([
            [
                'id' => $this->customerGroupId,
                'name' => 'test customer group',
            ],
        ], Context::createDefaultContext());
    }

    private function createPaymentMethod(): void
    {
        $this->paymentMethodRepository->upsert([
            [
                'id' => $this->paymentMethodId,
                'name' => 'test payment method',
                'technicalName' => 'payment_test',
            ],
        ], Context::createDefaultContext());
    }
}
