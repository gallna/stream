<?php
namespace Kemer\Stream\Factory;

use Zend\Http\Request;
use Zend\Http\Headers;
use Zend\Uri\UriInterface;
use Zend\Http\Client as ZendClient;
use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\StreamInterface;

abstract class AbstractHttpFactory
{
    /**
     * Returns Zend Client
     *
     * @param string $url
     * @return Psr\Http\Message\StreamInterface
     */
    abstract public function getClient(array $options = []);

    /**
     * Create stream from given uri
     *
     * @param Zend\Uri\UriInterface $uri
     * @return Psr\Http\Message\StreamInterface
     */
    public function createStream(UriInterface $uri)
    {
        $response = $this->sendHead($uri);
        if ($response->isSuccess() && $this->isRange($response->getHeaders())) {
            return $this->getZendStream($uri);
        }

        if ($mime = get_headers($uri->toString())) {
            array_shift($mime);
            if (!$this->isRange(Headers::fromString(implode("\r\n", $mime)))) {
                return null;
            }
        }

        // $response = $this->sendRange($uri);
        // if ($response->isSuccess()) {
        //     return $this->getZendStream($uri);
        //     //return new Stream($response->getStream());
        // }
    }

    protected function sendHead(UriInterface $uri)
    {
        $request = new Request();
        $request->setUri($uri);
        $request->setMethod('HEAD');
        $client = $this->getClient();
        return $client->send($request);
    }

    protected function sendRange(UriInterface $uri)
    {
        $request = new Request();
        $request->setUri($uri);
        $request->setMethod('GET');
        $request->getHeaders()->addHeaders([
            'Range' => "bytes=0-999",
        ]);
        $client = $this->getClient();
        return $client->send($request);
    }

    protected function isRange(Headers $headers)
    {
        if ($headers->get("Accept-Ranges")) {
            return true;
        }
    }

    /**
     * Create stream from url using Zend Client
     *
     * @param string $url
     * @return Psr\Http\Message\StreamInterface
     */
    private function getZendStream(UriInterface $uri)
    {
        $request = new Request();
        $request->setUri($uri);
        $request->setMethod('GET');
        $client = $this->getClient(['outputstream' => true], $uri);
        $response = $client->send();
        if ($response->isSuccess()) {
            return new Stream($response->getStream());
        }
    }

    /**
     * Create stream from url using Guzzle Client
     *
     * @param string $url
     * @return Psr\Http\Message\StreamInterface
     */
    private function getGuzzleStream($url)
    {
        $client = new GuzzleClient();
        $promise = $client->requestAsync('GET', $url);
        $promise->then(
            function (ResponseInterface $res) {
                echo $res->getStatusCode() . "\n";
            },
            function (RequestException $e) {
                echo $e->getMessage() . "\n";
                echo $e->getRequest()->getMethod();
            }
        );
        $response = $promise->wait();
        return $response->getBody();
    }
}
