<?php

namespace SdkBase\API;

use SdkBase\Exceptions\Http\BadRequestException;
use SdkBase\Exceptions\Http\ConflictException;
use SdkBase\Exceptions\Http\ForbiddenException;
use SdkBase\Exceptions\Http\InternalServerErrorException;
use SdkBase\Exceptions\Http\MethodNotAllowedException;
use SdkBase\Exceptions\Http\NotFoundException;
use SdkBase\Exceptions\Http\UnauthorizedException;
use SdkBase\Exceptions\Validation\UnexpectedResultException;
use SdkBase\Exceptions\Validation\UnexpectedValueException;
use SdkBase\Exceptions\Validation\UnidentifiedEntityException;
use SdkBase\Exceptions\Validation\WorthlessVariableException;

abstract class Entity extends HttpRequest
{
    protected $properties = [];
    private $shadowCopy = [];

    abstract protected function getEndpointUrl(): string;

    abstract protected function getEndpointUrlExtension(array $postFields = []): string;

    abstract protected function injectSettingsData(array $postFields): array;

    public function getId()
    {
        return $this->getProperty("id");
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    protected function getProperty(string $name)
    {
        return isset($this->properties[$name]) ? $this->properties[$name] : null;
    }

    /**
     * @param string $name
     * @param $value
     */
    protected function setProperty(string $name, $value): void
    {
        $this->properties[$name] = $value;
    }

    /**
     * @param mixed $result
     * @return array
     * @throws UnexpectedResultException
     */
    protected function decodeResult($result): array
    {
        if (is_array($result)) {
            return $result;
        } elseif (!is_string($result) || $result === "" || $result === "[]") {
            return [];
        }
        $result = json_decode($result, true);
        if (!is_array($result)) {
            throw new UnexpectedResultException("Unable to decode result");
        }
        return $result;
    }

    protected function fetchArray(array $result): void
    {
        $this->properties = $this->shadowCopy = $result;
        /**
         * some variables as 'parent' are required for any request when it exists
         * so I'm removing it from shadowCopy
         * then our method 'getDirty' will  always identify it as a new data
        **/
        $requiredVars = ["id", "parent", "children", "hidden", "deleted"];
        foreach($requiredVars as $requiredVar) {
            if(isset($this->shadowCopy[$requiredVar])) {
                unset($this->shadowCopy[$requiredVar]);
            }
        }
    }

    /**
     * @param mixed $result
     * @param array $postedFields: usage on class SdkBase\API\Entities\Media
     * @throws UnexpectedResultException
     */
    protected function fetchResult($result, array $postedFields = []): void
    {
        $this->fetchArray(
            $this->decodeResult($result)
        );
    }

    /**
     * @param array $postFields
     * @return array
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
     */
    public function search(array $postFields = []): array
    {
        $result = $this->decodeResult(
            $this->curlGET(
                $this->getEndpointUrl(),
                $this->injectSettingsData($postFields)
            )
        );
        if (empty($result)) {
            return [];
        }
        $output = [];
        $class = get_called_class();
        foreach ($result as $item) {
            if($item['id'] === 0) {
                continue;
            }
            /** @var Entity $object */
            $object = new $class();
            $object->fetchResult($item, $postFields);
            $output[] = $object;
        }
        return $output;
    }

    /**
     * @param $id
     * @param array $postFields
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
     */
    public function fetch($id, array $postFields = []): void
    {
        $result = $this->curlGET(
            "{$this->getEndpointUrl()}/$id",
            $this->injectSettingsData($postFields)
        );
        $this->fetchResult($result, $postFields);
    }

    /**
     * @return bool
     */
    protected function existsOnVendor(): bool
    {
        return !empty($this->properties['id']);
    }

    /**
     * @param array $postFields
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
     */
    public function save(array $postFields = []): void
    {
        if ($this->existsOnVendor()) {
            $this->update($postFields);
        } else {
            $this->create($postFields);
        }
    }

    /**
     * @param array $postFields
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
     */
    protected function create(array $postFields): void
    {
        $result = $this->curlPOST(
            "{$this->getEndpointUrl()}{$this->getEndpointUrlExtension($postFields)}",
            array_merge($postFields, $this->properties)
        );
        $this->fetchResult($result, $postFields);
    }

    /**
     * @param array $properties
     * @return array
     */
    private function getDirty(array $properties): array
    {
        $dirty = [];
        foreach ($properties as $name => $value) {
            $shadowValue = isset($this->shadowCopy[$name]) ? $this->shadowCopy[$name] : null;
            if ($shadowValue !== $value) {
                $dirty[$name] = $value;
            }
        }
        return $dirty;
    }

    /**
     * @param array $postFields
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
     */
    protected function update(array $postFields): void
    {
        $id = $this->getId();
        $dirty = $this->getDirty($this->properties);
        if (empty($dirty)) {
            // if we don't have changes, we don't need to execute anything
            return;
        }
        $result = $this->curlPUT(
            "{$this->getEndpointUrl()}/$id{$this->getEndpointUrlExtension($postFields)}",
            $dirty
        );
        $this->fetchResult($result, $postFields);
    }

    /**
     * @param array $postFields
     * @throws BadRequestException
     * @throws ConflictException
     * @throws ForbiddenException
     * @throws InternalServerErrorException
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     * @throws UnauthorizedException
     * @throws UnexpectedResultException
     * @throws UnexpectedValueException
     * @throws UnidentifiedEntityException
     * @throws WorthlessVariableException
     */
    public function delete(array $postFields = []): void
    {
        if (!$this->existsOnVendor()) {
            throw new UnidentifiedEntityException("You can't delete an entity without id.");
        }
        $id = $this->getId();
        $this->curlDELETE(
            "{$this->getEndpointUrl()}/$id{$this->getEndpointUrlExtension($postFields)}",
            $postFields
        );
    }
}