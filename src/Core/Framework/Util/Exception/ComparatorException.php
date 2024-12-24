<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Util\Exception;

use Cicada\Core\Framework\HttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('services-settings')]
class ComparatorException extends HttpException
{
    public const OPERATOR_NOT_SUPPORTED = 'CONTENT__OPERATOR_NOT_SUPPORTED';

    public static function operatorNotSupported(string $operator): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::OPERATOR_NOT_SUPPORTED,
            'Operator "{{ operator }}" is not supported.',
            ['operator' => $operator]
        );
    }
}
