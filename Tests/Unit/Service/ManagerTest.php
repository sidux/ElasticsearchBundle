<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\DSL\Aggregation;

use ONGR\ElasticsearchBundle\Service\Manager;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Data provider for testBulk()
     *
     * @return array[]
     *
     * @todo Add more test cases to cover all logic
     */
    public function getTestBulkData()
    {
        return [
            [
                'expected' => [
                    'body' => [
                        [
                            'update' => ['_index' => 'test', '_type' => 'product'],
                        ],
                        [
                            'doc' => ['title' => 'Sample'],
                        ],
                    ],
                ],
                'calls' => [
                    [
                        'update',
                        'product',
                        [
                            'doc' => [
                                'title' => 'Sample',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'expected' => [
                    'body' => [
                        [
                            'update' => ['_index' => 'test', '_type' => 'product'],
                        ],
                        [
                            'script' => 'ctx._source.counter += count',
                            'params' => ['count' => '4'],
                        ],
                    ],
                ],
                'calls' => [
                    [
                        'update',
                        'product',
                        [
                            'script' => 'ctx._source.counter += count',
                            'params' => ['count' => '4'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Test if manager builds correct bulk structure
     *
     * @param array  $expected
     * @param array  $calls
     *
     * @dataProvider getTestBulkData()
     */
    public function testBulk($expected, $calls)
    {
        $indices = $this->getMock('Elasticsearch\Namespaces\IndicesNamespace', [], [], '', false);

        $esClient = $this->getMock('Elasticsearch\Client', [], [], '', false);
        $esClient->expects($this->once())->method('bulk')->with($expected);
        $esClient->expects($this->any())->method('indices')->will($this->returnValue($indices));

        $metadataCollector = $this->getMockBuilder('ONGR\ElasticsearchBundle\Mapping\MetadataCollector')
            ->disableOriginalConstructor()
            ->getMock();

        $converter = $this->getMockBuilder('ONGR\ElasticsearchBundle\Result\Converter')
            ->disableOriginalConstructor()
            ->getMock();

        $config = ['readonly' => false];

        $manager = new Manager('test', $config, $esClient, ['index' => 'test'], $metadataCollector, $converter);

        foreach ($calls as list($operation, $type, $query)) {
            $manager->bulk($operation, $type, $query);
        }

        $manager->commit();
    }
}
