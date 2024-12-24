<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Changelog;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;

/**
 * @internal
 */
#[Package('core')]
class ChangelogFile extends Struct
{
    protected string $name;

    protected string $path;

    protected ChangelogDefinition $definition;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): ChangelogFile
    {
        $this->name = $name;

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): ChangelogFile
    {
        $this->path = $path;

        return $this;
    }

    public function getDefinition(): ChangelogDefinition
    {
        return $this->definition;
    }

    public function setDefinition(ChangelogDefinition $definition): ChangelogFile
    {
        $this->definition = $definition;

        return $this;
    }
}
