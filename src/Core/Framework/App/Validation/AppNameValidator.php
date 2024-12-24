<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Validation;

use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\App\Validation\Error\AppNameError;
use Cicada\Core\Framework\App\Validation\Error\ErrorCollection;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
class AppNameValidator extends AbstractManifestValidator
{
    public function validate(Manifest $manifest, ?Context $context): ErrorCollection
    {
        $errors = new ErrorCollection();

        $appName = strtolower(substr($manifest->getPath(), strrpos($manifest->getPath(), '/') + 1));

        if ($appName !== strtolower($manifest->getMetadata()->getName())) {
            $errors->add(new AppNameError($manifest->getMetadata()->getName()));
        }

        return $errors;
    }
}
