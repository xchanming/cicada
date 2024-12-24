<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Rule\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('services-settings')]
class UnsupportedValueException extends CicadaHttpException
{
    public function __construct(
        protected string $type,
        protected string $class
    ) {
        parent::__construct(
            'Unsupported value of type {{ type }} in {{ class }}',
            ['type' => $type, 'class' => $class]
        );
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'CONTENT__RULE_VALUE_NOT_SUPPORTED';
    }
}
