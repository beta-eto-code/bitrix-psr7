<?php

namespace BitrixPSR7;

use Bitrix\Main\HttpRequest;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

class Request extends Message implements RequestInterface
{
    /**
     * @return string
     * @psalm-suppress UndefinedDocblockClass
     */
    public function getRequestTarget()
    {
        return (string)$this->request->getRequestUri();
    }

    /**
     * @param mixed $requestTarget
     *
     * @return static
     * @psalm-suppress UndefinedDocblockClass
     */
    public function withRequestTarget($requestTarget)
    {
        $newRequest = $this->getClonedRequest();
        $newRequest->getServer()->set('REQUEST_URI', $requestTarget);

        return new static($newRequest, $this->httpVersion, $this->body, $this->attributes);
    }

    /**
     * @return string
     * @psalm-suppress UndefinedDocblockClass
     */
    public function getMethod()
    {
        return (string)$this->request->getRequestMethod();
    }

    /**
     * @param string $method
     *
     * @return static
     * @psalm-suppress UndefinedDocblockClass
     */
    public function withMethod($method)
    {
        $newRequest = $this->getClonedRequest();
        $newRequest->getServer()->set('REQUEST_METHOD', $method);

        return new static($newRequest, $this->httpVersion, $this->body, $this->attributes);
    }

    /**
     * @return UriInterface
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param UriInterface $uri
     * @param bool $preserveHost
     *
     * @return static
     * @psalm-suppress UndefinedDocblockClass
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $newRequest = $this->getClonedRequest();
        $newRequest->getServer()->set('REQUEST_URI', $uri);

        return new static($newRequest, $this->httpVersion, $this->body, $this->attributes);
    }
}
