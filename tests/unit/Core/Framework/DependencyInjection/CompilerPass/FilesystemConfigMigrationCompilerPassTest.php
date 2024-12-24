<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DependencyInjection\CompilerPass;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\DependencyInjection\CompilerPass\FilesystemConfigMigrationCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
#[CoversClass(FilesystemConfigMigrationCompilerPass::class)]
class FilesystemConfigMigrationCompilerPassTest extends TestCase
{
    private ContainerBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new ContainerBuilder();
        $this->builder->addCompilerPass(new FilesystemConfigMigrationCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $this->builder->setParameter('cicada.filesystem.public', []);
        $this->builder->setParameter('cicada.filesystem.public.type', 'local');
        $this->builder->setParameter('cicada.filesystem.public.config', []);
        $this->builder->setParameter('cicada.cdn.url', 'http://test.de');
    }

    public function testConfigMigration(): void
    {
        $this->builder->compile(false);

        static::assertSame($this->builder->getParameter('cicada.filesystem.public'), $this->builder->getParameter('cicada.filesystem.theme'));
        static::assertSame($this->builder->getParameter('cicada.filesystem.public'), $this->builder->getParameter('cicada.filesystem.asset'));
        static::assertSame($this->builder->getParameter('cicada.filesystem.public'), $this->builder->getParameter('cicada.filesystem.sitemap'));

        static::assertSame($this->builder->getParameter('cicada.filesystem.public.type'), $this->builder->getParameter('cicada.filesystem.theme.type'));
        static::assertSame($this->builder->getParameter('cicada.filesystem.public.type'), $this->builder->getParameter('cicada.filesystem.asset.type'));
        static::assertSame($this->builder->getParameter('cicada.filesystem.public.type'), $this->builder->getParameter('cicada.filesystem.sitemap.type'));

        static::assertSame($this->builder->getParameter('cicada.filesystem.public.config'), $this->builder->getParameter('cicada.filesystem.theme.config'));
        static::assertSame($this->builder->getParameter('cicada.filesystem.public.config'), $this->builder->getParameter('cicada.filesystem.asset.config'));
        static::assertSame($this->builder->getParameter('cicada.filesystem.public.config'), $this->builder->getParameter('cicada.filesystem.sitemap.config'));

        // We cannot inherit them, cause they use always in 6.2 the shop url instead the configured one
        static::assertSame('', $this->builder->getParameter('cicada.filesystem.theme.url'));
        static::assertSame('', $this->builder->getParameter('cicada.filesystem.asset.url'));
        static::assertSame('', $this->builder->getParameter('cicada.filesystem.sitemap.url'));
    }

    public function testSetCustomConfigForTheme(): void
    {
        $this->builder->setParameter('cicada.filesystem.theme', ['foo' => 'foo']);
        $this->builder->setParameter('cicada.filesystem.theme.type', 'amazon-s3');
        $this->builder->setParameter('cicada.filesystem.theme.config', ['test' => 'test']);
        $this->builder->setParameter('cicada.filesystem.theme.url', 'http://cdn.de');

        $this->builder->compile(false);

        static::assertNotSame($this->builder->getParameter('cicada.filesystem.public'), $this->builder->getParameter('cicada.filesystem.theme'));
        static::assertNotSame($this->builder->getParameter('cicada.filesystem.public.type'), $this->builder->getParameter('cicada.filesystem.theme.type'));
        static::assertNotSame($this->builder->getParameter('cicada.filesystem.public.config'), $this->builder->getParameter('cicada.filesystem.theme.config'));

        static::assertSame('amazon-s3', $this->builder->getParameter('cicada.filesystem.theme.type'));
        static::assertSame('http://cdn.de', $this->builder->getParameter('cicada.filesystem.theme.url'));
        static::assertSame(['test' => 'test'], $this->builder->getParameter('cicada.filesystem.theme.config'));

        static::assertSame($this->builder->getParameter('cicada.filesystem.public'), $this->builder->getParameter('cicada.filesystem.asset'));
        static::assertSame($this->builder->getParameter('cicada.filesystem.public.type'), $this->builder->getParameter('cicada.filesystem.asset.type'));
        static::assertSame($this->builder->getParameter('cicada.filesystem.public.config'), $this->builder->getParameter('cicada.filesystem.asset.config'));

        static::assertSame($this->builder->getParameter('cicada.filesystem.public'), $this->builder->getParameter('cicada.filesystem.sitemap'));
        static::assertSame($this->builder->getParameter('cicada.filesystem.public.type'), $this->builder->getParameter('cicada.filesystem.sitemap.type'));
        static::assertSame($this->builder->getParameter('cicada.filesystem.public.config'), $this->builder->getParameter('cicada.filesystem.sitemap.config'));
    }
}
