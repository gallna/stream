<?php
namespace Kemer\Stream\Factory;

use Kemer\Stream\Stream\SocketStream;
use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\Stream;
use Zend\Uri\UriInterface;
use Zend\Http\Headers;

class StreamFactory
{
    /**
     * Create stream from given uri
     *
     * @param Zend\Uri\UriInterface $uri
     * @return Psr\Http\Message\StreamInterface
     */
    public function createStream(UriInterface $uri)
    {
        $options = [];
        if ($mime = get_headers($uri->toString())) {
            array_shift($mime);
            $headers = Headers::fromString(implode("\r\n", $mime));
            $options = $this->getHeaders($headers);
        }
        $socket = $this->createSocket($uri);
        return new SocketStream($socket, $options);
    }

    protected function createSocket(UriInterface $uri)
    {
        /* Create a TCP/IP socket. */
        if (false === ($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP))) {
            throw new \Exception(socket_strerror(socket_last_error()));
        }
        /* Get the IP address for the target host. */
        $ip = gethostbyname($uri->getHost());
        if (false === socket_connect($socket, $ip, $uri->getPort())) {
            throw new \Exception(socket_strerror(socket_last_error($socket)));
        }
        return $socket;
    }


    protected function getHeaders(Headers $headers)
    {
        $options = [];
        $options["content-length"] = $headers->get("Content-Length")
            ? $headers->get("Content-Length")->getFieldValue()
            : null;
        $options["content-type"] = $headers->get("Content-Type")
            ? $headers->get("Content-Type")->getFieldValue()
            : false;
        return $options;
    }
}
