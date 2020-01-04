<?php

namespace SdkBase\Utils;

use SdkBase\Exceptions\Validation\FileNotFoundException;
use SdkBase\Exceptions\Validation\UnexpectedResultException;
use SdkBase\Exceptions\Validation\UnexpectedValueException;
use SdkBase\Exceptions\Validation\UnwritablePathException;
use SdkBase\Exceptions\Validation\WorthlessVariableException;

class Json
{
    private $path;
    private $data = [];

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param string $path
     * @throws UnexpectedValueException
     */
    public function setPath(string $path): void
    {
        if(file_exists($path) && is_dir($path)) {
            throw new UnexpectedValueException("Path should be a file");
        } elseif(strpos($path, ".json", strlen(strtolower($path))-6) === false) {
            throw new UnexpectedValueException("Path must end with '.json'");
        }
        $this->path = $path;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @throws WorthlessVariableException
     */
    private function checkPath(): void
    {
        if(!$this->path) {
            throw new WorthlessVariableException("Empty JSON path. Nothing to load.");
        }
    }

    /**
     * @return string
     * @throws FileNotFoundException
     * @throws UnexpectedResultException
     */
    private function pull(): string
    {
        if(!file_exists($this->path)) {
            throw new FileNotFoundException();
        }
        $result = file_get_contents($this->path);
        if(!is_string($result)) {
            throw new UnexpectedResultException("Unable to pull JSON: $result");
        }
        return $result;
    }

    /**
     * @param string $dataStr
     * @return array
     * @throws UnexpectedResultException
     */
    private function decodeData(string $dataStr): array
    {
        if($dataStr === "" || $dataStr === "[]" || $dataStr === "{}") {
            $data = [];
        } else {
            $data = json_decode($dataStr, 1);
        }
        if(!is_array($data)) {
            throw new UnexpectedResultException("Unable to decode JSON: $dataStr");
        }
        return $data;
    }

    /**
     * @param array $data
     * @return string
     * @throws UnexpectedResultException
     */
    private function encodeData(array $data): string
    {
        $dataStr = json_encode($data);
        if(!is_string($dataStr)) {
            throw new UnexpectedResultException("Unable to encode JSON: $dataStr");
        }
        return $dataStr;
    }

    /**
     * @throws FileNotFoundException
     * @throws UnexpectedResultException
     * @throws WorthlessVariableException
     */
    public function load(): void
    {
        $this->checkPath();
        $dataStr = $this->pull();
        $this->data = $this->decodeData($dataStr);
    }

    /**
     * @throws UnwritablePathException
     */
    private function checkPathWritePermission(): void
    {
        $dir = dirname($this->path);
        if(!is_writable($dir)) {
            throw new UnwritablePathException("We can't write on directory '$dir'");
        }
    }

    /**
     * @param string $dataStr
     * @throws UnexpectedResultException
     */
    private function push(string $dataStr): void
    {
        $result = file_put_contents($this->path, $dataStr);
        if(!$result) {
            throw new UnexpectedResultException("Unable to write JSON");
        }
    }

    /**
     * @throws UnexpectedResultException
     * @throws UnwritablePathException
     * @throws WorthlessVariableException
     */
    public function save(): void
    {
        $this->checkPath();
        $this->checkPathWritePermission();
        $dataStr = $this->encodeData($this->data);
        $this->push($dataStr);
    }

    /**
     * @param array $fields
     * @param bool $getMultiple
     * @return array
     * @throws UnexpectedValueException
     */
    private function getDataWhere(array $fields, bool $getMultiple = false): array
    {
        if(empty($fields)) {
            if(!$getMultiple) {
                throw new UnexpectedValueException("Please inform something to search.");
            }
            return $this->data;
        }
        if(empty($this->data)) {
            return [];
        }
        $result = [];
        foreach($this->data as $item) {
            $match = true;
            foreach($fields as $key => $value) {
                $match = $match && !empty($item[$key]) && $item[$key] === $value;
            }
            if($match) {
                if(!$getMultiple) {
                    return $item;
                }
                $result[] = $item;
            }
        }
        return $result;
    }

    /**
     * @param array $fields
     * @return array
     * @throws UnexpectedValueException
     */
    public function searchItem(array $fields): array
    {
        return $this->getDataWhere($fields, false);
    }

    /**
     * @param array $fields
     * @return array
     * @throws UnexpectedValueException
     */
    public function searchAll(array $fields = []): array
    {
        return $this->getDataWhere($fields, true);
    }
}