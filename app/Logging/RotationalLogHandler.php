<?php

namespace App\Logging;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

class RotationalLogHandler extends RotatingFileHandler
{
    /**
     * Create a new rotational log handler instance.
     *
     * @param string $filename
     * @param int $maxFiles
     * @param int $level
     * @param bool $bubble
     * @param int|null $filePermission
     * @param bool $useLocking
     */
    public function __construct(
        string $filename,
        int $maxFiles = 30,
        int $level = Logger::DEBUG,
        bool $bubble = true,
        ?int $filePermission = null,
        bool $useLocking = false
    ) {
        // Extract directory and base filename
        $directory = dirname($filename);
        $basename = basename($filename, '.log');
        
        // Create directory if it doesn't exist
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        parent::__construct($filename, $maxFiles, $level, $bubble, $filePermission, $useLocking);
    }

    /**
     * Get the filename for the current day.
     *
     * @return string
     */
    protected function getTimedFilename(): string
    {
        $date = date('Y-m-d');
        $directory = dirname($this->filename);
        $basename = basename($this->filename, '.log');
        
        return $directory . '/' . $date . '.log';
    }
}