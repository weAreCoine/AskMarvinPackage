<?php

declare(strict_types=1);

namespace Marvin\Ask\Contracts;

interface AudioFileTranscoderContract
{
    /**
     * Converts a base64-encoded audio file to MP3 format.
     *
     * @param string $base64 The base64-encoded audio data.
     * @param string $mimeType The MIME type of the source audio file.
     * @param bool $useTestFile Optional flag to determine whether to use a test file instead of the provided data.
     * @return string The base64-encoded MP3 file data.
     */
    public function toMp3(string $base64, string $mimeType, bool $useTestFile = false): string;
}
