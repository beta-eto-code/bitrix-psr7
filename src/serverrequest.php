<?php

namespace BitrixPSR7;

use GuzzleHttp\Psr7\UploadedFile;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

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
     * @return $this|Request
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
     * @return array[]|UploadedFile[]|UploadedFileInterface[]
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

    private function getFileList(): array
    {
        $fileList = [];
        foreach ($this->request->getFileList() as $key => $file) {
            foreach ($file as $k => $value) {
                if (is_array($value)) {
                    foreach ($value as $i => $v) {
                        $fileList[$key][$i][$k] = $v;
                    }
                } else {
                    $fileList[$key][$k] = $v;
                }
            }
        }

        return $fileList;
    }

    /**
     * @param array $uploadedFiles
     * @return $this|Request
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
        return $this->request->getPostList()->toArray();
    }

    /**
     * @param array|object|null $data
     * @return $this|Request
     */
    public function withParsedBody($data)
    {
        $newRequest = $this->getClonedRequest();
        $newRequest->getPostList()->setValues($data);

        return new static($newRequest, $this->httpVersion, $this->body, $this->attributes);
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param string $attribute
     * @param null $default
     * @return mixed|null
     */
    public function getAttribute($attribute, $default = null)
    {
        if (false === array_key_exists($attribute, $this->attributes)) {
            return $default;
        }

        return $this->attributes[$attribute];
    }

    /**
     * @param string $attribute
     * @param mixed $value
     * @return ServerRequestInterface
     */
    public function withAttribute($attribute, $value): ServerRequestInterface
    {
        $new = clone $this;
        $new->attributes[$attribute] = $value;

        return $new;
    }

    /**
     * @param string $attribute
     * @return ServerRequestInterface
     */
    public function withoutAttribute($attribute): ServerRequestInterface
    {
        if (false === array_key_exists($attribute, $this->attributes)) {
            return $this;
        }

        $new = clone $this;
        unset($new->attributes[$attribute]);

        return $new;
    }
}