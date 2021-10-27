<?php

declare(strict_types=1);

namespace ArielAllon\RetsCli\Output;

class MetadataJson implements MetadataJsonInterface
{
    private string $mls_key;
    private string $resource_name;
    private string $class_name;

    /** @var resource */
    private $file;

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

    /**
     * @param array $results
     * @return $this
     */
    public function outputResults($results): self
    {
        fwrite(
            $this->getFile(),
            json_encode(
                $results,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE
            )
        );

        return $this;
    }

    public function complete() : self
    {
        fclose($this->getFile());
        return $this;
    }


    private function getFile()
    {
        if ($this->file === null) {
            $this->setFile(fopen($this->buildFullFilePath(), 'w'));
        }

        return $this->file;
    }

    private function setFile($file): self
    {
        if ($this->file !== null) {
            throw new \LogicException('ListingsCsv file already set.');
        }

        $this->file = $file;

        return $this;
    }

    private function getMlsKey(): string
    {
        return $this->mls_key; // Will throw if uninitialized
    }

    public function setMlsKey(string $mls_key): self
    {
        try {
            $this->mls_key; // Attempt to read
        } catch (\Error $e) {
            $this->mls_key = $mls_key; // Variable hasn't been initialized
            return $this;
        }

        throw new \LogicException('MetadataJson mls_key is already set.');
    }

    private function getResourceName(): string
    {
        return $this->resource_name; // Will throw if uninitialized
    }

    public function setResourceName(string $resource_name): self
    {
        try {
            $this->resource_name; // Attempt to read
        } catch (\Error $e) {
            $this->resource_name = $resource_name; // Variable hasn't been initialized
            return $this;
        }

        throw new \LogicException('MetadataJson resource_name is already set.');
    }

    private function getClassName(): string
    {
        return $this->class_name; // Will throw if uninitialized
    }

    public function setClassName(string $class_name): self
    {
        try {
            $this->class_name; // Attempt to read
        } catch (\Error $e) {
            $this->class_name = $class_name; // Variable hasn't been initialized
            return $this;
        }

        throw new \LogicException('MetadataJson class_name is already set.');
    }
}
