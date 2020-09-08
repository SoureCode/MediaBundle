<?php

namespace SoureCode\MediaBundle\Tests\Resource;

use PHPUnit\Framework\TestCase;
use SoureCode\MediaBundle\Resource\MediaResource;

final class MediaResourceTest extends TestCase
{

    public function testConstructor()
    {
        $stream = $this->getStream("foobar");
        $resource = new MediaResource($stream, "foo.txt", 6, "text/plain");

        self::assertSame($stream, $resource->getStream());
        self::assertSame("foobar", stream_get_contents($resource->getStream()));
        self::assertSame("foo.txt", $resource->getName());
        self::assertSame(6, $resource->getSize());
        self::assertSame("text/plain", $resource->getMimeType());
    }

    protected function getStream($data)
    {
        $stream = fopen('php://memory', 'rb+', false);
        fwrite($stream, $data);
        rewind($stream);

        return $stream;
    }

}
