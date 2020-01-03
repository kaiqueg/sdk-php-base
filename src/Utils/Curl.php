<?php

namespace SdkBase\Utils;

use Exception;
use SdkBase\Exceptions\Http\BadRequestException;
use SdkBase\Exceptions\Http\ConflictException;
use SdkBase\Exceptions\Http\ForbiddenException;
use SdkBase\Exceptions\Http\GatewayTimeoutException;
use SdkBase\Exceptions\Http\InternalServerErrorException;
use SdkBase\Exceptions\Http\MethodNotAllowedException;
use SdkBase\Exceptions\Http\NotFoundException;
use SdkBase\Exceptions\Http\NotImplementedException;
use SdkBase\Exceptions\Http\ServiceUnavailableException;
use SdkBase\Exceptions\Http\TooManyRequestsException;
use SdkBase\Exceptions\Http\UnauthorizedException;
use SdkBase\Exceptions\Http\UnavailableForLegalReasonsException;
use SdkBase\Exceptions\Validation\UnexpectedResultException;
use SdkBase\Exceptions\Validation\UnexpectedValueException;
use SdkBase\Exceptions\Validation\WorthlessVariableException;

class Curl
{
    private $url = "";
    private $method = CurlMethod::GET;
    private $contentype = CurlContentType::JSON;
    private $headers = [];
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
     * @param string $header
     * @throws UnexpectedValueException
     */
    public function addHeader(string $header): void
    {
        if(strpos(strtolower($header), "content-type") !== false) {
            throw new UnexpectedValueException("If you want define content-type header, use Curl->setContentType method.");
        }
        $this->headers[] = $header;
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
        $httpHeader = $this->headers;
        $httpHeader[] = "Content-Type: {$this->contentype}; charset=UTF-8";
        $opts = [
            CURLOPT_URL => $this->url . $getFields,
            CURLOPT_HTTPHEADER => $httpHeader,
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
     * @param int $attemptTimes
     * @param Exception $exception
     * @return string
     * @throws Exception
     */
    private function tryAgain(int $attemptTimes, Exception $exception): string
    {
        if($attemptTimes <= 1) {
            throw $exception;
        }
        sleep(2);
        return $this->send(--$attemptTimes);
    }

    /**
     * @param int $attemptTimes
     * @return string
     * @throws BadRequestException
     * @throws ConflictException
     * @throws ForbiddenException
     * @throws InternalServerErrorException
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     * @throws NotImplementedException
     * @throws UnauthorizedException
     * @throws UnavailableForLegalReasonsException
     * @throws UnexpectedResultException
     * @throws TooManyRequestsException
     * @throws ServiceUnavailableException
     * @throws GatewayTimeoutException
     * @throws Exception
     */
    public function send(int $attemptTimes = 1): string
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
            case 429:
                return $this->tryAgain($attemptTimes, new TooManyRequestsException($result));
                break;
            case 451:
                throw new UnavailableForLegalReasonsException($result);
                break;
            case 500:
                throw new InternalServerErrorException($result);
                break;
            case 501:
                throw new NotImplementedException($result);
                break;
            case 503:
                return $this->tryAgain($attemptTimes, new ServiceUnavailableException($result));
                break;
            case 504:
                return $this->tryAgain($attemptTimes, new GatewayTimeoutException($result));
                break;
            default:
                throw new UnexpectedResultException($result);
                break;
        }
    }
}
