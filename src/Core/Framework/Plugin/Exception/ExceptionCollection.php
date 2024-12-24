<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Plugin\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Struct\Collection;

/**
 * @extends Collection<CicadaHttpException>
 */
#[Package('core')]
class ExceptionCollection extends Collection
{
    public function getApiAlias(): string
    {
        return 'plugin_exception_collection';
    }

    protected function getExpectedClass(): ?string
    {
        return CicadaHttpException::class;
    }
}
