<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\CustomEntity\Xml\Config\Fixture;

use Cicada\Core\System\CustomEntity\Xml\Config\ConfigXmlElement;

/**
 * @internal
 */
class TestElement extends ConfigXmlElement
{
    /**
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    public $extensions = [];

    public string $testData = 'TEST_DATA';

    protected static function parse(\DOMElement $element): array
    {
        return [];
    }
}
