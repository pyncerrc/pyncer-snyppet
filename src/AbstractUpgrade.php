<?php
namespace Pyncer\Snyppet;

use Pyncer\Database\ConnectionInterface;
use Pyncer\Snyppet\AbstractInstall;
use Pyncer\Snyppet\UpgradeInterface;

abstract class AbstractUpgrade extends AbstractInstall implements
    UpgradeInterface
{
    public function __construct(
        protected ConnectionInterface $connection,
        private string $version
    ) {}

    public function getVersion(): string
    {
        return $this->version;
    }
}
