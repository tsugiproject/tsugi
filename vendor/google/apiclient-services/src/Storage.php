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

namespace Google\Service;

use Google\Client;

/**
 * Service definition for Storage (v1).
 *
 * <p>
 * Stores and retrieves potentially large, immutable data objects.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/storage/docs/json_api/" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Storage extends \Google\Service
{
  /** View and manage your data across Google Cloud Platform services. */
  const CLOUD_PLATFORM =
      "https://www.googleapis.com/auth/cloud-platform";
  /** View your data across Google Cloud Platform services. */
  const CLOUD_PLATFORM_READ_ONLY =
      "https://www.googleapis.com/auth/cloud-platform.read-only";
  /** Manage your data and permissions in Google Cloud Storage. */
  const DEVSTORAGE_FULL_CONTROL =
      "https://www.googleapis.com/auth/devstorage.full_control";
  /** View your data in Google Cloud Storage. */
  const DEVSTORAGE_READ_ONLY =
      "https://www.googleapis.com/auth/devstorage.read_only";
  /** Manage your data in Google Cloud Storage. */
  const DEVSTORAGE_READ_WRITE =
      "https://www.googleapis.com/auth/devstorage.read_write";

  public $anywhereCaches;
  public $bucketAccessControls;
  public $buckets;
  public $channels;
  public $defaultObjectAccessControls;
  public $folders;
  public $managedFolders;
  public $notifications;
  public $objectAccessControls;
  public $objects;
  public $operations;
  public $projects_hmacKeys;
  public $projects_serviceAccount;
  public $rootUrlTemplate;

  /**
   * Constructs the internal representation of the Storage service.
   *
   * @param Client|array $clientOrConfig The client used to deliver requests, or a
   *                                     config array to pass to a new Client instance.
   * @param string $rootUrl The root URL used for requests to the service.
   */
  public function __construct($clientOrConfig = [], $rootUrl = null)
  {
    parent::__construct($clientOrConfig);
    $this->rootUrl = $rootUrl ?: 'https://storage.googleapis.com/';
    $this->rootUrlTemplate = $rootUrl ?: 'https://storage.UNIVERSE_DOMAIN/';
    $this->servicePath = 'storage/v1/';
    $this->batchPath = 'batch/storage/v1';
    $this->version = 'v1';
    $this->serviceName = 'storage';

    $this->anywhereCaches = new Storage\Resource\AnywhereCaches(
        $this,
        $this->serviceName,
        'anywhereCaches',
        [
          'methods' => [
            'disable' => [
              'path' => 'b/{bucket}/anywhereCaches/{anywhereCacheId}/disable',
              'httpMethod' => 'POST',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'anywhereCacheId' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'get' => [
              'path' => 'b/{bucket}/anywhereCaches/{anywhereCacheId}',
              'httpMethod' => 'GET',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'anywhereCacheId' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'insert' => [
              'path' => 'b/{bucket}/anywhereCaches',
              'httpMethod' => 'POST',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'list' => [
              'path' => 'b/{bucket}/anywhereCaches',
              'httpMethod' => 'GET',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'pageSize' => [
                  'location' => 'query',
                  'type' => 'integer',
                ],
                'pageToken' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'pause' => [
              'path' => 'b/{bucket}/anywhereCaches/{anywhereCacheId}/pause',
              'httpMethod' => 'POST',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'anywhereCacheId' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'resume' => [
              'path' => 'b/{bucket}/anywhereCaches/{anywhereCacheId}/resume',
              'httpMethod' => 'POST',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'anywhereCacheId' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'update' => [
              'path' => 'b/{bucket}/anywhereCaches/{anywhereCacheId}',
              'httpMethod' => 'PATCH',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'anywhereCacheId' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],
          ]
        ]
    );
    $this->bucketAccessControls = new Storage\Resource\BucketAccessControls(
        $this,
        $this->serviceName,
        'bucketAccessControls',
        [
          'methods' => [
            'delete' => [
              'path' => 'b/{bucket}/acl/{entity}',
              'httpMethod' => 'DELETE',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'entity' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'get' => [
              'path' => 'b/{bucket}/acl/{entity}',
              'httpMethod' => 'GET',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'entity' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'insert' => [
              'path' => 'b/{bucket}/acl',
              'httpMethod' => 'POST',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'list' => [
              'path' => 'b/{bucket}/acl',
              'httpMethod' => 'GET',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'patch' => [
              'path' => 'b/{bucket}/acl/{entity}',
              'httpMethod' => 'PATCH',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'entity' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'update' => [
              'path' => 'b/{bucket}/acl/{entity}',
              'httpMethod' => 'PUT',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'entity' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],
          ]
        ]
    );
    $this->buckets = new Storage\Resource\Buckets(
        $this,
        $this->serviceName,
        'buckets',
        [
          'methods' => [
            'delete' => [
              'path' => 'b/{bucket}',
              'httpMethod' => 'DELETE',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'ifMetagenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifMetagenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'get' => [
              'path' => 'b/{bucket}',
              'httpMethod' => 'GET',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'generation' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifMetagenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifMetagenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'projection' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'softDeleted' => [
                  'location' => 'query',
                  'type' => 'boolean',
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'getIamPolicy' => [
              'path' => 'b/{bucket}/iam',
              'httpMethod' => 'GET',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'optionsRequestedPolicyVersion' => [
                  'location' => 'query',
                  'type' => 'integer',
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'getStorageLayout' => [
              'path' => 'b/{bucket}/storageLayout',
              'httpMethod' => 'GET',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'prefix' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'insert' => [
              'path' => 'b',
              'httpMethod' => 'POST',
              'parameters' => [
                'project' => [
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ],
                'enableObjectRetention' => [
                  'location' => 'query',
                  'type' => 'boolean',
                ],
                'predefinedAcl' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'predefinedDefaultObjectAcl' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'projection' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'list' => [
              'path' => 'b',
              'httpMethod' => 'GET',
              'parameters' => [
                'project' => [
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ],
                'maxResults' => [
                  'location' => 'query',
                  'type' => 'integer',
                ],
                'pageToken' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'prefix' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'projection' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'softDeleted' => [
                  'location' => 'query',
                  'type' => 'boolean',
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'lockRetentionPolicy' => [
              'path' => 'b/{bucket}/lockRetentionPolicy',
              'httpMethod' => 'POST',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'ifMetagenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'patch' => [
              'path' => 'b/{bucket}',
              'httpMethod' => 'PATCH',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'ifMetagenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifMetagenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'predefinedAcl' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'predefinedDefaultObjectAcl' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'projection' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'relocate' => [
              'path' => 'b/{bucket}/relocate',
              'httpMethod' => 'POST',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'restore' => [
              'path' => 'b/{bucket}/restore',
              'httpMethod' => 'POST',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'generation' => [
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ],
                'projection' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'setIamPolicy' => [
              'path' => 'b/{bucket}/iam',
              'httpMethod' => 'PUT',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'testIamPermissions' => [
              'path' => 'b/{bucket}/iam/testPermissions',
              'httpMethod' => 'GET',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'permissions' => [
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                  'required' => true,
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'update' => [
              'path' => 'b/{bucket}',
              'httpMethod' => 'PUT',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'ifMetagenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifMetagenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'predefinedAcl' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'predefinedDefaultObjectAcl' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'projection' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],
          ]
        ]
    );
    $this->channels = new Storage\Resource\Channels(
        $this,
        $this->serviceName,
        'channels',
        [
          'methods' => [
            'stop' => [
              'path' => 'channels/stop',
              'httpMethod' => 'POST',
              'parameters' => [],
            ],
          ]
        ]
    );
    $this->defaultObjectAccessControls = new Storage\Resource\DefaultObjectAccessControls(
        $this,
        $this->serviceName,
        'defaultObjectAccessControls',
        [
          'methods' => [
            'delete' => [
              'path' => 'b/{bucket}/defaultObjectAcl/{entity}',
              'httpMethod' => 'DELETE',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'entity' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'get' => [
              'path' => 'b/{bucket}/defaultObjectAcl/{entity}',
              'httpMethod' => 'GET',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'entity' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'insert' => [
              'path' => 'b/{bucket}/defaultObjectAcl',
              'httpMethod' => 'POST',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'list' => [
              'path' => 'b/{bucket}/defaultObjectAcl',
              'httpMethod' => 'GET',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'ifMetagenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifMetagenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'patch' => [
              'path' => 'b/{bucket}/defaultObjectAcl/{entity}',
              'httpMethod' => 'PATCH',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'entity' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'update' => [
              'path' => 'b/{bucket}/defaultObjectAcl/{entity}',
              'httpMethod' => 'PUT',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'entity' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],
          ]
        ]
    );
    $this->folders = new Storage\Resource\Folders(
        $this,
        $this->serviceName,
        'folders',
        [
          'methods' => [
            'delete' => [
              'path' => 'b/{bucket}/folders/{folder}',
              'httpMethod' => 'DELETE',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'folder' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'ifMetagenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifMetagenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'get' => [
              'path' => 'b/{bucket}/folders/{folder}',
              'httpMethod' => 'GET',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'folder' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'ifMetagenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifMetagenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'insert' => [
              'path' => 'b/{bucket}/folders',
              'httpMethod' => 'POST',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'recursive' => [
                  'location' => 'query',
                  'type' => 'boolean',
                ],
              ],
            ],'list' => [
              'path' => 'b/{bucket}/folders',
              'httpMethod' => 'GET',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'delimiter' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'endOffset' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'pageSize' => [
                  'location' => 'query',
                  'type' => 'integer',
                ],
                'pageToken' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'prefix' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'startOffset' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'rename' => [
              'path' => 'b/{bucket}/folders/{sourceFolder}/renameTo/folders/{destinationFolder}',
              'httpMethod' => 'POST',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'sourceFolder' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'destinationFolder' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'ifSourceMetagenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifSourceMetagenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],
          ]
        ]
    );
    $this->managedFolders = new Storage\Resource\ManagedFolders(
        $this,
        $this->serviceName,
        'managedFolders',
        [
          'methods' => [
            'delete' => [
              'path' => 'b/{bucket}/managedFolders/{managedFolder}',
              'httpMethod' => 'DELETE',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'managedFolder' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'allowNonEmpty' => [
                  'location' => 'query',
                  'type' => 'boolean',
                ],
                'ifMetagenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifMetagenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'get' => [
              'path' => 'b/{bucket}/managedFolders/{managedFolder}',
              'httpMethod' => 'GET',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'managedFolder' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'ifMetagenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifMetagenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'getIamPolicy' => [
              'path' => 'b/{bucket}/managedFolders/{managedFolder}/iam',
              'httpMethod' => 'GET',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'managedFolder' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'optionsRequestedPolicyVersion' => [
                  'location' => 'query',
                  'type' => 'integer',
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'insert' => [
              'path' => 'b/{bucket}/managedFolders',
              'httpMethod' => 'POST',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'list' => [
              'path' => 'b/{bucket}/managedFolders',
              'httpMethod' => 'GET',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'pageSize' => [
                  'location' => 'query',
                  'type' => 'integer',
                ],
                'pageToken' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'prefix' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'setIamPolicy' => [
              'path' => 'b/{bucket}/managedFolders/{managedFolder}/iam',
              'httpMethod' => 'PUT',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'managedFolder' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'testIamPermissions' => [
              'path' => 'b/{bucket}/managedFolders/{managedFolder}/iam/testPermissions',
              'httpMethod' => 'GET',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'managedFolder' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'permissions' => [
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                  'required' => true,
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],
          ]
        ]
    );
    $this->notifications = new Storage\Resource\Notifications(
        $this,
        $this->serviceName,
        'notifications',
        [
          'methods' => [
            'delete' => [
              'path' => 'b/{bucket}/notificationConfigs/{notification}',
              'httpMethod' => 'DELETE',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'notification' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'get' => [
              'path' => 'b/{bucket}/notificationConfigs/{notification}',
              'httpMethod' => 'GET',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'notification' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'insert' => [
              'path' => 'b/{bucket}/notificationConfigs',
              'httpMethod' => 'POST',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'list' => [
              'path' => 'b/{bucket}/notificationConfigs',
              'httpMethod' => 'GET',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],
          ]
        ]
    );
    $this->objectAccessControls = new Storage\Resource\ObjectAccessControls(
        $this,
        $this->serviceName,
        'objectAccessControls',
        [
          'methods' => [
            'delete' => [
              'path' => 'b/{bucket}/o/{object}/acl/{entity}',
              'httpMethod' => 'DELETE',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'object' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'entity' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'generation' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'get' => [
              'path' => 'b/{bucket}/o/{object}/acl/{entity}',
              'httpMethod' => 'GET',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'object' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'entity' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'generation' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'insert' => [
              'path' => 'b/{bucket}/o/{object}/acl',
              'httpMethod' => 'POST',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'object' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'generation' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'list' => [
              'path' => 'b/{bucket}/o/{object}/acl',
              'httpMethod' => 'GET',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'object' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'generation' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'patch' => [
              'path' => 'b/{bucket}/o/{object}/acl/{entity}',
              'httpMethod' => 'PATCH',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'object' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'entity' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'generation' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'update' => [
              'path' => 'b/{bucket}/o/{object}/acl/{entity}',
              'httpMethod' => 'PUT',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'object' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'entity' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'generation' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],
          ]
        ]
    );
    $this->objects = new Storage\Resource\Objects(
        $this,
        $this->serviceName,
        'objects',
        [
          'methods' => [
            'bulkRestore' => [
              'path' => 'b/{bucket}/o/bulkRestore',
              'httpMethod' => 'POST',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'compose' => [
              'path' => 'b/{destinationBucket}/o/{destinationObject}/compose',
              'httpMethod' => 'POST',
              'parameters' => [
                'destinationBucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'destinationObject' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'destinationPredefinedAcl' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifGenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifMetagenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'kmsKeyName' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'copy' => [
              'path' => 'b/{sourceBucket}/o/{sourceObject}/copyTo/b/{destinationBucket}/o/{destinationObject}',
              'httpMethod' => 'POST',
              'parameters' => [
                'sourceBucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'sourceObject' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'destinationBucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'destinationObject' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'destinationKmsKeyName' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'destinationPredefinedAcl' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifGenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifGenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifMetagenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifMetagenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifSourceGenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifSourceGenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifSourceMetagenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifSourceMetagenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'projection' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'sourceGeneration' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'delete' => [
              'path' => 'b/{bucket}/o/{object}',
              'httpMethod' => 'DELETE',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'object' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'generation' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifGenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifGenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifMetagenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifMetagenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'get' => [
              'path' => 'b/{bucket}/o/{object}',
              'httpMethod' => 'GET',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'object' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'generation' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifGenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifGenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifMetagenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifMetagenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'projection' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'restoreToken' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'softDeleted' => [
                  'location' => 'query',
                  'type' => 'boolean',
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'getIamPolicy' => [
              'path' => 'b/{bucket}/o/{object}/iam',
              'httpMethod' => 'GET',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'object' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'generation' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'insert' => [
              'path' => 'b/{bucket}/o',
              'httpMethod' => 'POST',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'contentEncoding' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifGenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifGenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifMetagenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifMetagenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'kmsKeyName' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'name' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'predefinedAcl' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'projection' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'list' => [
              'path' => 'b/{bucket}/o',
              'httpMethod' => 'GET',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'delimiter' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'endOffset' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'includeFoldersAsPrefixes' => [
                  'location' => 'query',
                  'type' => 'boolean',
                ],
                'includeTrailingDelimiter' => [
                  'location' => 'query',
                  'type' => 'boolean',
                ],
                'matchGlob' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'maxResults' => [
                  'location' => 'query',
                  'type' => 'integer',
                ],
                'pageToken' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'prefix' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'projection' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'softDeleted' => [
                  'location' => 'query',
                  'type' => 'boolean',
                ],
                'startOffset' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'versions' => [
                  'location' => 'query',
                  'type' => 'boolean',
                ],
              ],
            ],'move' => [
              'path' => 'b/{bucket}/o/{sourceObject}/moveTo/o/{destinationObject}',
              'httpMethod' => 'POST',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'sourceObject' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'destinationObject' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'ifGenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifGenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifMetagenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifMetagenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifSourceGenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifSourceGenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifSourceMetagenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifSourceMetagenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'patch' => [
              'path' => 'b/{bucket}/o/{object}',
              'httpMethod' => 'PATCH',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'object' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'generation' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifGenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifGenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifMetagenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifMetagenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'overrideUnlockedRetention' => [
                  'location' => 'query',
                  'type' => 'boolean',
                ],
                'predefinedAcl' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'projection' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'restore' => [
              'path' => 'b/{bucket}/o/{object}/restore',
              'httpMethod' => 'POST',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'object' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'generation' => [
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ],
                'copySourceAcl' => [
                  'location' => 'query',
                  'type' => 'boolean',
                ],
                'ifGenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifGenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifMetagenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifMetagenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'projection' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'restoreToken' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'rewrite' => [
              'path' => 'b/{sourceBucket}/o/{sourceObject}/rewriteTo/b/{destinationBucket}/o/{destinationObject}',
              'httpMethod' => 'POST',
              'parameters' => [
                'sourceBucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'sourceObject' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'destinationBucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'destinationObject' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'destinationKmsKeyName' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'destinationPredefinedAcl' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifGenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifGenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifMetagenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifMetagenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifSourceGenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifSourceGenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifSourceMetagenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifSourceMetagenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'maxBytesRewrittenPerCall' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'projection' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'rewriteToken' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'sourceGeneration' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'setIamPolicy' => [
              'path' => 'b/{bucket}/o/{object}/iam',
              'httpMethod' => 'PUT',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'object' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'generation' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'testIamPermissions' => [
              'path' => 'b/{bucket}/o/{object}/iam/testPermissions',
              'httpMethod' => 'GET',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'object' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'permissions' => [
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                  'required' => true,
                ],
                'generation' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'update' => [
              'path' => 'b/{bucket}/o/{object}',
              'httpMethod' => 'PUT',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'object' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'generation' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifGenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifGenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifMetagenerationMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'ifMetagenerationNotMatch' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'overrideUnlockedRetention' => [
                  'location' => 'query',
                  'type' => 'boolean',
                ],
                'predefinedAcl' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'projection' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'watchAll' => [
              'path' => 'b/{bucket}/o/watch',
              'httpMethod' => 'POST',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'delimiter' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'endOffset' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'includeTrailingDelimiter' => [
                  'location' => 'query',
                  'type' => 'boolean',
                ],
                'maxResults' => [
                  'location' => 'query',
                  'type' => 'integer',
                ],
                'pageToken' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'prefix' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'projection' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'startOffset' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'versions' => [
                  'location' => 'query',
                  'type' => 'boolean',
                ],
              ],
            ],
          ]
        ]
    );
    $this->operations = new Storage\Resource\Operations(
        $this,
        $this->serviceName,
        'operations',
        [
          'methods' => [
            'advanceRelocateBucket' => [
              'path' => 'b/{bucket}/operations/{operationId}/advanceRelocateBucket',
              'httpMethod' => 'POST',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'operationId' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'cancel' => [
              'path' => 'b/{bucket}/operations/{operationId}/cancel',
              'httpMethod' => 'POST',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'operationId' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'get' => [
              'path' => 'b/{bucket}/operations/{operationId}',
              'httpMethod' => 'GET',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'operationId' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'list' => [
              'path' => 'b/{bucket}/operations',
              'httpMethod' => 'GET',
              'parameters' => [
                'bucket' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'filter' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'pageSize' => [
                  'location' => 'query',
                  'type' => 'integer',
                ],
                'pageToken' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],
          ]
        ]
    );
    $this->projects_hmacKeys = new Storage\Resource\ProjectsHmacKeys(
        $this,
        $this->serviceName,
        'hmacKeys',
        [
          'methods' => [
            'create' => [
              'path' => 'projects/{projectId}/hmacKeys',
              'httpMethod' => 'POST',
              'parameters' => [
                'projectId' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'serviceAccountEmail' => [
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'delete' => [
              'path' => 'projects/{projectId}/hmacKeys/{accessId}',
              'httpMethod' => 'DELETE',
              'parameters' => [
                'projectId' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'accessId' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'get' => [
              'path' => 'projects/{projectId}/hmacKeys/{accessId}',
              'httpMethod' => 'GET',
              'parameters' => [
                'projectId' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'accessId' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'list' => [
              'path' => 'projects/{projectId}/hmacKeys',
              'httpMethod' => 'GET',
              'parameters' => [
                'projectId' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'maxResults' => [
                  'location' => 'query',
                  'type' => 'integer',
                ],
                'pageToken' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'serviceAccountEmail' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'showDeletedKeys' => [
                  'location' => 'query',
                  'type' => 'boolean',
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'update' => [
              'path' => 'projects/{projectId}/hmacKeys/{accessId}',
              'httpMethod' => 'PUT',
              'parameters' => [
                'projectId' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'accessId' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],
          ]
        ]
    );
    $this->projects_serviceAccount = new Storage\Resource\ProjectsServiceAccount(
        $this,
        $this->serviceName,
        'serviceAccount',
        [
          'methods' => [
            'get' => [
              'path' => 'projects/{projectId}/serviceAccount',
              'httpMethod' => 'GET',
              'parameters' => [
                'projectId' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'userProject' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],
          ]
        ]
    );
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Storage::class, 'Google_Service_Storage');
