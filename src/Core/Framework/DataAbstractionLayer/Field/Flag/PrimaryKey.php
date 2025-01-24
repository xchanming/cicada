<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Field\Flag;

use Cicada\Core\Framework\Log\Package;

#[Package('framework')]
class PrimaryKey extends Flag
{
    public function parse(): \Generator
    {
        yield 'primary_key' => true;
    }
}
