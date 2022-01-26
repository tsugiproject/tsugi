<p>
<b>(under construction)</b>
</p>
<p>
Since a Blackboard developer key <b>Well-Known/KeySet URL</b> that is unique
to each developer key, it is best to skip creating an Issuer, select
"No Global Issuer Selected" and set all the issuer values here
to create a Tenant key.
For Blackboard, the following are the typical values for the Tsugi items:
<pre>
<b>LTI 1.3 Platform Issuer URL</b>
https://blackboard.com

<b>LTI 1.3 Platform Client ID - usually a GUID (from the Platform)</b>
This is the "Application ID" from the Blackboard developer portal.

<b>LTI 1.3 Deployent ID</b>
This is the "???" from the Blackboard developer portal.

<b>LTI 1.3 Platform KeySet URL </b>
https://developer.blackboard.com/api/vl/management/applications/...unique.identifier.../jwks.json

<b> LTI 1.3 Platform Token URL</b>
https://developer.blackboard.com/api/v1/gateway/oauth2/jwttoken

<b>LTI 1.3 Platform OIDC Login / Authorization Endpoint URL</b>
https://developer.blackboard.com/api/v1/gateway/oidcauth

<b>LTI 1.3 Platform OAuth2 Bearer Token Audience</b>
This value is required and it is the "???" in the Blackboard developer portal.
</pre>
</p>
