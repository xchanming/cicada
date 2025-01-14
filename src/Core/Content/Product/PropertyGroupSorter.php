<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product;

use Cicada\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionCollection;
use Cicada\Core\Content\Property\PropertyGroupCollection;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;

#[Package('inventory')]
class PropertyGroupSorter extends AbstractPropertyGroupSorter
{
    public function getDecorated(): AbstractPropertyGroupSorter
    {
        throw new DecorationPatternException(self::class);
    }

    public function sort(EntityCollection $options): PropertyGroupCollection
    {
        $sorted = [];

        foreach ($options as $option) {
            $origin = $option->get('group');

            if ($origin === null || $origin->get('visibleOnProductDetailPage') === false) {
                continue;
            }

            $group = clone $origin;

            $groupId = $group->get('id');
            if (\array_key_exists($groupId, $sorted)) {
                \assert($sorted[$groupId]->get('options') !== null);
                $sorted[$groupId]->get('options')->add($option);

                continue;
            }

            if ($group->get('options') === null) {
                $group->assign([
                    'options' => new PropertyGroupOptionCollection(),
                ]);
            }

            \assert($group->get('options') !== null);
            $group->get('options')->add($option);

            $sorted[$groupId] = $group;
        }

        $collection = new PropertyGroupCollection($sorted);
        $collection->sortByPositions();
        $collection->sortByConfig();

        return $collection;
    }
}
