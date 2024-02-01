<?php
namespace Pyncer\Snyppet;

use Psr\Http\Server\MiddlewareInterface as PsrMiddlewareInterface;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Snyppet\InstallInterface;

interface SnyppetInterface
{
    public function initialize(): void;
    public function getAlias(): string;
    public function getDir(): string;
    /**
     * @param string $type The type of middleware to get.
     * @return array<PsrMiddlewareInterface|MiddlewareInterface>
     */
    public function getMiddlewares(string $type): array;
    public function getName(): ?string;
    public function getDescription(): ?string;
    public function getVersion(): ?string;
    public function getNamespace(): ?string;

    /**
     * @return array<string> An array of required snyppet aliases.
     */
    public function getRequired(): array;
}
