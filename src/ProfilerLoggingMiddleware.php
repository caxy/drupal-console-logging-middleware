<?php

namespace Caxy\Drupal\Logging;

use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ProfilerLoggingMiddleware extends AbstractLoggingMiddleware
{
    /**
     * List of Parameters to exclude from console logging.
     *
     * @var array
     */
    public static $blacklistParameters;

    /**
     * List of Parameters to censored in console logging.
     *
     * @var array
     */
    public static $censorParameters;

    /**
     * @param array $censorParameters
     */
    public function setCensorParameters($censorParameters)
    {
        self::$censorParameters = $censorParameters;
    }

    /**
     * @param array $blacklistParameters
     */
    public function setBlacklistParameters($blacklistParameters)
    {
        self::$blacklistParameters = $blacklistParameters;
    }

    /**
     * Log an incoming request from the middleware.
     *
     * @param Request $request
     *                         The incoming request.
     * @param int     $type
     *                         The type of request (master or sub request).
     */
    protected function logRequest(Request $request, $type = HttpKernelInterface::MASTER_REQUEST)
    {
        if ($type == HttpKernelInterface::MASTER_REQUEST) {
            // Starts timers and logs.
            Database::startLog('console_logger');
            if ($request->getRealMethod() == 'POST') {
                $parameters = $request->request->all();

                $parameters = $this->sanitizeParameters($parameters);
                if (!empty($parameters)) {
                    $this->logger->log($this->logLevel, 'Request parameters', $parameters);
                }
            }
        }
    }

    /**
     * @param array $parameters
     *                          An array of parameters to be sanitized.
     *
     * @return array
     */
    private function sanitizeParameters($parameters)
    {
        array_filter($parameters, function ($name) {
            foreach (self::$blacklistParameters as $pattern) {
                if (!empty($pattern) && preg_match($pattern, $name)) {
                    return false;
                }
            }
        }, ARRAY_FILTER_USE_KEY);

        foreach ($parameters as $name => $param) {
            foreach (self::$blacklistParameters as $pattern) {
                if (!empty($pattern) && preg_match($pattern, $name)) {
                    unset($parameters[$name]);
                }
            }
        }

        foreach ($parameters as $name => $param) {
            foreach (self::$censorParameters as $pattern) {
                if (isset($parameters[$name]) && !empty($pattern) && preg_match($pattern, $name)) {
                    $parameters[$name] = '********';
                }
            }
        }

        return $parameters;
    }

    /**
     * Log the termination of a request.
     *
     * @param Response $response
     * @param Request  $request
     */
    protected function logResponse(Response $response, Request $request)
    {
        $queries = Database::getLog('console_logger', 'default');

        $sum = 0;
        if (!empty($queries)) {
            foreach ($queries as $query) {
                $text[] = $query['query'];
                $sum += $query['time'];
            }

            $querySummary = 'Executed {queries} queries in {time} ms.';

            $this->logger->log($this->logLevel, $querySummary, [
              'queries' => count($queries),
              'time' => round($sum * 1000, 2),
            ]);
        }

        if ($response->headers->has('x-debug-token-link')) {
            $this->logger->log($this->logLevel, 'Profiler at {url}', [
              'url' => $GLOBALS['base_url'].$response->headers->get('x-debug-token-link'),
            ]);
        }
    }
}
