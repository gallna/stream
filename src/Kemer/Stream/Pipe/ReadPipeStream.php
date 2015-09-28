<?php
namespace Kemer\Stream\Pipe;

use Kemer\Stream\Stream;
use Kemer\Stream\Buffer;
use Psr\Http\Message\StreamInterface;
use Kemer\Stream\Exceptions\ProcessException;

class ReadPipeStream extends Stream
{
    const STATUS_READY = 'ready';
    const STATUS_STARTED = 'started';
    const STATUS_TERMINATED = 'terminated';

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
     * @var bool
     */
    private $status = self::STATUS_READY;

    /**
     * @var array
     */
    private $procStatus;

    /**
     * @param StreamInterface $stream
     * @param Buffer|null $buffer
     */
    public function __construct($command, Buffer\BufferInterface $buffer = null)
    {
        $this->command = $command;
        $this->buffer = $buffer ?: new Buffer\Buffer();
    }


    public function getContents()
    {
        $contents = null;
        while(!$contents) {
            $contents = $this->process();
            if ($this->isTerminated()) {
                throw new ProcessException($this->procStatus('exitcode'));
            }
        }
        return $contents;
    }

    public function read($length)
    {
        $data = null;
        while ($this->buffer->getSize() < $length) {
            if($data = $this->process()) {
                $this->buffer->write($data);
            } elseif ($this->isTerminated()) {
                throw new ProcessException($this->procStatus('exitcode'));
            }
        }
        return $this->buffer->read($length);
    }

    protected function process()
    {
        $pipes = $this->getPipes();
        $write  = null;
        $read   = array($pipes[1]);
        $except = null;
        if(stream_select($read, $write, $except, null, 0) > 0) {
            if (isset($read[0]) && $read[0] == $pipes[1]) {
                return stream_get_contents($pipes[1]);
            }
        }
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
           //0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
           1 => array("pipe", "w"), //$stream,  // stdout is a pipe that the child will write to
           2 => array("file", "/tmp/error-output.txt", "a") // stderr is a file to write to
        );

        $this->process = proc_open($this->command, $descriptorspec, $pipes);
        if (!is_resource($this->process)) {
            throw new \Exception ("process not created");
        }
        $this->status = self::STATUS_STARTED;

        stream_set_blocking($pipes[1], 0);

        return $pipes;
    }

    public function close()
    {
        $this->status = self::STATUS_TERMINATED;

        if (is_resource($this->pipes[1])) {
            fclose($this->pipes[1]);
        }

        if (is_resource($this->process)) {
            proc_close($this->process);
        }
        parent::close();
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * Checks if the process is currently running.
     *
     * @return bool true if the process is currently running, false otherwise
     */
    public function isRunning()
    {
        if ($this->isStarted()) {
            return $this->procStatus('running');
        }
        return false;

    }

    /**
     * Checks if the process has been started with no regard to the current state.
     *
     * @return bool true if status is ready, false otherwise
     */
    public function isStarted()
    {
        return $this->status !== self::STATUS_READY;
    }

    /**
     * Checks if the process is terminated.
     *
     * @return bool true if process is terminated, false otherwise
     */
    public function isTerminated()
    {
        $this->procStatus();
        return $this->status === self::STATUS_TERMINATED;
    }

    /**
     * Updates the status of the process, reads pipes.
     *
     * @param bool $blocking Whether to use a blocking read call.
     */
    protected function procStatus($key = null)
    {
        if (!$this->isStarted()) {
            return;
        }
        if ($this->status === self::STATUS_STARTED) {
            $this->procStatus = proc_get_status($this->process);
            if (!$this->procStatus['running']) {
                $this->close();
            }
        }
        return $key && isset($this->procStatus[$key]) ? $this->procStatus[$key] : null;
    }
}
