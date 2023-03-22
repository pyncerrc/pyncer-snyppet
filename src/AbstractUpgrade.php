<?php
namespace Pyncer\Snyppet;

use Pyncer\Database\ConnectionInterface;
use Pyncer\Snyppet\AbstractInstall;
use Pyncer\Snyppet\UpgradeInterface;
use Pyncer\Snyppet\SnyppetManager;

abstract class AbstractUpgrade extends AbstractInstall implements
    UpgradeInterface
{
    public function __construct(
        ConnectionInterface $connection,
        private string $version,
    ) {
        parent::__construct($connection);
    }

    public function getVersion(): string
    {
        return $this->version;
    }
}
