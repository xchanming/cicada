<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\Snippet;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Snippet\Files\SnippetFileCollection;
use Cicada\Core\System\Snippet\SnippetFileHandler;
use Cicada\Core\System\Snippet\SnippetValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(SnippetValidator::class)]
class SnippetValidatorTest extends TestCase
{
    public function testValidateShouldFindMissingSnippets(): void
    {
        $snippetFileHandler = $this->getMockBuilder(SnippetFileHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $firstPath = 'irrelevant.zh-CN.json';
        $secondPath = 'irrelevant.en-GB.json';
        $snippetFileHandler->method('findAdministrationSnippetFiles')
            ->willReturn([$firstPath]);
        $snippetFileHandler->method('findStorefrontSnippetFiles')
            ->willReturn([$secondPath]);

        $snippetFileHandler->method('openJsonFile')
            ->willReturnCallback(function ($path) use ($firstPath) {
                if ($path === $firstPath) {
                    return ['german' => 'exampleGerman'];
                }

                return ['english' => 'exampleEnglish'];
            });

        $snippetValidator = new SnippetValidator(new SnippetFileCollection(), $snippetFileHandler, '');
        $missingSnippets = $snippetValidator->validate();

        static::assertCount(2, $missingSnippets);
        static::assertArrayHasKey('german', $missingSnippets['en-GB']);
        static::assertSame('german', $missingSnippets['en-GB']['german']['keyPath']);
        static::assertSame('exampleGerman', $missingSnippets['en-GB']['german']['availableValue']);

        static::assertArrayHasKey('english', $missingSnippets['zh-CN']);
        static::assertSame('english', $missingSnippets['zh-CN']['english']['keyPath']);
        static::assertSame('exampleEnglish', $missingSnippets['zh-CN']['english']['availableValue']);
    }

    public function testValidateShouldNotFindAnyMissingSnippets(): void
    {
        $snippetFileHandler = $this->getMockBuilder(SnippetFileHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $firstPath = 'irrelevant.zh-CN.json';
        $secondPath = 'irrelevant.en-GB.json';
        $snippetFileHandler->method('findAdministrationSnippetFiles')
            ->willReturn([$firstPath]);
        $snippetFileHandler->method('findStorefrontSnippetFiles')
            ->willReturn([$secondPath]);

        $snippetFileHandler->method('openJsonFile')
            ->willReturnCallback(fn () => ['foo' => 'bar']);

        $snippetValidator = new SnippetValidator(new SnippetFileCollection(), $snippetFileHandler, '');
        $missingSnippets = $snippetValidator->validate();

        static::assertCount(0, $missingSnippets);
    }
}
