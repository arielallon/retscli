<?php

declare(strict_types=1);

namespace ArielAllon\RetsCli\Command;

use ArielAllon\RetsCli\Configuration;
use ArielAllon\RetsCli\PHRETS;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ObjectQuery extends Command
{
    private const ARGUMENT_KEY = 'key';
    private const ARGUMENT_ID = 'id';
    private const ARGUMENT_RESOURCE_ALIAS = 'resource_alias';

    private const OPTION_RESOURCE = 'resource';
    private const OPTION_CLASS = 'class';
    private const OPTION_FIELD = 'field';
    private const OPTION_OBJECT_ID = 'object_id';
    private const OPTION_BY_LOCATION = 'by_location';
    private const OPTION_SAVE_BINARIES = 'save_binaries';

    private const KEY_RESOURCE = 'resource';
    private const KEY_CLASSES = 'classes';

    protected static $defaultName = 'objectquery';

    /** @var \PHRETS\Session */
    private $phrets_session;

    /** @var string */
    private $resource_alias;

    /** @var string */
    private $resource;

    protected function configure()
    {
        $this->setDescription('Send a GetObject query to the RETS server')
            ->addArgument(
                self::ARGUMENT_KEY,
                InputArgument::REQUIRED,
                'Key of configuration to use'
            )
            ->addArgument(
                self::ARGUMENT_ID,
                InputArgument::REQUIRED,
                'Id to use in query to RETS server'
            )
            ->addArgument(
                self::ARGUMENT_RESOURCE_ALIAS,
                InputArgument::REQUIRED,
                'Alias in config file for resource+class(es) to query'
            )
            ->addOption(
                self::OPTION_RESOURCE,
                'r',
                InputOption::VALUE_OPTIONAL,
                'Specific resource for this query. If not provided, will run against all in config file.',
                null
            )
            ->addOption(
                self::OPTION_CLASS,
                'c',
                InputOption::VALUE_OPTIONAL,
                'Specific class for this query. If not provided, will run against all in config file.',
                null
            )
            ->addOption(
                self::OPTION_FIELD,
                'f',
                InputOption::VALUE_OPTIONAL,
                'Name of field of media object. Overrides value in config file.',
                null
            )
            ->addOption(
                self::OPTION_OBJECT_ID,
                'i',
                InputOption::VALUE_OPTIONAL,
                'Id(s) of the object to request (usually an index). Defaults to *.',
                '*'
            )
            ->addOption(
                self::OPTION_BY_LOCATION,
                'l',
                InputOption::VALUE_NONE,
                'Will request the locations (URLs) of the media, otherwise requests binaries. Overrides value in config file.',
                null
            )
            ->addOption(
                self::OPTION_SAVE_BINARIES,
                'b',
                InputOption::VALUE_NONE,
                'Save the binaries from the response.'
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $mlsConfigurationArray = (new Configuration\FromYaml())->getConfigurationByKey(
            $input->getArgument(self::ARGUMENT_KEY)
        );

        $this->setPhretsSession((new PHRETS\SessionBuilder())->fromConfigurationArray($mlsConfigurationArray))
            ->setResourceAlias($input->getArgument(self::ARGUMENT_RESOURCE_ALIAS))
            ->initializeResource($input, $mlsConfigurationArray);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->phretsLogin();
        $resource = $this->getResource();
        $output->writeln('Resource: ' . $resource);

//        $dataOutput = $this->getOutputStrategyFromInput($input); // @todo
//        if ($dataOutput !== null) {
//            $dataOutput->setMlsKey($input->getArgument(self::ARGUMENT_KEY))
//                ->setResourceName($resource);
//        }

        $offset = null;

        $results = $this->getPhretsSession()->GetObject(
            $resource,
            'Photo', //$input->getOption(self::OPTION_FIELD), // @todo allow defaulting to yml
            $input->getArgument(self::ARGUMENT_ID),
            $input->getOption(self::OPTION_OBJECT_ID),
            $input->getOption(self::OPTION_BY_LOCATION) // @todo allow default to yml
        );

        if ($dataOutput !== null) {
            $dataOutput->outputResults($results);
        } else {
            $this->outputResultsToStdOut($results, $output, $input->getOption(self::OPTION_BY_LOCATION));
        }

        $count = count($results);
        $offset += $count;


        if ($dataOutput !== null) {
            $dataOutput->complete();
        }
        $output->writeln('');
        $this->getPhretsSession()->Disconnect();
        return Command::SUCCESS;
    }

    private function outputResultsToStdOut(
        \Illuminate\Support\Collection $results,
        OutputInterface $output,
        bool $includeLocation
    ): void {
        /** @var \PHRETS\Models\BaseObject $result */
        foreach ($results as $result) {
            $resultArray = [
                'ContentId' => $result->getContentId(),
                'ContentDescription' => $result->getContentDescription(),
                'ContentType' => $result->getContentType(),
                'ObjectId' => $result->getObjectId(),
            ];
            if ((int)$includeLocation === 1) {
                $resultArray['Location'] = $result->getLocation();
            }
            $output->write(var_export($resultArray, true));
        }
    }

    private function initializeResource(InputInterface $input, array $mlsConfigurationArray): self
    {
        $specificResource = $input->getOption(self::OPTION_RESOURCE);
        if ($specificResource !== null) {
            $this->setResource($specificResource);
        } else {
            $this->setResource($mlsConfigurationArray['resources'][$this->getResourceAlias()]['resource']);
        }

        return $this;
    }

    private function phretsLogin(): self
    {
        // Some RETS servers inexplicably fail on the first login but succeed if you try again.
        try {
            $this->getPhretsSession()->Login();
        } catch (\Exception $e) {
            $this->getPhretsSession()->Login();
        }

        return $this;
    }


    private function getPhretsSession(): \PHRETS\Session
    {
        if ($this->phrets_session === null) {
            throw new \LogicException('Query phretsSession has not been set.');
        }

        return $this->phrets_session;
    }

    private function setPhretsSession(\PHRETS\Session $phretsSession): self
    {
        if ($this->phrets_session !== null) {
            throw new \LogicException('Query phretsSession already set.');
        }

        $this->phrets_session = $phretsSession;

        return $this;
    }

    private function getResourceAlias(): string
    {
        if ($this->resource_alias === null) {
            throw new \LogicException('Query resource_alias has not been set.');
        }

        return $this->resource_alias;
    }

    private function setResourceAlias(string $resource_alias): self
    {
        if ($this->resource_alias !== null) {
            throw new \LogicException('Query resource_alias already set.');
        }

        $this->resource_alias = $resource_alias;

        return $this;
    }

    private function getResource(): string
    {
        if ($this->resource === null) {
            throw new \LogicException('Query resource has not been set.');
        }

        return $this->resource;
    }

    private function setResource(string $resource): self
    {
        if ($this->resource !== null) {
            throw new \LogicException('Query resource already set.');
        }

        $this->resource = $resource;

        return $this;
    }
}