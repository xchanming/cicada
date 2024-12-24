<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\Aggregate\ProductSearchConfig;

use Cicada\Core\Content\Product\Exception\DuplicateProductSearchConfigLanguageException;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\ExceptionHandlerInterface;
use Cicada\Core\Framework\Log\Package;

#[Package('inventory')]
class ProductSearchConfigExceptionHandler implements ExceptionHandlerInterface
{
    public function getPriority(): int
    {
        return ExceptionHandlerInterface::PRIORITY_DEFAULT;
    }

    public function matchException(\Exception $e): ?\Exception
    {
        if (preg_match('/SQLSTATE\[23000\]:.*1062 Duplicate.*uniq.product_search_config.language_id\'/', $e->getMessage())) {
            return new DuplicateProductSearchConfigLanguageException('', $e);
        }

        return null;
    }
}
