<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Webhook;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Event\FlowEventAware;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\Webhook\_fixtures\BusinessEvents\ArrayBusinessEvent;
use Cicada\Core\Framework\Test\Webhook\_fixtures\BusinessEvents\CollectionBusinessEvent;
use Cicada\Core\Framework\Test\Webhook\_fixtures\BusinessEvents\EntityBusinessEvent;
use Cicada\Core\Framework\Test\Webhook\_fixtures\BusinessEvents\InvalidAvailableDataBusinessEvent;
use Cicada\Core\Framework\Test\Webhook\_fixtures\BusinessEvents\InvalidTypeBusinessEvent;
use Cicada\Core\Framework\Test\Webhook\_fixtures\BusinessEvents\NestedEntityBusinessEvent;
use Cicada\Core\Framework\Test\Webhook\_fixtures\BusinessEvents\ScalarBusinessEvent;
use Cicada\Core\Framework\Test\Webhook\_fixtures\BusinessEvents\StructuredArrayObjectBusinessEvent;
use Cicada\Core\Framework\Test\Webhook\_fixtures\BusinessEvents\StructuredObjectBusinessEvent;
use Cicada\Core\Framework\Test\Webhook\_fixtures\BusinessEvents\UnstructuredObjectBusinessEvent;
use Cicada\Core\Framework\Webhook\BusinessEventEncoder;
use Cicada\Core\System\Tax\TaxCollection;
use Cicada\Core\System\Tax\TaxEntity;

/**
 * @internal
 */
class BusinessEventEncoderTest extends TestCase
{
    use IntegrationTestBehaviour;

    private BusinessEventEncoder $businessEventEncoder;

    protected function setUp(): void
    {
        $this->businessEventEncoder = static::getContainer()->get(BusinessEventEncoder::class);
    }

    #[DataProvider('getEvents')]
    public function testScalarEvents(FlowEventAware $event): void
    {
        $cicadaVersion = static::getContainer()->getParameter('kernel.cicada_version');
        static::assertTrue(
            method_exists($event, 'getEncodeValues'),
            'Event does not have method getEncodeValues'
        );
        static::assertEquals($event->getEncodeValues($cicadaVersion), $this->businessEventEncoder->encode($event));
    }

    public static function getEvents(): \Generator
    {
        $tax = new TaxEntity();
        $tax->setId('tax-id');
        $tax->setName('test');
        $tax->setTaxRate(19);
        $tax->setPosition(1);

        yield 'ScalarBusinessEvent' => [new ScalarBusinessEvent()];
        yield 'StructuredObjectBusinessEvent' => [new StructuredObjectBusinessEvent()];
        yield 'StructuredArrayObjectBusinessEvent' => [new StructuredArrayObjectBusinessEvent()];
        yield 'UnstructuredObjectBusinessEvent' => [new UnstructuredObjectBusinessEvent()];
        yield 'EntityBusinessEvent' => [new EntityBusinessEvent($tax)];
        yield 'CollectionBusinessEvent' => [new CollectionBusinessEvent(new TaxCollection([$tax]))];
        yield 'ArrayBusinessEvent' => [new ArrayBusinessEvent(new TaxCollection([$tax]))];
        yield 'NestedEntityBusinessEvent' => [new NestedEntityBusinessEvent($tax)];
    }

    public function testInvalidType(): void
    {
        static::expectException(\RuntimeException::class);
        $this->businessEventEncoder->encode(new InvalidTypeBusinessEvent());
    }

    public function testInvalidAvailableData(): void
    {
        static::expectException(\RuntimeException::class);
        $this->businessEventEncoder->encode(new InvalidAvailableDataBusinessEvent());
    }
}
