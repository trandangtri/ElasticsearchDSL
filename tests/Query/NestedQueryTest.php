<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchDSL\Tests\Unit\DSL\Query;

use ONGR\ElasticsearchDSL\Query\BoolQuery;
use ONGR\ElasticsearchDSL\Query\MatchQuery;
use ONGR\ElasticsearchDSL\Query\NestedQuery;
use ONGR\ElasticsearchDSL\Query\RangeQuery;
use ONGR\ElasticsearchDSL\Search;

class NestedQueryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Tests toArray method.
     */
    public function testToArray()
    {
        $missingFilterMock = $this->getMockBuilder('ONGR\ElasticsearchDSL\Filter\MissingFilter')
                                  ->setConstructorArgs(['test_field'])
                                  ->getMock();
        $missingFilterMock->expects($this->any())
                          ->method('getType')
                          ->willReturn('test_type');
        $missingFilterMock->expects($this->any())
                          ->method('toArray')
                          ->willReturn(['testKey' => 'testValue']);

        $result = [
            'path'  => 'test_path',
            'query' => [
                'test_type' => ['testKey' => 'testValue'],
            ],
        ];

        $query = new NestedQuery('test_path', $missingFilterMock);
        $this->assertEquals($result, $query->toArray());
    }

    /**
     * Tests if Nested Query has parameters.
     */
    public function testParameters()
    {
        $nestedQuery = $this->getMockBuilder('ONGR\ElasticsearchDSL\Query\NestedQuery')
                            ->disableOriginalConstructor()
                            ->setMethods(null)
                            ->getMock();

        $this->assertTrue(method_exists($nestedQuery, 'addParameter'), 'Nested query must have addParameter method');
        $this->assertTrue(method_exists($nestedQuery, 'setParameters'), 'Nested query must have setParameters method');
        $this->assertTrue(method_exists($nestedQuery, 'getParameters'), 'Nested query must have getParameters method');
        $this->assertTrue(method_exists($nestedQuery, 'hasParameter'), 'Nested query must have hasParameter method');
        $this->assertTrue(method_exists($nestedQuery, 'getParameter'), 'Nested query must have getParameter method');
    }

    /**
     * Tests if Nested Query has 1 Boolean query.
     */
    public function testWith1Boolean()
    {

        // Case 1: With one Bool Query
        $matchQuery = new MatchQuery('some.field', 'someValue');

        $boolQuery = new BoolQuery();
        $boolQuery->add($matchQuery, BoolQuery::MUST);

        $nestedQuery = new NestedQuery('urls', $boolQuery);

        $search = new Search();
        $search->addQuery($nestedQuery);

        $expected = [
            'query' => [
                'nested' => [
                    'path'  => 'urls',
                    'query' => [
                        'bool' => [
                            'must' => [
                                'match' => ['some.field' => ['query' => 'someValue']]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals($expected, $search->toArray());
    }

    /**
     * Tests if Nested Query has 2 Boolean queries.
     */
    public function testWith2Boolean()
    {
        $matchQuery = new MatchQuery('obj1.name', 'blue');
        $rangeQuery = new RangeQuery('obj1.count', ['gt' => 5]);

        $boolQuery = new BoolQuery();
        $boolQuery->add($matchQuery);
        $boolQuery->add($rangeQuery);

        $nestedQuery = new NestedQuery('obj1', $boolQuery);
        $nestedQuery->addParameter('score_mode', 'avg');

        $search = new Search();
        $search->addQuery($nestedQuery);

        $expected = [
            'query' => [
                'nested' => [
                    'path'       => 'obj1',
                    'score_mode' => 'avg',
                    'query'      => [
                        'bool' => [
                            'must' => [
                                [
                                    'match' => ['obj1.name' => ['query' => 'blue']]
                                ],
                                [
                                    'range' => ['obj1.count' => ['gt' => 5]]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals($expected, $search->toArray());
    }
}
