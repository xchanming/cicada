<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\CustomEntity\Xml\Config;

use Cicada\Core\System\CustomEntity\Xml\Config\ConfigXmlElement;
use Cicada\Tests\Unit\Core\System\CustomEntity\Xml\Config\Fixture\TestElement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ConfigXmlElement::class)]
class ConfigXmlElementTest extends TestCase
{
    public function testJsonSerialize(): void
    {
        $extendedConfigXmlElement = TestElement::fromArray([]);

        $serializeResult = $extendedConfigXmlElement->jsonSerialize();
        static::assertSame(['testData' => 'TEST_DATA'], $serializeResult);

        static::assertSame([], $extendedConfigXmlElement->extensions);
        static::assertSame('TEST_DATA', $extendedConfigXmlElement->testData);
    }
}
