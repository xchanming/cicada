<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\LineItem\Group\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class LineItemGroupPackagerNotFoundException extends CicadaHttpException
{
    public function __construct(string $key)
    {
        parent::__construct('Packager "{{ key }}" has not been found!', ['key' => $key]);
    }

    public function getErrorCode(): string
    {
        return 'CHECKOUT__GROUP_PACKAGER_NOT_FOUND';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
