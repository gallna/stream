<?php
namespace Kemer\Stream\Exceptions;

/**
 * Socket exception
 */
class SocketException extends \RuntimeException implements SocketExceptionInterface
{
    /**
     * Constructor
     *
     * @param int $code
     */
    public function __construct($code = null)
    {
        if ($code !== null) {
            $code = socket_last_error();
        }
        parent::__construct(socket_strerror($code), $code);
    }

    public function isConnectionClosed()
    {
        return in_array(
            $this->getCode(),
            [static::SUCCESS, static::ECONNRESET, static::EPIPE]
        );
    }
}
