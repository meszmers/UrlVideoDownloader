<?php

declare(strict_types=1);

namespace App\Command;

use App\Services\VideoDownloadService;
use Exception;
use React\EventLoop\Loop;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:video-downloader',
    description: 'Command to download videos from URL\'s',
)]
class VideoDownloaderCommand extends Command
{
    private array $videoUrls = [
        'https://storage.googleapis.com/public_test_access_ae/output_20sec.mp4',
        'https://storage.googleapis.com/public_test_access_ae/output_30sec.mp4',
        'https://storage.googleapis.com/public_test_access_ae/output_40sec.mp4',
        'https://storage.googleapis.com/public_test_access_ae/output_50sec.mp4',
        'https://storage.googleapis.com/public_test_access_ae/output_60sec.mp4',
    ];

    public function __construct(
        private readonly VideoDownloadService $videoDownloaderService,
    )
    {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $loop = Loop::get();

        $this->videoDownloaderService->setLoop($loop);

        foreach ($this->videoUrls as $url) {
            $this->videoDownloaderService->downloadFromUrl($url);
        }

        $loop->run();

        return Command::SUCCESS;
    }
}
