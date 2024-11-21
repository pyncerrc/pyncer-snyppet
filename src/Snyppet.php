<?php
namespace Pyncer\Snyppet;

use Psr\Http\Server\MiddlewareInterface as PsrMiddlewareInterface;
use Pyncer\Exception\UnexpectedValueException;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Snyppet\SnyppetInterface;

use const DIRECTORY_SEPARATOR as DS;

use function Pyncer\IO\clean_path as pyncer_io_clean_path;

class Snyppet implements SnyppetInterface
{
    /**
     * @var null|array{
     *     name?: string,
     *     description?: string,
     *     version?: string,
     *     autoload?: array{
     *         psr-4?: array<string, string|array<string>>,
     *     },
     *     extra?: array{
     *         snyppet?: array{
     *              name?: string,
     *              description?: string,
     *              path?: string,
     *              namespace?: string,
     *              version?: string,
     *              install?: string,
     *         },
     *     },
     * }
     */
    private ?array $composer = null;

    /**
     * @param string $alias A unique alias to represent this snyppet.
     * @param string $dir The directory this snyppet resides in.
     * @param array<string, array<string>> $middlewares An array of names of
     *  middlewares to run automatically when the snyppet is initialized.
     * @param array<string> $required An array of required snyppet aliases.
     */
    public function __construct(
        private readonly string $alias,
        private readonly string $dir,
        private readonly array $middlewares = [],
        private readonly array $required = [],
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

            $middlewares[] = $this->forgeMiddleware($class);
        }

        return $middlewares;
    }

    /**
     * @param string $class A fully qualified name of a middleware class.
     *
     * @return PsrMiddlewareInterface|MiddlewareInterface An instance of the specified middleware class.
     */
    protected function forgeMiddleware(string $class): PsrMiddlewareInterface|MiddlewareInterface
    {
        if (!class_exists($class, true)) {
            throw new UnexpectedValueException('Middleware not found. (' . $class . ')');
        }

        /** @var PsrMiddlewareInterface|MiddlewareInterface */
        return new $class();
    }

    /**
     * @deprecated Use forgeMiddleware.
     */
    protected function initializeMiddleware(string $class): PsrMiddlewareInterface|MiddlewareInterface
    {
        return $this->forgeMiddleware($class);
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
            return trim($namespace, '\\');
        }

        $namespace = $composer['autoload']['psr-4'] ?? null;
        if ($namespace === null || !count($namespace)) {
            return null;
        }

        return trim(array_keys($namespace)[0], '\\');
    }

    /**
     * @inheritdoc
     */
    public function getExtra(): array
    {
        $composer = $this->getComposer();

        return $composer['extra']['snyppet'] ?? [];
    }

    /**
     * Gets the composer file data as an array.
     *
     * @return array{
     *     name?: string,
     *     description?: string,
     *     version?: string,
     *     autoload?: array{
     *         psr-4?: array<string, string|array<string>>,
     *     },
     *     extra?: array{
     *         snyppet?: array{
     *              name?: string,
     *              description?: string,
     *              path?: string,
     *              namespace?: string,
     *              version?: string,
     *              install?: string,
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
                /** @var array{
                 *     name?: string,
                 *     description?: string,
                 *     version?: string,
                 *     autoload?: array{
                 *         psr-4?: array<string, string|array<string>>,
                 *     },
                 *     extra?: array{
                 *         snyppet?: array{
                 *              name?: string,
                 *              description?: string,
                 *              path?: string,
                 *              namespace?: string,
                 *              version?: string,
                 *              install?: string,
                 *         },
                 *     },
                 * }
                 */
                $json = json_decode($json, true);

                $this->composer = $json;
            }
        }

        return $this->composer;
    }

    /**
     * @inheritdoc
     */
    public function getRequired(): array
    {
        return $this->required;
    }
}
