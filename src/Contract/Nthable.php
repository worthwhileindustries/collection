<?php

declare(strict_types=1);

namespace loophp\collection\Contract;

/**
 * Interface Nthable.
 */
interface Nthable
{
    /**
     * Create a new collection consisting of every n-th element.
     *
     * @param int $step
     * @param int $offset
     *
     * @return \loophp\collection\Contract\Collection<mixed>
     */
    public function nth(int $step, int $offset = 0): Base;
}
