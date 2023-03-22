<?php
namespace Pyncer\Snyppet;

use Pyncer\Snyppet\Snyppet;
use Pyncer\Snyppet\Exception\SnyppetNotFoundException;
use Pyncer\Iterable\StaticAccessIterator;

class SnyppetManager extends StaticAccessIterator
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

    public static function register(Snyppet $snyppet): void
    {
        static::$values[$snyppet->getAlias()] = $snyppet;
    }

    /**
     * @return array<string> An array of snyppets.
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

    public function has(string $alias): bool
    {
        if ($this->snyppets !== null &&
            !in_array($alias, $this->snyppets)
        ) {
            return false;
        }

        return array_key_exists($alias, static::$values);
    }
}
