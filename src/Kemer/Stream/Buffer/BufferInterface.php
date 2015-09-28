<?php
namespace Kemer\Stream\Buffer;

interface BufferInterface
{
    /**
     * Get size of buffer
     *
     * @return integer
     */
    public function getSize();

    /**
     * Read data from the buffer
     *
     * @param integer $length
     * @return string
     */
    public function read($length);

    /**
     * Write data into the buffer
     *
     * @param string $data
     * @return this
     */
    public function write($data);
}
