<?php
namespace Kemer\Stream\Buffer;

class Buffer implements BufferInterface
{
    /**
     * @var string
     */
    protected $buffer = '';

    public function __construct()
    {

    }

    public function getSize()
    {
        return strlen($this->buffer);
    }

    public function read($length)
    {
        if ($this->getSize() <= $length) {
            $data = $this->buffer;
            $this->buffer = '';
            return $data;
        }
        $data = substr($this->buffer, 0, $length);
        $this->buffer = substr($this->buffer, $length);
        return $data;
    }

    public function write($data)
    {
        $this->buffer .= $data;
        return $this;
    }
}
