<?php

declare(strict_types=1);

namespace LaminasTest\Hydrator\Strategy;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Laminas\Hydrator\Strategy\DateTimeFormatterStrategy;
use Laminas\Hydrator\Strategy\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Tests for {@see DateTimeFormatterStrategy}
 *
 * @covers \Laminas\Hydrator\Strategy\DateTimeFormatterStrategy
 */
class DateTimeFormatterStrategyTest extends TestCase
{
    public function testHydrate(): void
    {
        $strategy = new DateTimeFormatterStrategy('Y-m-d');
        self::assertEquals('2014-04-26', $strategy->hydrate('2014-04-26')->format('Y-m-d'));

        $strategy = new DateTimeFormatterStrategy('Y-m-d', new DateTimeZone('Asia/Kathmandu'));

        $date = $strategy->hydrate('2014-04-26');
        self::assertEquals('Asia/Kathmandu', $date->getTimezone()->getName());
    }

    public function testExtract(): void
    {
        $strategy = new DateTimeFormatterStrategy('d/m/Y');
        self::assertEquals('26/04/2014', $strategy->extract(new DateTime('2014-04-26')));
    }

    public function testGetNullWithInvalidDateOnHydration(): void
    {
        $strategy = new DateTimeFormatterStrategy('Y-m-d');
        self::assertEquals(null, $strategy->hydrate(null));
        self::assertEquals(null, $strategy->hydrate(''));
    }

    public function testCanExtractIfNotDateTime(): void
    {
        $strategy = new DateTimeFormatterStrategy();
        $date     = $strategy->extract(new stdClass());

        self::assertInstanceOf(stdClass::class, $date);
    }

    public function testCanHydrateWithInvalidDateTime(): void
    {
        $strategy = new DateTimeFormatterStrategy('d/m/Y');
        self::assertSame('foo bar baz', $strategy->hydrate('foo bar baz'));
    }

    public function testCanExtractAnyDateTimeInterface(): void
    {
        $dateMock = $this
            ->getMockBuilder(DateTime::class)
            ->getMock();

        $format = 'Y-m-d';
        $dateMock
            ->expects(self::once())
            ->method('format')
            ->with($format);

        $dateImmutableMock = $this
            ->getMockBuilder(DateTimeImmutable::class)
            ->getMock();

        $dateImmutableMock
            ->expects(self::once())
            ->method('format')
            ->with($format);

        $strategy = new DateTimeFormatterStrategy($format);

        $strategy->extract($dateMock);
        $strategy->extract($dateImmutableMock);
    }

    /**
     * @dataProvider formatsWithSpecialCharactersProvider
     * @param string $format
     * @param string $expectedValue
     */
    public function testAcceptsCreateFromFormatSpecialCharacters($format, $expectedValue): void
    {
        $strategy = new DateTimeFormatterStrategy($format);
        $hydrated = $strategy->hydrate($expectedValue);

        self::assertInstanceOf(DateTime::class, $hydrated);
        self::assertEquals($expectedValue, $hydrated->format('Y-m-d'));
    }

    /**
     * @dataProvider formatsWithSpecialCharactersProvider
     */
    public function testCanExtractWithCreateFromFormatSpecialCharacters(string $format, string $expectedValue): void
    {
        $date      = DateTime::createFromFormat($format, $expectedValue);
        $strategy  = new DateTimeFormatterStrategy($format);
        $extracted = $strategy->extract($date);

        self::assertEquals($expectedValue, $extracted);
    }

    public function testCanExtractWithCreateFromFormatEscapedSpecialCharacters(): void
    {
        $date      = DateTime::createFromFormat('Y-m-d', '2018-02-05');
        $strategy  = new DateTimeFormatterStrategy('Y-m-d\\+');
        $extracted = $strategy->extract($date);
        self::assertEquals('2018-02-05+', $extracted);
    }

    /**
     * @return string[][]
     * @psalm-return array<string, array{0: string, 1: string}>
     */
    public function formatsWithSpecialCharactersProvider(): array
    {
        return [
            '!-prepended' => ['!Y-m-d', '2018-02-05'],
            '|-appended'  => ['Y-m-d|', '2018-02-05'],
            '+-appended'  => ['Y-m-d+', '2018-02-05'],
        ];
    }

    public function testCanHydrateWithDateTimeFallback(): void
    {
        $strategy = new DateTimeFormatterStrategy('Y-m-d', null, true);
        $date     = $strategy->hydrate('2018-09-06T12:10:30');

        self::assertInstanceOf(DateTimeInterface::class, $date);
        self::assertSame('2018-09-06', $date->format('Y-m-d'));

        $strategy = new DateTimeFormatterStrategy('Y-m-d', new DateTimeZone('Europe/Prague'), true);
        $date     = $strategy->hydrate('2018-09-06T12:10:30');

        self::assertInstanceOf(DateTimeInterface::class, $date);
        self::assertSame('Europe/Prague', $date->getTimezone()->getName());
    }

    /** @return array<string, list<mixed>> */
    public function invalidValuesForHydration(): array
    {
        return [
            'zero'       => [0],
            'int'        => [1],
            'zero-float' => [0.0],
            'float'      => [1.1],
            'array'      => [['2018-11-20']],
            'object'     => [(object) ['date' => '2018-11-20']],
        ];
    }

    /**
     * @dataProvider invalidValuesForHydration
     */
    public function testHydrateRaisesExceptionIfValueIsInvalid(mixed $value): void
    {
        $strategy = new DateTimeFormatterStrategy('Y-m-d');

        $this->expectException(InvalidArgumentException::class);

        $strategy->hydrate($value);
    }

    /** @return array<string, list<mixed>> */
    public function validUnHydratableValues(): array
    {
        return [
            'empty string' => [''],
            'null'         => [null],
            'date-time'    => [new DateTimeImmutable('now')],
        ];
    }

    /**
     * @dataProvider validUnHydratableValues
     */
    public function testReturnsValueVerbatimUnderSpecificConditions(mixed $value): void
    {
        $strategy = new DateTimeFormatterStrategy('Y-m-d');
        $hydrated = $strategy->hydrate($value);
        self::assertSame($value, $hydrated);
    }
}
