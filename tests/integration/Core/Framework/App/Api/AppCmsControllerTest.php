<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Api;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Test\TestCaseBase\AdminApiTestBehaviour;
use Cicada\Core\Test\AppSystemTestBehaviour;
use Cicada\Tests\Integration\Core\Framework\App\GuzzleTestClientBehaviour;

/**
 * @internal
 */
class AppCmsControllerTest extends TestCase
{
    use AdminApiTestBehaviour;
    use AppSystemTestBehaviour;
    use GuzzleTestClientBehaviour;

    public function testGetBlocks(): void
    {
        $this->loadAppsFromDir(__DIR__ . '/../Manifest/_fixtures/test');
        $this->getBrowser()->request('GET', '/api/app-system/cms/blocks');

        $response = $this->getBrowser()->getResponse();
        static::assertNotFalse($response->getContent());
        static::assertSame(200, $response->getStatusCode());

        $json = \file_get_contents(__DIR__ . '/_fixtures/expectedCmsBlocks.json');
        static::assertNotFalse($json);
        static::assertJson($json);

        $expected = \json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
        $actual = \json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        $expected['blocks'][0]['template'] = $this->stripWhitespace($expected['blocks'][0]['template']);
        $expected['blocks'][1]['template'] = $this->stripWhitespace($expected['blocks'][1]['template']);
        $actual['blocks'][0]['template'] = $this->stripWhitespace($actual['blocks'][0]['template']);
        $actual['blocks'][1]['template'] = $this->stripWhitespace($actual['blocks'][1]['template']);

        static::assertEquals(
            $expected,
            $actual
        );
    }

    private function stripWhitespace(string $text): string
    {
        return (string) preg_replace('/\s/m', '', $text);
    }
}
