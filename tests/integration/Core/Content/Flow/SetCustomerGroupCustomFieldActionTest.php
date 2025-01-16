<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Flow;

use Cicada\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Cicada\Core\Checkout\Customer\Event\CustomerGroupRegistrationAccepted;
use Cicada\Core\Content\Flow\Dispatching\Action\SetCustomerGroupCustomFieldAction;
use Cicada\Core\Content\Test\Flow\OrderActionTrait;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\AdminApiTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\CacheTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('after-sales')]
class SetCustomerGroupCustomFieldActionTest extends TestCase
{
    use AdminApiTestBehaviour;
    use CacheTestBehaviour;
    use OrderActionTrait;

    private EntityRepository $flowRepository;

    protected function setUp(): void
    {
        $this->flowRepository = static::getContainer()->get('flow.repository');

        $this->customerRepository = static::getContainer()->get('customer.repository');

        $this->ids = new IdsCollection();

        $this->browser = $this->createCustomSalesChannelBrowser([
            'id' => $this->ids->create('sales-channel'),
        ]);
    }

    /**
     * @param array<int, mixed>|null $existedData
     * @param array<int, mixed>|null $updateData
     * @param array<int, mixed>|null $expectData
     */
    #[DataProvider('createDataProvider')]
    public function testCreateCustomFieldForCustomerGroup(string $option, ?array $existedData, ?array $updateData, ?array $expectData): void
    {
        $customFieldName = 'custom_field_test';
        $entity = 'customer_group';
        $customFieldId = $this->createCustomField($customFieldName, $entity);

        $email = 'thuy@gmail.com';
        $this->prepareCustomer($email, [
            'requestedGroup' => [
                'id' => $this->ids->create('customer_group'),
                'name' => 'foo',
                'customFields' => [$customFieldName => $existedData],
            ],
        ]);

        $sequenceId = Uuid::randomHex();
        $this->flowRepository->create([[
            'name' => 'Customer group registration accepted',
            'eventName' => CustomerGroupRegistrationAccepted::EVENT_NAME,
            'priority' => 1,
            'active' => true,
            'sequences' => [
                [
                    'id' => $sequenceId,
                    'parentId' => null,
                    'ruleId' => null,
                    'actionName' => SetCustomerGroupCustomFieldAction::getName(),
                    'position' => 1,
                    'config' => [
                        'entity' => $entity,
                        'customFieldId' => $customFieldId,
                        'customFieldText' => $customFieldName,
                        'customFieldValue' => $updateData,
                        'customFieldSetId' => null,
                        'customFieldSetText' => null,
                        'option' => $option,
                    ],
                ],
            ],
        ]], Context::createDefaultContext());

        $browser = $this->createClient();
        $browser->request('POST', '/api/_action/customer-group-registration/accept', [
            'customerIds' => [$this->ids->get('customer')],
        ]);

        /** @var CustomerGroupEntity $customerGroup */
        $customerGroup = static::getContainer()->get('customer_group.repository')
            ->search(new Criteria([$this->ids->get('customer_group')]), Context::createDefaultContext())->first();

        $expect = $option === 'clear' ? null : [$customFieldName => $expectData];
        static::assertEquals($customerGroup->getCustomFields(), $expect);
    }

    /**
     * @return array<string, mixed>
     */
    public static function createDataProvider(): array
    {
        return [
            'upsert / existed data / update data / expect data' => ['upsert', ['red', 'green'], ['blue', 'gray'], ['blue', 'gray']],
            'create / existed data / update data / expect data' => ['create', ['red', 'green'], ['blue', 'gray'], ['red', 'green']],
            'clear / existed data / update data / expect data' => ['clear', ['red', 'green', 'blue'], null, null],
            'add / existed data / update data / expect data' => ['add', ['red', 'green'], ['blue', 'gray'], ['red', 'green', 'blue', 'gray']],
            'remove / existed data / update data / expect data' => ['remove', ['red', 'green', 'blue'], ['green', 'blue'], ['red']],
        ];
    }
}
