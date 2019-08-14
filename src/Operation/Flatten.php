<?php

declare(strict_types=1);

namespace drupol\collection\Operation;

use drupol\collection\Collection;
use drupol\collection\Contract\Collection as CollectionInterface;

/**
 * Class Flatten.
 */
final class Flatten extends Operation
{
    /**
     * {@inheritdoc}
     */
    public function run(CollectionInterface $collection): CollectionInterface
    {
        $depth = $this->parameters[0];

        return Collection::withClosure(
            static function () use ($depth, $collection) {
                $iterator = $collection->getIterator();

                foreach ($iterator as $item) {
                    if (!\is_array($item) && !$item instanceof Collection) {
                        yield $item;
                    } elseif (1 === $depth) {
                        foreach ($item as $i) {
                            yield $i;
                        }
                    } else {
                        foreach (Collection::with($item)->flatten($depth - 1) as $flattenItem) {
                            yield $flattenItem;
                        }
                    }
                }
            }
        );
    }
}