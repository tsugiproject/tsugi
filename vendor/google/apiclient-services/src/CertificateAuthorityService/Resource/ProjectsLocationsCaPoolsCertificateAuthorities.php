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

namespace Google\Service\CertificateAuthorityService\Resource;

use Google\Service\CertificateAuthorityService\ActivateCertificateAuthorityRequest;
use Google\Service\CertificateAuthorityService\CertificateAuthority;
use Google\Service\CertificateAuthorityService\DisableCertificateAuthorityRequest;
use Google\Service\CertificateAuthorityService\EnableCertificateAuthorityRequest;
use Google\Service\CertificateAuthorityService\FetchCertificateAuthorityCsrResponse;
use Google\Service\CertificateAuthorityService\ListCertificateAuthoritiesResponse;
use Google\Service\CertificateAuthorityService\Operation;
use Google\Service\CertificateAuthorityService\UndeleteCertificateAuthorityRequest;

/**
 * The "certificateAuthorities" collection of methods.
 * Typical usage is:
 *  <code>
 *   $privatecaService = new Google\Service\CertificateAuthorityService(...);
 *   $certificateAuthorities = $privatecaService->projects_locations_caPools_certificateAuthorities;
 *  </code>
 */
class ProjectsLocationsCaPoolsCertificateAuthorities extends \Google\Service\Resource
{
  /**
   * Activate a CertificateAuthority that is in state AWAITING_USER_ACTIVATION and
   * is of type SUBORDINATE. After the parent Certificate Authority signs a
   * certificate signing request from FetchCertificateAuthorityCsr, this method
   * can complete the activation process. (certificateAuthorities.activate)
   *
   * @param string $name Required. The resource name for this CertificateAuthority
   * in the format `projects/locations/caPools/certificateAuthorities`.
   * @param ActivateCertificateAuthorityRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function activate($name, ActivateCertificateAuthorityRequest $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('activate', [$params], Operation::class);
  }
  /**
   * Create a new CertificateAuthority in a given Project and Location.
   * (certificateAuthorities.create)
   *
   * @param string $parent Required. The resource name of the CaPool associated
   * with the CertificateAuthorities, in the format `projects/locations/caPools`.
   * @param CertificateAuthority $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string certificateAuthorityId Required. It must be unique within a
   * location and match the regular expression `[a-zA-Z0-9_-]{1,63}`
   * @opt_param string requestId Optional. An ID to identify requests. Specify a
   * unique request ID so that if you must retry your request, the server will
   * know to ignore the request if it has already been completed. The server will
   * guarantee that for at least 60 minutes since the first request. For example,
   * consider a situation where you make an initial request and the request times
   * out. If you make the request again with the same request ID, the server can
   * check if original operation with the same request ID was received, and if so,
   * will ignore the second request. This prevents clients from accidentally
   * creating duplicate commitments. The request ID must be a valid UUID with the
   * exception that zero UUID is not supported
   * (00000000-0000-0000-0000-000000000000).
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function create($parent, CertificateAuthority $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('create', [$params], Operation::class);
  }
  /**
   * Delete a CertificateAuthority. (certificateAuthorities.delete)
   *
   * @param string $name Required. The resource name for this CertificateAuthority
   * in the format `projects/locations/caPools/certificateAuthorities`.
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool ignoreActiveCertificates Optional. This field allows the CA
   * to be deleted even if the CA has active certs. Active certs include both
   * unrevoked and unexpired certs.
   * @opt_param bool ignoreDependentResources Optional. This field allows this CA
   * to be deleted even if it's being depended on by another resource. However,
   * doing so may result in unintended and unrecoverable effects on any dependent
   * resources since the CA will no longer be able to issue certificates.
   * @opt_param string requestId Optional. An ID to identify requests. Specify a
   * unique request ID so that if you must retry your request, the server will
   * know to ignore the request if it has already been completed. The server will
   * guarantee that for at least 60 minutes since the first request. For example,
   * consider a situation where you make an initial request and the request times
   * out. If you make the request again with the same request ID, the server can
   * check if original operation with the same request ID was received, and if so,
   * will ignore the second request. This prevents clients from accidentally
   * creating duplicate commitments. The request ID must be a valid UUID with the
   * exception that zero UUID is not supported
   * (00000000-0000-0000-0000-000000000000).
   * @opt_param bool skipGracePeriod Optional. If this flag is set, the
   * Certificate Authority will be deleted as soon as possible without a 30-day
   * grace period where undeletion would have been allowed. If you proceed, there
   * will be no way to recover this CA.
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
   * Disable a CertificateAuthority. (certificateAuthorities.disable)
   *
   * @param string $name Required. The resource name for this CertificateAuthority
   * in the format `projects/locations/caPools/certificateAuthorities`.
   * @param DisableCertificateAuthorityRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function disable($name, DisableCertificateAuthorityRequest $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('disable', [$params], Operation::class);
  }
  /**
   * Enable a CertificateAuthority. (certificateAuthorities.enable)
   *
   * @param string $name Required. The resource name for this CertificateAuthority
   * in the format `projects/locations/caPools/certificateAuthorities`.
   * @param EnableCertificateAuthorityRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function enable($name, EnableCertificateAuthorityRequest $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('enable', [$params], Operation::class);
  }
  /**
   * Fetch a certificate signing request (CSR) from a CertificateAuthority that is
   * in state AWAITING_USER_ACTIVATION and is of type SUBORDINATE. The CSR must
   * then be signed by the desired parent Certificate Authority, which could be
   * another CertificateAuthority resource, or could be an on-prem certificate
   * authority. See also ActivateCertificateAuthority.
   * (certificateAuthorities.fetch)
   *
   * @param string $name Required. The resource name for this CertificateAuthority
   * in the format `projects/locations/caPools/certificateAuthorities`.
   * @param array $optParams Optional parameters.
   * @return FetchCertificateAuthorityCsrResponse
   * @throws \Google\Service\Exception
   */
  public function fetch($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('fetch', [$params], FetchCertificateAuthorityCsrResponse::class);
  }
  /**
   * Returns a CertificateAuthority. (certificateAuthorities.get)
   *
   * @param string $name Required. The name of the CertificateAuthority to get.
   * @param array $optParams Optional parameters.
   * @return CertificateAuthority
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], CertificateAuthority::class);
  }
  /**
   * Lists CertificateAuthorities.
   * (certificateAuthorities.listProjectsLocationsCaPoolsCertificateAuthorities)
   *
   * @param string $parent Required. The resource name of the CaPool associated
   * with the CertificateAuthorities, in the format `projects/locations/caPools`.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Optional. Only include resources that match the
   * filter in the response.
   * @opt_param string orderBy Optional. Specify how the results should be sorted.
   * @opt_param int pageSize Optional. Limit on the number of
   * CertificateAuthorities to include in the response. Further
   * CertificateAuthorities can subsequently be obtained by including the
   * ListCertificateAuthoritiesResponse.next_page_token in a subsequent request.
   * If unspecified, the server will pick an appropriate default.
   * @opt_param string pageToken Optional. Pagination token, returned earlier via
   * ListCertificateAuthoritiesResponse.next_page_token.
   * @return ListCertificateAuthoritiesResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsLocationsCaPoolsCertificateAuthorities($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListCertificateAuthoritiesResponse::class);
  }
  /**
   * Update a CertificateAuthority. (certificateAuthorities.patch)
   *
   * @param string $name Identifier. The resource name for this
   * CertificateAuthority in the format
   * `projects/locations/caPools/certificateAuthorities`.
   * @param CertificateAuthority $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string requestId Optional. An ID to identify requests. Specify a
   * unique request ID so that if you must retry your request, the server will
   * know to ignore the request if it has already been completed. The server will
   * guarantee that for at least 60 minutes since the first request. For example,
   * consider a situation where you make an initial request and the request times
   * out. If you make the request again with the same request ID, the server can
   * check if original operation with the same request ID was received, and if so,
   * will ignore the second request. This prevents clients from accidentally
   * creating duplicate commitments. The request ID must be a valid UUID with the
   * exception that zero UUID is not supported
   * (00000000-0000-0000-0000-000000000000).
   * @opt_param string updateMask Required. A list of fields to be updated in this
   * request.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function patch($name, CertificateAuthority $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('patch', [$params], Operation::class);
  }
  /**
   * Undelete a CertificateAuthority that has been deleted.
   * (certificateAuthorities.undelete)
   *
   * @param string $name Required. The resource name for this CertificateAuthority
   * in the format `projects/locations/caPools/certificateAuthorities`.
   * @param UndeleteCertificateAuthorityRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function undelete($name, UndeleteCertificateAuthorityRequest $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('undelete', [$params], Operation::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsCaPoolsCertificateAuthorities::class, 'Google_Service_CertificateAuthorityService_Resource_ProjectsLocationsCaPoolsCertificateAuthorities');
