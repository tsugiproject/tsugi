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

namespace Google\Service\CloudDomains\Resource;

use Google\Service\CloudDomains\AuthorizationCode;
use Google\Service\CloudDomains\ConfigureContactSettingsRequest;
use Google\Service\CloudDomains\ConfigureDnsSettingsRequest;
use Google\Service\CloudDomains\ConfigureManagementSettingsRequest;
use Google\Service\CloudDomains\ExportRegistrationRequest;
use Google\Service\CloudDomains\ImportDomainRequest;
use Google\Service\CloudDomains\InitiatePushTransferRequest;
use Google\Service\CloudDomains\ListRegistrationsResponse;
use Google\Service\CloudDomains\Operation;
use Google\Service\CloudDomains\Policy;
use Google\Service\CloudDomains\RegisterDomainRequest;
use Google\Service\CloudDomains\Registration;
use Google\Service\CloudDomains\RenewDomainRequest;
use Google\Service\CloudDomains\ResetAuthorizationCodeRequest;
use Google\Service\CloudDomains\RetrieveGoogleDomainsDnsRecordsResponse;
use Google\Service\CloudDomains\RetrieveGoogleDomainsForwardingConfigResponse;
use Google\Service\CloudDomains\RetrieveImportableDomainsResponse;
use Google\Service\CloudDomains\RetrieveRegisterParametersResponse;
use Google\Service\CloudDomains\RetrieveTransferParametersResponse;
use Google\Service\CloudDomains\SearchDomainsResponse;
use Google\Service\CloudDomains\SetIamPolicyRequest;
use Google\Service\CloudDomains\TestIamPermissionsRequest;
use Google\Service\CloudDomains\TestIamPermissionsResponse;
use Google\Service\CloudDomains\TransferDomainRequest;

/**
 * The "registrations" collection of methods.
 * Typical usage is:
 *  <code>
 *   $domainsService = new Google\Service\CloudDomains(...);
 *   $registrations = $domainsService->projects_locations_registrations;
 *  </code>
 */
class ProjectsLocationsRegistrations extends \Google\Service\Resource
{
  /**
   * Updates a `Registration`'s contact settings. Some changes require
   * confirmation by the domain's registrant contact . Caution: Please consider
   * carefully any changes to contact privacy settings when changing from
   * `REDACTED_CONTACT_DATA` to `PUBLIC_CONTACT_DATA.` There may be a delay in
   * reflecting updates you make to registrant contact information such that any
   * changes you make to contact privacy (including from `REDACTED_CONTACT_DATA`
   * to `PUBLIC_CONTACT_DATA`) will be applied without delay but changes to
   * registrant contact information may take a limited time to be publicized. This
   * means that changes to contact privacy from `REDACTED_CONTACT_DATA` to
   * `PUBLIC_CONTACT_DATA` may make the previous registrant contact data public
   * until the modified registrant contact details are published.
   * (registrations.configureContactSettings)
   *
   * @param string $registration Required. The name of the `Registration` whose
   * contact settings are being updated, in the format
   * `projects/locations/registrations`.
   * @param ConfigureContactSettingsRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function configureContactSettings($registration, ConfigureContactSettingsRequest $postBody, $optParams = [])
  {
    $params = ['registration' => $registration, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('configureContactSettings', [$params], Operation::class);
  }
  /**
   * Updates a `Registration`'s DNS settings. (registrations.configureDnsSettings)
   *
   * @param string $registration Required. The name of the `Registration` whose
   * DNS settings are being updated, in the format
   * `projects/locations/registrations`.
   * @param ConfigureDnsSettingsRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function configureDnsSettings($registration, ConfigureDnsSettingsRequest $postBody, $optParams = [])
  {
    $params = ['registration' => $registration, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('configureDnsSettings', [$params], Operation::class);
  }
  /**
   * Updates a `Registration`'s management settings.
   * (registrations.configureManagementSettings)
   *
   * @param string $registration Required. The name of the `Registration` whose
   * management settings are being updated, in the format
   * `projects/locations/registrations`.
   * @param ConfigureManagementSettingsRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function configureManagementSettings($registration, ConfigureManagementSettingsRequest $postBody, $optParams = [])
  {
    $params = ['registration' => $registration, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('configureManagementSettings', [$params], Operation::class);
  }
  /**
   * Deletes a `Registration` resource. This method works on any `Registration`
   * resource using [Subscription or Commitment billing](/domains/pricing#billing-
   * models), provided that the resource was created at least 1 day in the past.
   * When an active registration is successfully deleted, you can continue to use
   * the domain in [Google Domains](https://domains.google/) until it expires. The
   * calling user becomes the domain's sole owner in Google Domains, and
   * permissions for the domain are subsequently managed there. The domain does
   * not renew automatically unless the new owner sets up billing in Google
   * Domains. After January 2024 you will only be able to delete `Registration`
   * resources when `state` is one of: `EXPORTED`, `EXPIRED`,`REGISTRATION_FAILED`
   * or `TRANSFER_FAILED`. See [Cloud Domains feature
   * deprecation](https://cloud.google.com/domains/docs/deprecations/feature-
   * deprecations) for more details. (registrations.delete)
   *
   * @param string $name Required. The name of the `Registration` to delete, in
   * the format `projects/locations/registrations`.
   * @param array $optParams Optional parameters.
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
   * Deprecated: For more information, see [Cloud Domains feature
   * deprecation](https://cloud.google.com/domains/docs/deprecations/feature-
   * deprecations) Exports a `Registration` resource, such that it is no longer
   * managed by Cloud Domains. When an active domain is successfully exported, you
   * can continue to use the domain in [Google Domains](https://domains.google/)
   * until it expires. The calling user becomes the domain's sole owner in Google
   * Domains, and permissions for the domain are subsequently managed there. The
   * domain does not renew automatically unless the new owner sets up billing in
   * Google Domains. (registrations.export)
   *
   * @param string $name Required. The name of the `Registration` to export, in
   * the format `projects/locations/registrations`.
   * @param ExportRegistrationRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function export($name, ExportRegistrationRequest $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('export', [$params], Operation::class);
  }
  /**
   * Gets the details of a `Registration` resource. (registrations.get)
   *
   * @param string $name Required. The name of the `Registration` to get, in the
   * format `projects/locations/registrations`.
   * @param array $optParams Optional parameters.
   * @return Registration
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], Registration::class);
  }
  /**
   * Gets the access control policy for a resource. Returns an empty policy if the
   * resource exists and does not have a policy set. (registrations.getIamPolicy)
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
   * Deprecated: For more information, see [Cloud Domains feature
   * deprecation](https://cloud.google.com/domains/docs/deprecations/feature-
   * deprecations) Imports a domain name from [Google
   * Domains](https://domains.google/) for use in Cloud Domains. To transfer a
   * domain from another registrar, use the `TransferDomain` method instead. Since
   * individual users can own domains in Google Domains, the calling user must
   * have ownership permission on the domain. (registrations.import)
   *
   * @param string $parent Required. The parent resource of the Registration. Must
   * be in the format `projects/locations`.
   * @param ImportDomainRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function import($parent, ImportDomainRequest $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('import', [$params], Operation::class);
  }
  /**
   * Initiates the `Push Transfer` process to transfer the domain to another
   * registrar. The process might complete instantly or might require confirmation
   * or additional work. Check the emails sent to the email address of the
   * registrant. The process is aborted after a timeout if it's not completed.
   * This method is only supported for domains that have the
   * `REQUIRE_PUSH_TRANSFER` property in the list of `domain_properties`. The
   * domain must also be unlocked before it can be transferred to a different
   * registrar. For more information, see [Transfer a registered domain to another
   * registrar](https://cloud.google.com/domains/docs/transfer-domain-to-another-
   * registrar). (registrations.initiatePushTransfer)
   *
   * @param string $registration Required. The name of the `Registration` for
   * which the push transfer is initiated, in the format
   * `projects/locations/registrations`.
   * @param InitiatePushTransferRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function initiatePushTransfer($registration, InitiatePushTransferRequest $postBody, $optParams = [])
  {
    $params = ['registration' => $registration, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('initiatePushTransfer', [$params], Operation::class);
  }
  /**
   * Lists the `Registration` resources in a project.
   * (registrations.listProjectsLocationsRegistrations)
   *
   * @param string $parent Required. The project and location from which to list
   * `Registration`s, specified in the format `projects/locations`.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Filter expression to restrict the `Registration`s
   * returned. The expression must specify the field name, a comparison operator,
   * and the value that you want to use for filtering. The value must be a string,
   * a number, a boolean, or an enum value. The comparison operator should be one
   * of =, !=, >, <, >=, <=, or : for prefix or wildcard matches. For example, to
   * filter to a specific domain name, use an expression like
   * `domainName="example.com"`. You can also check for the existence of a field;
   * for example, to find domains using custom DNS settings, use an expression
   * like `dnsSettings.customDns:*`. You can also create compound filters by
   * combining expressions with the `AND` and `OR` operators. For example, to find
   * domains that are suspended or have specific issues flagged, use an expression
   * like `(state=SUSPENDED) OR (issue:*)`.
   * @opt_param int pageSize Maximum number of results to return.
   * @opt_param string pageToken When set to the `next_page_token` from a prior
   * response, provides the next page of results.
   * @return ListRegistrationsResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsLocationsRegistrations($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListRegistrationsResponse::class);
  }
  /**
   * Updates select fields of a `Registration` resource, notably `labels`. To
   * update other fields, use the appropriate custom update method: * To update
   * management settings, see `ConfigureManagementSettings` * To update DNS
   * configuration, see `ConfigureDnsSettings` * To update contact information,
   * see `ConfigureContactSettings` (registrations.patch)
   *
   * @param string $name Output only. Name of the `Registration` resource, in the
   * format `projects/locations/registrations/`.
   * @param Registration $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string updateMask Required. The field mask describing which fields
   * to update as a comma-separated list. For example, if only the labels are
   * being updated, the `update_mask` is `"labels"`.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function patch($name, Registration $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('patch', [$params], Operation::class);
  }
  /**
   * Registers a new domain name and creates a corresponding `Registration`
   * resource. Call `RetrieveRegisterParameters` first to check availability of
   * the domain name and determine parameters like price that are needed to build
   * a call to this method. A successful call creates a `Registration` resource in
   * state `REGISTRATION_PENDING`, which resolves to `ACTIVE` within 1-2 minutes,
   * indicating that the domain was successfully registered. If the resource ends
   * up in state `REGISTRATION_FAILED`, it indicates that the domain was not
   * registered successfully, and you can safely delete the resource and retry
   * registration. (registrations.register)
   *
   * @param string $parent Required. The parent resource of the `Registration`.
   * Must be in the format `projects/locations`.
   * @param RegisterDomainRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function register($parent, RegisterDomainRequest $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('register', [$params], Operation::class);
  }
  /**
   * Renews a recently expired domain. This method can only be called on domains
   * that expired in the previous 30 days. After the renewal, the new expiration
   * time of the domain is one year after the old expiration time and you are
   * charged a `yearly_price` for the renewal. (registrations.renewDomain)
   *
   * @param string $registration Required. The name of the `Registration` whish is
   * being renewed, in the format `projects/locations/registrations`.
   * @param RenewDomainRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function renewDomain($registration, RenewDomainRequest $postBody, $optParams = [])
  {
    $params = ['registration' => $registration, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('renewDomain', [$params], Operation::class);
  }
  /**
   * Resets the authorization code of the `Registration` to a new random string.
   * You can call this method only after 60 days have elapsed since the initial
   * domain registration. Domains that have the `REQUIRE_PUSH_TRANSFER` property
   * in the list of `domain_properties` don't support authorization codes and must
   * use the `InitiatePushTransfer` method to initiate the process to transfer the
   * domain to a different registrar. (registrations.resetAuthorizationCode)
   *
   * @param string $registration Required. The name of the `Registration` whose
   * authorization code is being reset, in the format
   * `projects/locations/registrations`.
   * @param ResetAuthorizationCodeRequest $postBody
   * @param array $optParams Optional parameters.
   * @return AuthorizationCode
   * @throws \Google\Service\Exception
   */
  public function resetAuthorizationCode($registration, ResetAuthorizationCodeRequest $postBody, $optParams = [])
  {
    $params = ['registration' => $registration, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('resetAuthorizationCode', [$params], AuthorizationCode::class);
  }
  /**
   * Gets the authorization code of the `Registration` for the purpose of
   * transferring the domain to another registrar. You can call this method only
   * after 60 days have elapsed since the initial domain registration. Domains
   * that have the `REQUIRE_PUSH_TRANSFER` property in the list of
   * `domain_properties` don't support authorization codes and must use the
   * `InitiatePushTransfer` method to initiate the process to transfer the domain
   * to a different registrar. (registrations.retrieveAuthorizationCode)
   *
   * @param string $registration Required. The name of the `Registration` whose
   * authorization code is being retrieved, in the format
   * `projects/locations/registrations`.
   * @param array $optParams Optional parameters.
   * @return AuthorizationCode
   * @throws \Google\Service\Exception
   */
  public function retrieveAuthorizationCode($registration, $optParams = [])
  {
    $params = ['registration' => $registration];
    $params = array_merge($params, $optParams);
    return $this->call('retrieveAuthorizationCode', [$params], AuthorizationCode::class);
  }
  /**
   * Lists the DNS records from the Google Domains DNS zone for domains that use
   * the deprecated `google_domains_dns` in the `Registration`'s `dns_settings`.
   * (registrations.retrieveGoogleDomainsDnsRecords)
   *
   * @param string $registration Required. The name of the `Registration` whose
   * Google Domains DNS records details you are retrieving, in the format
   * `projects/locations/registrations`.
   * @param array $optParams Optional parameters.
   *
   * @opt_param int pageSize Optional. Maximum number of results to return.
   * @opt_param string pageToken Optional. When set to the `next_page_token` from
   * a prior response, provides the next page of results.
   * @return RetrieveGoogleDomainsDnsRecordsResponse
   * @throws \Google\Service\Exception
   */
  public function retrieveGoogleDomainsDnsRecords($registration, $optParams = [])
  {
    $params = ['registration' => $registration];
    $params = array_merge($params, $optParams);
    return $this->call('retrieveGoogleDomainsDnsRecords', [$params], RetrieveGoogleDomainsDnsRecordsResponse::class);
  }
  /**
   * Lists the deprecated domain and email forwarding configurations you set up in
   * the deprecated Google Domains UI. The configuration is present only for
   * domains with the `google_domains_redirects_data_available` set to `true` in
   * the `Registration`'s `dns_settings`. A forwarding configuration might not
   * work correctly if required DNS records are not present in the domain's
   * authoritative DNS Zone. (registrations.retrieveGoogleDomainsForwardingConfig)
   *
   * @param string $registration Required. The name of the `Registration` whose
   * Google Domains forwarding configuration details are being retrieved, in the
   * format `projects/locations/registrations`.
   * @param array $optParams Optional parameters.
   * @return RetrieveGoogleDomainsForwardingConfigResponse
   * @throws \Google\Service\Exception
   */
  public function retrieveGoogleDomainsForwardingConfig($registration, $optParams = [])
  {
    $params = ['registration' => $registration];
    $params = array_merge($params, $optParams);
    return $this->call('retrieveGoogleDomainsForwardingConfig', [$params], RetrieveGoogleDomainsForwardingConfigResponse::class);
  }
  /**
   * Deprecated: For more information, see [Cloud Domains feature
   * deprecation](https://cloud.google.com/domains/docs/deprecations/feature-
   * deprecations) Lists domain names from [Google
   * Domains](https://domains.google/) that can be imported to Cloud Domains using
   * the `ImportDomain` method. Since individual users can own domains in Google
   * Domains, the list of domains returned depends on the individual user making
   * the call. Domains already managed by Cloud Domains are not returned.
   * (registrations.retrieveImportableDomains)
   *
   * @param string $location Required. The location. Must be in the format
   * `projects/locations`.
   * @param array $optParams Optional parameters.
   *
   * @opt_param int pageSize Maximum number of results to return.
   * @opt_param string pageToken When set to the `next_page_token` from a prior
   * response, provides the next page of results.
   * @return RetrieveImportableDomainsResponse
   * @throws \Google\Service\Exception
   */
  public function retrieveImportableDomains($location, $optParams = [])
  {
    $params = ['location' => $location];
    $params = array_merge($params, $optParams);
    return $this->call('retrieveImportableDomains', [$params], RetrieveImportableDomainsResponse::class);
  }
  /**
   * Gets parameters needed to register a new domain name, including price and up-
   * to-date availability. Use the returned values to call `RegisterDomain`.
   * (registrations.retrieveRegisterParameters)
   *
   * @param string $location Required. The location. Must be in the format
   * `projects/locations`.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string domainName Required. The domain name. Unicode domain names
   * must be expressed in Punycode format.
   * @return RetrieveRegisterParametersResponse
   * @throws \Google\Service\Exception
   */
  public function retrieveRegisterParameters($location, $optParams = [])
  {
    $params = ['location' => $location];
    $params = array_merge($params, $optParams);
    return $this->call('retrieveRegisterParameters', [$params], RetrieveRegisterParametersResponse::class);
  }
  /**
   * Deprecated: For more information, see [Cloud Domains feature
   * deprecation](https://cloud.google.com/domains/docs/deprecations/feature-
   * deprecations) Gets parameters needed to transfer a domain name from another
   * registrar to Cloud Domains. For domains already managed by [Google
   * Domains](https://domains.google/), use `ImportDomain` instead. Use the
   * returned values to call `TransferDomain`.
   * (registrations.retrieveTransferParameters)
   *
   * @param string $location Required. The location. Must be in the format
   * `projects/locations`.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string domainName Required. The domain name. Unicode domain names
   * must be expressed in Punycode format.
   * @return RetrieveTransferParametersResponse
   * @throws \Google\Service\Exception
   */
  public function retrieveTransferParameters($location, $optParams = [])
  {
    $params = ['location' => $location];
    $params = array_merge($params, $optParams);
    return $this->call('retrieveTransferParameters', [$params], RetrieveTransferParametersResponse::class);
  }
  /**
   * Searches for available domain names similar to the provided query.
   * Availability results from this method are approximate; call
   * `RetrieveRegisterParameters` on a domain before registering to confirm
   * availability. (registrations.searchDomains)
   *
   * @param string $location Required. The location. Must be in the format
   * `projects/locations`.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string query Required. String used to search for available domain
   * names.
   * @return SearchDomainsResponse
   * @throws \Google\Service\Exception
   */
  public function searchDomains($location, $optParams = [])
  {
    $params = ['location' => $location];
    $params = array_merge($params, $optParams);
    return $this->call('searchDomains', [$params], SearchDomainsResponse::class);
  }
  /**
   * Sets the access control policy on the specified resource. Replaces any
   * existing policy. Can return `NOT_FOUND`, `INVALID_ARGUMENT`, and
   * `PERMISSION_DENIED` errors. (registrations.setIamPolicy)
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
   * (registrations.testIamPermissions)
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
   * Deprecated: For more information, see [Cloud Domains feature
   * deprecation](https://cloud.google.com/domains/docs/deprecations/feature-
   * deprecations) Transfers a domain name from another registrar to Cloud
   * Domains. For domains already managed by [Google
   * Domains](https://domains.google/), use `ImportDomain` instead. Before calling
   * this method, go to the domain's current registrar to unlock the domain for
   * transfer and retrieve the domain's transfer authorization code. Then call
   * `RetrieveTransferParameters` to confirm that the domain is unlocked and to
   * get values needed to build a call to this method. A successful call creates a
   * `Registration` resource in state `TRANSFER_PENDING`. It can take several days
   * to complete the transfer process. The registrant can often speed up this
   * process by approving the transfer through the current registrar, either by
   * clicking a link in an email from the registrar or by visiting the registrar's
   * website. A few minutes after transfer approval, the resource transitions to
   * state `ACTIVE`, indicating that the transfer was successful. If the transfer
   * is rejected or the request expires without being approved, the resource can
   * end up in state `TRANSFER_FAILED`. If transfer fails, you can safely delete
   * the resource and retry the transfer. (registrations.transfer)
   *
   * @param string $parent Required. The parent resource of the `Registration`.
   * Must be in the format `projects/locations`.
   * @param TransferDomainRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function transfer($parent, TransferDomainRequest $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('transfer', [$params], Operation::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsRegistrations::class, 'Google_Service_CloudDomains_Resource_ProjectsLocationsRegistrations');
