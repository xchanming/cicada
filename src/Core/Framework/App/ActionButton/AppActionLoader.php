<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\ActionButton;

use Cicada\Core\Framework\App\Aggregate\ActionButton\ActionButtonCollection;
use Cicada\Core\Framework\App\Aggregate\ActionButton\ActionButtonEntity;
use Cicada\Core\Framework\App\AppException;
use Cicada\Core\Framework\App\Exception\AppUrlChangeDetectedException;
use Cicada\Core\Framework\App\Payload\AppPayloadServiceHelper;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
class AppActionLoader
{
    /**
     * @param EntityRepository<ActionButtonCollection> $actionButtonRepo
     */
    public function __construct(
        private readonly EntityRepository $actionButtonRepo,
        private readonly AppPayloadServiceHelper $appPayloadServiceHelper,
    ) {
    }

    /**
     * @param array<string> $ids
     */
    public function loadAppAction(string $actionId, array $ids, Context $context): AppAction
    {
        $criteria = new Criteria([$actionId]);
        $criteria->addAssociation('app.integration');

        /** @var ActionButtonEntity $actionButton */
        $actionButton = $this->actionButtonRepo->search($criteria, $context)->getEntities()->first();

        if ($actionButton === null) {
            throw AppException::actionNotFound();
        }

        $app = $actionButton->getApp();
        \assert($app !== null);

        try {
            $source = $this->appPayloadServiceHelper->buildSource($app->getVersion(), $app->getName());
        } catch (AppUrlChangeDetectedException) {
            throw AppException::actionNotFound();
        }

        return new AppAction(
            $app,
            $source,
            $actionButton->getUrl(),
            $actionButton->getEntity(),
            $actionButton->getAction(),
            $ids,
            $actionId
        );
    }
}
