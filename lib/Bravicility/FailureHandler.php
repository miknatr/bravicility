<?php

namespace Bravicility;

// Example:
// PHP_FailureHandler::setup(function($error){
//     $message = strtr($error['message'], array("\t"=>'\t',"\r"=>'\r',"\n"=>'\n'));
//     $file    = $error['trace'][0]['file'];
//     $line    = $error['trace'][0]['line'];
//     $log = date('Y-m-d H:i:s O')."\t$message\t$file:$line\t".json_encode($error)."\n";
//     file_put_contents('errors.log', $log, FILE_APPEND);
// });

// Get the example log in human-readable format (without the JSON column):
// cut -f1-3 errors.log

class FailureHandler
{
    private static $callback;
    private static $disable_shutdown_handler = false;

    // long arrays in traces will be replaced by their count
    public static $max_array_count = 5;
    public static $keep_string_length = 100;

    /**
     * Installs a callback as a handler for all failures
     *
     * The callback will be called for every reported error, including fatal,
     * and for uncaught exceptions. After the callback default handlers will be
     * called: XDebug tables with stacks, native error_log, etc.
     * The idea is to silently log all failures while not altering script behaviour
     * in any way. The only exception to this is the uncaught exception (hur-hur),
     * which needs to be wrapped on rethrow in order to keep the call stack intact.
     *
     * This callback will receive an array with the following structure:
     * array(
     *     [exception] => Exception      // class of exception, will not be present for php errors
     *     [type] => 0                   // $e->getCode or php error type (E_NOTICE, E_WARNING, etc)
     *     [message] => Inconceivable
     *     [trace] => array(
     *         array(
     *             [file] => /path/file.php  // line/file of first trace level
     *             [line] => 179             // is the precise point where the error occured
     *             [function] => execute
     *             [class] => Foo
     *             [type] => ->
     *         ),
     *         // more trace levels
     *     ),
     *     [exception_wrapped] => array(...) // wrapper exception (if it was rethrown)
     * )
     *
     * @param callback $callback
     */
    public static function setup($callback)
    {
        static::$callback = $callback;

        $class = get_called_class();
        set_exception_handler(array($class, 'uncaught_exception'));
        set_error_handler(array($class, 'error'));
        register_shutdown_function(array($class, 'shutdown'));
    }

    private static function clean_trace_args($trace)
    {
        foreach ($trace as &$level) {
            unset($level['object']);
            if (!empty($level['args'])) {
                foreach ($level['args'] as &$arg) {
                    $arg = static::normalize_data($arg);
                }
            }
        }

        return $trace;
    }

    public static function normalize_data($arg)
    {
        if (is_array($arg)) {
            if (count($arg) > static::$max_array_count) {
                $arg = '<array ' . count($arg) . '>';
            } else {
                $tmp = array();
                foreach ($arg as $k => $v) {
                    // we don't want to go deep into the array
                    if (is_array($v) && $v !== array())
                        $v = '<array ' . count($v) . '>';
                    $tmp[static::normalize_data($k)] = static::normalize_data($v);
                }
                $arg = $tmp;
            }
        } // object => class + toString
        else if (is_object($arg)) {
            $s = '<' . get_class($arg);
            if (method_exists($arg, '__toString'))
                $s .= ' ' . static::normalize_data((string)$arg);
            $s .= '>';
            $arg = $s;
        } else if (is_string($arg)) {
            if (strlen($arg) > static::$keep_string_length)
                $arg = substr($arg, 0, static::$keep_string_length) . '... (' . strlen($arg) . ' bytes)';
        }

        return $arg;
    }

    private static function exception2error(\Exception $e, $wrapped_in = array())
    {
        $trace = static::clean_trace_args($e->getTrace());
        array_unshift($trace, array(
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ));

        $error = array(
            'exception' => get_class($e),
            'type'      => $e->getCode(),
            'message'   => $e->getMessage(),
            'trace'     => $trace,
        );
        if ($wrapped_in)
            $error['exception_wrapped'] = $wrapped_in;

        $prev = $e->getPrevious();
        if ($prev)
            return static::exception2error($prev, $error);

        return $error;
    }

    /**
     * Handle an exception
     *
     * If you catch all exceptions in your application to show a nice error page,
     * those exceptions are not going to be handled on their own.
     * This method can be used to handle those exceptions manually.
     * Default exception handler will not be called here (it's for uncaught ones).
     *
     * @param \Exception $exception
     */
    public static function handle_exception(\Exception $exception)
    {
        if (static::$callback)
            call_user_func(static::$callback, static::exception2error($exception));
    }

    public static function uncaught_exception(\Exception $exception)
    {
        call_user_func(static::$callback, static::exception2error($exception));

        // We need to disable shutdown func so it won't report 'Uncaught exception' which is a fatal error.
        // Unfortunately, there's no unregister_shutdown_function, so we emulate it.
        static::$disable_shutdown_handler = true;

        // We cannot just return false from the exception handler to run the default one.
        // Instead, we have to remove this handler and throw the exception again.
        restore_exception_handler();

        // If you throw $e, it will lose the call stack.
        throw new FailureException($exception->getMessage(), $exception->getCode(), $exception);
    }

    public static function error($type, $message)
    {
        if (error_reporting() & $type) {
            $trace = static::clean_trace_args(debug_backtrace());
            if (!isset($trace[0]['line'])) {
                // For errors originating in native functions (incorrect arguments, etc),
                // the trace[0] will not have the file/line, only FailureHandler::error() call.
                // trace[1] will have the correct point of error.
                array_shift($trace);
            } else {
                // For errors originating in expressions (undefined var, bad include, etc),
                // the trace[0] will have correct file/line of the notice,
                // but marked as FailureHandler::error() call.
                unset($trace[0]['function'], $trace[0]['class'], $trace[0]['type']);
            }
            call_user_func(static::$callback, array(
                'type'    => $type,
                'message' => $message,
                'trace'   => $trace,
            ));
        }

        return false; // continue with the default error handler (write logs, draw xdebug tables, etc)
    }

    // catching fatal errors
    public static function shutdown()
    {
        if (static::$disable_shutdown_handler)
            return;

        $error = error_get_last();
        // We should only react to fatal errors,
        // because all other errors are processed by normal error handler.
        if ($error && $error['type'] == E_ERROR) {
            // unfortunately, there's no way to get call stack for fatal errors
            $error['trace'] = array(
                array(
                    'file' => $error['file'],
                    'line' => $error['line'],
                ),
            );
            unset($error['file'], $error['line']);
            call_user_func(static::$callback, $error);
        }
    }
}
