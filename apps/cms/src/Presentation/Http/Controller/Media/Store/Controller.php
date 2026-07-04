<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Media\Store;

use App\Cms\Application\Media\Upload\Input;
use App\Cms\Application\Media\Upload\Interactor;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;

final readonly class Controller
{
    private const BYTES_PER_KILOBYTE = 1024;

    public function __construct(
        private Session $session,
        private Interactor $upload,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        if ($this->exceedsPostMaxSize($request)) {
            $this->session->flash(key: 'error', value: 'アップロード全体のサイズが大きすぎます。画像は20MB以内、動画は512MB以内にしてください。');
            return Response::redirect('/admin/media');
        }
        $file = $request->files['media_file'] ?? [];
        if (!is_array($file)) {
            $file = [];
        }
        /** @var array<string, mixed> $file */

        $output = $this->upload->execute(new Input(
            file: $file,
            altText: $request->input(key: 'alt_text'),
            caption: $request->input(key: 'caption'),
        ));

        if (!$output->uploaded) {
            $this->session->flash(key: 'error', value: $output->error ?? 'メディアアップロードに失敗しました。');
            return Response::redirect('/admin/media');
        }

        $this->session->flash(key: 'success', value: 'メディアをアップロードしました');
        return Response::redirect('/admin/media');
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
            'g' => $number * self::BYTES_PER_KILOBYTE * self::BYTES_PER_KILOBYTE * self::BYTES_PER_KILOBYTE,
            'm' => $number * self::BYTES_PER_KILOBYTE * self::BYTES_PER_KILOBYTE,
            'k' => $number * self::BYTES_PER_KILOBYTE,
            default => $number,
        };
    }
}
