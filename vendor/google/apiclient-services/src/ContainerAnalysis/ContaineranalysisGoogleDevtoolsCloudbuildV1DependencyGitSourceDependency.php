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

class ContaineranalysisGoogleDevtoolsCloudbuildV1DependencyGitSourceDependency extends \Google\Model
{
  /**
   * @var string
   */
  public $depth;
  /**
   * @var string
   */
  public $destPath;
  /**
   * @var bool
   */
  public $recurseSubmodules;
  protected $repositoryType = ContaineranalysisGoogleDevtoolsCloudbuildV1DependencyGitSourceRepository::class;
  protected $repositoryDataType = '';
  /**
   * @var string
   */
  public $revision;

  /**
   * @param string
   */
  public function setDepth($depth)
  {
    $this->depth = $depth;
  }
  /**
   * @return string
   */
  public function getDepth()
  {
    return $this->depth;
  }
  /**
   * @param string
   */
  public function setDestPath($destPath)
  {
    $this->destPath = $destPath;
  }
  /**
   * @return string
   */
  public function getDestPath()
  {
    return $this->destPath;
  }
  /**
   * @param bool
   */
  public function setRecurseSubmodules($recurseSubmodules)
  {
    $this->recurseSubmodules = $recurseSubmodules;
  }
  /**
   * @return bool
   */
  public function getRecurseSubmodules()
  {
    return $this->recurseSubmodules;
  }
  /**
   * @param ContaineranalysisGoogleDevtoolsCloudbuildV1DependencyGitSourceRepository
   */
  public function setRepository(ContaineranalysisGoogleDevtoolsCloudbuildV1DependencyGitSourceRepository $repository)
  {
    $this->repository = $repository;
  }
  /**
   * @return ContaineranalysisGoogleDevtoolsCloudbuildV1DependencyGitSourceRepository
   */
  public function getRepository()
  {
    return $this->repository;
  }
  /**
   * @param string
   */
  public function setRevision($revision)
  {
    $this->revision = $revision;
  }
  /**
   * @return string
   */
  public function getRevision()
  {
    return $this->revision;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ContaineranalysisGoogleDevtoolsCloudbuildV1DependencyGitSourceDependency::class, 'Google_Service_ContainerAnalysis_ContaineranalysisGoogleDevtoolsCloudbuildV1DependencyGitSourceDependency');
