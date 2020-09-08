<?php

namespace SoureCode\MediaBundle\Tests\Range;

use PHPUnit\Framework\TestCase;
use SoureCode\MediaBundle\Exception\IncompatibleRangesException;
use SoureCode\MediaBundle\Exception\InvalidRangeException;
use SoureCode\MediaBundle\Exception\LengthNotAvailableException;
use SoureCode\MediaBundle\Exception\UnsatisfiableRangeException;
use SoureCode\MediaBundle\Range\Range;

final class RangeTest extends TestCase
{
    public function testGetLengthOfRangeFromBeginningToEndFails()
    {
        $this->expectException(LengthNotAvailableException::class);

        $range = new Range(0);

        self::assertSame(0, $range->getBegin());

        self::assertNull($range->getEnd());

        $range->getLength();
    }

    public function testRangeFromBeginningToOffset()
    {
        $range = new Range(0, 99);

        self::assertSame(0, $range->getBegin());

        self::assertSame(99, $range->getEnd());

        self::assertSame(100, $range->getLength());
    }

    public function testGetLengthOfRangeFromOffsetToEndFails()
    {
        $this->expectException(LengthNotAvailableException::class);

        $range = new Range(10);

        self::assertSame(10, $range->getBegin());

        self::assertNull($range->getEnd());

        $range->getLength();
    }

    public function testRangeFromOffsetToOffset()
    {
        $range = new Range(10, 99);

        self::assertSame(10, $range->getBegin());

        self::assertSame(99, $range->getEnd());

        self::assertSame(90, $range->getLength());
    }

    public function testGetLengthOfRangeWithNegativeStartFails()
    {
        $this->expectException(LengthNotAvailableException::class);

        $range = new Range(-10);

        self::assertSame(-10, $range->getBegin());

        self::assertNull($range->getEnd());

        $range->getLength();
    }

    public function testRangeFromBeginningToEndNormalized()
    {
        $range = (new Range(0))->normalize(100);

        self::assertSame(0, $range->getBegin());

        self::assertSame(99, $range->getEnd());

        self::assertSame(100, $range->getLength());
    }

    public function testRangeFromOffsetToEndNormalized()
    {
        $range = (new Range(10))->normalize(100);

        self::assertSame(10, $range->getBegin());

        self::assertSame(99, $range->getEnd());

        self::assertSame(90, $range->getLength());
    }

    public function testRangeFromNegativeStartNormalized()
    {
        $range = (new Range(-10))->normalize(100);

        self::assertSame(90, $range->getBegin());

        self::assertSame(99, $range->getEnd());

        self::assertSame(10, $range->getLength());
    }

    public function testNormalizingNormalizedRangeReturnsSameInstance()
    {
        $range = new Range(0, 99);

        self::assertSame($range, $range->normalize(100));
    }

    public function testNormalizingNonNormalizedRangeReturnsDifferentInstance()
    {
        $range = new Range(0);

        self::assertNotSame($range, $range->normalize(100));
    }

    public function testNormalizingNormalizedRangeAfterEndOfSizeFails()
    {
        $this->expectException(UnsatisfiableRangeException::class);

        (new Range(10, 100))->normalize(5);
    }

    public function testNormalizingNonNormalizedRangeAfterEndOfSizeFails()
    {
        $this->expectException(UnsatisfiableRangeException::class);

        (new Range(10))->normalize(5);
    }

    public function testNegativeEndFails()
    {
        $this->expectException(InvalidRangeException::class);

        new Range(0, -1);
    }

    public function testEndSmallerThanStartFails()
    {
        $this->expectException(InvalidRangeException::class);

        new Range(10, 9);
    }

    public function testEndWithNegativeStartFails()
    {
        $this->expectException(InvalidRangeException::class);

        new Range(-1, 10);
    }

    public function testToStringNormal()
    {
        self::assertSame('1-10', (string)new Range(1, 10));
    }

    public function testToStringNonNormalPositive()
    {
        self::assertSame('1-', (string)new Range(1));
    }

    public function testToStringNonNormalNegative()
    {
        self::assertSame('-1', (string)new Range(-1));
    }

    public function testCompareNonNormalizedWithNormalizedFails()
    {
        $this->expectException(IncompatibleRangesException::class);

        (new Range(1, 10))->overlaps(new Range(1));
    }

    public function testCompareNormalizedWithNonNormalizedFails()
    {
        $this->expectException(IncompatibleRangesException::class);

        (new Range(1, 10))->overlaps(new Range(1));
    }

    public function testCompareNonNormalizedWithNonNormalizedFails()
    {
        $this->expectException(IncompatibleRangesException::class);

        (new Range(1))->overlaps(new Range(2));
    }

    public function testOverlappingRanges()
    {
        self::assertTrue((new Range(1, 10))->overlaps(new Range(5, 15)));
    }

    public function testNonOverlappingRanges()
    {
        self::assertFalse((new Range(1, 10))->overlaps(new Range(15, 25)));
    }

    public function testCombineNonNormalizedWithNormalizedFails()
    {
        $this->expectException(IncompatibleRangesException::class);

        (new Range(1, 10))->combine(new Range(1));
    }

    public function testCombineNormalizedWithNonNormalizedFails()
    {
        $this->expectException(IncompatibleRangesException::class);

        (new Range(1))->combine(new Range(1, 10));
    }

    public function testCombineNonNormalizedWithNonNormalizedFails()
    {
        $this->expectException(IncompatibleRangesException::class);

        (new Range(1))->combine(new Range(2));
    }

    public function testCombiningOverlappingRanges()
    {
        $range = (new Range(1, 10))->combine(new Range(5, 15));

        self::assertSame(1, $range->getBegin());

        self::assertSame(15, $range->getEnd());

        self::assertSame(15, $range->getLength());
    }

    public function testCombiningNonOverlappingRangesFails()
    {
        $this->expectException(IncompatibleRangesException::class);

        (new Range(1, 10))->combine(new Range(15, 25));
    }
}
