<?php

namespace codename\core\test;

use Exception;

/**
 * Class that contains some helpers for test executions
 */
class helper
{
    /**
     * synchronously waits for a host to be available
     *
     * @param string $host [host to connect to]
     * @param int $port [port to connect on]
     * @param int $connectionTimeout [the connection timeout per try]
     * @param int $waitBetweenRetries [wait time in seconds after a try failed]
     * @param int $tryCount [overall count of tries to connect (>0)]
     * @return bool                       [whether waiting was successful]
     * @throws Exception
     */
    public static function waitForIt(string $host, int $port, int $connectionTimeout, int $waitBetweenRetries, int $tryCount): bool
    {
        if ($tryCount < 1) {
            throw new Exception('Invalid tryCount');
        }
        for ($i = 0; $i < $tryCount; $i++) {
            try {
                $ret = (@fsockopen($host, $port, $error_code, $error_message, $connectionTimeout) !== false);
                if ($ret) {
                    return true;
                }
            } catch (Exception) {
                // NOTE: simply swallow exception
            }
            sleep($waitBetweenRetries);
        }
        return false;
    }
}
