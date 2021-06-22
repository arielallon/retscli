<?php

declare(strict_types=1);

namespace ArielAllon\RetsCli\Output;

class MediaBinary implements MediaBinaryInterface
{
    private string $mls_key;
    private string $content_id;
    private string $file_path;

    /**
     * @param \Illuminate\Support\Collection $results
     * @return $this
     */
    public function outputResults($results): self
    {
        foreach ($results as $result) {
            $binary = $result->getContent();
            $fullFilePath = $this->buildFullFilePath(md5($binary), explode('/', $result->getContentType())[1]);
            if (!is_dir(dirname($fullFilePath))) {
                mkdir(dirname($fullFilePath), 0755, true);
            }
            $file = fopen($fullFilePath, 'w');
            fwrite($file, $binary);
            fclose($file);
        }

        return $this;
    }

    private function buildFullFilePath(string $fileName, string $fileExtention) : string
    {
        return $this->getFilePath() . sprintf(
                self::FILENAME_FORMAT,
                $fileName,
                $fileExtention
            );
    }

    private function buildFilePath() : string
    {
        return self::FILEPATH . sprintf(
                self::MEDIA_FILEPATH_FORMAT,
                $this->getMlsKey(),
                $this->getContentId()
            );
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
    }

    private function getContentId(): string
    {
        return $this->content_id; // Will throw if uninitialized
    }

    public function setContentId(string $content_id): self
    {
        try {
            $this->content_id; // Attempt to read
        } catch (\Error $e) {
            $this->content_id = $content_id; // Variable hasn't been initialized
            return $this;
        }
    }

    private function getFilePath(): string
    {
        try {
            $this->file_path; // Attempt to read
        } catch (\Error $e) {
            $this->file_path = $this->buildFilePath(); // Variable hasn't been initialized
        }
        return $this->file_path;
    }

    private function setFilePath(string $file_path): self
    {
        try {
            $this->file_path; // Attempt to read
        } catch (\Error $e) {
            $this->file_path = $file_path; // Variable hasn't been initialized
            return $this;
        }
    }
}
