<h2>LTI 1.3</h2>
<p>
Blackboard useds a single issuer for all tenants, but
since the Blackboard developer portal assigns a <b>Well-Known/KeySet URL</b> that is unique
to each Client ID, it is best to skip creating a reusable Issuer in Tsugi, and instead create a Key for each Tenant,
select "No Global Issuer Selected" and set all the issuer values here
to create a Tenant key.
The <b>Deployment ID</b> is provided when you place Tsugi into a Blackboard
instance using the <b>Client ID</b>.
For Blackboard, the following are the typical values for the Tsugi items:
<pre>
<b>LTI 1.3 Platform Issuer URL</b>
https://blackboard.com

<b>LTI 1.3 Platform Client ID</b>
fe3ebd13-39a4-42c4-8b83-194f08e77f8a  (just an example value)

<b>LTI 1.3 Deployent ID</b>
ea4e4459-2363-348e-bd38-048993689aa0  (just an example value)

<b>LTI 1.3 Platform KeySet URL </b>
https://developer.blackboard.com/api/vl/management/applications/fe3ebd13-39a4-42c4-8b83-194f08e77f8a/jwks.json

The path parameter in the Keyset URL is the Client Id.

<b>LTI 1.3 Platform Token URL</b>
https://developer.blackboard.com/api/v1/gateway/oauth2/jwttoken

<b>LTI 1.3 Platform OIDC Login / Authorization Endpoint URL</b>
https://developer.blackboard.com/api/v1/gateway/oidcauth

</pre>
</p>
<p>
When you create a developer key in the Blackboard Developer portal, there
are two values that are <b>only</b> used for Blackboard's REST web services.
The <b>Application Key</b> and <b>Secret</b> are not used at all
for LTI 1.3 integrations.  The <b>Client ID</b> and the URLs from the
developer portal are used to configure LTI 1.3.  To make things a little
more confusing, the Blackboard UI might call the "Client ID" the "Application ID".
"Application ID" and "Application Key" are not the same thing at all.
</p>
