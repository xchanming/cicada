<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Rule;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\RuleConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(RuleConfig::class)]
#[Group('rules')]
class RuleConfigTest extends TestCase
{
    public function testNonExistentFieldReturnsNull(): void
    {
        $ruleConfig = new RuleConfig();

        static::assertNull($ruleConfig->getField('nonExistent'));
    }

    public function testFieldIsReturned(): void
    {
        $ruleConfig = new RuleConfig();

        $ruleConfig->field('foo', 'int', []);

        $field = $ruleConfig->getField('foo');

        static::assertNotNull($field);
        static::assertEquals('foo', $field['name']);
        static::assertEquals('int', $field['type']);
    }

    public function testFieldIsOverwritten(): void
    {
        $ruleConfig = new RuleConfig();

        $ruleConfig->field('foo', 'int', []);
        $ruleConfig->field('foo', 'string', []);

        $field = $ruleConfig->getField('foo');

        static::assertNotNull($field);
        static::assertEquals('foo', $field['name']);
        static::assertEquals('string', $field['type']);
    }

    public function testNumberFieldDefaultDigits(): void
    {
        $ruleConfig = new RuleConfig();

        $ruleConfig->numberField('foo', []);

        $field = $ruleConfig->getField('foo');

        static::assertNotNull($field);
        static::assertEquals('foo', $field['name']);
        static::assertEquals('float', $field['type']);
        static::assertEquals(RuleConfig::DEFAULT_DIGITS, $field['config']['digits']);
    }

    public function testNotOverrideNumberFieldDigits(): void
    {
        $ruleConfig = new RuleConfig();

        $ruleConfig->numberField('foo', [
            'digits' => 5,
        ]);

        $field = $ruleConfig->getField('foo');

        static::assertNotNull($field);
        static::assertEquals('foo', $field['name']);
        static::assertEquals('float', $field['type']);
        static::assertEquals(5, $field['config']['digits']);
    }
}
