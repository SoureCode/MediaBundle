<?php

namespace App\Response;

use SoureCode\MediaBundle\Range\Range;
use SoureCode\MediaBundle\Range\RangeSet;
use SoureCode\MediaBundle\Resource\MediaResource;
use SoureCode\MediaBundle\Writer\ResourceStreamWriter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PartialStreamedResponse extends StreamedResponse
{

    private MediaResource $resource;

    /**
     * @var resource $resource
     */
    private $outputHandle;

    private bool $sendHeaders = false;

    private bool $sendContent = false;

    private ?RangeSet $rangeSet;

    /**
     * @var Range[]
     */
    private array $ranges;

    /**
     * PartialResponse constructor.
     * @param MediaResource $resource
     * @param resource|null $outputHandle
     */
    public function __construct(MediaResource $resource, $outputHandle = null)
    {
        parent::__construct();
        $this->resource = $resource;
        $this->outputHandle = $outputHandle;
    }

    public function prepare(Request $request): PartialStreamedResponse
    {
        parent::prepare($request);

        $rangeHeader = $request->server->get("HTTP_RANGE");
        $this->rangeSet = RangeSet::createFromHeader($rangeHeader);

        if ($this->rangeSet) {
            $this->ranges = $this->rangeSet->getRangesForSize($this->resource->getSize());
        }

        return $this;
    }

    public function sendHeaders(): PartialStreamedResponse
    {
        if (!$this->sendHeaders) {
            $basename = basename($this->resource->getName());

            $this->headers->set('Accept-Ranges', "bytes");
            $this->headers->set('Content-Disposition', 'attachment; filename="' . $basename . '"');
            $this->headers->set('Content-Type', $this->resource->getMimeType());

            if ($this->rangeSet === null) {
                $this->setStatusCode(Response::HTTP_OK);

                $this->headers->set('Content-Length', $this->resource->getSize());
            } else {
                $responseBodySize = \array_reduce($this->ranges, static function (int $size, Range $range) {
                    return $size + $range->getLength();
                }, 0);

                $this->setStatusCode(Response::HTTP_PARTIAL_CONTENT);
                $contentRangeHeader = 'bytes ' . \implode(',', $this->ranges) . '/' . $this->resource->getSize();

                $this->headers->set('Content-Range', $contentRangeHeader);
                $this->headers->set('Content-Length', (string)$responseBodySize);
            }

            $this->sendHeaders = true;
        }

        return parent::sendHeaders();
    }

    public function sendContent(): PartialStreamedResponse
    {
        if (!$this->sendContent) {
            $writer = new ResourceStreamWriter($this->getOutputHandle());
            $resource = $this->resource;

            if ($this->rangeSet === null) {
                $this->setCallback(static function () use ($resource, $writer) {
                    $writer->write($resource);
                });
            } else {
                $ranges = $this->ranges;

                $this->setCallback(static function () use ($resource, $writer, $ranges) {
                    foreach ($ranges as $range) {
                        $writer->write($resource, $range);
                    }
                });
            }

            $this->sendContent = true;
        }

        return parent::sendContent();
    }

    /**
     * @return resource
     */
    private function getOutputHandle()
    {
        if (!$this->outputHandle) {
            $this->outputHandle = fopen('php://output', 'wb');
        }

        return $this->outputHandle;
    }


}
