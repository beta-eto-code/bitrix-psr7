<?php

namespace BitrixPSR7;

use GuzzleHttp\Psr7\UploadedFile;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * @psalm-suppress UndefinedDocblockClass
 */
class ServerRequest extends Request implements ServerRequestInterface
{
    /**
     * @return array
     */
    public function getServerParams(): array
    {
        return $this->request->getServer()->toArray();
    }

    /**
     * @return array
     */
    public function getCookieParams(): array
    {
        return $this->request->getCookieList()->toArray();
    }

    /**
     * @param array $cookies
     *
     * @return static
     */
    public function withCookieParams(array $cookies)
    {
        $newRequest = $this->getClonedRequest();
        $newRequest->getCookieList()->setValues($cookies);

        return new static($newRequest, $this->httpVersion, $this->body, $this->attributes);
    }

    public function getQueryParams(): array
    {
        return $this->request->getQueryList()->toArray();
    }

    public function withQueryParams(array $query): ServerRequest
    {
        $newRequest = $this->getClonedRequest();
        $newRequest->getQueryList()->setValues($query);

        return new static($newRequest, $this->httpVersion, $this->body, $this->attributes);
    }

    /**
     * @return array[]|UploadedFile[] (UploadedFile|UploadedFile[])[] (UploadedFile|UploadedFile[])[]
     *
     */
    public function getUploadedFiles()
    {
        return array_map(function (array $file) {
            if (is_array($file['tmp_name'])) {
                $result = [];
                for ($i = 0; $i < count($file['tmp_name']); $i++) {
                    $result[$i] = new UploadedFile(
                        $file['tmp_name'][$i],
                        (int)$file['size'][$i],
                        (int)$file['error'][$i],
                        $file['name'][$i],
                        $file['type'][$i]
                    );
                }

                return $result;
            }
            return new UploadedFile(
                $file['tmp_name'],
                (int) $file['size'],
                (int) $file['error'],
                $file['name'],
                $file['type']
            );
        }, $this->request->getFileList()->toArray());
    }

    /**
     * @param array $uploadedFiles
     *
     * @return static
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        $newRequest = $this->getClonedRequest();
        $newRequest->getFileList()->setValues($uploadedFiles);

        return new static($newRequest, $this->httpVersion, $this->body, $this->attributes);
    }

    /**
     * @return array|object|null
     */
    public function getParsedBody()
    {
        if ($this->isCloned) {
            return $this->request->getPostList()->toArray();
        }

        $contentType = $this->getHeaderLine('Content-type');
        if (strpos($contentType, 'application/json') === 0) {
            return json_decode($this->body, true) ?? [];
        }

        return $this->request->getPostList()->toArray();
    }

    /**
     * @param array|object|null $data
     *
     * @return static
     */
    public function withParsedBody($data)
    {
        $newRequest = $this->getClonedRequest();
        $newRequest->getPostList()->setValues($data);

        return new static($newRequest, $this->httpVersion, $this->body, $this->attributes, true);
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed|null
     */
    public function getAttribute($name, $default = null)
    {
        if (false === array_key_exists($name, $this->attributes)) {
            return $default;
        }

        return $this->attributes[$name];
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return static
     */
    public function withAttribute($name, $value): ServerRequestInterface
    {
        $new = clone $this;
        $new->attributes[$name] = $value;

        return $new;
    }

    /**
     * @param string $name
     *
     * @return static
     */
    public function withoutAttribute($name): ServerRequestInterface
    {
        if (false === array_key_exists($name, $this->attributes)) {
            return $this;
        }

        $new = clone $this;
        unset($new->attributes[$name]);

        return $new;
    }
}
