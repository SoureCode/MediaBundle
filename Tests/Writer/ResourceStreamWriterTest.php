<?php

namespace Writer;

use PHPUnit\Framework\TestCase;
use SoureCode\MediaBundle\Exception\UnsatisfiableRangeException;
use SoureCode\MediaBundle\Range\Range;
use SoureCode\MediaBundle\Resource\MediaResource;
use SoureCode\MediaBundle\Writer\ResourceStreamWriter;

final class ResourceStreamWriterTest extends TestCase
{
    /**
     * @var false|resource
     */
    protected $stream;

    protected MediaResource $resource;

    protected function setUp(): void
    {
        $data = "foo bar";
        $stream = $this->getStream($data);
        $this->resource = new MediaResource($stream, "foo.txt", strlen($data), "text/plain");
        $this->stream = fopen('php://memory', 'ab', false);
    }

    protected function tearDown(): void
    {
        $this->stream = null;
    }

    public function testWrite()
    {
        $writer = new ResourceStreamWriter($this->stream);

        $writer->write($this->resource);

        rewind($this->stream);
        self::assertSame("foo bar", stream_get_contents($this->stream));
    }

    public function testWriteRange()
    {
        $range = new Range(4,6);
        $writer = new ResourceStreamWriter($this->stream);

        $writer->write($this->resource, $range);

        rewind($this->stream);
        self::assertSame("bar", stream_get_contents($this->stream));
    }

    public function testUnit()
    {
        $this->expectException(UnsatisfiableRangeException::class);

        $writer = new ResourceStreamWriter($this->stream);

        $writer->write($this->resource, null, "megabytes");
    }
    protected function getStream($data)
    {
        $stream = fopen('php://memory', 'rb+', false);
        fwrite($stream, $data);
        rewind($stream);

        return $stream;
    }
}
