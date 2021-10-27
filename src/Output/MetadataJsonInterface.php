<?php

declare(strict_types=1);

namespace ArielAllon\RetsCli\Output;

interface MetadataJsonInterface extends StrategyInterface
{
    public const FILENAME_FORMAT = '%s_%s_%s_%s.json';

    public function setResourceName(string $resource_name): StrategyInterface;
    public function setClassName(string $class_name): StrategyInterface;
    public function complete() : StrategyInterface;
}
