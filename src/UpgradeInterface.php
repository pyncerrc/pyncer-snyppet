<?php
namespace Pyncer\Snyppet;

use Pyncer\Snyppet\InstallInterface;

interface UpgradeInterface extends InstallInterface
{
    /**
     * Gets the version string this upgrade belongs to.
     *
     * @return string The version string of this upgrade.
     */
    public function getVersion(): string;
}
