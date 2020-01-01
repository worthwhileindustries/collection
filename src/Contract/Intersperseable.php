<?php

declare(strict_types=1);

namespace loophp\collection\Contract;

/**
 * Interface Intersperseable.
 */
interface Intersperseable
{
    /**
     * Insert a given value between each element of a collection.
     * Indices are not preserved.
     *
     * @param mixed $element
     * @param int $every
     * @param int $startAt
     *
     * @return \loophp\collection\Contract\Collection<mixed>
     */
    public function intersperse($element, int $every = 1, int $startAt = 0): Base;
}
