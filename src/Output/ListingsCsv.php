<?php

declare(strict_types=1);

namespace ArielAllon\RetsCli\Output;

use PHRETS\Models\Search\Results;

class ListingsCsv implements ListingsCsvInterface
{
    private const FILENAME_FORMAT = '%s_%s_%s_%s.csv';
    private CONST FILEPATH = RETSCLI_ROOT_DIR . DIRECTORY_SEPARATOR . 'output' . DIRECTORY_SEPARATOR;

    /** @var string */
    private $mls_key;

    /** @var string */
    private $resource_name;

    /** @var string */
    private $class_name;

    /** @var string */
    private $full_file_path;

    /** @var resource */
    private $file;

    /** @var bool */
    private $is_header_written;

    private function buildFullFilePath() : string
    {
        return self::FILEPATH . sprintf(
            self::FILENAME_FORMAT,
            $this->getMlsKey(),
            $this->getResourceName(),
            $this->getClassName(),
            time()
            );
    }

    public function outputResults(Results $results): StrategyInterface
    {
        if (!$this->getIsHeaderWritten()) {
            $this->writeHeader($results);
        }

        foreach ($results as $row) {
            fputcsv($this->getFile(), $row->toArray());
        }

        return $this;
    }

    private function writeHeader(Results $results): StrategyInterface
    {
        $headerRow = array_keys(current($results->toArray()));
        fputcsv($this->getFile(), $headerRow);
        $this->setIsHeaderWritten(true);
        return $this;
    }

    public function complete() : StrategyInterface
    {
        fclose($this->getFile());
        return $this;
    }

    private function getMlsKey(): string
    {
        if ($this->mls_key === null) {
            throw new \LogicException('ListingsCsv mls_name has not been set.');
        }

        return $this->mls_key;
    }

    public function setMlsKey(string $mls_key): StrategyInterface
    {
        if ($this->mls_key !== null) {
            throw new \LogicException('ListingsCsv mls_name already set.');
        }

        $this->mls_key = $mls_key;

        return $this;
    }

    private function getResourceName(): string
    {
        if ($this->resource_name === null) {
            throw new \LogicException('ListingsCsv resource_name has not been set.');
        }

        return $this->resource_name;
    }

    public function setResourceName(string $resource_name): StrategyInterface
    {
        if ($this->resource_name !== null) {
            throw new \LogicException('ListingsCsv resource_name already set.');
        }

        $this->resource_name = $resource_name;

        return $this;
    }

    private function getClassName(): string
    {
        if ($this->class_name === null) {
            throw new \LogicException('ListingsCsv class_name has not been set.');
        }

        return $this->class_name;
    }

    public function setClassName(string $class_name): StrategyInterface
    {
        if ($this->class_name !== null) {
            throw new \LogicException('ListingsCsv class_name already set.');
        }

        $this->class_name = $class_name;

        return $this;
    }

    private function getFullFilePath(): string
    {
        if ($this->full_file_path === null) {
            $this->setFullFilePath($this->buildFullFilePath());
        }

        return $this->full_file_path;
    }

    private function setFullFilePath(string $full_file_path): ListingsCsvInterface
    {
        if ($this->full_file_path !== null) {
            throw new \LogicException('ListingsCsv full_file_path already set.');
        }

        $this->full_file_path = $full_file_path;

        return $this;
    }


    private function getFile()
    {
        if ($this->file === null) {
            $this->setFile(fopen($this->buildFullFilePath(), 'w'));
        }

        return $this->file;
    }

    private function setFile($file): ListingsCsvInterface
    {
        if ($this->file !== null) {
            throw new \LogicException('ListingsCsv file already set.');
        }

        $this->file = $file;

        return $this;
    }

    public function getIsHeaderWritten(): bool
    {
        return (bool)$this->is_header_written;
    }

    public function setIsHeaderWritten(bool $is_header_written): ListingsCsvInterface
    {
        if ($this->is_header_written !== null) {
            throw new \LogicException('ListingsCsv is_header_written already set.');
        }

        $this->is_header_written = $is_header_written;

        return $this;
    }

}
