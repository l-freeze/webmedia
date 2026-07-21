<?php

declare(strict_types=1);

namespace App\{{ namespace }}\Presentation\Http\Controller\Contact\Submit;

use App\{{ namespace }}\Application\Contact\Submit\Input;
use App\{{ namespace }}\Application\Contact\Submit\Interactor;
use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;

final readonly class Controller
{
    public function __construct(private Interactor $interactor)
    {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->interactor->execute(input: new Input(
            name: $request->input(key: 'name'),
            email: $request->input(key: 'email'),
            message: $request->input(key: 'message'),
        ));

        return new Response(
            body: json_encode($output->toArray(), JSON_THROW_ON_ERROR),
            status: $output->accepted ? 201 : 422,
            headers: ['Content-Type' => 'application/json; charset=utf-8'],
        );
    }
}
