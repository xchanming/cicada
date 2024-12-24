<?php declare(strict_types=1);

namespace Cicada\Core\Content\ProductExport\Error;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Collection;

/**
 * @extends Collection<Error>
 */
#[Package('inventory')]
class ErrorCollection extends Collection
{
    /**
     * @param Error $error
     */
    public function add($error): void
    {
        $this->set($error->getId(), $error);
    }

    /**
     * @param string $key
     * @param Error $error
     */
    public function set($key, $error): void
    {
        parent::set($error->getId(), $error);
    }

    public function getApiAlias(): string
    {
        return 'product_export_error';
    }

    protected function getExpectedClass(): ?string
    {
        return Error::class;
    }
}
