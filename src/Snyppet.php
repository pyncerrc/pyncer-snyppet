<?php
namespace Pyncer\Snyppet;

use Pyncer\Exception\UnexpectedValueException;
use Pyncer\Snyppet\SnyppetInterface;

use const DIRECTORY_SEPARATOR as DS;

use function Pyncer\IO\clean_path as pyncer_io_clean_path;

class Snyppet implements SnyppetInterface
{
    /**
     * @var array<string, mixed>
     */
    private ?array $composer = null;

    /**
     * @param string $alias A unique alias to represent this snyppet.
     * @param string $dir The directory this snyppet resides in.
     * @param array<string, array<string>> $middlewares An array of names of
     *  middlewares to run automatically when the snyppet is initialized.
     */
    public function __construct(
        private readonly string $alias,
        private readonly string $dir,
        private readonly array $middlewares = [],
    ) {}

    /**
     * @inheritdoc
     */
    public function initialize(): void
    {
        $file = $this->getDir() . DS . 'initialize.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }

    /**
     * @inheritdoc
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @inheritdoc
     */
    public function getDir(): string
    {
        $composer = $this->getComposer();

        $dir = $composer['extra']['snyppet']['path'] ?? null;
        if ($dir !== null) {
            return $this->dir . pyncer_io_clean_path($dir);
        }

        $dir = $composer['autoload']['psr-4'] ?? null;
        if ($dir === null || !count($dir)) {
            return $this->dir;
        }

        $dir = array_values($dir)[0];

        if (is_array($dir)) {
            return $this->dir . pyncer_io_clean_path($dir[0]);
        }

        return $this->dir . pyncer_io_clean_path($dir);
    }

    /**
     * @inheritdoc
     */
    public function getMiddlewares(string $type): array
    {
        $middlewares = [];

        $namespace = $this->getNamespace();

        if ($namespace === null) {
            return $middlewares;
        }

        $namespace .= '\\Middleware\\';

        $typeMiddlewares = $this->middlewares[$type] ?? [];

        foreach ($typeMiddlewares as $middleware) {
            $class = $namespace . $middleware . 'Middleware';

            if (!class_exists($class, true)) {
                throw new UnexpectedValueException('Middleware not found. (' . $class . ')');
            }

            $middlewares[] = new $class();
        }

        return $middlewares;
    }

    /**
     * @inheritdoc
     */
    public function getName(): ?string
    {
        $composer = $this->getComposer();

        return $composer['extra']['snyppet']['name'] ??
            $composer['name'] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): ?string
    {
        $composer = $this->getComposer();

        return $composer['extra']['snyppet']['description'] ??
            $composer['description'] ??
            null;
    }

    /**
     * @inheritdoc
     */
    public function getVersion(): ?string
    {
        $composer = $this->getComposer();

        $version = $composer['extra']['snyppet']['version'] ??
            $composer['version'] ?? null;

        if ($version !== null) {
            return strval($version);
        }

        $name = $composer['name'] ?? null;

        if ($name === null) {
            return null;
        }

        return \Composer\InstalledVersions::getVersion($name);
    }

    /**
     * @inheritdoc
     */
    public function getNamespace(): ?string
    {
        $composer = $this->getComposer();

        $namespace = $composer['extra']['snyppet']['namespace'] ?? null;
        if ($namespace !== null) {
            return '\\' . trim($namespace, '\\');
        }

        $namespace = $composer['autoload']['psr-4'] ?? null;
        if ($namespace === null || !count($namespace)) {
            return null;
        }

        return '\\' . trim(array_keys($namespace)[0], '\\');
    }

    /**
     * Gets the composer file data as an array.
     *
     * @return array{
     *     name: null|string,
     *     description: null|string,
     *     version: null|string,
     *     autoload: null|array{
     *         psr-4: null|array<string, string|array<string>>,
     *     },
     *     extra: null|array{
     *         snyppet: null|array{
     *              name: null|string,
     *              description: null|string,
     *              path: null|string,
     *              namespace: null|string,
     *              version: null|string,
     *         },
     *     },
     * }
     */
    protected function getComposer(): array
    {
        if ($this->composer !== null) {
            return $this->composer;
        }

        $this->composer = [];

        $file = $this->dir . DS . 'composer.json';

        if (file_exists($file)) {
            $json = file_get_contents($file);

            if ($json !== false) {
                $json = json_decode($json, true);

                $this->composer = $json;
            }
        }

        return $this->composer;
    }
}
