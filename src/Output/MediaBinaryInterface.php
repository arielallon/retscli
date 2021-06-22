<?php

declare(strict_types=1);

namespace ArielAllon\RetsCli\Output;

interface MediaBinaryInterface extends StrategyInterface
{
    public const MEDIA_FILEPATH_FORMAT = 'media' . DIRECTORY_SEPARATOR . '%s' . DIRECTORY_SEPARATOR . '%s' . DIRECTORY_SEPARATOR;
    public const FILENAME_FORMAT = '%s.%s';

    public function setContentId(string $contentId): MediaBinaryInterface;
}
