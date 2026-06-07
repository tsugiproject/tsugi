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

namespace Google\Service\WebRisk\Resource;

use Google\Service\WebRisk\GoogleCloudWebriskV1Submission;

/**
 * The "submissions" collection of methods.
 * Typical usage is:
 *  <code>
 *   $webriskService = new Google\Service\WebRisk(...);
 *   $submissions = $webriskService->projects_submissions;
 *  </code>
 */
class ProjectsSubmissions extends \Google\Service\Resource
{
  /**
   * Creates a Submission of a URI suspected of containing phishing content for
   * review. If the review confirms malicious phishing content, Google adds the
   * site to [Google's Social Engineering
   * lists](https://support.google.com/webmasters/answer/6350487/) to help protect
   * users. Only allowlisted projects can use this method during Early Access. To
   * obtain access, contact Sales or your customer engineer. (submissions.create)
   *
   * @param string $parent Required. The name of the project that is making the
   * submission. This string is in the format "projects/{project_number}".
   * @param GoogleCloudWebriskV1Submission $postBody
   * @param array $optParams Optional parameters.
   * @return GoogleCloudWebriskV1Submission
   * @throws \Google\Service\Exception
   */
  public function create($parent, GoogleCloudWebriskV1Submission $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('create', [$params], GoogleCloudWebriskV1Submission::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsSubmissions::class, 'Google_Service_WebRisk_Resource_ProjectsSubmissions');
