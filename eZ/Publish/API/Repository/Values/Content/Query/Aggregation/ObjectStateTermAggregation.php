<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace eZ\Publish\API\Repository\Values\Content\Query\Aggregation;

final class ObjectStateTermAggregation extends AbstractTermAggregation
{
    /** @var string */
    private $objectStateGroupIdentifier;

    public function __construct(
        string $name,
        string $objectStateGroupIdentifier,
        int $limit = self::DEFAULT_LIMIT,
        int $minCount = self::DEFAULT_MIN_COUNT
    ) {
        parent::__construct($name, $limit, $minCount);

        $this->objectStateGroupIdentifier = $objectStateGroupIdentifier;
    }

    public function getObjectStateGroupIdentifier(): string
    {
        return $this->objectStateGroupIdentifier;
    }
}
