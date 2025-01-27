<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Flow\Action\Xml;

use Cicada\Core\Framework\App\Flow\Action\Xml\Action;
use Cicada\Core\Framework\App\Flow\Action\Xml\Config;
use Cicada\Core\Framework\App\Flow\Action\Xml\Headers;
use Cicada\Core\Framework\App\Flow\Action\Xml\InputField;
use Cicada\Core\Framework\App\Flow\Action\Xml\Metadata;
use Cicada\Core\Framework\App\Flow\Action\Xml\Parameter;
use Cicada\Core\Framework\App\Flow\Action\Xml\Parameters;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Util\XmlUtils;

/**
 * @internal
 */
#[CoversClass(Action::class)]
class ActionTest extends TestCase
{
    private Action $action;

    private \DOMDocument $document;

    protected function setUp(): void
    {
        $this->document = XmlUtils::loadFile(
            __DIR__ . '/../../../_fixtures/Resources/flow.xml',
            __DIR__ . '/../../../../../../../../src/Core/Framework/App/Flow/Schema/flow-1.0.xsd'
        );

        $actions = $this->document->getElementsByTagName('flow-actions')->item(0);
        static::assertNotNull($actions);
        $action = $actions->getElementsByTagName('flow-action')->item(0);
        static::assertNotNull($action);
        $meta = $action->getElementsByTagName('meta')->item(0);
        static::assertNotNull($meta);

        $meta = Metadata::fromXml($meta);

        $parameter = Parameter::fromArray(['id' => 'key']);
        $parameters = Parameters::fromArray(['parameters' => [$parameter]]);
        $headers = Headers::fromArray(['parameters' => [$parameter]]);
        $inputFiled = InputField::fromArray(['id' => 'key']);
        $config = Config::fromArray(['config' => [$inputFiled]]);

        $this->action = Action::fromArray([
            'meta' => $meta,
            'headers' => $headers,
            'parameters' => $parameters,
            'config' => $config,
        ]);
    }

    public function testToArray(): void
    {
        $result = $this->action->toArray('en-GB');
        static::assertArrayHasKey('name', $result);
        static::assertArrayHasKey('swIcon', $result);
        static::assertArrayHasKey('url', $result);
        static::assertArrayHasKey('delayable', $result);
        static::assertArrayHasKey('parameters', $result);
        static::assertArrayHasKey('config', $result);
        static::assertArrayHasKey('headers', $result);
        static::assertArrayHasKey('requirements', $result);
        static::assertArrayHasKey('label', $result);
        static::assertArrayHasKey('description', $result);
        static::assertArrayHasKey('headline', $result);
    }

    public function testFromXml(): void
    {
        $actions = $this->document->getElementsByTagName('flow-actions')->item(0);
        static::assertNotNull($actions);
        foreach ($actions->getElementsByTagName('flow-action') as $action) {
            $result = $this->action::fromXml($action)->toArray('en-GB');
            static::assertArrayHasKey('meta', $result);
            static::assertArrayHasKey('headers', $result);
            static::assertArrayHasKey('config', $result);
            static::assertArrayHasKey('parameters', $result);
        }
    }

    public function testGetMeta(): void
    {
        $expected = [
            'label' => [
                'zh-CN' => 'First action app',
                'en-GB' => 'First action app DE',
            ],
            'description' => [
                'zh-CN' => 'First action app description',
                'en-GB' => 'First action app description DE',
            ],
            'name' => 'abc.cde.ccc',
            'url' => 'https://example.xyz',
            'requirements' => ['order', 'customer'],
            'icon' => 'resource/pencil',
            'swIcon' => 'sw-pencil',
            'headline' => [
                'zh-CN' => 'Headline for action',
                'en-GB' => 'Überschrift für Aktion',
            ],
            'delayable' => true,
            'badge' => 'abc',
        ];

        static::assertSame($expected, $this->action->getMeta()->toArray('zh-CN'));
    }

    public function testGetHeaders(): void
    {
        static::assertArrayHasKey('parameters', $this->action->getHeaders()->toArray('eb-GB'));
    }

    public function testGetParameters(): void
    {
        static::assertArrayHasKey('parameters', $this->action->getParameters()->toArray('eb-GB'));
    }

    public function testGetConfig(): void
    {
        static::assertArrayHasKey('config', $this->action->getConfig()->toArray('eb-GB'));
    }
}
