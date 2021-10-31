<?php

declare(strict_types=1);

namespace ArielAllon\RetsCli\Output;

interface MetadataStrategyInterface extends StrategyInterface
{
    public function setTypeName(string $type_name): StrategyInterface;
    public function setResourceName(string $resource_name): StrategyInterface;
    public function setClassName(string $class_name): StrategyInterface;
    public function complete() : StrategyInterface;
}
