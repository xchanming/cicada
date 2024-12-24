<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Plugin\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\PluginEntity;
use Cicada\Core\Framework\CicadaHttpException;

#[Package('core')]
class PluginHasActiveDependantsException extends CicadaHttpException
{
    /**
     * @param PluginEntity[] $dependants
     */
    public function __construct(
        string $dependency,
        array $dependants
    ) {
        $dependantNameList = array_map(static fn ($plugin) => \sprintf('"%s"', $plugin->getName()), $dependants);

        parent::__construct(
            'The following plugins depend on "{{ dependency }}": {{ dependantNames }}. They need to be deactivated before "{{ dependency }}" can be deactivated or uninstalled itself.',
            [
                'dependency' => $dependency,
                'dependants' => $dependants,
                'dependantNames' => implode(', ', $dependantNameList),
            ]
        );
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__PLUGIN_HAS_DEPENDANTS';
    }
}
