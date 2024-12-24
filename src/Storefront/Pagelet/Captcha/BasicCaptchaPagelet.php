<?php declare(strict_types=1);

namespace Cicada\Storefront\Pagelet\Captcha;

use Cicada\Core\Framework\Log\Package;
use Cicada\Storefront\Framework\Captcha\BasicCaptcha\BasicCaptchaImage;
use Cicada\Storefront\Pagelet\Pagelet;

#[Package('storefront')]
class BasicCaptchaPagelet extends Pagelet
{
    protected BasicCaptchaImage $captcha;

    public function setCaptcha(BasicCaptchaImage $captcha): void
    {
        $this->captcha = $captcha;
    }

    public function getCaptcha(): BasicCaptchaImage
    {
        return $this->captcha;
    }
}
