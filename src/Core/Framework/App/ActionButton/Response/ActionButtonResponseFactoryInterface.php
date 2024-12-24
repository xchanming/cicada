<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\ActionButton\Response;

use Cicada\Core\Framework\App\ActionButton\AppAction;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
interface ActionButtonResponseFactoryInterface
{
    public function supports(string $actionType): bool;

    /**
     * @param array<string, mixed> $payload
     */
    public function create(AppAction $action, array $payload, Context $context): ActionButtonResponse;
}
