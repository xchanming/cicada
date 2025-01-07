<?php declare(strict_types=1);

namespace Cicada\Core\Content\Sitemap\ScheduledTask;

use Cicada\Core\Content\Sitemap\Event\SitemapSalesChannelCriteriaEvent;
use Cicada\Core\Content\Sitemap\Service\SitemapExporterInterface;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Cicada\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[AsMessageHandler(handles: SitemapGenerateTask::class)]
#[Package('discovery')]
final class SitemapGenerateTaskHandler extends ScheduledTaskHandler
{
    /**
     * @internal
     */
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        LoggerInterface $logger,
        private readonly EntityRepository $salesChannelRepository,
        private readonly SystemConfigService $systemConfigService,
        private readonly MessageBusInterface $messageBus,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct($scheduledTaskRepository, $logger);
    }

    public function run(): void
    {
        $sitemapRefreshStrategy = $this->systemConfigService->getInt('core.sitemap.sitemapRefreshStrategy');
        if ($sitemapRefreshStrategy !== SitemapExporterInterface::STRATEGY_SCHEDULED_TASK) {
            return;
        }

        $criteria = new Criteria();
        $criteria->addAssociation('domains');
        $criteria->addFilter(new NotFilter(
            NotFilter::CONNECTION_AND,
            [new EqualsFilter('domains.id', null)]
        ));

        $criteria->addAssociation('type');
        $criteria->addFilter(new EqualsFilter('type.id', Defaults::SALES_CHANNEL_TYPE_STOREFRONT));

        $context = Context::createCLIContext();

        $this->eventDispatcher->dispatch(
            new SitemapSalesChannelCriteriaEvent($criteria, $context)
        );

        $salesChannels = $this->salesChannelRepository->search($criteria, $context)->getEntities();

        /** @var SalesChannelEntity $salesChannel */
        foreach ($salesChannels as $salesChannel) {
            if ($salesChannel->getDomains() === null) {
                continue;
            }

            $languageIds = $salesChannel->getDomains()->map(fn (SalesChannelDomainEntity $salesChannelDomain) => $salesChannelDomain->getLanguageId());

            $languageIds = array_unique($languageIds);

            foreach ($languageIds as $languageId) {
                $this->messageBus->dispatch(new SitemapMessage($salesChannel->getId(), $languageId, null, null, false));
            }
        }
    }
}
