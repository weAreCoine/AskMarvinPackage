<?php

declare(strict_types=1);

namespace Marvin\Ask\Abstracts;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Marvin\Ask\Contracts\AudioFileTranscoderContract;
use Ramsey\Collection\Exception\InvalidPropertyOrMethod;

abstract class AudioFileTranscoder implements AudioFileTranscoderContract
{
    protected string $diskName = 'audio';

    protected string $pathDirectory = '';

    protected Filesystem $disk;

    /**
     * @throws InvalidPropertyOrMethod
     */
    public function __construct()
    {
        if (empty($this->diskName)) {
            throw new InvalidPropertyOrMethod('Disk name is empty');
        }
        $this->disk = Storage::disk($this->diskName);
    }

    abstract public function toMp3(string $base64, string $mimeType, bool $useTestFile = false): string;

    protected function generateBaseName(): string
    {
        return Str::uuid()->toString();
    }

    protected function buildPath(string $filename, string $extension): string
    {
        $filename = $this->buildFilename($filename, $extension);
        if (empty($this->pathDirectory)) {
            return $filename;
        }

        return sprintf('%s/%s', $this->pathDirectory, $filename);
    }

    protected function buildFilename(string $filename, string $extension): string
    {
        return sprintf('%s.%s', $filename, $extension);
    }

    protected function getInputMimeTypeExtension(string $mimeType): string
    {
        return match ($mimeType) {
            'audio/mp4' => 'm4a',   // AAC
            'audio/mpeg', 'audio/mp3' => 'mp3',
            'audio/ogg' => 'ogg',
            'audio/wav', 'audio/x-wav' => 'wav',
            'audio/flac' => 'flac',
            'audio/webm' => 'webm',
            default => 'dat',
        };
    }
}
