<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Script\Api;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Facade\RepositoryFacadeHookFactory;
use Cicada\Core\Framework\DataAbstractionLayer\Facade\RepositoryWriterFacadeHookFactory;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Script\Execution\Awareness\ScriptResponseAwareTrait;
use Cicada\Core\Framework\Script\Execution\Awareness\StoppableHook;
use Cicada\Core\Framework\Script\Execution\Awareness\StoppableHookTrait;
use Cicada\Core\Framework\Script\Execution\Hook;
use Cicada\Core\System\SystemConfig\Facade\SystemConfigFacadeHookFactory;

/**
 * Triggered when the api endpoint /api/script/{hook} is called
 *
 * @hook-use-case custom_endpoint
 *
 * @since 6.4.9.0
 *
 * @final
 */
#[Package('core')]
class ApiHook extends Hook implements StoppableHook
{
    use ScriptResponseAwareTrait;
    use StoppableHookTrait;

    final public const HOOK_NAME = 'api-{hook}';

    public function __construct(
        private readonly string $name,
        /**
         * @var array<string, mixed>
         */
        private readonly array $request,
        Context $context
    ) {
        parent::__construct($context);
    }

    public function getInternalName(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRequest(): array
    {
        return $this->request;
    }

    public function getName(): string
    {
        return \str_replace(
            ['{hook}'],
            [$this->name],
            self::HOOK_NAME
        );
    }

    public static function getServiceIds(): array
    {
        return [
            RepositoryFacadeHookFactory::class,
            RepositoryWriterFacadeHookFactory::class,
            SystemConfigFacadeHookFactory::class,
            ScriptResponseFactoryFacadeHookFactory::class,
        ];
    }
}
