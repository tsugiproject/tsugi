<?php
require_once "util.php";

$abs_url = str_replace("lti_config.php", "convert.php", curPageURL());
header('Content-Type: text/xml');
echo('<?xml version="1.0" encoding="UTF-8"?>'."\n");
?>
<cartridge_basiclti_link xmlns="http://www.imsglobal.org/xsd/imslticc_v1p0" xmlns:blti="http://www.imsglobal.org/xsd/imsbasiclti_v1p0" xmlns:lticm="http://www.imsglobal.org/xsd/imslticm_v1p0" xmlns:lticp="http://www.imsglobal.org/xsd/imslticp_v1p0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/imslticc_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticc_v1p0.xsd http://www.imsglobal.org/xsd/imsbasiclti_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imsbasiclti_v1p0p1.xsd http://www.imsglobal.org/xsd/imslticm_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticm_v1p0.xsd http://www.imsglobal.org/xsd/imslticp_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticp_v1p0.xsd">
<blti:title>Gift Quiz Converter</blti:title>
<blti:launch_url><?php echo $abs_url ?></blti:launch_url>
<blti:extensions platform="canvas.instructure.com">
    <lticm:options name="migration_selection">
        <lticm:property name="enabled">true</lticm:property>
        <lticm:property name="selection_height">900</lticm:property>
        <lticm:property name="selection_width">900</lticm:property>
    </lticm:options>
</blti:extensions>
</cartridge_basiclti_link>
