<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\Snippet\Filter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Snippet\Filter\AuthorFilter;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(AuthorFilter::class)]
class AuthorFilterTest extends TestCase
{
    public function testGetFilterName(): void
    {
        static::assertSame('author', (new AuthorFilter())->getName());
    }

    public function testSupports(): void
    {
        static::assertTrue((new AuthorFilter())->supports('author'));
        static::assertFalse((new AuthorFilter())->supports(''));
        static::assertFalse((new AuthorFilter())->supports('test'));
    }

    public function testFilter(): void
    {
        $snippets = [
            'firstSetId' => [
                'snippets' => [
                    '1.bar' => [
                        'value' => '1_bar',
                        'author' => 'Cicada',
                        'origin' => '',
                        'resetTo' => '',
                        'translationKey' => '',
                        'id' => null,
                        'setId' => '',
                    ],
                    '1.bas' => [
                        'value' => '1_bas',
                        'author' => 'Anonymous',
                        'origin' => '',
                        'resetTo' => '',
                        'translationKey' => '',
                        'id' => null,
                        'setId' => '',
                    ],
                ],
            ],
            'secondSetId' => [
                'snippets' => [
                    '2.bar' => [
                        'value' => '2_bar',
                        'author' => 'Cicada',
                        'origin' => '',
                        'resetTo' => '',
                        'translationKey' => '',
                        'id' => null,
                        'setId' => '',
                    ],
                    '2.baz' => [
                        'value' => '2_baz',
                        'author' => 'Anonymous',
                        'origin' => '',
                        'resetTo' => '',
                        'translationKey' => '',
                        'id' => null,
                        'setId' => '',
                    ],
                ],
            ],
        ];

        $expected = [
            'firstSetId' => [
                'snippets' => [
                    '1.bar' => [
                        'value' => '1_bar',
                        'author' => 'Cicada',
                        'origin' => '',
                        'resetTo' => '',
                        'translationKey' => '',
                        'id' => null,
                        'setId' => '',
                    ],
                    '2.bar' => [
                        'value' => '',
                        'origin' => '',
                        'resetTo' => '',
                        'translationKey' => '2.bar',
                        'author' => '',
                        'id' => null,
                        'setId' => 'firstSetId',
                    ],
                ],
            ],
            'secondSetId' => [
                'snippets' => [
                    '2.bar' => [
                        'value' => '2_bar',
                        'author' => 'Cicada',
                        'origin' => '',
                        'resetTo' => '',
                        'translationKey' => '',
                        'id' => null,
                        'setId' => '',
                    ],
                    '1.bar' => [
                        'value' => '',
                        'origin' => '',
                        'resetTo' => '',
                        'translationKey' => '1.bar',
                        'author' => '',
                        'id' => null,
                        'setId' => 'secondSetId',
                    ],
                ],
            ],
        ];

        $result = (new AuthorFilter())->filter($snippets, ['Cicada']);

        static::assertSame($expected, $result);
    }

    public function testFilterDoesntRemoveSnippetInOtherSet(): void
    {
        $snippets = [
            'firstSetId' => [
                'snippets' => [
                    'foo.bar' => [
                        'value' => '1_bar',
                        'author' => 'Cicada',
                        'origin' => '',
                        'resetTo' => '',
                        'translationKey' => '',
                        'id' => null,
                        'setId' => '',
                    ],
                    'foo.baz' => [
                        'value' => '1_baz',
                        'author' => 'Cicada',
                        'origin' => '',
                        'resetTo' => '',
                        'translationKey' => '',
                        'id' => null,
                        'setId' => '',
                    ],
                    'foo.bas' => [
                        'value' => '1_bas',
                        'author' => 'Anonymous',
                        'origin' => '',
                        'resetTo' => '',
                        'translationKey' => '',
                        'id' => null,
                        'setId' => '',
                    ],
                ],
            ],
            'secondSetId' => [
                'snippets' => [
                    'foo.bar' => [
                        'value' => '2_bar',
                        'author' => 'Cicada',
                        'origin' => '',
                        'resetTo' => '',
                        'translationKey' => '',
                        'id' => null,
                        'setId' => '',
                    ],
                    'foo.baz' => [
                        'value' => '2_baz',
                        'author' => 'Anonymous',
                        'origin' => '',
                        'resetTo' => '',
                        'translationKey' => '',
                        'id' => null,
                        'setId' => '',
                    ],
                ],
            ],
        ];

        $expected = [
            'firstSetId' => [
                'snippets' => [
                    'foo.bar' => [
                        'value' => '1_bar',
                        'author' => 'Cicada',
                        'origin' => '',
                        'resetTo' => '',
                        'translationKey' => '',
                        'id' => null,
                        'setId' => '',
                    ],
                    'foo.baz' => [
                        'value' => '1_baz',
                        'author' => 'Cicada',
                        'origin' => '',
                        'resetTo' => '',
                        'translationKey' => '',
                        'id' => null,
                        'setId' => '',
                    ],
                ],
            ],
            'secondSetId' => [
                'snippets' => [
                    'foo.bar' => [
                        'value' => '2_bar',
                        'author' => 'Cicada',
                        'origin' => '',
                        'resetTo' => '',
                        'translationKey' => '',
                        'id' => null,
                        'setId' => '',
                    ],
                    'foo.baz' => [
                        'value' => '2_baz',
                        'author' => 'Anonymous',
                        'origin' => '',
                        'resetTo' => '',
                        'translationKey' => '',
                        'id' => null,
                        'setId' => '',
                    ],
                ],
            ],
        ];

        $result = (new AuthorFilter())->filter($snippets, ['Cicada']);

        static::assertSame($expected, $result);
    }

    public function testFilterWithMultipleAuthors(): void
    {
        $snippets = [
            'firstSetId' => [
                'snippets' => [
                    'foo.bar' => [
                        'value' => '1_bar',
                        'author' => 'Test',
                        'origin' => '',
                        'resetTo' => '',
                        'translationKey' => '',
                        'id' => null,
                        'setId' => '',
                    ],
                    'foo.baz' => [
                        'value' => '1_baz',
                        'author' => 'Cicada',
                        'origin' => '',
                        'resetTo' => '',
                        'translationKey' => '',
                        'id' => null,
                        'setId' => '',
                    ],
                    'foo.bas' => [
                        'value' => '1_bas',
                        'author' => 'Anonymous',
                        'origin' => '',
                        'resetTo' => '',
                        'translationKey' => '',
                        'id' => null,
                        'setId' => '',
                    ],
                ],
            ],
            'secondSetId' => [
                'snippets' => [
                    'foo.bar' => [
                        'value' => '2_bar',
                        'author' => 'Test',
                        'origin' => '',
                        'resetTo' => '',
                        'translationKey' => '',
                        'id' => null,
                        'setId' => '',
                    ],
                    'foo.baz' => [
                        'value' => '2_baz',
                        'author' => 'Anonymous',
                        'origin' => '',
                        'resetTo' => '',
                        'translationKey' => '',
                        'id' => null,
                        'setId' => '',
                    ],
                ],
            ],
        ];

        $expected = [
            'firstSetId' => [
                'snippets' => [
                    'foo.bar' => [
                        'value' => '1_bar',
                        'author' => 'Test',
                        'origin' => '',
                        'resetTo' => '',
                        'translationKey' => '',
                        'id' => null,
                        'setId' => '',
                    ],
                    'foo.baz' => [
                        'value' => '1_baz',
                        'author' => 'Cicada',
                        'origin' => '',
                        'resetTo' => '',
                        'translationKey' => '',
                        'id' => null,
                        'setId' => '',
                    ],
                ],
            ],
            'secondSetId' => [
                'snippets' => [
                    'foo.bar' => [
                        'value' => '2_bar',
                        'author' => 'Test',
                        'origin' => '',
                        'resetTo' => '',
                        'translationKey' => '',
                        'id' => null,
                        'setId' => '',
                    ],
                    'foo.baz' => [
                        'value' => '2_baz',
                        'author' => 'Anonymous',
                        'origin' => '',
                        'resetTo' => '',
                        'translationKey' => '',
                        'id' => null,
                        'setId' => '',
                    ],
                ],
            ],
        ];

        $result = (new AuthorFilter())->filter($snippets, ['Cicada', 'Test']);

        static::assertSame($expected, $result);
    }
}
