<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Manifest\Xml\Administration;

use Cicada\Core\Framework\App\Manifest\Xml\XmlElement;
use Cicada\Core\Framework\App\Manifest\XmlParserUtils;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
class MainModule extends XmlElement
{
    protected string $source;

    public function getSource(): string
    {
        return $this->source;
    }

    protected static function parse(\DOMElement $element): array
    {
        return XmlParserUtils::parseAttributes($element);
    }
}
