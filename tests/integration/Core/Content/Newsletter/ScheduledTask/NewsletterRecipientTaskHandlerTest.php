<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Newsletter\ScheduledTask;

use Cicada\Core\Content\Newsletter\ScheduledTask\NewsletterRecipientTaskHandler;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseHelper\ReflectionHelper;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
#[Package('checkout')]
class NewsletterRecipientTaskHandlerTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testGetExpiredNewsletterRecipientCriteria(): void
    {
        $taskHandler = $this->getTaskHandler();
        $method = ReflectionHelper::getMethod(NewsletterRecipientTaskHandler::class, 'getExpiredNewsletterRecipientCriteria');

        /** @var Criteria $criteria */
        $criteria = $method->invoke($taskHandler);

        $filters = $criteria->getFilters();
        $dateFilter = array_shift($filters);
        $equalsFilter = array_shift($filters);

        static::assertInstanceOf(RangeFilter::class, $dateFilter);
        static::assertInstanceOf(EqualsFilter::class, $equalsFilter);

        static::assertSame('createdAt', $dateFilter->getField());
        static::assertNotEmpty($dateFilter->getParameter(RangeFilter::LTE));

        static::assertSame('status', $equalsFilter->getField());
        static::assertSame('notSet', $equalsFilter->getValue());
    }

    public function testRun(): void
    {
        $this->installTestData();

        $taskHandler = $this->getTaskHandler();
        $taskHandler->run();

        /** @var EntityRepository $repository */
        $repository = static::getContainer()->get('newsletter_recipient.repository');
        $result = $repository->searchIds(new Criteria(), Context::createDefaultContext());

        $expectedResult = [
            '7912f4de72aa43d792bcebae4eb45c5c',
            'b4b45f58088d41289490db956ca19af7',
        ];

        foreach ($expectedResult as $id) {
            static::assertContains($id, array_keys($result->getData()), print_r(array_keys($result->getData()), true));
        }
    }

    private function installTestData(): void
    {
        $salutationSql = file_get_contents(__DIR__ . '/../fixtures/salutation.sql');
        static::assertIsString($salutationSql);
        static::getContainer()->get(Connection::class)->executeStatement($salutationSql);

        $recipientSql = file_get_contents(__DIR__ . '/../fixtures/recipient.sql');
        static::assertIsString($recipientSql);
        $recipientSql = str_replace(':createdAt', (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT), $recipientSql);
        static::getContainer()->get(Connection::class)->executeStatement($recipientSql);
    }

    private function getTaskHandler(): NewsletterRecipientTaskHandler
    {
        return new NewsletterRecipientTaskHandler(
            static::getContainer()->get('scheduled_task.repository'),
            $this->createMock(LoggerInterface::class),
            static::getContainer()->get('newsletter_recipient.repository')
        );
    }
}
