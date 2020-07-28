<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace eZ\Publish\API\Repository\Values\Content\Query\Aggregation;

final class RawTermAggregation extends AbstractTermAggregation
{
    /** @var string */
    private $fieldName;

    public function __construct(
        string $name,
        string $fieldName,
        int $limit = self::DEFAULT_LIMIT,
        int $minCount = self::DEFAULT_MIN_COUNT
    ) {
        parent::__construct($name, $limit, $minCount);

        $this->fieldName = $fieldName;
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }
}
