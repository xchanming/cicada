<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Controller;

use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Storefront\Test\Controller\StorefrontControllerTestBehaviour;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class WellKnownControllerTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontControllerTestBehaviour;

    public function testRedirectFromPasswordResetRoute(): void
    {
        $response = $this->request('GET', '/.well-known/change-password', []);

        static::assertSame(302, $response->getStatusCode());

        $location = $response->headers->get('Location');

        static::assertIsString($location);
        static::assertStringContainsString('account/profile', $location);
        static::assertStringContainsString('profile-password-form', $location);
    }
}
