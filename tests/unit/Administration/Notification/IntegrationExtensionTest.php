<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Administration\Notification;

use Cicada\Administration\Notification\Extension\IntegrationExtension;
use Cicada\Administration\Notification\NotificationDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityProtection\EntityProtectionCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Integration\IntegrationDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('administration')]
#[CoversClass(IntegrationExtension::class)]
class IntegrationExtensionTest extends TestCase
{
    private IntegrationExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new IntegrationExtension();
    }

    public function testExtendFieldsAddsOneAssociation(): void
    {
        $collection = new FieldCollection();

        $this->extension->extendFields($collection);

        static::assertCount(1, $collection);
        $associationField = $collection->first();
        static::assertInstanceOf(OneToManyAssociationField::class, $associationField);
        static::assertSame('created_by_integration_id', $associationField->getReferenceField());
        static::assertSame(NotificationDefinition::class, $associationField->getReferenceClass());
    }

    public function testGetDefinitionClassIsDefined(): void
    {
        static::assertSame(IntegrationDefinition::class, $this->extension->getDefinitionClass());
    }

    public function testExtendProtectionsIsUntouched(): void
    {
        $protections = new EntityProtectionCollection([]);

        $this->extension->extendProtections($protections);
        static::assertCount(0, $protections);
    }
}
