<?php declare(strict_types=1);

namespace Cicada\Core\System\CustomEntity\Xml\Config\AdminUi\XmlElements;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\CustomEntity\Xml\Config\ConfigXmlElement;

/**
 * Represents the XML tabs element
 *
 * admin-ui > entity > detail > tabs
 *
 * @internal
 */
#[Package('buyers-experience')]
final class Tabs extends ConfigXmlElement
{
    /**
     * @var list<Tab>
     */
    protected array $content;

    /**
     * @return list<Tab>
     */
    public function getContent(): array
    {
        return $this->content;
    }

    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();

        return $data['content'];
    }

    protected static function parse(\DOMElement $element): array
    {
        $tabs = [];
        foreach ($element->getElementsByTagName('tab') as $tab) {
            $tabs[] = Tab::fromXml($tab);
        }

        return ['content' => $tabs];
    }
}
