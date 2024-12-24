<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Framework\Captcha\BasicCaptcha;

use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Storefront\Framework\Captcha\BasicCaptcha\BasicCaptchaGenerator;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class BasicCaptchaGeneratorTest extends TestCase
{
    use KernelTestBehaviour;

    private BasicCaptchaGenerator $captcha;

    protected function setUp(): void
    {
        $this->captcha = static::getContainer()->get(BasicCaptchaGenerator::class);
    }

    public function testGetCaptchaImage(): void
    {
        $basicCaptchaImage = $this->captcha->generate();
        static::assertTrue($this->isValid64base($basicCaptchaImage->imageBase64()));
        static::assertNotEmpty($basicCaptchaImage->getCode());
    }

    private function isValid64base(string $string): bool
    {
        $decoded = base64_decode($string, true);

        if (!$decoded) {
            return false;
        }

        return base64_encode($decoded) === $string;
    }
}
