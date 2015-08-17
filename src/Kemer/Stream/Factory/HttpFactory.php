<?php
namespace Kemer\Stream\Factory;

use Zend\Uri\UriInterface;
use Zend\Http\Client as ZendClient;

class HttpFactory extends AbstractHttpFactory
{
    /**
     * Create stream from given uri
     *
     * @param Zend\Uri\UriInterface $uri
     * @return Psr\Http\Message\StreamInterface
     */
    public function createStream(UriInterface $uri)
    {
        return parent::createStream($uri);
    }

    /**
     * Returns Zend Client
     *
     * @param string $url
     * @return Psr\Http\Message\StreamInterface
     */
    public function getClient(array $options = [], UriInterface $uri = null)
    {
        $options = array_merge([], $options);
        return new ZendClient($uri ? $uri->toString() : null, $options);
    }
}
