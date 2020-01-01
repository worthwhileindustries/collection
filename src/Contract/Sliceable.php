<?php

declare(strict_types=1);

namespace loophp\collection\Contract;

/**
 * Interface Sliceable.
 */
interface Sliceable
{
    /**
     * Get a slice of items.
     *
     * @param int $offset
     * @param int|null $length
     *
     * @return \loophp\collection\Contract\Collection<mixed>
     */
    public function slice(int $offset, ?int $length = null): Base;
}
