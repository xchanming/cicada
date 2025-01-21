<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Dbal;

use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('framework')]
class ExceptionHandlerRegistry
{
    /**
     * @var array<int, list<ExceptionHandlerInterface>>
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $exceptionHandlers = [];

    /**
     * @internal
     *
     * @param iterable<ExceptionHandlerInterface> $exceptionHandlers
     */
    public function __construct(iterable $exceptionHandlers)
    {
        foreach ($exceptionHandlers as $exceptionHandler) {
            $this->add($exceptionHandler);
        }
    }

    public function add(ExceptionHandlerInterface $exceptionHandler): void
    {
        $this->exceptionHandlers[$exceptionHandler->getPriority()][] = $exceptionHandler;
    }

    public function matchException(\Exception $e): ?\Exception
    {
        foreach ($this->getExceptionHandlers() as $priorityExceptionHandlers) {
            foreach ($priorityExceptionHandlers as $exceptionHandler) {
                $innerException = $exceptionHandler->matchException($e);

                if ($innerException instanceof \Exception) {
                    return $innerException;
                }
            }
        }

        return null;
    }

    /**
     * @return array<int, list<ExceptionHandlerInterface>>
     */
    public function getExceptionHandlers(): array
    {
        return $this->exceptionHandlers;
    }
}
