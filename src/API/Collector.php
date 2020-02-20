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

    abstract protected static function getTemporaryCollectionJsonPath(): string;

    abstract protected function getListFromVendor(): array;

    abstract protected function collectItem(array $item): array;

    /** @var Json $listManager */
    private $listManager;
    /** @var Json $collectionManager */
    private $collectionManager;
    /** @var Json $tempCollectionManager */
    private $tempCollectionManager;
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
        $this->setCollectionManager();
        $this->setTemporaryCollectionManager();
    }

    /**
     * @throws UnexpectedValueException
     */
    private function setTemporaryCollectionManager(): void
    {
        $this->tempCollectionManager = new Json();
        $this->tempCollectionManager->setPath(static::getTemporaryCollectionJsonPath());
        try {
            $this->tempCollectionManager->load();
        } catch (Exception $e) {
            // do nothing
        }
    }

    /**
     * @throws UnexpectedValueException
     */
    private function setCollectionManager(): void
    {
        $this->collectionManager = new Json();
        $this->collectionManager->setPath(static::getCollectionJsonPath());
        try {
            $this->collectionManager->load();
        } catch (Exception $e) {
            // do nothing
        }
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
            $this->setTotalCount();
        } catch (FileNotFoundException $exception) {
            $this->importList();
        } catch (UnexpectedResultException $e) {
            $this->importList();
        }
    }

    private function setTotalCount(): void
    {
        $this->totalCount = count($this->listManager->getData());
    }

    private function setCollectedCount(): void
    {
        $this->collectedCount = count($this->tempCollectionManager->getData());
    }

    public function getLeftCount(): int
    {
        return $this->totalCount > 0 && $this->collectedCount < $this->totalCount ? $this->totalCount - $this->collectedCount : 0;
    }

    public function getLeftCallsCount(): int
    {
        $left = $this->getLeftCount();
        if (!$left) {
            return 0;
        }
        return intval(ceil($left / static::LOOP_ITERATION_LIMIT));
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
        $this->setTotalCount();
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
            $this->tempCollectionManager->load();
            $this->setCollectedCount();
        } catch (FileNotFoundException $exception) {
            // do nothing, it's expected if it's a collection init
        }
        if ($this->collectedCount < $this->totalCount) {
            $collection = $this->getCollectedItems();
            $this->tempCollectionManager->setData($collection);
            $this->tempCollectionManager->save();
            $this->setCollectedCount();
        }
        $left = $this->getLeftCount();
        if (!$left) {
            $this->transferTemporaryCollection();
        }
        return $left;
    }

    /**
     * @throws UnexpectedResultException
     * @throws UnwritablePathException
     * @throws WorthlessVariableException
     */
    public function transferTemporaryCollection(): void
    {
        $this->collectionManager->setData($this->tempCollectionManager->getData());
        $this->collectionManager->save();
    }

    private function getCollectedItems(): array
    {
        $end = $this->collectedCount + static::LOOP_ITERATION_LIMIT;
        $list = $this->listManager->getData();
        $collection = $this->tempCollectionManager->getData();
        for (
            $index = $this->collectedCount;
            $index < $end && $index < $this->totalCount;
            $index++
        ) {
            $collection[] = $this->collectItem($list[$index]);
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
        $this->tempCollectionManager->setData([]);
        try {
            $this->tempCollectionManager->save();
            $this->setCollectedCount();
        } catch (Exception $e) {
            // do nothing
        }
    }
}