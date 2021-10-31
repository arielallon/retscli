<?php

declare(strict_types=1);

namespace ArielAllon\RetsCli\Output;

interface MetadataYamlInterface extends StrategyInterface
{
    public const FILENAME_FORMAT = '%s_%s_%s_%s.yaml';

    public function setResourceName(string $resource_name): StrategyInterface;
    public function setClassName(string $class_name): StrategyInterface;
    public function complete() : StrategyInterface;
}
