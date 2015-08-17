<?php
namespace Kemer\Stream\Factory;

use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\Stream;
use Zend\Uri\UriInterface;

class FileFactory
{
    /**
     * Create stream from given uri
     *
     * @param Zend\Uri\UriInterface $uri
     * @return Psr\Http\Message\StreamInterface
     */
    public function createStream(UriInterface $uri)
    {
        $path = $uri->getPath();
        if (!is_file($path)) {
            throw new \Exception("Couldn't create stream from '$uri'");
        }
        $resource = fopen($path, "rb");
        return new Stream(
            $resource,
            ['metadata' => ["content-type" => finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path)]]
        );
    }
}
