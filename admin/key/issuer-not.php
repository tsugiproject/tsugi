<div class="tab-pane fade" id="brightspace">
<p>
Brightspace uses a per-server Issuer value and supports
<a href="https://www.imsglobal.org/spec/lti-dr/v1p0" target="_blank">IMS Dynamic Registration</a>
so you
should create a Tenant Key without a global Issuer.
</p>
<p>
Brightspace is the one LMS that will provide you with an <b>audience</b>
value.  Launches and token requests to Brightspace will fail without this value.
</p>
</div>
<div class="tab-pane fade" id="sakai">
<p>
Sakai uses a per-server Issuer value and supports
<a href="https://www.imsglobal.org/spec/lti-dr/v1p0" target="_blank">IMS Dynamic Registration</a>
so you
should create a Tenant Key without a global Issuer.
</p><p>
Because a Sakai server usually supports a single tenant they usually set the
<b>deployment id</b> to <b>1</b> in the tenant configuration.
</p>
</div>
<div class="tab-pane fade" id="moodle">
<p>
Moodle uses a per-server Issuer value and supports
<a href="https://www.imsglobal.org/spec/lti-dr/v1p0" target="_blank">IMS Dynamic Registration</a>
so you
should create a Tenant Key without a global Issuer.
</p><p>
Because a Moodle server usually supports a single tenant they usually set the
<b>deployment id</b> to <b>1</b> in the tenant configuration.
</p>
</div>
<div class="tab-pane fade" id="blackboard">
<p>
Blackboard does use a single global issuer value, but its KeySet URL is
unique for each client, so you should create a Tenant Key for each integration
without a global Issuer.
</p>
<pre>
<b>LTI 1.3 Issuer</b>
https://developer.blackboard.com

<b>LTI 1.3 Platform OAuth2 Well-Known/KeySet URL (from the platform)</b>
https://developer.blackboard.com/api/vl/management/applications/...unique.identifier.../jwks.json

</pre>
</p>
</div>
