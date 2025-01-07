<?php declare(strict_types=1);

namespace Cicada\Core\System\Snippet\Files;

use Cicada\Core\Framework\Log\Package;

#[Package('discovery')]
abstract class AbstractSnippetFile
{
    /**
     * Returns the displayed name.
     *
     * Example:
     * storefront.en-GB
     */
    abstract public function getName(): string;

    /**
     * Returns the path to the json language file.
     *
     * Example:
     * /appPath/subDirectory/storefront.en-GB.json
     */
    abstract public function getPath(): string;

    /**
     * Returns the associated language ISO.
     *
     * Example:
     * en-GB
     * zh-CN
     */
    abstract public function getIso(): string;

    /**
     * Return the snippet author, which will be used when editing a file snippet in a snippet set
     *
     * Example:
     * cicada
     * pluginName
     */
    abstract public function getAuthor(): string;

    /**
     * Returns a boolean which determines if its a base language file
     */
    abstract public function isBase(): bool;

    /**
     * Returns a technical name of the bundle or app that the file is belonged to
     */
    abstract public function getTechnicalName(): string;
}
