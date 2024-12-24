<?php declare(strict_types=1);

namespace Cicada\Core\Content\ProductStream\ScheduledTask;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler(handles: UpdateProductStreamMappingTask::class)]
#[Package('inventory')]
final class UpdateProductStreamMappingTaskHandler extends ScheduledTaskHandler
{
    /**
     * @internal
     */
    public function __construct(
        EntityRepository $repository,
        LoggerInterface $logger,
        private readonly EntityRepository $productStreamRepository
    ) {
        parent::__construct($repository, $logger);
    }

    public function run(): void
    {
        $context = Context::createCLIContext();
        $criteria = new Criteria();
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
            new EqualsFilter('filters.type', 'until'),
            new EqualsFilter('filters.type', 'since'),
        ]));

        /** @var array<string> $streamIds */
        $streamIds = $this->productStreamRepository->searchIds($criteria, $context)->getIds();
        $data = array_map(fn (string $id) => ['id' => $id], $streamIds);

        $this->productStreamRepository->update($data, $context);
    }
}
