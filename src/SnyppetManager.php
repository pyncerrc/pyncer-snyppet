<?php
namespace Pyncer\Snyppet;

use Pyncer\Snyppet\SnyppetInterface;
use Pyncer\Snyppet\Exception\SnyppetNotFoundException;
use Pyncer\Iterable\StaticIterator;

class SnyppetManager extends StaticIterator
{
    /**
     * @param null|array<string> $snyppets An array of snyppet aliases to
     *  limit what snyppets are available.
     */
    public function __construct(
        protected ?array $snyppets = null
    ) {
        $this->initialize();
    }

    public function rewind(): void
    {
        if ($this->snyppets === null) {
            static::$keys = array_keys(static::$values);
        } else {
            // Limit iterator to enabled snyppets
            $keys = array_keys(static::$values);

            static::$keys = [];

            foreach ($this->snyppets as $snyppet) {
                if (in_array($snyppet, $keys)) {
                    static::$keys[] = $snyppet;
                }
            }
        }
        static::$position = 0;
    }

    public function initialize(): void
    {
        foreach (static::$values as $snyppet) {
            if ($this->snyppets !== null &&
                !in_array($snyppet->getAlias(), $this->snyppets)
            ) {
                continue;
            }

            $snyppet->initialize();
        }
    }

    public static function register(SnyppetInterface $snyppet): void
    {
        static::$values[$snyppet->getAlias()] = $snyppet;

        // Ensure app is snyppet is always first
        if ($snyppet->getAlias() === 'app') {
            uasort(static::$values, function($a, $b) {
                if ($a->getAlias() === $b->getAlias()) {
                    return 0;
                }

                if ($a->getAlias() === 'app') {
                    return -1;
                }

                if ($b->getAlias() === 'app') {
                    return 1;
                }

                // Sort so required goes first
                if (in_array($a->getAlias(), $b->getRequired())) {
                    if (!in_array($b->getAlias(), $a->getRequired())) {
                        return -1;
                    }
                } elseif (in_array($b->getAlias(), $a->getRequired())) {
                    return 1;
                }

                return $a->getAlias() <=> $b->getAlias();
            });
        }
    }

    /**
     * @return array<SnyppetInterface> An array of snyppets.
     */
    public function getSnyppets(): array
    {
        $snyppets = [];

        foreach ($this as $snyppet) {
            $snyppets[] = $snyppet;
        }

        return $snyppets;
    }

    /**
     * @return array<string> An array of snyppet aliases.
     */
    public function getAliases(): array
    {
        $aliases = [];

        foreach ($this as $snyppet) {
            $aliases[] = $snyppet->getAlias();
        }

        return $aliases;
    }

    public function get(string $alias): SnyppetInterface
    {
        if ($this->snyppets !== null &&
            !in_array($alias, $this->snyppets)
        ) {
            throw new SnyppetNotFoundException($alias);
        }

        if (!array_key_exists($alias, static::$values)) {
            throw new SnyppetNotFoundException($alias);
        }

        return static::$values[$alias];
    }

    public function has(string $alias, ?string $version = null): bool
    {
        if ($this->snyppets !== null &&
            !in_array($alias, $this->snyppets)
        ) {
            return false;
        }

        if (!array_key_exists($alias, static::$values)) {
            return false;
        }

        if ($version !== null && $version !== '*') {
            $versionValue = static::$values[$alias]->getVersion();

            if (version_compare($versionValue , $version, '<')) {
                return false;
            }
        }

        return true;
    }
}
