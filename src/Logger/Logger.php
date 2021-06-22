<?php

declare(strict_types=1);

namespace ArielAllon\RetsCli\Logger;

class Logger implements LoggerInterface
{
    private CONST LOGFILENAME = 'retscli.log';
    private CONST LOGFILEPATH = RETSCLI_ROOT_DIR . DIRECTORY_SEPARATOR;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    public function __construct()
    {
        $logger = new \Monolog\Logger('retscli');
        $logger->pushHandler(new \Monolog\Handler\StreamHandler(self::LOGFILEPATH . self::LOGFILENAME));
        $this->setLogger($logger);
    }

    public function getLogger(): \Psr\Log\LoggerInterface
    {
        if ($this->logger === null) {
            throw new \LogicException('Logger logger has not been set.');
        }

        return $this->logger;
    }

    public function setLogger(\Psr\Log\LoggerInterface $logger): LoggerInterface
    {
        if ($this->logger !== null) {
            throw new \LogicException('Logger logger already set.');
        }

        $this->logger = $logger;

        return $this;
    }

}
