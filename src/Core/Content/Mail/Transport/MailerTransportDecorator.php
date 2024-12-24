<?php declare(strict_types=1);

namespace Cicada\Core\Content\Mail\Transport;

use Cicada\Core\Content\Mail\Service\Mail;
use Cicada\Core\Content\Mail\Service\MailAttachmentsBuilder;
use Cicada\Core\Content\MailTemplate\Subscriber\MailSendSubscriberConfig;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToRetrieveMetadata;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;

/**
 * @internal
 */
#[Package('services-settings')]
class MailerTransportDecorator implements TransportInterface, \Stringable
{
    public function __construct(
        private readonly TransportInterface $decorated,
        private readonly MailAttachmentsBuilder $attachmentsBuilder,
        private readonly FilesystemOperator $filesystem,
        private readonly EntityRepository $documentRepository
    ) {
    }

    public function __toString(): string
    {
        return $this->decorated->__toString();
    }

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        if (!$message instanceof Mail) {
            return $this->decorated->send($message, $envelope);
        }

        foreach ($message->getAttachmentUrls() as $url) {
            try {
                $mimeType = $this->filesystem->mimeType($url);
            } catch (UnableToRetrieveMetadata) {
                $mimeType = null;
            }
            $message->attach($this->filesystem->read($url) ?: '', basename($url), $mimeType);
        }

        $config = $message->getMailAttachmentsConfig();

        if (!$config) {
            return $this->decorated->send($message, $envelope);
        }

        $attachments = $this->attachmentsBuilder->buildAttachments(
            $config->getContext(),
            $config->getMailTemplate(),
            $config->getExtension(),
            $config->getEventConfig(),
            $config->getOrderId()
        );

        foreach ($attachments as $attachment) {
            $message->attach(
                $attachment['content'],
                $attachment['fileName'],
                $attachment['mimeType']
            );
        }

        $sentMessage = $this->decorated->send($message, $envelope);

        $this->setDocumentsSent($attachments, $config->getExtension(), $config->getContext());
        $config->getExtension()->setDocumentIds([]);

        return $sentMessage;
    }

    /**
     * @param array<int, array{id?: string, content: string, fileName: string, mimeType: string|null}> $attachments
     */
    private function setDocumentsSent(array $attachments, MailSendSubscriberConfig $extension, Context $context): void
    {
        $documentAttachments = array_filter($attachments, fn (array $attachment) => \in_array($attachment['id'] ?? null, $extension->getDocumentIds(), true));

        $documentAttachments = array_column($documentAttachments, 'id');

        if (empty($documentAttachments)) {
            return;
        }

        $payload = array_map(static fn (string $documentId) => [
            'id' => $documentId,
            'sent' => true,
        ], $documentAttachments);

        $this->documentRepository->update($payload, $context);
    }
}
