<?php
namespace Pyncer\Snyppet;

use Pyncer\Database\ConnectionInterface;

interface InstallInterface
{
    public function install(): bool;
    public function uninstall(): bool;

    public function hasRelated(string $snyppetAlias): bool;
    public function installRelated(string $snyppetAlias): bool;
    public function uninstallRelated(string $snyppetAlias): bool;

    public function upgradeRelated(string $snyppetAlias, string $version): bool;
    public function downgradeRelated(string $snyppetAlias, string $version): bool;

    /**
     * Gets an array of snyppets that are required to be installed before this
     * install can be run.
     *
     * The array is made up of key value pairs where the key is the snyppet
     * alias and the value is the version of the snyppet requried. (In a
     * similar format as composer versions.)
     *
     * Currently the version is not taken into account, but will be in future
     * versions.
     *
     * Be aware that circular dependencies are not checked for and will prevent
     * the install from finishing.
     *
     * @return array<string, string> An array of required snyppet aliases.
     */
    public function getRequired(): array;
}
