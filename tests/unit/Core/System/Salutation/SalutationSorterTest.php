<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\Salutation;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Salutation\SalutationCollection;
use Cicada\Core\System\Salutation\SalutationEntity;
use Cicada\Core\System\Salutation\SalutationSorter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(SalutationSorter::class)]
class SalutationSorterTest extends TestCase
{
    public function testSort(): void
    {
        $mrs = new SalutationEntity();
        $mrs->setId(Uuid::randomBytes());
        $mrs->setSalutationKey('mrs');

        $mr = new SalutationEntity();
        $mr->setId(Uuid::randomBytes());
        $mr->setSalutationKey('mr');

        $notSpecified = new SalutationEntity();
        $notSpecified->setId(Uuid::randomBytes());
        $notSpecified->setSalutationKey('not_specified');

        $test = new SalutationEntity();
        $test->setId(Uuid::randomHex());
        $test->setSalutationKey('test');

        $salutations = new SalutationCollection();
        $salutations->add($mr);
        $salutations->add($mrs);
        $salutations->add($notSpecified);
        $salutations->add($test);

        static::assertSame($salutations->first(), $mr);

        $sorter = new SalutationSorter();
        $salutations = $sorter->sort($salutations);

        static::assertSame($salutations->first(), $notSpecified);
    }
}
