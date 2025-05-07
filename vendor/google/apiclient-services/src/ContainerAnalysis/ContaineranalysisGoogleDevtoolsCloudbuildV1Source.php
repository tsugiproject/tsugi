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

namespace Google\Service\ContainerAnalysis;

class ContaineranalysisGoogleDevtoolsCloudbuildV1Source extends \Google\Model
{
  protected $connectedRepositoryType = ContaineranalysisGoogleDevtoolsCloudbuildV1ConnectedRepository::class;
  protected $connectedRepositoryDataType = '';
  protected $developerConnectConfigType = ContaineranalysisGoogleDevtoolsCloudbuildV1DeveloperConnectConfig::class;
  protected $developerConnectConfigDataType = '';
  protected $gitSourceType = ContaineranalysisGoogleDevtoolsCloudbuildV1GitSource::class;
  protected $gitSourceDataType = '';
  protected $repoSourceType = ContaineranalysisGoogleDevtoolsCloudbuildV1RepoSource::class;
  protected $repoSourceDataType = '';
  protected $storageSourceType = ContaineranalysisGoogleDevtoolsCloudbuildV1StorageSource::class;
  protected $storageSourceDataType = '';
  protected $storageSourceManifestType = ContaineranalysisGoogleDevtoolsCloudbuildV1StorageSourceManifest::class;
  protected $storageSourceManifestDataType = '';

  /**
   * @param ContaineranalysisGoogleDevtoolsCloudbuildV1ConnectedRepository
   */
  public function setConnectedRepository(ContaineranalysisGoogleDevtoolsCloudbuildV1ConnectedRepository $connectedRepository)
  {
    $this->connectedRepository = $connectedRepository;
  }
  /**
   * @return ContaineranalysisGoogleDevtoolsCloudbuildV1ConnectedRepository
   */
  public function getConnectedRepository()
  {
    return $this->connectedRepository;
  }
  /**
   * @param ContaineranalysisGoogleDevtoolsCloudbuildV1DeveloperConnectConfig
   */
  public function setDeveloperConnectConfig(ContaineranalysisGoogleDevtoolsCloudbuildV1DeveloperConnectConfig $developerConnectConfig)
  {
    $this->developerConnectConfig = $developerConnectConfig;
  }
  /**
   * @return ContaineranalysisGoogleDevtoolsCloudbuildV1DeveloperConnectConfig
   */
  public function getDeveloperConnectConfig()
  {
    return $this->developerConnectConfig;
  }
  /**
   * @param ContaineranalysisGoogleDevtoolsCloudbuildV1GitSource
   */
  public function setGitSource(ContaineranalysisGoogleDevtoolsCloudbuildV1GitSource $gitSource)
  {
    $this->gitSource = $gitSource;
  }
  /**
   * @return ContaineranalysisGoogleDevtoolsCloudbuildV1GitSource
   */
  public function getGitSource()
  {
    return $this->gitSource;
  }
  /**
   * @param ContaineranalysisGoogleDevtoolsCloudbuildV1RepoSource
   */
  public function setRepoSource(ContaineranalysisGoogleDevtoolsCloudbuildV1RepoSource $repoSource)
  {
    $this->repoSource = $repoSource;
  }
  /**
   * @return ContaineranalysisGoogleDevtoolsCloudbuildV1RepoSource
   */
  public function getRepoSource()
  {
    return $this->repoSource;
  }
  /**
   * @param ContaineranalysisGoogleDevtoolsCloudbuildV1StorageSource
   */
  public function setStorageSource(ContaineranalysisGoogleDevtoolsCloudbuildV1StorageSource $storageSource)
  {
    $this->storageSource = $storageSource;
  }
  /**
   * @return ContaineranalysisGoogleDevtoolsCloudbuildV1StorageSource
   */
  public function getStorageSource()
  {
    return $this->storageSource;
  }
  /**
   * @param ContaineranalysisGoogleDevtoolsCloudbuildV1StorageSourceManifest
   */
  public function setStorageSourceManifest(ContaineranalysisGoogleDevtoolsCloudbuildV1StorageSourceManifest $storageSourceManifest)
  {
    $this->storageSourceManifest = $storageSourceManifest;
  }
  /**
   * @return ContaineranalysisGoogleDevtoolsCloudbuildV1StorageSourceManifest
   */
  public function getStorageSourceManifest()
  {
    return $this->storageSourceManifest;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ContaineranalysisGoogleDevtoolsCloudbuildV1Source::class, 'Google_Service_ContainerAnalysis_ContaineranalysisGoogleDevtoolsCloudbuildV1Source');
