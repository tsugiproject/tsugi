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

namespace Google\Service\AnalyticsHub;

class BigtableConfig extends \Google\Model
{
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
   * agent]({$universe.dns_names.final_documentation_domain}/iam/docs/service-
   * agents), service-{project_number}@gcp-sa-pubsub.iam.gserviceaccount.com, is
   * used.
   *
   * @var string
   */
  public $serviceAccountEmail;
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
   * agent]({$universe.dns_names.final_documentation_domain}/iam/docs/service-
   * agents), service-{project_number}@gcp-sa-pubsub.iam.gserviceaccount.com, is
   * used.
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
class_alias(BigtableConfig::class, 'Google_Service_AnalyticsHub_BigtableConfig');
