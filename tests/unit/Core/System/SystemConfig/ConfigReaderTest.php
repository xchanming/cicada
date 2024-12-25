<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\SystemConfig;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Util\UtilException;
use Cicada\Core\System\SystemConfig\Util\ConfigReader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(ConfigReader::class)]
class ConfigReaderTest extends TestCase
{
    private ConfigReader $configReader;

    protected function setUp(): void
    {
        $this->configReader = new ConfigReader();
    }

    public function testConfigReaderWithValidConfig(): void
    {
        $actualConfig = $this->configReader->read(__DIR__ . '/_fixtures/valid_config.xml');

        static::assertSame($this->getExpectedConfig(), $actualConfig);
    }

    public function testConfigReaderWithInvalidPath(): void
    {
        $this->expectException(UtilException::class);

        $this->configReader->read(__DIR__ . '/config.xml');
    }

    public function testConfigReaderWithInvalidConfig(): void
    {
        $this->expectException(UtilException::class);

        $this->configReader->read(__DIR__ . '/_fixtures/invalid_config.xml');
    }

    /**
     * @return array<mixed>
     */
    private function getExpectedConfig(): array
    {
        return [
            0 => [
                'title' => [
                    'en-GB' => 'Basic configuration',
                    'zh-CN' => 'Grundeinstellungen',
                ],
                'name' => null,
                'elements' => [
                    0 => [
                        'type' => 'text',
                        'name' => 'email',
                        'copyable' => true,
                        'label' => [
                            'en-GB' => 'eMail',
                            'zh-CN' => 'E-Mail',
                        ],
                        'placeholder' => [
                            'en-GB' => 'Enter your eMail address',
                            'zh-CN' => 'Bitte gib deine E-Mail Adresse ein',
                        ],
                    ],
                    1 => [
                        'type' => 'single-select',
                        'name' => 'mailMethod',
                        'options' => [
                            0 => [
                                'id' => 'smtp',
                                'name' => [
                                    'en-GB' => 'SMTP',
                                ],
                            ],
                            1 => [
                                'id' => 'pop3',
                                'name' => [
                                    'en-GB' => 'POP3',
                                ],
                            ],
                        ],
                        'label' => [
                            'en-GB' => 'Mailing protocol',
                            'zh-CN' => 'E-Mail Versand Protokoll',
                        ],
                        'placeholder' => [
                            'en-GB' => 'Choose your preferred transfer method',
                            'zh-CN' => 'Bitte wähle dein bevorzugtes Versand Protokoll',
                        ],
                    ],
                    2 => [
                        'componentName' => 'sw-select',
                        'name' => 'mailMethodComponent',
                        'disabled' => true,
                        'options' => [
                            0 => [
                                'id' => 'smtp',
                                'name' => [
                                    'en-GB' => 'English smtp',
                                    'zh-CN' => 'German smtp',
                                ],
                            ],
                            1 => [
                                'id' => 'pop3',
                                'name' => [
                                    'en-GB' => 'English pop3',
                                    'zh-CN' => 'German pop3',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
