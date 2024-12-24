<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\TestCaseBase;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Test\TestCaseBase\FilesystemBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

/**
 * @internal
 */
class FilesystemBehaviourTest extends TestCase
{
    use FilesystemBehaviour;
    use KernelTestBehaviour;

    public function testWrittenFilesGetDeleted(): void
    {
        $this->getPublicFilesystem()
            ->write('testFile', 'testContent');

        $this->getPublicFilesystem()
            ->write('public/testFile', 'testContent');

        static::assertNotEmpty($this->getPublicFilesystem()->listContents('', true)->toArray());
    }

    #[Depends('testWrittenFilesGetDeleted')]
    public function testFileSystemIsEmptyOnNextTest(): void
    {
        static::assertEmpty($this->getPublicFilesystem()->listContents('', true)->toArray());
    }
}
