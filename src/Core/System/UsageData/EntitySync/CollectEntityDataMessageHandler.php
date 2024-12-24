<?php declare(strict_types=1);

namespace Cicada\Core\System\UsageData\EntitySync;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\UsageData\Services\EntityDispatchService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler(handles: CollectEntityDataMessage::class)]
#[Package('data-services')]
final class CollectEntityDataMessageHandler
{
    public function __construct(
        private readonly EntityDispatchService $entityDispatchService,
    ) {
    }

    public function __invoke(CollectEntityDataMessage $message): void
    {
        $this->entityDispatchService->dispatchIterateEntityMessages($message);
    }
}
