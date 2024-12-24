<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Promotion\Cart\Discount\Filter\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('buyers-experience')]
class FilterSorterNotFoundException extends CicadaHttpException
{
    public function __construct(string $key)
    {
        parent::__construct('Sorter "{{ key }}" has not been found!', ['key' => $key]);
    }

    public function getErrorCode(): string
    {
        return 'CHECKOUT__FILTER_SORTER_NOT_FOUND';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
