<?php

namespace Tale\Http;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class Response extends MessageBase implements ResponseInterface
{

    const DEFAULT_STATUS_CODE = StatusCode::OK;

    private $_statusCode;
    private $_reasonPhrase;

    public function __construct(
        StreamInterface $body = null,
        $statusCode = null,
        array $headers = null,
        $reasonPhrase = null,
        $protocolVersion = null
    )
    {
        parent::__construct($body, $headers, $protocolVersion);

        $this->_statusCode = $statusCode !== null
                           ? $this->_filterStatusCode($statusCode)
                           : self::DEFAULT_STATUS_CODE;
        $this->_reasonPhrase = !empty($reasonPhrase)
                             ? $reasonPhrase
                             : null;
    }

    /**
     * @inheritDoc
     */
    public function getStatusCode()
    {

        return $this->_statusCode;
    }

    /**
     * @inheritDoc
     */
    public function withStatus($code, $reasonPhrase = '')
    {

        $response = clone $this;
        $response->_statusCode = $this->_filterStatusCode($code);

        if (!empty($reasonPhrase))
            $response->_reasonPhrase = $reasonPhrase;

        return $response;
    }

    /**
     * @inheritDoc
     */
    public function getReasonPhrase()
    {

        if (empty($this->_reasonPhrase))
            return StatusCode::getReasonPhrase($this->_statusCode);

        return $this->_reasonPhrase;
    }

    public function emit()
    {

        header($this->getInitialHeaderLine(), true, $this->getStatusCode());
        foreach ($this->getHeaders() as $name => $value) {

            header("$name: ".implode(',', $value));
        }

        $body = $this->getBody();
        echo $body;
        $body->rewind();

        return $this;
    }

    private function _filterStatusCode($code)
    {

        if (is_string($code) && is_numeric($code))
            $code = intval($code);

        if (is_string($code) && defined(StatusCode::class."::$code"))
            $code = constant(StatusCode::class."::$code");

        if (!is_int($code))
            throw new InvalidArgumentException(
                "StatusCode needs to be an integer, numeric string or a name"
                ." of a ".StatusCode::class." constant"
            );

        if ($code < 100 || $code > 599)
            throw new InvalidArgumentException(
                "StatusCode needs to be a valid HTTP status code."
                ." It's usually a number between 100 and 600"
            );

        return $code;
    }

    protected function getInitialHeaderLine()
    {

        return implode(' ', [
            $this->getProtocol(),
            $this->getStatusCode(),
            $this->getReasonPhrase()
        ]);
    }
}