<?php declare(strict_types=1);

namespace Cicada\Storefront\Framework\Routing\Exception;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CicadaEvent;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Request;

#[Package('storefront')]
class ErrorRedirectRequestEvent implements CicadaEvent
{
    public function __construct(
        private readonly Request $request,
        private readonly \Throwable $exception,
        private readonly Context $context,
    ) {
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getException(): \Throwable
    {
        return $this->exception;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
