<?php
/*
 * Created for oxrun
 * Author: Stefan Moises <moises@shoptimax.de>
 * Some code in this file was taken from OXID Console package.
 *
 * (c) Eligijus Vitkauskas <eligijusvitkauskas@gmail.com>
 */

namespace Oxrun\Command\Module;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\NullOutput;

use OxidEsales\EshopCommunity\Core\Exception\InputException;
use Oxrun\Helper\ModuleStateFixer;
use Oxrun\Helper\SpecificShopConfig;

/**
 * Class FixCommand
 * @package Oxrun\Command\Module
 */
class FixCommand extends Command
{

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('module:fix')
            ->setDescription('Fixes a module')
            ->addOption('shopId', null, InputOption::VALUE_OPTIONAL, null)
            ->addOption('base-shop', 'b', InputOption::VALUE_NONE, null)
            ->addOption('no-debug', 'x', InputOption::VALUE_NONE, null)
            ->addOption('all', 'a', InputOption::VALUE_NONE, null)
            ->addArgument('module', InputArgument::OPTIONAL, 'Module name');
    }

    /**
     * {@inheritdoc}
     */
    public function getHelp()
    {
        $sHelp = 'Usage: module:fix [options] <module_id> [<other_module_id>...]';
        $sHelp .= "\n";
        $sHelp .= 'This command fixes information stored in database of modules';
        $sHelp .= "\n";
        $sHelp .= 'Available options:';
        $sHelp .= "\n";
        $sHelp .= '  -a, --all         Passes all modules';
        $sHelp .= "\n";
        $sHelp .= '  -b, --base-shop   Fix only on base shop';
        $sHelp .= "\n";
        $sHelp .= '  --shopId=<shop_id>  Specifies in which shop to fix states';
        $sHelp .= "\n";
        $sHelp .= '  -x, --no-debug    No debug output';
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
        //die(print_r($input->getOptions()));
        $shopId = $input->getOption('shopId');
        if ($shopId) {
            $this->getApplication()->switchToShopId($shopId);
        }
        $oDebugOutput = $input->getOption('no-debug')
            ? new NullOutput()
            : $output;

        try {
            $aShopConfigs = $this->parseShopConfigs($input);
        } catch (oxInputException $oEx) {
            $oOutput->writeLn($oEx->getMessage());
            return;
        }
    
        /** @var ModuleStateFixer $oModuleStateFixer */
        $oModuleStateFixer = new ModuleStateFixer();
        $oModuleStateFixer->setDebugOutput($oDebugOutput);
        /** @var oxModule $oModule */
        $oModule = oxNew('oxModule');
        foreach ($aShopConfigs as $oConfig) {
            try {
                $aModuleIds = $this->parseModuleIds($input, $oConfig);
            } catch (InputException $oEx) {
                $output->writeLn($oEx->getMessage());
                return;
            }
            $sShopId = $oConfig->getShopId();
            $oDebugOutput->writeLn('[DEBUG] Working on shop id ' . $sShopId);
            $oModuleStateFixer->setConfig($oConfig);
            $oModuleStateFixer->setDebugOutput($oDebugOutput);
            foreach ($aModuleIds as $sModuleId) {
                if (!$oModule->load($sModuleId)) {
                    $oDebugOutput->writeLn("[DEBUG] {$sModuleId} can not be loaded - skipping");
                    continue;
                }
                //$oDebugOutput->writeLn("[DEBUG] Fixing {$sModuleId} module");
                $blWasActive = $oModule->isActive();
                try {
                    if ($oModuleStateFixer->fix($oModule, $oConfig)) {
                        $oDebugOutput->writeLn("[DEBUG] {$sModuleId} extensions fixed");
                        if (!$blWasActive && $oModule->isActive()) {
                            $oDebugOutput->writeLn("[WARN] {$sModuleId} is now activated again!");
                        }
                    }
                } catch (oxShopException $ex) {
                    $oDebugOutput->writeLn();
                    $output->writeLn("[ERROR]:" . $ex->getMessage());
                    $output->writeLn("No success! You have to fix that errors manually!!\n");
                    exit(1);
                }
            }
            $this->cleanup($oConfig, $oDebugOutput);
        }
        
        $output->writeLn("<info>Modules fixed</info>");
    }

    /**
     * Parse and return module ids from input
     *
     * @return array
     *
     * @throws oxInputException
     */
    protected function parseModuleIds(InputInterface $input, $oConfig)
    {
        if ($input->getOption('all')) {
            return $this->getAvailableModuleIds($oConfig);
        }
        if (count($input->getArguments()) < 2) { // Note: first argument is command name
            /** @var InputException $oEx */
            $oEx = new InputException();
            $oEx->setMessage('Please specify at least one module as argument or use --all (-a) option');
            throw $oEx;
        }
        $aModuleIds = $input->getArguments();
        array_shift($aModuleIds); // Getting rid of command name argument
        $aAvailableModuleIds = $this->getAvailableModuleIds($oConfig);
        // Checking if all provided module ids exist
        foreach ($aModuleIds as $sModuleId) {
            if (!in_array($sModuleId, $aAvailableModuleIds)) {
                /** @var InputException $oEx */
                $oEx = new InputException();
                $oEx->setMessage("{$sModuleId} module does not exist");
                throw $oEx;
            }
        }
        return $aModuleIds;
    }
    /**
     * Parse and return shop config objects from input
     *
     * @return oxSpecificShopConfig[]
     *
     * @throws InputException
     */
    protected function parseShopConfigs(InputInterface $input)
    {
        if ($input->getOption('base-shop')) {
            return array(oxRegistry::getConfig());
        }
        if ($mShopId = $input->getOption('shopId')) {
            // No value for option were passed
            if (is_bool($mShopId)) {
                /** @var InputException $oEx */
                $oEx = new InputException();
                $oEx->setMessage('Please specify shop id in option following this format --shopId=<shop_id>');
                throw $oEx;
            }
            if ($oConfig = SpecificShopConfig::get($mShopId)) {
                return array($oConfig);
            }
            /** @var InputException $oEx */
            $oEx = new InputException();
            $oEx->setMessage('Shop id does not exist');
            throw $oEx;
        }
        return SpecificShopConfig::getAll();
    }

    /**
     * Get all available module ids
     *
     * @return array
     */
    protected function getAvailableModuleIds($oConfig)
    {
        // We are calling getModulesFromDir() because we want to refresh
        // the list of available modules. This is a workaround for OXID
        // bug.
        $oModuleList = oxNew('oxModuleList');
        $oModuleList->setConfig($oConfig);
        $oModuleList->getModulesFromDir($oConfig->getModulesDir());
        $_aAvailableModuleIds = array_keys($oConfig->getConfigParam('aModulePaths'));
        if (!is_array($_aAvailableModuleIds)) {
            $_aAvailableModuleIds = array();
        }
        return $_aAvailableModuleIds;
    }

    /**
     * @param $oDebugOutput
     * @param $oModuleList
     */
    protected function cleanup($oConfig, $oDebugOutput)
    {
        $oModuleList = oxNew("oxModuleList");
        $oModuleList->setConfig($oConfig);
        $aDeletedExt = $oModuleList->getDeletedExtensions();
        if ($aDeletedExt) {
            //collecting deleted extension IDs
            $aDeletedExtIds = array_keys($aDeletedExt);
            foreach ($aDeletedExtIds as $sIdIndex => $sId) {
                $oDebugOutput->writeLn(
                    "[ERROR] Module $sId has errors so module will be removed, including all settings"
                );
                if (isset($aDeletedExt[$sId]['extensions'])) {
                    foreach ($aDeletedExt[$sId]['extensions'] as $sClass => $aExtensions) {
                        foreach ($aExtensions as $sExtension) {
                            $sExtPath = $oConfig->getModulesDir() . $sExtension . '.php';
                            $oDebugOutput->writeLn("[ERROR] $sExtPath not found");
                        }
                    }
                }
            }
        }
        $oModuleList->cleanup();
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->getApplication()->bootstrapOxid();
    }
}
