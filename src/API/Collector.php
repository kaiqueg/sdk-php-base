<?php

namespace SdkBase\API;

use SdkBase\Exceptions\Validation\UnexpectedResultException;
use SdkBase\Exceptions\Validation\UnexpectedValueException;
use SdkBase\Exceptions\Validation\UnwritablePathException;
use SdkBase\Exceptions\Validation\WorthlessVariableException;
use SdkBase\Utils\Json;

abstract class Collector
{
    const LOOP_ITERATION_LIMIT = 200;

    abstract protected static function getListJsonPath(): string;
    abstract protected static function getCollectionJsonPath(): string;
    abstract protected function getListFromVendor(): array;
    abstract protected function collectItem(array $item): array;

    private $listManager;
    private $collectionManager;

    /**
     * StudentCollector constructor.
     * @throws UnexpectedResultException
     * @throws UnexpectedValueException
     * @throws UnwritablePathException
     * @throws WorthlessVariableException
     */
    public function __construct()
    {
        $this->listManager = new Json();
        $this->listManager->setPath(static::getListJsonPath());
        $this->collectionManager = new Json();
        $this->collectionManager->setPath(static::getCollectionJsonPath());

        if (!file_exists(static::getListJsonPath())) {
            $this->importList();
        } else {
            try {
                $this->listManager->load();
            } catch (UnexpectedResultException $e) {
                $this->importList();
            }
        }
    }

    /**
     * @param array $fields
     * @return array
     * @throws UnexpectedValueException
     */
    public function getListItem(array $fields): array
    {
        return $this->listManager->searchItem($fields);
    }

    /**
     * @param array $fields
     * @return array
     * @throws UnexpectedValueException
     */
    public function searchOnList(array $fields): array
    {
        return $this->listManager->searchAll($fields);
    }

    /**
     * @param array $fields
     * @return array
     * @throws UnexpectedValueException
     */
    public function getCollectionItem(array $fields): array
    {
        return $this->collectionManager->searchItem($fields);
    }

    /**
     * @param array $fields
     * @return array
     * @throws UnexpectedValueException
     */
    public function searchOnCollection(array $fields): array
    {
        return $this->collectionManager->searchAll($fields);
    }

    /**
     * @throws UnexpectedResultException
     * @throws UnwritablePathException
     * @throws WorthlessVariableException
     */
    private function importList()
    {
        $data = $this->getListFromVendor();
        $this->listManager->setData($data);
        $this->listManager->save();
    }

    /**
     * @return int
     * @throws UnexpectedResultException
     * @throws UnwritablePathException
     * @throws WorthlessVariableException
     */
    public function collect(): int
    {
        $this->collectionManager->load();
        $collection = $this->collectionManager->getData();
        $collectionCount = count($collection);
        $list = $this->listManager->getData();
        $listCount = count($list);
        if ($collectionCount >= $listCount) {
            return 0;
        }
        $start = $collectionCount;
        $end = $start + self::LOOP_ITERATION_LIMIT;
        for ($index = $start; $index < $end && $index < $listCount; $index++) {
            $collection = $this->collectItem($list[$index]);
        }
        $this->collectionManager->setData($collection);
        $this->collectionManager->save();
        return $listCount - $index;
    }

    /**
     * @return bool
     * @throws UnexpectedResultException
     * @throws UnwritablePathException
     * @throws WorthlessVariableException
     */
    public function mustRecollect(): bool
    {
        $list = $this->getListFromVendor();
        $hasUpdates = $list != $this->listManager->getData();
        if (!$hasUpdates) {
            return false;
        }
        $this->listManager->setData($list);
        $this->listManager->save();
        return true;
    }
}