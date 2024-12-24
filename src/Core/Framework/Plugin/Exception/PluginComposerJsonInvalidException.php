<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Plugin\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('core')]
class PluginComposerJsonInvalidException extends CicadaHttpException
{
    public function __construct(
        string $composerJsonPath,
        array $errors
    ) {
        parent::__construct(
            'The file "{{ composerJsonPath }}" is invalid. Errors:' . \PHP_EOL . '{{ errorsString }}',
            ['composerJsonPath' => $composerJsonPath, 'errorsString' => implode(\PHP_EOL, $errors), 'errors' => $errors]
        );
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__PLUGIN_COMPOSER_JSON_INVALID';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
