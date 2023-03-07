<?php
namespace Pyncer\Snyppet;

use Pyncer\Snyppet\Snyppet;
use Pyncer\Iterable\StaticAccessIterator;

class SnyppetManager extends StaticAccessIterator
{
    public function __construct()
    {
        $this->initialize();
    }

    public function initialize(): void
    {
        foreach (static::$values as $snyppet) {
            $snyppet->initialize();
        }
    }

    public static function register(Snyppet $snyppet): void
    {
        static::$values[$snyppet->getAlias()] = $snyppet;
    }

    public function get(string $alias): SnyppetInterface
    {
        return static::$values[$alias];
    }

    public function has(string $alias): bool
    {
        return array_key_exists($alias, static::$values);
    }
}
