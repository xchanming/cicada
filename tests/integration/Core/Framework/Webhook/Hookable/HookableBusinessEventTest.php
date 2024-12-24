<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Webhook\Hookable;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Framework\Api\Acl\Role\AclRoleDefinition;
use Cicada\Core\Framework\Event\FlowEventAware;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\Webhook\_fixtures\BusinessEvents\ArrayBusinessEvent;
use Cicada\Core\Framework\Test\Webhook\_fixtures\BusinessEvents\CollectionBusinessEvent;
use Cicada\Core\Framework\Test\Webhook\_fixtures\BusinessEvents\EntityBusinessEvent;
use Cicada\Core\Framework\Test\Webhook\_fixtures\BusinessEvents\NestedEntityBusinessEvent;
use Cicada\Core\Framework\Test\Webhook\_fixtures\BusinessEvents\ScalarBusinessEvent;
use Cicada\Core\Framework\Test\Webhook\_fixtures\BusinessEvents\StructuredArrayObjectBusinessEvent;
use Cicada\Core\Framework\Test\Webhook\_fixtures\BusinessEvents\StructuredObjectBusinessEvent;
use Cicada\Core\Framework\Test\Webhook\_fixtures\BusinessEvents\UnstructuredObjectBusinessEvent;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Webhook\AclPrivilegeCollection;
use Cicada\Core\Framework\Webhook\BusinessEventEncoder;
use Cicada\Core\Framework\Webhook\Hookable\HookableBusinessEvent;
use Cicada\Core\System\Tax\TaxCollection;
use Cicada\Core\System\Tax\TaxDefinition;
use Cicada\Core\System\Tax\TaxEntity;

/**
 * @internal
 */
class HookableBusinessEventTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testGetter(): void
    {
        $scalarEvent = new ScalarBusinessEvent();
        $event = HookableBusinessEvent::fromBusinessEvent(
            $scalarEvent,
            static::getContainer()->get(BusinessEventEncoder::class)
        );

        static::assertEquals($scalarEvent->getName(), $event->getName());
        $cicadaVersion = static::getContainer()->getParameter('kernel.cicada_version');
        static::assertEquals($scalarEvent->getEncodeValues($cicadaVersion), $event->getWebhookPayload());
    }

    #[DataProvider('getEventsWithoutPermissions')]
    public function testIsAllowedForNonEntityBasedEvents(FlowEventAware $rootEvent): void
    {
        $event = HookableBusinessEvent::fromBusinessEvent(
            $rootEvent,
            static::getContainer()->get(BusinessEventEncoder::class)
        );

        static::assertTrue($event->isAllowed(Uuid::randomHex(), new AclPrivilegeCollection([])));
    }

    #[DataProvider('getEventsWithPermissions')]
    public function testIsAllowedForEntityBasedEvents(FlowEventAware $rootEvent): void
    {
        $event = HookableBusinessEvent::fromBusinessEvent(
            $rootEvent,
            static::getContainer()->get(BusinessEventEncoder::class)
        );

        $allowedPermissions = new AclPrivilegeCollection([
            TaxDefinition::ENTITY_NAME . ':' . AclRoleDefinition::PRIVILEGE_READ,
        ]);
        static::assertTrue($event->isAllowed(Uuid::randomHex(), $allowedPermissions));

        $notAllowedPermissions = new AclPrivilegeCollection([
            ProductDefinition::ENTITY_NAME . ':' . AclRoleDefinition::PRIVILEGE_READ,
        ]);
        static::assertFalse($event->isAllowed(Uuid::randomHex(), $notAllowedPermissions));
    }

    /**
     * @return array<array{0: FlowEventAware}>
     */
    public static function getEventsWithoutPermissions(): array
    {
        return [
            [new ScalarBusinessEvent()],
            [new StructuredObjectBusinessEvent()],
            [new StructuredArrayObjectBusinessEvent()],
            [new UnstructuredObjectBusinessEvent()],
        ];
    }

    /**
     * @return array<array{0: FlowEventAware}>
     */
    public static function getEventsWithPermissions(): array
    {
        $tax = new TaxEntity();
        $tax->setId('tax-id');
        $tax->setName('test');
        $tax->setTaxRate(19);
        $tax->setPosition(1);

        return [
            [new EntityBusinessEvent($tax)],
            [new CollectionBusinessEvent(new TaxCollection([$tax]))],
            [new ArrayBusinessEvent(new TaxCollection([$tax]))],
            [new NestedEntityBusinessEvent($tax)],
        ];
    }
}
