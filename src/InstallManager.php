<?php
namespace Pyncer\Snyppet;

use Pyncer\Data\Mapper\MapperAdaptorInterface;
use Pyncer\Data\Model\ModelInterface;
use Pyncer\Database\ConnectionInterface;
use Pyncer\Database\Exception\QueryException;
use Pyncer\Snyppet\Exception\SnyppetInstalledException;
use Pyncer\Snyppet\Exception\SnyppetNotFoundException;
use Pyncer\Snyppet\Exception\SnyppetNotInstalledException;
use Pyncer\Snyppet\SnyppetManager;

use function Pyncer\IO\filenames as pyncer_io_filenames;
use function Pyncer\Utility\class_implements as pyncer_class_implements;

use const DIRECTORY_SEPARATOR as DS;

class InstallManager
{
    /**
     * @var array<string, bool> Tracks which snyppets are installed.
     */
    private array $installed = [];

    public function __construct(
        protected ConnectionInterface $connection,
        protected MapperAdaptorInterface $mapperAdaptor,
        protected SnyppetManager $snyppetManager,
    ) {}

    /**
     * Installs the specified snyppet.
     *
     * @param string $snyppetAlias The snyppet to install.
     * @return bool True on success, otherwise false.
     */
    public function install(string $snyppetAlias): bool
    {
        if (!$this->snyppetManager->has($snyppetAlias)) {
            throw new SnyppetNotFoundException($snyppetAlias);
        }

        if ($this->isInstalled($snyppetAlias)) {
            throw new SnyppetInstalledException($snyppetAlias);
        }

        $install = $this->getInstall($snyppetAlias);

        if ($install !== null && !$install->install()) {
            return false;
        }

        $this->insertInstall($snyppetAlias);

        if ($snyppetAlias === 'install') {
            return true;
        }

        $this->installed[$snyppetAlias] = true;

        foreach ($this->snyppetManager as $relatedSnyppet) {
            if ($relatedSnyppet->getAlias() === $snyppetAlias) {
                continue;
            }

            if (!$this->isInstalled($relatedSnyppet->getAlias())) {
                continue;
            }

            $relatedInstall = $this->getInstall($relatedSnyppet->getAlias());
            if ($relatedInstall === null) {
                continue;
            }

            if ($relatedInstall->hasRelated($snyppetAlias)) {
                $relatedInstall->installRelated($snyppetAlias);
            }
        }

        return true;
    }

    /**
     * Determines if the specified snyppet is already installed.
     *
     * @param string $snyppetAlias The snyppet to check.
     * @param ?string $version The version to upgrade to.
     * @return bool True if it is already installed, otherwise false.
     */
    public function isInstalled(string $snyppetAlias, ?string $version = null): bool
    {
        if (array_key_exists($snyppetAlias, $this->installed)) {
            return $this->installed[$snyppetAlias];
        }

        $installModel = $this->getInstallModel($snyppetAlias, $version);

        $this->installed[$snyppetAlias] = ($installModel ? true : false);

        return $this->installed[$snyppetAlias];
    }

    /**
     * Uninstalls the specified snyppet.
     *
     * @param string $snyppetAlias The snyppet to uninstall.
     * @return bool True on success, otherwise false.
     */
    public function uninstall(string $snyppetAlias): bool
    {
        if (!$this->snyppetManager->has($snyppetAlias)) {
            throw new SnyppetNotFoundException($snyppetAlias);
        }

        if (!$this->isInstalled($snyppetAlias)) {
            throw new SnyppetNotInstalledException($snyppetAlias);
        }

        $install = $this->getInstall($snyppetAlias);

        if ($install !== null && !$install->uninstall()) {
            return false;
        }

        $this->deleteInstall($snyppetAlias);

        $this->installed[$snyppetAlias] = false;

        foreach ($this->snyppetManager as $relatedSnyppet) {
            if ($relatedSnyppet->getAlias() === $snyppetAlias) {
                continue;
            }

            if (!$this->isInstalled($relatedSnyppet->getAlias())) {
                continue;
            }

            $relatedInstall = $this->getInstall($relatedSnyppet->getAlias());
            if ($relatedInstall === null) {
                continue;
            }

            if ($relatedInstall->hasRelated($snyppetAlias)) {
                $relatedInstall->uninstallRelated($snyppetAlias);
            }
        }

        return true;
    }

    /**
     * Installs all registered snyppets.
     *
     * If a snyppet is already installed, it will be skipped.
     *
     * @param null|array<string> $snyppets An optional array of snyppets to limit
     *  install by.
     *
     * @return bool True on success, otherwise false.
     */
    public function installAll(?array $snyppets = null): bool
    {
        $success = true;

        foreach ($this->snyppetManager as $snyppet) {
            if ($snyppets !== null &&
                !in_array($snyppet->getAlias(), $snyppets)
            ) {
                continue;
            }

            if ($this->isInstalled($snyppet->getAlias())) {
                continue;
            }

            if (!$this->install($snyppet->getAlias())) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Upgrades the specified snyppet to the specified version.
     *
     * If no version is specified, it will be upgraded to the latest.
     *
     * @param string $snyppetAlias The snyppet to upgrade.
     * @param ?string $version The version to upgrade to.
     * @return bool True on success, otherwise false.
     */
    public function upgrade(
        string $snyppetAlias,
        ?string $version = null
    ): bool
    {
        if (!$this->snyppetManager->has($snyppetAlias)) {
            throw new SnyppetNotFoundException($snyppetAlias);
        }

        $upgrades = $this->getUpgrades($snyppetAlias);

        foreach ($upgrades as $upgrade) {
            // Don't upgrade already upgraded
            if ($this->getInstallModel($snyppetAlias, $upgrade->getVersion())) {
                continue;
            }

            // Only upgrade to specified version
            if ($version !== null && $upgrade->getVersion() > $version) {
                continue;
            }

            if (!$upgrade->install()) {
                return false;
            }

            $this->insertInstall($snyppetAlias, $upgrade->getVersion());

            foreach ($this->snyppetManager as $relatedSnyppet) {
                if ($relatedSnyppet->getAlias() === $snyppetAlias) {
                    continue;
                }

                if (!$this->isInstalled($relatedSnyppet->getAlias())) {
                    continue;
                }

                $relatedInstall = $this->getInstall($relatedSnyppet->getAlias());
                if ($relatedInstall === null) {
                    continue;
                }

                if ($relatedInstall->hasRelated($snyppetAlias)) {
                    if (!$relatedInstall->upgradeRelated(
                        $snyppetAlias,
                        $upgrade->getVersion()
                    )) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Downgrades the snyppet to the specified version.
     *
     * @param string $snyppetAlias The snyppet to downgrade.
     * @param string $version The version to downgrade to.
     * @return bool True on success, otherwise false.
     */
    public function downgrade(string $snyppetAlias, string $version): bool
    {
        if (!$this->snyppetManager->has($snyppetAlias)) {
            throw new SnyppetNotFoundException($snyppetAlias);
        }

        $upgrades = $this->getUpgrades($snyppetAlias);
        $upgrades = array_reverse($upgrades);

        foreach ($upgrades as $upgrade) {
            if (!$this->getInstallModel(
                $snyppetAlias,
                $upgrade->getVersion()
            )) {
                continue;
            }

            if ($upgrade->getVersion() < $version) {
                break;
            }

            foreach ($this->snyppetManager as $relatedSnyppet) {
                if ($relatedSnyppet->getAlias() === $snyppetAlias) {
                    continue;
                }

                if (!$this->isInstalled($relatedSnyppet->getAlias())) {
                    continue;
                }

                $relatedInstall = $this->getInstall($relatedSnyppet->getAlias());
                if ($relatedInstall === null) {
                    continue;
                }

                if ($relatedInstall->hasRelated($snyppetAlias)) {
                    if (!$relatedInstall->downgradeRelated(
                        $snyppetAlias,
                        $upgrade->getVersion()
                    )) {
                        return false;
                    }
                }
            }

            if (!$upgrade->uninstall()) {
                return false;
            }

            $this->deleteInstall($snyppetAlias, $upgrade->getVersion());
        }

        return true;
    }

    /**
     * Upgrades all currently installed snyppets.
     *
     * @param null|array<string> $snyppets An optional array of snyppets to limit
     *  upgrade by.
     *
     * @return bool True on success, otherwise false.
     */
    public function upgradeAll(?array $snyppets = null): bool
    {
        $success = true;

        foreach ($this->snyppetManager as $snyppet) {
            if ($snyppets !== null &&
                !in_array($snyppet->getAlias(), $snyppets)
            ) {
                continue;
            }

            if (!$this->isInstalled($snyppet->getAlias())) {
                continue;
            }

            if (!$this->upgrade($snyppet->getAlias())) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Installs the specified snyppet if it is not already installed,
     * otherwise it will upgrade it to the latest version.
     *
     * @param string $snyppetAlias The snyppet to install or upgrade.
     * @return bool True on success, otherwise false.
     */
    public function installOrUpgrade(string $snyppetAlias): bool
    {
        if (!$this->snyppetManager->has($snyppetAlias)) {
            throw new SnyppetNotFoundException($snyppetAlias);
        }

        if ($this->isInstalled($snyppetAlias)) {
            return $this->upgrade($snyppetAlias);
        }

        return $this->install($snyppetAlias);
    }

    /**
     * Installs or upgrades all available snyppets.
     *
     * @return bool True on success, otherwise false.
     */
    public function installOrUpgradeAll(): bool
    {
        $success = true;

        foreach ($this->snyppetManager as $snyppet) {
            if (!$this->installOrUpgrade($snyppet->getAlias())) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Gets an install table ModelInterface object from the mapper adaptor.
     *
     * @param string $snyppetAlias The snyppet to get a model of.
     * @param ?string $version The version to get a model of.
     * @return ModelInterface An install model.
     */
    protected function getInstallModel(
        string $snyppetAlias,
        ?string $version = null
    ): ?ModelInterface
    {
        $installMapper = $this->mapperAdaptor->getMapper();

        $data = $this->mapperAdaptor->getFormatter()->formatData([
            'alias' => $snyppetAlias,
            'version' => $version,
        ]);

        $installModel = null;

        try {
            $installModel = $installMapper->selectByColumns($data);
        } catch (QueryException $e) {
            // Do nothing.
        }

        return $installModel;
    }

    /**
     * Inserts the specified snyppet and version into the install table.
     *
     * @param string $snyppetAlias The snyppet to insert.
     * @param ?string $version The version to insert.
     * @return bool True on success, otherwise false.
     */
    protected function insertInstall(
        string $snyppetAlias,
        ?string $version = null
    ): bool
    {
        $installModel = $this->mapperAdaptor->forgeModel([
            'alias' => $snyppetAlias,
            'version' => $version,
        ]);

        $result = $this->mapperAdaptor->getMapper()->insert($installModel);

        // Main install is always to latest spec so any upgrades will already
        // be installed.
        if ($version === null) {
            $upgrades = $this->getUpgrades($snyppetAlias);

            foreach ($upgrades as $upgrade) {
                $this->insertInstall($snyppetAlias, $upgrade->getVersion());
            }
        }

        return $result;
    }

    /**
     * Deletes teh specified snyppet and version from the install table.
     *
     * If no version is specified all installs for the specified snyppet will
     * be deleted.
     *
     * If a version is specified it and any later versions will be deleted.
     *
     * @param string $snyppetAlias The snyppet to delete.
     * @param ?string $version The version to delete.
     * @return bool True on success, false otherwise.
     */
    protected function deleteInstall(
        string $snyppetAlias,
        ?string $version = null
    ): bool
    {
        $installMapper = $this->mapperAdaptor->getMapper();
        $formatter = $this->mapperAdaptor->getFormatter();

        if ($version === null) {
            $data = $formatter->formatData([
                'alias' => $snyppetAlias,
            ]);

            $affectedRows = $installMapper->deleteAllByColumns($data);
        } else {
            $affectedRows = $installMapper->deleteAllByQuery(
                function($query) use ($formatter, $snyppetAlias, $version) {
                    // Alias
                    $data = $formatter->formatData([
                        'alias' => $snyppetAlias,
                    ]);

                    $key = array_keys($data)[0];
                    $value = array_values($data)[0];
                    $query->getWhere()->compare($key, $value);

                    // Version
                    $data = $formatter->formatData([
                        'version' => $version,
                    ]);

                    $key = array_keys($data)[0];
                    $value = array_values($data)[0];
                    $query->getWhere()->compare($key, $value, '>=');
                }
            );
        }

        return ($affectedRows > 0);
    }

    /**
     * @return null|InstallInterface
     */
    public function getInstall(string $snyppetAlias): ?InstallInterface
    {
        $snyppet = $this->snyppetManager->get($snyppetAlias);

        $namespace = $snyppet->getNamespace();

        if ($namespace === null) {
            return null;
        }

        $class = $namespace . '\\Install\\Install';

        if (class_exists($class, true)) {
            return new $class($this->connection);
        }

        return null;
    }

    /**
     * @return array<UpgradeInterface>
     */
    public function getUpgrades(string $snyppetAlias): array
    {
        $upgrades = [];

        $snyppet = $this->snyppetManager->get($snyppetAlias);

        $dir = $snyppet->getDir() . DS . 'Install';

        if (!file_exists($dir)) {
            return [];
        }

        $filenames = pyncer_io_filenames($dir, 'php', true);

        $namespace = $snyppet->getNamespace();
        if ($namespace === null) {
            return $upgrades;
        }

        $namespace .= '\\Install\\';

        foreach ($filenames as $filename) {
            if ($filename === 'Install') {
                continue;
            }

            $class = $namespace . $filename;

            if (!pyncer_class_implements(
                $filename,
                '\Pyncer\Snyppet\UpgradeInterface',
                true
            )) {
                continue;
            }

            $upgrades[] = new $class($this->connection);
        }

        usort($upgrades, function($a, $b) {
            return $a->getVersion() <=> $b->getVersion();
        });

        return $upgrades;
    }
}
