<?php declare(strict_types=1);

namespace Cicada\Storefront\Framework\Captcha\BasicCaptcha;

use Cicada\Core\Framework\Log\Package;

#[Package('framework')]
abstract class AbstractBasicCaptchaGenerator
{
    abstract public function generate(): BasicCaptchaImage;
}
