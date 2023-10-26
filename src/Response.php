<?php

namespace BitrixPSR7;

use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\HttpResponse;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Serializable;

class Response implements ResponseInterface, Serializable
{
    public const DEFAULT_HTTP_VERSION = '1.1';

    private HttpResponse $response;
    private string $httpVersion;
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
    public function getProtocolVersion(): string
    {
        return $this->httpVersion;
    }

    /**
     * @param string $version
     * @return MessageInterface
     * @psalm-suppress LessSpecificImplementedReturnType
     */
    public function withProtocolVersion(string $version): MessageInterface
    {
        return new Response($this->response, $version, $this->body);
    }

    public function getHeaders(): array
    {
        return array_column($this->response->getHeaders()->toArray(), 'values', 'name');
    }

    public function hasHeader(string $name): bool
    {
        return !empty($this->getHeader($name));
    }

    public function getHeader(string $name): array
    {
        return $this->response->getHeaders()->get($name, true);
    }

    public function getHeaderLine(string $name): string
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

    private function getClonedResponse(): HttpResponse
    {
        /**
         * @psalm-suppress InvalidClone
         */
        return clone $this->response;
    }

    /**
     * @param string $name
     * @param string|string[] $value
     * @return MessageInterface
     * @psalm-suppress LessSpecificImplementedReturnType
     */
    public function withHeader(string $name, $value): MessageInterface
    {
        $newResponse = $this->getClonedResponse();
        $newResponse->getHeaders()->set($name, $value);
        return new Response($newResponse, $this->httpVersion, $this->body);
    }

    /**
     * @param string $name
     * @param string|string[] $value
     * @return MessageInterface
     * @psalm-suppress LessSpecificImplementedReturnType
     */
    public function withAddedHeader(string $name, $value): MessageInterface
    {
        if ($this->hasHeader($name)) {
            return $this;
        }

        return $this->withHeader($name, $value);
    }

    /**
     * @param string $name
     * @return MessageInterface
     * @psalm-suppress LessSpecificImplementedReturnType
     */
    public function withoutHeader(string $name): MessageInterface
    {
        if (!$this->hasHeader($name)) {
            return $this;
        }

        $newResponse = $this->getClonedResponse();
        $newResponse->getHeaders()->delete($name);
        return new Response($newResponse, $this->httpVersion, $this->body);
    }

    public function getBody(): StreamInterface
    {
        if (!$this->body) {
            $this->body = Utils::streamFor($this->response->getContent());
        }

        return $this->body;
    }

    /**
     * @param StreamInterface $body
     * @return Response
     * @psalm-suppress LessSpecificImplementedReturnType
     */
    public function withBody(StreamInterface $body): MessageInterface
    {
        $newResponse = $this->getClonedResponse();
        $newResponse->setContent($body);

        return new Response($newResponse, $this->httpVersion, $body);
    }

    /**
     * @return int
     * @psalm-suppress UndefinedDocblockClass
     */
    public function getStatusCode(): int
    {
        preg_match('/(\d+)\s+.*/', $this->response->getStatus(), $match);
        return (int)($match[1] ?? 200);
    }

    /**
     * @param int $code
     * @param string $reasonPhrase
     * @return Response
     * @psalm-suppress LessSpecificImplementedReturnType
     */
    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        $newResponse = $this->getClonedResponse();
        $newResponse->getHeaders()->set('Status', implode(' ', [$code, $reasonPhrase]));
        return new Response($newResponse, $this->httpVersion, $this->body);
    }

    /**
     * @return string
     * @psalm-suppress UndefinedDocblockClass
     */
    public function getReasonPhrase(): string
    {
        preg_match('/\d+\s+(.*)/', $this->response->getStatus(), $match);
        return $match[1] ?? '';
    }

    public function serialize(): string
    {
        return serialize([
            'response' => $this->response,
            'http_version' => $this->httpVersion,
            'body' => (string)$this->body,
        ]);
    }

    /**
     * @param string $data
     */
    public function unserialize($data): void
    {
        $data = unserialize($data);
        $this->response = $data['response'];
        $this->httpVersion = $data['http_version'];
        $this->body = $data['body'];
    }
}
