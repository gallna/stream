<?php
namespace Kemer\Stream;

use Zend\Http\Client;
use Zend\Http\Request;
use Zend\Uri\File as FileUri;
use Zend\Stdlib\ResponseInterface;
use Zend\Uri\UriFactory;
use Zend\Uri\UriInterface;
use Zend\Http\Headers;
use Psr\Http\Message\StreamInterface;

class Factory
{
    private $streamFactory;
    private $handlerFactory;

    /**
     * @param StreamFactory $streamFactory
     * @param HandlerFactory $handlerFactory
     */
    public function __construct(StreamFactory $streamFactory, HandlerFactory $handlerFactory)
    {
        $this->streamFactory = $streamFactory;
        $this->handlerFactory = $handlerFactory;
    }

    /**
     * Create stream from given uri
     *
     * @param Zend\Uri\UriInterface $uri
     * @return Psr\Http\Message\StreamInterface
     */
    public function create($url)
    {
        $stream = $this->streamFactory->createStream($url);
        if ($stream instanceof StreamInterface) {
            $handler = $this->handlerFactory->createHandler($url, $stream);
            return $handler;
        }
        //$handler = $this->handlerFactory->createHandler($url);
        //return $handler = $this->createHandler($url);
    }

    public function createHandler($url)
    {
        $uri = UriFactory::factory($url);
        if ($uri instanceof FileUri) {
            return $handler = new PartialHandler();
        }
        $response = $this->sendHead($uri);
        if ($response->isSuccess()) {
            return $this->fromHeaders($response->getHeaders());
        }
        if ($mime = get_headers($uri->toString())) {
            array_shift($mime);
            return $this->fromHeaders(Headers::fromString(implode("\r\n", $mime)));
        }
        $response = $this->sendRange($uri);
        if ($response->isSuccess()) {
            return $this->getHandler($response);
        }
        return new Handler\StreamHandler();
    }

    protected function sendHead(UriInterface $uri)
    {
        $request = new Request();
        $request->setUri($uri);
        $request->setMethod('HEAD');
        $client = new Client(null, [
           //'adapter' => 'Zend\Http\Client\Adapter\Curl'
           'sslcapath' => '/etc/ssl/certs',
        ]);
        return $response = $client->send($request);
    }

    protected function sendRange(UriInterface $uri)
    {
        $request = new Request();
        $request->setUri($uri);
        $request->setMethod('GET');
        $request->getHeaders()->addHeaders([
            'Range' => "bytes=0-999",
        ]);
        $client = new Client(null, [
           //'adapter' => 'Zend\Http\Client\Adapter\Curl'
           'sslcapath' => '/etc/ssl/certs',
        ]);
        return $response = $client->send($request);
    }

    protected function fromHeaders(Headers $headers)
    {
        if ($headers->get("Accept-Ranges")) {
            return new Handler\PartialHandler();
        }
        $contentLength = $headers->get("Content-Length")
            ? $headers->get("Content-Length")->getFieldValue()
            : false;
        if ($contentLength > 0) {
            return new Handler\FullHandler();
        }
        $contentType = $headers->get("Content-Type")
            ? $headers->get("Content-Type")->getFieldValue()
            : false;
        if ($contentType == 'application/octet-stream') {
            return new Handler\StreamHandler();
        }
        var_dump($contentLength, $contentType);
    }

    /**
     * Registered scheme-specific classes
     *
     * @var array
     */
     protected static $factories = [
        'http'   => 'Kemer\Stream\Factory\HttpFactory',
        'https'  => 'Kemer\Stream\Factory\HttpsFactory',
        'file'   => 'Kemer\Stream\Factory\FileFactory',
        'stream'   => 'Kemer\Stream\Factory\StreamFactory',
    ];

    /**
     * Create stream from given uri
     *
     * @param string $url
     * @return Psr\Http\Message\StreamInterface
     */
    public function createStream($url)
    {
        $uri = UriFactory::factory($url);
        $scheme = strtolower($uri->getScheme());
        if ($scheme && ! isset(static::$factories[$scheme])) {
            throw new \InvalidArgumentException(sprintf(
                'no class registered for scheme "%s"',
                $scheme
            ));
        }
        $class = static::$factories[$scheme];
        $factory = new $class();
        $stream = $factory->createStream($uri);
        if ($stream instanceof StreamInterface) {
            return $stream;
        }
        throw new \Exception(
            sprintf(
                "The stream for '%s' should implement StreamInterface, %s created",
                $url,
                gettype($stream)
            )
        );
    }

}
