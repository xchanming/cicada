<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\System\Country\SalesChannel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[Package('fundamentals@discovery')]
#[Group('store-api')]
class CountryStateRouteTest extends TestCase
{
    use IntegrationTestBehaviour;
    use SalesChannelApiTestBehaviour;

    private KernelBrowser $browser;

    private IdsCollection $ids;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();

        $this->browser = $this->createCustomSalesChannelBrowser([
            'id' => $this->ids->create('sales-channel'),
        ]);

        $this->ids->set(
            'countryId',
            $this->getCnCountryId()
        );
    }

    public function testGetStates(): void
    {
        $this->browser
            ->request(
                'POST',
                '/store-api/country-state/' . $this->ids->get('countryId'),
                [
                ]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertCount(34, $response['elements']);
        static::assertContains($this->ids->get('countryId'), array_column($response['elements'], 'countryId'));
        static::assertContains('CN-FJ', array_column($response['elements'], 'shortCode'));
    }

    public function testIncludes(): void
    {
        $this->browser
            ->request(
                'POST',
                '/store-api/country-state/' . $this->ids->get('countryId'),
                [
                    'includes' => [
                        'country_state' => ['shortCode'],
                    ],
                ]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertCount(34, $response['elements']);
        static::assertArrayNotHasKey('id', $response['elements'][0]);
        static::assertContains('CN-FJ', array_column($response['elements'], 'shortCode'));
    }

    public function testLimit(): void
    {
        $this->browser
            ->request(
                'POST',
                '/store-api/country-state/' . $this->ids->get('countryId'),
                [
                    'limit' => 2,
                ]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertCount(2, $response['elements']);
    }

    public function testSortByAlphabetical(): void
    {
        $this->browser
            ->request(
                'POST',
                '/store-api/country-state/' . $this->ids->get('countryId'),
                [
                    'limit' => 2,
                ]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertNotNull($response['elements']);
        static::assertCount(2, $response['elements']);

        static::assertEquals([
            'Anhui', 'Beijing',
        ], array_map(fn (array $state) => $state['name'], $response['elements']));
    }
}
