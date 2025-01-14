<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\CustomEntity\Xml\Config\AdminUi\XmlElements;

use Cicada\Core\System\CustomEntity\Xml\Config\AdminUi\XmlElements\Column;
use Cicada\Core\System\CustomEntity\Xml\Config\AdminUi\XmlElements\Columns;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Columns::class)]
class ColumnsTest extends TestCase
{
    public function testFromXml(): void
    {
        $dom = new \DOMDocument();
        $columnsElement = $dom->createElement('columns');
        $columnElement = $dom->createElement('column');

        $columnsElement->appendChild(
            $columnElement
        );

        $columns = Columns::fromXml($columnsElement);
        $columnsList = $columns->getContent();
        static::assertInstanceOf(Column::class, \array_pop($columnsList));
    }

    public function testJsonSerialize(): void
    {
        $dom = new \DOMDocument();
        $columnsElement = $dom->createElement('columns');
        $columnElement0 = $dom->createElement('column');
        $columnElement1 = $dom->createElement('column');

        $columnsElement->appendChild(
            $columnElement0
        );
        $columnsElement->appendChild(
            $columnElement1
        );

        $columns = Columns::fromXml($columnsElement);

        $serializedColumns = $columns->jsonSerialize();

        static::assertEquals(
            [
                0 => Column::fromXml($columnElement0),
                1 => Column::fromXml($columnElement1),
            ],
            $serializedColumns
        );
    }
}
