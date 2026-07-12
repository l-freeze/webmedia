<?php

declare(strict_types=1);

namespace App\Cms\Application\Article;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final readonly class PublicationDateTime
{
    private const FORMAT = 'Y-m-d H:i:s';
    private const EDITOR_TIME_ZONE = 'Asia/Tokyo';
    private const STORAGE_TIME_ZONE = 'UTC';

    public static function isValidEditorValue(string $value): bool
    {
        if ($value === '') {
            return true;
        }

        $dateTime = DateTimeImmutable::createFromFormat(
            format: '!' . self::FORMAT,
            datetime: $value,
            timezone: new DateTimeZone(self::EDITOR_TIME_ZONE),
        );

        return $dateTime !== false && $dateTime->format(self::FORMAT) === $value;
    }

    public function toStorage(string $editorValue): string
    {
        $dateTime = DateTimeImmutable::createFromFormat(
            format: '!' . self::FORMAT,
            datetime: $editorValue,
            timezone: new DateTimeZone(self::EDITOR_TIME_ZONE),
        );
        if ($dateTime === false || $dateTime->format(self::FORMAT) !== $editorValue) {
            throw new InvalidArgumentException('Invalid publication datetime.');
        }

        return $dateTime
            ->setTimezone(new DateTimeZone(self::STORAGE_TIME_ZONE))
            ->format(self::FORMAT);
    }

    public function toEditor(string $storageValue): string
    {
        if ($storageValue === '') {
            return '';
        }

        $dateTime = DateTimeImmutable::createFromFormat(
            format: '!' . self::FORMAT,
            datetime: $storageValue,
            timezone: new DateTimeZone(self::STORAGE_TIME_ZONE),
        );
        if ($dateTime === false || $dateTime->format(self::FORMAT) !== $storageValue) {
            throw new InvalidArgumentException('Invalid stored publication datetime.');
        }

        return $dateTime
            ->setTimezone(new DateTimeZone(self::EDITOR_TIME_ZONE))
            ->format(self::FORMAT);
    }
}
