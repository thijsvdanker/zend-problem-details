<?php

namespace ProblemDetails;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Zend\Diactoros\Response\JsonResponse;
use Throwable;

/**
 * Model a Problem Details response.
 *
 * @see https://tools.ietf.org/html/rfc7807
 */
class ProblemDetailsResponse extends JsonResponse
{
    const INCLUDE_THROWABLE_DETAILS = true;
    const EXCLUDE_THROWABLE_DETAILS = false;

    /**
     * @var string[] Default problem detail titles based on status code
     */
    private static $defaultStatusTitles = [
        // 4×× Client Error
        StatusCode::STATUS_BAD_REQUEST                        => 'Bad Request',
        StatusCode::STATUS_UNAUTHORIZED                       => 'Unauthorized',
        StatusCode::STATUS_PAYMENT_REQUIRED                   => 'Payment Required',
        StatusCode::STATUS_FORBIDDEN                          => 'Forbidden',
        StatusCode::STATUS_NOT_FOUND                          => 'Not Found',
        StatusCode::STATUS_METHOD_NOT_ALLOWED                 => 'Method Not Allowed',
        StatusCode::STATUS_NOT_ACCEPTABLE                     => 'Not Acceptable',
        StatusCode::STATUS_PROXY_AUTHENTICATION_REQUIRED      => 'Proxy Authentication Required',
        StatusCode::STATUS_REQUEST_TIMEOUT                    => 'Request Timeout',
        StatusCode::STATUS_CONFLICT                           => 'Conflict',
        StatusCode::STATUS_GONE                               => 'Gone',
        StatusCode::STATUS_LENGTH_REQUIRED                    => 'Length Required',
        StatusCode::STATUS_PRECONDITION_FAILED                => 'Precondition Failed',
        StatusCode::STATUS_PAYLOAD_TOO_LARGE                  => 'Payload Too Large',
        StatusCode::STATUS_URI_TOO_LONG                       => 'Request-URI Too Long',
        StatusCode::STATUS_UNSUPPORTED_MEDIA_TYPE             => 'Unsupported Media Type',
        StatusCode::STATUS_RANGE_NOT_SATISFIABLE              => 'Requested Range Not Satisfiable',
        StatusCode::STATUS_EXPECTATION_FAILED                 => 'Expectation Failed',
        StatusCode::STATUS_IM_A_TEAPOT                        => 'I\'m a teapot',
        StatusCode::STATUS_MISDIRECTED_REQUEST                => 'Misdirected Request',
        StatusCode::STATUS_UNPROCESSABLE_ENTITY               => 'Unprocessable Entity',
        StatusCode::STATUS_LOCKED                             => 'Locked',
        StatusCode::STATUS_FAILED_DEPENDENCY                  => 'Failed Dependency',
        StatusCode::STATUS_UPGRADE_REQUIRED                   => 'Upgrade Required',
        StatusCode::STATUS_PRECONDITION_REQUIRED              => 'Precondition Required',
        StatusCode::STATUS_TOO_MANY_REQUESTS                  => 'Too Many Requests',
        StatusCode::STATUS_REQUEST_HEADER_FIELDS_TOO_LARGE    => 'Request Header Fields Too Large',
        444                                                   => 'Connection Closed Without Response',
        StatusCode::STATUS_UNAVAILABLE_FOR_LEGAL_REASONS      => 'Unavailable For Legal Reasons',
        499                                                   => 'Client Closed Request',
        // 5×× Server Error
        StatusCode::STATUS_INTERNAL_SERVER_ERROR           => 'Internal Server Error',
        StatusCode::STATUS_NOT_IMPLEMENTED                 => 'Not Implemented',
        StatusCode::STATUS_BAD_GATEWAY                     => 'Bad Gateway',
        StatusCode::STATUS_SERVICE_UNAVAILABLE             => 'Service Unavailable',
        StatusCode::STATUS_GATEWAY_TIMEOUT                 => 'Gateway Timeout',
        StatusCode::STATUS_VERSION_NOT_SUPPORTED           => 'HTTP Version Not Supported',
        StatusCode::STATUS_VARIANT_ALSO_NEGOTIATES         => 'Variant Also Negotiates',
        StatusCode::STATUS_INSUFFICIENT_STORAGE            => 'Insufficient Storage',
        StatusCode::STATUS_LOOP_DETECTED                   => 'Loop Detected',
        StatusCode::STATUS_NOT_EXTENDED                    => 'Not Extended',
        StatusCode::STATUS_NETWORK_AUTHENTICATION_REQUIRED => 'Network Authentication Required',
        599                                                => 'Network Connect Timeout Error',
    ];

    public static function create(
        int $status,
        string $detail,
        string $title = '',
        string $type = '',
        array $additional = []
    ) : self {
        $status = self::normalizeStatus($status);
        $title  = empty($title) ? self::createTitleFromStatus($status) : $title;
        $type   = empty($type) ? self::createTypeFromStatus($status) : $type;

        $payload = [
            'title'  => $title,
            'type'   => $type,
            'status' => $status,
            'detail' => $detail,
        ];

        if ($additional) {
            $payload = array_merge($additional, $payload);
        }

        return new self($payload, $status, ['Content-Type' => 'application/problem+json']);
    }

    public static function createFromThrowable(Throwable $e, bool $includeThrowable = self::EXCLUDE_THROWABLE_DETAILS)
    {
        $additionalDetails = $includeThrowable ? self::createThrowableDetail($e) : [];
        $code = is_int($e->getCode()) ? $e->getCode() : 0;
        return self::create($code, $e->getMessage(), '', '', $additionalDetails);
    }

    private static function normalizeStatus(int $status) : int
    {
        if ($status < 100 || $status > 599) {
            return 500;
        }

        return $status;
    }

    private static function createTitleFromStatus(int $status) : string
    {
        return self::$defaultStatusTitles[$status] ?? 'Unknown Error';
    }

    private static function createTypeFromStatus(int $status) : string
    {
        return sprintf('https://httpstatus.es/%s', $status);
    }

    private static function createThrowableDetail(Throwable $e) : array
    {
        $detail = [
            'class'   => get_class($e),
            'code'    => $e->getCode(),
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => $e->getTrace(),
        ];

        $previous = [];
        while ($e = $e->getPrevious()) {
            $previous[] = [
                'class'   => get_class($e),
                'code'    => $e->getCode(),
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTrace(),
            ];
        }

        if (count($previous) > 0) {
            $detail['stack'] = $previous;
        }

        return ['exception' => $detail];
    }
}