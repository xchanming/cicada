<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\CustomEntity\Xml\Config\AdminUi\XmlElements;

use Cicada\Core\System\CustomEntity\Xml\Config\AdminUi\XmlElements\AdminUi;
use Cicada\Core\System\CustomEntity\Xml\Config\AdminUi\XmlElements\Entity as AdminUiEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AdminUi::class)]
class AdminUiTest extends TestCase
{
    public function testFromXml(): void
    {
        $dom = new \DOMDocument();
        $adminUiElement = $dom->createElement('adminUi');
        $adminUiEntityElement = $dom->createElement('entity');

        $adminUiEntityElement->setAttribute('name', 'AdminUiTest');
        $adminUiEntityElement->setAttribute('icon', 'triangle');
        $adminUiEntityElement->setAttribute('color', 'red');
        $adminUiEntityElement->setAttribute('position', '1');
        $adminUiEntityElement->setAttribute('navigation-parent', 'test');

        $adminUiElement->appendChild(
            $adminUiEntityElement
        );

        $adminUi = AdminUi::fromXml($adminUiElement);

        $adminUiEntities = $adminUi->getEntities();
        static::assertInstanceOf(AdminUiEntity::class, \array_pop($adminUiEntities));
    }
}
