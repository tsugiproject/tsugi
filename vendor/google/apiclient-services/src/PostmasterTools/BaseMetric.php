<?php
/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

namespace Google\Service\PostmasterTools;

class BaseMetric extends \Google\Model
{
  /**
   * Unspecified standard metric. This value should not be used directly.
   */
  public const STANDARD_METRIC_STANDARD_METRIC_UNSPECIFIED = 'STANDARD_METRIC_UNSPECIFIED';
  /**
   * Predefined metric for Feedback Loop (FBL) id. The `filter` field supports
   * selecting the aggregation key type. Supported format:
   * `aggregation_key_type` = "". Supported values: * `FROM_HEADER`: (Default)
   * The metric includes messages with From: header domain matching the
   * requested domain. * `ALL_DKIM`: The metric includes messages with one of
   * the signed DKIM domains matching the requested domain.
   */
  public const STANDARD_METRIC_FEEDBACK_LOOP_ID = 'FEEDBACK_LOOP_ID';
  /**
   * Predefined metric for Feedback Loop (FBL) spam rate. The `filter` field
   * requires a `feedback_loop_id` and optionally accepts an
   * `aggregation_key_type`. Supported formats are: * `feedback_loop_id` = "" *
   * `feedback_loop_id` = "" AND `aggregation_key_type` = "" If
   * `aggregation_key_type` is omitted, it defaults to `FROM_HEADER`. Supported
   * values: * `FROM_HEADER`: (Default) The metric includes messages with From:
   * header domain matching the requested domain. * `ALL_DKIM`: The metric
   * includes messages with one of the signed DKIM domains matching the
   * requested domain.
   */
  public const STANDARD_METRIC_FEEDBACK_LOOP_SPAM_RATE = 'FEEDBACK_LOOP_SPAM_RATE';
  /**
   * Predefined metric for spam rate.
   */
  public const STANDARD_METRIC_SPAM_RATE = 'SPAM_RATE';
  /**
   * The success rate of authentication mechanisms (DKIM, SPF, DMARC). Filter
   * must be of type auth_type = "" where is one of: [spf, dkim, dmarc]
   */
  public const STANDARD_METRIC_AUTH_SUCCESS_RATE = 'AUTH_SUCCESS_RATE';
  /**
   * The rate of messages that were TLS encrypted in transit Filter must be of
   * type traffic_direction = "" where is one of: [inbound, outbound]
   */
  public const STANDARD_METRIC_TLS_ENCRYPTION_MESSAGE_COUNT = 'TLS_ENCRYPTION_MESSAGE_COUNT';
  /**
   * The rate of messages that were TLS encrypted in transit Filter must be of
   * type traffic_direction = "" where is one of: [inbound, outbound]
   */
  public const STANDARD_METRIC_TLS_ENCRYPTION_RATE = 'TLS_ENCRYPTION_RATE';
  /**
   * The total count of delivery errors encountered (temporary or permanent
   * rejects). The `filter` field supports a limited syntax. Supported formats
   * are: * Empty: No filter is applied. * `error_type` = "" * `error_type` = ""
   * AND `error_reason` = "" If an empty filter is provided, the metric will be
   * aggregated across all error types and reasons. If only `error_type` is
   * specified, the metric will be aggregated across all reasons for that type.
   * Supported values: * reject * temp_fail Supported values depend on the : *
   * For 'reject': [bad_attachment, bad_or_missing_ptr_record, ip_in_rbls,
   * low_domain_reputation, low_ip_reputation, spammy_content,
   * stamp_policy_error, other] * For 'temp_fail': [anomalous_traffic_pattern,
   * other]
   */
  public const STANDARD_METRIC_DELIVERY_ERROR_COUNT = 'DELIVERY_ERROR_COUNT';
  /**
   * Delivery error rate for the specified delivery error type. The `filter`
   * field supports a limited syntax. Supported formats are: * Empty: No filter
   * is applied. * `error_type` = "" * `error_type` = "" AND `error_reason` = ""
   * If an empty filter is provided, the metric will be aggregated across all
   * error types and reasons. If only `error_type` is specified, the metric will
   * be aggregated across all reasons for that type. Supported values: * reject
   * * temp_fail Supported values depend on the : * For 'reject':
   * [bad_attachment, bad_or_missing_ptr_record, ip_in_rbls,
   * low_domain_reputation, low_ip_reputation, spammy_content,
   * stamp_policy_error, other] * For 'temp_fail': [anomalous_traffic_pattern,
   * other]
   */
  public const STANDARD_METRIC_DELIVERY_ERROR_RATE = 'DELIVERY_ERROR_RATE';
  /**
   * A predefined standard metric.
   *
   * @var string
   */
  public $standardMetric;

  /**
   * A predefined standard metric.
   *
   * Accepted values: STANDARD_METRIC_UNSPECIFIED, FEEDBACK_LOOP_ID,
   * FEEDBACK_LOOP_SPAM_RATE, SPAM_RATE, AUTH_SUCCESS_RATE,
   * TLS_ENCRYPTION_MESSAGE_COUNT, TLS_ENCRYPTION_RATE, DELIVERY_ERROR_COUNT,
   * DELIVERY_ERROR_RATE
   *
   * @param self::STANDARD_METRIC_* $standardMetric
   */
  public function setStandardMetric($standardMetric)
  {
    $this->standardMetric = $standardMetric;
  }
  /**
   * @return self::STANDARD_METRIC_*
   */
  public function getStandardMetric()
  {
    return $this->standardMetric;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(BaseMetric::class, 'Google_Service_PostmasterTools_BaseMetric');
