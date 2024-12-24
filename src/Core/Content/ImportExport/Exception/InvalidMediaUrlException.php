<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('services-settings')]
class InvalidMediaUrlException extends CicadaHttpException
{
    public function __construct(?string $url)
    {
        parent::__construct('Invalid media url: {{ url }}', ['url' => $url ?? 'null']);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'CONTENT__IMPORT_EXPORT_MEDIA_INVALID_URL';
    }
}
