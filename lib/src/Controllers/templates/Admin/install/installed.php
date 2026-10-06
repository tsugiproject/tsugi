<?php
/** @var array<int, \stdClass> $installed */
/** @var string $installGit */

if ( count($installed) < 1 ) {
    echo "<p>No installed modules</p>\n";
    return;
}
?>
<ul id="installed_ul">
<?php foreach ( $installed as $tool ) {
    $index = isset($tool->index) ? (int) $tool->index : 0;
    $name = isset($tool->name) ? (string) $tool->name : '';
    $htmlUrl = isset($tool->html_url) ? (string) $tool->html_url : '';
    $description = isset($tool->description) ? (string) $tool->description : '';
    $statusId = 'status_note_'.$index;
?>
    <li><?= htmlentities($name) ?>
       (<a href="<?= htmlentities($htmlUrl) ?>" target="_blank"><?= htmlentities($htmlUrl) ?></a>)
       <?php if ( $description !== '' ) { ?>
         <br/><?= htmlentities($description) ?>
       <?php } ?>
        <ul>
       <?php if ( ! empty($tool->error) ) { ?>
        <li class="text-danger">
        <pre><?= htmlentities((string) $tool->error) ?></pre>
        </li>
       <?php } ?>
       <?php if ( ! empty($tool->status_note) ) { ?>
        <li>
        <div id="<?= htmlentities($statusId) ?>" title="git status" style="display: none;">
<pre>
<?= htmlentities((string) $tool->status_note) ?>
</pre>
        </div>
       <a href="#" onclick="showModal('git status','<?= htmlentities($statusId) ?>'); return false;">
    Current git status
       </a>
         </li>
       <?php } ?>
       <?php if ( empty($tool->writeable) ) { ?>
              <li>
              Cannot be updated via this page
              <a href="#" onclick="showModal('Readonly Detail','readonly-dialog'); return false;">
              <i class="fa fa-lock text-danger"></i>
              ?
              </a>
         </li>
       <?php } ?>
       <?php if ( ! empty($tool->guid) ) { ?>
        <li>
       <a href="<?= htmlentities(addSession($installGit.'?command=log&path='.rawurlencode((string) $tool->guid))) ?>" title="git log" target="iframe-frame"
        onclick="showModalIframe(this.title, 'iframe-dialog', 'iframe-frame', _TSUGI.spinnerUrl);" >
       Git log
       </a>
        </li>
       <?php } ?>
       <?php if ( ! empty($tool->updates) ) { ?>
         <li>
         Updates Available
         <i class="fa fa-cloud-download text-important"></i>
         </li>
         <?php if ( ! empty($tool->guid) && ! empty($tool->writeable) ) { ?>
            <li>
               <a href="<?= htmlentities(addSession($installGit.'?command=pull&path='.rawurlencode((string) $tool->guid))) ?>" title="git pull" target="iframe-frame"
                onclick="showModalIframe(this.title, 'iframe-dialog', 'iframe-frame', _TSUGI.spinnerUrl, true);" >
               Git pull
           </a>
            </li>
         <?php } ?>
       <?php } ?>
       </ul>
    </li>
<?php } ?>
</ul>
