<?php

declare(strict_types=1);

namespace spec\loophp\collection;

use ArrayObject;
use Closure;
use Exception;
use Generator;
use InvalidArgumentException;
use Iterator;
use JsonSerializable;
use loophp\collection\Collection;
use loophp\collection\Contract\Operation;
use loophp\collection\Operation\AbstractOperation;
use OutOfBoundsException;
use PhpSpec\ObjectBehavior;
use stdClass;
use const INF;

class CollectionSpec extends ObjectBehavior
{
    public function it_can_append(): void
    {
        $generator = static function (): Generator {
            yield 0 => '1';

            yield 1 => '2';

            yield 2 => '3';

            yield 0 => '4';
        };

        $this::fromIterable(['1', '2', '3'])
            ->append('4')
            ->shouldIterateAs($generator());

        $generator = static function (): Generator {
            yield 0 => '1';

            yield 1 => '2';

            yield 2 => '3';

            yield 0 => '5';

            yield 1 => '6';
        };

        $this::fromIterable(['1', '2', '3'])
            ->append('5', '6')
            ->shouldIterateAs($generator());
    }

    public function it_can_apply(): void
    {
        $input = array_combine(range('A', 'Z'), range('A', 'Z'));

        $this::fromIterable($input)
            ->apply(static function ($item) {
                // do what you want here.

                return true;
            })
            ->shouldIterateAs($input);

        $this::fromIterable($input)
            ->apply(static function ($item) {
                // do what you want here.

                return false;
            })
            ->shouldIterateAs($input);

        $this::fromIterable($input)
            ->apply(
                static function ($item) {
                    return $item;
                }
            )
            ->shouldIterateAs($input);

        $this::fromIterable($input)
            ->apply(
                static function ($item) {
                    return false;
                }
            )
            ->shouldReturnAnInstanceOf(Collection::class);

        $callback = static function (): void {
            throw new Exception('foo');
        };

        $this::fromIterable($input)
            ->apply($callback)
            ->shouldThrow(Exception::class)
            ->during('all');

        $apply1 = static function ($value) {
            return true === $value % 2;
        };

        $apply2 = static function ($value) {
            return true === $value % 3;
        };

        $this::fromIterable([1, 2, 3, 4, 5, 6])
            ->apply($apply1)
            ->apply($apply2)
            ->shouldIterateAs([1, 2, 3, 4, 5, 6]);
    }

    public function it_can_associate(): void
    {
        $input = range(1, 10);

        $this::fromIterable($input)
            ->associate()
            ->shouldIterateAs($input);

        $this::fromIterable($input)
            ->associate(
                static function ($key, $value) {
                    return $key * 2;
                },
                static function ($key, $value) {
                    return $value * 2;
                }
            )
            ->shouldIterateAs(
                [
                    0 => 2,
                    2 => 4,
                    4 => 6,
                    6 => 8,
                    8 => 10,
                    10 => 12,
                    12 => 14,
                    14 => 16,
                    16 => 18,
                    18 => 20,
                ]
            );
    }

    public function it_can_be_constructed_from_a_stream(): void
    {
        $string = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.';

        $stream = fopen('data://text/plain,' . $string, 'rb');
        $this::fromResource($stream)
            ->count()
            ->shouldReturn(56);

        $stream = fopen('data://text/plain,' . $string, 'rb');
        $this::fromResource($stream)
            ->implode('')
            ->shouldReturn($string);
    }

    public function it_can_be_constructed_from_array(): void
    {
        $this
            ->beConstructedThrough('fromIterable', [range('A', 'E')]);

        $this->shouldImplement(Collection::class);

        $this
            ->shouldIterateAs(['A', 'B', 'C', 'D', 'E']);
    }

    public function it_can_be_constructed_from_empty(): void
    {
        $this
            ->beConstructedThrough('empty');

        $this
            ->shouldIterateAs([]);
    }

    public function it_can_be_constructed_from_nothing(): void
    {
        $this
            ->beConstructedWith(null);

        $this
            ->shouldIterateAs([]);
    }

    public function it_can_be_constructed_with_a_closure(): void
    {
        $this
            ->beConstructedThrough('fromCallable', [static function () {
                yield from range(1, 3);
            }]);

        $this->shouldImplement(Collection::class);
    }

    public function it_can_be_constructed_with_an_arrayObject(): void
    {
        $this
            ->beConstructedThrough('fromIterable', [new ArrayObject([1, 2, 3])]);

        $this->shouldImplement(Collection::class);
    }

    public function it_can_be_instantiated_with_withClosure(): void
    {
        $fibonacci = static function ($start, $inc) {
            yield $start;

            while (true) {
                $inc = $start + $inc;
                $start = $inc - $start;

                yield $start;
            }
        };

        $this::fromCallable($fibonacci, 0, 1)
            ->limit(10)
            ->shouldIterateAs([0, 1, 1, 2, 3, 5, 8, 13, 21, 34]);
    }

    public function it_can_be_json_encoded()
    {
        $input = ['a' => 'A', 'b' => 'B', 'c' => 'C'];

        $this->beConstructedThrough('fromIterable', [$input]);

        $this
            ->jsonSerialize()
            ->shouldReturn($this->all());

        $this
            ->shouldImplement(JsonSerializable::class);
    }

    public function it_can_be_returned_as_an_array(): void
    {
        $this::fromIterable(new ArrayObject(['1', '2', '3']))
            ->shouldIterateAs(['1', '2', '3']);
    }

    public function it_can_cache(): void
    {
        $fhandle = fopen(__DIR__ . '/../../fixtures/sample.txt', 'rb');

        $this::fromResource($fhandle)
            ->window(2)
            ->shouldIterateAs([
                [0 => 'a'],
                [0 => 'a', 1 => 'b'],
                [0 => 'a', 1 => 'b', 2 => 'c'],
            ]);

        $fhandle = fopen(__DIR__ . '/../../fixtures/sample.txt', 'rb');

        $this::fromResource($fhandle)
            ->cache()
            ->window(2)
            ->shouldIterateAs([
                [0 => 'a'],
                [0 => 'a', 1 => 'b'],
                [0 => 'a', 1 => 'b', 2 => 'c'],
            ]);

        $fhandle = fopen(__DIR__ . '/../../fixtures/sample.txt', 'rb');

        $this::fromResource($fhandle)
            ->cache()
            ->shouldIterateAs(['a', 'b', 'c']);

        $fhandle = fopen(__DIR__ . '/../../fixtures/sample.txt', 'rb');

        $this::fromResource($fhandle)
            ->cache()
            ->shouldIterateAs(['a', 'b', 'c']);
    }

    public function it_can_chunk(): void
    {
        $this::fromIterable(range('A', 'F'))
            ->chunk(2)
            ->shouldIterateAs([[0 => 'A', 1 => 'B'], [0 => 'C', 1 => 'D'], [0 => 'E', 1 => 'F']]);

        $this::fromIterable(range('A', 'F'))
            ->chunk(0)
            ->shouldIterateAs([]);

        $this::fromIterable(range('A', 'F'))
            ->chunk(1)
            ->shouldIterateAs([[0 => 'A'], [0 => 'B'], [0 => 'C'], [0 => 'D'], [0 => 'E'], [0 => 'F']]);

        $this::fromIterable(range('A', 'F'))
            ->chunk(2, 3)
            ->shouldIterateAs([[0 => 'A', 1 => 'B'], [0 => 'C', 1 => 'D', 2 => 'E'], [0 => 'F']]);
    }

    public function it_can_collapse(): void
    {
        $generator = static function () {
            yield 0 => 'A';

            yield 1 => 'B';

            yield 'foo' => 'C';

            yield 0 => 'E';

            yield 1 => 'F';
        };

        $this::fromIterable([
            ['A', 'B', 'foo' => 'C'],
            'D',
            ['E', 'F'],
            'G',
        ])
            ->collapse()
            ->shouldIterateAs($generator());

        $this::fromIterable(range('A', 'E'))
            ->collapse()
            ->shouldIterateAs([]);
    }

    public function it_can_column(): void
    {
        $records = [
            [
                'id' => 2135,
                'first_name' => 'John',
                'last_name' => 'Doe',
            ],
            [
                'id' => 3245,
                'first_name' => 'Sally',
                'last_name' => 'Smith',
            ],
            [
                'id' => 5342,
                'first_name' => 'Jane',
                'last_name' => 'Jones',
            ],
            [
                'id' => 5623,
                'first_name' => 'Peter',
                'last_name' => 'Doe',
            ],
        ];

        $this::fromIterable($records)
            ->column('first_name')
            ->shouldIterateAs(
                [
                    0 => 'John',
                    1 => 'Sally',
                    2 => 'Jane',
                    3 => 'Peter',
                ]
            );
    }

    public function it_can_combinate(): void
    {
        $this::fromIterable(range('a', 'c'))
            ->combinate(0)
            ->shouldIterateAs(
                [
                    [
                        0 => 'a',
                        1 => 'b',
                        2 => 'c',
                    ],
                ]
            );

        $this::fromIterable(range('a', 'c'))
            ->combinate(1)
            ->shouldIterateAs(
                [
                    [
                        'a',
                    ],
                    [
                        'b',
                    ],
                    [
                        'c',
                    ],
                ]
            );

        $this::fromIterable(range('a', 'c'))
            ->combinate()
            ->all()
            ->shouldBeEqualTo(
                [
                    0 => [
                        0 => 'a',
                        1 => 'b',
                        2 => 'c',
                    ],
                    1 => [
                        0 => 'a',
                        1 => 'c',
                    ],
                    2 => [
                        0 => 'b',
                        1 => 'c',
                    ],
                ]
            );
    }

    public function it_can_combine(): void
    {
        $this::fromIterable(range('A', 'E'))
            ->combine(...range('e', 'a'))
            ->shouldIterateAs(['e' => 'A', 'd' => 'B', 'c' => 'C', 'b' => 'D', 'a' => 'E']);

        $this::fromIterable(range('A', 'E'))
            ->combine(...range(1, 100))
            ->shouldThrow(Exception::class)
            ->during('all');
    }

    public function it_can_compact(): void
    {
        $input = ['a', 1 => 'b', null, false, 0, 'c'];

        $this::fromIterable($input)
            ->compact()
            ->shouldIterateAs(['a', 1 => 'b', 3 => false, 4 => 0, 5 => 'c']);

        $this::fromIterable($input)
            ->compact(null, 0)
            ->shouldIterateAs(['a', 1 => 'b', 3 => false, 5 => 'c']);
    }

    public function it_can_contains(): void
    {
        $this::fromIterable(range('A', 'C'))
            ->contains('A')
            ->shouldReturn(true);

        $this::fromIterable(range('A', 'C'))
            ->contains('unknown')
            ->shouldReturn(false);

        $this::fromIterable(range('A', 'C'))
            ->contains('C', 'A')
            ->shouldReturn(true);

        $this::fromIterable(range('A', 'C'))
            ->contains('C', 'unknown', 'A')
            ->shouldReturn(false);
    }

    public function it_can_convert_use_a_string_as_parameter(): void
    {
        $this::fromString('foo')
            ->shouldIterateAs([0 => 'f', 1 => 'o', 2 => 'o']);

        $this::fromString('hello, world', ',')
            ->shouldIterateAs([0 => 'hello', 1 => ' world']);
    }

    public function it_can_count_its_items(): void
    {
        $this::fromIterable(range('A', 'C'))
            ->count()
            ->shouldReturn(3);
    }

    public function it_can_cycle(): void
    {
        $generator = static function (): Generator {
            yield 0 => 1;

            yield 1 => 2;

            yield 2 => 3;

            yield 0 => 1;

            yield 1 => 2;

            yield 2 => 3;

            yield 0 => 1;
        };

        $this::fromIterable(range(1, 3))
            ->cycle()
            ->limit(7)
            ->shouldIterateAs($generator());
    }

    public function it_can_diff(): void
    {
        $this::fromIterable(range(1, 5))
            ->diff(1, 2, 3, 9)
            ->shouldIterateAs([3 => 4, 4 => 5]);

        $this::fromIterable(range(1, 5))
            ->diff()
            ->shouldIterateAs(range(1, 5));
    }

    public function it_can_diffKeys(): void
    {
        $input = array_combine(range('a', 'e'), range(1, 5));

        $this::fromIterable($input)
            ->diffKeys('b', 'd')
            ->shouldIterateAs(['a' => 1, 'c' => 3, 'e' => 5]);

        $this::fromIterable($input)
            ->diffKeys()
            ->shouldIterateAs($input);
    }

    public function it_can_distinct(): void
    {
        $stdclass = new stdClass();

        $this::fromIterable([1, 1, 2, 2, 3, 3, $stdclass, $stdclass])
            ->distinct()
            ->shouldIterateAs([0 => 1, 2 => 2, 4 => 3, 6 => $stdclass]);
    }

    public function it_can_do_the_cartesian_product(): void
    {
        $this::fromIterable(range('A', 'C'))
            ->product()
            ->shouldIterateAs([0 => ['A'], 1 => ['B'], 2 => ['C']]);

        $this::fromIterable(range('A', 'C'))
            ->product([1, 2])
            ->shouldIterateAs([0 => ['A', 1], 1 => ['A', 2], 2 => ['B', 1], 3 => ['B', 2], 4 => ['C', 1], 5 => ['C', 2]]);
    }

    public function it_can_drop(): void
    {
        $this::fromIterable(range('A', 'F'))
            ->drop(3)
            ->shouldIterateAs([3 => 'D', 4 => 'E', 5 => 'F']);

        $this::fromIterable(range('A', 'F'))
            ->drop(3, 3)
            ->shouldIterateAs([]);
    }

    public function it_can_dropWhile(): void
    {
        $isSmallerThanThree = static function ($value) {
            return 3 > $value;
        };

        $this::fromIterable([1, 2, 3, 4, 5, 6, 7, 8, 9, 1, 2, 3])
            ->dropWhile($isSmallerThanThree)
            ->shouldIterateAs([
                2 => 3,
                3 => 4,
                4 => 5,
                5 => 6,
                6 => 7,
                7 => 8,
                8 => 9,
                9 => 1,
                10 => 2,
                11 => 3,
            ]);
    }

    public function it_can_explode(): void
    {
        $string = 'I am just a random piece of text.';

        $this::fromString($string)
            ->explode('o')
            ->shouldIterateAs(
                [
                    0 => [
                        0 => 'I',
                        1 => ' ',
                        2 => 'a',
                        3 => 'm',
                        4 => ' ',
                        5 => 'j',
                        6 => 'u',
                        7 => 's',
                        8 => 't',
                        9 => ' ',
                        10 => 'a',
                        11 => ' ',
                        12 => 'r',
                        13 => 'a',
                        14 => 'n',
                        15 => 'd',
                    ],
                    1 => [
                        0 => 'o',
                        1 => 'm',
                        2 => ' ',
                        3 => 'p',
                        4 => 'i',
                        5 => 'e',
                        6 => 'c',
                        7 => 'e',
                        8 => ' ',
                    ],
                    2 => [
                        0 => 'o',
                        1 => 'f',
                        2 => ' ',
                        3 => 't',
                        4 => 'e',
                        5 => 'x',
                        6 => 't',
                        7 => '.',
                    ],
                ]
            );
    }

    public function it_can_falsy(): void
    {
        $this::fromIterable([false, false, false])
            ->falsy()
            ->shouldReturn(true);

        $this::fromIterable([false, true, false])
            ->falsy()
            ->shouldReturn(false);

        $this::fromIterable([0, [], ''])
            ->falsy()
            ->shouldReturn(true);
    }

    public function it_can_filter(): void
    {
        $input = array_merge([0, false], range(1, 10));

        $callable = static function ($value, $key, $iterator) {
            return $value % 2;
        };

        $this::fromIterable($input)
            ->filter($callable)
            ->count()
            ->shouldReturn(5);

        $this::fromIterable($input)
            ->filter($callable)
            ->normalize()
            ->shouldIterateAs([1, 3, 5, 7, 9]);

        $this::fromIterable(['afooe', 'fooe', 'allo', 'llo'])
            ->filter(
                static function ($value) {
                    return 0 === mb_strpos($value, 'a');
                },
                static function ($value) {
                    return mb_strlen($value) - 1 === mb_strpos($value, 'o');
                }
            )
            ->shouldIterateAs([2 => 'allo']);

        $this::fromIterable([true, false, 0, '', null])
            ->filter()
            ->shouldIterateAs([true]);
    }

    public function it_can_flatten(): void
    {
        $input = [
            ['a', 'b', 'c'],
            'd',
            ['d', ['e', 'f']],
        ];

        $output = static function (): Generator {
            yield 0 => 'a';

            yield 1 => 'b';

            yield 2 => 'c';

            yield 1 => 'd';

            yield 0 => 'd';

            yield 0 => 'e';

            yield 1 => 'f';
        };

        $this::fromIterable($input)
            ->flatten()
            ->shouldIterateAs($output());

        $output = static function (): Generator {
            yield 0 => 'a';

            yield 1 => 'b';

            yield 2 => 'c';

            yield 1 => 'd';

            yield 0 => 'd';

            yield 1 => ['e', 'f'];
        };

        $this::fromIterable($input)
            ->flatten(1)
            ->shouldIterateAs($output());
    }

    public function it_can_flip(): void
    {
        $this::fromIterable(range('A', 'E'))
            ->flip()
            ->shouldIterateAs(['A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4]);

        $this::fromIterable(['a', 'b', 'c', 'd', 'a'])
            ->flip()
            ->flip()
            ->all()
            ->shouldIterateAs(['a', 'b', 'c', 'd', 'a']);
    }

    public function it_can_fold_from_the_left(): void
    {
        $this::fromIterable(range('A', 'C'))
            ->foldLeft(
                static function (string $carry, string $item): string {
                    $carry .= $item;

                    return $carry;
                },
                ''
            )
            ->shouldReturn('ABC');
    }

    public function it_can_fold_from_the_right(): void
    {
        $this::fromIterable(range('A', 'C'))
            ->foldRight(
                static function (string $carry, string $item): string {
                    $carry .= $item;

                    return $carry;
                },
                ''
            )
            ->shouldReturn('CBA');
    }

    public function it_can_forget(): void
    {
        $this::fromIterable(range('A', 'E'))
            ->forget(0, 4)
            ->shouldIterateAs([1 => 'B', 2 => 'C', 3 => 'D']);
    }

    public function it_can_frequency(): void
    {
        $object = new StdClass();

        $input = ['1', '2', '3', null, '4', '2', null, '6', $object, $object];

        $iterateAs = static function () use ($object): Generator {
            yield 1 => '1';

            yield 2 => '2';

            yield 1 => '3';

            yield 2 => null;

            yield 1 => '4';

            yield 1 => '6';

            yield 2 => $object;
        };

        $this::fromIterable($input)
            ->frequency()
            ->shouldIterateAs($iterateAs());
    }

    public function it_can_get(): void
    {
        $this::fromIterable(range('A', 'E'))
            ->get(4)
            ->shouldReturn('E');

        $this::fromIterable(range('A', 'E'))
            ->get('unexistent key', 'default')
            ->shouldReturn('default');
    }

    public function it_can_get_an_iterator(): void
    {
        $collection = Collection::fromIterable(range(1, 5));

        $this::fromIterable($collection)
            ->getIterator()
            ->shouldImplement(Iterator::class);
    }

    public function it_can_get_current()
    {
        $input = array_combine(range('A', 'E'), range('A', 'E'));

        $this::fromIterable($input)
            ->current()
            ->shouldReturn('A');

        $this::fromIterable($input)
            ->current(1)
            ->shouldReturn('B');

        $this::fromIterable($input)
            ->current(10)
            ->shouldReturn(null);
    }

    public function it_can_get_key()
    {
        $input = array_combine(range('A', 'E'), range('A', 'E'));

        $this::fromIterable($input)
            ->key()
            ->shouldReturn('A');

        $this::fromIterable($input)
            ->key(1)
            ->shouldReturn('B');

        $this::fromIterable($input)
            ->key(10)
            ->shouldReturn(null);
    }

    public function it_can_get_the_first_item(): void
    {
        $this::fromIterable(range(1, 10))
            ->first()
            ->shouldIterateAs([0 => 1]);

        $this::fromIterable([])
            ->first()
            ->shouldIterateAs([]);
    }

    public function it_can_get_the_last_item(): void
    {
        $this::fromIterable(range('A', 'F'))
            ->last()
            ->shouldIterateAs([5 => 'F']);

        $this::fromIterable(['A'])
            ->last()
            ->shouldIterateAs([0 => 'A']);

        $this::fromIterable([])
            ->last()
            ->shouldIterateAs([]);
    }

    public function it_can_group()
    {
        $callback = static function () {
            yield 1 => 'a';

            yield 1 => 'b';

            yield 1 => 'c';

            yield 2 => 'd';

            yield 2 => 'e';

            yield 3 => 'f';

            yield 4 => 'g';

            yield 10 => 'h';
        };

        $this::fromCallable($callback)
            ->group()
            ->shouldIterateAs([
                1 => [
                    'a',
                    'b',
                    'c',
                ],
                2 => [
                    'd',
                    'e',
                ],
                3 => ['f'],
                4 => ['g'],
                10 => ['h'],
            ]);

        $callback = static function (int $value, int $key) {
            return 0 === ($value % 2) ? 'even' : 'odd';
        };

        $this::fromIterable(range(0, 20))
            ->group($callback)
            ->shouldIterateAs([
                'even' => [
                    0,
                    2,
                    4,
                    6,
                    8,
                    10,
                    12,
                    14,
                    16,
                    18,
                    20,
                ],
                'odd' => [
                    1,
                    3,
                    5,
                    7,
                    9,
                    11,
                    13,
                    15,
                    17,
                    19,
                ],
            ]);

        $input = range(0, 20);
        $this::fromIterable($input)
            ->group(static function () {return null; })
            ->shouldIterateAs($input);
    }

    public function it_can_has(): void
    {
        $this::fromIterable(range('A', 'C'))
            ->has(static function ($key, $value) {
                return 'A';
            })
            ->shouldReturn(true);

        $this::fromIterable(range('A', 'C'))
            ->has(static function ($key, $value) {
                return 'Z';
            })
            ->shouldReturn(false);
    }

    public function it_can_head(): void
    {
        $input = range('A', 'E');

        $this::fromIterable($input)
            ->head()
            ->shouldIterateAs([0 => 'A']);
    }

    public function it_can_if_then_else()
    {
        $input = range(1, 5);

        $condition = static function ($value) {
            return 0 === $value % 2;
        };

        $then = static function ($value) {
            return $value * $value;
        };

        $else = static function ($value) {
            return $value + 2;
        };

        $this::fromIterable($input)
            ->ifThenElse($condition, $then)
            ->shouldIterateAs([
                1, 4, 3, 16, 5,
            ]);

        $this::fromIterable($input)
            ->ifThenElse($condition, $then, $else)
            ->shouldIterateAs([
                3, 4, 5, 16, 7,
            ]);
    }

    public function it_can_implode(): void
    {
        $this::fromIterable(range('A', 'C'))
            ->implode('-')
            ->shouldReturn('A-B-C');

        $this::fromIterable(range('A', 'C'))
            ->implode()
            ->shouldReturn('ABC');
    }

    public function it_can_init(): void
    {
        $this::fromIterable(range(0, 4))
            ->init()
            ->shouldIterateAs([
                0 => 0,
                1 => 1,
                2 => 2,
                3 => 3,
            ]);
    }

    public function it_can_intersect(): void
    {
        $this::fromIterable(range(1, 5))
            ->intersect(1, 2, 3, 9)
            ->shouldIterateAs([0 => 1, 1 => 2, 2 => 3]);

        $this::fromIterable(range(1, 5))
            ->intersect()
            ->shouldIterateAs([]);
    }

    public function it_can_intersectKeys(): void
    {
        $input = array_combine(range('a', 'e'), range(1, 5));

        $this::fromIterable($input)
            ->intersectKeys('b', 'd')
            ->shouldIterateAs(['b' => 2, 'd' => 4]);

        $this::fromIterable($input)
            ->intersectKeys()
            ->shouldIterateAs([]);

        $this::fromIterable(range('A', 'E'))
            ->intersectKeys(0, 1, 3)
            ->shouldIterateAs([0 => 'A', 1 => 'B', 3 => 'D']);

        $this::fromIterable(range('A', 'E'))
            ->intersectKeys()
            ->shouldIterateAs([]);
    }

    public function it_can_intersperse(): void
    {
        $generator = static function () {
            yield 0 => 'foo';

            yield 0 => 'A';

            yield 1 => 'foo';

            yield 1 => 'B';

            yield 2 => 'foo';

            yield 2 => 'C';

            yield 3 => 'foo';

            yield 3 => 'D';

            yield 4 => 'foo';

            yield 4 => 'E';

            yield 5 => 'foo';

            yield 5 => 'F';
        };

        $this::fromIterable(range('A', 'F'))
            ->intersperse('foo')
            ->shouldIterateAs($generator());

        $generator = static function () {
            yield 0 => 'foo';

            yield 0 => 'A';

            yield 1 => 'B';

            yield 2 => 'foo';

            yield 2 => 'C';

            yield 3 => 'D';

            yield 4 => 'foo';

            yield 4 => 'E';

            yield 5 => 'F';
        };

        $this::fromIterable(range('A', 'F'))
            ->intersperse('foo', 2, 0)
            ->shouldIterateAs($generator());

        $generator = static function () {
            yield 0 => 'A';

            yield 1 => 'foo';

            yield 1 => 'B';

            yield 2 => 'C';

            yield 3 => 'foo';

            yield 3 => 'D';

            yield 4 => 'E';

            yield 5 => 'foo';

            yield 5 => 'F';
        };

        $this::fromIterable(range('A', 'F'))
            ->intersperse('foo', 2, 1)
            ->shouldIterateAs($generator());

        $generator = static function () {
            yield 0 => 'foo';

            yield 0 => 'A';

            yield 1 => 'B';

            yield 2 => 'foo';

            yield 2 => 'C';

            yield 3 => 'D';

            yield 4 => 'foo';

            yield 4 => 'E';

            yield 5 => 'F';
        };

        $this::fromIterable(range('A', 'F'))
            ->intersperse('foo', 2, 2)
            ->shouldIterateAs($generator());

        $this::fromIterable(range('A', 'F'))
            ->intersperse('foo', -1, 1)
            ->shouldThrow(Exception::class)
            ->during('all');

        $this::fromIterable(range('A', 'F'))
            ->intersperse('foo', 1, -1)
            ->shouldThrow(Exception::class)
            ->during('all');
    }

    public function it_can_keys(): void
    {
        $this::fromIterable(range('A', 'E'))
            ->keys()
            ->shouldIterateAs(range(0, 4));
    }

    public function it_can_limit(): void
    {
        $input = range('A', 'E');
        $this::fromIterable($input)
            ->limit(3)
            ->shouldHaveCount(3);

        $this::fromIterable($input)
            ->limit(3)
            ->shouldIterateAs(['A', 'B', 'C']);

        $this::fromIterable($input)
            ->limit(0)
            ->shouldThrow(OutOfBoundsException::class)
            ->during('all');
    }

    public function it_can_map(): void
    {
        $input = array_combine(range('A', 'E'), range('A', 'E'));

        $this::fromIterable($input)
            ->map(static function (string $item): string {
                return $item . $item;
            })
            ->shouldIterateAs(['A' => 'AA', 'B' => 'BB', 'C' => 'CC', 'D' => 'DD', 'E' => 'EE']);

        $callback1 = static function (string $a): string {
            return $a . $a;
        };

        $callback2 = static function (string $a): string {
            return '[' . $a . ']';
        };

        $this::fromIterable(range('a', 'e'))
            ->map($callback1, $callback2)
            ->shouldIterateAs([
                '[aa]',
                '[bb]',
                '[cc]',
                '[dd]',
                '[ee]',
            ]);
    }

    public function it_can_merge(): void
    {
        $collection = Collection::fromCallable(static function () {
            yield from range('F', 'J');
        });

        $generator = static function (): Generator {
            yield 0 => 'A';

            yield 1 => 'B';

            yield 2 => 'C';

            yield 3 => 'D';

            yield 4 => 'E';

            yield 0 => 'F';

            yield 1 => 'G';

            yield 2 => 'H';

            yield 3 => 'I';

            yield 4 => 'J';
        };

        $this::fromIterable(range('A', 'E'))
            ->merge($collection->all())
            ->shouldIterateAs($generator());
    }

    public function it_can_nth(): void
    {
        $this::fromIterable(range(0, 70))
            ->nth(7)
            ->shouldIterateAs([0 => 0, 7 => 7, 14 => 14, 21 => 21, 28 => 28, 35 => 35, 42 => 42, 49 => 49, 56 => 56, 63 => 63, 70 => 70]);

        $this::fromIterable(range(0, 70))
            ->nth(7, 3)
            ->shouldIterateAs([3 => 3, 10 => 10, 17 => 17, 24 => 24, 31 => 31, 38 => 38, 45 => 45, 52 => 52, 59 => 59, 66 => 66]);
    }

    public function it_can_nullsy(): void
    {
        $this::fromIterable([null, null, null])
            ->nullsy()
            ->shouldReturn(true);

        $this::fromIterable([null, 0, null])
            ->nullsy()
            ->shouldReturn(false);
    }

    public function it_can_pack(): void
    {
        $input = array_combine(range('a', 'c'), range('a', 'c'));

        $this::fromIterable($input)
            ->pack()
            ->shouldIterateAs(
                [
                    0 => [
                        0 => 'a',
                        1 => 'a',
                    ],
                    1 => [
                        0 => 'b',
                        1 => 'b',
                    ],
                    2 => [
                        0 => 'c',
                        1 => 'c',
                    ],
                ]
            );
    }

    public function it_can_pad(): void
    {
        $input = array_combine(range('A', 'E'), range('A', 'E'));

        $this::fromIterable($input)
            ->pad(10, 'foo')
            ->shouldIterateAs(['A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D', 'E' => 'E', 0 => 'foo', 1 => 'foo', 2 => 'foo', 3 => 'foo', 4 => 'foo']);
    }

    public function it_can_pair(): void
    {
        $input = [
            [
                'key' => 'k1',
                'value' => 'v1',
            ],
            [
                'key' => 'k2',
                'value' => 'v2',
            ],
            [
                'key' => 'k3',
                'value' => 'v3',
            ],
            [
                'key' => 'k4',
                'value' => 'v4',
            ],
            [
                'key' => 'k4',
                'value' => 'v5',
            ],
        ];

        $gen = static function () {
            yield 'k1' => 'v1';

            yield 'k2' => 'v2';

            yield 'k3' => 'v3';

            yield 'k4' => 'v4';

            yield 'k4' => 'v5';
        };

        $this::fromIterable($input)
            ->unwrap()
            ->pair()
            ->shouldIterateAs($gen());
    }

    public function it_can_permutate(): void
    {
        $this::fromIterable(range('a', 'c'))
            ->permutate()
            ->shouldIterateAs(
                [
                    [
                        0 => 'a',
                        1 => 'b',
                        2 => 'c',
                    ],
                    [
                        0 => 'a',
                        1 => 'c',
                        2 => 'b',
                    ],
                    [
                        0 => 'b',
                        1 => 'a',
                        2 => 'c',
                    ],
                    [
                        0 => 'b',
                        1 => 'c',
                        2 => 'a',
                    ],
                    [
                        0 => 'c',
                        1 => 'a',
                        2 => 'b',
                    ],
                    [
                        0 => 'c',
                        1 => 'b',
                        2 => 'a',
                    ],
                ]
            );
    }

    public function it_can_pluck(): void
    {
        $six = new class() {
            public $foo = [
                'bar' => 5,
            ];
        };

        $input = [
            [
                0 => 'A',
                'foo' => [
                    'bar' => 0,
                ],
            ],
            [
                0 => 'B',
                'foo' => [
                    'bar' => 1,
                ],
            ],
            [
                0 => 'C',
                'foo' => [
                    'bar' => 2,
                ],
            ],
            Collection::fromIterable(
                [
                    'foo' => [
                        'bar' => 3,
                    ],
                ]
            ),
            new ArrayObject([
                'foo' => [
                    'bar' => 4,
                ],
            ]),
            new class() {
                public $foo = [
                    'bar' => 5,
                ];
            },
            [
                0 => 'D',
                'foo' => [
                    'bar' => $six,
                ],
            ],
        ];

        $this::fromIterable($input)
            ->pluck('foo')
            ->shouldIterateAs([0 => ['bar' => 0], 1 => ['bar' => 1], 2 => ['bar' => 2], 3 => ['bar' => 3], 4 => ['bar' => 4], 5 => ['bar' => 5], 6 => ['bar' => $six]]);

        $this::fromIterable($input)
            ->pluck('foo.*')
            ->shouldIterateAs([0 => [0 => 0], 1 => [0 => 1], 2 => [0 => 2], 3 => [0 => 3], 4 => [0 => 4], 5 => [0 => 5], 6 => [0 => $six]]);

        $this::fromIterable($input)
            ->pluck('.foo.bar.')
            ->shouldIterateAs([0 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => $six]);

        $this::fromIterable($input)
            ->pluck('foo.bar.*', 'taz')
            ->shouldIterateAs([0 => 'taz', 1 => 'taz', 2 => 'taz', 3 => 'taz', 4 => 'taz', 5 => 'taz', 6 => 'taz']);

        $this::fromIterable($input)
            ->pluck('azerty', 'taz')
            ->shouldIterateAs([0 => 'taz', 1 => 'taz', 2 => 'taz', 3 => 'taz', 4 => 'taz', 5 => 'taz', 6 => 'taz']);

        $this::fromIterable($input)
            ->pluck(0)
            ->shouldIterateAs([0 => 'A', 1 => 'B', 2 => 'C', null, null, null, 6 => 'D']);
    }

    public function it_can_prepend(): void
    {
        $generator = static function (): Generator {
            yield 0 => 'A';

            yield 1 => 'B';

            yield 2 => 'C';

            yield 0 => 'D';

            yield 1 => 'E';

            yield 2 => 'F';
        };

        $this::fromIterable(range('D', 'F'))
            ->prepend('A', 'B', 'C')
            ->shouldIterateAs($generator());
    }

    public function it_can_random(): void
    {
        $input = range('a', 'z');

        $generator = static function (array $input): Generator {
            yield from $input;
        };

        $this::fromIterable($input)
            ->random()
            ->count()
            ->shouldBeEqualTo(1);

        $this::fromIterable($input)
            ->random(100)
            ->count()
            ->shouldBeEqualTo(26);

        $this::fromIterable($input)
            ->random(26)
            ->shouldNotIterateAs($generator($input));
    }

    public function it_can_reduce(): void
    {
        $this::fromIterable(range(1, 100))
            ->reduce(
                static function ($carry, $item) {
                    return $carry + $item;
                },
                0
            )
            ->shouldReturn(5050);
    }

    public function it_can_reduction(): void
    {
        $this::fromIterable(range(1, 5))
            ->reduction(
                static function ($carry, $item) {
                    return $carry + $item;
                },
                0
            )
            ->shouldIterateAs([1, 3, 6, 10, 15]);
    }

    public function it_can_reverse(): void
    {
        $this::fromIterable(range('A', 'F'))
            ->reverse()
            ->shouldIterateAs([5 => 'F', 4 => 'E', 3 => 'D', 2 => 'C', 1 => 'B', 0 => 'A']);

        $this::fromIterable(range('A', 'F'))
            ->drop(3, 3)
            ->shouldIterateAs([]);
    }

    public function it_can_rsample(): void
    {
        $this::fromIterable(range(1, 10))
            ->rsample(1)
            ->shouldHaveCount(10);

        $this::fromIterable(range(1, 10))
            ->rsample(.5)
            ->shouldNotHaveCount(10);
    }

    public function it_can_run_an_operation(Operation $operation): void
    {
        $square = new class() extends AbstractOperation implements Operation {
            public function __invoke(): Closure
            {
                return static function ($collection): Generator {
                    foreach ($collection as $item) {
                        yield $item ** 2;
                    }
                };
            }
        };

        $sqrt = new class() extends AbstractOperation implements Operation {
            public function __invoke(): Closure
            {
                return static function ($collection) {
                    foreach ($collection as $item) {
                        yield $item ** .5;
                    }
                };
            }
        };

        $map = new class() extends AbstractOperation implements Operation {
            public function __invoke(): Closure
            {
                return static function ($collection) {
                    foreach ($collection as $item) {
                        yield (int) $item;
                    }
                };
            }
        };

        $this::fromIterable(range(1, 5))
            ->run($square(), $sqrt(), $map())
            ->shouldIterateAs(range(1, 5));
    }

    public function it_can_scale(): void
    {
        $input = [0, 2, 4, 6, 8, 10];

        $this::fromIterable($input)
            ->scale(0, 10)
            ->shouldIterateAs([0.0, 0.2, 0.4, 0.6, 0.8, 1.0]);

        $this::fromIterable($input)
            ->scale(0, 10, 5, 15, 3)
            ->map(static function ($value) {
                return (float) round($value, 2);
            })
            ->shouldIterateAs([5.0, 8.01, 11.02, 12.78, 14.03, 15.0]);
    }

    public function it_can_shuffle(): void
    {
        $data = range('A', 'Z');

        $this::fromIterable($data)
            ->shuffle()
            ->shouldNotIterateAs($data);

        $this::fromIterable($data)
            ->shuffle()
            ->shouldNotIterateAs([]);
    }

    public function it_can_since(): void
    {
        $this::fromIterable(range('a', 'z'))
            ->since(
                static function ($letter) {
                    return 'x' === $letter;
                }
            )
            ->shouldIterateAs([23 => 'x', 24 => 'y', 25 => 'z']);

        $this::fromIterable(range('a', 'z'))
            ->since(
                static function ($letter) {
                    return 'x' === $letter;
                },
                static function ($letter) {
                    return 1 === mb_strlen($letter);
                }
            )
            ->shouldIterateAs([23 => 'x', 24 => 'y', 25 => 'z']);

        $this::fromIterable(range('a', 'z'))
            ->since(
                static function ($letter) {
                    return 'foo' === $letter;
                },
                static function ($letter) {
                    return 'x' === $letter;
                }
            )
            ->shouldIterateAs([]);
    }

    public function it_can_slice(): void
    {
        $this::fromIterable(range(0, 10))
            ->slice(5)
            ->shouldIterateAs([5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10]);

        $this::fromIterable(range(0, 10))
            ->slice(5, 2)
            ->shouldIterateAs([5 => 5, 6 => 6]);

        $this::fromIterable(range(0, 10))
            ->slice(5, 1000)
            ->shouldIterateAs([5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10]);
    }

    public function it_can_sort(): void
    {
        $input = array_combine(range('A', 'E'), range('E', 'A'));

        $this::fromIterable($input)
            ->sort(3)
            ->shouldThrow(Exception::class)
            ->during('all');

        $this::fromIterable($input)
            ->sort()
            ->shouldIterateAs(array_combine(range('E', 'A'), range('A', 'E')));

        $this::fromIterable($input)
            ->sort(Operation\Sortable::BY_VALUES)
            ->shouldIterateAs(array_combine(range('E', 'A'), range('A', 'E')));

        $this::fromIterable($input)
            ->sort(Operation\Sortable::BY_KEYS)
            ->shouldIterateAs(array_combine(range('A', 'E'), range('E', 'A')));

        $this::fromIterable($input)
            ->sort(
                Operation\Sortable::BY_VALUES,
                static function ($left, $right): int {
                    return $right <=> $left;
                }
            )
            ->shouldIterateAs(array_combine(range('A', 'E'), range('E', 'A')));

        $this::fromIterable($input)
            ->sort(Operation\Sortable::BY_KEYS)
            ->shouldIterateAs(array_combine(range('A', 'E'), range('E', 'A')));

        $inputGen = static function () {
            yield 'k1' => 'v1';

            yield 'k2' => 'v2';

            yield 'k3' => 'v3';

            yield 'k4' => 'v4';

            yield 'k1' => 'v1';

            yield 'k2' => 'v2';

            yield 'k3' => 'v3';

            yield 'k4' => 'v4';

            yield 'a' => 'z';
        };

        $output = static function () {
            yield 'a' => 'z';

            yield 'k1' => 'v1';

            yield 'k1' => 'v1';

            yield 'k2' => 'v2';

            yield 'k2' => 'v2';

            yield 'k3' => 'v3';

            yield 'k3' => 'v3';

            yield 'k4' => 'v4';

            yield 'k4' => 'v4';
        };

        $this::fromIterable($inputGen())
            ->sort(Operation\Sortable::BY_KEYS)
            ->shouldIterateAs($output());

        $this::fromIterable($inputGen())
            ->flip()
            ->sort(Operation\Sortable::BY_VALUES)
            ->flip()
            ->shouldIterateAs($output());
    }

    public function it_can_split(): void
    {
        $this::fromIterable(range(1, 17))
            ->split(static function ($value) {
                return 0 === $value % 3;
            })
            ->shouldIterateAs([
                0 => [1, 2],
                1 => [3, 4, 5],
                2 => [6, 7, 8],
                3 => [9, 10, 11],
                4 => [12, 13, 14],
                5 => [15, 16, 17],
            ]);
    }

    public function it_can_tail(): void
    {
        $this::fromIterable(range('A', 'F'))
            ->tail()
            ->shouldIterateAs([1 => 'B', 2 => 'C', 3 => 'D', 4 => 'E', 5 => 'F']);
    }

    public function it_can_takeWhile(): void
    {
        $isSmallerThanThree = static function ($value) {
            return 3 > $value;
        };

        $this::fromIterable([1, 2, 3, 4, 5, 6, 7, 8, 9, 1, 2, 3])
            ->takeWhile($isSmallerThanThree)
            ->shouldIterateAs([
                0 => 1,
                1 => 2,
            ]);
    }

    public function it_can_transpose(): void
    {
        $records = [
            [
                'id' => 2135,
                'first_name' => 'John',
                'last_name' => 'Doe',
            ],
            [
                'id' => 3245,
                'first_name' => 'Sally',
                'last_name' => 'Smith',
            ],
            [
                'id' => 5342,
                'first_name' => 'Jane',
                'last_name' => 'Jones',
            ],
            [
                'id' => 5623,
                'first_name' => 'Peter',
                'last_name' => 'Doe',
            ],
        ];

        $this::fromIterable($records)
            ->transpose()
            ->shouldIterateAs(
                [
                    'id' => [
                        0 => 2135,
                        1 => 3245,
                        2 => 5342,
                        3 => 5623,
                    ],
                    'first_name' => [
                        0 => 'John',
                        1 => 'Sally',
                        2 => 'Jane',
                        3 => 'Peter',
                    ],
                    'last_name' => [
                        0 => 'Doe',
                        1 => 'Smith',
                        2 => 'Jones',
                        3 => 'Doe',
                    ],
                ]
            );
    }

    public function it_can_truthy(): void
    {
        $this::fromIterable([true, true, true])
            ->truthy()
            ->shouldReturn(true);

        $this::fromIterable([true, false, true])
            ->truthy()
            ->shouldReturn(false);

        $this::fromIterable([1, 2, 3])
            ->truthy()
            ->shouldReturn(true);

        $this::fromIterable([1, 2, 3, 0])
            ->truthy()
            ->shouldReturn(false);
    }

    public function it_can_unfold(): void
    {
        $this::unfold(static function (int $n): int {return $n + 1; }, 1)
            ->limit(10)
            ->shouldIterateAs([
                0 => 2,
                1 => 3,
                2 => 4,
                3 => 5,
                4 => 6,
                5 => 7,
                6 => 8,
                7 => 9,
                8 => 10,
                9 => 11,
            ]);

        $fibonacci = static function ($value1, $value2) {
            return [$value2, $value1 + $value2];
        };

        $this::unfold($fibonacci, 0, 1)
            ->limit(10)
            ->shouldIterateAs([[1, 1], [1, 2], [2, 3], [3, 5], [5, 8], [8, 13], [13, 21], [21, 34], [34, 55], [55, 89]]);

        $plusOne = static function ($value) {
            return $value + 1;
        };

        $this::unfold($plusOne, 0)
            ->limit(10)
            ->shouldIterateAs([
                1,
                2,
                3,
                4,
                5,
                6,
                7,
                8,
                9,
                10,
            ]);
    }

    public function it_can_unpack(): void
    {
        $input = [
            ['a', 'a'],
            ['b', 'b'],
            ['c', 'c'],
            ['d', 'd'],
            ['e', 'e'],
            'bar',
        ];

        $this::fromIterable($input)
            ->unpack()
            ->shouldIterateAs([
                'a' => 'a',
                'b' => 'b',
                'c' => 'c',
                'd' => 'd',
                'e' => 'e',
            ]);

        $input = [
            ['a', 'b', 'c' => 'c', 'd' => 'd'],
            ['e', 'f', 'g' => 'g', 'h' => 'h'],
            ['i', 'j'],
            'foo',
        ];

        $this::fromIterable($input)
            ->unpack()
            ->shouldIterateAs([
                'a' => 'b',
                'c' => 'd',
                'e' => 'f',
                'g' => 'h',
                'i' => 'j',
            ]);
    }

    public function it_can_unpair(): void
    {
        $input = [
            'k1' => 'v1',
            'k2' => 'v2',
            'k3' => 'v3',
            'k4' => 'v4',
        ];

        $this::fromIterable($input)
            ->unpair()
            ->shouldIterateAs([
                'k1', 'v1',
                'k2', 'v2',
                'k3', 'v3',
                'k4', 'v4',
            ]);
    }

    public function it_can_until(): void
    {
        $collatz = static function (int $initial = 1): int {
            return 0 === $initial % 2 ?
                $initial / 2 :
                $initial * 3 + 1;
        };

        $until = static function (int $number): bool {
            return 1 === $number;
        };

        $this::unfold($collatz, 10)
            ->until($until)
            ->shouldIterateAs([
                0 => 5,
                1 => 16,
                2 => 8,
                3 => 4,
                4 => 2,
                5 => 1,
            ]);
    }

    public function it_can_unwrap()
    {
        $this::fromIterable([['a' => 'A'], ['b' => 'B'], ['c' => 'C']])
            ->unwrap()
            ->shouldIterateAs([
                'a' => 'A',
                'b' => 'B',
                'c' => 'C',
            ]);

        $this::fromIterable(['foo' => ['a' => 'A'], 'bar' => ['b' => 'B'], 'foobar' => ['c' => 'C', 'd' => 'D']])
            ->unwrap()
            ->shouldIterateAs([
                'a' => 'A',
                'b' => 'B',
                'c' => 'C',
                'd' => 'D',
            ]);
    }

    public function it_can_unzip(): void
    {
        $this::fromIterable(range('A', 'C'))
            ->zip(['D', 'E', 'F', 'G'], [1, 2, 3, 4, 5])
            ->unzip()
            ->shouldIterateAs([
                [
                    'A', 'B', 'C', null, null,
                ],
                [
                    'D', 'E', 'F', 'G', null,
                ],
                [
                    1, 2, 3, 4, 5,
                ],
            ]);
    }

    public function it_can_use_range(): void
    {
        $this::range(0, 5)
            ->shouldIterateAs([(float) 0, (float) 1, (float) 2, (float) 3, (float) 4]);

        $this::range(1, 10, 2)
            ->shouldIterateAs([(float) 1, (float) 3, (float) 5, (float) 7, (float) 9]);

        $this::range(-5, 5, 2)
            ->shouldIterateAs([0 => (float) -5, 1 => (float) -3, 2 => (float) -1, 3 => (float) 1, 4 => (float) 3]);

        $this::range()
            ->limit(10)
            ->shouldIterateAs([0 => (float) 0, 1 => (float) 1, 2 => (float) 2, 3 => (float) 3, 4 => (float) 4, 5 => (float) 5, 6 => (float) 6, 7 => (float) 7, 8 => (float) 8, 9 => (float) 9]);

        $this::range(0, INF, 0)
            ->limit(10)
            ->shouldIterateAs([
                (float) 0,
                (float) 0,
                (float) 0,
                (float) 0,
                (float) 0,
                (float) 0,
                (float) 0,
                (float) 0,
                (float) 0,
                (float) 0,
            ]);
    }

    public function it_can_use_range_with_value_1(): void
    {
        $this::range(0, 1)
            ->shouldIterateAs([(float) 0]);

        $this::range()
            ->limit(5)
            ->shouldIterateAs([(float) 0, (float) 1, (float) 2, (float) 3, (float) 4]);
    }

    public function it_can_use_times_with_a_callback(): void
    {
        $a = [[1, 2, 3, 4, 5], [1, 2, 3, 4, 5]];

        $this::times(2, static function () {
            return range(1, 5);
        })
            ->shouldIterateAs($a);

        $this::times(-1, 'count')
            ->shouldThrow(InvalidArgumentException::class)
            ->during('all');
    }

    public function it_can_use_times_without_a_callback(): void
    {
        $this::times(10)
            ->shouldIterateAs(range(1, 10));

        $this::times(-5)
            ->shouldThrow(InvalidArgumentException::class)
            ->during('all');

        $this::times(1)
            ->shouldIterateAs([1]);
    }

    public function it_can_use_with(): void
    {
        $input = ['a' => 'A', 'b' => 'B', 'c' => 'C'];

        $generator = static function () {
            yield 'a' => 'A';

            yield 'b' => 'B';

            yield 'c' => 'C';
        };

        $this::with($input)
            ->shouldIterateAs($generator());

        $this::with($generator)
            ->shouldIterateAs($generator());

        $this::with('abc')
            ->shouldIterateAs(['a', 'b', 'c']);

        $this::with('abc def', ' ')
            ->shouldIterateAs(['abc', 'def']);

        $stream = static function () {
            $stream = fopen(__DIR__ . '/../../../.editorconfig', 'rb');

            while (false !== $chunk = fgetc($stream)) {
                yield $chunk;
            }

            fclose($stream);
        };

        $this::with($stream)
            ->split(static function ($v): bool {
                return "\n" === $v;
            })
            ->last()
            ->map(static function (array $value): array {
                array_shift($value);

                return $value;
            })
            ->unwrap()
            ->implode()
            ->shouldReturn('indent_size = 4');

        $stream = fopen(__DIR__ . '/../../fixtures/sample.txt', 'rb');

        $this::with($stream)
            ->shouldIterateAs(['a', 'b', 'c']);

        $this::with(1)
            ->shouldIterateAs([1]);
    }

    public function it_can_window(): void
    {
        $this::fromIterable(range('a', 'z'))
            ->window(2)
            ->shouldIterateAs([
                0 => [
                    0 => 'a',
                ],
                1 => [
                    0 => 'a',
                    1 => 'b',
                ],
                2 => [
                    0 => 'a',
                    1 => 'b',
                    2 => 'c',
                ],
                3 => [
                    0 => 'b',
                    1 => 'c',
                    2 => 'd',
                ],
                4 => [
                    0 => 'c',
                    1 => 'd',
                    2 => 'e',
                ],
                5 => [
                    0 => 'd',
                    1 => 'e',
                    2 => 'f',
                ],
                6 => [
                    0 => 'e',
                    1 => 'f',
                    2 => 'g',
                ],
                7 => [
                    0 => 'f',
                    1 => 'g',
                    2 => 'h',
                ],
                8 => [
                    0 => 'g',
                    1 => 'h',
                    2 => 'i',
                ],
                9 => [
                    0 => 'h',
                    1 => 'i',
                    2 => 'j',
                ],
                10 => [
                    0 => 'i',
                    1 => 'j',
                    2 => 'k',
                ],
                11 => [
                    0 => 'j',
                    1 => 'k',
                    2 => 'l',
                ],
                12 => [
                    0 => 'k',
                    1 => 'l',
                    2 => 'm',
                ],
                13 => [
                    0 => 'l',
                    1 => 'm',
                    2 => 'n',
                ],
                14 => [
                    0 => 'm',
                    1 => 'n',
                    2 => 'o',
                ],
                15 => [
                    0 => 'n',
                    1 => 'o',
                    2 => 'p',
                ],
                16 => [
                    0 => 'o',
                    1 => 'p',
                    2 => 'q',
                ],
                17 => [
                    0 => 'p',
                    1 => 'q',
                    2 => 'r',
                ],
                18 => [
                    0 => 'q',
                    1 => 'r',
                    2 => 's',
                ],
                19 => [
                    0 => 'r',
                    1 => 's',
                    2 => 't',
                ],
                20 => [
                    0 => 's',
                    1 => 't',
                    2 => 'u',
                ],
                21 => [
                    0 => 't',
                    1 => 'u',
                    2 => 'v',
                ],
                22 => [
                    0 => 'u',
                    1 => 'v',
                    2 => 'w',
                ],
                23 => [
                    0 => 'v',
                    1 => 'w',
                    2 => 'x',
                ],
                24 => [
                    0 => 'w',
                    1 => 'x',
                    2 => 'y',
                ],
                25 => [
                    0 => 'x',
                    1 => 'y',
                    2 => 'z',
                ],
            ]);
    }

    public function it_can_wrap()
    {
        $this::fromIterable(['a' => 'A', 'b' => 'B', 'c' => 'C'])
            ->wrap()
            ->shouldIterateAs([
                ['a' => 'A'],
                ['b' => 'B'],
                ['c' => 'C'],
            ]);

        $this::fromIterable(range('a', 'e'))
            ->window(0)
            ->shouldIterateAs(['a', 'b', 'c', 'd', 'e']);
    }

    public function it_can_zip(): void
    {
        $this::fromIterable(range('A', 'C'))
            ->zip(['D', 'E', 'F'])
            ->shouldIterateAs([['A', 'D'], ['B', 'E'], ['C', 'F']]);

        $this::fromIterable(['A', 'C', 'E'])
            ->zip(['B', 'D', 'F', 'H'])
            ->shouldIterateAs([['A', 'B'], ['C', 'D'], ['E', 'F'], [null, 'H']]);

        $collection = Collection::fromIterable(range(1, 5));

        $this::fromIterable($collection)
            ->zip(range('A', 'E'))
            ->shouldIterateAs([[1, 'A'], [2, 'B'], [3, 'C'], [4, 'D'], [5, 'E']]);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(Collection::class);
    }
}
