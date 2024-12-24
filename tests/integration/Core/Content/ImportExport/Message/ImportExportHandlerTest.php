<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\ImportExport\Message;

use Cicada\Core\Content\ImportExport\Aggregate\ImportExportLog\ImportExportLogEntity;
use Cicada\Core\Content\ImportExport\Event\ImportExportExceptionImportExportHandlerEvent;
use Cicada\Core\Content\ImportExport\ImportExportFactory;
use Cicada\Core\Content\ImportExport\Message\ImportExportHandler;
use Cicada\Core\Content\ImportExport\Message\ImportExportMessage;
use Cicada\Core\Content\ImportExport\Service\ImportExportService;
use Cicada\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionDefinition;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Exception\InvalidUuidException;
use Cicada\Tests\Integration\Core\Content\ImportExport\AbstractImportExportTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\TraceableMessageBus;

/**
 * @internal
 */
#[Package('services-settings')]
class ImportExportHandlerTest extends AbstractImportExportTestCase
{
    public function testImportExportHandlerDispatchesMessage(): void
    {
        $messageBus = static::getContainer()->get('messenger.bus.cicada');
        static::assertInstanceOf(TraceableMessageBus::class, $messageBus);

        $importExportMessageCount = \count(
            \array_filter($messageBus->getDispatchedMessages(), function ($message): bool {
                return $message['message'] instanceof ImportExportMessage;
            })
        );

        $factory = static::getContainer()->get(ImportExportFactory::class);

        $eventDispatcher = static::getContainer()->get('event_dispatcher');

        $context = Context::createDefaultContext();

        $importExportHandler = new ImportExportHandler($messageBus, $factory, $eventDispatcher);

        $importExportService = static::getContainer()->get(ImportExportService::class);

        $profileId = $this->getDefaultProfileId(PropertyGroupOptionDefinition::ENTITY_NAME);

        $expireDate = new \DateTimeImmutable('2099-01-01');
        $file = new UploadedFile(__DIR__ . '/../fixtures/properties.csv', 'properties.csv', 'text/csv');

        $logEntity = $importExportService->prepareImport(
            $context,
            $profileId,
            $expireDate,
            $file
        );

        $importExportMessage = new ImportExportMessage($context, $logEntity->getId(), ImportExportLogEntity::ACTIVITY_IMPORT);

        $importExportHandler->__invoke($importExportMessage);

        $messages = \array_filter($messageBus->getDispatchedMessages(), function ($message): bool {
            return $message['message'] instanceof ImportExportMessage;
        });

        static::assertCount($importExportMessageCount + 1, $messages);

        $importExportMessage = $messages[\array_key_last($messages)]['message'];
        static::assertInstanceOf(ImportExportMessage::class, $importExportMessage);

        static::assertSame($logEntity->getId(), $importExportMessage->getLogId());
        static::assertSame($logEntity->getActivity(), $importExportMessage->getActivity());

        $updatedLogEntity = $this->getLogEntity($logEntity->getId());
        static::assertSame(50, $updatedLogEntity->getRecords());

        $importExportHandler->__invoke($importExportMessage);
        $updatedLogEntity = $this->getLogEntity($logEntity->getId());
        static::assertSame(100, $updatedLogEntity->getRecords());
    }

    public function testImportExportHandlerOnError(): void
    {
        $messageBus = static::getContainer()->get('messenger.bus.cicada');
        static::assertInstanceOf(TraceableMessageBus::class, $messageBus);

        $importExportMessageCount
            = \count(
                \array_filter($messageBus->getDispatchedMessages(), function ($message): bool {
                    return $message['message'] instanceof ImportExportMessage;
                })
            );

        $factory = static::getContainer()->get(ImportExportFactory::class);
        $eventDispatcher = static::getContainer()->get('event_dispatcher');
        $context = Context::createDefaultContext();

        $importExportHandler = new ImportExportHandler($messageBus, $factory, $eventDispatcher);
        $importExportService = static::getContainer()->get(ImportExportService::class);
        $profileId = $this->getDefaultProfileId(PropertyGroupOptionDefinition::ENTITY_NAME);

        $expireDate = new \DateTimeImmutable('2099-01-01');
        $file = new UploadedFile(__DIR__ . '/../fixtures/properties.csv', 'properties.csv', 'text/csv');

        $logEntity = $importExportService->prepareImport(
            $context,
            $profileId,
            $expireDate,
            $file
        );

        $importExportMessage = new ImportExportMessage($context, 'invalid_id', ImportExportLogEntity::ACTIVITY_IMPORT);

        $importExportExceptionImportExportHandlerEventCount = 0;

        $eventDispatcher
            ->addListener(
                ImportExportExceptionImportExportHandlerEvent::class,
                function (ImportExportExceptionImportExportHandlerEvent $event) use (&$importExportExceptionImportExportHandlerEventCount, $importExportMessage): void {
                    static::assertInstanceOf(InvalidUuidException::class, $event->getException());
                    static::assertSame(
                        0,
                        $event->getException()->getCode()
                    );
                    static::assertSame($importExportMessage, $event->getMessage());
                    ++$importExportExceptionImportExportHandlerEventCount;
                }
            );

        $importExportHandler->__invoke($importExportMessage);

        $messages = \array_filter($messageBus->getDispatchedMessages(), function ($message): bool {
            return $message['message'] instanceof ImportExportMessage;
        });

        static::assertCount($importExportMessageCount, $messages);
        static::assertSame(1, $importExportExceptionImportExportHandlerEventCount);
    }
}
