<?php

namespace SdkBase\Utils;

use SdkBase\Exceptions\Http\BadRequestException;
use SdkBase\Exceptions\Http\ConflictException;
use SdkBase\Exceptions\Http\ForbiddenException;
use SdkBase\Exceptions\Http\InternalServerErrorException;
use SdkBase\Exceptions\Http\MethodNotAllowedException;
use SdkBase\Exceptions\Http\NotFoundException;
use SdkBase\Exceptions\Http\UnauthorizedException;
use SdkBase\Exceptions\Validation\UnexpectedResultException;
use SdkBase\Exceptions\Validation\UnexpectedValueException;
use SdkBase\Exceptions\Validation\WorthlessVariableException;

class Curl
{
    private $url = "";
    private $method = CurlMethod::GET;
    private $contentype = CurlContentType::JSON;
    private $postFields = [];

    /**
     * @param string $url
     * @throws WorthlessVariableException
     */
    public function setUrl(string $url): void
    {
        if ($url === "") {
            throw new WorthlessVariableException("Please inform an URL to send Curl.");
        }
        $this->url = $url;
    }

    /**
     * @param string $method
     * @throws UnexpectedValueException
     * @throws WorthlessVariableException
     */
    public function setMethod(string $method): void
    {
        if ($method === "") {
            throw new WorthlessVariableException("Please inform a method to send Curl.");
        } elseif (!CurlMethod::isValid($method)) {
            throw new UnexpectedValueException("Invalid method '$method'.");
        }
        $this->method = $method;
    }

    /**
     * @param string $contentType
     * @throws UnexpectedValueException
     * @throws WorthlessVariableException
     */
    public function setContentType(string $contentType): void
    {
        if ($contentType === "") {
            throw new WorthlessVariableException("Please inform a content type to send Curl.");
        } elseif (!CurlContentType::isValid($contentType)) {
            throw new UnexpectedValueException("Invalid content type '$contentType'.");
        }
        $this->contentype = $contentType;
    }

    /**
     * @param string $name
     * @param $value
     * @throws WorthlessVariableException
     */
    public function addField(string $name, $value): void
    {
        if ($name === "") {
            throw new WorthlessVariableException("Please inform a name to postField before send Curl.");
        }
        $this->postFields[$name] = $value;
    }

    /**
     * @param string $name
     */
    public function unsetField(string $name): void
    {
        if(isset($this->postFields[$name])) {
            unset($this->postFields[$name]);
        }
    }

    /**
     * @param array $postFields
     * @throws WorthlessVariableException
     */
    public function setPostFields(array $postFields): void
    {
        $this->postFields = [];
        foreach ($postFields as $name => $value) {
            $this->addField($name, $value);
        }
    }

    /**
     * @return false|string
     * @throws UnexpectedResultException
     */
    private function getCurlPostFields()
    {
        switch ($this->contentype) {
            case CurlContentType::JSON:
                return json_encode($this->postFields);
                break;
            case CurlContentType::XML:
                return Xml::fromArray($this->postFields);
                break;
            case CurlContentType::HTML:
            case CurlContentType::TEXT:
            case CurlContentType::FORM_URL_ENCODED:
                return http_build_query($this->postFields);
                break;
        }
    }

    /**
     * @return array
     * @throws UnexpectedResultException
     */
    private function getCurlOptions(): array
    {
        $isGetMethod = $this->method === CurlMethod::GET;
        $getFields =
            $isGetMethod && !empty($this->postFields)
                ? "?" . http_build_query($this->postFields)
                : "";
        $opts = [
            CURLOPT_URL => $this->url . $getFields,
            CURLOPT_HTTPHEADER => [
                "Content-Type: {$this->contentype}; charset=UTF-8",
            ],
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => '30',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_CUSTOMREQUEST => $this->method,
            CURLOPT_POST => $this->method === CurlMethod::POST,
        ];
        if (!$isGetMethod && !empty($this->postFields)) {
            $opts[CURLOPT_POSTFIELDS] = $this->getCurlPostFields();
        }
        return $opts;
    }

    /**
     * @return string
     * @throws BadRequestException
     * @throws ConflictException
     * @throws ForbiddenException
     * @throws InternalServerErrorException
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     * @throws UnauthorizedException
     * @throws UnexpectedResultException
     */
    public function send(): string
    {
        $curl = curl_init();
        curl_setopt_array(
            $curl,
            $this->getCurlOptions()
        );
        $result = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        switch ($code) {
            case 200: // OK
            case 201: // CREATED
                return $result;
                break;
            case 204: // NO CONTENT
                return "";
                break;
            case 400:
                throw new BadRequestException($result);
                break;
            case 401:
                throw new UnauthorizedException($result);
                break;
            case 403:
                throw new ForbiddenException($result);
                break;
            case 404:
                throw new NotFoundException($result);
                break;
            case 405:
                throw new MethodNotAllowedException($result);
                break;
            case 409:
                throw new ConflictException($result);
                break;
            case 500:
                throw new InternalServerErrorException($result);
                break;
            default:
                throw new UnexpectedResultException($result);
                break;
        }
    }
}