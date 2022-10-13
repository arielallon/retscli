<?php

/** @noinspection PhpExpressionResultUnusedInspection */
declare(strict_types=1);

namespace ArielAllon\RetsCli\DateTime;

class DateTimeRange implements DateTimeRangeInterface
{
    private \DateTimeInterface $startDateTime;
    private \DateTimeInterface $endDateTime;

    public function getStartDateTime(): \DateTimeInterface
    {
        return $this->startDateTime; // Will throw if uninitialized
    }

    public function setStartDateTime(\DateTimeInterface $startDateTime): self
    {
        try {
            $this->startDateTime; // Attempt to read
        } catch (\Error $e) {
            $this->startDateTime = $startDateTime; // Variable hasn't been initialized
            return $this;
        }

        throw new \LogicException('DateTimeRange startDateTime is already set.');
    }

    public function getEndDateTime(): \DateTimeInterface
    {
        return $this->endDateTime; // Will throw if uninitialized
    }

    public function setEndDateTime(\DateTimeInterface $endDateTime): self
    {
        try {
            $this->endDateTime; // Attempt to read
        } catch (\Error $e) {
            $this->endDateTime = $endDateTime; // Variable hasn't been initialized
            return $this;
        }

        throw new \LogicException('DateTimeRange endDateTime is already set.');
    }
}
