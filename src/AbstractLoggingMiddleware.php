<?php

namespace Caxy\Drupal\Logging;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Class AbstractLoggingMiddleware.
 */
abstract class AbstractLoggingMiddleware implements HttpKernelInterface
{
    /**
     * The decorated kernel.
     *
     * @var HttpKernelInterface
     */
    protected $httpKernel;

    /**
     * @var string
     */
    protected $logLevel;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var bool
     */
    protected $logSubRequest;

    /**
     * Constructs a RequestLoggingMiddleware object.
     *
     * @param HttpKernelInterface $http_kernel
     *                                           The decorated kernel.
     * @param LoggerInterface     $logger
     * @param string              $logLevel
     * @param bool                $logSubRequest
     */
    public function __construct(HttpKernelInterface $http_kernel, LoggerInterface $logger, $logLevel = LogLevel::INFO, $logSubRequest = true)
    {
        $this->httpKernel = $http_kernel;
        $this->logLevel = $logLevel;
        $this->logger = $logger;
        $this->logSubRequest = $logSubRequest;
    }

    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        if ($type !== HttpKernelInterface::MASTER_REQUEST && false == $this->logSubRequest) {
            // Do not log SUB requests.
          return $this->httpKernel->handle($request, $type, $catch);
        }

        $this->logRequest($request);

        $response = $this->httpKernel->handle($request, $type, $catch);

        $this->logResponse($response, $request);

        return $response;
    }

    abstract protected function logRequest(Request $request);

    abstract protected function logResponse(Response $response, Request $request);
}
