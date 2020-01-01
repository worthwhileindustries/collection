<?php

declare(strict_types=1);

namespace loophp\collection\Contract;

/**
 * Interface Chunkable.
 */
interface Chunkable
{
    /**
     * Chunk the collection into chunks of the given size.
     *
     * @param int $size
     *
     * @return \loophp\collection\Contract\Collection<mixed>
     */
    public function chunk(int $size): Base;
}
