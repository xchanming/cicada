<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\System\Language\SalesChannel;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[Group('store-api')]
class LanguageRouteTest extends TestCase
{
    use IntegrationTestBehaviour;
    use SalesChannelApiTestBehaviour;

    private KernelBrowser $browser;

    private IdsCollection $ids;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();

        $this->createData();

        $this->browser = $this->createCustomSalesChannelBrowser([
            'id' => $this->ids->create('sales-channel'),
            'languageId' => $this->ids->get('language'),
            'languages' => [
                ['id' => $this->ids->get('language')],
                ['id' => $this->ids->get('language2')],
            ],
            'domains' => [
                [
                    'languageId' => $this->ids->get('language'),
                    'currencyId' => Defaults::CURRENCY,
                    'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                    'url' => 'http://example.com',
                ],
                [
                    'languageId' => $this->ids->get('language2'),
                    'currencyId' => Defaults::CURRENCY,
                    'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                    'url' => 'http://example.com/second',
                ],
            ],
        ]);
    }

    public function testLanguages(): void
    {
        $this->browser
            ->request(
                'POST',
                '/store-api/language',
                [
                ]
            );

        $response = $this->getResponse();

        $ids = array_column($response['elements'], 'id');
        $names = array_column($response['elements'], 'name');

        static::assertSame(2, $response['total']);
        static::assertContains($this->ids->get('language'), $ids);
        static::assertContains($this->ids->get('language2'), $ids);
        static::assertContains($this->ids->get('language2'), $ids);
        static::assertContains('match', $names);
        static::assertContains('match2', $names);
        static::assertEmpty($response['elements'][0]['locale']);
    }

    public function testIncludes(): void
    {
        $this->browser
            ->request(
                'POST',
                '/store-api/language',
                [
                    'includes' => [
                        'language' => ['name'],
                    ],
                ]
            );

        $response = $this->getResponse();

        static::assertSame(2, $response['total']);
        static::assertArrayHasKey('name', $response['elements'][0]);
        static::assertArrayNotHasKey('id', $response['elements'][0]);
    }

    public function testAssociation(): void
    {
        $this->browser
            ->request(
                'POST',
                '/store-api/language',
                [
                    'associations' => [
                        'locale' => [],
                    ],
                ]
            );

        $response = $this->getResponse();

        static::assertSame(2, $response['total']);
        static::assertArrayHasKey('locale', $response['elements'][0]);
        static::assertNotEmpty($response['elements'][0]['locale']);
        static::assertArrayHasKey('id', $response['elements'][0]['locale']);
    }

    private function createData(): void
    {
        static::getContainer()->get('locale.repository')->create([
            [
                'id' => $this->ids->get('locale-1'),
                'code' => 'locale-1',
                'name' => 'locale-1',
                'territory' => 'locale-1',
            ],
            [
                'id' => $this->ids->get('locale-2'),
                'code' => 'locale-2',
                'name' => 'locale-2',
                'territory' => 'locale-2',
            ],
        ], Context::createDefaultContext());

        $data = [
            [
                'id' => $this->ids->create('language'),
                'name' => 'match',
                'localeId' => $this->ids->get('locale-1'),
                'translationCodeId' => $this->ids->get('locale-1'),
            ],
            [
                'id' => $this->ids->create('language2'),
                'name' => 'match2',
                'localeId' => $this->ids->get('locale-2'),
                'translationCodeId' => $this->ids->get('locale-2'),
            ],
        ];

        static::getContainer()->get('language.repository')
            ->create($data, Context::createDefaultContext());
    }

    /**
     * @return array<string, mixed>
     */
    private function getResponse(): array
    {
        $content = $this->browser->getResponse()->getContent();
        static::assertIsString($content);

        return json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
    }
}
