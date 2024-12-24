<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Cms\Xml;

use Cicada\Core\Framework\App\Manifest\Xml\XmlElement;
use Cicada\Core\Framework\App\Manifest\XmlParserUtils;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('buyers-experience')]
class DefaultConfig extends XmlElement
{
    protected ?string $marginTop = null;

    protected ?string $marginRight = null;

    protected ?string $marginBottom = null;

    protected ?string $marginLeft = null;

    protected ?string $sizingMode = null;

    protected ?string $backgroundColor = null;

    public function getMarginTop(): ?string
    {
        return $this->marginTop;
    }

    public function getMarginRight(): ?string
    {
        return $this->marginRight;
    }

    public function getMarginBottom(): ?string
    {
        return $this->marginBottom;
    }

    public function getMarginLeft(): ?string
    {
        return $this->marginLeft;
    }

    public function getSizingMode(): ?string
    {
        return $this->sizingMode;
    }

    public function getBackgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    protected static function parse(\DOMElement $element): array
    {
        $defaultConfig = [];

        foreach ($element->childNodes as $config) {
            if ($config instanceof \DOMText) {
                continue;
            }

            $defaultConfig[XmlParserUtils::kebabCaseToCamelCase($config->nodeName)] = $config->nodeValue;
        }

        return $defaultConfig;
    }
}
