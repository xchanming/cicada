<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Controller\Api;

use Cicada\Storefront\Controller\Api\CaptchaController;
use Cicada\Storefront\Framework\Captcha\AbstractCaptcha;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CaptchaController::class)]
class CaptchaControllerTest extends TestCase
{
    private const CAPTCHA_NAME = 'lorem-ipsum';

    private CaptchaController $captchaController;

    protected function setUp(): void
    {
        $captchaMock = static::getMockBuilder(AbstractCaptcha::class)->getMock();
        $captchaMock->method('getName')->willReturn(self::CAPTCHA_NAME);

        $this->captchaController = new CaptchaController([$captchaMock]);
    }

    public function testList(): void
    {
        $expected = json_encode([
            self::CAPTCHA_NAME,
        ]);

        static::assertIsString($expected);

        $response = $this->captchaController->list();

        static::assertJsonStringEqualsJsonString($expected, $response->getContent() ?: '');
    }
}
