<?php declare(strict_types=1);

namespace Cicada\Core\Content\MailTemplate\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('after-sales')]
class MailEventConfigurationException extends CicadaHttpException
{
    public function __construct(
        string $message,
        string $eventClass
    ) {
        parent::__construct(
            'Failed processing the mail event: {{ errorMessage }}. {{ eventClass }}',
            [
                'errorMessage' => $message,
                'eventClass' => $eventClass,
            ]
        );
    }

    public function getErrorCode(): string
    {
        return 'CONTENT__MAIL_INVALID_EVENT_CONFIGURATION';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
