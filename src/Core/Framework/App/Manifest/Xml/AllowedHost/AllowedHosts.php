<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Manifest\Xml\AllowedHost;

use Cicada\Core\Framework\App\Manifest\Xml\XmlElement;
use Cicada\Core\Framework\App\Manifest\XmlParserUtils;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
class AllowedHosts extends XmlElement
{
    /**
     * @var list<string>
     */
    protected array $allowedHosts;

    /**
     * @return list<string>
     */
    public function getHosts(): array
    {
        return $this->allowedHosts;
    }

    protected static function parse(\DOMElement $element): array
    {
        return ['allowedHosts' => XmlParserUtils::parseChildrenAsList($element)];
    }
}
