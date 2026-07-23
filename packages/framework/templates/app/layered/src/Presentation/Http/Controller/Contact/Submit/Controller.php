<?php

declare(strict_types=1);

namespace App\{{ namespace }}\Presentation\Http\Controller\Contact\Submit;

use App\{{ namespace }}\Application\Contact\Submit\Input;
use App\{{ namespace }}\Application\Contact\Submit\Interactor;
use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\Security\Csrf;
use Pera\Framework\View\ITemplateRenderer;

final readonly class Controller
{
    public function __construct(
        private Interactor $interactor,
        private ITemplateRenderer $renderer,
        private Csrf $csrf,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->interactor->execute(input: new Input(
            name: $request->input(key: 'name'),
            email: $request->input(key: 'email'),
            message: $request->input(key: 'message'),
        ));

        return new Response(body: $this->renderer->render(template: 'contact.latte', params: [
            'title' => '{{ name }} contact',
            'action' => '{{ contactPath }}',
            'csrfToken' => $this->csrf->token(),
            'values' => [
                'name' => $request->input(key: 'name'),
                'email' => $request->input(key: 'email'),
                'message' => $request->input(key: 'message'),
            ],
            'error' => $output->validation->firstMessage(),
            'accepted' => $output->accepted,
        ]), status: $output->accepted ? Response::HTTP_OK : Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
