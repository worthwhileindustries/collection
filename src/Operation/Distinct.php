<?php

declare(strict_types=1);

namespace loophp\collection\Operation;

use Closure;
use Generator;
use loophp\collection\Collection;
use loophp\collection\Contract\Operation;

/**
 * Class Distinct.
 */
final class Distinct implements Operation
{
    /**
     * {@inheritdoc}
     */
    public function on(iterable $collection): Closure
    {
        return static function () use ($collection): Generator {
            $seen = new Collection([]);

            foreach ($collection as $key => $value) {
                if (true === $seen->contains($value)) {
                    continue;
                }

                $seen = $seen
                    ->append($value)
                    ->rebase();

                yield $key => $value;
            }
        };
    }
}
