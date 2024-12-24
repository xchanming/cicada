<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Api\EventListener;

use Cicada\Core\Framework\Api\EventListener\ExpectationSubscriber;
use Cicada\Core\Framework\Api\Exception\ExpectationFailedException;
use Cicada\Core\Framework\Routing\ApiRouteScope;
use Cicada\Core\Kernel;
use Cicada\Core\PlatformRequest;
use Composer\InstalledVersions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @internal
 */
#[CoversClass(ExpectationSubscriber::class)]
class ExpectationSubscriberTest extends TestCase
{
    private ExpectationSubscriber $expectationSubscriber;

    protected function setUp(): void
    {
        $this->expectationSubscriber = new ExpectationSubscriber('6.3.0.0', []);
        InstalledVersions::reload([
            'root' => [
                'name' => 'cicada/production',
                'pretty_version' => '6.3.0.0',
                'version' => '6.3.0.0',
                'reference' => 'foo',
                'type' => 'project',
                'install_path' => __DIR__,
                'aliases' => [],
                'dev' => false,
            ],
            'versions' => [
                'cicada-ag/core' => [
                    'version' => '6.3.0.0',
                    'dev_requirement' => false,
                ],
            ],
        ]);
    }

    public function testExpectFailsOutdatedCicadaVersion(): void
    {
        $request = $this->makeRequest();
        $request->headers->set(PlatformRequest::HEADER_EXPECT_PACKAGES, 'cicada-ag/core:~6.4');

        $event = new ControllerEvent(
            $this->createMock(Kernel::class),
            $this->setUp(...),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        static::expectException(ExpectationFailedException::class);

        $this->expectationSubscriber->checkExpectations($event);
    }

    #[DoesNotPerformAssertions]
    public function testExpectMatchesCicadaVersion(): void
    {
        $request = $this->makeRequest();
        $request->headers->set(PlatformRequest::HEADER_EXPECT_PACKAGES, 'cicada-ag/core:~6.3.0.0');

        $event = new ControllerEvent(
            $this->createMock(Kernel::class),
            $this->setUp(...),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->expectationSubscriber->checkExpectations($event);
    }

    public function testExpectMatchesCicadaVersionButNotPlugin(): void
    {
        $request = $this->makeRequest();
        $request->headers->set(PlatformRequest::HEADER_EXPECT_PACKAGES, 'cicada-ag/core:~6.3.0.0,swag/paypal:*');

        $event = new ControllerEvent(
            $this->createMock(Kernel::class),
            $this->setUp(...),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        static::expectException(ExpectationFailedException::class);

        $this->expectationSubscriber->checkExpectations($event);
    }

    #[DoesNotPerformAssertions]
    public function testExpectMatchesCicadaVersionAndPlugin(): void
    {
        $this->expectationSubscriber = new ExpectationSubscriber('6.3.0.0', [['composerName' => 'swag/paypal', 'active' => true, 'version' => '1.0.0']]);

        $request = $this->makeRequest();
        $request->headers->set(PlatformRequest::HEADER_EXPECT_PACKAGES, 'cicada-ag/core:~6.3.0.0,swag/paypal:*');

        $event = new ControllerEvent(
            $this->createMock(Kernel::class),
            $this->setUp(...),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->expectationSubscriber->checkExpectations($event);
    }

    public function testExpectMatchesCicadaVersionAndPluginIsNotActive(): void
    {
        $this->expectationSubscriber = new ExpectationSubscriber('6.3.0.0', [['composerName' => 'swag/paypal', 'active' => false, 'version' => '1.0.0']]);

        $request = $this->makeRequest();
        $request->headers->set(PlatformRequest::HEADER_EXPECT_PACKAGES, 'cicada-ag/core:~6.3.0.0,swag/paypal:*');

        $event = new ControllerEvent(
            $this->createMock(Kernel::class),
            $this->setUp(...),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        static::expectException(ExpectationFailedException::class);

        $this->expectationSubscriber->checkExpectations($event);
    }

    private function makeRequest(): Request
    {
        $request = new Request();
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, [ApiRouteScope::ID]);

        return $request;
    }
}
