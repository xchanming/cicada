<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Validation;

use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\App\Validation\Error\ErrorCollection;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
abstract class AbstractManifestValidator
{
    abstract public function validate(Manifest $manifest, Context $context): ErrorCollection;
}
