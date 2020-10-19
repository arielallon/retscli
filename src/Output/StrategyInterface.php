<?php

declare(strict_types=1);

namespace ArielAllon\RetsCli\Output;

interface StrategyInterface
{
    public function setMlsKey(string $mls_key): StrategyInterface;
    public function setResourceName(string $resource_name): StrategyInterface;
    public function setClassName(string $class_name): StrategyInterface;
    public function outputResults(\PHRETS\Models\Search\Results $results) : StrategyInterface;
    public function complete() : StrategyInterface;
}
