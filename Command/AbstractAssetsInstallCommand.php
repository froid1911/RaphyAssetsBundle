<?php

/*
 * This file is part of the RaphyAssetsBundle package.
 *
 * (c) Raphael De Freitas <raphael@de-freitas.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Raphy\AssetsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class AbstractAssetsInstallCommand is a generic assets installer for resources bundles
 *
 * @author Raphael De Freitas <raphael@de-freitas.net>
 */
abstract class AbstractAssetsInstallCommand extends ContainerAwareCommand
{
    /**
     * Contains the files to install and their destination
     *
     * @var string[] $filesToInstall An array where the key is the source file and the value is the destination file
     */
    private $filesToInstall = null;

    /**
     * Contains the name of the package where the assets are located
     *
     * @var string
     */
    private $package = null;

    /**
     * Contains the bundle name
     *
     * @var string
     */
    private $bundleName = null;

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Checking the installer configuration
        if ($this->filesToInstall == null || $this->package == null || $this->bundleName == null) {
            throw new \RuntimeException("The installer is bad configured");
        }

        // Checking the source directory
        $sourceDirectory = realpath($this->getContainer()->get("kernel")->getRootDir() . "/../" . $input->getOption("vendor") . "/" . $this->package);
        if ($sourceDirectory === false) {
            throw new IOException("The package " . $this->package . " is not found in the " . $input->getOption("vendor") . " directory");
        }

        // Checking the destination directory
        $destinationDirectory = realpath($this->getContainer()->get("kernel")->locateResource("@" . $this->bundleName) . "/Resources/public");
        if ($destinationDirectory === false) {
            throw new IOException("The directory @" . $this->bundleName . "/Resources/public is not a directory or not exists");
        }

        // Symbolic links or hard copies ?
        if ($input->getOption("symlink")) {
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
                $output->writeln("Trying to install <comment>" . $this->package . "</comment> assets as <comment>symbolic links</comment>.");
            }
        } else {
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
                $output->writeln("Installing <comment>" . $this->package . "</comment> assets as <comment>hard copies</comment>.");
            }
        }

        // Installing
        /** @var Filesystem $filesystem */
        $filesystem = $this->getContainer()->get("filesystem");
        foreach ($this->filesToInstall as $sourceName => $destinationName) {
            $sourcePath = $sourceDirectory . DIRECTORY_SEPARATOR . $sourceName;
            $destinationPath = $destinationDirectory . DIRECTORY_SEPARATOR . $destinationName;
            $filesystem->remove($destinationPath);
            if ($filesystem->exists($sourcePath)) {
                if ($input->getOption("symlink")) {
                    try {
                        $filesystem->symlink($sourcePath, $destinationPath);
                        if (!file_exists($destinationPath)) {
                            throw new IOException("Symbolic link is broken");
                        }
                        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                            $output->writeln("<info>[OK]</info> " . $sourceName);
                        }
                    } catch (IOException $e) {
                        $filesystem->copy($sourcePath, $destinationPath);
                        if (!file_exists($destinationPath)) {
                            throw new IOException("Symbolic link is broken");
                        }
                        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                            $output->writeln("<comment>[OK]</comment> " . $sourceName . " <comment>hard copy</comment>");
                        }
                    }
                } else {
                    $filesystem->copy($sourcePath, $destinationPath);
                    if (!file_exists($destinationPath)) {
                        throw new IOException("Cannot copy " . $sourcePath . " to " . $destinationPath);
                    }
                    if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                        $output->writeln("<info>[OK]</info> " . $sourceName);
                    }
                }
            } else {
                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                    $output->writeln("<error>[KO]</error> " . $sourceName);
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->addOption("vendor", null, InputOption::VALUE_REQUIRED, "The vendor directory name relative to application root", "vendor");
        $this->addOption("symlink", null, InputOption::VALUE_NONE, "Symlinks the assets instead of hard copying them");
    }

    /**
     * Set the files to install and their destination
     *
     * @param string[] $filesToInstall An array where the key is the source file and the value is the destination file
     * @return $this
     */
    protected function setFilesToInstall(array $filesToInstall)
    {
        $this->filesToInstall = $filesToInstall;

        return $this;
    }

    /**
     * Set the package name where the assets are located
     *
     * @param string $package
     * @return $this
     */
    protected function setPackage($package)
    {
        $this->package = $package;

        return $this;
    }

    /**
     * Set the bundle name
     *
     * @param string $bundleName
     * @return $this
     */
    protected function setBundleName($bundleName)
    {
        $this->bundleName = $bundleName;

        return $this;
    }
}