<?php

declare(strict_types=1);

namespace App\Framework\Runtime;

use App\Framework\Http\Request;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

final class RoadRunnerRequestMapper
{
    /** @var list<string> */
    private array $temporaryFiles = [];

    public function map(ServerRequestInterface $request): Request
    {
        $uri = $request->getUri();
        $path = $uri->getPath() !== '' ? $uri->getPath() : '/';
        $server = Request::stringMap(values: $request->getServerParams());
        foreach ($request->getHeaders() as $name => $values) {
            $serverName = strtoupper(str_replace('-', '_', $name));
            $server[$serverName === 'CONTENT_TYPE' || $serverName === 'CONTENT_LENGTH' ? $serverName : 'HTTP_' . $serverName] = implode(', ', $values);
        }

        $parsedBody = $request->getParsedBody();

        return new Request(
            method: strtoupper($request->getMethod()),
            path: $path,
            query: Request::stringMap(values: $request->getQueryParams()),
            body: is_array($parsedBody) ? Request::mixedMap(values: $parsedBody) : [],
            server: $server,
            files: $this->mapUploadedFiles(files: $request->getUploadedFiles()),
        );
    }

    public function cleanup(): void
    {
        foreach ($this->temporaryFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        $this->temporaryFiles = [];
    }

    /**
     * @param array<array-key, mixed> $files
     * @return array<string, mixed>
     */
    private function mapUploadedFiles(array $files): array
    {
        $mapped = [];
        foreach ($files as $key => $file) {
            if (!is_string($key)) {
                continue;
            }

            if ($file instanceof UploadedFileInterface) {
                $mapped[$key] = $this->mapUploadedFile(file: $file);
                continue;
            }

            if (is_array($file)) {
                $mapped[$key] = $this->mapUploadedFiles(files: $file);
            }
        }

        return $mapped;
    }

    /** @return array{name:string,type:string,tmp_name:string,error:int,size:int} */
    private function mapUploadedFile(UploadedFileInterface $file): array
    {
        $error = $file->getError();
        $tmpName = '';
        if ($error === UPLOAD_ERR_OK) {
            $tmpName = tempnam(sys_get_temp_dir(), 'framework-rr-upload-');
            if ($tmpName === false) {
                throw new RuntimeException('Failed to create a temporary upload file.');
            }
            $this->temporaryFiles[] = $tmpName;
            file_put_contents($tmpName, (string) $file->getStream());
        }

        return [
            'name' => $file->getClientFilename() ?? '',
            'type' => $file->getClientMediaType() ?? '',
            'tmp_name' => $tmpName,
            'error' => $error,
            'size' => $file->getSize() ?? 0,
        ];
    }
}
