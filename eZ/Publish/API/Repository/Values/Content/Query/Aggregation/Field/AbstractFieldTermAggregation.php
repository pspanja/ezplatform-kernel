<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace eZ\Publish\API\Repository\Values\Content\Query\Aggregation\Field;

use eZ\Publish\API\Repository\Values\Content\Query\Aggregation\AbstractTermAggregation;
use eZ\Publish\API\Repository\Values\Content\Query\Aggregation\FieldAggregationInterface;

abstract class AbstractFieldTermAggregation extends AbstractTermAggregation implements FieldAggregationInterface
{
    use FieldAggregationTrait;

    public function __construct(
        string $name,
        string $contentTypeIdentifier,
        string $fieldDefinitionIdentifier,
        int $limit = self::DEFAULT_LIMIT,
        int $minCount = self::DEFAULT_MIN_COUNT)
    {
        parent::__construct($name, $limit, $minCount);

        $this->contentTypeIdentifier = $contentTypeIdentifier;
        $this->fieldDefinitionIdentifier = $fieldDefinitionIdentifier;
    }
}
