<?php

namespace Isotopes\Profiler\Entry;

use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;

class IncomingExceptionEntry extends IncomingEntry
{
    /**
     * The underlying exception instance.
     *
     * @var Exception
     */
    public $exception;

    /**
     * IncomingExceptionEntry constructor.
     *
     * @param Exception $exception
     * @param array $content
     * @return void
     */
    public function __construct($exception, array $content)
    {
        $this->exception = $exception;

        parent::__construct($content);
    }

    /**
    * Determine if the incoming entry is a reportable exception.
    *
    * @return bool
    */
    public function isReportableException(): bool
    {
        $handler = app(ExceptionHandler::class);

        return method_exists($handler, 'shouldReport')
            ? $handler->shouldReport($this->exception) : true;
    }

    /**
     * Determine if the incoming entry is an exception.
     *
     * @return bool
     */
    public function isException(): bool
    {
        return true;
    }
}
