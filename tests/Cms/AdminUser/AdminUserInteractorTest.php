<?php

declare(strict_types=1);

namespace Tests\Cms\AdminUser;

use App\Cms\Application\AdminUser\Create\Input as CreateInput;
use App\Cms\Application\AdminUser\Create\Interactor as CreateInteractor;
use App\Cms\Application\AdminUser\Delete\Input as DeleteInput;
use App\Cms\Application\AdminUser\Delete\Interactor as DeleteInteractor;
use App\Cms\Application\AdminUser\Update\Input as UpdateInput;
use App\Cms\Application\AdminUser\Update\Interactor as UpdateInteractor;
use App\Cms\Domain\Value\CmsRole;
use App\Cms\Application\Auth\GetCurrentUser\Value\CurrentCmsUser;
use App\Cms\Application\AdminUser\Create\Port\PasswordHasher as CreatePasswordHasher;
use App\Cms\Application\AdminUser\Update\Port\PasswordHasher as UpdatePasswordHasher;
use App\Cms\Domain\Entity\AdminUser;
use App\Cms\Domain\Repository\IAdminUserRepository;
use PHPUnit\Framework\TestCase;

final class AdminUserInteractorTest extends TestCase
{
    public function testCreateHashesPasswordAndPersistsUser(): void
    {
        $users = new InMemoryAdminUserRepository();
        $interactor = new CreateInteractor(users: $users, passwords: new FixedPasswordHasher());

        $output = $interactor->execute(input: new CreateInput(data: [
            'email' => 'editor@example.test',
            'name' => 'Editor',
            'role' => CmsRole::EDITOR,
            'password' => 'password123',
        ]));

        self::assertTrue($output->created);
        self::assertSame(2, $output->userId);
        self::assertSame('hashed:password123', $users->records[2]['password_hash']);
    }

    public function testCreateRejectsDuplicateEmail(): void
    {
        $users = new InMemoryAdminUserRepository();
        $interactor = new CreateInteractor(users: $users, passwords: new FixedPasswordHasher());

        $output = $interactor->execute(input: new CreateInput(data: [
            'email' => 'admin@example.test',
            'name' => 'Admin',
            'role' => CmsRole::ADMIN,
            'password' => 'password123',
        ]));

        self::assertFalse($output->created);
        self::assertSame('このメールアドレスは既に使われています。', $output->error);
    }

    public function testUpdateRejectsDemotingLastAdmin(): void
    {
        $users = new InMemoryAdminUserRepository();
        $interactor = new UpdateInteractor(users: $users, passwords: new FixedPasswordHasher());

        $output = $interactor->execute(input: new UpdateInput(
            id: 1,
            data: [
                'email' => 'admin@example.test',
                'name' => 'Admin',
                'role' => CmsRole::EDITOR,
                'password' => '',
            ],
            currentUser: $this->currentUser(),
        ));

        self::assertFalse($output->updated);
        self::assertSame('最後のadminロールは変更できません。', $output->error);
    }

    public function testDeleteRejectsCurrentUser(): void
    {
        $users = new InMemoryAdminUserRepository();
        $interactor = new DeleteInteractor(users: $users);

        $output = $interactor->execute(input: new DeleteInput(id: 1, currentUser: $this->currentUser()));

        self::assertFalse($output->deleted);
        self::assertSame('ログイン中の自分自身は削除できません。', $output->error);
    }

    private function currentUser(): CurrentCmsUser
    {
        return new CurrentCmsUser(id: 1, email: 'admin@example.test', name: 'Admin', role: CmsRole::ADMIN);
    }
}

final class FixedPasswordHasher implements CreatePasswordHasher, UpdatePasswordHasher
{
    public function hash(string $plainPassword): string
    {
        return 'hashed:' . $plainPassword;
    }
}

final class InMemoryAdminUserRepository implements IAdminUserRepository
{
    /** @var array<int, array{email:string,name:string,role:string,password_hash:string}> */
    public array $records = [
        1 => [
            'email' => 'admin@example.test',
            'name' => 'Admin',
            'role' => CmsRole::ADMIN,
            'password_hash' => 'hash',
        ],
    ];

    public function create(AdminUser $user): int
    {
        $ids = array_keys($this->records);
        $id = ($ids === [] ? 0 : max($ids)) + 1;
        $this->records[$id] = [
            'email' => $user->email,
            'name' => $user->name,
            'role' => $user->role,
            'password_hash' => $user->passwordHash,
        ];

        return $id;
    }

    public function update(AdminUser $user): bool
    {
        if ($user->id === null || !isset($this->records[$user->id])) {
            return false;
        }

        $this->records[$user->id]['email'] = $user->email;
        $this->records[$user->id]['name'] = $user->name;
        $this->records[$user->id]['role'] = $user->role;
        if ($user->passwordHash !== '') {
            $this->records[$user->id]['password_hash'] = $user->passwordHash;
        }

        return true;
    }

    public function delete(int $id): bool
    {
        if (!isset($this->records[$id])) {
            return false;
        }

        unset($this->records[$id]);

        return true;
    }

    public function emailExists(string $email, ?int $exceptId): bool
    {
        foreach ($this->records as $id => $record) {
            if ($record['email'] === $email && $id !== $exceptId) {
                return true;
            }
        }

        return false;
    }

    public function roleOf(int $id): ?string
    {
        return $this->records[$id]['role'] ?? null;
    }

    public function adminCount(): int
    {
        return count(array_filter(
            $this->records,
            static fn (array $record): bool => $record['role'] === CmsRole::ADMIN,
        ));
    }
}
