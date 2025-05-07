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

namespace Google\Service\GKEOnPrem\Resource;

use Google\Service\GKEOnPrem\EnrollVmwareClusterRequest;
use Google\Service\GKEOnPrem\ListVmwareClustersResponse;
use Google\Service\GKEOnPrem\Operation;
use Google\Service\GKEOnPrem\Policy;
use Google\Service\GKEOnPrem\QueryVmwareVersionConfigResponse;
use Google\Service\GKEOnPrem\SetIamPolicyRequest;
use Google\Service\GKEOnPrem\TestIamPermissionsRequest;
use Google\Service\GKEOnPrem\TestIamPermissionsResponse;
use Google\Service\GKEOnPrem\VmwareCluster;

/**
 * The "vmwareClusters" collection of methods.
 * Typical usage is:
 *  <code>
 *   $gkeonpremService = new Google\Service\GKEOnPrem(...);
 *   $vmwareClusters = $gkeonpremService->projects_locations_vmwareClusters;
 *  </code>
 */
class ProjectsLocationsVmwareClusters extends \Google\Service\Resource
{
  /**
   * Creates a new VMware user cluster in a given project and location.
   * (vmwareClusters.create)
   *
   * @param string $parent Required. The parent of the project and location where
   * this cluster is created in. Format: "projects/{project}/locations/{location}"
   * @param VmwareCluster $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool allowPreflightFailure Optional. If set to true, CLM will
   * force CCFE to persist the cluster resource in RMS when the creation fails
   * during standalone preflight checks. In that case the subsequent create call
   * will fail with "cluster already exists" error and hence a update cluster is
   * required to fix the cluster.
   * @opt_param bool validateOnly Validate the request without actually doing any
   * updates.
   * @opt_param string vmwareClusterId User provided identifier that is used as
   * part of the resource name; This value must be up to 40 characters and follow
   * RFC-1123 (https://tools.ietf.org/html/rfc1123) format.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function create($parent, VmwareCluster $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('create', [$params], Operation::class);
  }
  /**
   * Deletes a single VMware Cluster. (vmwareClusters.delete)
   *
   * @param string $name Required. Name of the VMware user cluster to be deleted.
   * Format:
   * "projects/{project}/locations/{location}/vmwareClusters/{vmware_cluster}"
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool allowMissing If set to true, and the VMware cluster is not
   * found, the request will succeed but no action will be taken on the server and
   * return a completed LRO.
   * @opt_param string etag The current etag of the VMware cluster. If an etag is
   * provided and does not match the current etag of the cluster, deletion will be
   * blocked and an ABORTED error will be returned.
   * @opt_param bool force If set to true, any node pools from the cluster will
   * also be deleted.
   * @opt_param bool ignoreErrors If set to true, the deletion of a VMware user
   * cluster resource will succeed even if errors occur during deletion. This
   * parameter can be used when you want to delete GCP's cluster resource and the
   * on-prem admin cluster that hosts your user cluster is disconnected /
   * unreachable or deleted. WARNING: Using this parameter when your user cluster
   * still exists may result in a deleted GCP user cluster but an existing on-prem
   * user cluster.
   * @opt_param bool validateOnly Validate the request without actually doing any
   * updates.
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
   * Enrolls an existing VMware user cluster and its node pools to the Anthos On-
   * Prem API within a given project and location. Through enrollment, an existing
   * cluster will become Anthos On-Prem API managed. The corresponding GCP
   * resources will be created and all future modifications to the cluster and/or
   * its node pools will be expected to be performed through the API.
   * (vmwareClusters.enroll)
   *
   * @param string $parent Required. The parent of the project and location where
   * the cluster is Enrolled in. Format: "projects/{project}/locations/{location}"
   * @param EnrollVmwareClusterRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function enroll($parent, EnrollVmwareClusterRequest $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('enroll', [$params], Operation::class);
  }
  /**
   * Gets details of a single VMware Cluster. (vmwareClusters.get)
   *
   * @param string $name Required. Name of the VMware user cluster to be returned.
   * Format:
   * "projects/{project}/locations/{location}/vmwareClusters/{vmware_cluster}"
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool allowMissing Optional. If true, return Vmware Cluster
   * including the one that only exists in RMS.
   * @opt_param string view View for VMware user cluster. When `BASIC` is
   * specified, only the cluster resource name and admin cluster membership are
   * returned. The default/unset value `CLUSTER_VIEW_UNSPECIFIED` is the same as
   * `FULL', which returns the complete cluster configuration details.
   * @return VmwareCluster
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], VmwareCluster::class);
  }
  /**
   * Gets the access control policy for a resource. Returns an empty policy if the
   * resource exists and does not have a policy set. (vmwareClusters.getIamPolicy)
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
   * Lists VMware Clusters in a given project and location.
   * (vmwareClusters.listProjectsLocationsVmwareClusters)
   *
   * @param string $parent Required. The parent of the project and location where
   * the clusters are listed in. Format: "projects/{project}/locations/{location}"
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool allowMissing Optional. If true, return list of Vmware
   * Clusters including the ones that only exists in RMS.
   * @opt_param string filter A resource filtering expression following
   * https://google.aip.dev/160. When non-empty, only resource's whose attributes
   * field matches the filter are returned.
   * @opt_param int pageSize Requested page size. Server may return fewer items
   * than requested. If unspecified, at most 50 clusters will be returned. The
   * maximum value is 1000; values above 1000 will be coerced to 1000.
   * @opt_param string pageToken A token identifying a page of results the server
   * should return.
   * @opt_param string view View for VMware clusters. When `BASIC` is specified,
   * only the cluster resource name and admin cluster membership are returned. The
   * default/unset value `CLUSTER_VIEW_UNSPECIFIED` is the same as `FULL', which
   * returns the complete cluster configuration details.
   * @return ListVmwareClustersResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsLocationsVmwareClusters($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListVmwareClustersResponse::class);
  }
  /**
   * Updates the parameters of a single VMware cluster. (vmwareClusters.patch)
   *
   * @param string $name Immutable. The VMware user cluster resource name.
   * @param VmwareCluster $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string updateMask Required. Field mask is used to specify the
   * fields to be overwritten in the VMwareCluster resource by the update. The
   * fields specified in the update_mask are relative to the resource, not the
   * full request. A field will be overwritten if it is in the mask. If the user
   * does not provide a mask then all populated fields in the VmwareCluster
   * message will be updated. Empty fields will be ignored unless a field mask is
   * used.
   * @opt_param bool validateOnly Validate the request without actually doing any
   * updates.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function patch($name, VmwareCluster $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('patch', [$params], Operation::class);
  }
  /**
   * Queries the VMware user cluster version config.
   * (vmwareClusters.queryVersionConfig)
   *
   * @param string $parent Required. The parent of the project and location to
   * query for version config. Format: "projects/{project}/locations/{location}"
   * @param array $optParams Optional parameters.
   *
   * @opt_param string createConfig.adminClusterMembership The admin cluster
   * membership. This is the full resource name of the admin cluster's fleet
   * membership. Format:
   * "projects/{project}/locations/{location}/memberships/{membership}"
   * @opt_param string createConfig.adminClusterName The admin cluster resource
   * name. This is the full resource name of the admin cluster resource. Format: "
   * projects/{project}/locations/{location}/vmwareAdminClusters/{vmware_admin_clu
   * ster}"
   * @opt_param string upgradeConfig.clusterName The user cluster resource name.
   * This is the full resource name of the user cluster resource. Format:
   * "projects/{project}/locations/{location}/vmwareClusters/{vmware_cluster}"
   * @return QueryVmwareVersionConfigResponse
   * @throws \Google\Service\Exception
   */
  public function queryVersionConfig($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('queryVersionConfig', [$params], QueryVmwareVersionConfigResponse::class);
  }
  /**
   * Sets the access control policy on the specified resource. Replaces any
   * existing policy. Can return `NOT_FOUND`, `INVALID_ARGUMENT`, and
   * `PERMISSION_DENIED` errors. (vmwareClusters.setIamPolicy)
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
   * (vmwareClusters.testIamPermissions)
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
  /**
   * Unenrolls an existing VMware user cluster and its node pools from the Anthos
   * On-Prem API within a given project and location. Unenrollment removes the
   * Cloud reference to the cluster without modifying the underlying OnPrem
   * Resources. Clusters and node pools will continue to run; however, they will
   * no longer be accessible through the Anthos On-Prem API or UI.
   * (vmwareClusters.unenroll)
   *
   * @param string $name Required. Name of the VMware user cluster to be
   * unenrolled. Format:
   * "projects/{project}/locations/{location}/vmwareClusters/{vmware_cluster}"
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool allowMissing If set to true, and the VMware cluster is not
   * found, the request will succeed but no action will be taken on the server and
   * return a completed LRO.
   * @opt_param string etag The current etag of the VMware Cluster. If an etag is
   * provided and does not match the current etag of the cluster, deletion will be
   * blocked and an ABORTED error will be returned.
   * @opt_param bool force This is required if the cluster has any associated node
   * pools. When set, any child node pools will also be unenrolled.
   * @opt_param bool validateOnly Validate the request without actually doing any
   * updates.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function unenroll($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('unenroll', [$params], Operation::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsVmwareClusters::class, 'Google_Service_GKEOnPrem_Resource_ProjectsLocationsVmwareClusters');
