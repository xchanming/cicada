<?php declare(strict_types=1);

namespace Cicada\Core\Content\Newsletter\ScheduledTask;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler(handles: NewsletterRecipientTask::class)]
#[Package('after-sales')]
final class NewsletterRecipientTaskHandler extends ScheduledTaskHandler
{
    /**
     * @internal
     */
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        LoggerInterface $logger,
        private readonly EntityRepository $newsletterRecipientRepository
    ) {
        parent::__construct($scheduledTaskRepository, $logger);
    }

    public function run(): void
    {
        $context = Context::createCLIContext();

        $criteria = $this->getExpiredNewsletterRecipientCriteria();
        $emailRecipient = $this->newsletterRecipientRepository->searchIds($criteria, $context);

        if (empty($emailRecipient->getIds())) {
            return;
        }

        $emailRecipientIds = array_map(fn ($id) => ['id' => $id], $emailRecipient->getIds());

        $this->newsletterRecipientRepository->delete($emailRecipientIds, $context);
    }

    private function getExpiredNewsletterRecipientCriteria(): Criteria
    {
        $criteria = new Criteria();

        $dateTime = (new \DateTime())->add(\DateInterval::createFromDateString('-30 days'));

        $criteria->addFilter(new RangeFilter(
            'createdAt',
            [
                RangeFilter::LTE => $dateTime->format(\DATE_ATOM),
            ]
        ));

        $criteria->addFilter(new EqualsFilter('status', 'notSet'));

        $criteria->setLimit(999);

        return $criteria;
    }
}
