<?php
namespace Kemer\Stream\Pipe;

use Kemer\Stream\StreamWrapper;
use Kemer\Stream\Buffer;
use Psr\Http\Message\StreamInterface;

class PipeStream extends StreamWrapper
{
    /**
     * @var Buffer
     */
    private $buffer;

    /**
     * @var string
     */
    private $command;

    /**
     * @var array
     */
    private $pipes;

    /**
     * @var resource
     */
    private $process;

    /**
     * @param StreamInterface $stream
     * @param Buffer|null $buffer
     */
    public function __construct(StreamInterface $stream, $command, Buffer\BufferInterface $buffer = null)
    {
        parent::__construct($stream);
        $this->command = $command;
        $this->buffer = $buffer ?: new Buffer\Buffer();
    }

    public function getContents()
    {
        $contents = null;
        while(!$contents) {
            $chunk = $this->stream->read(16384);
            $contents = $this->process($chunk);
        }
        return $contents;
    }

    public function read($length)
    {
        $data = null;
        while ($this->buffer->getSize() < $length) {
            if ($chunk = $this->stream->read($length)) {
                if($data = $this->process($chunk)) {
                    $this->buffer->write($data);
                }
            } else {
                return $this->buffer->read($length);
            }
        }
        return $this->buffer->read($length);
    }

    protected function process($chunk)
    {
        $pipes = $this->getPipes();
        $contents = '';
        while ($chunk) {
            $write  = [$pipes[0]];
            $read   = [$pipes[1]];
            $except = null;
            if(stream_select($read, $write, $except, null, 0) > 0) {
                if (isset($read[0]) && $read[0] == $pipes[1]) {
                    $contents .= stream_get_contents($pipes[1]);
                }
                if (isset($write[0]) && $write[0] == $pipes[0]) {
                    fwrite($pipes[0], $chunk);
                    $chunk = false;
                }
            }
        }
        return $contents;
    }

    private function getPipes()
    {
        if (!$this->pipes) {
            $this->pipes = $this->createProcess();
        }
        return $this->pipes;
    }

    private function createProcess()
    {
        $descriptorspec = array(
           0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
           1 => array("pipe", "w"), //$stream,  // stdout is a pipe that the child will write to
           2 => array("file", "/tmp/error-output-pipe.txt", "a") // stderr is a file to write to
        );

        $this->process = proc_open($this->command, $descriptorspec, $pipes);
        if (!is_resource($this->process)) {
            throw new \Exception ("process not created");
        }

        stream_set_blocking($pipes[0], 0);
        stream_set_blocking($pipes[1], 0);

        return $pipes;
    }

    public function close()
    {
        parent::close();
        if (is_resource($this->pipes[0])) {
            fclose($this->pipes[0]);
        }
        if (is_resource($this->pipes[1])) {
            fclose($this->pipes[1]);
        }
        if (is_resource($this->process)) {
            proc_close($this->process);
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}
