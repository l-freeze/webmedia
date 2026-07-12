<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Audit\List;

use App\Cms\Application\Audit\List\Input;
use App\Cms\Application\Audit\List\Interactor;
use App\Cms\Application\Audit\List\Value\AuditLogSearch;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\ITemplateRenderer;

final readonly class Controller
{
    private const FILTER_VALUE_MAX_LENGTH = 120;
    private const START_OF_DAY_TIME = 'T00:00:00';
    private const END_OF_DAY_TIME = 'T23:59:59';

    public function __construct(
        private ITemplateRenderer $renderer,
        private Interactor $interactor,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $filters = [
            'date_from' => $this->queryValue(request: $request, key: 'date_from'),
            'date_to' => $this->queryValue(request: $request, key: 'date_to'),
            'result' => $this->queryValue(request: $request, key: 'result'),
            'action' => $this->queryValue(request: $request, key: 'action'),
            'resource_type' => $this->queryValue(request: $request, key: 'resource_type'),
            'resource_id' => $this->queryValue(request: $request, key: 'resource_id'),
            'actor_user_id' => $this->queryValue(request: $request, key: 'actor_user_id'),
        ];
        $limit = (int) $request->queryInput(key: 'limit', default: (string) Input::DEFAULT_LIMIT);
        $page = (int) $request->queryInput(key: 'page', default: '1');
        $sort = $request->queryInput(key: 'sort', default: 'desc') === 'asc' ? 'asc' : 'desc';

        $output = $this->interactor->execute(Input::create(
            page: $page,
            limit: $limit,
            sortDirection: $sort,
            search: new AuditLogSearch(
                timestampFrom: $this->dateBoundary(value: $filters['date_from'], endOfDay: false),
                timestampTo: $this->dateBoundary(value: $filters['date_to'], endOfDay: true),
                result: $filters['result'],
                action: $filters['action'],
                resourceType: $filters['resource_type'],
                resourceId: $filters['resource_id'],
                actorUserId: $filters['actor_user_id'],
            ),
        ));

        $baseQuery = array_filter(
            array_merge($filters, [
                'limit' => (string) $output->perPage,
                'sort' => $sort,
            ]),
            static fn (string $value): bool => $value !== '',
        );

        return new Response(body: $this->renderer->render(template: 'audit/index.latte', params: [
            'title' => '監査ログ',
            'rows' => $output->rows,
            'filters' => $filters,
            'sort' => $sort,
            'sortToggleUrl' => '/admin/audit-logs?' . http_build_query(array_merge($baseQuery, [
                'sort' => $sort === 'asc' ? 'desc' : 'asc',
                'page' => 1,
            ])),
            'pagination' => [
                'current_page' => $output->currentPage,
                'per_page' => $output->perPage,
                'total_items' => $output->totalItems,
                'total_pages' => $output->totalPages,
                'previous_url' => $output->previousPage() === null
                    ? null
                    : '/admin/audit-logs?' . http_build_query(array_merge($baseQuery, ['page' => $output->previousPage()])),
                'next_url' => $output->nextPage() === null
                    ? null
                    : '/admin/audit-logs?' . http_build_query(array_merge($baseQuery, ['page' => $output->nextPage()])),
            ],
        ]));
    }

    private function queryValue(Request $request, string $key): string
    {
        return mb_substr(trim($request->queryInput(key: $key)), 0, self::FILTER_VALUE_MAX_LENGTH);
    }

    private function dateBoundary(string $value, bool $endOfDay): string
    {
        if ($value === '') {
            return '';
        }

        return $value . ($endOfDay ? self::END_OF_DAY_TIME : self::START_OF_DAY_TIME);
    }
}
