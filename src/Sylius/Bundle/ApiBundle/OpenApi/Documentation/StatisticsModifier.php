<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sylius\Bundle\ApiBundle\OpenApi\Documentation;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;
use Sylius\Calendar\Provider\DateTimeProviderInterface;
use Symfony\Component\HttpFoundation\Response;

/** @experimental */
final class StatisticsModifier implements DocumentationModifierInterface
{
    private const PATH = '/admin/statistics';

    public function __construct(
        private string $apiRoute,
        private DateTimeProviderInterface $dateTimeProvider,
    ) {
    }

    public function modify(OpenApi $docs): OpenApi
    {
        $schemas = $docs->getComponents()->getSchemas();
        $schemas['Statistics'] = [
            'type' => 'object',
            'properties' => [
                'salesPerPeriod' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'period' => [
                                'type' => 'string',
                                'format' => 'date-time',
                            ],
                            'total' => [
                                'type' => 'integer',
                                'example' => '1000',
                            ],
                        ],
                    ],
                    'minItems' => 12,
                ],
                'newCustomersCount' => [
                    'type' => 'integer',
                    'example' => '10',
                ],
                'paidOrdersCount' => [
                    'type' => 'integer',
                    'example' => '12',
                ],
                'averageOrderValue' => [
                    'type' => 'integer',
                    'example' => '1000',
                ],
                'totalSales' => [
                    'type' => 'integer',
                    'example' => '12000',
                ],
                'intervalType' => [
                    'type' => 'string',
                    'enum' => ['month', 'year', 'week', 'day'],
                ],
            ],
        ];

        $path = $this->apiRoute . self::PATH;
        $paths = $docs->getPaths();
        $paths->addPath($path, $this->getPathItem());

        return $docs
            ->withPaths($paths)
            ->withComponents($docs->getComponents()->withSchemas($schemas))
        ;
    }

    private function getPathItem(): PathItem
    {
        return new PathItem(
            ref: 'Statistics',
            summary: 'Get statistics',
            get: new Operation(
                operationId: 'get_statistics',
                tags: ['Statistics'],
                responses: [
                    Response::HTTP_OK => [
                        'description' => 'Statistics',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/Statistics',
                                ],
                            ],
                        ],
                    ],
                ],
                summary: 'Get statistics',
                description: 'Get statistics',
                parameters: $this->getParameters(),
            ),
        );
    }

    /** @return Parameter[] */
    private function getParameters(): array
    {
        $channelCode = new Parameter(
            name: 'channelCode',
            in: 'query',
            description: 'Channel to get statistics for',
            required: true,
            schema: [
                'type' => 'string',
            ],
        );

        $startDate = new Parameter(
            name: 'startDate',
            in: 'query',
            description: 'Start date for statistics',
            required: true,
            schema: [
                'type' => 'string',
                'format' => 'date-time',
                'default' => $this->dateTimeProvider->now()->format('Y-01-01\T00:00:00'),
            ],
        );

        $dateInterval = new Parameter(
            name: 'dateInterval',
            in: 'query',
            description: 'Date interval for statistics',
            required: true,
            schema: [
                'type' => 'string',
                'default' => 'P1M',
            ],
        );

        $endDate = new Parameter(
            name: 'endDate',
            in: 'query',
            description: 'End date for statistics',
            required: true,
            schema: [
                'type' => 'string',
                'format' => 'date-time',
                'default' => $this->dateTimeProvider->now()->format('Y-12-31\T23:59:59'),
            ],
        );

        return [$channelCode, $startDate, $dateInterval, $endDate];
    }
}
