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

namespace Google\Service\Spanner\Resource;

use Google\Service\Spanner\AddSplitPointsRequest;
use Google\Service\Spanner\AddSplitPointsResponse;
use Google\Service\Spanner\ChangeQuorumRequest;
use Google\Service\Spanner\CreateDatabaseRequest;
use Google\Service\Spanner\Database;
use Google\Service\Spanner\GetDatabaseDdlResponse;
use Google\Service\Spanner\GetIamPolicyRequest;
use Google\Service\Spanner\ListDatabasesResponse;
use Google\Service\Spanner\Operation;
use Google\Service\Spanner\Policy;
use Google\Service\Spanner\RestoreDatabaseRequest;
use Google\Service\Spanner\Scan;
use Google\Service\Spanner\SetIamPolicyRequest;
use Google\Service\Spanner\SpannerEmpty;
use Google\Service\Spanner\TestIamPermissionsRequest;
use Google\Service\Spanner\TestIamPermissionsResponse;
use Google\Service\Spanner\UpdateDatabaseDdlRequest;

/**
 * The "databases" collection of methods.
 * Typical usage is:
 *  <code>
 *   $spannerService = new Google\Service\Spanner(...);
 *   $databases = $spannerService->projects_instances_databases;
 *  </code>
 */
class ProjectsInstancesDatabases extends \Google\Service\Resource
{
  /**
   * Adds split points to specified tables, indexes of a database.
   * (databases.addSplitPoints)
   *
   * @param string $database Required. The database on whose tables/indexes split
   * points are to be added. Values are of the form
   * `projects//instances//databases/`.
   * @param AddSplitPointsRequest $postBody
   * @param array $optParams Optional parameters.
   * @return AddSplitPointsResponse
   * @throws \Google\Service\Exception
   */
  public function addSplitPoints($database, AddSplitPointsRequest $postBody, $optParams = [])
  {
    $params = ['database' => $database, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('addSplitPoints', [$params], AddSplitPointsResponse::class);
  }
  /**
   * `ChangeQuorum` is strictly restricted to databases that use dual-region
   * instance configurations. Initiates a background operation to change the
   * quorum of a database from dual-region mode to single-region mode or vice
   * versa. The returned long-running operation has a name of the format
   * `projects//instances//databases//operations/` and can be used to track
   * execution of the `ChangeQuorum`. The metadata field type is
   * ChangeQuorumMetadata. Authorization requires `spanner.databases.changequorum`
   * permission on the resource database. (databases.changequorum)
   *
   * @param string $name Required. Name of the database in which to apply
   * `ChangeQuorum`. Values are of the form `projects//instances//databases/`.
   * @param ChangeQuorumRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function changequorum($name, ChangeQuorumRequest $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('changequorum', [$params], Operation::class);
  }
  /**
   * Creates a new Spanner database and starts to prepare it for serving. The
   * returned long-running operation will have a name of the format `/operations/`
   * and can be used to track preparation of the database. The metadata field type
   * is CreateDatabaseMetadata. The response field type is Database, if
   * successful. (databases.create)
   *
   * @param string $parent Required. The name of the instance that will serve the
   * new database. Values are of the form `projects//instances/`.
   * @param CreateDatabaseRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function create($parent, CreateDatabaseRequest $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('create', [$params], Operation::class);
  }
  /**
   * Drops (aka deletes) a Cloud Spanner database. Completed backups for the
   * database will be retained according to their `expire_time`. Note: Cloud
   * Spanner might continue to accept requests for a few seconds after the
   * database has been deleted. (databases.dropDatabase)
   *
   * @param string $database Required. The database to be dropped.
   * @param array $optParams Optional parameters.
   * @return SpannerEmpty
   * @throws \Google\Service\Exception
   */
  public function dropDatabase($database, $optParams = [])
  {
    $params = ['database' => $database];
    $params = array_merge($params, $optParams);
    return $this->call('dropDatabase', [$params], SpannerEmpty::class);
  }
  /**
   * Gets the state of a Cloud Spanner database. (databases.get)
   *
   * @param string $name Required. The name of the requested database. Values are
   * of the form `projects//instances//databases/`.
   * @param array $optParams Optional parameters.
   * @return Database
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], Database::class);
  }
  /**
   * Returns the schema of a Cloud Spanner database as a list of formatted DDL
   * statements. This method does not show pending schema updates, those may be
   * queried using the Operations API. (databases.getDdl)
   *
   * @param string $database Required. The database whose schema we wish to get.
   * Values are of the form `projects//instances//databases/`
   * @param array $optParams Optional parameters.
   * @return GetDatabaseDdlResponse
   * @throws \Google\Service\Exception
   */
  public function getDdl($database, $optParams = [])
  {
    $params = ['database' => $database];
    $params = array_merge($params, $optParams);
    return $this->call('getDdl', [$params], GetDatabaseDdlResponse::class);
  }
  /**
   * Gets the access control policy for a database or backup resource. Returns an
   * empty policy if a database or backup exists but does not have a policy set.
   * Authorization requires `spanner.databases.getIamPolicy` permission on
   * resource. For backups, authorization requires `spanner.backups.getIamPolicy`
   * permission on resource. For backup schedules, authorization requires
   * `spanner.backupSchedules.getIamPolicy` permission on resource.
   * (databases.getIamPolicy)
   *
   * @param string $resource REQUIRED: The Cloud Spanner resource for which the
   * policy is being retrieved. The format is `projects//instances/` for instance
   * resources and `projects//instances//databases/` for database resources.
   * @param GetIamPolicyRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Policy
   * @throws \Google\Service\Exception
   */
  public function getIamPolicy($resource, GetIamPolicyRequest $postBody, $optParams = [])
  {
    $params = ['resource' => $resource, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('getIamPolicy', [$params], Policy::class);
  }
  /**
   * Request a specific scan with Database-specific data for Cloud Key Visualizer.
   * (databases.getScans)
   *
   * @param string $name Required. The unique name of the scan containing the
   * requested information, specific to the Database service implementing this
   * interface.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string endTime The upper bound for the time range to retrieve Scan
   * data for.
   * @opt_param string startTime These fields restrict the Database-specific
   * information returned in the `Scan.data` field. If a `View` is provided that
   * does not include the `Scan.data` field, these are ignored. This range of time
   * must be entirely contained within the defined time range of the targeted
   * scan. The lower bound for the time range to retrieve Scan data for.
   * @opt_param string view Specifies which parts of the Scan should be returned
   * in the response. Note, if left unspecified, the FULL view is assumed.
   * @return Scan
   * @throws \Google\Service\Exception
   */
  public function getScans($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('getScans', [$params], Scan::class);
  }
  /**
   * Lists Cloud Spanner databases. (databases.listProjectsInstancesDatabases)
   *
   * @param string $parent Required. The instance whose databases should be
   * listed. Values are of the form `projects//instances/`.
   * @param array $optParams Optional parameters.
   *
   * @opt_param int pageSize Number of databases to be returned in the response.
   * If 0 or less, defaults to the server's maximum allowed page size.
   * @opt_param string pageToken If non-empty, `page_token` should contain a
   * next_page_token from a previous ListDatabasesResponse.
   * @return ListDatabasesResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsInstancesDatabases($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListDatabasesResponse::class);
  }
  /**
   * Updates a Cloud Spanner database. The returned long-running operation can be
   * used to track the progress of updating the database. If the named database
   * does not exist, returns `NOT_FOUND`. While the operation is pending: * The
   * database's reconciling field is set to true. * Cancelling the operation is
   * best-effort. If the cancellation succeeds, the operation metadata's
   * cancel_time is set, the updates are reverted, and the operation terminates
   * with a `CANCELLED` status. * New UpdateDatabase requests will return a
   * `FAILED_PRECONDITION` error until the pending operation is done (returns
   * successfully or with error). * Reading the database via the API continues to
   * give the pre-request values. Upon completion of the returned operation: * The
   * new values are in effect and readable via the API. * The database's
   * reconciling field becomes false. The returned long-running operation will
   * have a name of the format `projects//instances//databases//operations/` and
   * can be used to track the database modification. The metadata field type is
   * UpdateDatabaseMetadata. The response field type is Database, if successful.
   * (databases.patch)
   *
   * @param string $name Required. The name of the database. Values are of the
   * form `projects//instances//databases/`, where `` is as specified in the
   * `CREATE DATABASE` statement. This name can be passed to other API methods to
   * identify the database.
   * @param Database $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string updateMask Required. The list of fields to update.
   * Currently, only `enable_drop_protection` field can be updated.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function patch($name, Database $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('patch', [$params], Operation::class);
  }
  /**
   * Create a new database by restoring from a completed backup. The new database
   * must be in the same project and in an instance with the same instance
   * configuration as the instance containing the backup. The returned database
   * long-running operation has a name of the format
   * `projects//instances//databases//operations/`, and can be used to track the
   * progress of the operation, and to cancel it. The metadata field type is
   * RestoreDatabaseMetadata. The response type is Database, if successful.
   * Cancelling the returned operation will stop the restore and delete the
   * database. There can be only one database being restored into an instance at a
   * time. Once the restore operation completes, a new restore operation can be
   * initiated, without waiting for the optimize operation associated with the
   * first restore to complete. (databases.restore)
   *
   * @param string $parent Required. The name of the instance in which to create
   * the restored database. This instance must be in the same project and have the
   * same instance configuration as the instance containing the source backup.
   * Values are of the form `projects//instances/`.
   * @param RestoreDatabaseRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function restore($parent, RestoreDatabaseRequest $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('restore', [$params], Operation::class);
  }
  /**
   * Sets the access control policy on a database or backup resource. Replaces any
   * existing policy. Authorization requires `spanner.databases.setIamPolicy`
   * permission on resource. For backups, authorization requires
   * `spanner.backups.setIamPolicy` permission on resource. For backup schedules,
   * authorization requires `spanner.backupSchedules.setIamPolicy` permission on
   * resource. (databases.setIamPolicy)
   *
   * @param string $resource REQUIRED: The Cloud Spanner resource for which the
   * policy is being set. The format is `projects//instances/` for instance
   * resources and `projects//instances//databases/` for databases resources.
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
   * Returns permissions that the caller has on the specified database or backup
   * resource. Attempting this RPC on a non-existent Cloud Spanner database will
   * result in a NOT_FOUND error if the user has `spanner.databases.list`
   * permission on the containing Cloud Spanner instance. Otherwise returns an
   * empty set of permissions. Calling this method on a backup that does not exist
   * will result in a NOT_FOUND error if the user has `spanner.backups.list`
   * permission on the containing instance. Calling this method on a backup
   * schedule that does not exist will result in a NOT_FOUND error if the user has
   * `spanner.backupSchedules.list` permission on the containing database.
   * (databases.testIamPermissions)
   *
   * @param string $resource REQUIRED: The Cloud Spanner resource for which
   * permissions are being tested. The format is `projects//instances/` for
   * instance resources and `projects//instances//databases/` for database
   * resources.
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
   * Updates the schema of a Cloud Spanner database by creating/altering/dropping
   * tables, columns, indexes, etc. The returned long-running operation will have
   * a name of the format `/operations/` and can be used to track execution of the
   * schema change(s). The metadata field type is UpdateDatabaseDdlMetadata. The
   * operation has no response. (databases.updateDdl)
   *
   * @param string $database Required. The database to update.
   * @param UpdateDatabaseDdlRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function updateDdl($database, UpdateDatabaseDdlRequest $postBody, $optParams = [])
  {
    $params = ['database' => $database, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('updateDdl', [$params], Operation::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsInstancesDatabases::class, 'Google_Service_Spanner_Resource_ProjectsInstancesDatabases');
