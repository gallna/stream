<?php
namespace Kemer\Stream;

/**
 * PHP stream wrapper
 */
class StreamWrapper implements StreamInterface
{
    protected $stream;

    /**
     * @param StreamInterface $stream
     */
    public function __construct(StreamInterface $stream)
    {
        $this->setStream($stream);
    }

    /**
     * Set stream to wrap
     *
     * @param StreamInterface $stream
     * @return this
     */
    public function setStream(StreamInterface $stream)
    {
        $this->stream = $stream;
        return $this;
    }

    /**
     * Returns wrapped stream
     *
     * @return StreamInterface
     */
    public function getStream()
    {
        return $this->stream;
    }

    public function __get($name)
    {
        if ($name == 'stream') {
            throw new \RuntimeException('The stream is detached');
        }

        throw new \BadMethodCallException('No value for ' . $name);
    }

    /**
     * Closes the stream when the destructed
     */
    public function __destruct()
    {
        $this->stream->close();
    }

    public function __toString()
    {
        return $this->stream->__toString();
    }

    public function getContents()
    {
        return $this->stream->getContents();
    }

    public function close()
    {
        return $this->stream->close();
    }

    public function getResource()
    {
        return $this->stream->getResource();
    }

    public function detach()
    {
        return $this->stream->detach();
    }

    public function getSize()
    {
        return $this->stream->getSize();
    }

    public function isReadable()
    {
        return $this->stream->isReadable();
    }

    public function isWritable()
    {
        return $this->stream->isWritable();
    }

    public function isSeekable()
    {
        return $this->stream->isSeekable();
    }

    public function eof()
    {
        return $this->stream->eof();
    }

    public function tell()
    {
        return $this->stream->tell();
    }

    public function rewind()
    {
        return $this->stream->rewind();
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        return $this->stream->seek($offset, $whence);
    }

    public function read($length)
    {
        return $this->stream->read($length);
    }

    public function write($string)
    {
        return $this->stream->write($string);
    }

    public function getMetadata($key = null)
    {
        return $this->stream->getMetadata($key = null);
    }
}
