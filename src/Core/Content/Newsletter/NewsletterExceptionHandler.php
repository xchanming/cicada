<?php declare(strict_types=1);

namespace Cicada\Core\Content\Newsletter;

use Cicada\Core\Content\Newsletter\Exception\LanguageOfNewsletterDeleteException;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\ExceptionHandlerInterface;
use Cicada\Core\Framework\Log\Package;

#[Package('buyers-experience')]
class NewsletterExceptionHandler implements ExceptionHandlerInterface
{
    public function getPriority(): int
    {
        return ExceptionHandlerInterface::PRIORITY_DEFAULT;
    }

    public function matchException(\Exception $e): ?\Exception
    {
        if (preg_match('/SQLSTATE\[23000\]:.*1451.*a foreign key constraint.*newsletter_recipient.*CONSTRAINT `fk.newsletter_recipient.language_id`/', $e->getMessage())) {
            return new LanguageOfNewsletterDeleteException($e);
        }

        return null;
    }
}
