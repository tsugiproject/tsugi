<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";

// Sanity checks
$LTI = lti_require_data(array('user_id', 'link_id', 'role','context_id'));
$instructor = isset($LTI['role']) && $LTI['role'] == 1 ;

$p = $CFG->dbprefix;
$localstatic = getLocalStatic(__FILE__) . '/static';

html_header_content();
?>
<!--
<link rel="stylesheet" type="text/css" media="screen" href="http://www.trirand.com/blog/jqgrid/themes/redmond/jquery-ui-custom.css" />
<link rel="stylesheet" type="text/css" media="screen" href="http://www.trirand.com/blog/jqgrid/themes/ui.jqgrid.css" />
<link rel="stylesheet" type="text/css" media="screen" href="http://www.trirand.com/blog/jqgrid/themes/ui.multiselect.css" />
-->
<link href="<?php echo($localstatic); ?>/jquery.jqGrid-4.6.0/css/ui.jqgrid.css" rel="stylesheet">
<?php
html_start_body();
flash_messages();
welcome_user_course($LTI);
?>
<h1>This is under construction - it really does nothing at this point</h1>
<div id="yo">
<table id="list2"></table>
<div id="pager2"></div>
</div>
<?php
html_footer_start();
?>
<script src="<?php echo($localstatic); ?>/jquery.jqGrid-4.6.0/js/i18n/grid.locale-en.js" type="text/javascript"></script>
<script src="<?php echo($localstatic); ?>/jquery.jqGrid-4.6.0/js/jquery.jqGrid.src.js" type="text/javascript"></script>
<script type="text/javascript">
// See http://www.trirand.com/blog/jqgrid/jqgrid.html
jQuery(document).ready(function(){

jQuery("#list2").jqGrid({
    url:'server-test.php?q=2',
    datatype: "json",
    colNames:['Inv No','Date', 'Client', 'Amount','Tax','Total','Notes'],
    colModel:[
        {name:'id',index:'id', width:55},
        {name:'invdate',index:'invdate', width:90},
        {name:'name',index:'name asc, invdate', width:100},
        {name:'amount',index:'amount', width:80, align:"right"},
        {name:'tax',index:'tax', width:80, align:"right"},      
        {name:'total',index:'total', width:80,align:"right"},       
        {name:'note',index:'note', width:150, sortable:false}       
    ],
    rowNum:20,
    rowList:[20, 40, 80, 160],
    pager: '#pager2',
    sortname: 'id',
    viewrecords: true,
    sortorder: "desc",
    height: "100%",
    caption:"JSON Example"
});
});
jQuery("#list2").jqGrid('navGrid','#pager2',{edit:false,add:false,del:false});
</script>
<?php
html_footer_end();

