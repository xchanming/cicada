<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Page\Account;

use Cicada\Core\Checkout\Customer\CustomerException;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Storefront\Page\Account\CustomerGroupRegistration\CustomerGroupRegistrationPageLoader;
use Cicada\Storefront\Test\Page\StorefrontPageTestBehaviour;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class CustomerGroupRegistrationTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontPageTestBehaviour;

    private IdsCollection $ids;

    private SalesChannelContext $salesChannel;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();
        $this->salesChannel = $this->createSalesChannelContext();
    }

    public function test404(): void
    {
        static::expectException(CustomerException::class);
        $request = new Request();
        $request->attributes->set('customerGroupId', Defaults::LANGUAGE_SYSTEM);

        $this->getPageLoader()->load($request, $this->salesChannel);
    }

    public function testGetConfiguration(): void
    {
        $customerGroupRepository = static::getContainer()->get('customer_group.repository');
        $customerGroupRepository->create([
            [
                'id' => $this->ids->create('group'),
                'name' => 'foo',
                'registrationActive' => true,
                'registrationTitle' => 'test',
                'registrationSalesChannels' => [['id' => $this->salesChannel->getSalesChannelId()]],
            ],
        ], Context::createDefaultContext());

        $request = new Request();
        $request->attributes->set('customerGroupId', $this->ids->get('group'));

        $page = $this->getPageLoader()->load($request, $this->salesChannel);
        static::assertSame($this->ids->get('group'), $page->getGroup()->getId());
        static::assertSame('test', $page->getGroup()->getRegistrationTitle());
    }

    protected function getPageLoader(): CustomerGroupRegistrationPageLoader
    {
        return static::getContainer()->get(CustomerGroupRegistrationPageLoader::class);
    }
}
