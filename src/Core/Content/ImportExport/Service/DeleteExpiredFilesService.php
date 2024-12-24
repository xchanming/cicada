<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Service;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal We might break this in v6.2
 */
#[Package('services-settings')]
class DeleteExpiredFilesService
{
    public function __construct(private readonly EntityRepository $fileRepository)
    {
    }

    public function countFiles(Context $context): int
    {
        $criteria = $this->buildCriteria();
        $criteria->setLimit(1);
        $criteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_EXACT);

        return $this->fileRepository->search($criteria, $context)->getTotal();
    }

    public function deleteFiles(Context $context): void
    {
        $criteria = $this->buildCriteria();

        $ids = $this->fileRepository->searchIds($criteria, $context)->getIds();
        $ids = array_map(fn ($id) => ['id' => $id], $ids);
        $this->fileRepository->delete($ids, $context);
    }

    private function buildCriteria(): Criteria
    {
        $criteria = new Criteria();
        $criteria->addFilter(new RangeFilter(
            'expireDate',
            [
                RangeFilter::LT => date(\DATE_ATOM),
            ]
        ));

        return $criteria;
    }
}
