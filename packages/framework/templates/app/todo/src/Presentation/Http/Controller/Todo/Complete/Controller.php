<?php

declare(strict_types=1);

namespace App\{{ namespace }}\Presentation\Http\Controller\Todo\Complete;

use App\{{ namespace }}\Application\Todo\ITodoRepository;
use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;

final readonly class Controller
{
    public function __construct(private ITodoRepository $todos)
    {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $id = $vars['id'] ?? '';
        if ($id !== '') {
            $this->todos->complete(id: $id);
        }

        return Response::redirect(location: '{{ basePath }}');
    }
}
