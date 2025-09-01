<?php declare(strict_types=1);

namespace DCarbone\Go\HTTP {

    const StatusContinue = 100; // RFC 7231, 6.2.1
    const StatusSwitchingProtocols = 101; // RFC 7231, 6.2.2
    const StatusProcessing = 102; // RFC 2518, 10.1

    const StatusOK = 200; // RFC 7231, 6.3.1
    const StatusCreated = 201; // RFC 7231, 6.3.2
    const StatusAccepted = 202; // RFC 7231, 6.3.3
    const StatusNonAuthoritativeInfo = 203; // RFC 7231, 6.3.4
    const StatusNoContent = 204; // RFC 7231, 6.3.5
    const StatusResetContent = 205; // RFC 7231, 6.3.6
    const StatusPartialContent = 206; // RFC 7233, 4.1
    const StatusMultiStatus = 207; // RFC 4918, 11.1
    const StatusAlreadyReported = 208; // RFC 5842, 7.1
    const StatusIMUsed = 226; // RFC 3229, 10.4.1

    const StatusMultipleChoices = 300; // RFC 7231, 6.4.1
    const StatusMovedPermanently = 301; // RFC 7231, 6.4.2
    const StatusFound = 302; // RFC 7231, 6.4.3
    const StatusSeeOther = 303; // RFC 7231, 6.4.4
    const StatusNotModified = 304; // RFC 7232, 4.1
    const StatusUseProxy = 305; // RFC 7231, 6.4.5

    const StatusTemporaryRedirect = 307; // RFC 7231, 6.4.7
    const StatusPermanentRedirect = 308; // RFC 7538, 3

    const StatusBadRequest = 400; // RFC 7231, 6.5.1
    const StatusUnauthorized = 401; // RFC 7235, 3.1
    const StatusPaymentRequired = 402; // RFC 7231, 6.5.2
    const StatusForbidden = 403; // RFC 7231, 6.5.3
    const StatusNotFound = 404; // RFC 7231, 6.5.4
    const StatusMethodNotAllowed = 405; // RFC 7231, 6.5.5
    const StatusNotAcceptable = 406; // RFC 7231, 6.5.6
    const StatusProxyAuthRequired = 407; // RFC 7235, 3.2
    const StatusRequestTimeout = 408; // RFC 7231, 6.5.7
    const StatusConflict = 409; // RFC 7231, 6.5.8
    const StatusGone = 410; // RFC 7231, 6.5.9
    const StatusLengthRequired = 411; // RFC 7231, 6.5.10
    const StatusPreconditionFailed = 412; // RFC 7232, 4.2
    const StatusRequestEntityTooLarge = 413; // RFC 7231, 6.5.11
    const StatusRequestURITooLong = 414; // RFC 7231, 6.5.12
    const StatusUnsupportedMediaType = 415; // RFC 7231, 6.5.13
    const StatusRequestedRangeNotSatisfiable = 416; // RFC 7233, 4.4
    const StatusExpectationFailed = 417; // RFC 7231, 6.5.14
    const StatusTeapot = 418; // RFC 7168, 2.3.3
    const StatusUnprocessableEntity = 422; // RFC 4918, 11.2
    const StatusLocked = 423; // RFC 4918, 11.3
    const StatusFailedDependency = 424; // RFC 4918, 11.4
    const StatusUpgradeRequired = 426; // RFC 7231, 6.5.15
    const StatusPreconditionRequired = 428; // RFC 6585, 3
    const StatusTooManyRequests = 429; // RFC 6585, 4
    const StatusRequestHeaderFieldsTooLarge = 431; // RFC 6585, 5
    const StatusUnavailableForLegalReasons = 451; // RFC 7725, 3

    const StatusInternalServerError = 500; // RFC 7231, 6.6.1
    const StatusNotImplemented = 501; // RFC 7231, 6.6.2
    const StatusBadGateway = 502; // RFC 7231, 6.6.3
    const StatusServiceUnavailable = 503; // RFC 7231, 6.6.4
    const StatusGatewayTimeout = 504; // RFC 7231, 6.6.5
    const StatusHTTPVersionNotSupported = 505; // RFC 7231, 6.6.6
    const StatusVariantAlsoNegotiates = 506; // RFC 2295, 8.1
    const StatusInsufficientStorage = 507; // RFC 4918, 11.5
    const StatusLoopDetected = 508; // RFC 5842, 7.2
    const StatusNotExtended = 510; // RFC 2774, 7
    const StatusNetworkAuthenticationRequired = 511; // RFC 6585, 6

    const StatusTexts = [
        StatusContinue           => 'Continue',
        StatusSwitchingProtocols => 'Switching Protocols',
        StatusProcessing         => 'Processing',


        StatusOK                   => 'OK',
        StatusCreated              => 'Created',
        StatusAccepted             => 'Accepted',
        StatusNonAuthoritativeInfo => 'Non-Authoritative Information',
        StatusNoContent            => 'No Content',
        StatusResetContent         => 'Reset Content',
        StatusPartialContent       => 'Partial Content',
        StatusMultiStatus          => 'Multi-Status',
        StatusAlreadyReported      => 'Already Reported',
        StatusIMUsed               => 'IM Used',

        StatusMultipleChoices   => 'Multiple Choices',
        StatusMovedPermanently  => 'Moved Permanently',
        StatusFound             => 'Found',
        StatusSeeOther          => 'See Other',
        StatusNotModified       => 'Not Modified',
        StatusUseProxy          => 'Use Proxy',
        StatusTemporaryRedirect => 'Temporary Redirect',
        StatusPermanentRedirect => 'Permanent Redirect',

        StatusBadRequest                   => 'Bad Request',
        StatusUnauthorized                 => 'Unauthorized',
        StatusPaymentRequired              => 'Payment Required',
        StatusForbidden                    => 'Forbidden',
        StatusNotFound                     => 'Not Found',
        StatusMethodNotAllowed             => 'Method Not Allowed',
        StatusNotAcceptable                => 'Not Acceptable',
        StatusProxyAuthRequired            => 'Proxy Authentication Required',
        StatusRequestTimeout               => 'Request Timeout',
        StatusConflict                     => 'Conflict',
        StatusGone                         => 'Gone',
        StatusLengthRequired               => 'Length Required',
        StatusPreconditionFailed           => 'Precondition Failed',
        StatusRequestEntityTooLarge        => 'Request Entity Too Large',
        StatusRequestURITooLong            => 'Request URI Too Long',
        StatusUnsupportedMediaType         => 'Unsupported Media Type',
        StatusRequestedRangeNotSatisfiable => 'Requested Range Not Satisfiable',
        StatusExpectationFailed            => 'Expectation Failed',
        StatusTeapot                       => 'I\'m a teapot',
        StatusUnprocessableEntity          => 'Unprocessable Entity',
        StatusLocked                       => 'Locked',
        StatusFailedDependency             => 'Failed Dependency',
        StatusUpgradeRequired              => 'Upgrade Required',
        StatusPreconditionRequired         => 'Precondition Required',
        StatusTooManyRequests              => 'Too Many Requests',
        StatusRequestHeaderFieldsTooLarge  => 'Request Header Fields Too Large',
        StatusUnavailableForLegalReasons   => 'Unavailable For Legal Reasons',

        StatusInternalServerError           => 'Internal Server Error',
        StatusNotImplemented                => 'Not Implemented',
        StatusBadGateway                    => 'Bad Gateway',
        StatusServiceUnavailable            => 'Service Unavailable',
        StatusGatewayTimeout                => 'Gateway Timeout',
        StatusHTTPVersionNotSupported       => 'HTTP Version Not Supported',
        StatusVariantAlsoNegotiates         => 'Variant Also Negotiates',
        StatusInsufficientStorage           => 'Insufficient Storage',
        StatusLoopDetected                  => 'Loop Detected',
        StatusNotExtended                   => 'Not Extended',
        StatusNetworkAuthenticationRequired => 'Network Authentication Required',
    ];

    const StatusNames = [
        StatusContinue           => 'Continue',
        StatusSwitchingProtocols => 'SwitchingProtocols',
        StatusProcessing         => 'Processing',

        StatusOK                   => 'OK',
        StatusCreated              => 'Created',
        StatusAccepted             => 'Accepted',
        StatusNonAuthoritativeInfo => 'NonAuthoritativeInfo',
        StatusNoContent            => 'NoContent',
        StatusResetContent         => 'ResetContent',
        StatusPartialContent       => 'PartialContent',
        StatusMultiStatus          => 'MultiStatus',
        StatusAlreadyReported      => 'AlreadyReported',
        StatusIMUsed               => 'IMUsed',

        StatusMultipleChoices  => 'MultipleChoices',
        StatusMovedPermanently => 'MovedPermanently',
        StatusFound            => 'Found',
        StatusSeeOther         => 'SeeOther',
        StatusNotModified      => 'NotModified',
        StatusUseProxy         => 'UseProxy',

        StatusTemporaryRedirect => 'TemporaryRedirect',
        StatusPermanentRedirect => 'PermanentRedirect',

        StatusBadRequest                   => 'BadRequest',
        StatusUnauthorized                 => 'Unauthorized',
        StatusPaymentRequired              => 'PaymentRequired',
        StatusForbidden                    => 'Forbidden',
        StatusNotFound                     => 'NotFound',
        StatusMethodNotAllowed             => 'MethodNotAllowed',
        StatusNotAcceptable                => 'NotAcceptable',
        StatusProxyAuthRequired            => 'ProxyAuthRequired',
        StatusRequestTimeout               => 'RequestTimeout',
        StatusConflict                     => 'Conflict',
        StatusGone                         => 'Gone',
        StatusLengthRequired               => 'LengthRequired',
        StatusPreconditionFailed           => 'PreconditionFailed',
        StatusRequestEntityTooLarge        => 'RequestEntityTooLarge',
        StatusRequestURITooLong            => 'RequestURITooLong',
        StatusUnsupportedMediaType         => 'UnsupportedMediaType',
        StatusRequestedRangeNotSatisfiable => 'RequestedRangeNotSatisfiable',
        StatusExpectationFailed            => 'ExpectationFailed',
        StatusTeapot                       => 'Teapot',
        StatusUnprocessableEntity          => 'UnprocessableEntity',
        StatusLocked                       => 'Locked',
        StatusFailedDependency             => 'FailedDependency',
        StatusUpgradeRequired              => 'UpgradeRequired',
        StatusPreconditionRequired         => 'PreconditionRequired',
        StatusTooManyRequests              => 'TooManyRequests',
        StatusRequestHeaderFieldsTooLarge  => 'RequestHeaderFilesTooLarge',
        StatusUnavailableForLegalReasons   => 'UnavailableForLegalReasons',

        StatusInternalServerError           => 'InternalServerError',
        StatusNotImplemented                => 'NotImplemented',
        StatusBadGateway                    => 'BadGateway',
        StatusServiceUnavailable            => 'ServiceUnavailable',
        StatusGatewayTimeout                => 'GatewayTimeout',
        StatusHTTPVersionNotSupported       => 'HTTPVersionNotSupported',
        StatusVariantAlsoNegotiates         => 'VariantAlsoNegotiates',
        StatusInsufficientStorage           => 'InsufficientStorage',
        StatusLoopDetected                  => 'LoopDetected',
        StatusNotExtended                   => 'NotExtended',
        StatusNetworkAuthenticationRequired => 'NetworkAuthenticationRequired',
    ];

    const MethodGet = 'GET';
    const MethodHead = 'HEAD';
    const MethodPost = 'POST';
    const MethodPut = 'PUT';
    const MethodPatch = 'PATCH';
    const MethodDelete = 'DELETE';
    const MethodConnect = 'CONNECT';
    const MethodOptions = 'OPTIONS';
    const MethodTrace = 'TRACE';

    /**
     * @param int $code
     * @return string
     */
    function StatusText(int $code): string
    {
        return StatusTexts[$code] ?? '';
    }

    /**
     * @param int $code
     * @return string
     */
    function StatusName(int $code): string
    {
        return StatusNames[$code] ?? '';
    }
}