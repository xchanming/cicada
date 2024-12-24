<?php declare(strict_types=1);

namespace Cicada\Core\System\Language;

use Cicada\Core\Framework\DataAbstractionLayer\Dbal\ExceptionHandlerInterface;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Language\Exception\LanguageForeignKeyDeleteException;

#[Package('buyers-experience')]
class LanguageExceptionHandler implements ExceptionHandlerInterface
{
    public function getPriority(): int
    {
        return ExceptionHandlerInterface::PRIORITY_LATE;
    }

    public function matchException(\Exception $e): ?\Exception
    {
        if (preg_match('/SQLSTATE\[23000\]:.*(1217|1216).*a foreign key constraint/', $e->getMessage())) {
            return new LanguageForeignKeyDeleteException($e);
        }

        return null;
    }
}
