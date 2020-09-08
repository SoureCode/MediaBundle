<?php

namespace SoureCode\MediaBundle\Writer;

use SoureCode\MediaBundle\Exception\SendFileFailureException;
use SoureCode\MediaBundle\Exception\UnsatisfiableRangeException;
use SoureCode\MediaBundle\Range\Range;
use SoureCode\MediaBundle\Resource\MediaResource;

class ResourceStreamWriter
{
    /** @internal */
    private const DEFAULT_CHUNK_SIZE = 1024 * 8;

    private int $chunkSize;

    /**
     * @var resource
     */
    private $outputStream;

    /**
     * MediaResourceWriter constructor.
     * @param resource $outputStream
     * @param int $chunkSize
     */
    public function __construct($outputStream, int $chunkSize = self::DEFAULT_CHUNK_SIZE)
    {
        $this->chunkSize = $chunkSize;
        $this->outputStream = $outputStream;
    }

    /**
     * @param MediaResource $resource
     * @param Range|null $range
     * @param string|null $unit
     */
    public function write(MediaResource $resource, Range $range = null, string $unit = null): void
    {
        if (\strtolower($unit ?? 'bytes') !== 'bytes') {
            throw new UnsatisfiableRangeException('Unit not handled by this writer: ' . $unit);
        }

        $start = 0;
        $length = $resource->getSize();

        if ($range !== null) {
            $start = $range->getBegin();
            $length = $range->getLength();
        }

        $stream = $resource->getStream();

        fseek($stream, $start);

        while ($length > 0) {
            $length -= $this->writeChunk($stream, $length);
        }
    }

    /**
     * @param resource $stream
     * @param int $length
     * @return int
     */
    private function writeChunk($stream, int $length): int
    {
        $read = $length > $this->chunkSize ? $this->chunkSize : $length;

        $copied = stream_copy_to_stream($stream, $this->outputStream, $read);

        if ($copied === false) {
            throw new SendFileFailureException('stream_copy_to_stream() operation failed');
        }

        return $copied;
    }

}
