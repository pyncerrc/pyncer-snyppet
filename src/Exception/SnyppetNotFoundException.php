<?php
namespace Pyncer\Snyppet\Exception;

use Pyncer\Exception\RuntimeException;
use Throwable;

class SnyppetNotFoundException extends RuntimeException
{
    protected string $snyppetAlias;

    public function __construct(
        string $snyppetAlias,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            'The specified snyppet, ' . $snyppetAlias. ', was not found.',
            $code,
            $previous
        );

        $this->snyppetAlias = $snyppetAlias;
    }

    public function getSnyppetAlias(): string
    {
        return $this->snyppetAlias;
    }
}
