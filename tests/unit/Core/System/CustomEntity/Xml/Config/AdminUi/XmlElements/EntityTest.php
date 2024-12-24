<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\CustomEntity\Xml\Config\AdminUi\XmlElements;

use Cicada\Core\System\CustomEntity\Xml\Config\AdminUi\XmlElements\AdminUi;
use Cicada\Core\System\CustomEntity\Xml\Config\AdminUi\XmlElements\Entity as AdminUiEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AdminUiEntity::class)]
class EntityTest extends TestCase
{
    public function testFromXml(): void
    {
        $dom = new \DOMDocument();
        $adminUi = $dom->createElement('adminUi');
        $adminUiEntity = $dom->createElement('entity');

        $adminUiEntity->setAttribute('name', 'AdminUiTest');
        $adminUiEntity->setAttribute('icon', 'triangle');
        $adminUiEntity->setAttribute('color', 'red');
        $adminUiEntity->setAttribute('position', '1');
        $adminUiEntity->setAttribute('navigation-parent', 'test');

        $adminUi->appendChild(
            $adminUiEntity
        );

        $adminUi = AdminUi::fromXml($adminUi);

        $adminUiEntities = $adminUi->getEntities();
        static::assertInstanceOf(AdminUiEntity::class, \array_pop($adminUiEntities));
    }
}
