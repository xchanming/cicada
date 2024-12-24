<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DataAbstractionLayer\Field;

use Cicada\Core\Framework\DataAbstractionLayer\Field\PasswordField;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(PasswordField::class)]
class PasswordFieldTest extends TestCase
{
    public function testInstantiate(): void
    {
        $field = new PasswordField(
            'custom_password',
            'customPassword',
            \PASSWORD_DEFAULT,
            ['b'],
            PasswordField::FOR_ADMIN
        );

        static::assertSame('custom_password', $field->getStorageName());
        static::assertSame('customPassword', $field->getPropertyName());
        static::assertSame(\PASSWORD_DEFAULT, $field->getAlgorithm());
        static::assertSame(['b'], $field->getHashOptions());
        static::assertSame('admin', $field->getFor());
    }
}
