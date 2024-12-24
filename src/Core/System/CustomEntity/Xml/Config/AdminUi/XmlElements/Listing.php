<?php declare(strict_types=1);

namespace Cicada\Core\System\CustomEntity\Xml\Config\AdminUi\XmlElements;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\CustomEntity\Xml\Config\ConfigXmlElement;

/**
 * Represents the XML listing element
 *
 * admin-ui > entity > listing
 *
 * @internal
 */
#[Package('buyers-experience')]
final class Listing extends ConfigXmlElement
{
    protected Columns $columns;

    public function getColumns(): Columns
    {
        return $this->columns;
    }

    protected static function parse(\DOMElement $element): array
    {
        return ['columns' => Columns::fromXml($element)];
    }
}
