<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Plugin\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('core')]
class PluginBaseClassNotFoundException extends CicadaHttpException
{
    public function __construct(string $baseClass)
    {
        parent::__construct(
            'The class "{{ baseClass }}" is not found. Probably a class loader error. Check your plugin composer.json',
            ['baseClass' => $baseClass]
        );
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__PLUGIN_BASE_CLASS_NOT_FOUND';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
