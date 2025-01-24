<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Asset;

use Cicada\Core\DevOps\Environment\EnvironmentHelper;
use Cicada\Core\Framework\Adapter\Asset\FallbackUrlPackage;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\EnvTestBehaviour;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
#[Package('framework')]
#[CoversClass(FallbackUrlPackage::class)]
class FallbackUrlPackageTest extends TestCase
{
    use EnvTestBehaviour;

    public function testCliFallbacksToAppUrl(): void
    {
        $url = $this->createPackage()->getUrl('test');

        static::assertSame(EnvironmentHelper::getVariable('APP_URL') . '/test', $url);
    }

    public function testCliUrlGiven(): void
    {
        $url = $this->createPackage('https://xchanming.com')->getUrl('test');

        static::assertSame('https://xchanming.com/test', $url);
    }

    public function testWebFallbackToRequest(): void
    {
        $this->setEnvVars(['HTTP_HOST' => 'test.de']);
        $url = $this->createPackage()->getUrl('test');

        static::assertSame('http://test.de/test', $url);
    }

    public function testGetFromRequestStack(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('https://test.de'));

        $url = $this->createPackage(requestStack: $requestStack)->getUrl('test');

        static::assertSame('https://test.de/test', $url);
    }

    private function createPackage(string $url = '', ?RequestStack $requestStack = null): FallbackUrlPackage
    {
        return new FallbackUrlPackage([$url], new EmptyVersionStrategy(), $requestStack);
    }
}
