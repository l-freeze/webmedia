<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Auth\Login;

use App\Cms\Application\Auth\Login\Input;
use App\Cms\Application\Auth\Login\Interactor;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Csrf;
use App\Framework\Security\Session;
use App\Framework\View\TemplateRenderer;
use App\Shared\Event\Application\EventBusInterface;
use App\Shared\SecurityLog\Domain\SecurityLogEvent;

final readonly class Controller
{
    public function __construct(
        private TemplateRenderer $renderer,
        private Csrf $csrf,
        private Session $session,
        private Interactor $login,
        private EventBusInterface $events,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->login->execute(Input::create(
            email: $request->input(key: 'email'),
            password: $request->input(key: 'password'),
            ipAddress: $this->serverString(request: $request, key: 'REMOTE_ADDR') ?? '',
        ));
        if (!$output->authenticated) {
            $this->events->dispatch(event: $this->securityEvent(
                request: $request,
                result: $output->blocked ? 'blocked' : 'failure',
            ));

            return $this->form(error: 'ログインに失敗しました。時間をおいて再度お試しください。');
        }

        $this->session->regenerate();
        $this->session->set(key: 'cms.user_id', value: $output->userId);
        $this->session->set(key: 'cms.role', value: $output->role);
        $this->events->dispatch(event: $this->securityEvent(
            request: $request,
            result: 'success',
            actorUserId: $output->userId === null ? null : (string) $output->userId,
            actorRole: $output->role,
        ));

        return Response::redirect('/admin');
    }

    private function securityEvent(
        Request $request,
        string $result,
        ?string $actorUserId = null,
        ?string $actorRole = null,
    ): SecurityLogEvent {
        return new SecurityLogEvent(
            eventType: 'cms.login',
            result: $result,
            actorUserId: $actorUserId,
            actorRole: $actorRole,
            ipAddress: $this->serverString(request: $request, key: 'REMOTE_ADDR'),
            userAgent: $this->serverString(request: $request, key: 'HTTP_USER_AGENT'),
        );
    }

    private function serverString(Request $request, string $key): ?string
    {
        $value = $request->server[$key] ?? null;

        return is_scalar($value) ? (string) $value : null;
    }

    private function form(string $error): Response
    {
        return new Response(body: $this->renderer->render(template: 'auth/login.latte', params: [
            'csrf' => $this->csrf->token(),
            'error' => $error,
        ]), status: Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
