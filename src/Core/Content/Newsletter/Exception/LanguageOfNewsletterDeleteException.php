<?php declare(strict_types=1);

namespace Cicada\Core\Content\Newsletter\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('buyers-experience')]
class LanguageOfNewsletterDeleteException extends CicadaHttpException
{
    public function __construct(?\Throwable $e = null)
    {
        parent::__construct('Language is still linked in newsletter recipients', [], $e);
    }

    public function getErrorCode(): string
    {
        return 'CONTENT__LANGUAGE_OF_NEWSLETTER_RECIPIENT_DELETE';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
