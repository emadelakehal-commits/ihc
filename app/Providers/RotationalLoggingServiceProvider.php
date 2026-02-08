<?php

namespace App\Providers;

use App\Logging\RotationalLogHandler;
use Illuminate\Log\LogManager;
use Illuminate\Support\ServiceProvider;
use Monolog\Logger;

class RotationalLoggingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Register the custom rotational logging channel
        $this->app->resolving('log', function (LogManager $logManager) {
            $logManager->extend('rotational', function ($app, array $config) {
                $level = $app->make('config')->get('logging.level', 'debug');
                $maxFiles = $app->make('config')->get('logging.channels.rotational.days', 30);
                
                // Create the handler with the base filename
                $handler = new RotationalLogHandler(
                    storage_path('logs/rotational.log'),
                    $maxFiles,
                    $this->levelToMonologConstant($level)
                );

                return new Logger('rotational', [$handler]);
            });
        });
    }

    /**
     * Convert Laravel log level to Monolog constant.
     *
     * @param string $level
     * @return int
     */
    protected function levelToMonologConstant(string $level): int
    {
        switch (strtolower($level)) {
            case 'debug':
                return Logger::DEBUG;
            case 'info':
                return Logger::INFO;
            case 'notice':
                return Logger::NOTICE;
            case 'warning':
                return Logger::WARNING;
            case 'error':
                return Logger::ERROR;
            case 'critical':
                return Logger::CRITICAL;
            case 'alert':
                return Logger::ALERT;
            case 'emergency':
                return Logger::EMERGENCY;
            default:
                return Logger::DEBUG;
        }
    }
}