<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Page\LandingPage;

use Cicada\Core\Content\LandingPage\LandingPageDefinition;
use Cicada\Core\Content\LandingPage\LandingPageEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Storefront\Page\LandingPage\LandingPage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('buyers-experience')]
#[CoversClass(LandingPage::class)]
class LandingPageTest extends TestCase
{
    public function testLandingPage(): void
    {
        $page = new LandingPage();
        $entity = new LandingPageEntity();

        $page->setLandingPage($entity);

        static::assertSame(LandingPageDefinition::ENTITY_NAME, $page->getEntityName());
        static::assertSame($entity, $page->getLandingPage());
    }
}
