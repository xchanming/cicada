<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\FieldSerializer;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\TranslatedFieldSerializer;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommandQueue;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

/**
 * @internal
 */
class TranslatedFieldSerializerTest extends TestCase
{
    use KernelTestBehaviour;

    protected TranslatedFieldSerializer $serializer;

    protected WriteContext $writeContext;

    protected function setUp(): void
    {
        $serializer = static::getContainer()->get(TranslatedFieldSerializer::class);
        static::assertInstanceOf(TranslatedFieldSerializer::class, $serializer);
        $this->serializer = $serializer;
        $this->writeContext = WriteContext::createFromContext(Context::createDefaultContext());
    }

    public function testNormalizeNullData(): void
    {
        $data = $this->normalize(['description' => null]);

        static::assertEquals([
            'description' => null,
            'translations' => [
                $this->writeContext->getContext()->getLanguageId() => [
                    'description' => null,
                ],
            ],
        ], $data);
    }

    public function testNormalizeStringData(): void
    {
        $data = $this->normalize(['description' => 'abc']);

        static::assertEquals([
            'description' => 'abc',
            'translations' => [
                $this->writeContext->getContext()->getLanguageId() => [
                    'description' => 'abc',
                ],
            ],
        ], $data);
    }

    public function testNormalizeArrayData(): void
    {
        $languageId = $this->writeContext->getContext()->getLanguageId();

        $data = $this->normalize([
            'description' => [
                $languageId => 'abc',
            ],
        ]);

        static::assertEquals([
            'description' => [
                $languageId => 'abc',
            ],
            'translations' => [
                $languageId => [
                    'description' => 'abc',
                ],
            ],
        ], $data);
    }

    /**
     * @param array<string, string|array<string, string>|null> $data
     *
     * @return array<string, string|array<string, string>|null>
     */
    private function normalize(array $data): array
    {
        $field = new TranslatedField('description');
        $bag = new WriteParameterBag(
            static::getContainer()->get(ProductDefinition::class),
            $this->writeContext,
            '',
            new WriteCommandQueue()
        );

        return $this->serializer->normalize($field, $data, $bag);
    }
}
