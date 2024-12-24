<?php declare(strict_types=1);

namespace Cicada\Core\Content\Mail\Service;

use Cicada\Core\Framework\Log\Package;
use Symfony\Component\Mime\Email;

#[Package('services-settings')]
abstract class AbstractMailFactory
{
    /**
     * @param array<string, string|null> $sender e.g. ['cicada@example.com' => 'Cicada AG']
     * @param array<string, string|null> $recipients e.g. ['cicada@example.com' => 'Cicada AG', 'symfony@example.com' => 'Symfony']
     * @param array<'text/plain'|'text/html', string> $contents e.g. ['text/plain' => 'Foo', 'text/html' => '<h1>Bar</h1>']
     * @param list<string> $attachments
     * @param array{
     *     attachmentsConfig?: MailAttachmentsConfig|null,
     *     recipientsCc?: string|array<string, string|null>,
     *     recipientsBcc?: string|array<string, string|null>,
     *     replyTo?: string|array<string, string|null>,
     *     returnPath?: string|array<string, string|null>,
     * } $additionalData e.g. ['recipientsCc' => ['cicada@example.com' => 'cicada', 'recipientsBcc' => 'cicada@example.com', 'replyTo' => 'reply@example.com', 'returnPath' => 'bounce@example.com']
     * @param list<array{content: resource|string, fileName: string|null, mimeType: string|null}>|null $binAttachments
     */
    abstract public function create(
        string $subject,
        array $sender,
        array $recipients,
        array $contents,
        array $attachments,
        array $additionalData,
        ?array $binAttachments = null
    ): Email;

    abstract public function getDecorated(): AbstractMailFactory;
}
