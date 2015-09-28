<?php
namespace Kemer\Stream;

class HttpStream extends Stream
{
    /**
     * Stream constructor
     *
     * @param string $url
     * @param string $method
     * @param array $headers
     */
    public function __construct($url, $method = "GET", array $headers = [])
    {
        $context['method'] = $method;
        if (!empty($headers)) {
            $context['header'] = "";
            foreach ($headers as $name => $value) {
                $context['header'] .= "$name: $value\r\n";
            }
        }
        $context = stream_context_create([
            'http' => $context
        ]);
        $stream = fopen($url, 'rb', false, $context);
        stream_set_blocking($stream, 0);
        parent::__construct($stream);
    }
}
