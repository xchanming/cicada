<?php declare(strict_types=1);

use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;

use function PHPStan\Testing\assertType;

$collection = new EntityCollection(['foo' => new Entity()]);

if ($collection->has('foo')) {
    assertType(Entity::class, $collection->get('foo'));
    assertType(Entity::class . '|null', $collection->get('bar'));
} else {
    assertType('null', $collection->get('foo'));
    assertType(Entity::class . '|null', $collection->get('bar'));
}
