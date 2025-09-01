<?php declare(strict_types=1);

namespace DCarbone\Go\HTTP;

/**
 * Class HTTP
 * @package DCarbone\Go\HTTP
 */
class HTTP
{
    const StatusContinue           = \DCarbone\Go\HTTP\StatusContinue;
    const StatusSwitchingProtocols = \DCarbone\Go\HTTP\StatusSwitchingProtocols;
    const StatusProcessing         = \DCarbone\Go\HTTP\StatusProcessing;

    const StatusOK                   = \DCarbone\Go\HTTP\StatusOK;
    const StatusCreated              = \DCarbone\Go\HTTP\StatusCreated;
    const StatusAccepted             = \DCarbone\Go\HTTP\StatusAccepted;
    const StatusNonAuthoritativeInfo = \DCarbone\Go\HTTP\StatusNonAuthoritativeInfo;
    const StatusNoContent            = \DCarbone\Go\HTTP\StatusNoContent;
    const StatusResetContent         = \DCarbone\Go\HTTP\StatusResetContent;
    const StatusPartialContent       = \DCarbone\Go\HTTP\StatusPartialContent;
    const StatusMultiStatus          = \DCarbone\Go\HTTP\StatusMultiStatus;
    const StatusAlreadyReported      = \DCarbone\Go\HTTP\StatusAlreadyReported;
    const StatusIMUsed               = \DCarbone\Go\HTTP\StatusIMUsed;

    const StatusMultipleChoices  = \DCarbone\Go\HTTP\StatusMultipleChoices;
    const StatusMovedPermanently = \DCarbone\Go\HTTP\StatusMovedPermanently;
    const StatusFound            = \DCarbone\Go\HTTP\StatusFound;
    const StatusSeeOther         = \DCarbone\Go\HTTP\StatusSeeOther;
    const StatusNotModified      = \DCarbone\Go\HTTP\StatusNotModified;
    const StatusUseProxy         = \DCarbone\Go\HTTP\StatusUseProxy;

    const StatusTemporaryRedirect = \DCarbone\Go\HTTP\StatusTemporaryRedirect;
    const StatusPermanentRedirect = \DCarbone\Go\HTTP\StatusPermanentRedirect;

    const StatusBadRequest                   = \DCarbone\Go\HTTP\StatusBadRequest;
    const StatusUnauthorized                 = \DCarbone\Go\HTTP\StatusUnauthorized;
    const StatusPaymentRequired              = \DCarbone\Go\HTTP\StatusPaymentRequired;
    const StatusForbidden                    = \DCarbone\Go\HTTP\StatusForbidden;
    const StatusNotFound                     = \DCarbone\Go\HTTP\StatusNotFound;
    const StatusMethodNotAllowed             = \DCarbone\Go\HTTP\StatusMethodNotAllowed;
    const StatusNotAcceptable                = \DCarbone\Go\HTTP\StatusNotAcceptable;
    const StatusProxyAuthRequired            = \DCarbone\Go\HTTP\StatusProxyAuthRequired;
    const StatusRequestTimeout               = \DCarbone\Go\HTTP\StatusRequestTimeout;
    const StatusConflict                     = \DCarbone\Go\HTTP\StatusConflict;
    const StatusGone                         = \DCarbone\Go\HTTP\StatusGone;
    const StatusLengthRequired               = \DCarbone\Go\HTTP\StatusLengthRequired;
    const StatusPreconditionFailed           = \DCarbone\Go\HTTP\StatusPreconditionFailed;
    const StatusRequestEntityTooLarge        = \DCarbone\Go\HTTP\StatusRequestEntityTooLarge;
    const StatusRequestURITooLong            = \DCarbone\Go\HTTP\StatusRequestURITooLong;
    const StatusUnsupportedMediaType         = \DCarbone\Go\HTTP\StatusUnsupportedMediaType;
    const StatusRequestedRangeNotSatisfiable = \DCarbone\Go\HTTP\StatusRequestedRangeNotSatisfiable;
    const StatusExpectationFailed            = \DCarbone\Go\HTTP\StatusExpectationFailed;
    const StatusTeapot                       = \DCarbone\Go\HTTP\StatusTeapot;
    const StatusUnprocessableEntity          = \DCarbone\Go\HTTP\StatusUnprocessableEntity;
    const StatusLocked                       = \DCarbone\Go\HTTP\StatusLocked;
    const StatusFailedDependency             = \DCarbone\Go\HTTP\StatusFailedDependency;
    const StatusUpgradeRequired              = \DCarbone\Go\HTTP\StatusUpgradeRequired;
    const StatusPreconditionRequired         = \DCarbone\Go\HTTP\StatusPreconditionRequired;
    const StatusTooManyRequests              = \DCarbone\Go\HTTP\StatusTooManyRequests;
    const StatusRequestHeaderFieldsTooLarge  = \DCarbone\Go\HTTP\StatusRequestHeaderFieldsTooLarge;
    const StatusUnavailableForLegalReasons   = \DCarbone\Go\HTTP\StatusUnavailableForLegalReasons;

    const StatusInternalServerError           = \DCarbone\Go\HTTP\StatusInternalServerError;
    const StatusNotImplemented                = \DCarbone\Go\HTTP\StatusNotImplemented;
    const StatusBadGateway                    = \DCarbone\Go\HTTP\StatusBadGateway;
    const StatusServiceUnavailable            = \DCarbone\Go\HTTP\StatusServiceUnavailable;
    const StatusGatewayTimeout                = \DCarbone\Go\HTTP\StatusGatewayTimeout;
    const StatusHTTPVersionNotSupported       = \DCarbone\Go\HTTP\StatusHTTPVersionNotSupported;
    const StatusVariantAlsoNegotiates         = \DCarbone\Go\HTTP\StatusVariantAlsoNegotiates;
    const StatusInsufficientStorage           = \DCarbone\Go\HTTP\StatusInsufficientStorage;
    const StatusLoopDetected                  = \DCarbone\Go\HTTP\StatusLoopDetected;
    const StatusNotExtended                   = \DCarbone\Go\HTTP\StatusNotExtended;
    const StatusNetworkAuthenticationRequired = \DCarbone\Go\HTTP\StatusNetworkAuthenticationRequired;

    const MethodGet     = \DCarbone\Go\HTTP\MethodGet;
    const MethodHead    = \DCarbone\Go\HTTP\MethodHead;
    const MethodPost    = \DCarbone\Go\HTTP\MethodPost;
    const MethodPut     = \DCarbone\Go\HTTP\MethodPut;
    const MethodPatch   = \DCarbone\Go\HTTP\MethodPatch;
    const MethodDelete  = \DCarbone\Go\HTTP\MethodDelete;
    const MethodConnect = \DCarbone\Go\HTTP\MethodConnect;
    const MethodOptions = \DCarbone\Go\HTTP\MethodOptions;
    const MethodTrace   = \DCarbone\Go\HTTP\MethodTrace;

    /**
     * @param int $code
     * @return string
     */
    public static function StatusText(int $code): string
    {
        return StatusText($code);
    }

    /**
     * @param int $code
     * @return string
     */
    public static function StatusName(int $code): string
    {
        return StatusName($code);
    }
}