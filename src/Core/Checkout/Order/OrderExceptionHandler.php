<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Order;

use Cicada\Core\Checkout\Order\Exception\LanguageOfOrderDeleteException;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\ExceptionHandlerInterface;
use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
class OrderExceptionHandler implements ExceptionHandlerInterface
{
    public function getPriority(): int
    {
        return ExceptionHandlerInterface::PRIORITY_DEFAULT;
    }

    public function matchException(\Exception $e): ?\Exception
    {
        if (preg_match('/SQLSTATE\[23000\]:.*1451.*a foreign key constraint.*order.*CONSTRAINT `fk.language_id`/', $e->getMessage())) {
            return new LanguageOfOrderDeleteException($e);
        }

        return null;
    }
}
