<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Translation\Fixtures;

use Cicada\Core\System\Snippet\Files\AbstractSnippetFile;

/**
 * @internal
 */
class UnitTest_SnippetFile extends AbstractSnippetFile
{
    public function getName(): string
    {
        return 'storefront.unitTest';
    }

    public function getPath(): string
    {
        return __DIR__ . '/storefront.unitTest.json';
    }

    public function getIso(): string
    {
        return 'zh-CN';
    }

    public function getAuthor(): string
    {
        return 'unitTest';
    }

    public function isBase(): bool
    {
        return false;
    }

    public function getTechnicalName(): string
    {
        return 'unitFile';
    }
}
