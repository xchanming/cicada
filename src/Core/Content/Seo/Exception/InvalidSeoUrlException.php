<?php declare(strict_types=1);

namespace Cicada\Core\Content\Seo\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('buyers-experience')]
class InvalidSeoUrlException extends CicadaHttpException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__INVALID_SEO_URL';
    }
}
