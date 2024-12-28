<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Mail\Transport;

use Cicada\Core\Content\Mail\Service\Mail;
use Cicada\Core\Content\Mail\Service\MailAttachmentsBuilder;
use Cicada\Core\Content\Mail\Service\MailAttachmentsConfig;
use Cicada\Core\Content\Mail\Transport\MailerTransportDecorator;
use Cicada\Core\Content\MailTemplate\MailTemplateEntity;
use Cicada\Core\Content\MailTemplate\Subscriber\MailSendSubscriberConfig;
use Cicada\Core\Framework\Adapter\Filesystem\MemoryFilesystemAdapter;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Uuid\Uuid;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;

/**
 * @internal
 */
#[CoversClass(MailerTransportDecorator::class)]
class MailerTransportDecoratorTest extends TestCase
{
    private MockObject&TransportInterface $decorated;

    private MockObject&MailAttachmentsBuilder $attachmentsBuilder;

    private Filesystem $filesystem;

    private MailerTransportDecorator $decorator;

    protected function setUp(): void
    {
        $this->decorated = $this->createMock(TransportInterface::class);
        $this->attachmentsBuilder = $this->createMock(MailAttachmentsBuilder::class);
        $this->filesystem = new Filesystem(new MemoryFilesystemAdapter());

        $this->decorator = new MailerTransportDecorator(
            $this->decorated,
            $this->attachmentsBuilder,
            $this->filesystem,
        );
    }

    public function testMailerTransportDecoratorDefault(): void
    {
        $mail = $this->createMock(Email::class);
        $envelope = $this->createMock(Envelope::class);

        $this->decorated->expects(static::once())->method('send')->with($mail, $envelope);

        $this->decorator->send($mail, $envelope);
    }

    public function testMailerTransportDecoratorWithUrlAttachments(): void
    {
        $mail = new Mail();
        $envelope = $this->createMock(Envelope::class);
        $mail->addAttachmentUrl('foo');
        $mail->addAttachmentUrl('bar');

        $this->filesystem->write('foo', 'foo');
        $this->filesystem->write('bar', 'bar');

        $this->decorated->expects(static::once())->method('send')->with($mail, $envelope);

        $this->decorator->send($mail, $envelope);
        $attachments = $mail->getAttachments();
        static::assertCount(2, $attachments);

        static::assertSame('foo', $attachments[0]->getBody());
        static::assertSame('bar', $attachments[1]->getBody());
    }

    public function testMailerTransportDecoratorWithBuildAttachments(): void
    {
        $mail = new Mail();
        $envelope = $this->createMock(Envelope::class);
        $mailAttachmentsConfig = new MailAttachmentsConfig(
            Context::createDefaultContext(),
            new MailTemplateEntity(),
            new MailSendSubscriberConfig(false, ['foo', 'bar']),
            [],
            Uuid::randomHex()
        );

        $mail->setMailAttachmentsConfig($mailAttachmentsConfig);

        $this->decorated->expects(static::once())->method('send')->with($mail, $envelope);

        $this->attachmentsBuilder
            ->expects(static::once())
            ->method('buildAttachments')
            ->with(
                $mailAttachmentsConfig->getContext(),
                $mailAttachmentsConfig->getMailTemplate(),
                $mailAttachmentsConfig->getExtension()
            )
            ->willReturn([
                ['id' => 'foo', 'content' => 'foo', 'name' => 'bar', 'mimeType' => 'baz/asd'],
                ['id' => 'bar', 'content' => 'bar', 'name' => 'bar', 'mimeType' => 'baz/asd'],
            ]);

        $this->decorator->send($mail, $envelope);

        $attachments = $mail->getAttachments();
        static::assertCount(2, $attachments);

        static::assertSame('foo', $attachments[0]->getBody());
        static::assertSame('bar', $attachments[1]->getBody());
    }
}
