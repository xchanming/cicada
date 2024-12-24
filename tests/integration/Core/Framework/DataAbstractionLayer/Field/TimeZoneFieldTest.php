<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\Field;

use Cicada\Core\Framework\DataAbstractionLayer\Field\TimeZoneField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\TimeZoneFieldSerializer;
use Cicada\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\WriteConstraintViolationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class TimeZoneFieldTest extends TestCase
{
    use KernelTestBehaviour;

    #[DataProvider('validTimeZones')]
    public function testTimeZoneSerializerTest(string $timeZone): void
    {
        $serializer = static::getContainer()->get(TimeZoneFieldSerializer::class);

        $name = 'string_' . Uuid::randomHex();
        $data = new KeyValuePair($name, $timeZone, false);

        $val = $serializer->encode(
            new TimeZoneField($name, $name),
            new EntityExistence(null, [], true, false, false, []),
            $data,
            $this->createMock(WriteParameterBag::class)
        );

        $array = iterator_to_array($val);

        static::assertSame($timeZone, $array[$name]);
    }

    #[DataProvider('inValidTimeZones')]
    public function testInvalidTimeZone(string $timeZone): void
    {
        $serializer = static::getContainer()->get(TimeZoneFieldSerializer::class);

        $name = 'string_' . Uuid::randomHex();
        $data = new KeyValuePair($name, $timeZone, false);

        static::expectException(WriteConstraintViolationException::class);

        iterator_to_array($serializer->encode(
            new TimeZoneField($name, $name),
            new EntityExistence(null, [], true, false, false, []),
            $data,
            $this->createMock(WriteParameterBag::class)
        ));
    }

    public function testNullable(): void
    {
        $serializer = static::getContainer()->get(TimeZoneFieldSerializer::class);

        $name = 'string_' . Uuid::randomHex();
        $data = new KeyValuePair($name, null, false);

        $array = iterator_to_array($serializer->encode(
            new TimeZoneField($name, $name),
            new EntityExistence(null, [], true, false, false, []),
            $data,
            $this->createMock(WriteParameterBag::class)
        ));

        static::assertNull($array[$name]);
    }

    /**
     * @return array<array<string>>
     */
    public static function validTimeZones(): array
    {
        return [
            ['UTC'],
            ['Europe/Berlin'],
        ];
    }

    /**
     * @return array<array<string>>
     */
    public static function inValidTimeZones(): array
    {
        return [
            ['+01:00'],
            ['UTC+1'],
        ];
    }
}
