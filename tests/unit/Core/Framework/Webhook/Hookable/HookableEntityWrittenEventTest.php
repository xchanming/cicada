<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Webhook\Hookable;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Customer\CustomerDefinition;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Api\Acl\Role\AclRoleDefinition;
use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Webhook\AclPrivilegeCollection;
use Cicada\Core\Framework\Webhook\Hookable\HookableEntityWrittenEvent;

/**
 * @internal
 */
#[CoversClass(HookableEntityWrittenEvent::class)]
class HookableEntityWrittenEventTest extends TestCase
{
    public function testGetter(): void
    {
        $entityId = Uuid::randomHex();
        $event = HookableEntityWrittenEvent::fromWrittenEvent($this->getEntityWrittenEvent($entityId));

        static::assertEquals('product.written', $event->getName());
        static::assertEquals([
            [
                'entity' => 'product',
                'operation' => 'delete',
                'primaryKey' => $entityId,
                'updatedFields' => [],
            ],
        ], $event->getWebhookPayload(new AppEntity()));
    }

    public function testGetterWithVersionId(): void
    {
        $entityId = Uuid::randomHex();
        $event = HookableEntityWrittenEvent::fromWrittenEvent($this->getEntityWrittenEvent(
            $entityId,
            ['versionId' => Defaults::LIVE_VERSION]
        ));

        static::assertEquals('product.written', $event->getName());
        static::assertEquals([
            [
                'entity' => 'product',
                'operation' => 'delete',
                'primaryKey' => $entityId,
                'updatedFields' => ['versionId'],
                'versionId' => Defaults::LIVE_VERSION,
            ],
        ], $event->getWebhookPayload(new AppEntity()));
    }

    public function testIsAllowed(): void
    {
        $entityId = Uuid::randomHex();
        $event = HookableEntityWrittenEvent::fromWrittenEvent($this->getEntityWrittenEvent($entityId));

        $allowedPermissions = new AclPrivilegeCollection([
            ProductDefinition::ENTITY_NAME . ':' . AclRoleDefinition::PRIVILEGE_READ,
        ]);
        static::assertTrue($event->isAllowed(
            Uuid::randomHex(),
            $allowedPermissions
        ));

        $notAllowedPermissions = new AclPrivilegeCollection([
            CustomerDefinition::ENTITY_NAME . ':' . AclRoleDefinition::PRIVILEGE_READ,
        ]);
        static::assertFalse($event->isAllowed(
            Uuid::randomHex(),
            $notAllowedPermissions
        ));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function getEntityWrittenEvent(string $entityId, array $payload = []): EntityWrittenEvent
    {
        $context = Context::createDefaultContext();

        return new EntityWrittenEvent(
            ProductDefinition::ENTITY_NAME,
            [
                new EntityWriteResult(
                    $entityId,
                    $payload,
                    ProductDefinition::ENTITY_NAME,
                    EntityWriteResult::OPERATION_DELETE,
                    null,
                    null
                ),
            ],
            $context
        );
    }
}
