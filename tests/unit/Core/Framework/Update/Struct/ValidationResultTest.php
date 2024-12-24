<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Update\Struct;

use Cicada\Core\Framework\Update\Struct\ValidationResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ValidationResult::class)]
class ValidationResultTest extends TestCase
{
    public function testCreateResult(): void
    {
        $result = new ValidationResult('name', true, 'message', ['var' => 'value']);
        $vars = $result->getVars();

        static::assertSame('name', $vars['name']);
        static::assertTrue($vars['result']);
        static::assertSame('message', $vars['message']);
        static::assertSame(['var' => 'value'], $vars['vars']);

        static::assertSame('update_api_validation_result', $result->getApiAlias());
    }
}
