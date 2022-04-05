<?php

/** @noinspection PhpUnusedPrivateFieldInspection */
/** @noinspection PhpMultipleClassDeclarationsInspection */

declare(strict_types=1);

namespace ArielAllon\RetsCli\Command;

use ArielAllon\RetsCli\Configuration;
use ArielAllon\RetsCli\DateTime\DateTimeRange;
use ArielAllon\RetsCli\DateTime\DateTimeRangeInterface;
use ArielAllon\RetsCli\Output\ListingsCsv;
use ArielAllon\RetsCli\Output\StrategyInterface;
use ArielAllon\RetsCli\PHRETS;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use PHRETS\Session;

class Histogram extends Command
{
    private const ARGUMENT_KEY = 'key'; // mls
    private const ARGUMENT_INTERVAL = 'interval'; // year, month, etc.
    private const ARGUMENT_RESOURCE_ALIAS = 'resource_alias'; // listings, rooms, etc.

    private const OPTION_OUTPUT = 'output';
    private const OPTION_NO_PROGRESS_BAR = 'no-progress-bar';
    private const OPTION_ADDITIONAL_QUERY = 'additional_query'; // TODO: Support this
    private const OPTION_TIMESTAMP_FIELD = 'timestamp_field'; // pulled from config if not specified
    private const OPTION_START = 'start';
    private const OPTION_END = 'end';
    private const OPTION_RESOURCE = 'resource';
    private const OPTION_CLASS = 'class';

    private const KEY_RESOURCE = 'resource';
    private const KEY_CLASSES = 'classes';

    protected static $defaultName = 'histogram';

    private InputInterface $input;

    private OutputInterface $output;

    private Session $phrets_session;

    private string $resource_alias;

    private array $resources_and_classes;

    private ProgressBar $progress_bar;

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure(): void
    {
        $this->setDescription('Get A Histogram of Object Counts from the server')
            ->addArgument(
                self::ARGUMENT_KEY,
                InputArgument::REQUIRED,
                'Key of configuration to use'
            )
            ->addArgument(
                self::ARGUMENT_RESOURCE_ALIAS,
                InputArgument::REQUIRED,
                'Alias in config file for resource+class(es) to query'
            )
            ->addArgument(
                self::ARGUMENT_INTERVAL,
                InputArgument::REQUIRED,
                'Interval for Histogram Buckets, e.g. \'P1D\' (1 day), \'P2H\' (2 hours)'
            )
            ->addOption(
                self::OPTION_TIMESTAMP_FIELD,
                't',
                InputOption::VALUE_OPTIONAL,
                'Field of the modification timestamp to base the histogram on',
                null
            )
            ->addOption(
                self::OPTION_OUTPUT,
                'O',
                InputOption::VALUE_OPTIONAL,
                'Specifies the output file format for the data. Current possibilities: csv',
                null
            )
            ->addOption(
                self::OPTION_NO_PROGRESS_BAR,
                'Q',
                InputOption::VALUE_NONE,
                'Turns off progress bar in output'
            )
            ->addOption(
                self::OPTION_START,
                's',
                InputOption::VALUE_REQUIRED,
                'Start timestamp for earliest bucket'
            )
            ->addOption(
                self::OPTION_END,
                'e',
                InputOption::VALUE_REQUIRED,
                'End timestamp for latest bucket'
            )
            ->addOption(
                self::OPTION_RESOURCE,
                'r',
                InputOption::VALUE_OPTIONAL,
                'Resource if not specified in config'
            )
            ->addOption(
                self::OPTION_CLASS,
                'c',
                InputOption::VALUE_OPTIONAL,
                'Class if not specified in config'
            )
        ;
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     * @noinspection DuplicatedCode currently duplicated in other Commands
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->validateOptionsCombinations($input, $output);

        $this->setInput($input);
        $this->setOutput($output);

        $mlsConfigurationArray = (new Configuration\FromYaml())->getConfigurationByKey(
            $input->getArgument(self::ARGUMENT_KEY)
        );

        $this->setPhretsSession((new PHRETS\SessionBuilder())->fromConfigurationArray($mlsConfigurationArray))
            ->setResourceAlias($input->getArgument(self::ARGUMENT_RESOURCE_ALIAS))
            ->initializeResourcesAndClasses($input, $mlsConfigurationArray);
    }

    /**
     * $input and $output will generally be used from the getters, and were set in initialize.
     * they are still in the signature as required by the Command parent class
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->phretsLogin();
        foreach ($this->getResourcesAndClasses()[self::KEY_CLASSES] as $class) {
            $resource = $this->getResourcesAndClasses()[self::KEY_RESOURCE];
            $output->writeln('Resource: ' . $resource);
            $output->writeln('Class: ' . $class);

            // TODO: Support file output
//            $dataOutput = $this->getOutputStrategy();
//            if ($dataOutput !== null) {
//                $dataOutput->setMlsKey($input->getArgument(self::ARGUMENT_KEY))
//                    ->setResourceName($resource)
//                    ->setClassName($class);
//            }

            // TODO: Support this once output to a file is done
//            $this->initProgressBar();
//            $this->startProgressBar();

            foreach($this->generateBuckets() as $bucket) {
                $start = $bucket->getStartDateTime()->format(\DateTimeInterface::ATOM);
                $end = $bucket->getEndDateTime()->format(\DateTimeInterface::ATOM);

                $extras = $this->getQueryExtras();

                $results = $this->getPhretsSession()->Search(
                    $resource,
                    $class,
                    $this->generateQuery($bucket),
                    $extras
                );

                $count = $results->getTotalResultsCount();

                // TODO: Support file output
                echo "Start: $start; End: $end; Count: $count" . PHP_EOL;

//                $dataOutput->outputResults([$bucketName => $count]);

//                $this->advanceProgressBar(1);
            }

//            $this->finishProgressBar();

//            $dataOutput->complete();
        }
        $this->getPhretsSession()->Disconnect();
        return Command::SUCCESS;
    }

    private function generateQuery(DateTimeRangeInterface $dateTimeRange): string
    {
        $timestampField = $this->getInput()->getOption(self::OPTION_TIMESTAMP_FIELD);
        $start = $dateTimeRange->getStartDateTime()->format(\DateTimeInterface::ATOM);
        $end = $dateTimeRange->getEndDateTime()->format(\DateTimeInterface::ATOM);
        $query = "($timestampField=$start-$end)";

        return $query;
    }

    /** @return DateTimeRange[] */
    private function generateBuckets(): array
    {
        $buckets = []; // TODO: DateTimeRange Sequence

        $currentBucket = null;
        foreach($this->buildDatePeriod() as $timestamp) {
            if ($currentBucket === null) {
                $currentBucket = new DateTimeRange();
                $currentBucket->setStartDateTime($timestamp);
                continue;
            }

            $currentBucket->setEndDateTime($timestamp);
            $buckets[] = $currentBucket;
            $currentBucket = new DateTimeRange();
            $currentBucket->setStartDateTime($timestamp);
        }

        $currentBucket->setEndDateTime(new \DateTime($this->getInput()->getOption(self::OPTION_END)));
        $buckets[] = $currentBucket;

        return $buckets;
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function getOutputStrategy(): ?StrategyInterface
    {
        /** @noinspection DegradedSwitchInspection */
        switch ($this->getInput()->getOption(self::OPTION_OUTPUT)) {
            case 'csv':
                return new ListingsCsv();
            default:
                return null; // TODO: Make a default stdout option.
        }
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function initProgressBar(): self
    {
        $this->progress_bar = new ProgressBar(
            $this->getOutput(), $this->buildDatePeriod()->getRecurrences() ?? 0
        );
        $this->progress_bar->setFormat('very_verbose');
        return $this;
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function startProgressBar(): self
    {
        if (!$this->getInput()->getOption(self::OPTION_NO_PROGRESS_BAR)) {
            $this->getProgressBar()->start();
        }
        return $this;
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function setProgressBarMaxSteps(int $maxSteps): self
    {
        if (!$this->getInput()->getOption(self::OPTION_NO_PROGRESS_BAR)) {
            $this->getProgressBar()->setMaxSteps($maxSteps);
        }
        return $this;
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function advanceProgressBar(int $count): self
    {
        if (!$this->getInput()->getOption(self::OPTION_NO_PROGRESS_BAR)) {
            $this->getProgressBar()->advance($count);
        }
        return $this;
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function finishProgressBar(): self
    {
        if (!$this->getInput()->getOption(self::OPTION_NO_PROGRESS_BAR)) {
            $this->getProgressBar()->finish();
        }
        return $this;
    }

    /** @noinspection PhpUnusedParameterInspection */
    private function validateOptionsCombinations(InputInterface $input, OutputInterface $output): self
    {
        // TODO: Validate stuff

        return $this;
    }

    /** @noinspection DuplicatedCode */
    private function initializeResourcesAndClasses(InputInterface $input, array $mlsConfigurationArray): self
    {
        $specificResource = $input->getOption(self::OPTION_RESOURCE);
        $specificClass = $input->getOption(self::OPTION_CLASS);
        if ($specificResource !== null && $specificClass !== null) {
            $this->setResourcesAndClasses(
                [
                    self::KEY_RESOURCE => $specificResource,
                    self::KEY_CLASSES => [$specificClass],
                ]
            );
        } else {
            $this->setResourcesAndClasses($mlsConfigurationArray['resources'][$this->getResourceAlias()]);
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

    private function getQueryExtras(): array
    {
        $extras = [
            'Format' => 'COMPACT-DECODED',
            'Count' => 2, // 2 is TRUE, 1 is FALSE?
        ];

        return $extras;
    }

    private function buildDatePeriod(): \DatePeriod
    {
        $datePeriod = new \DatePeriod(
            new \DateTime($this->getInput()->getOption(self::OPTION_START)),
            new \DateInterval($this->getInput()->getArgument(self::ARGUMENT_INTERVAL)),
            new \DateTime($this->getInput()->getOption(self::OPTION_END))
        );

        return $datePeriod;
    }

    private function getInput(): InputInterface
    {
        return $this->input; // Will throw if uninitialized
    }

    private function setInput(InputInterface $input): self
    {
        try {
            $this->input; // Attempt to read
        } catch (\Error $e) {
            $this->input = $input; // Variable hasn't been initialized
            return $this;
        }

        throw new \LogicException('Histogram input is already set.');
    }

    private function getOutput(): OutputInterface
    {
        return $this->output; // Will throw if uninitialized
    }

    private function setOutput(OutputInterface $output): self
    {
        try {
            $this->output; // Attempt to read
        } catch (\Error $e) {
            $this->output = $output; // Variable hasn't been initialized
            return $this;
        }

        throw new \LogicException('Histogram output is already set.');
    }

    private function getPhretsSession(): Session
    {
        return $this->phrets_session; // Will throw if uninitialized
    }

    private function setPhretsSession(Session $phrets_session): self
    {
        try {
            $this->phrets_session; // Attempt to read
        } catch (\Error $e) {
            $this->phrets_session = $phrets_session; // Variable hasn't been initialized
            return $this;
        }

        throw new \LogicException('Histogram phrets_session is already set.');
    }

    private function getResourceAlias(): string
    {
        return $this->resource_alias; // Will throw if uninitialized
    }

    private function setResourceAlias(string $resource_alias): self
    {
        try {
            $this->resource_alias; // Attempt to read
        } catch (\Error $e) {
            $this->resource_alias = $resource_alias; // Variable hasn't been initialized
            return $this;
        }

        throw new \LogicException('Histogram resource_alias is already set.');
    }

    private function getResourcesAndClasses(): array
    {
        return $this->resources_and_classes; // Will throw if uninitialized
    }

    private function setResourcesAndClasses(array $resources_and_classes): self
    {
        try {
            $this->resources_and_classes; // Attempt to read
        } catch (\Error $e) {
            $this->resources_and_classes = $resources_and_classes; // Variable hasn't been initialized
            return $this;
        }

        throw new \LogicException('Histogram resources_and_classes is already set.');
    }

    private function getProgressBar(): ProgressBar
    {
        return $this->progress_bar; // Will throw if uninitialized
    }
}
