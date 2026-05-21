<?php

declare(strict_types=1);

namespace Parvion\Msg91\Contracts;

use Parvion\Msg91\DTOs\SmsData;
use Parvion\Msg91\Exceptions\FeatureDisabledException;
use Parvion\Msg91\Exceptions\Msg91ApiException;
use Parvion\Msg91\Exceptions\Msg91RateLimitException;

/**
 * Interface SmsServiceInterface
 *
 * Defines the public contract for SMS operations.
 * Both the real Msg91 class (via ManagesSms trait) and Msg91Fake must satisfy this contract.
 *
 * @package Parvion\Msg91\Contracts
 */
interface SmsServiceInterface
{
    /**
     * Send a single SMS to one or more recipients defined in SmsData.
     *
     * @param  SmsData  $data  Strongly-typed SMS payload (recipients, message, route, sender).
     * @return array           Normalised MSG91 response array.
     *
     * @throws FeatureDisabledException  If the SMS channel is disabled in config.
     * @throws Msg91RateLimitException   If the local throttle or MSG91 rejects the request.
     * @throws Msg91ApiException         On any other API-level error.
     */
    public function sendSms(SmsData $data): array;

    /**
     * Send the same SMS template to a large list of recipients.
     *
     * Internally this builds a bulk-formatted MSG91 request body. The $recipients
     * array overrides any recipients already set inside $data.
     *
     * @param  string[]  $recipients  Array of mobile numbers in E.164 format.
     * @param  SmsData   $data        Shared SMS configuration (message, route, sender, variables).
     * @return array                  Normalised MSG91 response array.
     *
     * @throws FeatureDisabledException  If the SMS channel is disabled in config.
     * @throws Msg91RateLimitException   If the local throttle or MSG91 rejects the request.
     * @throws Msg91ApiException         On any other API-level error.
     */
    public function sendBulkSms(array $recipients, SmsData $data): array;

    /**
     * Check the delivery status of a previously sent SMS campaign.
     *
     * @param  string  $requestId  The MSG91 campaign/request ID returned from sendSms().
     * @return array               Normalised delivery status response array.
     *
     * @throws FeatureDisabledException  If the SMS channel is disabled in config.
     * @throws Msg91ApiException         On any other API-level error.
     */
    public function checkDeliveryStatus(string $requestId): array;
}
