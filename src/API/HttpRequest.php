<?php

namespace SdkBase\API;

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
use SdkBase\Utils\Curl;
use SdkBase\Utils\CurlContentType;
use SdkBase\Utils\CurlMethod;

abstract class HttpRequest
{
    abstract protected function getAuthorizationHeader(): array;
    /**
     * @param string $url
     * @param array $postFields
     * @param string $contentType
     * @return Curl
     * @throws UnexpectedValueException
     * @throws WorthlessVariableException
     */
    private function curlInit(string $url, array $postFields, string $contentType): Curl
    {
        $curl = new Curl();
        $curl->setContentType($contentType);
        $curl->setPostFields($postFields);
        $curl->setUrl($url);
        $headers = $this->getAuthorizationHeader();
        if(!empty($headers)) {
            foreach($headers as $header) {
                $curl->addHeader($header);
            }
        }
        return $curl;
    }

    /**
     * @param string $url
     * @param array $postFields
     * @param string $contentType
     * @return string
     * @throws BadRequestException
     * @throws ConflictException
     * @throws ForbiddenException
     * @throws InternalServerErrorException
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     * @throws UnauthorizedException
     * @throws UnexpectedResultException
     * @throws UnexpectedValueException
     * @throws WorthlessVariableException
     * @throws GatewayTimeoutException
     * @throws NotImplementedException
     * @throws ServiceUnavailableException
     * @throws TooManyRequestsException
     * @throws UnavailableForLegalReasonsException
     */
    protected function curlGET(string $url, array $postFields = [], string $contentType = CurlContentType::JSON): string
    {
        $curl = $this->curlInit($url, $postFields, $contentType);
        $curl->setMethod(CurlMethod::GET);
        return $curl->send(3);
    }

    /**
     * @param string $url
     * @param array $postFields
     * @param string $contentType
     * @return string
     * @throws BadRequestException
     * @throws ConflictException
     * @throws ForbiddenException
     * @throws GatewayTimeoutException
     * @throws InternalServerErrorException
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     * @throws NotImplementedException
     * @throws ServiceUnavailableException
     * @throws TooManyRequestsException
     * @throws UnauthorizedException
     * @throws UnavailableForLegalReasonsException
     * @throws UnexpectedResultException
     * @throws UnexpectedValueException
     * @throws WorthlessVariableException
     */
    protected function curlPOST(string $url, array $postFields = [], string $contentType = CurlContentType::JSON): string
    {
        $curl = $this->curlInit($url, $postFields, $contentType);
        $curl->setMethod(CurlMethod::POST);
        $curl->unsetField("pid");
        return $curl->send(3);
    }

    /**
     * @param string $url
     * @param array $postFields
     * @param string $contentType
     * @return string
     * @throws BadRequestException
     * @throws ConflictException
     * @throws ForbiddenException
     * @throws GatewayTimeoutException
     * @throws InternalServerErrorException
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     * @throws NotImplementedException
     * @throws ServiceUnavailableException
     * @throws TooManyRequestsException
     * @throws UnauthorizedException
     * @throws UnavailableForLegalReasonsException
     * @throws UnexpectedResultException
     * @throws UnexpectedValueException
     * @throws WorthlessVariableException
     */
    protected function curlPUT(string $url, array $postFields = [], string $contentType = CurlContentType::JSON): string
    {
        $curl = $this->curlInit($url, $postFields, $contentType);
        $curl->setMethod(CurlMethod::PUT);
        $curl->unsetField("pid");
        return $curl->send(3);
    }

    /**
     * @param string $url
     * @param array $postFields
     * @param string $contentType
     * @return string
     * @throws BadRequestException
     * @throws ConflictException
     * @throws ForbiddenException
     * @throws GatewayTimeoutException
     * @throws InternalServerErrorException
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     * @throws NotImplementedException
     * @throws ServiceUnavailableException
     * @throws TooManyRequestsException
     * @throws UnauthorizedException
     * @throws UnavailableForLegalReasonsException
     * @throws UnexpectedResultException
     * @throws UnexpectedValueException
     * @throws WorthlessVariableException
     */
    protected function curlDELETE(string $url, array $postFields = [], string $contentType = CurlContentType::JSON): string
    {
        $curl = $this->curlInit($url, $postFields, $contentType);
        $curl->setMethod(CurlMethod::DELETE);
        $curl->unsetField("pid");
        return $curl->send(3);
    }
}
