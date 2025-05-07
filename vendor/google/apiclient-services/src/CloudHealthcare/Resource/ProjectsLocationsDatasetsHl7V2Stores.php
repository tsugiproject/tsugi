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

namespace Google\Service\CloudHealthcare\Resource;

use Google\Service\CloudHealthcare\ExportMessagesRequest;
use Google\Service\CloudHealthcare\HealthcareEmpty;
use Google\Service\CloudHealthcare\Hl7V2Store;
use Google\Service\CloudHealthcare\Hl7V2StoreMetrics;
use Google\Service\CloudHealthcare\ImportMessagesRequest;
use Google\Service\CloudHealthcare\ListHl7V2StoresResponse;
use Google\Service\CloudHealthcare\Operation;
use Google\Service\CloudHealthcare\Policy;
use Google\Service\CloudHealthcare\RollbackHl7V2MessagesRequest;
use Google\Service\CloudHealthcare\SetIamPolicyRequest;
use Google\Service\CloudHealthcare\TestIamPermissionsRequest;
use Google\Service\CloudHealthcare\TestIamPermissionsResponse;

/**
 * The "hl7V2Stores" collection of methods.
 * Typical usage is:
 *  <code>
 *   $healthcareService = new Google\Service\CloudHealthcare(...);
 *   $hl7V2Stores = $healthcareService->projects_locations_datasets_hl7V2Stores;
 *  </code>
 */
class ProjectsLocationsDatasetsHl7V2Stores extends \Google\Service\Resource
{
  /**
   * Creates a new HL7v2 store within the parent dataset. (hl7V2Stores.create)
   *
   * @param string $parent Required. The name of the dataset this HL7v2 store
   * belongs to.
   * @param Hl7V2Store $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string hl7V2StoreId Required. The ID of the HL7v2 store that is
   * being created. The string must match the following regex:
   * `[\p{L}\p{N}_\-\.]{1,256}`.
   * @return Hl7V2Store
   * @throws \Google\Service\Exception
   */
  public function create($parent, Hl7V2Store $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('create', [$params], Hl7V2Store::class);
  }
  /**
   * Deletes the specified HL7v2 store and removes all messages that it contains.
   * (hl7V2Stores.delete)
   *
   * @param string $name Required. The resource name of the HL7v2 store to delete.
   * @param array $optParams Optional parameters.
   * @return HealthcareEmpty
   * @throws \Google\Service\Exception
   */
  public function delete($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('delete', [$params], HealthcareEmpty::class);
  }
  /**
   * Exports the messages to a destination. To filter messages to be exported,
   * define a filter using the start and end time, relative to the message
   * generation time (MSH.7). This API returns an Operation that can be used to
   * track the status of the job by calling GetOperation. Immediate fatal errors
   * appear in the error field. Otherwise, when the operation finishes, a detailed
   * response of type ExportMessagesResponse is returned in the response field.
   * The metadata field type for this operation is OperationMetadata.
   * (hl7V2Stores.export)
   *
   * @param string $name Required. The name of the source HL7v2 store, in the
   * format `projects/{project_id}/locations/{location_id}/datasets/{dataset_id}/h
   * l7v2Stores/{hl7v2_store_id}`
   * @param ExportMessagesRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function export($name, ExportMessagesRequest $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('export', [$params], Operation::class);
  }
  /**
   * Gets the specified HL7v2 store. (hl7V2Stores.get)
   *
   * @param string $name Required. The resource name of the HL7v2 store to get.
   * @param array $optParams Optional parameters.
   * @return Hl7V2Store
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], Hl7V2Store::class);
  }
  /**
   * Gets metrics associated with the HL7v2 store.
   * (hl7V2Stores.getHL7v2StoreMetrics)
   *
   * @param string $name Required. The resource name of the HL7v2 store to get
   * metrics for, in the format `projects/{project_id}/locations/{location_id}/dat
   * asets/{dataset_id}/hl7V2Stores/{hl7v2_store_id}`.
   * @param array $optParams Optional parameters.
   * @return Hl7V2StoreMetrics
   * @throws \Google\Service\Exception
   */
  public function getHL7v2StoreMetrics($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('getHL7v2StoreMetrics', [$params], Hl7V2StoreMetrics::class);
  }
  /**
   * Gets the access control policy for a resource. Returns an empty policy if the
   * resource exists and does not have a policy set. (hl7V2Stores.getIamPolicy)
   *
   * @param string $resource REQUIRED: The resource for which the policy is being
   * requested. See [Resource
   * names](https://cloud.google.com/apis/design/resource_names) for the
   * appropriate value for this field.
   * @param array $optParams Optional parameters.
   *
   * @opt_param int options.requestedPolicyVersion Optional. The maximum policy
   * version that will be used to format the policy. Valid values are 0, 1, and 3.
   * Requests specifying an invalid value will be rejected. Requests for policies
   * with any conditional role bindings must specify version 3. Policies with no
   * conditional role bindings may specify any valid value or leave the field
   * unset. The policy in the response might use the policy version that you
   * specified, or it might use a lower policy version. For example, if you
   * specify version 3, but the policy has no conditional role bindings, the
   * response uses version 1. To learn which resources support conditions in their
   * IAM policies, see the [IAM
   * documentation](https://cloud.google.com/iam/help/conditions/resource-
   * policies).
   * @return Policy
   * @throws \Google\Service\Exception
   */
  public function getIamPolicy($resource, $optParams = [])
  {
    $params = ['resource' => $resource];
    $params = array_merge($params, $optParams);
    return $this->call('getIamPolicy', [$params], Policy::class);
  }
  /**
   * Import messages to the HL7v2 store by loading data from the specified
   * sources. This method is optimized to load large quantities of data using
   * import semantics that ignore some HL7v2 store configuration options and are
   * not suitable for all use cases. It is primarily intended to load data into an
   * empty HL7v2 store that is not being used by other clients. An existing
   * message will be overwritten if a duplicate message is imported. A duplicate
   * message is a message with the same raw bytes as a message that already exists
   * in this HL7v2 store. When a message is overwritten, its labels will also be
   * overwritten. The import operation is idempotent unless the input data
   * contains multiple valid messages with the same raw bytes but different
   * labels. In that case, after the import completes, the store contains exactly
   * one message with those raw bytes but there is no ordering guarantee on which
   * version of the labels it has. The operation result counters do not count
   * duplicated raw bytes as an error and count one success for each message in
   * the input, which might result in a success count larger than the number of
   * messages in the HL7v2 store. If some messages fail to import, for example due
   * to parsing errors, successfully imported messages are not rolled back. This
   * method returns an Operation that can be used to track the status of the
   * import by calling GetOperation. Immediate fatal errors appear in the error
   * field, errors are also logged to Cloud Logging (see [Viewing error logs in
   * Cloud Logging](https://cloud.google.com/healthcare/docs/how-tos/logging)).
   * Otherwise, when the operation finishes, a response of type
   * ImportMessagesResponse is returned in the response field. The metadata field
   * type for this operation is OperationMetadata. (hl7V2Stores.import)
   *
   * @param string $name Required. The name of the target HL7v2 store, in the
   * format `projects/{project_id}/locations/{location_id}/datasets/{dataset_id}/h
   * l7v2Stores/{hl7v2_store_id}`
   * @param ImportMessagesRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function import($name, ImportMessagesRequest $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('import', [$params], Operation::class);
  }
  /**
   * Lists the HL7v2 stores in the given dataset.
   * (hl7V2Stores.listProjectsLocationsDatasetsHl7V2Stores)
   *
   * @param string $parent Required. Name of the dataset.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Restricts stores returned to those matching a
   * filter. The following syntax is available: * A string field value can be
   * written as text inside quotation marks, for example `"query text"`. The only
   * valid relational operation for text fields is equality (`=`), where text is
   * searched within the field, rather than having the field be equal to the text.
   * For example, `"Comment = great"` returns messages with `great` in the comment
   * field. * A number field value can be written as an integer, a decimal, or an
   * exponential. The valid relational operators for number fields are the
   * equality operator (`=`), along with the less than/greater than operators
   * (`<`, `<=`, `>`, `>=`). Note that there is no inequality (`!=`) operator. You
   * can prepend the `NOT` operator to an expression to negate it. * A date field
   * value must be written in `yyyy-mm-dd` form. Fields with date and time use the
   * RFC3339 time format. Leading zeros are required for one-digit months and
   * days. The valid relational operators for date fields are the equality
   * operator (`=`) , along with the less than/greater than operators (`<`, `<=`,
   * `>`, `>=`). Note that there is no inequality (`!=`) operator. You can prepend
   * the `NOT` operator to an expression to negate it. * Multiple field query
   * expressions can be combined in one query by adding `AND` or `OR` operators
   * between the expressions. If a boolean operator appears within a quoted
   * string, it is not treated as special, it's just another part of the character
   * string to be matched. You can prepend the `NOT` operator to an expression to
   * negate it. Only filtering on labels is supported. For example,
   * `labels.key=value`.
   * @opt_param int pageSize Limit on the number of HL7v2 stores to return in a
   * single response. If not specified, 100 is used. May not be larger than 1000.
   * @opt_param string pageToken The next_page_token value returned from the
   * previous List request, if any.
   * @return ListHl7V2StoresResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsLocationsDatasetsHl7V2Stores($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListHl7V2StoresResponse::class);
  }
  /**
   * Updates the HL7v2 store. (hl7V2Stores.patch)
   *
   * @param string $name Identifier. Resource name of the HL7v2 store, of the form
   * `projects/{project_id}/locations/{location_id}/datasets/{dataset_id}/hl7V2Sto
   * res/{hl7v2_store_id}`.
   * @param Hl7V2Store $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string updateMask Required. The update mask applies to the
   * resource. For the `FieldMask` definition, see
   * https://developers.google.com/protocol-
   * buffers/docs/reference/google.protobuf#fieldmask
   * @return Hl7V2Store
   * @throws \Google\Service\Exception
   */
  public function patch($name, Hl7V2Store $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('patch', [$params], Hl7V2Store::class);
  }
  /**
   * Rolls back messages from the HL7v2 store to the specified time. This method
   * returns an Operation that can be used to track the status of the rollback by
   * calling GetOperation. Immediate fatal errors appear in the error field,
   * errors are also logged to Cloud Logging (see [Viewing error logs in Cloud
   * Logging](https://cloud.google.com/healthcare/docs/how-tos/logging)).
   * Otherwise, when the operation finishes, a detailed response of type
   * RollbackHl7V2MessagesResponse is returned in the response field. The metadata
   * field type for this operation is OperationMetadata. (hl7V2Stores.rollback)
   *
   * @param string $name Required. The name of the HL7v2 store to rollback, in the
   * format of
   * "projects/{project_id}/locations/{location_id}/datasets/{dataset_id}
   * /hl7V2Stores/{hl7v2_store_id}".
   * @param RollbackHl7V2MessagesRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function rollback($name, RollbackHl7V2MessagesRequest $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('rollback', [$params], Operation::class);
  }
  /**
   * Sets the access control policy on the specified resource. Replaces any
   * existing policy. Can return `NOT_FOUND`, `INVALID_ARGUMENT`, and
   * `PERMISSION_DENIED` errors. (hl7V2Stores.setIamPolicy)
   *
   * @param string $resource REQUIRED: The resource for which the policy is being
   * specified. See [Resource
   * names](https://cloud.google.com/apis/design/resource_names) for the
   * appropriate value for this field.
   * @param SetIamPolicyRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Policy
   * @throws \Google\Service\Exception
   */
  public function setIamPolicy($resource, SetIamPolicyRequest $postBody, $optParams = [])
  {
    $params = ['resource' => $resource, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('setIamPolicy', [$params], Policy::class);
  }
  /**
   * Returns permissions that a caller has on the specified resource. If the
   * resource does not exist, this will return an empty set of permissions, not a
   * `NOT_FOUND` error. Note: This operation is designed to be used for building
   * permission-aware UIs and command-line tools, not for authorization checking.
   * This operation may "fail open" without warning.
   * (hl7V2Stores.testIamPermissions)
   *
   * @param string $resource REQUIRED: The resource for which the policy detail is
   * being requested. See [Resource
   * names](https://cloud.google.com/apis/design/resource_names) for the
   * appropriate value for this field.
   * @param TestIamPermissionsRequest $postBody
   * @param array $optParams Optional parameters.
   * @return TestIamPermissionsResponse
   * @throws \Google\Service\Exception
   */
  public function testIamPermissions($resource, TestIamPermissionsRequest $postBody, $optParams = [])
  {
    $params = ['resource' => $resource, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('testIamPermissions', [$params], TestIamPermissionsResponse::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsDatasetsHl7V2Stores::class, 'Google_Service_CloudHealthcare_Resource_ProjectsLocationsDatasetsHl7V2Stores');
