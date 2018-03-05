<?php namespace Dimbo\Filecachegc;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Tethra\Console\LoggedCommandTrait;
use FilesystemIterator;
use Symfony\Component\Finder\Finder;

class CollectFileCacheGarbageCommand extends Command
{
    use LoggedCommandTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'cache:gc';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Collect garbage in file-based cache';

    /**
     * @param $bytes
     * @param int $decimals
     *
     * @return string
     */
    protected function formatFileSize($bytes, $decimals = 2)
    {
        $suffixes = [ 'B', 'Kb', 'Mb', 'Gb', 'Tb', 'Pb' ];

        $factor = intval((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$suffixes[$factor];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        if(\Config::get('cache.driver') !== 'file')
        {
            $this->error('File-based cache is not configured - cannot collect garbage');
            return;
        }

        $expiredFileCount = 0;
        $sizeReclaimed = 0;
        $activeFileCount = 0;
        $sizeRemaining = 0;

        $splFilesIterator = Finder::create()->files()->in(\Config::get('cache.path'));

        foreach($splFilesIterator as $key => $file)
        {
            if($file->getBasename() === '.gitignore')
            {
                continue;
            }

            // Load only the very beginning of the file
            $contents = @file_get_contents($file, false, null, 0, 16);

            if($contents !== false)
            {
                $fileSize = filesize($file);
                if($fileSize === false)
                {
                    $fileSize = 0;
                }

                // Get expiration time
                $expire = substr($contents, 0, 10);

                if(time() >= $expire)
                {
                    if( @unlink($file) )
                    {
                        $expiredFileCount++;
                        $sizeReclaimed += $fileSize;
                    }

                }
                else
                {
                    $activeFileCount++;
                    $sizeRemaining += $fileSize;
                }
            }
        }

        $this->info('Cache garbage collection results: '
            . 'files deleted: ' . $expiredFileCount
            . ', size reclaimed: ' . $this->formatFilesize($sizeReclaimed)
            . ', files remaining: ' . $activeFileCount
            . ', size remaining: ' . $this->formatFileSize($sizeRemaining));
    }
}
