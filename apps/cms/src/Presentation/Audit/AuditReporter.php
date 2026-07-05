<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Audit;

use App\Framework\Http\Request;

final readonly class AuditReporter
{
    /** @param array<string, mixed> $metadata */
    public function describe(
        Request $request,
        string $action,
        string $resourceType,
        ?string $resourceId = null,
        array $metadata = [],
    ): void {
        $trail = $request->attribute(key: AuditTrail::ATTRIBUTE);
        if (!$trail instanceof AuditTrail) {
            return;
        }

        $trail->describe(summary: new AuditSummary(
            action: $action,
            resourceType: $resourceType,
            resourceId: $resourceId,
            metadata: $metadata,
        ));
    }

    public function articleSaved(Request $request, int $articleId, bool $created): void
    {
        $this->describe(
            request: $request,
            action: $created ? 'article.created' : 'article.updated',
            resourceType: 'article',
            resourceId: (string) $articleId,
            metadata: $this->bodyValues(request: $request, keys: ['title', 'slug', 'status', 'body_format']),
        );
    }

    public function articleTrashed(Request $request, int $articleId): void
    {
        $this->describe(request: $request, action: 'article.trashed', resourceType: 'article', resourceId: (string) $articleId);
    }

    public function articleRestored(Request $request, int $articleId, int $versionNo): void
    {
        $this->describe(
            request: $request,
            action: 'article.restored',
            resourceType: 'article',
            resourceId: (string) $articleId,
            metadata: ['version_no' => $versionNo],
        );
    }

    public function pageSaved(Request $request, int $pageId, bool $created): void
    {
        $this->describe(
            request: $request,
            action: $created ? 'page.created' : 'page.updated',
            resourceType: 'page',
            resourceId: (string) $pageId,
            metadata: $this->bodyValues(request: $request, keys: ['title', 'slug', 'status', 'body_format', 'display_order']),
        );
    }

    public function pageRestored(Request $request, int $pageId, int $versionNo): void
    {
        $this->describe(
            request: $request,
            action: 'page.restored',
            resourceType: 'page',
            resourceId: (string) $pageId,
            metadata: ['version_no' => $versionNo],
        );
    }

    public function inquiryUpdated(Request $request, int $inquiryId, string $status, string $adminNote): void
    {
        $this->describe(
            request: $request,
            action: 'inquiry.updated',
            resourceType: 'inquiry',
            resourceId: (string) $inquiryId,
            metadata: [
                'status' => $status,
                'admin_note_present' => $adminNote !== '',
                'admin_note_length' => strlen($adminNote),
                'admin_note_hash' => $adminNote === '' ? null : hash('sha256', $adminNote),
            ],
        );
    }

    public function adminUserCreated(Request $request, ?int $userId): void
    {
        $this->describe(
            request: $request,
            action: 'admin_user.created',
            resourceType: 'admin_user',
            resourceId: $userId === null ? null : (string) $userId,
            metadata: $this->bodyValues(request: $request, keys: ['email', 'name', 'role']),
        );
    }

    public function adminUserUpdated(Request $request, int $userId): void
    {
        $metadata = $this->bodyValues(request: $request, keys: ['email', 'name', 'role']);
        $metadata['password_changed'] = $request->bodyInput(key: 'password') !== '';
        $this->describe(
            request: $request,
            action: 'admin_user.updated',
            resourceType: 'admin_user',
            resourceId: (string) $userId,
            metadata: $metadata,
        );
    }

    public function adminUserDeleted(Request $request, int $userId): void
    {
        $this->describe(request: $request, action: 'admin_user.deleted', resourceType: 'admin_user', resourceId: (string) $userId);
    }

    public function loginAttemptReset(Request $request, string $scope, string $ipAddress): void
    {
        $metadata = [
            'scope' => $scope,
            'ip_address' => $ipAddress,
            'email_present' => $request->bodyInput(key: 'email') !== '',
            'reason_present' => $request->bodyInput(key: 'reason') !== '',
        ];

        $this->describe(
            request: $request,
            action: 'security.login_attempt_reset',
            resourceType: 'security',
            metadata: $metadata,
        );
    }

    public function mediaDeleted(Request $request, int $mediaId): void
    {
        $this->describe(request: $request, action: 'media.deleted', resourceType: 'media', resourceId: (string) $mediaId);
    }

    public function mediaUploaded(Request $request, int $mediaId): void
    {
        $file = $request->files['media_file'] ?? [];
        if (!is_array($file)) {
            $file = [];
        }
        /** @var array<string, mixed> $file */

        $this->describe(
            request: $request,
            action: 'media.uploaded',
            resourceType: 'media',
            resourceId: (string) $mediaId,
            metadata: [
                'file_name' => $this->fileValue(file: $file, key: 'name'),
                'mime_type' => $this->fileValue(file: $file, key: 'type'),
                'file_size' => $this->fileInt(file: $file, key: 'size'),
                'alt_text_present' => $request->bodyInput(key: 'alt_text') !== '',
                'caption_present' => $request->bodyInput(key: 'caption') !== '',
            ],
        );
    }

    public function settingsUpdated(Request $request): void
    {
        $keys = array_values(array_filter(array_keys($request->body), static fn (string $key): bool => $key !== '_csrf'));
        sort($keys);
        $this->describe(request: $request, action: 'settings.updated', resourceType: 'settings', metadata: ['keys' => $keys]);
    }

    public function categorySaved(Request $request, ?int $categoryId, bool $created): void
    {
        $this->describe(
            request: $request,
            action: $created ? 'category.created' : 'category.updated',
            resourceType: 'category',
            resourceId: $categoryId === null ? null : (string) $categoryId,
            metadata: $this->bodyValues(request: $request, keys: ['name', 'slug']),
        );
    }

    public function categoryDeleted(Request $request, int $categoryId): void
    {
        $this->describe(request: $request, action: 'category.deleted', resourceType: 'category', resourceId: (string) $categoryId);
    }

    public function tagSaved(Request $request, ?int $tagId, bool $created): void
    {
        $this->describe(
            request: $request,
            action: $created ? 'tag.created' : 'tag.updated',
            resourceType: 'tag',
            resourceId: $tagId === null ? null : (string) $tagId,
            metadata: $this->bodyValues(request: $request, keys: ['name', 'slug']),
        );
    }

    public function tagDeleted(Request $request, int $tagId): void
    {
        $this->describe(request: $request, action: 'tag.deleted', resourceType: 'tag', resourceId: (string) $tagId);
    }

    /**
     * @param list<string> $keys
     * @return array<string, string>
     */
    private function bodyValues(Request $request, array $keys): array
    {
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $request->bodyInput(key: $key);
        }

        return $values;
    }

    /**
     * @param array<string, mixed> $file
     */
    private function fileValue(array $file, string $key): string
    {
        $value = $file[$key] ?? '';

        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * @param array<string, mixed> $file
     */
    private function fileInt(array $file, string $key): int
    {
        $value = $file[$key] ?? 0;

        return is_numeric($value) ? (int) $value : 0;
    }
}
