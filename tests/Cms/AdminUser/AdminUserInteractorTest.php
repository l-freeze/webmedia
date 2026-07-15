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
use App\Cms\Application\AdminUser\Create\Port\IPasswordHasher as CreatePasswordHasher;
use App\Cms\Application\AdminUser\Update\Port\IPasswordHasher as UpdatePasswordHasher;
use App\Cms\Domain\Entity\AdminUser;
use App\Cms\Domain\Repository\IAdminUserRepository;
use App\Cms\Domain\Value\AdminUserId;
use App\Cms\Domain\Value\AdminUserEmail;
use PHPUnit\Framework\TestCase;

final class AdminUserInteractorTest extends TestCase
{
    public function testCreateHashesPasswordAndPersistsUser(): void
    {
        $users = new InMemoryAdminUserRepository();
        $interactor = new CreateInteractor(users: $users, passwords: new FixedPasswordHasher());

        $output = $interactor->execute(input: CreateInput::create(
            email: 'editor@example.test',
            name: 'Editor',
            password: 'password123',
            role: CmsRole::Editor->value,
        ));

        self::assertTrue($output->created);
        self::assertSame(2, $output->userId);
        self::assertSame('hashed:password123', $users->records[2]['password_hash']);
    }

    public function testCreateRejectsDuplicateEmail(): void
    {
        $users = new InMemoryAdminUserRepository();
        $interactor = new CreateInteractor(users: $users, passwords: new FixedPasswordHasher());

        $output = $interactor->execute(input: CreateInput::create(
            email: 'admin@example.test',
            name: 'Admin',
            password: 'password123',
            role: CmsRole::Admin->value,
        ));

        self::assertFalse($output->created);
        self::assertSame('このメールアドレスは既に使われています。', $output->error);
    }

    public function testUpdateRejectsDemotingLastAdmin(): void
    {
        $users = new InMemoryAdminUserRepository();
        $interactor = new UpdateInteractor(users: $users, passwords: new FixedPasswordHasher());

        $output = $interactor->execute(input: UpdateInput::create(
            id: 1,
            currentUser: $this->currentUser(),
            email: 'admin@example.test',
            name: 'Admin',
            password: '',
            role: CmsRole::Editor->value,
        ));

        self::assertFalse($output->updated);
        self::assertSame('最後のadminロールは変更できません。', $output->error);
    }

    public function testDeleteRejectsCurrentUser(): void
    {
        $users = new InMemoryAdminUserRepository();
        $interactor = new DeleteInteractor(users: $users);

        $output = $interactor->execute(input: DeleteInput::create(id: 1, currentUser: $this->currentUser()));

        self::assertFalse($output->deleted);
        self::assertSame('ログイン中の自分自身は削除できません。', $output->error);
    }

    private function currentUser(): CurrentCmsUser
    {
        return new CurrentCmsUser(id: 1, email: 'admin@example.test', name: 'Admin', role: CmsRole::Admin->value);
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
            'role' => CmsRole::Admin->value,
            'password_hash' => 'hash',
        ],
    ];

    public function create(AdminUser $user): AdminUserId
    {
        $ids = array_keys($this->records);
        $id = ($ids === [] ? 0 : max($ids)) + 1;
        $this->records[$id] = [
            'email' => $user->email->value,
            'name' => $user->name->value,
            'role' => $user->role->value,
            'password_hash' => $user->passwordHash->value,
        ];

        return new AdminUserId(value: $id);
    }

    public function update(AdminUser $user): bool
    {
        if ($user->id === null || !isset($this->records[$user->id->value])) {
            return false;
        }

        $this->records[$user->id->value]['email'] = $user->email->value;
        $this->records[$user->id->value]['name'] = $user->name->value;
        $this->records[$user->id->value]['role'] = $user->role->value;
        if ($user->passwordHash->value !== '') {
            $this->records[$user->id->value]['password_hash'] = $user->passwordHash->value;
        }

        return true;
    }

    public function delete(AdminUserId $id): bool
    {
        if (!isset($this->records[$id->value])) {
            return false;
        }

        unset($this->records[$id->value]);

        return true;
    }

    public function emailExists(AdminUserEmail $email, ?AdminUserId $exceptId): bool
    {
        foreach ($this->records as $id => $record) {
            if ($record['email'] === $email->value && $id !== $exceptId?->value) {
                return true;
            }
        }

        return false;
    }

    public function roleOf(AdminUserId $id): ?CmsRole
    {
        $role = $this->records[$id->value]['role'] ?? null;

        return $role === null ? null : CmsRole::from($role);
    }

    public function adminCount(): int
    {
        $count = 0;
        foreach ($this->records as $record) {
            if ($record['role'] === CmsRole::Admin->value) {
                ++$count;
            }
        }

        return $count;
    }
}
