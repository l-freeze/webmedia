<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Http;

use App\Framework\Http\Response;
use App\Framework\Security\Session;

trait AuthGuard
{
    private function requireAuth(Session $session): ?Response
    {
        return $session->has('cms.user_id') ? null : Response::redirect('/admin/login');
    }
}
