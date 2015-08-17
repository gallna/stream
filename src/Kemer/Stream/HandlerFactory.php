<?php
namespace Kemer\Stream;

use Kemer\Stream\Handler;
use Zend\Http\Client;
use Zend\Http\Request;
use Zend\Uri\File as FileUri;
use Zend\Uri\UriFactory;
use Zend\Uri\UriInterface;
use Psr\Http\Message\StreamInterface;

class HandlerFactory
{
    public function createHandler($url, StreamInterface $stream)
    {
        // $uri = UriFactory::factory($url);
        if ($stream->isSeekable()) {
            return new Handler\PartialHandler($stream);
        }
        return new Handler\BufferHandler($stream);
    }
}
