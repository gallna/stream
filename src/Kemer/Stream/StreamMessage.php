<?php
namespace Kemer\Stream;

use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\StreamInterface;
use Zend\Http\Request;
use Zend\Http\Response;

class StreamMessage
{
    private $path = "";
    private $stream;
    private $buffer = 102400;
    private $start  = -1;
    private $end    = -1;
    private $size   = 0;
    private $contentType = "application/octet-stream";

    public $streamBuffer;
    function __construct(StreamInterface $stream)
    {
        $this->stream = $stream;
        $this->theBuffer = tmpfile();
    }

    /**
     * Open stream
     */
    private function open()
    {
        if (!($this->stream = fopen($this->path, 'rb'))) {
            die('Could not open stream for reading');
        }

    }

    public function getContentType()
    {
        return $this->contentType;
    }

    public function createResponse(Request $request)
    {
        $response = new Response();
        $response->getHeaders()->addHeaders([
            'Content-Type' => $this->getContentType(),
            "Cache-Control" => "no-cache",
            //'Cache-Control' => 'max-age=2592000, public',
            //"Expires" => gmdate('D, d M Y H:i:s', time()+2592000) . ' GMT',
            //"Last-Modified" => gmdate('D, d M Y H:i:s', strtotime("today")) . ' GMT',
            //'Accept-Ranges' => sprintf("%d-%d", 0, $this->stream->getSize()),
            //'Accept-Ranges' => 'bytes',
        ]);
        return $response;
    }

    public function getPartialResponse(Request $request, $start, $end)
    {
        $response = $this->createResponse($request);
        $response->setStatusCode(Response::STATUS_CODE_206);
        if ($end > $this->stream->getSize()) {
            $end = $this->stream->getSize();
        };

        $response->getHeaders()->addHeaders([
            'Content-Length' => $length = $end - $start,
            'Content-Range' => sprintf("bytes %d-%d/%d", $start, $end - 1, $this->stream->getSize()),
        ]);
        $this->conn->write($response->toString());
        $this->getContent($start, $length);

        return $response;
    }
    private $sent = 0;
    public function getResponse(Request $request, $start = 0)
    {
        $response = $this->createResponse($request);
        $response->setVersion(Response::VERSION_10);
        $response->setStatusCode(Response::STATUS_CODE_200);

        $response->getHeaders()->addHeaders([
            "Content-Transfer-Encoding" => "binary",
            "Connection" =>  "keep-alive",
            //'Content-Length' => $this->buffer,
            //'Content-Range' => sprintf("bytes %d-%d/%d", $start, $start + $this->buffer - 1, $this->buffer * 500),
        ]);
        $this->buffer = 102400 * 100;
        var_dump("full");
        $this->sent += $this->buffer;
        e($response->toString(), "green");
        $response->setContent($this->stream->read($this->buffer));
        //$this->conn->write($response->toString());
        $this->conn->end($response->toString());
        return $response;
        $size = 0;
        while(1) {



            if (!$this->stream->eof()) {
                $this->conn->write($data = $this->stream->read($this->buffer));
                var_dump($data);
            }
        }
        var_dump("end");
        //$this->getNoSeekContent($start, $this->buffer);

        return $response;
    }

    public function get416Error(Request $request)
    {
        $response = $this->createResponse($request);
        $response->setStatusCode(Response::STATUS_CODE_416);
            $response->getHeaders()->addHeaders([
                'Content-Length' => $length = $this->stream->getSize()
            ]);

        var_dump("erro");
        $this->conn->write($response->toString());


        return $response;
    }

    /**
     * perform the streaming of calculated range
     */
    private function getContent($start, $length)
    {
        $this->stream->seek($start);
        $sent = 0;
        $buffer = $this->buffer;
        while(!$this->stream->eof() && $sent <= $length) {
            $data = $this->stream->read($buffer);
            $this->conn->write($data);
            $sent += $buffer;
        }
    }
    private $theBuffer = '';
    /**
     * perform the streaming of calculated range
     */
    private function getNoSeekContent($start, $length)
    {

        // $stats = fstat($this->theBuffer);
        // if (isset($stats['size'])) {
        //     $size = $stats['size'];
        // }
        echo $size = 0;
        $sent = 0;
        $buffer = $this->buffer;
        var_dump($size < ($start + $length));
        $data = '';
        while(!$this->stream->eof() && $size < $length) {
            //sleep(1);
            $data .= $this->stream->read(1);
            $size += strlen($data);
            //fwrite($this->theBuffer, $data);
        }
        echo "done";
        //fseek($this->theBuffer, $start);
        //$data = fread($this->theBuffer, $length);
        $this->conn->write($data);
        //$this->getNoSeekContent($start, $length);
        //var_dump("response sent", $start, $length);
    }
    private $conn;
    public function handle(Request $request, $conn)
    {
        $this->conn = $conn;
        // $path = rawurldecode($request->getUri()->getPath());
        // var_dump($path);
        // $this->stream = $this->getStream($path);
        // $this->contentType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
        if(!$this->stream->isSeekable()) {
            if ($request->getHeader("Range")) {

                list(, $range) = explode('=', $request->getHeader("range")->getFieldValue(), 2);
                list($start, $end) = explode('-', $range);
                var_dump($start, $end);
                if (is_numeric($end) || $start != 0) {
                    $end = is_numeric($end) ? $end : $start + $this->buffer;
                    $response = $this->getResponse($request, $start);
                } else {
                    //$response = $this->get416Error($request);
                    $response = $this->getResponse($request, 0, $this->buffer);
                    //$response = $this->getResponse($request, true);
                }
            } else {
                $response = $this->getResponse($request);
            }
            return $response;

        }
        if ($request->getHeader("Range")) {
            list(, $range) = explode('=', $request->getHeader("range")->getFieldValue(), 2);
            list($start, $end) = explode('-', $range);
            if (is_numeric($end) || $start != 0) {
                $end = is_numeric($end) ? $end : $start + $this->buffer * 5;
                $response = $this->getPartialResponse($request, $start, $end);
            } else {
                //$response = $this->get416Error($request);
                $response = $this->getPartialResponse($request, 0, $this->buffer);
                //$response = $this->getResponse($request, true);
            }

        } else {
            $response = $this->getPartialResponse($request, 0, $this->buffer);
            //$response = $this->get416Error($request);
            //$response = $this->getResponse($request);
        }

        return $response;
    }

    private function getStream($path)
    {
        if (!isset($this->streams[$path])) {
            $this->streams[$path] = $this->createStream($path);
        }
        return $this->streams[$path];
    }

    private function createStream($path)
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new \InvalidArgumentException(
                sprintf("The provided path '%s' is not a file", $path)
            );
        }
        if (!is_readable($path)) {
            throw new \LogicException(
                sprintf("The file '%s' is not readable", $path)
            );
        }
        return $stream = new Stream(fopen($path, "r"));
    }

    public function createRespoasdnse()
    {
        $response = new Response();
        $response->setStatusCode(Response::STATUS_CODE_200);
        $response->getHeaders()->addHeaders([
            'Content-Type' => $this->getContentType(),
            'Cache-Control' => 'max-age=2592000, public',
            "Expires" => gmdate('D, d M Y H:i:s', time()+2592000) . ' GMT',
            "Last-Modified" => gmdate('D, d M Y H:i:s', strtotime("today")) . ' GMT',
            'Header' => '',
            'Accept-Ranges' => sprintf("%d-%d", 0, $this->stream->getSize()),
            'Header' => $this->getContentType(),
        ]);

        $response->setStatusCode(Response::STATUS_CODE_206);
        header("Content-Length: ".$length);
        header("Content-Range: bytes $this->start-$this->end/".$this->size);
        return $response->toString();
    }

    /**
     * Set proper header to serve the video content
     */
    private function setHeader()
    {

        ob_get_clean();
        header("Content-Type: video/mp4");
        header("Cache-Control: max-age=2592000, public");
        header("Expires: ".gmdate('D, d M Y H:i:s', time()+2592000) . ' GMT');
        header("Last-Modified: ".gmdate('D, d M Y H:i:s', @filemtime($this->path)) . ' GMT' );
        $this->start = 0;
        $this->size  = filesize($this->path);
        $this->end   = $this->size - 1;
        header("Accept-Ranges: 0-".$this->end);

        if (isset($_SERVER['HTTP_RANGE'])) {

            $c_start = $this->start;
            $c_end = $this->end;

            list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
            if (strpos($range, ',') !== false) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $this->start-$this->end/$this->size");
                exit;
            }
            if ($range == '-') {
                $c_start = $this->size - substr($range, 1);
            }else{
                $range = explode('-', $range);
                $c_start = $range[0];

                $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $c_end;
            }
            $c_end = ($c_end > $this->end) ? $this->end : $c_end;
            if ($c_start > $c_end || $c_start > $this->size - 1 || $c_end >= $this->size) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $this->start-$this->end/$this->size");
                exit;
            }
            $this->start = $c_start;
            $this->end = $c_end;
            $length = $this->end - $this->start + 1;
            fseek($this->stream, $this->start);
            header('HTTP/1.1 206 Partial Content');
            header("Content-Length: ".$length);
            header("Content-Range: bytes $this->start-$this->end/".$this->size);
        }
        else
        {
            header("Content-Length: ".$this->size);
        }

    }

    /**
     * close curretly opened stream
     */
    private function end()
    {
        fclose($this->stream);
        exit;
    }

    /**
     * perform the streaming of calculated range
     */
    private function stream()
    {
        $i = $this->start;
        set_time_limit(0);
        while(!feof($this->stream) && $i <= $this->end) {
            $bytesToRead = $this->buffer;
            if(($i+$bytesToRead) > $this->end) {
                $bytesToRead = $this->end - $i + 1;
            }
            $data = fread($this->stream, $bytesToRead);
            echo $data;
            flush();
            $i += $bytesToRead;
        }
    }

    /**
     * Start streaming video content
     */
    function start()
    {
        $this->open();
        $this->setHeader();
        $this->stream();
        $this->end();
    }
}
