<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DataAbstractionLayer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('core')]
#[CoversClass(EntityCustomFieldsTrait::class)]
class EntityCustomFieldsTraitTest extends TestCase
{
    public function testGetCustomFieldValues(): void
    {
        $entity = new MyTraitEntity('id', ['foo' => 'bar', 'bar' => 'foo', 'baz' => 'baz']);

        static::assertSame(['foo' => 'bar'], $entity->getCustomFieldsValues('foo'));

        static::assertEquals([], $entity->getCustomFieldsValues('not-exists'));

        static::assertSame(['foo' => 'bar', 'bar' => 'foo'], $entity->getCustomFieldsValues('foo', 'bar'));
    }

    public function testGetCustomFieldValue(): void
    {
        $entity = new MyTraitEntity('id', ['foo' => 'bar', 'bar' => 'foo', 'baz' => 'baz']);

        static::assertSame('bar', $entity->getCustomFieldsValue('foo'));

        static::assertNull($entity->getCustomFieldsValue('not-exists'));
    }

    public function testGetCustomFieldsValue(): void
    {
        $entity = new MyTraitEntity('id', ['foo' => 'bar', 'bar' => 'foo', 'baz' => 'baz']);

        static::assertSame(['foo' => 'bar'], $entity->getCustomFieldsValues('foo'));

        static::assertNull($entity->getCustomFieldsValue('not-exists'));

        static::assertSame(['foo' => 'bar', 'bar' => 'foo'], $entity->getCustomFieldsValues('foo', 'bar'));
    }

    public function testChangeCustomFields(): void
    {
        $entity = new MyTraitEntity('id', [
            'foo' => 'bar',
            'bar' => ['foo' => 'bar'],
        ]);

        $entity->changeCustomFields(['foo' => 'baz']);
        static::assertEquals('baz', $entity->getCustomFieldsValue('foo'));
        static::assertEquals(['foo' => 'bar'], $entity->getCustomFieldsValue('bar'));

        $entity->changeCustomFields(['bar' => ['foo' => 'baz']]);
        static::assertEquals(['foo' => 'baz'], $entity->getCustomFieldsValue('bar'));

        $entity->changeCustomFields(['foo' => 'baz', 'bar' => ['foo' => 'foo'], 'baz' => 'baz']);
        static::assertEquals(['foo' => 'baz', 'bar' => ['foo' => 'foo'], 'baz' => 'baz'], $entity->getCustomFields());
    }
}

/**
 * @internal
 */
class MyTraitEntity extends Entity
{
    use EntityCustomFieldsTrait;

    /**
     * @param string $_uniqueIdentifier
     * @param array<string, mixed>|null $customFields
     */
    public function __construct(
        /** @deprecated tag:v6.7.0 - Will be natively typed */
        protected $_uniqueIdentifier,
        /** @deprecated tag:v6.7.0 - Will be natively typed */
        protected $customFields = []
    ) {
    }
}
