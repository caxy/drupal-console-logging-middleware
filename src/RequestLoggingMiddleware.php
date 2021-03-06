<?php

namespace Caxy\Drupal\Logging;

use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RequestLoggingMiddleware extends AbstractLoggingMiddleware
{
    protected function logRequest(Request $request)
    {
        $msg = 'Request "{request_method} {request_uri}"';

        $map = array(
          'request_method' => $request->getMethod(),
          'request_uri' => $request->getRequestUri(),
          'request_host' => $request->getHost(),
          'request_port' => $request->getPort(),
          'request_scheme' => $request->getScheme(),
          'request_client_ip' => $request->getClientIp(),
          'request_content_type' => $request->getContentType(),
          'request_acceptable_content_types' => $request->getAcceptableContentTypes(),
          'request_etags' => $request->getETags(),
          'request_charsets' => $request->getCharsets(),
          'request_languages' => $request->getLanguages(),
          'request_locale' => $request->getLocale(),
          'request_auth_user' => $request->getUser(),
          'request_auth_has_password' => !is_null($request->getPassword()),
        );
        // Attributes from newer versions.
        if (method_exists($request, 'getEncodings')) {
            $map['request_encodings'] = $request->getEncodings();
        }
        if (method_exists($request, 'getClientIps')) {
            $map['request_client_ips'] = $request->getClientIps();
        }

        $this->logger->log($this->logLevel, $msg, $map);
    }

    protected function logResponse(Response $response, Request $request)
    {
        if ($response->getStatusCode() >= 500) {
            $color = LogLevel::ERROR;
        } elseif ($response->getStatusCode() >= 400) {
            $color = LogLevel::WARNING;
        } elseif ($response->getStatusCode() >= 300) {
            $color = LogLevel::NOTICE;
        } elseif ($response->getStatusCode() >= 200) {
            $color = LogLevel::INFO;
        } else {
            $color = LogLevel::INFO;
        }

        $msg = 'Response {response_status_code} for "{request_method} {request_uri}"';

        $context = array(
          'request_method' => $request->getMethod(),
          'request_uri' => $request->getRequestUri(),
          'response_status_code' => $response->getStatusCode(),
          'response_charset' => $response->getCharset(),
          'response_date' => $response->getDate(),
          'response_etag' => $response->getEtag(),
          'response_expires' => $response->getExpires(),
          'response_last_modified' => $response->getLastModified(),
          'response_max_age' => $response->getMaxAge(),
          'response_protocol_version' => $response->getProtocolVersion(),
          'response_ttl' => $response->getTtl(),
          'response_vary' => $response->getVary(),
        );

        $this->logger->log($color, $msg, $context);
    }
}
