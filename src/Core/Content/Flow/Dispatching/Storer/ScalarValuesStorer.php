<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Dispatching\Storer;

use Cicada\Core\Content\Flow\Dispatching\Aware\ScalarValuesAware;
use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Framework\Event\FlowEventAware;
use Cicada\Core\Framework\Log\Package;

#[Package('after-sales')]
class ScalarValuesStorer extends FlowStorer
{
    /**
     * @param array<string, mixed> $stored
     *
     * @return array<string, mixed>
     */
    public function store(FlowEventAware $event, array $stored): array
    {
        if (!$event instanceof ScalarValuesAware) {
            return $stored;
        }

        if (isset($stored[ScalarValuesAware::STORE_VALUES])) {
            throw new \RuntimeException('Can not store generic values twice.');
        }

        $stored[ScalarValuesAware::STORE_VALUES] = $event->getValues();

        return $stored;
    }

    public function restore(StorableFlow $storable): void
    {
        if (!$storable->hasStore(ScalarValuesAware::STORE_VALUES)) {
            return;
        }

        $values = $storable->getStore(ScalarValuesAware::STORE_VALUES);
        foreach ($values as $key => $value) {
            $storable->setData($key, $value);
        }
    }
}
