<?php

declare(strict_types=1);

namespace loophp\collection\Operation;

use Closure;
use Generator;
use loophp\collection\Contract\Operation;

/**
 * Class Split.
 */
final class Split implements Operation
{
    /**
     * @var callable[]
     */
    private $callbacks;

    /**
     * Split constructor.
     *
     * @param callable ...$callbacks
     */
    public function __construct(callable ...$callbacks)
    {
        $this->callbacks = $callbacks;
    }

    /**
     * {@inheritdoc}
     */
    public function on(iterable $collection): Closure
    {
        $callbacks = $this->callbacks;

        return static function () use ($callbacks, $collection): Generator {
            $carry = [];

            foreach ($collection as $key => $value) {
                $carry[] = $value;

                foreach ($callbacks as $callback) {
                    if (true !== $callback($value, $key)) {
                        continue;
                    }

                    yield $carry;

                    $carry = [];
                }
            }

            if ([] !== $carry) {
                yield $carry;
            }
        };
    }
}
