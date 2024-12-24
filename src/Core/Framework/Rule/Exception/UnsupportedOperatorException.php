<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Rule\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('services-settings')]
class UnsupportedOperatorException extends CicadaHttpException
{
    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $operator;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $class;

    public function __construct(
        string $operator,
        string $class
    ) {
        $this->operator = $operator;
        $this->class = $class;

        parent::__construct(
            'Unsupported operator {{ operator }} in {{ class }}',
            ['operator' => $operator, 'class' => $class]
        );
    }

    public function getOperator(): string
    {
        return $this->operator;
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
        return 'CONTENT__RULE_OPERATOR_NOT_SUPPORTED';
    }
}
