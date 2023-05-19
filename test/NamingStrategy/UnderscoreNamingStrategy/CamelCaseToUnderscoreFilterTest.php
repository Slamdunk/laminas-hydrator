<?php

declare(strict_types=1);

namespace LaminasTest\Hydrator\NamingStrategy\UnderscoreNamingStrategy;

use Laminas\Hydrator\NamingStrategy\UnderscoreNamingStrategy\CamelCaseToUnderscoreFilter;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function extension_loaded;

/**
 * Tests for {@see CamelCaseToUnderscoreFilter}
 *
 * @covers Laminas\Hydrator\NamingStrategy\UnderscoreNamingStrategy\CamelCaseToUnderscoreFilter
 */
class CamelCaseToUnderscoreFilterTest extends TestCase
{
    /**
     * @dataProvider nonUnicodeProvider
     * @param string $string
     * @param string $expected
     */
    public function testFilterUnderscoresNonUnicodeStrings($string, $expected): void
    {
        $filter = new CamelCaseToUnderscoreFilter();

        $reflectionClass = new ReflectionClass($filter);
        $property        = $reflectionClass->getProperty('pcreUnicodeSupport');
        $property->setValue($filter, false);

        $filtered = $filter->filter($string);

        $this->assertNotEquals($string, $filtered);
        $this->assertEquals($expected, $filtered);
    }

    /**
     * @dataProvider unicodeProvider
     * @param string $string
     * @param string $expected
     */
    public function testFilterUnderscoresUnicodeStrings($string, $expected): void
    {
        if (! extension_loaded('mbstring')) {
            $this->markTestSkipped('Extension mbstring not available');
        }

        $filter = new CamelCaseToUnderscoreFilter();

        $filtered = $filter->filter($string);

        $this->assertNotEquals($string, $filtered);
        $this->assertEquals($expected, $filtered);
    }

    /**
     * @dataProvider unicodeProviderWithoutMbStrings
     * @param string $string
     * @param string $expected
     */
    public function testFilterUnderscoresUnicodeStringsWithoutMbStrings($string, $expected): void
    {
        $filter = new CamelCaseToUnderscoreFilter();

        $reflectionClass = new ReflectionClass($filter);
        $property        = $reflectionClass->getProperty('mbStringSupport');
        $property->setValue($filter, false);

        $filtered = $filter->filter($string);

        $this->assertNotEquals($string, $filtered);
        $this->assertEquals($expected, $filtered);
    }

    /**
     * @return string[][]
     * @psalm-return array<string, array{0: string, 1: string}>
     */
    public function nonUnicodeProvider(): array
    {
        return [
            'upcased first letter'                        => [
                'Camel',
                'camel',
            ],
            'multiple words'                              => [
                'underscoresMe',
                'underscores_me',
            ],
            'alphanumeric'                                => [
                'one2Three',
                'one2_three',
            ],
            'multiple uppercased letters and underscores' => [
                'TheseAre_SOME_CamelCASEDWords',
                'these_are_some_camel_cased_words',
            ],
            'alphanumeric multiple up cases'              => [
                'one2THR23ree',
                'one2_thr23ree',
            ],
            'lowercased alphanumeric'                     => [
                'bfd7b82e9cfceaa82704d1c1Foo',
                'bfd7b82e9cfceaa82704d1c1_foo',
            ],
        ];
    }

    /**
     * @return string[][]
     * @psalm-return array<string, array{0: string, 1: string}>
     */
    public function unicodeProvider(): array
    {
        return [
            'upcased first letter'                        => [
                'Camel',
                'camel',
            ],
            'multiple words'                              => [
                'underscoresMe',
                'underscores_me',
            ],
            'alphanumeric'                                => [
                'one2Three',
                'one2_three',
            ],
            'multiple uppercased letters and underscores' => [
                'TheseAre_SOME_CamelCASEDWords',
                'these_are_some_camel_cased_words',
            ],
            'alphanumeric multiple up cases'              => [
                'one2THR23ree',
                'one2_thr23ree',
            ],
            'unicode'                                     => [
                'testŠuma',
                'test_šuma',
            ],
        ];
    }

    /**
     * @return string[][]
     * @psalm-return array<string, array{0: string, 1: string}>
     */
    public function unicodeProviderWithoutMbStrings(): array
    {
        return [
            'upcased first letter'                        => [
                'Camel',
                'camel',
            ],
            'multiple words'                              => [
                'underscoresMe',
                'underscores_me',
            ],
            'alphanumeric'                                => [
                'one2Three',
                'one2_three',
            ],
            'multiple uppercased letters and underscores' => [
                'TheseAre_SOME_CamelCASEDWords',
                'these_are_some_camel_cased_words',
            ],
            'alphanumeric multiple up cases'              => [
                'one2THR23ree',
                'one2_thr23ree',
            ],
            'unicode uppercase character'                 => [
                'testŠuma',
                'test_Šuma',
            ],
        ];
    }
}
