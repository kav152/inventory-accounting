<?php
class Logger
{
    private string $logPath;

    public function __construct(string $logPath)
    {
        $this->logPath = $logPath;
        $dir = dirname($logPath);
        if ($dir !== '' && $dir !== '.' && !is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }

    public function log(string $action, string $message, array $context = []): void
    {
        $logMessage = sprintf(
            "[%s] [%s] %s %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($action),
            $message,
            json_encode($context, JSON_UNESCAPED_UNICODE)
        );

        @error_log($logMessage, 3, $this->logPath);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }
}
//namespace App\Logging;
