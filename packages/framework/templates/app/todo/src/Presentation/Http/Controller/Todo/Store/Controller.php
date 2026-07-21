<?php

declare(strict_types=1);

namespace App\{{ namespace }}\Presentation\Http\Controller\Todo\Store;

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
        $title = trim($request->input(key: 'title'));
        if ($title !== '') {
            $this->todos->add(title: $title);
        }

        return Response::redirect(location: '{{ basePath }}');
    }
}
