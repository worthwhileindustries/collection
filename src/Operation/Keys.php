<?php

declare(strict_types=1);

namespace drupol\collection\Operation;

use drupol\collection\Collection;
use drupol\collection\Contract\Collection as CollectionInterface;

/**
 * Class Keys.
 */
final class Keys extends Operation
{
    /**
     * {@inheritdoc}
     */
    public function run(CollectionInterface $collection): CollectionInterface
    {
        return Collection::withClosure(
            static function () use ($collection) {
                foreach ($collection as $key => $value) {
                    yield $key;
                }
            }
        );
    }
}