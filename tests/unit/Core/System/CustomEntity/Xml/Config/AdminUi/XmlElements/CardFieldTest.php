<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\CustomEntity\Xml\Config\AdminUi\XmlElements;

use Cicada\Core\System\CustomEntity\Xml\Config\AdminUi\XmlElements\CardField;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CardField::class)]
class CardFieldTest extends TestCase
{
    public function testFromXml(): void
    {
        $dom = new \DOMDocument();
        $cardFieldElement = $dom->createElement('field');
        $cardFieldElement->setAttribute('ref', 'cardField ref');

        $cardField = CardField::fromXml($cardFieldElement);
        static::assertSame('cardField ref', $cardField->getRef());
    }
}
