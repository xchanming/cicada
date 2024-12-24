<?php declare(strict_types=1);

namespace Cicada\Storefront\Theme\DataAbstractionLayer;

use Cicada\Core\Framework\DataAbstractionLayer\Dbal\ExceptionHandlerInterface;
use Cicada\Core\Framework\Log\Package;
use Cicada\Storefront\Theme\Exception\ThemeException;

/**
 * @internal
 */
#[Package('storefront')]
class ThemeExceptionHandler implements ExceptionHandlerInterface
{
    public function getPriority(): int
    {
        return ExceptionHandlerInterface::PRIORITY_DEFAULT;
    }

    public function matchException(\Exception $e): ?\Exception
    {
        if (preg_match(
            '/SQLSTATE\[23000]: Integrity constraint violation: 1451.*CONSTRAINT `fk.theme_media.media_id` FOREIGN KEY \(`media_id`\) REFERENCES `media` \(`id`\)/',
            $e->getMessage(),
        )) {
            return ThemeException::themeMediaStillInUse();
        }

        return null;
    }
}
