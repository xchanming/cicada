<?php declare(strict_types=1);

namespace Cicada\Core\DevOps\Test\Environment\_fixtures;

use Cicada\Core\DevOps\Environment\EnvironmentHelperTransformerData;
use Cicada\Core\DevOps\Environment\EnvironmentHelperTransformerInterface;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('core')]
class EnvironmentHelperTransformer implements EnvironmentHelperTransformerInterface
{
    public static function transform(EnvironmentHelperTransformerData $data): void
    {
        $data->setValue($data->getValue() !== null ? $data->getValue() . ' bar' : null);
        $data->setDefault($data->getDefault() . ' baz');
    }
}
