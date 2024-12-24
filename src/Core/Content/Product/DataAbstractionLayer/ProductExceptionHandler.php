<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\DataAbstractionLayer;

use Cicada\Core\Content\Product\Exception\DuplicateProductNumberException;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\ExceptionHandlerInterface;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class ProductExceptionHandler implements ExceptionHandlerInterface
{
    public function getPriority(): int
    {
        return ExceptionHandlerInterface::PRIORITY_DEFAULT;
    }

    public function matchException(\Exception $e): ?\Exception
    {
        if (preg_match('/SQLSTATE\[23000\]:.*1062 Duplicate.*uniq.product.product_number__version_id\'/', $e->getMessage())) {
            $number = [];
            preg_match('/Duplicate entry \'(.*)\' for key/', $e->getMessage(), $number);
            $numberMatch = $number[1] ?? '';
            $position = (int) strrpos($numberMatch, '-');
            $number = substr($numberMatch, 0, $position);

            return new DuplicateProductNumberException($number, $e);
        }

        return null;
    }
}
