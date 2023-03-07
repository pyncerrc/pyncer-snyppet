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
}
