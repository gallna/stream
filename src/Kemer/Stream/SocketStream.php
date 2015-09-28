<?php
namespace Kemer\Stream;

use Psr\Http\Message\StreamInterface;

/**
 * PHP stream implementation.
 *
 * @var $stream
 */
class SocketStream implements StreamInterface
{
    private $socket;
    private $size;
    private $seekable;
    private $readable;
    private $writable;
    private $uri;
    private $customMetadata = [];
    private $metadata = [];

    /**
     * This constructor accepts an associative array of options.
     *
     * - size: (int) If a read stream would otherwise have an indeterminate
     *   size, but the size is known due to foreknownledge, then you can
     *   provide that size, in bytes.
     * - metadata: (array) Any additional metadata to return when the metadata
     *   of the stream is accessed.
     *
     * @param resource $socket  Socket resource to wrap.
     * @param array $metadata: (array) Any additional metadata to return when the metadata
     *
     * @throws \InvalidArgumentException if the stream is not a stream resource
     */
    public function __construct($socket, array $metadata = [])
    {
        if (!is_resource($socket)) {
            throw new \InvalidArgumentException('Stream must be a resource');
        }
        $this->socket = $socket;

        $this->seekable = false;
        $this->readable = true;
        $this->writable = true;

        $this->metadata = $metadata;
        socket_getpeername($socket, $address, $port);
        $this->metadata["uri"] = sprintf("http://%s:%s", $address, $port);
    }

    /**
     * Closes the stream when the destructed
     */
    public function __destruct()
    {
        $this->close();
    }

    public function __toString()
    {
        try {
            $this->seek(0);

        } catch (\Exception $e) {
            return '';
        }
    }

    public function getContents()
    {
        $contents = false;

        if ($contents === false) {
            throw new \RuntimeException('Unable to read stream contents');
        }

        return $contents;
    }

    public function close()
    {
        if (isset($this->socket)) {
            if (is_resource($this->socket)) {
                socket_close($this->socket);
            }
            $this->detach();
        }
    }

    public function detach()
    {
        if (!isset($this->socket)) {
            return null;
        }

        $result = $this->socket;
        unset($this->socket);
        $this->size = $this->uri = null;
        $this->readable = $this->writable = $this->seekable = false;

        return $result;
    }

    public function getSize()
    {
        return null;
    }

    public function isReadable()
    {
        return $this->readable;
    }

    public function isWritable()
    {
        return $this->writable;
    }

    public function isSeekable()
    {
        return $this->seekable;
    }

    public function eof()
    {
        return !$this->socket;
    }

    public function tell()
    {

    }

    public function rewind()
    {
        $this->seek(0);
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->seekable) {
            throw new \RuntimeException('Stream is not seekable');
        } elseif (fseek($this->socket, $offset, $whence) === -1) {
            throw new \RuntimeException('Unable to seek to stream position '
                . $offset . ' with whence ' . var_export($whence, true));
        }
    }

    public function read($length)
    {
        if (!$this->readable) {
            throw new \RuntimeException('Cannot read from non-readable stream');
        }
        $buf = false;
        socket_recv($this->socket, $buf, $length, MSG_WAITALL);
        return $buf;
    }

    public function write($string)
    {
        if (!$this->writable) {
            throw new \RuntimeException('Cannot write to a non-writable stream');
        }
        $result = socket_write($this->socket, $string, strlen($string));
        if ($result === false) {
            throw new \RuntimeException('Unable to write to stream');
        }
        return $result;
    }

    public function getMetadata($key = null)
    {
        return $key
            ? (isset($this->metadata[$key]) ? $this->metadata[$key] : null)
            : $this->metadata;
    }
}
