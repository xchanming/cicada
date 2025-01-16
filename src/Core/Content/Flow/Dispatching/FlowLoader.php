<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Dispatching;

use Cicada\Core\Framework\DataAbstractionLayer\Doctrine\FetchModeHelper;
use Cicada\Core\Framework\Log\Package;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

/**
 * @internal not intended for decoration or replacement
 *
 * @phpstan-type TFlows array<string, array<array{id: string, name: string, payload: array<mixed>}>>
 */
#[Package('after-sales')]
class FlowLoader extends AbstractFlowLoader
{
    public function __construct(
        private readonly Connection $connection,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @return TFlows
     */
    public function load(): array
    {
        $flows = $this->connection->fetchAllAssociative(
            'SELECT `event_name`, LOWER(HEX(`id`)) as `id`, `name`, `payload` FROM `flow`
                WHERE `active` = 1 AND `invalid` = 0 AND `payload` IS NOT NULL
                ORDER BY `priority` DESC',
        );

        if (empty($flows)) {
            return [];
        }

        foreach ($flows as $key => $flow) {
            try {
                $payload = unserialize($flow['payload']);
            } catch (\Throwable $e) {
                $this->logger->error(
                    "Flow payload is invalid:\n"
                    . 'Flow name: ' . $flow['name'] . "\n"
                    . 'Flow id: ' . $flow['id'] . "\n"
                    . $e->getMessage() . "\n"
                    . 'Error Code: ' . $e->getCode() . "\n"
                );

                continue;
            }

            $flows[$key]['payload'] = $payload;
        }

        /** @var list<array{id: string, name: string, payload: string}> $flows */
        $result = FetchModeHelper::group($flows);

        /** @var TFlows $result */
        return $result;
    }
}
