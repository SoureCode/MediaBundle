<?php

namespace SoureCode\MediaBundle\Resource;

use SoureCode\MediaBundle\Exception\InvalidArgumentException;

final class MediaResource
{
    /**
     * @var resource
     */
    private $stream;

    private string $name;

    private int $size;

    private string $mimeType;

    /**
     * MediaResource constructor.
     * @param resource $stream
     * @param int $size
     * @param string $mimeType
     */
    public function __construct($stream, string $name, int $size, string $mimeType)
    {
        if (!\is_resource($stream) || 'stream' !== get_resource_type($stream)) {
            throw new InvalidArgumentException('The MediaResource class needs a stream as its first argument.');
        }

        $this->stream = $stream;
        $this->size = $size;
        $this->mimeType = $mimeType;
        $this->name = $name;
    }

    public function __destruct()
    {
        if ($this->stream !== null) {
            fclose($this->stream);
        }
    }

    /**
     * @return resource
     */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @return string
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }


}
