<?php declare(strict_types=1);

namespace Cicada\Core\Content\Category\DataAbstractionLayer;

use Cicada\Core\Content\Category\CategoryException;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\ExceptionHandlerInterface;
use Cicada\Core\Framework\Log\Package;

#[Package('inventory')]
class CategoryNonExistentExceptionHandler implements ExceptionHandlerInterface
{
    public function getPriority(): int
    {
        return ExceptionHandlerInterface::PRIORITY_DEFAULT;
    }

    public function matchException(\Exception $e): ?\Exception
    {
        if (preg_match('/SQLSTATE\[23000\]:.*1452 Cannot add or update a child row: a foreign key constraint fails.*category\.after_category_id/', $e->getMessage())) {
            return CategoryException::afterCategoryNotFound();
        }

        return null;
    }
}
