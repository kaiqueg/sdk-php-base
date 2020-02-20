<?php

namespace SdkBase\API;

use Exception;
use SdkBase\Exceptions\Validation\FileNotFoundException;
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

    abstract protected static function getCollectionJsonTemporaryPath(): string;

    abstract protected function getListFromVendor(): array;

    abstract protected function collectItem(array $item): array;

    /** @var Json $listManager */
    private $listManager;
    /** @var Json $collectionManager */
    private $collectionManager;
    private $totalCount = 0;
    private $collectedCount = 0;

    /**
     * StudentCollector constructor.
     * @throws UnexpectedResultException
     * @throws UnexpectedValueException
     * @throws UnwritablePathException
     * @throws WorthlessVariableException
     */
    public function __construct()
    {
        $this->setListManager();
        $this->collectionManager = new Json();
        $this->collectionManager->setPath(static::getCollectionJsonPath());
    }

    /**
     * @throws UnexpectedResultException
     * @throws UnexpectedValueException
     * @throws UnwritablePathException
     * @throws WorthlessVariableException
     */
    private function setListManager(): void
    {
        $this->listManager = new Json();
        $this->listManager->setPath(static::getListJsonPath());
        try {
            $this->listManager->load();
        } catch (FileNotFoundException $exception) {
            $this->importList();
        } catch (UnexpectedResultException $e) {
            $this->importList();
        }
        $this->setTotalCount();
    }

    private function setTotalCount(): void
    {
        $this->totalCount = count($this->listManager->getData());
    }

    private function setCollectedCount(): void
    {
        $this->collectedCount = count($this->collectionManager->getData());
    }

    public function getLeftCount(): int
    {
        return $this->totalCount > 0 && $this->collectedCount < $this->totalCount ? $this->totalCount - $this->collectedCount : 0;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    public function getCollectedCount(): int
    {
        return $this->collectedCount;
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
        try {
            $this->collectionManager->load();
            $this->setCollectedCount();
        } catch (FileNotFoundException $exception) {
            // do nothing, it's expected if it's a collection init
        }
        if ($this->collectedCount < $this->totalCount) {
            $collection = $this->getCollectedItems();
            $this->collectionManager->setData($collection);
            $this->collectionManager->save();
        }
        return $this->getLeftCount();
    }

    private function getCollectedItems(): array
    {
        $end = $this->collectedCount + static::LOOP_ITERATION_LIMIT;
        $list = $this->listManager->getData();
        $collection = $this->collectionManager->getData();
        for (
            $index = $this->collectedCount;
            $index < $end && $index < $this->totalCount;
            $index++
        ) {
            $collection = $this->collectItem($list[$index]);
            $this->collectedCount++;
        }
        return $collection;
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
        $this->setTotalCount();
        $this->resetCollection();
        return true;
    }

    public function resetCollection(): void
    {
        $this->collectionManager->setData([]);
        try {
            $this->collectionManager->save();
            $this->setCollectedCount();
        } catch (Exception $e) {
            // do nothing
        }
    }
}