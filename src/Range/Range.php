<?php

namespace SoureCode\MediaBundle\Range;

use SoureCode\MediaBundle\Exception\IncompatibleRangesException;
use SoureCode\MediaBundle\Exception\InvalidRangeException;
use SoureCode\MediaBundle\Exception\LengthNotAvailableException;
use SoureCode\MediaBundle\Exception\UnsatisfiableRangeException;

final class Range
{

    private int $begin;

    private ?int $end;

    private bool $normal;

    public function __construct(int $begin, int $end = null)
    {
        $this->begin = $begin;
        $this->end = $end;

        if ($end < 0) {
            throw new InvalidRangeException('End cannot be negative');
        }

        $haveEnd = $end !== null;

        if ($haveEnd && $begin > $end) {
            throw new InvalidRangeException('Begin cannot be larger than end');
        }

        if ($haveEnd && $begin < 0) {
            throw new InvalidRangeException('A range with a negative begin cannot specify an end');
        }

        $this->normal = $begin >= 0 && $haveEnd;
    }

    public function getBegin(): int
    {
        return $this->begin;
    }

    public function getEnd(): ?int
    {
        return $this->end;
    }

    public function getLength(): int
    {
        if (!$this->normal) {
            throw new LengthNotAvailableException('Cannot retrieve length of a range that is not normalized');
        }

        return ($this->end - $this->begin) + 1;
    }

    public function normalize(int $size): Range
    {
        if ($this->normal) {
            if ($this->begin > $size) {
                throw new UnsatisfiableRangeException('Not satisfiable by a resource of the specified size');
            }

            return $this;
        }

        $end = $this->end ?? $size - 1;
        $begin = $this->begin < 0
            ? $end + $this->begin + 1
            : $this->begin;

        if ($begin > $size) {
            throw new UnsatisfiableRangeException('Not satisfiable by a resource of the specified size');
        }

        return new self(\max($begin, 0), \min($end, $size - 1));
    }

    public function overlaps(Range $other): bool
    {
        if (!$this->normal || !$other->normal) {
            throw new IncompatibleRangesException('Cannot test for overlap of ranges that have not been normalized');
        }

        // https://stackoverflow.com/a/3269471/889949
        return $this->begin <= $other->end && $other->begin <= $this->end;
    }

    public function combine(Range $other): self
    {
        if (!$this->normal || !$other->normal) {
            throw new IncompatibleRangesException('Cannot combine ranges that have not been normalized');
        }

        if (!($this->begin <= $other->end && $other->begin <= $this->end)) {
            throw new IncompatibleRangesException('Cannot combine non-overlapping ranges');
        }

        return new self(\min($this->begin, $other->begin), \max($this->end, $other->end));
    }

    public function __toString(): string
    {
        $suffix = $this->end !== null || $this->begin >= 0
            ? '-' . $this->end
            : '';

        return $this->begin . $suffix;
    }
}
