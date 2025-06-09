<?php

declare(strict_types=1);

namespace App\Services;

use App\Traits\VideoDownloadMessageTrait;
use Exception;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\HttpClient\Client;
use React\HttpClient\Request;
use React\HttpClient\Response;
use React\Stream\WritableResourceStream;

class VideoDownloadService
{
    use VideoDownloadMessageTrait;

    private const RETRY_TIMEOUT_INTERVAL = 5;
    private const TEMP_LOCATION = 'storage/files/temp/';
    private const COMPLETED_LOCATION = 'storage/files/completed/';

    private Client $client;
    private ?LoopInterface $loop = null;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function setLoop(LoopInterface $loop): void
    {
        $this->loop = $loop;
        $this->client = new Client($loop);
    }

    /**
     * @throws Exception
     */
    public function downloadFromUrl(string $url): void
    {
        if (!$this->loop) {
            throw new Exception("Loop is not configured");
        }

        $fileName = self::getGeneratedFileName($url);
        $tempFileLocation = self::getTempFileLocation($fileName);
        $completedFileLocation = self::getCompletedFileLocation($fileName);

        $this->attemptDownload($url, $tempFileLocation, $completedFileLocation, 0);
    }

    private function attemptDownload(
        string $url,
        string $tempFileLocation,
        string $finalFileLocation,
        int $downloadedBytes,
    ): void {
        self::echoAttemptingDownloadMessage($url, $downloadedBytes);

        $request = $this->getVideoDownloadRequest($url, $downloadedBytes);

        $timeoutTimer = null;

        $request->on('response', function (Response $response) use (
            $url,
            $tempFileLocation,
            $finalFileLocation,
            &$timeoutTimer,
            $request
        ) {
            self::echoConnectedMessage($url);

            $stream = fopen($tempFileLocation, 'a');
            $destination = new WritableResourceStream($stream, $this->loop);

            $response->on('data', function ($chunk) use (
                $destination,
                &$timeoutTimer,
                $url,
                $tempFileLocation,
                $finalFileLocation,
                $request
            ) {
                $destination->write($chunk);

                if ($timeoutTimer !== null) {
                    $this->loop->cancelTimer($timeoutTimer);
                }

                $timeoutTimer = $this->loop->addTimer(self::RETRY_TIMEOUT_INTERVAL, function () use (
                    $url,
                    $tempFileLocation,
                    $finalFileLocation,
                    $request
                ) {
                    $request->close();
                    $this->retryDownload($url, $tempFileLocation, $finalFileLocation);
                });
            });

            $response->on('end', function () use (
                $destination,
                $tempFileLocation,
                $finalFileLocation,
                $url,
                &$timeoutTimer
            ) {
                $destination->end();

                if ($timeoutTimer !== null) {
                    $this->loop->cancelTimer($timeoutTimer);
                    $timeoutTimer = null;
                }

                rename($tempFileLocation, $finalFileLocation);

                self::echoDownloadCompleteMessage($url);
            });
        });

        $request->on('error', function (Exception $e) use (
            $url,
            $tempFileLocation,
            $finalFileLocation,
            &$timeoutTimer
        ) {
            $this->logger->error($e->getMessage());

            if ($timeoutTimer !== null) {
                $this->loop->cancelTimer($timeoutTimer);
            }

            self::echoAnErrorOccurredMessage($url, $e);

            $this->loop->addTimer(self::RETRY_TIMEOUT_INTERVAL, function () use (
                $url,
                $tempFileLocation,
                $finalFileLocation
            ) {
                $this->retryDownload($url, $tempFileLocation, $finalFileLocation);
            });
        });

        $request->end();
    }

    private function retryDownload($url, $tempFileLocation, $finalFileLocation): void
    {
        $downloadedBytes = file_exists($tempFileLocation) ? filesize($tempFileLocation) : 0;

        $this->attemptDownload($url, $tempFileLocation, $finalFileLocation, $downloadedBytes);
    }

    private function getVideoDownloadRequest(string $url, int $downloadedBytes): Request
    {
        return $this->client->request('GET', $url, [
            'Range' => "bytes={$downloadedBytes}-"
        ]);
    }

    private static function getTempFileLocation(string $fileName): string
    {
        return self::TEMP_LOCATION . $fileName . '.part';
    }

    private static function getCompletedFileLocation(string $fileName): string
    {
        return self::COMPLETED_LOCATION . $fileName;
    }

    private static function getGeneratedFileName(string $url): string
    {
        return uniqid('file_') . self::getUrlFileExtension($url);
    }

    private static function getUrlFileExtension(string $url): string
    {
        return '.' . pathinfo($url, PATHINFO_EXTENSION);
    }
}
