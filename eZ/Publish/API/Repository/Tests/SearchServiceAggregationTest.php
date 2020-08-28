<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

use eZ\Publish\API\Repository\Tests\BaseTest;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Aggregation\ContentTypeGroupTermAggregation;
use eZ\Publish\API\Repository\Values\Content\Query\Aggregation\ContentTypeTermAggregation;
use eZ\Publish\API\Repository\Values\Content\Query\Aggregation\LanguageTermAggregation;
use eZ\Publish\API\Repository\Values\Content\Query\Aggregation\ObjectStateTermAggregation;
use eZ\Publish\API\Repository\Values\Content\Query\Aggregation\SectionTermAggregation;
use eZ\Publish\API\Repository\Values\Content\Query\Aggregation\UserTermAggregation;
use eZ\Publish\API\Repository\Values\Content\Query\Aggregation\VisibilityTermAggregation;
use eZ\Publish\API\Repository\Values\Content\Query\AggregationInterface;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\ContentTypeIdentifier;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\LanguageCode;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\MatchAll;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\SectionIdentifier;
use eZ\Publish\API\Repository\Values\Content\Search\AggregationResult\TermAggregationResult;
use eZ\Publish\API\Repository\Values\Content\Search\AggregationResult\TermAggregationResultEntry;
use eZ\Publish\API\Repository\Values\Content\Search\AggregationResultCollection;

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
    public function testFindContentWithContentTypeTermAggregation(): void
    {
        $contentTypeService = $this->getRepository()->getContentTypeService();

        $expectedRawResults = [
            'folder' => 6,
            'user_group' => 6,
            'user' => 2,
            'common_ini_settings' => 1,
            'template_look' => 1,
            'feedback_form' => 1,
            'landing_page' => 1,
        ];

        $query = $this->createQueryWithAggregation(
            new ContentTypeTermAggregation('content_type'),
            new ContentTypeIdentifier(array_keys($expectedRawResults))
        );

        $expectedAggregationResult = $this->createTermAggregationResult(
            'content_type',
            $expectedRawResults,
            [$contentTypeService, 'loadContentTypeByIdentifier']
        );

        $this->assertContentAggregationResult($expectedAggregationResult, $query);
    }

    public function testFindLocationWithContentTypeTermAggregation(): void
    {
        $contentTypeService = $this->getRepository()->getContentTypeService();

        $expectedRawResults = [
            'folder' => 6,
            'user_group' => 6,
            'user' => 2,
            'common_ini_settings' => 1,
            'template_look' => 1,
            'feedback_form' => 1,
            'landing_page' => 1,
        ];

        $query = $this->createLocationQueryWithAggregation(
            new ContentTypeTermAggregation('content_type'),
            new ContentTypeIdentifier(array_keys($expectedRawResults))
        );

        $expectedAggregationResult = $this->createTermAggregationResult(
            'content_type',
            $expectedRawResults,
            [$contentTypeService, 'loadContentTypeByIdentifier']
        );

        $this->assertLocationAggregationResult($expectedAggregationResult, $query);
    }

    public function testContentTypeGroupTermAggregation(): void
    {
        $contentTypeService = $this->getRepository()->getContentTypeService();

        $query = $this->createQueryWithAggregation(
            new ContentTypeGroupTermAggregation('content_type_group'),
        );

        $expectedAggregationResult = $this->createTermAggregationResult(
            'content_type_group',
            [
                'Content' => 8,
                'Users' => 8,
                'Setup' => 2,
            ],
            [$contentTypeService, 'loadContentTypeGroupByIdentifier']
        );

        $this->assertContentAggregationResult($expectedAggregationResult, $query);
    }

    public function testDateRangeAggregation(): void
    {
    }

    public function testLanguageTermAggregation(): void
    {
        $languageService = $this->getRepository()->getContentLanguageService();

        $query = $this->createQueryWithAggregation(
            new LanguageTermAggregation('language'),
            new LanguageCode(['eng-US', 'eng-GB'])
        );

        $expectedAggregationResult = $this->createTermAggregationResult(
            'language',
            [
                'eng-US' => 16,
                'eng-GB' => 2,
            ],
            [$languageService, 'loadLanguage']
        );

        $this->assertContentAggregationResult($expectedAggregationResult, $query);
    }

    public function testObjectStateTermAggregation(): void
    {
        $objectStateService = $this->getRepository()->getObjectStateService();

        $query = $this->createQueryWithAggregation(
            new ObjectStateTermAggregation('object_state', 'ez_lock')
        );

        $objectStateGroup = $objectStateService->loadObjectStateGroupByIdentifier('ez_lock');

        $expectedAggregationResult = $this->createTermAggregationResult(
            'object_state',
            [
                // TODO: Change the state of some content objects to have better test data
                'not_locked' => 18,
            ],
            static function (string $identifier) use ($objectStateService, $objectStateGroup) {
                return $objectStateService->loadObjectStateByIdentifier($objectStateGroup, $identifier);
            }
        );

        $this->assertContentAggregationResult($expectedAggregationResult, $query);
    }

    public function testSectionTermAggregation(): void
    {
        $sectionService = $this->getRepository()->getSectionService();

        $query = $this->createQueryWithAggregation(
            new SectionTermAggregation('section'),
            new SectionIdentifier(['users', 'media', 'standard', 'setup', 'design'])
        );

        $expectedAggregationResult = $this->createTermAggregationResult(
            'section',
            [
                'users' => 8,
                'media' => 4,
                'standard' => 2,
                'setup' => 2,
                'design' => 2,
            ],
            [$sectionService, 'loadSectionByIdentifier']
        );

        $this->assertContentAggregationResult($expectedAggregationResult, $query);
    }

    public function testUserOwnerTermAggregation(): void
    {
        $userService = $this->getRepository()->getUserService();

        $query = $this->createQueryWithAggregation(
            new UserTermAggregation('owner', UserTermAggregation::OWNER)
        );

        $expectedAggregationResult = $this->createTermAggregationResult(
            'owner',
            [
                'admin' => 18,
            ],
            [$userService, 'loadUserByLogin']
        );

        $this->assertContentAggregationResult($expectedAggregationResult, $query);
    }

    public function testUserModifierTermAggregation(): void
    {
        $userService = $this->getRepository()->getUserService();

        $query = $this->createQueryWithAggregation(
            new UserTermAggregation('modifier', UserTermAggregation::MODIFIER)
        );

        $expectedAggregationResult = $this->createTermAggregationResult(
            'modifier',
            [
                'admin' => 18,
            ],
            [$userService, 'loadUserByLogin']
        );

        $this->assertContentAggregationResult($expectedAggregationResult, $query);
    }

    public function testUserGroupTermAggregation(): void
    {
        $userService = $this->getRepository()->getUserService();

        $query = $this->createQueryWithAggregation(
            new UserTermAggregation('user_group', UserTermAggregation::GROUP)
        );

        $expectedAggregationResult = $this->createTermAggregationResult(
            'user_group',
            [
                12 => 18,
                14 => 18,
                4 => 18
            ],
            [$userService, 'loadUserGroup']
        );

        $this->assertContentAggregationResult($expectedAggregationResult, $query);
    }

    public function testVisibilityTermAggregation(): void
    {
        $query = $this->createQueryWithAggregation(
            new VisibilityTermAggregation('visibility')
        );

        $expectedAggregationResult = $this->createTermAggregationResult(
            'visibility',
            [
                true => 18,
            ],
        );

        $this->assertContentAggregationResult($expectedAggregationResult, $query);
    }

    private function createTermAggregationResult(
        string $name,
        iterable $values,
        ?callable $loader = null
    ): AggregationResultCollection {
        $entries = [];
        foreach ($values as $key => $count) {
            $entries[] = new TermAggregationResultEntry($loader ? $loader($key) : $key, $count);
        }

        return new AggregationResultCollection([
            new TermAggregationResult($name, $entries),
        ]);
    }

    private function createQueryWithAggregation(
        AggregationInterface $aggregation,
        Criterion $filter = null
    ): Query {
        $query = new Query();
        $query->aggregations[] = $aggregation;
        $query->filter = $filter ?? new MatchAll();
        $query->limit = 0;

        return $query;
    }

    private function createLocationQueryWithAggregation(
        AggregationInterface $aggregation,
        Criterion $filter = null
    ): LocationQuery
    {
        $query = new LocationQuery();
        $query->aggregations[] = $aggregation;
        $query->filter = $filter ?? new MatchAll();
        $query->limit = 0;

        return $query;
    }

    private function assertContentAggregationResult(
        AggregationResultCollection $expectedResult,
        Query $query
    ): void {
        $searchService = $this->getRepository()->getSearchService();

        $this->assertEquals(
            $expectedResult,
            $searchService->findContent($query)->aggregations
        );
    }

    private function assertLocationAggregationResult(
        AggregationResultCollection $expectedResult,
        Query $query
    ): void {
        $searchService = $this->getRepository()->getSearchService();

        $this->assertEquals(
            $expectedResult,
            $searchService->findLocations($query)->aggregations
        );
    }
}
