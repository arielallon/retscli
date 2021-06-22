<?php
declare(strict_types=1);

namespace ArielAllon\RetsCli\Configuration;

interface ConfigurationInterface
{
    public function getConfigurationByKey(string $key) : array;
}
