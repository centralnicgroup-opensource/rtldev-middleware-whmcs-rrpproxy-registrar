<?php

namespace WHMCS\Module\Registrar\RRPproxy;

use CNIC\LoggerInterface;
use CNIC\ResponseInterface;

class Logger implements LoggerInterface
{
    /**
     * output/log given data
     */
    public function log(string $post, ResponseInterface $r, string $error = null): void
    {
        if (!function_exists("logModuleCall")) {
            return;
        }

        $cmd = $r->getCommand();
        $action = $cmd["COMMAND"];
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT);
        do {
            $t = array_shift($trace);
            if ($t !== null && preg_match("/^keysystems_(.+)$/i", $t["function"], $m) && $m[1] !== "call") {
                $action = $m[1];
            }
        } while (!empty($trace));

        logModuleCall(
            "keysystems",
            $action,
            $r->getCommandPlain() . "\n" . $post,
            ($error ? $error . "\n\n" : "") . $r->getPlain()
        );
    }
}
