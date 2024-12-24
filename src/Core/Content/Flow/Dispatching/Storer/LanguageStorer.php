<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Dispatching\Storer;

use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Framework\Event\FlowEventAware;
use Cicada\Core\Framework\Event\LanguageAware;
use Cicada\Core\Framework\Log\Package;

#[Package('services-settings')]
class LanguageStorer extends FlowStorer
{
    /**
     * @param array<string, mixed> $stored
     *
     * @return array<string, mixed>
     */
    public function store(FlowEventAware $event, array $stored): array
    {
        if (!$event instanceof LanguageAware) {
            return $stored;
        }

        $stored[LanguageAware::LANGUAGE_ID] = $event->getLanguageId();

        return $stored;
    }

    public function restore(StorableFlow $storable): void
    {
        if (!$storable->hasStore(LanguageAware::LANGUAGE_ID)) {
            return;
        }

        $storable->setData(LanguageAware::LANGUAGE_ID, $storable->getStore(LanguageAware::LANGUAGE_ID));
    }
}
