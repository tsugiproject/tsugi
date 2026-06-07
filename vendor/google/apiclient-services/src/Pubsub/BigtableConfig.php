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

namespace Google\Service\Pubsub;

class BigtableConfig extends \Google\Model
{
  /**
   * Default value. This value is unused.
   */
  public const STATE_STATE_UNSPECIFIED = 'STATE_UNSPECIFIED';
  /**
   * The subscription can actively send messages to Bigtable.
   */
  public const STATE_ACTIVE = 'ACTIVE';
  /**
   * Unused in the current implementation. Placeholder for future use.
   */
  public const STATE_NOT_FOUND = 'NOT_FOUND';
  /**
   * Unused in the current implementation. Placeholder for future use.
   */
  public const STATE_APP_PROFILE_MISCONFIGURED = 'APP_PROFILE_MISCONFIGURED';
  /**
   * Cannot write to Bigtable because of permission denied errors. This can
   * happen if: - The Bigtable instance, table, or app profile does not exist. -
   * The Pub/Sub service agent has not been granted the [appropriate Bigtable
   * IAM permission bigtable.tables.mutateRows]({$universe.dns_names.final_docum
   * entation_domain}/bigtable/docs/access-control#permissions) - The
   * bigtable.googleapis.com API is not enabled for the project
   * ([instructions]({$universe.dns_names.final_documentation_domain}/service-
   * usage/docs/enable-disable))
   */
  public const STATE_PERMISSION_DENIED = 'PERMISSION_DENIED';
  /**
   * Cannot write to Bigtable because of a missing column family ("data"), or if
   * there is no structured row key for the subscription name + message ID, if
   * because the app profile is not configured for single-cluster routing.
   */
  public const STATE_SCHEMA_MISMATCH = 'SCHEMA_MISMATCH';
  /**
   * Cannot write to the destination because enforce_in_transit is set to true
   * and the destination locations are not in the allowed regions.
   */
  public const STATE_IN_TRANSIT_LOCATION_RESTRICTION = 'IN_TRANSIT_LOCATION_RESTRICTION';
  /**
   * Cannot write to Bigtable because the table is not in the same location as
   * where Vertex AI models used in `message_transform`s are deployed.
   */
  public const STATE_VERTEX_AI_LOCATION_RESTRICTION = 'VERTEX_AI_LOCATION_RESTRICTION';
  /**
   * Optional. The app profile to use for the Bigtable writes. If not specified,
   * the "default" application profile will be used. The app profile must use
   * single-cluster routing.
   *
   * @var string
   */
  public $appProfileId;
  /**
   * Optional. The service account to use to write to Bigtable. The subscription
   * creator or updater that specifies this field must have
   * `iam.serviceAccounts.actAs` permission on the service account. If not
   * specified, the Pub/Sub [service
   * agent](https://cloud.google.com/iam/docs/service-agents),
   * service-{project_number}@gcp-sa-pubsub.iam.gserviceaccount.com, is used.
   *
   * @var string
   */
  public $serviceAccountEmail;
  /**
   * Output only. An output-only field that indicates whether or not the
   * subscription can receive messages.
   *
   * @var string
   */
  public $state;
  /**
   * Optional. The unique name of the table to write messages to. Values are of
   * the form `projects//instances//tables/`.
   *
   * @var string
   */
  public $table;
  /**
   * Optional. When true, write the subscription name, message_id, publish_time,
   * attributes, and ordering_key to additional columns in the table under the
   * pubsub_metadata column family. The subscription name, message_id, and
   * publish_time fields are put in their own columns while all other message
   * properties (other than data) are written to a JSON object in the attributes
   * column.
   *
   * @var bool
   */
  public $writeMetadata;

  /**
   * Optional. The app profile to use for the Bigtable writes. If not specified,
   * the "default" application profile will be used. The app profile must use
   * single-cluster routing.
   *
   * @param string $appProfileId
   */
  public function setAppProfileId($appProfileId)
  {
    $this->appProfileId = $appProfileId;
  }
  /**
   * @return string
   */
  public function getAppProfileId()
  {
    return $this->appProfileId;
  }
  /**
   * Optional. The service account to use to write to Bigtable. The subscription
   * creator or updater that specifies this field must have
   * `iam.serviceAccounts.actAs` permission on the service account. If not
   * specified, the Pub/Sub [service
   * agent](https://cloud.google.com/iam/docs/service-agents),
   * service-{project_number}@gcp-sa-pubsub.iam.gserviceaccount.com, is used.
   *
   * @param string $serviceAccountEmail
   */
  public function setServiceAccountEmail($serviceAccountEmail)
  {
    $this->serviceAccountEmail = $serviceAccountEmail;
  }
  /**
   * @return string
   */
  public function getServiceAccountEmail()
  {
    return $this->serviceAccountEmail;
  }
  /**
   * Output only. An output-only field that indicates whether or not the
   * subscription can receive messages.
   *
   * Accepted values: STATE_UNSPECIFIED, ACTIVE, NOT_FOUND,
   * APP_PROFILE_MISCONFIGURED, PERMISSION_DENIED, SCHEMA_MISMATCH,
   * IN_TRANSIT_LOCATION_RESTRICTION, VERTEX_AI_LOCATION_RESTRICTION
   *
   * @param self::STATE_* $state
   */
  public function setState($state)
  {
    $this->state = $state;
  }
  /**
   * @return self::STATE_*
   */
  public function getState()
  {
    return $this->state;
  }
  /**
   * Optional. The unique name of the table to write messages to. Values are of
   * the form `projects//instances//tables/`.
   *
   * @param string $table
   */
  public function setTable($table)
  {
    $this->table = $table;
  }
  /**
   * @return string
   */
  public function getTable()
  {
    return $this->table;
  }
  /**
   * Optional. When true, write the subscription name, message_id, publish_time,
   * attributes, and ordering_key to additional columns in the table under the
   * pubsub_metadata column family. The subscription name, message_id, and
   * publish_time fields are put in their own columns while all other message
   * properties (other than data) are written to a JSON object in the attributes
   * column.
   *
   * @param bool $writeMetadata
   */
  public function setWriteMetadata($writeMetadata)
  {
    $this->writeMetadata = $writeMetadata;
  }
  /**
   * @return bool
   */
  public function getWriteMetadata()
  {
    return $this->writeMetadata;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(BigtableConfig::class, 'Google_Service_Pubsub_BigtableConfig');
