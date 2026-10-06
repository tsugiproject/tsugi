<h2>Canvas LTI 1.3</h2>

<p>
To use LTI 1.3 in Canvas, first create a Tsugi Tenant using a temporary
<b>Client ID</b> such as <b>temporary-42-oertufgh</b>. Make sure it is random enough to be
unique across issuers as Tsugi prohibits duplicate (issuer, client_id) records system wide.
Save the Tenant in Tsugi and use the
LTI values like JWKs URL shown by Tsugi to create a
<b>Developer Key</b> in Canvas.
</p>

<p>
When the Developer Key is created, Canvas will assign a real
<b>Client ID</b> (for example
<b>38288000000000436</b>). Edit the Tsugi Tenant and replace the
temporary Client ID with the value assigned by Canvas and save the Tenant.
</p>

<p>
After the Developer Key is created, install the tool in Canvas using
the <b>+ App</b> feature in course, account, or sub-account settings.
Canvas will create a <b>Deployment ID</b> (for example
<b>a16eaea622168ab8327cddef847ccabeea459a79</b>).
</p>

<p>
The Deployment ID is optional in Tsugi and may be left blank to accept
launches from any Canvas deployment using that issuer/client pair.
If you enter a Deployment ID on the Tenant, launches will be restricted
to that specific Canvas deployment (i.e. it might just be one course).
</p>

<?php if ( isset($canvasContentItemUrl) ) { ?>
<h2>Canvas LTI 1.1</h2>
<p>
You can install Tsugi as a
<a href="http://www.imsglobal.org/specs/lticiv1p0/specification" target="_blank">Content-Item</a>
(i.e. a tool picker) by using the <b>consumer_key</b> and <b>consumer_secret</b> and the following
URL.
</p>
<p>
<b>Canvas LTI 1.1 Configuration URL:
<button href="#" onclick="copyToClipboardNoScroll(this, '<?= $canvasContentItemUrl ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</button></b>
</p>
<p>
<?= htmlentities($canvasContentItemUrl) ?>
</p>
<?php } else { ?>
<h2>LTI 1.1</h2>
<p>
To use LTI 1.1 in Canvas, create your key with a title, key, and secret and come back here
to view the XML-based Canvas auto-installation URL.   Auto-installation of LTI 1.1 tools
in Canvas is on a per-Tenant basis.
</p>
<?php } ?>
