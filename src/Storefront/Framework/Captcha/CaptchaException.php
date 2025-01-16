<?php declare(strict_types=1);

namespace Cicada\Storefront\Framework\Captcha;

use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\HttpException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Storefront\Framework\Captcha\Exception\CaptchaInvalidException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @codeCoverageIgnore
 */
#[Package('framework')]
class CaptchaException extends HttpException
{
    public const INVALID_CAPTCHA_ERROR = 'FRAMEWORK__INVALID_CAPTCHA_VALUE';

    public static function invalid(AbstractCaptcha $captcha): self
    {
        if (Feature::isActive('v6.7.0.0')) {
            return new self(
                Response::HTTP_FORBIDDEN,
                self::INVALID_CAPTCHA_ERROR,
                'The provided value for captcha "{{ captcha }}" is not valid.',
                [
                    'captcha' => $captcha::class,
                ]
            );
        }

        return new CaptchaInvalidException($captcha);
    }
}
