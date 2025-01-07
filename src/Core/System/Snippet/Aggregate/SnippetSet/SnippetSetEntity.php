<?php declare(strict_types=1);

namespace Cicada\Core\System\Snippet\Aggregate\SnippetSet;

use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Cicada\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainCollection;
use Cicada\Core\System\Snippet\SnippetCollection;

#[Package('discovery')]
class SnippetSetEntity extends Entity
{
    use EntityCustomFieldsTrait;
    use EntityIdTrait;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $name;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $baseFile;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $iso;

    /**
     * @var SnippetCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $snippets;

    /**
     * @var SalesChannelDomainCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $salesChannelDomains;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getBaseFile(): string
    {
        return $this->baseFile;
    }

    public function setBaseFile(string $baseFile): void
    {
        $this->baseFile = $baseFile;
    }

    public function getIso(): string
    {
        return $this->iso;
    }

    public function setIso(string $iso): void
    {
        $this->iso = $iso;
    }

    public function getSnippets(): ?SnippetCollection
    {
        return $this->snippets;
    }

    public function setSnippets(SnippetCollection $snippets): void
    {
        $this->snippets = $snippets;
    }

    public function getSalesChannelDomains(): ?SalesChannelDomainCollection
    {
        return $this->salesChannelDomains;
    }

    public function setSalesChannelDomains(SalesChannelDomainCollection $salesChannelDomains): void
    {
        $this->salesChannelDomains = $salesChannelDomains;
    }
}
