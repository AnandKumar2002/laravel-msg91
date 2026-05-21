<?php

declare(strict_types=1);

namespace Parvion\Msg91\Http;

use Parvion\Msg91\Contracts\Msg91ClientInterface;

/**
 * Class Msg91Client
 *
 * Concrete HTTP client implementation for the MSG91 REST API.
 * Wraps Laravel's Http facade with authentication headers, base URL
 * resolution, timeout configuration, and response normalization.
 *
 * @package Parvion\Msg91\Http
 */
class Msg91Client implements Msg91ClientInterface
{
    // TODO: Phase 2 — constructor injection of config, get(), post(), buildHeaders(), handleResponse()
}
