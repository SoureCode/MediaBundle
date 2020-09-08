<?php

namespace SoureCode\MediaBundle\Tests\Range;

use PHPUnit\Framework\TestCase;
use SoureCode\MediaBundle\Exception\InvalidRangeHeaderException;
use SoureCode\MediaBundle\Exception\UnsatisfiableRangeException;
use SoureCode\MediaBundle\Range\RangeSet;

final class RangeSetTest extends TestCase
{
    public function testCreateFromNullInputReturnsNull()
    {
        self::assertNull(RangeSet::createFromHeader(null));
    }

    public function testValidSingleRange()
    {
        $set = RangeSet::createFromHeader('bytes=0-23');

        self::assertInstanceOf(RangeSet::class, $set);

        self::assertSame('bytes', $set->getUnit());

        self::assertCount(1, $set->getRangesForSize(1000));
    }

    public function testValidSingleRanges()
    {
        foreach (['bytes=0-23', 'bytes=-23', 'bytes=0-', 'bytes=10-'] as $header) {
            $set = RangeSet::createFromHeader($header);

            self::assertInstanceOf(RangeSet::class, $set);

            self::assertSame('bytes', $set->getUnit(), "Header value: {$header}");

            self::assertCount(1, $set->getRangesForSize(1000), "Header value: {$header}");
        }
    }

    public function testValidSingleRangesVariance()
    {
        foreach (['bytes 0-23', 'bytes = 0-23', 'bytes = 0 - 23', 'bytes = 0 - 23'] as $header) {
            $set = RangeSet::createFromHeader($header);

            self::assertInstanceOf(RangeSet::class, $set);

            self::assertSame('bytes', $set->getUnit(), "Header value: {$header}");

            self::assertCount(1, $set->getRangesForSize(1000));
        }
    }

    public function testOverlappingRanges()
    {
        $set = RangeSet::createFromHeader('bytes=0-23,15-43');

        self::assertInstanceOf(RangeSet::class, $set);

        self::assertSame('bytes', $set->getUnit());

        $ranges = $set->getRangesForSize(1000);

        self::assertCount(1, $ranges);

        self::assertSame(0, $ranges[0]->getBegin());

        self::assertSame(43, $ranges[0]->getEnd());
    }

    public function testNonOverlappingRanges()
    {
        $set = RangeSet::createFromHeader('bytes=0-23,30-43');

        self::assertInstanceOf(RangeSet::class, $set);

        self::assertSame('bytes', $set->getUnit());

        self::assertCount(2, $set->getRangesForSize(1000));
    }

    public function testRangesNumberLimit()
    {
        $this->expectException(InvalidRangeHeaderException::class);

        $set = RangeSet::createFromHeader('bytes=0-1,1-2,2-3,3-4,4-5', 5);

        self::assertInstanceOf(RangeSet::class, $set);

        RangeSet::createFromHeader('bytes=0-1,1-2,2-3,3-4,4-5', 4);
    }

    public function testInvalidHeaderSyntaxThrows()
    {
        $this->expectException(InvalidRangeHeaderException::class);

        RangeSet::createFromHeader('randomgarbage');
    }

    public function testInvalidRangeSyntaxThrows()
    {
        $this->expectException(InvalidRangeHeaderException::class);

        RangeSet::createFromHeader('bytes=randomgarbage');
    }

    public function testEmptyRangeThrows()
    {
        $this->expectException(InvalidRangeHeaderException::class);

        RangeSet::createFromHeader('bytes=-');
    }

    public function testNoMatchingRangeThrows()
    {
        $this->expectException(UnsatisfiableRangeException::class);

        RangeSet::createFromHeader('bytes=10-100')->getRangesForSize(5);
    }
}
