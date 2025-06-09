<?php

declare(strict_types=1);

namespace App\Traits;

use Exception;

trait VideoDownloadMessageTrait
{
    private static function echoConnectedMessage(string $url): void
    {
        echo "[{$url}] ✅ Connected, receiving data...\n";
    }

    private static function echoAnErrorOccurredMessage(string $url, Exception $e): void
    {
        echo "[{$url}] ❌  An error occurred: {$e->getMessage()}.\n";
    }

    private static function echoAttemptingDownloadMessage(string $url, int $downloadedBytes): void
    {
        echo "[{$url}] 🟡 Attempting download: from byte {$downloadedBytes}\n";
    }

    private static function echoDownloadCompleteMessage(string $url): void
    {
        echo "[{$url}] ✅ Download complete.\n";
    }
}
