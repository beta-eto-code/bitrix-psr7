<?php

namespace BitrixPSR7;

use Bitrix\Main\HttpRequest;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class Message implements MessageInterface
{
    public const DEFAULT_HTTP_VERSION = '1.1';

    protected HttpRequest $request;

    protected ?string $httpVersion;
    protected mixed $body;
    protected UriInterface $uri;
    protected array $attributes;
    protected bool $isCloned;

    /**
     * @param HttpRequest $request
     * @param string|null $httpVersion
     * @param mixed $body
     * @param array $attributes
     * @param bool $isCloned
     * @psalm-suppress UndefinedDocblockClass, UndefinedClass
     */
    final public function __construct(
        HttpRequest $request,
        string $httpVersion = null,
        mixed $body = null,
        array $attributes = [],
        bool $isCloned = false
    ) {
        $this->request = $request;
        $this->httpVersion = $httpVersion;
        $this->body = $body;
        if (empty($this->body) && $this->needCheckBody($request)) {
            $rawInput = fopen('php://input', 'r');
            $tempStream = fopen('php://temp', 'r+');
            stream_copy_to_stream($rawInput, $tempStream);
            rewind($tempStream);
            $this->body = Utils::streamFor($tempStream);
        }
        $this->uri = new Uri($this->getCurrentLink());
        $this->attributes = $attributes;
        $this->isCloned = $isCloned;
    }

    /**
     * @param HttpRequest $request
     * @return bool
     * @psalm-suppress UndefinedDocblockClass, UndefinedClass
     */
    private function needCheckBody(HttpRequest $request): bool
    {
        $method = strtolower($request->getRequestMethod());
        return in_array($method, ['post', 'put']);
    }

    /**
     * @return string
     * @psalm-suppress UndefinedDocblockClass
     */
    private function getCurrentLink(): string
    {
        $server = $this->request->getServer();
        return ($server->get('HTTPS') === 'on' ? "https" : "http") .
            "://" .
            $server->get('HTTP_HOST') .
            $server->get('REQUEST_URI');
    }

    /**
     * @return string
     * @psalm-suppress UndefinedDocblockClass
     */
    public function getProtocolVersion(): string
    {
        if (!empty($this->httpVersion)) {
            return $this->httpVersion;
        }

        $version = $this->request->getServer()->get('SERVER_PROTOCOL') ?? static::DEFAULT_HTTP_VERSION;
        return $this->httpVersion = str_replace(['HTTP', '/'], '', $version);
    }


    /**
     * @param string $version
     *
     * @return static
     */
    public function withProtocolVersion(string $version): MessageInterface
    {
        return new static($this->request, $version, $this->body, $this->attributes);
    }

    /**
     * @return string[][]
     * @psalm-suppress UndefinedDocblockClass
     */
    public function getHeaders(): array
    {
        $headers = $this->request->getHeaders()->toArray();
        foreach ($headers as &$value) {
            $value = (array)($value ?? []);
        }
        unset($value);

        return $headers;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasHeader(string $name): bool
    {
        return !empty($this->getHeader($name));
    }

    /**
     * @param string $name
     * @return string[]
     * @psalm-suppress UndefinedDocblockClass
     */
    public function getHeader(string $name): array
    {
        return (array)($this->request->getHeader($name) ?? []);
    }

    /**
     * @param string $name
     * @return string
     */
    public function getHeaderLine(string $name): string
    {
        $value = $this->getHeader($name);
        if (empty($value)) {
            return '';
        }

        return implode(',', $value);
    }

    /**
     * @param string $name
     * @param string|string[] $value
     *
     * @return static
     * @psalm-suppress UndefinedDocblockClass, UndefinedClass
     */
    public function withHeader(string $name, $value): MessageInterface
    {
        $newRequest = $this->getClonedRequest();
        $newRequest->getHeaders()->add($name, $value);
        return new static($newRequest, $this->httpVersion, $this->body, $this->attributes);
    }

    /**
     * @param string $name
     * @param string|string[] $value
     *
     * @return static
     * @psalm-suppress UndefinedDocblockClass
     */
    public function withAddedHeader(string $name, $value): MessageInterface
    {
        if ($this->hasHeader($name)) {
            return $this;
        }

        $newRequest = $this->getClonedRequest();
        $newRequest->getHeaders()->add($name, $value);

        return new static($newRequest, $this->httpVersion, $this->body, $this->attributes);
    }

    /**
     * @param string $name
     *
     * @return static
     * @psalm-suppress UndefinedDocblockClass
     */
    public function withoutHeader(string $name): MessageInterface
    {
        if (!$this->hasHeader($name)) {
            return $this;
        }

        $newRequest = $this->getClonedRequest();
        $newRequest->getHeaders()->delete($name);

        return new static($newRequest, $this->httpVersion, $this->body, $this->attributes);
    }

    /**
     * @return StreamInterface
     */
    public function getBody(): StreamInterface
    {
        if (!$this->body) {
            $this->body = Utils::streamFor('');
        }

        return $this->body;
    }

    /**
     * @param StreamInterface $body
     *
     * @return static
     */
    public function withBody(StreamInterface $body): MessageInterface
    {
        if ($body === $this->body) {
            return $this;
        }

        return new static($this->request, $this->httpVersion, $body, $this->attributes);
    }

    /**
     * @return HttpRequest
     * @psalm-suppress UndefinedDocblockClass, InvalidClone, UndefinedClass
     */
    protected function getClonedRequest(): HttpRequest
    {
        return clone $this->request;
    }
}
