<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\Login;

use App\Cms\Domain\Repository\IUserRepository;

final readonly class Interactor
{
    public function __construct(
        private IUserRepository $users,
        private PasswordVerifier $passwordVerifier,
    ) {
    }

    public function execute(Input $input): Output
    {
        $user = $this->users->findByEmail(email: $input->email);
        if ($user === null || !$this->passwordVerifier->verify(
            plainPassword: $input->password,
            passwordHash: $user->passwordHash,
        )) {
            return Output::failed();
        }

        return Output::authenticated(userId: $user->id, role: $user->role);
    }
}
