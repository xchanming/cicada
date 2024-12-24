<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Flow\Action\Xml;

use Cicada\Core\Framework\App\Manifest\Xml\XmlElement;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('core')]
class Actions extends XmlElement
{
    /**
     * @var list<Action>
     */
    protected array $actions;

    /**
     * @return list<Action>
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    protected static function parse(\DOMElement $element): array
    {
        $actions = [];
        foreach ($element->getElementsByTagName('flow-action') as $flowAction) {
            $actions[] = Action::fromXml($flowAction);
        }

        return ['actions' => $actions];
    }
}
