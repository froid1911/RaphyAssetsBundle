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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class AbstractAssetsUninstallCommand is a generic commad to install assets
 *
 * @author Raphael De Freitas <raphael@de-freitas.net>
 */
abstract class AbstractAssetsUninstallCommand extends ContainerAwareCommand
{
    /**
     * Contains the files to uninstall
     *
     * @var string[] $filesToUninstall An array where the value is the destination file to remove
     */
    private $filesToUninstall = null;

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
        if ($this->filesToUninstall == null || $this->package == null || $this->bundleName == null) {
            throw new \RuntimeException("The uninstaller is bad configured");
        }

        // Checking the destination directory
        $destinationDirectory = realpath($this->getContainer()->get("kernel")->locateResource("@" . $this->bundleName) . "/Resources/public");
        if ($destinationDirectory === false) {
            throw new IOException("The directory @" . $this->bundleName . "/Resources/public is not a directory or not exists");
        }


        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
            $output->writeln("Uninstall <comment>" . $this->package . "</comment>.");
        }

        // Uninstalling
        /** @var Filesystem $filesystem */
        $filesystem = $this->getContainer()->get("filesystem");
        foreach ($this->filesToUninstall as $destinationName) {
            $destinationPath = $destinationDirectory . DIRECTORY_SEPARATOR . $destinationName;
            if ($filesystem->exists($destinationPath)) {
                $filesystem->remove($destinationPath);
                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                    $output->writeln("<info>[OK]</info> " . $destinationPath);
                }
            }

        }
    }


    /**
     * Set the files to install and their destination
     *
     * @param string[] $filesToUninstall An array where the value is the destination file to remove
     * @return $this
     */
    protected function setFilesToUninstall(array $filesToUninstall)
    {
        $this->filesToUninstall = $filesToUninstall;

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