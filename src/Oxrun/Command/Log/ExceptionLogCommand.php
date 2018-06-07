<?php

namespace Oxrun\Command\Log;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

/**
 * Class ExceptionLogCommand
 * @package Oxrun\Command\Log
 */

class ExceptionLogCommand extends Command
{
    /**
     * Log file
     *
     * @var string
     */
    protected $sLogFile = null;

    /**
     * @var bool
     */
    protected $displayReverse = false;

    /**
     * @var string
     */
    protected $sFilter = '';

    /**
     * @var int
     */
    protected $numLines = 100;

    /**
     * Regex patterns for log entries
     *
     * @var array
     */
    protected $aRegEx = [
        'DATE' => '/\[([0-9]{2}\s[A-Za-z]{3}\s[0-9:.]*\s[0-9]{4})\]/',
        // \x5c stands for "backslash",
        // see https://stackoverflow.com/questions/11044136/right-way-to-escape-backslash-in-php-regex
        'ERROR_TYPE' => '/\[.*\]\s\[([a-z]*)\]\s\[([\x5c a-zA-Z]*)\]/', // e.g. [type Error] or [stacktrace]
        'ERROR_CODE' => '/\[code\s([0-9]*)]/', // e.g. [code 0]
        'FILE' => '/\[file\s([A-Za-z0-9\/\.-]*)]/',
        'TRACE' => '/(#[0-9]*\s.*)/sim',
    ];

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('log:exceptionlog')
            ->setDescription('Read EXCEPTION_LOG.txt and display entries.')
            ->addOption('lines', 'l', InputOption::VALUE_OPTIONAL, null)
            ->addOption('filter', 'f', InputOption::VALUE_OPTIONAL, null)
            ->addOption('raw', 'r', InputOption::VALUE_NONE, null)
            ->addOption('tail', 't', InputOption::VALUE_NONE, null);
    }

    /**
     * {@inheritdoc}
     */
    public function getHelp()
    {
        $sHelp = "Usage: log:exceptionlog [options]\n";
        $sHelp .= "Read EXCEPTION_LOG.txt and display entries.\n";
        $sHelp .= "Available options:\n";
        $sHelp .= "  -t, --tail         Display last lines in file\n";
        $sHelp .= "  -l, --lines   Number of lines to show\n";
        $sHelp .= "  -f, --filter  String to search for / filter\n";
        $sHelp .= "  -r, --raw   Raw output, no table";
        return $sHelp;
    }
    /**
     * Executes the current command.
     *
     * @param InputInterface $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('tail')) {
            $this->displayReverse = $input->getOption('tail');
        }
        if ($input->getOption('lines')) {
            $this->numLines = $input->getOption('lines');
        }
        if ($input->getOption('filter')) {
            $this->sFilter = $input->getOption('filter');
        }

        $oConfig = \OxidEsales\Eshop\Core\Registry::getConfig();
        $this->sLogFile = $oConfig->getConfigParam('sShopDir') . DIRECTORY_SEPARATOR . "log" . DIRECTORY_SEPARATOR. "EXCEPTION_LOG.txt";

        $output->writeln("<info>Logfile: {$this->sLogFile}</info>");

        $aEntries = $this->getLogEntries($output);

        // raw output?
        if ($input->getOption('raw')) {
            foreach ($aEntries as $logEntry) {
                $output->writeln("<info>$logEntry</info>");
                $output->writeln("<info></info>");
            }
            return;
        }

        // parse info for table view
        $aSplitEntries = array();
        $aSplitEntries[] = array("Date", "Type", "Code", "File", "Message");
        foreach ($aEntries as $logEntry) {
            $aMatches = array();
            preg_match($this->aRegEx['ERROR_TYPE'], $logEntry, $aMatches);
            $sErrType = $aMatches[2];

            $aMatches = array();
            preg_match($this->aRegEx['DATE'], $logEntry, $aMatches);
            $sDate = $aMatches[1];

            $aMatches = array();
            preg_match($this->aRegEx['ERROR_CODE'], $logEntry, $aMatches);
            $sErrCode = $aMatches[1];

            $aMatches = array();
            preg_match($this->aRegEx['FILE'], $logEntry, $aMatches);
            $sErrFile = $aMatches[1];

            $aMatches = array();
            preg_match($this->aRegEx['TRACE'], $logEntry, $aMatches);
            $sErrTrace = $aMatches[1];

            $aSplitEntries[] = array($sDate, $sErrType, $sErrCode, $sErrFile, $sErrTrace);
        }
        $aEntriesDisplay = array_map(
            function ($item) {
                return array($item);
            },
            $aEntries
        );

        $table = new Table($output);
        $table->setHeaders(array_shift($aSplitEntries))->setRows($aSplitEntries);
        $table->render();
    }

    /**
     * Get log file entries
     *
     * @return array
     */
    protected function getLogEntries($output)
    {
        $results = array();
        if (file_exists($this->sLogFile)) {
            $lines = $this->numLines;
            // special case - if we have a filter,
            // we have to read the whole file first
            // and then limit via counter
            if ($this->sFilter != '') {
                $lines = 'all';
            }
            $output = $this->tail($this->sLogFile, $lines, !$this->displayReverse);
            $output = explode("\n", $output);
            $count = 0;
            // filter / cleanup
            foreach ($output as $line) {
                // if we have a filter, we've read in the whole file,
                // so there may be more lines than requested
                if ($count >= $this->numLines) {
                    break;
                }
                if (trim($line) == '' || ($this->sFilter != '' && stripos($line, $this->sFilter) === false)) {
                    continue;
                }
                $results[] = $line;
                $count++;
            }
            return $results;
        } else {
            $output->writeln("<error>No logfile found!</error>");
        }
    }

    /**
     * Read file parts
     * @param string $filename  The file name
     * @param int    $lines     Number of lines
     * @param bool   $fromStart Start from line 0
     * @param int    $buffer    Buffer size
     *
     * @return string
     */
    protected function tail($filename, $lines = 50, $fromStart = false, $buffer = 4096)
    {
        $output = '';
        // Open the file
        $f = fopen($filename, "rb");
        // read complete file, e.g. when filtering?
        if ($lines == "all") {
            while (!feof($f)) {
                $line = fgets($f);
                $output .= $line;
            }
            // Close file and return
            fclose($f);
            return $output;
        }
        // read from the beginning of the file
        if ($fromStart) {
            $currLine = 0;
            while ($currLine < $lines) {
                $line = fgets($f);
                $output .= $line;
                $currLine++;
            }
            // Close file and return
            fclose($f);
            return $output;
        }

        // Jump to last character
        fseek($f, -1, SEEK_END);
        // Read it and adjust line number if necessary
        // (Otherwise the result would be wrong if file doesn't end with a blank line)
        if (fread($f, 1) != "\n") {
            $lines -= 1;
        }
        // Start reading
        $chunk = '';
        // While we would like more
        while (ftell($f) > 0 && $lines >= 0) {
            // Figure out how far back we should jump
            $seek = min(ftell($f), $buffer);
            // Do the jump (backwards, relative to where we are)
            fseek($f, -$seek, SEEK_CUR);
            // Read a chunk and prepend it to our output
            $output = ($chunk = fread($f, $seek)) . $output;
            // Jump back to where we started reading
            fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
            // Decrease our line counter
            $lines -= substr_count($chunk, "\n");
        }
        // While we have too many lines
        // (Because of buffer size we might have read too many)
        while ($lines++ < 0) {
            // Find first newline and remove all text before that
            $output = substr($output, strpos($output, "\n") + 1);
        }
        // Close file and return
        fclose($f);

        return $output;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->getApplication()->bootstrapOxid();
    }
}
