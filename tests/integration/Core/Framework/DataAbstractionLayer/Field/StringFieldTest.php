<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\Field;

use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\AllowEmptyString;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\AllowHtml;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Flag;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\StringFieldSerializer;
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
class StringFieldTest extends TestCase
{
    use KernelTestBehaviour;

    /**
     * @param list<Flag> $flags
     */
    #[DataProvider('stringFieldDataProvider')]
    public function testStringFieldSerializer(string $type, bool|string|null $input, ?string $expected, array $flags = []): void
    {
        $serializer = static::getContainer()->get(StringFieldSerializer::class);

        $name = 'string_' . Uuid::randomHex();
        $data = new KeyValuePair($name, $input, false);

        if ($type === 'writeException') {
            $this->expectException(WriteConstraintViolationException::class);

            try {
                $serializer->encode(
                    $this->getStringField($name, $flags),
                    EntityExistence::createEmpty(),
                    $data,
                    $this->getWriteParameterBagMock()
                )->current();
            } catch (WriteConstraintViolationException $e) {
                static::assertSame('/' . $name, $e->getViolations()->get(0)->getPropertyPath());
                /* Unexpected language has to be fixed NEXT-9419 */
                // static::assertSame($expected, $e->getViolations()->get(0)->getMessage());

                throw $e;
            }
        }

        if ($type === 'assertion') {
            static::assertSame(
                $expected,
                $serializer->encode(
                    $this->getStringField($name, $flags),
                    EntityExistence::createEmpty(),
                    $data,
                    $this->getWriteParameterBagMock()
                )->current()
            );
        }
    }

    /**
     * @return list<array{string, bool|string|null, ?string, list<Flag>}>
     */
    public static function stringFieldDataProvider(): array
    {
        return [
            ['writeException', '<test>', 'This value should not be blank.', [new Required()]],
            ['writeException', null, 'This value should not be blank.', [new Required()]],
            ['writeException', '', 'This value should not be blank.', [new Required()]],
            ['writeException', true, 'This value should be of type string.', [new Required()]],
            ['assertion', 'test12-B', 'test12-B', [new Required()]],
            ['assertion', null, null, []],
            ['assertion', '<test>', '<test>', [new Required(), new AllowHtml(false)]],
            ['assertion', '', null, []],
            ['assertion', '', '', [new AllowEmptyString()]],
            ['assertion', '', '', [new Required(), new AllowEmptyString()]],
            ['assertion', '<script></script>test12-B', 'test12-B', [new Required(), new AllowHtml()]],
        ];
    }

    private function getWriteParameterBagMock(): WriteParameterBag
    {
        $mockBuilder = $this->getMockBuilder(WriteParameterBag::class);
        $mockBuilder->disableOriginalConstructor();

        return $mockBuilder->getMock();
    }

    /**
     * @param list<Flag> $flags
     */
    private function getStringField(string $name, array $flags = []): StringField
    {
        $field = new StringField($name, $name);

        if ($flags) {
            $field->addFlags(new ApiAware(), ...$flags);
        }

        return $field;
    }
}
