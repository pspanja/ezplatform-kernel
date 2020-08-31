<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace eZ\Publish\API\Repository\Tests;

use DateTime;
use eZ\Publish\API\Repository\Exceptions\ContentFieldValidationException;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Aggregation\ContentTypeGroupTermAggregation;
use eZ\Publish\API\Repository\Values\Content\Query\Aggregation\ContentTypeTermAggregation;
use eZ\Publish\API\Repository\Values\Content\Query\Aggregation\DateMetadataRangeAggregation;
use eZ\Publish\API\Repository\Values\Content\Query\Aggregation\Field\CheckboxTermAggregation;
use eZ\Publish\API\Repository\Values\Content\Query\Aggregation\Field\CountryTermAggregation;
use eZ\Publish\API\Repository\Values\Content\Query\Aggregation\Field\FloatRangeAggregation;
use eZ\Publish\API\Repository\Values\Content\Query\Aggregation\Field\FloatStatsAggregation;
use eZ\Publish\API\Repository\Values\Content\Query\Aggregation\Field\IntegerRangeAggregation;
use eZ\Publish\API\Repository\Values\Content\Query\Aggregation\Field\IntegerStatsAggregation;
use eZ\Publish\API\Repository\Values\Content\Query\Aggregation\LanguageTermAggregation;
use eZ\Publish\API\Repository\Values\Content\Query\Aggregation\ObjectStateTermAggregation;
use eZ\Publish\API\Repository\Values\Content\Query\Aggregation\Range;
use eZ\Publish\API\Repository\Values\Content\Query\Aggregation\SectionTermAggregation;
use eZ\Publish\API\Repository\Values\Content\Query\Aggregation\UserMetadataTermAggregation;
use eZ\Publish\API\Repository\Values\Content\Query\Aggregation\VisibilityTermAggregation;
use eZ\Publish\API\Repository\Values\Content\Query\AggregationInterface;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\ContentTypeIdentifier;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\MatchAll;
use eZ\Publish\API\Repository\Values\Content\Search\AggregationResult;
use eZ\Publish\API\Repository\Values\Content\Search\AggregationResult\RangeAggregationResult;
use eZ\Publish\API\Repository\Values\Content\Search\AggregationResult\RangeAggregationResultEntry;
use eZ\Publish\API\Repository\Values\Content\Search\AggregationResult\StatsAggregationResult;
use eZ\Publish\API\Repository\Values\Content\Search\AggregationResult\TermAggregationResult;
use eZ\Publish\API\Repository\Values\Content\Search\AggregationResult\TermAggregationResultEntry;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use eZ\Publish\API\Repository\Values\ObjectState\ObjectState;
use eZ\Publish\Core\FieldType\Checkbox\Value as CheckboxValue;

/**
 * Test case for aggregations in the SearchService.
 *
 * @see \eZ\Publish\API\Repository\SearchService
 * @group integration
 * @group search
 * @group aggregations
 */
final class SearchServiceAggregationTest extends BaseTest
{
    private const EXAMPLE_CONTENT_TYPE_IDENTIFIER = 'content_type';
    private const EXAMPLE_FIELD_DEFINITION_IDENTIFIER = 'field';

    protected function setUp(): void
    {
        parent::setUp();

//        $searchService = $this->getRepository()->getSearchService();
//        if (!$searchService->supports(SearchService::CAPABILITY_AGGREGATIONS)) {
//            $this->markTestSkipped("Search engine doesn't support aggregations");
//        }
    }

    /**
     * @dataProvider dataProviderForTestAggregation
     */
    public function testFindContentWithAggregation(
        AggregationInterface $aggregation,
        AggregationResult $expectedResult
    ): void {
        $searchService = $this->getRepository()->getSearchService();

        $query = new Query();
        $query->aggregations[] = $aggregation;
        $query->filter = new MatchAll();
        $query->limit = 0;

        $this->assertEquals(
            $expectedResult,
            $searchService->findContent($query)->aggregations->first()
        );
    }

    /**
     * @dataProvider dataProviderForTestAggregation
     */
    public function testFindLocationWithAggregation(
        AggregationInterface $aggregation,
        AggregationResult $expectedResult
    ): void {
        $searchService = $this->getRepository()->getSearchService();

        $query = new LocationQuery();
        $query->aggregations[] = $aggregation;
        $query->filter = new MatchAll();
        $query->limit = 0;

        $this->assertEquals(
            $expectedResult,
            $searchService->findLocations($query)->aggregations->first()
        );
    }

    public function dataProviderForTestAggregation(): iterable
    {
        yield ContentTypeTermAggregation::class => $this->createTermAggregationTestCase(
            new ContentTypeTermAggregation('content_type'),
            [
                'folder' => 6,
                'user_group' => 6,
                'user' => 2,
                'common_ini_settings' => 1,
                'template_look' => 1,
                'feedback_form' => 1,
                'landing_page' => 1,
            ],
            [$this->getRepository()->getContentTypeService(), 'loadContentTypeByIdentifier']
        );

        yield ContentTypeGroupTermAggregation::class => $this->createTermAggregationTestCase(
            new ContentTypeGroupTermAggregation('content_type_group'),
            [
                'Content' => 8,
                'Users' => 8,
                'Setup' => 2,
            ],
            [$this->getRepository()->getContentTypeService(), 'loadContentTypeGroupByIdentifier']
        );

        yield DateMetadataRangeAggregation::class . '::MODIFIED' => [
            new DateMetadataRangeAggregation(
                'modification_date',
                DateMetadataRangeAggregation::MODIFIED,
                [
                    new Range(null, new DateTime('2003-01-01')),
                    new Range(new DateTime('2003-01-01'), new DateTime('2004-01-01')),
                    new Range(new DateTime('2004-01-01'), null),
                ]
            ),
            new RangeAggregationResult(
                'modification_date',
                [
                    new RangeAggregationResultEntry(
                        new Range(null, new DateTime('2003-01-01')),
                        3
                    ),
                    new RangeAggregationResultEntry(
                        new Range(new DateTime('2003-01-01'), new DateTime('2004-01-01')),
                        3
                    ),
                    new RangeAggregationResultEntry(
                        new Range(new DateTime('2004-01-01'), null),
                        12
                    ),
                ]
            ),
        ];

        yield DateMetadataRangeAggregation::class . '::PUBLISHED' => [
            new DateMetadataRangeAggregation(
                'publication_date',
                DateMetadataRangeAggregation::PUBLISHED,
                [
                    new Range(null, new DateTime('2003-01-01')),
                    new Range(new DateTime('2003-01-01'), new DateTime('2004-01-01')),
                    new Range(new DateTime('2004-01-01'), null),
                ]
            ),
            new RangeAggregationResult(
                'publication_date',
                [
                    new RangeAggregationResultEntry(
                        new Range(null, new DateTime('2003-01-01')),
                        6
                    ),
                    new RangeAggregationResultEntry(
                        new Range(new DateTime('2003-01-01'), new DateTime('2004-01-01')),
                        2
                    ),
                    new RangeAggregationResultEntry(
                        new Range(new DateTime('2004-01-01'), null),
                        10
                    ),
                ]
            ),
        ];

        yield LanguageTermAggregation::class => $this->createTermAggregationTestCase(
            new LanguageTermAggregation('language'),
            [
                'eng-US' => 16,
                'eng-GB' => 2,
            ],
            [$this->getRepository()->getContentLanguageService(), 'loadLanguage']
        );

        yield ObjectStateTermAggregation::class => $this->createTermAggregationTestCase(
            new ObjectStateTermAggregation('object_state', 'ez_lock'),
            [
                // TODO: Change the state of some content objects to have better test data
                'not_locked' => 18,
            ],
            function (string $identifier): ObjectState {
                $objectStateService = $this->getRepository()->getObjectStateService();

                static $objectStateGroup = null;
                if ($objectStateGroup === null) {
                    $objectStateGroup = $objectStateService->loadObjectStateGroupByIdentifier('ez_lock');
                }

                return $objectStateService->loadObjectStateByIdentifier($objectStateGroup, $identifier);
            }
        );

        yield SectionTermAggregation::class => $this->createTermAggregationTestCase(
            new SectionTermAggregation('section'),
            [
                'users' => 8,
                'media' => 4,
                'standard' => 2,
                'setup' => 2,
                'design' => 2,
            ],
            [$this->getRepository()->getSectionService(), 'loadSectionByIdentifier']
        );

        yield UserMetadataTermAggregation::class . '::OWNER' => $this->createTermAggregationTestCase(
            new UserMetadataTermAggregation('owner', UserMetadataTermAggregation::OWNER),
            [
                'admin' => 18,
            ],
            [$this->getRepository()->getUserService(), 'loadUserByLogin']
        );

        yield UserMetadataTermAggregation::class . '::GROUP' => $this->createTermAggregationTestCase(
            new UserMetadataTermAggregation('user_group', UserMetadataTermAggregation::GROUP),
            [
                12 => 18,
                14 => 18,
                4 => 18,
            ],
            [$this->getRepository()->getUserService(), 'loadUserGroup']
        );

        yield UserMetadataTermAggregation::class . '::MODIFIER' => $this->createTermAggregationTestCase(
            new UserMetadataTermAggregation('modifier', UserMetadataTermAggregation::MODIFIER),
            [
                'admin' => 18,
            ],
            [$this->getRepository()->getUserService(), 'loadUserByLogin']
        );

        yield VisibilityTermAggregation::class => $this->createTermAggregationTestCase(
            new VisibilityTermAggregation('visibility'),
            [
                true => 18,
            ]
        );
    }

    /**
     * @dataProvider dataProviderForTestFieldAggregation
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Query\AggregationInterface|\eZ\Publish\API\Repository\Values\Content\Query\Aggregation\FieldAggregationInterface $aggregation
     */
    public function testFindContentWithFieldAggregation(
        AggregationInterface $aggregation,
        string $fieldTypeIdentifier,
        iterable $fieldValues,
        AggregationResult $expectedResult
    ): void {
        $this->createFieldAggregationFixtures(
            $aggregation->getContentTypeIdentifier(),
            $aggregation->getFieldDefinitionIdentifier(),
            $fieldTypeIdentifier,
            $fieldValues
        );

        $searchService = $this->getRepository()->getSearchService();

        $query = new Query();
        $query->aggregations[] = $aggregation;
        $query->filter = new ContentTypeIdentifier($aggregation->getContentTypeIdentifier());
        $query->limit = 0;

        $this->assertEquals(
            $expectedResult,
            $searchService->findContent($query)->aggregations->first()
        );
    }

    /**
     * @dataProvider dataProviderForTestFieldAggregation
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Query\AggregationInterface|\eZ\Publish\API\Repository\Values\Content\Query\Aggregation\FieldAggregationInterface $aggregation
     */
    public function testFindLocationWithFieldAggregation(
        AggregationInterface $aggregation,
        string $fieldTypeIdentifier,
        iterable $fieldValues,
        AggregationResult $expectedResult
    ): void {
        $this->createFieldAggregationFixtures(
            $aggregation->getContentTypeIdentifier(),
            $aggregation->getFieldDefinitionIdentifier(),
            $fieldTypeIdentifier,
            $fieldValues
        );

        $searchService = $this->getRepository()->getSearchService();

        $query = new LocationQuery();
        $query->aggregations[] = $aggregation;
        $query->filter = new ContentTypeIdentifier($aggregation->getContentTypeIdentifier());
        $query->limit = 0;

        $this->assertEquals(
            $expectedResult,
            $searchService->findLocations($query)->aggregations->first()
        );
    }

    public function dataProviderForTestFieldAggregation(): iterable
    {
        yield CheckboxTermAggregation::class => [
            new CheckboxTermAggregation('checkbox_term', 'content_type', 'boolean'),
            'ezboolean',
            [
                new CheckboxValue(true),
                new CheckboxValue(true),
                new CheckboxValue(true),
                new CheckboxValue(false),
                new CheckboxValue(false),
            ],
            new TermAggregationResult(
                'checkbox_term',
                [
                    new TermAggregationResultEntry(true, 3),
                    new TermAggregationResultEntry(false, 2),
                ]
            ),
        ];

        // yield CountryTermAggregation::class . '::TYPE_NAME' => [];
        // yield CountryTermAggregation::class . '::IDC' => [];

        yield CountryTermAggregation::class . '::TYPE_ALPHA_2' => [
            new CountryTermAggregation('country_term', 'content_type', 'country'),
            'ezcountry',
            [
                ['PL', 'EN'],
                ['FR', 'EN'],
                ['EN'],
                ['GA', 'PL', 'FR'],
                ['FR', 'BE', 'EN']
            ],
            new TermAggregationResult(
                'country_term',
                [
                    new TermAggregationResultEntry('EN', 4),
                    new TermAggregationResultEntry('FR', 3),
                    new TermAggregationResultEntry('PL', 2),
                    new TermAggregationResultEntry('GA', 1),
                    new TermAggregationResultEntry('BE', 1),
                ]
            ),
        ];

        // yield CountryTermAggregation::class . '::TYPE_ALPHA_3' => [];

        yield FloatStatsAggregation::class => [
            new FloatStatsAggregation('float_stats', 'content_type', 'float'),
            'ezfloat',
            [1.0 . 2.5, 2.5, 5.25, 7.75],
            new StatsAggregationResult(
                'float_stats',
                5,
                1.0,
                7.75,
                3.8,
                19.0
            ),
        ];

        yield FloatRangeAggregation::class => [
            new IntegerRangeAggregation('integer_range', 'content_type', 'integer', [
                new Range(null, 10.0),
                new Range(10.0, 25.0),
                new Range(25.0, 50.0),
                new Range(50.0, null),
            ]),
            'ezfloat',
            range(1.0, 100.0, 2.5),
            new RangeAggregationResult(
                'integer_range',
                [
                    new RangeAggregationResultEntry(new Range(null, 10.0), 4),
                    new RangeAggregationResultEntry(new Range(10.0, 25.0), 6),
                    new RangeAggregationResultEntry(new Range(25, 50), 9),
                    new RangeAggregationResultEntry(new Range(50, null), 19),
                ]
            ),
        ];

        yield IntegerStatsAggregation::class => [
            new IntegerStatsAggregation('integer_stats', 'content_type', 'integer'),
            'ezinteger',
            [1, 2, 3, 5, 8, 13, 21],
            new StatsAggregationResult(
                'integer_stats',
                7,
                1,
                21,
                7.571428571428571,
                53
            ),
        ];

        yield IntegerRangeAggregation::class => [
            new IntegerRangeAggregation('integer_range', 'content_type', 'integer', [
                new Range(null, 10),
                new Range(10, 25),
                new Range(25, 50),
                new Range(50, null),
            ]),
            'ezinteger',
            range(1, 100),
            new RangeAggregationResult(
                'integer_range',
                [
                    new RangeAggregationResultEntry(new Range(null, 10), 10),
                    new RangeAggregationResultEntry(new Range(10, 25), 15),
                    new RangeAggregationResultEntry(new Range(25, 50), 25),
                    new RangeAggregationResultEntry(new Range(50, null), 50),
                ]
            ),
        ];
    }

    private function createTermAggregationTestCase(
        AggregationInterface $aggregation,
        iterable $expectedEntries,
        ?callable $mapper = null
    ): array {
        if ($mapper === null) {
            $mapper = function ($key) {
                return $key;
            };
        }

        $entries = [];
        foreach ($expectedEntries as $key => $count) {
            $entries[] = new TermAggregationResultEntry($mapper($key), $count);
        }

        $expectedResult = TermAggregationResult::createForAggregation($aggregation, $entries);

        return [$aggregation, $expectedResult];
    }

    private function createFieldAggregationFixtures(
        string $contentTypeIdentifier,
        string $fieldDefinitionIdentifier,
        string $fieldTypeIdentifier,
        iterable $values
    ): void {
        $contentType = $this->createContentTypeForFieldAggregation(
            $contentTypeIdentifier,
            $fieldDefinitionIdentifier,
            $fieldTypeIdentifier
        );

        $contentService = $this->getRepository()->getContentService();

        foreach ($values as $value) {
            $contentCreateStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
            $contentCreateStruct->setField($fieldDefinitionIdentifier, $value);

            try {
                $contentService->publishVersion(
                    $contentService->createContent($contentCreateStruct)->versionInfo
                );
            } catch (ContentFieldValidationException $e) {
                // TODO: Remove var_dump
                var_dump($e->getFieldErrors());
            }
        }

        $this->refreshSearch($this->getRepository());
    }

    private function createContentTypeForFieldAggregation(
        string $contentTypeIdentifier,
        string $fieldDefinitionIdentifier,
        string $fieldTypeIdentifier
    ): ContentType {
        $contentTypeService = $this->getRepository()->getContentTypeService();

        $contentTypeCreateStruct = $contentTypeService->newContentTypeCreateStruct($contentTypeIdentifier);
        $contentTypeCreateStruct->mainLanguageCode = 'eng-GB';
        $contentTypeCreateStruct->names = [
            'eng-GB' => 'Field aggregation',
        ];

        $fieldDefinitionCreateStruct = $contentTypeService->newFieldDefinitionCreateStruct(
            $fieldDefinitionIdentifier,
            $fieldTypeIdentifier
        );
        $fieldDefinitionCreateStruct->names = [
            'eng-GB' => 'Aggregated field',
        ];
        $fieldDefinitionCreateStruct->isSearchable = true;

        $contentTypeCreateStruct->addFieldDefinition($fieldDefinitionCreateStruct);

        $contentTypeDraft = $contentTypeService->createContentType(
            $contentTypeCreateStruct,
            [
                $contentTypeService->loadContentTypeGroupByIdentifier('Content'),
            ]
        );

        $contentTypeService->publishContentTypeDraft($contentTypeDraft);

        return $contentTypeService->loadContentTypeByIdentifier($contentTypeIdentifier);
    }
}
