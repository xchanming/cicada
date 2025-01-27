<?php declare(strict_types=1);

namespace Cicada\Core\Content\ProductExport\Struct;

use Cicada\Core\Content\ProductExport\Error\Error;
use Cicada\Core\Framework\Log\Package;

#[Package('inventory')]
class ProductExportResult
{
    /**
     * @param list<Error> $errors
     */
    public function __construct(
        private readonly string $content,
        private readonly array $errors,
        private readonly int $total
    ) {
    }

    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return list<Error>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return \count($this->errors) > 0;
    }

    public function getTotal(): int
    {
        return $this->total;
    }
}
