<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Cms\DataResolver;

use Cicada\Core\Content\Cms\CmsException;
use Cicada\Core\Content\Cms\DataResolver\FieldConfig;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('buyers-experience')]
#[CoversClass(FieldConfig::class)]
class FieldConfigTest extends TestCase
{
    public function testFieldConfig(): void
    {
        $config = new FieldConfig('my-config', 'static', ['some-value']);

        static::assertSame('my-config', $config->getName());
        static::assertSame('static', $config->getSource());
        static::assertSame(['some-value'], $config->getValue());
        static::assertSame(['some-value'], $config->getArrayValue());
        static::assertTrue($config->getBoolValue());
        static::assertTrue($config->isStatic());
        static::assertFalse($config->isMapped());
        static::assertFalse($config->isDefault());
        static::assertFalse($config->isProductStream());
        static::assertSame('cms_data_resolver_field_config', $config->getApiAlias());
    }

    public function testFieldConfigCastsTheValues(): void
    {
        $config = new FieldConfig('my-config', 'static', '3');
        static::assertSame(3, $config->getIntValue());
        static::assertSame('3', $config->getStringValue());
        static::assertSame(3.0, $config->getFloatValue());
    }

    public function testThrowExceptionOnGetArrayValue(): void
    {
        $this->expectException(CmsException::class);
        $this->expectExceptionMessage('Expected to load value of "my-config" with type "array", but value with type "string" given.');
        (new FieldConfig('my-config', 'static', 'some-value'))->getArrayValue();
    }

    public function testThrowExceptionOnGetIntValue(): void
    {
        $this->expectException(CmsException::class);
        $this->expectExceptionMessage('Expected to load value of "my-config" with type "int", but value with type "array" given.');
        (new FieldConfig('my-config', 'static', ['some-value']))->getIntValue();
    }

    public function testThrowExceptionOnGetFloatValue(): void
    {
        $this->expectException(CmsException::class);
        $this->expectExceptionMessage('Expected to load value of "my-config" with type "float", but value with type "array" given.');
        (new FieldConfig('my-config', 'static', ['some-value']))->getFloatValue();
    }

    public function testThrowExceptionOnGetStringValue(): void
    {
        $this->expectException(CmsException::class);
        $this->expectExceptionMessage('Expected to load value of "my-config" with type "string", but value with type "array" given.');
        (new FieldConfig('my-config', 'static', ['some-value']))->getStringValue();
    }
}
