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

namespace Google\Service\Dataform\Resource;

use Google\Service\Dataform\DataformEmpty;
use Google\Service\Dataform\DeleteFolderTreeRequest;
use Google\Service\Dataform\Folder;
use Google\Service\Dataform\MoveFolderRequest;
use Google\Service\Dataform\Operation;
use Google\Service\Dataform\Policy;
use Google\Service\Dataform\QueryFolderContentsResponse;
use Google\Service\Dataform\SetIamPolicyRequest;
use Google\Service\Dataform\TestIamPermissionsRequest;
use Google\Service\Dataform\TestIamPermissionsResponse;

/**
 * The "folders" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dataformService = new Google\Service\Dataform(...);
 *   $folders = $dataformService->projects_locations_folders;
 *  </code>
 */
class ProjectsLocationsFolders extends \Google\Service\Resource
{
  /**
   * Creates a new Folder in a given project and location. (folders.create)
   *
   * @param string $parent Required. The location in which to create the Folder.
   * Must be in the format `projects/locations`.
   * @param Folder $postBody
   * @param array $optParams Optional parameters.
   * @return Folder
   * @throws \Google\Service\Exception
   */
  public function create($parent, Folder $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('create', [$params], Folder::class);
  }
  /**
   * Deletes a single Folder. (folders.delete)
   *
   * @param string $name Required. The Folder's name.
   * @param array $optParams Optional parameters.
   * @return DataformEmpty
   * @throws \Google\Service\Exception
   */
  public function delete($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('delete', [$params], DataformEmpty::class);
  }
  /**
   * Deletes a Folder with its contents (Folders, Repositories, Workspaces,
   * ReleaseConfigs, and WorkflowConfigs). (folders.deleteTree)
   *
   * @param string $name Required. The Folder's name. Format:
   * projects/{project}/locations/{location}/folders/{folder}
   * @param DeleteFolderTreeRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function deleteTree($name, DeleteFolderTreeRequest $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('deleteTree', [$params], Operation::class);
  }
  /**
   * Fetches a single Folder. (folders.get)
   *
   * @param string $name Required. The Folder's name.
   * @param array $optParams Optional parameters.
   * @return Folder
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], Folder::class);
  }
  /**
   * Gets the access control policy for a resource. Returns an empty policy if the
   * resource exists and does not have a policy set. (folders.getIamPolicy)
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
   * Moves a Folder to a new Folder, TeamFolder, or the root location.
   * (folders.move)
   *
   * @param string $name Required. The full resource name of the Folder to move.
   * @param MoveFolderRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function move($name, MoveFolderRequest $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('move', [$params], Operation::class);
  }
  /**
   * Updates a single Folder. (folders.patch)
   *
   * @param string $name Identifier. The Folder's name.
   * @param Folder $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string updateMask Optional. Specifies the fields to be updated in
   * the Folder. If left unset, all fields that can be updated, will be updated. A
   * few fields cannot be updated and will be ignored if specified in the
   * update_mask (e.g. parent_name, team_folder_name).
   * @return Folder
   * @throws \Google\Service\Exception
   */
  public function patch($name, Folder $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('patch', [$params], Folder::class);
  }
  /**
   * Returns the contents of a given Folder. (folders.queryFolderContents)
   *
   * @param string $folder Required. Resource name of the Folder to list contents
   * for. Format: projects/locations/folders
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Optional. Optional filtering for the returned list.
   * Filtering is currently only supported on the `display_name` field. Example: *
   * `filter="display_name="MyFolder""`
   * @opt_param string orderBy Optional. Field to additionally sort results by.
   * Will order Folders before Repositories, and then by `order_by` in ascending
   * order. Supported keywords: display_name (default), create_time,
   * last_modified_time. Examples: * `orderBy="display_name"` *
   * `orderBy="display_name desc"`
   * @opt_param int pageSize Optional. Maximum number of paths to return. The
   * server may return fewer items than requested. If unspecified, the server will
   * pick an appropriate default.
   * @opt_param string pageToken Optional. Page token received from a previous
   * `QueryFolderContents` call. Provide this to retrieve the subsequent page.
   * When paginating, all other parameters provided to `QueryFolderContents`, with
   * the exception of `page_size`, must match the call that provided the page
   * token.
   * @return QueryFolderContentsResponse
   * @throws \Google\Service\Exception
   */
  public function queryFolderContents($folder, $optParams = [])
  {
    $params = ['folder' => $folder];
    $params = array_merge($params, $optParams);
    return $this->call('queryFolderContents', [$params], QueryFolderContentsResponse::class);
  }
  /**
   * Sets the access control policy on the specified resource. Replaces any
   * existing policy. Can return `NOT_FOUND`, `INVALID_ARGUMENT`, and
   * `PERMISSION_DENIED` errors. (folders.setIamPolicy)
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
   * This operation may "fail open" without warning. (folders.testIamPermissions)
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
class_alias(ProjectsLocationsFolders::class, 'Google_Service_Dataform_Resource_ProjectsLocationsFolders');
