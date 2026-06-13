<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Media\Store;

use App\Cms\Application\Media\Upload\Input;
use App\Cms\Application\Media\Upload\Interactor;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Csrf;
use App\Framework\Security\Session;

final readonly class Controller
{
    public function __construct(
        private Session $session,
        private Csrf $csrf,
        private Interactor $upload,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        if ($this->exceedsPostMaxSize(request: $request)) {
            $this->session->flash(key: 'error', value: 'アップロード全体のサイズが大きすぎます。画像は20MB以内、動画は512MB以内にしてください。');
            return Response::redirect(location: '/admin/media');
        }
        if (!$this->csrf->validate(token: $request->input(key: '_csrf'))) {
            return new Response(body: 'Invalid CSRF token', status: 422);
        }

        $file = $request->files['media_file'] ?? [];
        if (!is_array($file)) {
            $file = [];
        }
        /** @var array<string, mixed> $file */

        $output = $this->upload->execute(input: new Input(
            file: $file,
            altText: $request->input(key: 'alt_text'),
            caption: $request->input(key: 'caption'),
        ));

        if (!$output->uploaded) {
            $this->session->flash(key: 'error', value: $output->error ?? 'メディアアップロードに失敗しました。');
            return Response::redirect(location: '/admin/media');
        }

        $this->session->flash(key: 'success', value: 'メディアをアップロードしました');
        return Response::redirect(location: '/admin/media');
    }

    private function exceedsPostMaxSize(Request $request): bool
    {
        $contentLength = (int) ($request->server['CONTENT_LENGTH'] ?? '0');
        $postMaxSize = $this->iniSizeToBytes(value: (string) ini_get('post_max_size'));

        return $postMaxSize > 0 && $contentLength > $postMaxSize;
    }

    private function iniSizeToBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $unit = strtolower($value[strlen($value) - 1]);
        $number = (float) $value;

        return (int) match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
