<?php

declare(strict_types=1);

namespace Marvin\Ask\Actions;

use Marvin\Ask\Abstracts\AudioFileTranscoder;

class FfmpegVoiceChatMessageConverter extends AudioFileTranscoder
{
    protected string $pathDirectory = 'stt';

    public function toMp3(string $base64, string $mimeType, bool $useTestFile = false): string
    {
        if ($useTestFile) {
            return $this->disk->path($this->buildPath('test', 'mp3'));
        }

        $temporaryFilename = $this->generateBaseName();
        $sourceFilePath = $this->buildPath($temporaryFilename, $this->getInputMimeTypeExtension($mimeType));
        $destinationFilePath = $this->disk->path($this->buildPath($temporaryFilename, 'mp3'));

        $this->disk->put($sourceFilePath, base64_decode($base64));

        $command = sprintf(
            'ffmpeg -y -hide_banner -loglevel error -i %s -ac 1 -ar 16000 %s',
            escapeshellarg($this->disk->path($sourceFilePath)),
            escapeshellarg($destinationFilePath)
        );
        exec($command);

        $this->disk->delete($sourceFilePath);
        return $destinationFilePath;
    }


}
