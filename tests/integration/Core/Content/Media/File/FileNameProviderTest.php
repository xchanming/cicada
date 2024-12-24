<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Media\File;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Media\File\FileNameProvider;
use Cicada\Core\Content\Test\Media\MediaFixtures;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;

/**
 * @internal
 */
class FileNameProviderTest extends TestCase
{
    use IntegrationTestBehaviour;
    use MediaFixtures;

    private FileNameProvider $nameProvider;

    private Context $context;

    protected function setUp(): void
    {
        $this->nameProvider = static::getContainer()->get(FileNameProvider::class);

        $this->context = Context::createDefaultContext();

        $this->setFixtureContext($this->context);
    }

    public function testItReturnsOriginalName(): void
    {
        $media = $this->getEmptyMedia();
        $original = 'test';

        $new = $this->nameProvider->provide($original, 'png', $media->getId(), $this->context);

        static::assertSame($original, $new);
    }

    public function testItGeneratesNewName(): void
    {
        $existing = $this->getJpg();

        $existingFileName = $existing->getFileName();
        static::assertIsString($existingFileName);

        $existingFileExtension = $existing->getFileExtension();
        static::assertIsString($existingFileExtension);

        $media = $this->getEmptyMedia();

        $new = $this->nameProvider->provide(
            $existingFileName,
            $existingFileExtension,
            $media->getId(),
            $this->context
        );

        static::assertSame($existingFileName . '_(1)', $new);
    }

    public function testItReturnsOriginalNameOnNewExtension(): void
    {
        $existingFileName = $this->getJpg()->getFileName();
        static::assertIsString($existingFileName);

        $media = $this->getEmptyMedia();

        $new = $this->nameProvider->provide(
            $existingFileName,
            'png',
            $media->getId(),
            $this->context
        );

        static::assertSame($existingFileName, $new);
    }

    public function testItGeneratesNewNameWithoutMediaEntity(): void
    {
        $existing = $this->getJpg();

        $existingFileName = $existing->getFileName();
        static::assertIsString($existingFileName);

        $existingFileExtension = $existing->getFileExtension();
        static::assertIsString($existingFileExtension);

        $new = $this->nameProvider->provide(
            $existingFileName,
            $existingFileExtension,
            null,
            $this->context
        );

        static::assertSame($existingFileName . '_(1)', $new);
    }
}
