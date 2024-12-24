<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Flow\Dispatching\Storer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Flow\Dispatching\Action\FlowMailVariables;
use Cicada\Core\Content\Flow\Dispatching\Aware\ScalarValuesAware;
use Cicada\Core\Content\Flow\Dispatching\Storer\CustomAppStorer;
use Cicada\Core\Content\Test\Flow\TestFlowBusinessEvent;
use Cicada\Core\Framework\App\Event\CustomAppEvent;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CustomerAware;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(CustomAppStorer::class)]
class CustomAppStorerTest extends TestCase
{
    private CustomAppStorer $customAppStorer;

    private Context $context;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        $this->customAppStorer = new CustomAppStorer();
    }

    public function testStoreWithAwareAndEmptyCustomData(): void
    {
        $event = new CustomAppEvent('custom_app_event', [], $this->context);

        $stored = [];
        $stored = $this->customAppStorer->store($event, $stored);
        static::assertEmpty($stored);
    }

    public function testStoreWithAware(): void
    {
        $customerId = Uuid::randomHex();

        $data = [
            'eventName' => 'swag.before.open_the_doors',
            CustomerAware::CUSTOMER_ID => $customerId,
            ScalarValuesAware::STORE_VALUES => [
                CustomerAware::CUSTOMER_ID => $customerId,
            ],
        ];

        $event = new CustomAppEvent('custom_app_event', $data, $this->context);

        $stored = [];
        $stored = $this->customAppStorer->store($event, $stored);
        static::assertEquals($data, $stored);
    }

    public function testStoreWithScalarAware(): void
    {
        $customerId = Uuid::randomHex();

        $data = [
            FlowMailVariables::SHOP_NAME => 'cicada',
            FlowMailVariables::RESET_URL => 'http://cicada.test',
            CustomerAware::CUSTOMER_ID => $customerId,
        ];

        $expected = [
            CustomerAware::CUSTOMER_ID => $customerId,
            FlowMailVariables::SHOP_NAME => 'cicada',
            FlowMailVariables::RESET_URL => 'http://cicada.test',
            ScalarValuesAware::STORE_VALUES => [
                FlowMailVariables::SHOP_NAME => 'cicada',
                FlowMailVariables::RESET_URL => 'http://cicada.test',
                CustomerAware::CUSTOMER_ID => $customerId,
            ],
        ];

        $event = new CustomAppEvent('custom_app_event', $data, $this->context);

        $stored = [];
        $stored = $this->customAppStorer->store($event, $stored);

        static::assertEquals($expected, $stored);
    }

    public function testStoreWithNotAware(): void
    {
        $event = new TestFlowBusinessEvent($this->context);

        $stored = [];
        $stored = $this->customAppStorer->store($event, $stored);
        static::assertEmpty($stored);
    }
}
