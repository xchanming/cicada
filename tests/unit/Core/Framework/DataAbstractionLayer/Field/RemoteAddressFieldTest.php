<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DataAbstractionLayer\Field;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\DataAbstractionLayer\Field\RemoteAddressField;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('core')]
#[CoversClass(RemoteAddressField::class)]
class RemoteAddressFieldTest extends TestCase
{
    public function testGetStorageName(): void
    {
        $field = new RemoteAddressField('remote_address', 'remoteAddress');

        static::assertEquals('remote_address', $field->getStorageName());
    }
}
