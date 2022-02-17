<?php

namespace BitrixPSR7;

use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\HttpResponse;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Serializable;

class Response implements ResponseInterface, Serializable
{
    public const DEFAULT_HTTP_VERSION = '1.1';

    /**
     * @var HttpResponse
     * @psalm-suppress UndefinedDocblockClass
     */
    private $response;
    /**
     * @var string
     */
    private $httpVersion;
    /**
     * @var mixed
     */
    private $body;

    /**
     * @param HttpResponse $response
     * @param string|null $httpVersion
     * @param mixed $body
     * @psalm-suppress UndefinedDocblockClass, UndefinedClass
     */
    public function __construct(HttpResponse $response, string $httpVersion = null, $body = '')
    {
        $this->response = $response;
        $this->httpVersion = $httpVersion ?? static::DEFAULT_HTTP_VERSION;
        $this->body = $body;
    }

    /**
     * @return string
     */
    public function getProtocolVersion()
    {
        return $this->httpVersion;
    }

    /**
     * @param string $version
     *
     * @return static
     */
    public function withProtocolVersion($version)
    {
        return new static($this->response, $version, $this->body);
    }

    /**
     * @return string[][]
     * @psalm-suppress UndefinedDocblockClass
     */
    public function getHeaders()
    {
        return array_column($this->response->getHeaders()->toArray(), 'values', 'name');
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasHeader($name)
    {
        return !empty($this->getHeader($name));
    }

    /**
     * @param string $name
     * @return string[]
     * @psalm-suppress UndefinedDocblockClass
     */
    public function getHeader($name)
    {
        return $this->response->getHeaders()->get($name, true);
    }

    /**
     * @param string $name
     * @return string
     */
    public function getHeaderLine($name)
    {
        $value = $this->getHeader($name);
        if (empty($value)) {
            return '';
        }

        return $this->implodeHeader($value);
    }

    /**
     * @param mixed $headerValues
     * @return string
     */
    private function implodeHeader($headerValues): string
    {
        if (!is_array($headerValues)) {
            return (string) $headerValues;
        }

        foreach ($headerValues as $i => $value) {
            if (is_array($value)) {
                $headerValues[$i] = $this->implodeHeader($value);
            }
        }

        return implode(',', $headerValues);
    }

    /**
     * @return HttpResponse
     * @psalm-suppress InvalidClone, UndefinedClass
     */
    private function getClonedResponse(): HttpResponse
    {
        return clone $this->response;
    }

    /**
     * @param string $name
     * @param string|string[] $value
     *
     * @return static
     * @psalm-suppress UndefinedClass
     */
    public function withHeader($name, $value)
    {
        $newResponse = $this->getClonedResponse();
        $newResponse->getHeaders()->set($name, $value);
        return new static($newResponse, $this->httpVersion, $this->body);
    }

    /**
     * @param string $name
     * @param string|string[] $value
     *
     * @return static
     */
    public function withAddedHeader($name, $value)
    {
        if ($this->hasHeader($name)) {
            return $this;
        }

        return $this->withHeader($name, $value);
    }

    /**
     * @param string $name
     *
     * @return static
     * @psalm-suppress UndefinedClass
     */
    public function withoutHeader($name)
    {
        if (!$this->hasHeader($name)) {
            return $this;
        }

        $newResponse = $this->getClonedResponse();
        $newResponse->getHeaders()->delete($name);
        return new static($newResponse, $this->httpVersion, $this->body);
    }

    /**
     * @return StreamInterface
     * @psalm-suppress UndefinedDocblockClass
     */
    public function getBody()
    {
        if (!$this->body) {
            $this->body = Utils::streamFor($this->response->getContent());
        }

        return $this->body;
    }

    /**
     * @param StreamInterface $body
     *
     * @return static
     *
     * @throws ArgumentTypeException
     * @psalm-suppress UndefinedDocblockClass, UndefinedClass
     */
    public function withBody(StreamInterface $body)
    {
        $newResponse = $this->getClonedResponse();
        $newResponse->setContent($body);

        return new static($newResponse, $this->httpVersion, $body);
    }

    /**
     * @return int
     * @psalm-suppress UndefinedDocblockClass
     */
    public function getStatusCode()
    {
        preg_match('/(\d+)\s+.*/', $this->response->getStatus(), $match);
        return (int)($match[1] ?? 200);
    }

    /**
     * @param int $code
     * @param string $reasonPhrase
     *
     * @return static
     *
     * @psalm-suppress UndefinedClass
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        $newResponse = $this->getClonedResponse();
        $newResponse->getHeaders()->set('Status', implode(' ', [$code, $reasonPhrase]));
        return new static($newResponse, $this->httpVersion, $this->body);
    }

    /**
     * @return string
     * @psalm-suppress UndefinedDocblockClass
     */
    public function getReasonPhrase()
    {
        preg_match('/\d+\s+(.*)/', $this->response->getStatus(), $match);
        return $match[1] ?? '';
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize([
            'response' => $this->response,
            'http_version' => $this->httpVersion,
            'body' => (string)$this->body,
        ]);
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->response = $data['response'];
        $this->httpVersion = $data['http_version'];
        $this->body = $data['body'];
    }
}
