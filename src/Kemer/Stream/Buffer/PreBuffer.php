<?php
namespace Kemer\Stream\Buffer;

class PreBuffer extends Buffer
{
    /**
     * @var integer
     */
    private $prebufferSize;

    /**
     * @var boolean
     */
    private $released = false;

    public function __construct($prebufferSize = 1000000)
    {
        $this->prebufferSize = $prebufferSize;
    }

    public function getSize()
    {
        if (!$this->released) {
            $size = strlen($this->buffer);
            if ($size < $this->prebufferSize) {
                return 0;
            }
            $this->released = true;
            return $size;
        }
        return strlen($this->buffer);
    }

}
