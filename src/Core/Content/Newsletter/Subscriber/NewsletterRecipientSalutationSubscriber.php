<?php declare(strict_types=1);

namespace Cicada\Core\Content\Newsletter\Subscriber;

use Cicada\Core\Content\Newsletter\NewsletterEvents;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Salutation\SalutationDefinition;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('buyers-experience')]
class NewsletterRecipientSalutationSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            NewsletterEvents::NEWSLETTER_RECIPIENT_WRITTEN_EVENT => 'setDefaultSalutation',
        ];
    }

    public function setDefaultSalutation(EntityWrittenEvent $event): void
    {
        $payloads = $event->getPayloads();
        foreach ($payloads as $payload) {
            if (\array_key_exists('salutationId', $payload) && $payload['salutationId']) {
                continue;
            }

            if (!isset($payload['id'])) {
                continue;
            }

            $this->updateNewsletterRecipientWithNotSpecifiedSalutation($payload['id']);
        }
    }

    private function updateNewsletterRecipientWithNotSpecifiedSalutation(string $id): void
    {
        $this->connection->executeStatement(
            '
                UPDATE `newsletter_recipient`
                SET `salutation_id` = (
                    SELECT `id`
                    FROM `salutation`
                    WHERE `salutation_key` = :notSpecified
                    LIMIT 1
                )
                WHERE `id` = :id AND `salutation_id` is NULL
            ',
            ['id' => Uuid::fromHexToBytes($id), 'notSpecified' => SalutationDefinition::NOT_SPECIFIED]
        );
    }
}
