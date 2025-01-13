<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Cms\DataResolver\Element;

use Cicada\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Cicada\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Cicada\Core\Content\Cms\DataResolver\Element\FormCmsElementResolver;
use Cicada\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Salutation\SalesChannel\AbstractSalutationRoute;
use Cicada\Core\System\Salutation\SalesChannel\SalutationRouteResponse;
use Cicada\Core\System\Salutation\SalutationCollection;
use Cicada\Core\System\Salutation\SalutationDefinition;
use Cicada\Core\System\Salutation\SalutationEntity;
use Cicada\Core\Test\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(FormCmsElementResolver::class)]
class FormCmsElementResolverTest extends TestCase
{
    public function testType(): void
    {
        $formCmsElementResolver = new FormCmsElementResolver($this->createMock(AbstractSalutationRoute::class));

        static::assertSame('form', $formCmsElementResolver->getType());
    }

    public function testResolverUsesAbstractSalutationsRouteToEnrichSlot(): void
    {
        $salutationCollection = $this->getSalutationCollection();
        $formCmsElementResolver = new FormCmsElementResolver($this->getSalutationRoute($salutationCollection));

        $formElement = $this->getCmsFormElement();
        $context = new ResolverContext(Generator::generateSalesChannelContext(), new Request());

        $formCmsElementResolver->enrich(
            $formElement,
            $context,
            new ElementDataCollection()
        );

        static::assertSame($formElement->getData(), $salutationCollection);
    }

    public function testResolverSortsSalutationsBySalutationKeyDesc(): void
    {
        $salutationCollection = $this->getSalutationCollection();
        $formCmsElementResolver = new FormCmsElementResolver($this->getSalutationRoute($salutationCollection));

        $formElement = $this->getCmsFormElement();
        $context = new ResolverContext(Generator::generateSalesChannelContext(), new Request());

        $formCmsElementResolver->enrich(
            $formElement,
            $context,
            new ElementDataCollection()
        );

        $enrichedCollection = $formElement->getData();
        static::assertInstanceOf(SalutationCollection::class, $enrichedCollection);

        $sortedKeys = array_values($enrichedCollection->map(static fn (SalutationEntity $salutation) => $salutation->getSalutationKey()));

        static::assertSame(['d', 'c', 'b', 'a'], $sortedKeys);
    }

    public function testCollectReturnsNull(): void
    {
        $context = new ResolverContext(Generator::generateSalesChannelContext(), new Request());
        $salutationRoute = $this->createMock(AbstractSalutationRoute::class);

        $formCmsElementResolver = new FormCmsElementResolver($salutationRoute);
        $actual = $formCmsElementResolver->collect(new CmsSlotEntity(), $context);

        static::assertNull($actual);
    }

    private function getCmsFormElement(): CmsSlotEntity
    {
        $slot = new CmsSlotEntity();
        $slot->setType('form');
        $slot->setUniqueIdentifier('id');

        return $slot;
    }

    private function getSalutationCollection(): SalutationCollection
    {
        return new SalutationCollection([
            $this->createSalutationWithSalutationKey('c'),
            $this->createSalutationWithSalutationKey('a'),
            $this->createSalutationWithSalutationKey('d'),
            $this->createSalutationWithSalutationKey('b'),
        ]);
    }

    private function createSalutationWithSalutationKey(string $salutationKey): SalutationEntity
    {
        return (new SalutationEntity())->assign([
            'id' => Uuid::randomHex(),
            'salutationKey' => $salutationKey,
        ]);
    }

    private function getSalutationRoute(SalutationCollection $salutationCollection): AbstractSalutationRoute
    {
        $salutationRoute = $this->createMock(AbstractSalutationRoute::class);
        $salutationRoute->expects(static::once())
            ->method('load')
            ->willReturn(new SalutationRouteResponse(
                new EntitySearchResult(
                    SalutationDefinition::ENTITY_NAME,
                    $salutationCollection->count(),
                    $salutationCollection,
                    null,
                    new Criteria(),
                    Context::createDefaultContext()
                )
            ));

        return $salutationRoute;
    }
}
