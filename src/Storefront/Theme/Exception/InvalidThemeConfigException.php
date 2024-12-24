<?php declare(strict_types=1);

namespace Cicada\Storefront\Theme\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('storefront')]
class InvalidThemeConfigException extends CicadaHttpException
{
    public function __construct(string $fieldName)
    {
        parent::__construct('Unable to find setter for config field "{{ fieldName }}"', ['fieldName' => $fieldName]);
    }

    public function getErrorCode(): string
    {
        return 'THEME__INVALID_THEME_CONFIG';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
