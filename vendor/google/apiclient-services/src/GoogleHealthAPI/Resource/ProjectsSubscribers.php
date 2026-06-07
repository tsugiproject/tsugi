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

namespace Google\Service\GoogleHealthAPI\Resource;

use Google\Service\GoogleHealthAPI\CreateSubscriberPayload;
use Google\Service\GoogleHealthAPI\ListSubscribersResponse;
use Google\Service\GoogleHealthAPI\Operation;
use Google\Service\GoogleHealthAPI\Subscriber;

/**
 * The "subscribers" collection of methods.
 * Typical usage is:
 *  <code>
 *   $healthService = new Google\Service\GoogleHealthAPI(...);
 *   $subscribers = $healthService->projects_subscribers;
 *  </code>
 */
class ProjectsSubscribers extends \Google\Service\Resource
{
  /**
   * Registers a new subscriber endpoint to receive notifications. A subscriber
   * represents an application or service that wishes to receive data change
   * notifications for users who have granted consent. **Endpoint Verification:**
   * For a subscriber to be successfully created, the provided `endpoint_uri` must
   * be a valid HTTPS endpoint and must pass an automated verification check. The
   * backend will send two HTTP POST requests to the `endpoint_uri`: 1.
   * **Verification with Authorization:** * **Headers:** Includes `Content-Type:
   * application/json` and `Authorization` (with the exact value from
   * `CreateSubscriberPayload.endpoint_authorization.secret`). * **Body:**
   * `{"type": "verification"}` * **Expected Response:** HTTP `201 Created`. 2.
   * **Verification without Authorization:** * **Headers:** Includes `Content-
   * Type: application/json`. The `Authorization` header is OMITTED. * **Body:**
   * `{"type": "verification"}` * **Expected Response:** HTTP `401 Unauthorized`
   * or `403 Forbidden`. Both tests must pass for the subscriber creation to
   * succeed. If verification fails, the operation will not be completed and an
   * error will be returned. This process ensures the endpoint is reachable and
   * correctly validates the `Authorization` header. (subscribers.create)
   *
   * @param string $parent Required. The parent resource where this subscriber
   * will be created. Format: projects/{project} Example: projects/my-project-123
   * @param CreateSubscriberPayload $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string subscriberId Optional. The ID to use for the subscriber,
   * which will become the final component of the subscriber's resource name. This
   * value should be 4-36 characters, and valid characters are
   * /[a-z]([a-z0-9-]{2,34}[a-z0-9])/.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function create($parent, CreateSubscriberPayload $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('create', [$params], Operation::class);
  }
  /**
   * Deletes a subscriber registration. This will stop all notifications to the
   * subscriber's endpoint. (subscribers.delete)
   *
   * @param string $name Required. The name of the subscriber to delete. Format:
   * projects/{project}/subscribers/{subscriber} Example: projects/my-
   * project/subscribers/my-subscriber-123 The {subscriber} ID is user-settable
   * (4-36 characters, matching /[a-z]([a-z0-9-]{2,34}[a-z0-9])/) or system-
   * generated if not provided during creation.
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool force Optional. If set to true, any child resources (e.g.,
   * subscriptions) will also be deleted. If false (default) and child resources
   * exist, the request will fail.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function delete($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('delete', [$params], Operation::class);
  }
  /**
   * Lists all subscribers registered within the owned Google Cloud Project.
   * (subscribers.listProjectsSubscribers)
   *
   * @param string $parent Required. The parent, which owns this collection of
   * subscribers. Format: projects/{project}
   * @param array $optParams Optional parameters.
   *
   * @opt_param int pageSize Optional. The maximum number of subscribers to
   * return. The service may return fewer than this value. If unspecified, at most
   * 50 subscribers will be returned. The maximum value is 1000; values above 1000
   * will be coerced to 1000.
   * @opt_param string pageToken Optional. A page token, received from a previous
   * `ListSubscribers` call. Provide this to retrieve the subsequent page. When
   * paginating, all other parameters provided to `ListSubscribers` must match the
   * call that provided the page token.
   * @return ListSubscribersResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsSubscribers($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListSubscribersResponse::class);
  }
  /**
   * Updates the configuration of an existing subscriber, such as the endpoint URI
   * or the data types it's interested in. **Endpoint Verification:** If the
   * `endpoint_uri` or `endpoint_authorization` field is included in the
   * `update_mask`, the backend will re-verify the endpoint. The verification
   * process is the same as described in `CreateSubscriber`: 1. **Verification
   * with Authorization:** POST to the new or existing `endpoint_uri` with the new
   * or existing `Authorization` secret. Expects HTTP `201 Created`. 2.
   * **Verification without Authorization:** POST to the `endpoint_uri` without
   * the `Authorization` header. Expects HTTP `401 Unauthorized` or `403
   * Forbidden`. Both tests must pass using the potentially updated values for the
   * subscriber update to succeed. If verification fails, the update will not be
   * applied, and an error will be returned. (subscribers.patch)
   *
   * @param string $name Identifier. The resource name of the Subscriber. Format:
   * projects/{project}/subscribers/{subscriber} The {project} ID is a Google
   * Cloud Project ID or Project Number. The {subscriber} ID is user-settable
   * (4-36 characters, matching /[a-z]([a-z0-9-]{2,34}[a-z0-9])/) if provided
   * during creation, or system-generated otherwise (e.g., a UUID). Example (User-
   * settable subscriber ID): projects/my-project/subscribers/my-sub-123 Example
   * (System-generated subscriber ID): projects/my-
   * project/subscribers/a1b2c3d4-e5f6-7890-1234-567890abcdef
   * @param Subscriber $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string updateMask Optional. A field mask that specifies which
   * fields of the Subscriber message are to be updated. This allows for partial
   * updates. Supported fields: - endpoint_uri - subscriber_configs -
   * endpoint_authorization
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function patch($name, Subscriber $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('patch', [$params], Operation::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsSubscribers::class, 'Google_Service_GoogleHealthAPI_Resource_ProjectsSubscribers');
