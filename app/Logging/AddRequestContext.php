<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Auth;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\Handler\ProcessableHandlerInterface;
use Monolog\LogRecord;

class AddRequestContext
{
    public function __invoke(Logger $logger): void
    {
        foreach ($logger->getHandlers() as $handler) {
            if ($handler instanceof FormattableHandlerInterface) {
                $handler->setFormatter(new JsonFormatter);
            }

            if ($handler instanceof ProcessableHandlerInterface) {
                $handler->pushProcessor(function (LogRecord $record): LogRecord {
                    $record->extra['request_id'] = app()->bound('request_id')
                        ? app('request_id')
                        : 'cli';

                    $record->extra['user_id'] = Auth::check()
                        ? Auth::id()
                        : null;

                    $record->extra['ip'] = request()->ip() ?? 'cli';

                    $record->extra['env'] = config('app.env');

                    return $record;
                });
            }
        }
    }
}
