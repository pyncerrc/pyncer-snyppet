<?php
namespace Pyncer\Snyppet;

use Pyncer\Database\ConnectionInterface;
use Pyncer\Snyppet\InstallInterface;
use Pyncer\Snyppet\SnyppetManager;

abstract class AbstractInstall implements InstallInterface
{
    public function __construct(
        protected ConnectionInterface $connection,
    ) {}

    /**
     * @inheritdoc
     */
    public function install(): bool
    {
        $this->connection->start();

        try {
            if (!$this->safeInstall()) {
                $this->connection->rollback();
                return false;
            }

            $this->connection->commit();
        } catch (\Throwable $e) {
            $this->connection->rollBack();

            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function uninstall(): bool
    {
        $this->connection->start();

        try {
            if (!$this->safeUninstall()) {
                $this->connection->rollback();
                return false;
            }

            $this->connection->commit();
        } catch (\Throwable $e) {
            $this->connection->rollBack();

            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function hasRelated(string $snyppetAlias): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function installRelated(string $snyppetAlias): bool
    {
        $this->connection->start();

        try {
            if (!$this->safeInstallRelated($snyppetAlias)) {
                $this->connection->rollback();
                return false;
            }

            $this->connection->commit();
        } catch (\Throwable $e) {
            $this->connection->rollBack();

            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function uninstallRelated(string $snyppetAlias): bool
    {
        $this->connection->start();

        try {
            if (!$this->safeUninstallRelated($snyppetAlias)) {
                $this->connection->rollback();
                return false;
            }

            $this->connection->commit();
        } catch (\Throwable $e) {
            $this->connection->rollBack();

            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function upgradeRelated(
        string $snyppetAlias,
        string $version
    ): bool
    {
        $this->connection->start();

        try {
            if (!$this->safeUpgradeRelated(
                $snyppetAlias,
                $version
            )) {
                $this->connection->rollback();
                return false;
            }

            $this->connection->commit();
        } catch (\Throwable $e) {
            $this->connection->rollBack();

            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function downgradeRelated(
        string $snyppetAlias,
        string $version
    ): bool
    {
        $this->connection->start();

        try {
            if (!$this->safeDowngradeRelated(
                $snyppetAlias,
                $version
            )) {
                $this->connection->rollback();
                return false;
            }

            $this->connection->commit();
        } catch (\Throwable $e) {
            $this->connection->rollBack();

            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getRequired(): array
    {
        return [];
    }

    protected abstract function safeInstall(): bool;

    protected abstract function safeUninstall(): bool;

    protected function safeInstallRelated(string $snyppetAlias): bool
    {
        return true;
    }

    protected function safeUninstallRelated(string $snyppetAlias): bool
    {
        return true;
    }

    protected function safeUpgradeRelated(
        string $snyppetAlias,
        string $version
    ): bool
    {
        return true;
    }

    protected function safeDowngradeRelated(
        string $snyppetAlias,
        string $version
    ): bool
    {
        return true;
    }
}
