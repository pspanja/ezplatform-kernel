<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace eZ\Publish\API\Repository\Values\Content\Query\Aggregation;

use eZ\Publish\API\Repository\Values\Content\Query\AggregationInterface;

abstract class AbstractTermAggregation implements AggregationInterface
{
    public const DEFAULT_LIMIT = 10;
    public const DEFAULT_MIN_COUNT = 1;

    /**
     * The name of the aggregation.
     *
     * @var string
     */
    protected $name;

    /**
     * Number of facets (terms) returned.
     *
     * @var int
     */
    protected $limit = 10;

    /**
     * Specifies the minimum count. Only facet groups with more or equal results are returned.
     *
     * @var int
     */
    protected $minCount = 1;

    public function __construct(string $name, int $limit = self::DEFAULT_LIMIT, int $minCount = self::DEFAULT_MIN_COUNT)
    {
        $this->name = $name;
        $this->limit = $limit;
        $this->minCount = $minCount;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getMinCount(): int
    {
        return $this->minCount;
    }
}
