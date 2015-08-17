<?php
namespace Kemer\Stream;

use Psr\Http\Message\StreamInterface;
use Zend\Uri\File as FileUri;
use Zend\Uri\UriFactory;

class StreamFactory
{
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
        $uri = UriFactory::factory($url, "file");

        $scheme = ($uri instanceof FileUri) ? "file" : strtolower($uri->getScheme());

        if ($scheme && !isset(static::$factories[$scheme])) {
            throw new \InvalidArgumentException(sprintf(
                'no class registered for scheme "%s"',
                $scheme
            ));
        }

        $class = static::$factories[$scheme];

        $factory = new $class();
        $stream = $factory->createStream($uri);

        if (!$stream && $scheme == "http") {

            $class = static::$factories["stream"];
            $factory = new $class();
            $stream = $factory->createStream($uri);
        }

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
