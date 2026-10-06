<?php
/** @var array<int, \stdClass> $required */
/** @var array $installReport */
/** @var string $installGit */

if ( count($required) < 1 ) {
    echo "<p>All required modules have been installed</p>\n";
    if ( ! empty($installReport['required_error']) ) {
        echo '<p>'.htmlentities((string) $installReport['required_error'])."</p>\n";
    }
    if ( ! empty($installReport['required_error_detail']) ) {
        echo "<pre>\n".htmlentities((string) $installReport['required_error_detail'])."\n</pre>\n";
    }
    return;
}
?>
<ul id="required_ul">
<?php foreach ( $required as $tool ) {
    $name = isset($tool->name) ? (string) $tool->name : '';
    $htmlUrl = isset($tool->html_url) ? (string) $tool->html_url : '';
    $description = isset($tool->description) ? (string) $tool->description : '';
    $cloneUrl = isset($tool->clone_url) ? (string) $tool->clone_url : '';
?>
    <li><?= htmlentities($name) ?>
        (<a href="<?= htmlentities($htmlUrl) ?>" target="_blank"><?= htmlentities($htmlUrl) ?></a>)
       <?php if ( $description !== '' ) { ?>
         <br/><?= htmlentities($description) ?>
       <?php } ?>
        <ul>
        <?php if ( ! empty($tool->writeable) ) { ?>
           <li>
              <a href="<?= htmlentities(addSession($installGit.'?command=clone&remote='.rawurlencode($cloneUrl))) ?>" title="Install" target="iframe-frame"
               onclick="showModalIframe(this.title, 'iframe-dialog', 'iframe-frame', _TSUGI.spinnerUrl, true);" >
              Install
           </a>
           </li>
        <?php } else { ?>
          <li>
          Cannot be installed via this page
            <a href="#" onclick="showModal('Readonly Detail','readonly-dialog'); return false;">
            <i class="fa fa-lock text-danger"></i> ?  </a>
          </li>
        <?php } ?>
        </ul>
    </li>
<?php } ?>
</ul>
