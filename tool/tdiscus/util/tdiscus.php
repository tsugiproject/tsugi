<?php

namespace Tdiscus;

use \Tsugi\Util\U;
use \Tsugi\Core\Settings;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\MoFileLoader;

class Tdiscus {

    const default_paginator_width = 7;

    public static function toolRoot() {
        global $TOOL_ROOT;
        if ( ! isset($TOOL_ROOT) ) $TOOL_ROOT = dirname($_SERVER['SCRIPT_NAME']);
        return $TOOL_ROOT;
    }

    public static function header() {
        // Styles are centralized in tsugi-static/css/tsugi2.css.
    }

    public static function footer() {
        self::ckeditor_load();
        // Timeago is in Output::footer();
        // echo('<script>$(document).ready(function() { jQuery("time.timeago").timeago(); });</script>'."\n");
    }

    public static function ckeditor_load() {
        global $CFG;
        echo('<script src="'.$CFG->staticroot.'/util/ckeditor_4.8.0/ckeditor.js"></script>'."\n");
    }

    public static function ckeditor_footer() {
        global $CFG;
?>
<script>
$(document).ready( function () {
    CKEDITOR.replace( 'editor' );
});
</script>
<?php
    }

    public static function search_box($sortby=false) {
        $searchvalue = U::get($_GET,'search') ? 'value="'.htmlentities(U::get($_GET,'search')).'" ' : "";
        $sortvalue = U::get($_GET,'sort');

        // https://www.w3schools.com/howto/howto_css_search_button.asp
        echo('<div class="tdiscus-threads-search-sort" role="search"><form>'."\n");
        if ( is_array($sortby) ) {
?>
<div class="tdiscus-threads-sort">
<label for="sort"><?= __("Sort by") ?></label>
<select name="sort" id="sort" onchange="this.form.submit();" aria-label="<?= htmlspecialchars(__("Sort by")) ?>">
<?php
foreach($sortby as $sort) {
  echo('<option value="'.$sort.'" '.($sortvalue == $sort ? 'selected="selected"' : '').' >'.__(ucfirst($sort)).'</option>'."\n");
}
?>
</select>
</div>
<?php
        }
?>
<div class="tdiscus-threads-search">
  <label for="tdiscus-threads-search-input" class="tdiscus-visually-hidden"><?= __("Search") ?></label>
  <input type="text" id="tdiscus-threads-search-input" placeholder="<?= htmlspecialchars(__("Search")) ?>..." name="search"
  <?= $searchvalue ?> aria-label="<?= htmlspecialchars(__("Search threads")) ?>"
  >
  <button type="submit" aria-label="<?= htmlspecialchars(__("Search")) ?>"><i class="fa fa-search" aria-hidden="true"></i></button>
  <button type="button" onclick='document.getElementById("tdiscus-threads-search-input").value = ""; this.form.submit();' aria-label="<?= htmlspecialchars(__("Clear search")) ?>"><i class="fa fa-undo" aria-hidden="true"></i></button>
</div>
<?php
        echo("</form></div>\n");
    }

    public static function renderComment($LAUNCH, $thread_id, $comment)
    {
        $locked = $comment['locked'];
        $hidden = $comment['hidden'];
        $depth = $comment['depth'];
        $children = $comment['children'];
        $comment_id = $comment['comment_id'];

        $unique = '_'.$thread_id.'_'.$comment_id;

        if ( $depth < 3 ) {
            $indent = ($depth * 10);
        } else {
            $indent = 20 + ($depth-3) * 2;
        }

        $comment_author = $comment['displayname'] ?? '';
        echo('<article id="tdiscus-comment-container-'.$comment_id.'" class="tdiscus-comment-container" style="padding-left:'.$indent.'px;" aria-label="'.htmlspecialchars(sprintf(__('Comment by %s'), $comment_author)).'">'."\n");

        if ( $LAUNCH->user->instructor ) {
           Tdiscus::renderBooleanSwitch('comment', $comment_id, 'hidden', 'hide', $hidden, 0, 'fa-eye-slash', 'orange');
        }

        if ( $LAUNCH->user->instructor ) {
            Tdiscus::renderBooleanSwitch('comment', $comment_id, 'locked', 'lock', $locked, 0, 'fa-lock', 'orange');
        } else {
            echo('<span '.($locked == 0 ? 'style="display:none;"' : '').' aria-hidden="true"><i class="fa fa-lock fa-rotate-270" style="color: orange;"></i></span>');
        }
?>
  <span class="tdiscus-user-name"><?= htmlentities($comment['displayname'] ?? '') ?></span>
  <time class="timeago" datetime="<?= $comment['modified_at'] ?>"><?= $comment['modified_at'] ?></time>
  <?php if ( $comment['owned'] || $LAUNCH->user->instructor ) { ?>
    <a href="<?= self::toolRoot() ?>/commentform/<?= $comment['comment_id'] ?>" aria-label="<?= htmlspecialchars(__("Edit comment")) ?>"><i class="fa fa-pencil" aria-hidden="true"></i></a>
    <a href="<?= self::toolRoot() ?>/commentremove/<?= $comment['comment_id'] ?>" aria-label="<?= htmlspecialchars(__("Delete comment")) ?>"><i class="fa fa-trash" aria-hidden="true"></i></a>
  <?php } ?>
<?php
        if ( $LAUNCH->user->instructor ) {
            Tdiscus::renderBooleanSwitch('comment', $comment_id, 'hidden', 'hide', $hidden, 1, 'fa-eye-slash');
        }
        if ( $LAUNCH->user->instructor ) {
            Tdiscus::renderBooleanSwitch('comment', $comment_id, 'locked', 'lock', $locked, 1, 'fa-lock');
        }
        $id = "tdiscus-add-sub-comment-div$unique";

        if ( Threads::maxDepth() > 0 && ($depth+1) < Threads::maxDepth() ) {
            Tdiscus::renderToggle(__('reply'), $id, 'fa-comment', 'green');
        }
/*
        if ( $children > 0 ) {
            echo('<a href="#"><i class="fa fa-angle-double-down"></i> ('.$children.')</a>');
        }
*/
?>
  <br/>
  <div style="padding-left: 10px;<?= ($hidden ? ' text-decoration: line-through;' : '') ?>"><?= htmlentities($comment['comment'] ?? '') ?></div>
  </p>
<?php
        if ( Threads::maxDepth() > 0 ) {
            echo('<div class="tdiscus-sub-comment-container">'."\n");
            Tdiscus::add_sub_comment($id, $thread_id, $comment_id, 1);
            echo('</div> <!-- tdiscus-sub-comment-container -->');
        }
        echo('</article> <!-- tdiscus-comment-container -->');
    }

    public static function add_comment($thread_id) {
?>
<div id="tdiscus-add-comment-div" class="tdiscus-add-comment-container" title="<?= __("Reply") ?>" role="region" aria-label="<?= htmlspecialchars(__("Add reply")) ?>">
<form id="tdiscus-add-comment-form" method="post">
<p>
<label for="tdiscus-add-comment-text-<?= $thread_id ?>" class="tdiscus-visually-hidden"><?= __("Your reply") ?></label>
<textarea id="tdiscus-add-comment-text-<?= $thread_id ?>" style="width:100%;" class="tdiscus-add-sub-comment-text form-control" name="comment" aria-label="<?= htmlspecialchars(__("Your reply")) ?>">
</textarea>
</p>
<p>
<input type="submit" id="tdiscus-add-comment-submit" name="submit" value="<?= __('Reply') ?>" >
</p>
</form>
</div>
<?php
    }

    public static function add_sub_comment($html_id, $thread_id, $comment_id, $depth) {
        $textarea_id = $html_id . '-textarea';
?>
<div id="<?= $html_id ?>" class="tdiscus-add-sub-comment-container" title="<?= __("Reply") ?>" role="region" aria-label="<?= htmlspecialchars(__("Add reply")) ?>" style="display:none;">
<form method="post" 
    data-click-done="<?= $html_id ?>_toggle" 
    class="tdiscus-add-sub-comment-form">
<p>
<input type="hidden" name="comment_id" value="<?= $comment_id ?>">
<input type="hidden" name="thread_id" value="<?= $thread_id ?>">
<label for="<?= $textarea_id ?>" class="tdiscus-visually-hidden"><?= __("Your reply") ?></label>
<textarea id="<?= $textarea_id ?>" style="width:100%;" class="tdiscus-add-sub-comment-text form-control" name="comment" aria-label="<?= htmlspecialchars(__("Your reply")) ?>">
</textarea>
</p>
<p>
<input type="submit" name="submit" value="<?= __('Reply') ?>" >
</p>
</form>
</div>
<?php
    }

    public static function paginator($baseurl, $start, $pagesize, $total) {
    // echo("<p>baseurl=$baseurl start=$start size=$pagesize total=$total</p>\n");
    if ( $start == 0 && $total < $pagesize ) return;

    $laststart = intval($total /$pagesize) * $pagesize;
    $showpages = self::default_paginator_width; // The number of pages
    $firststart = $start - (intval($showpages/2) * $pagesize);
    if ( $firststart < 0 ) $firststart = 0;
?>
<nav aria-label="Page navigation">
  <ul class="pagination">
  <li class="page-item<?= ($start>0) ? '' : ' disabled'?>">
    <a class="page-link" href="<?= U::add_url_parm($baseurl, 'start', "0") ?>" aria-label="First">
        First
      </a>
    </li>
<?php
    if ( $firststart > 0 ) {
        $prefirststart = $firststart - $pagesize;
        echo('<li class="page-item"><a class="page-link" href="'.U::add_url_parm($baseurl, 'start', $prefirststart).'">...</a></li>');
    }
    for($i=0;$i<$showpages;$i++) {
        if ( $firststart > $laststart ) break;
        $active = ($firststart == $start ) ? ' active' : '';
        $pageno = intval($firststart/$pagesize);
        echo('<li class="page-item'.$active.'"><a class="page-link" href="'.U::add_url_parm($baseurl, 'start', $firststart).'">'.($pageno+1)."</a></li>\n");
        $firststart = $firststart + $pagesize;
    }
    if ( $firststart <= $laststart ) {
        echo('<li class="page-item"><a class="page-link" href="'.U::add_url_parm($baseurl, 'start', $firststart).'">...</a></li>');
    }
?>
    <li class="page-item<?= ($start<$laststart) ? '' : ' disabled'?>">
      <a class="page-link" href="<?= U::add_url_parm($baseurl, 'start', ($laststart)) ?>" aria-label="Last">
        Last
      </a>
    </li>
  </ul>
</nav>
<?php
    }

    public static function renderBooleanSwitch($type, $thread_id, $variable, $title, $value, $set, $icon, $color=false)
    {
        $action = ($set ? '' : 'un').$title;
        $uitype = $type;
        if ( $uitype == 'threaduser' ) $uitype = 'thread';
        if ( $uitype == 'threadcomment' ) $uitype = 'comment';
?>
        <a href="#"
        class="<?= $type ?><?= $variable ?>_<?= $thread_id ?> tdiscus-boolean-api-call"
        data-class="<?= $type ?><?= $variable ?>_<?= $thread_id ?>"
        data-endpoint="<?= $type ?>setboolean/<?= $thread_id ?>/<?= $variable ?>/<?= $set ?>"
        data-confirm="<?= htmlentities(__('Do you want to '.$action.' this '.$uitype.'?')) ?>"
        title="<?= __(ucfirst($action)." ".ucfirst($uitype)) ?>"
        aria-label="<?= htmlspecialchars(__(ucfirst($action)." ".ucfirst($uitype))) ?>"
        role="button"
         <?= ($value == $set ? 'style="display:none;"' : '') ?>
         ><i class="fa <?= $icon ?>" aria-hidden="true" <?= ($color ? 'style="color: '.$color.'";' : '') ?>></i></a>
<?php
    }

    public static function renderToggle($title, $id, $icon, $color=false)
    {
?>
        <a href="#"
        id="<?= $id ?>_toggle"
        data-id="<?= $id ?>"
        class="tdiscus-toggle-api-call"
        aria-label="<?= htmlspecialchars(__("Toggle").' '.$title) ?>"
        aria-expanded="false"
        role="button"
        title="<?= htmlentities(__("Toggle").' '.$title) ?>">
        <i id="<?= $id ?>_icon_on" class="fa <?= $icon ?>" aria-hidden="true"></i>
        <i id="<?= $id ?>_icon_off" class="fa <?= $icon ?>"
         style="display:none; <?= ($color ? ('color: '.$color.';') : '') ?>" aria-hidden="true"></i></a>
<?php
    }

    public static function renderBooleanScript()
    {
        global $OUTPUT;
?>
<script>
$(document).ready( function() {
    function handleApiCall(ev) {
        ev.preventDefault()
        if ( ! confirm($(this).attr('data-confirm')) ) return;
        var data_class = $(this).attr('data-class');
        $.post(addSession('<?= self::toolRoot() ?>'+'/api/'+$(this).attr('data-endpoint')))
            .done( function(data) {
                $('.'+data_class).toggle();
            })
            .error( function(xhr, status, error) {
                console.log(xhr);
                console.log(status);
                var message = '<?= htmlentities(__('Request Failed')) ?>';
                if ( error && error.length > 0 ) {
                    message = message + ": "+error.substring(0,40);
                }
                console.log(error);
                alert(message);
            });
   }

    function toggleApiCall(ev) {
        ev.preventDefault()
        var $btn = $(this);
        var data_id = $btn.attr('data-id');
        var $target = $('#'+data_id);
        var isExpanded = $target.is(':visible');
        $target.toggle();
        $('#'+data_id+"_icon_on").toggle();
        $('#'+data_id+"_icon_off").toggle();
        $btn.attr('aria-expanded', !isExpanded);
    }

   function handleSubmit(ev) {
       var comment = $(this).find('textarea[name="comment"]').val();
       console.log('comment=',comment);
       ev.preventDefault();
       var ser = $(this).serialize();
       var form = $(this);
       console.log('ser', ser);
       var click_done = $(this).attr('data-click-done');
       var txt3 = document.createElement("p");  // Create with DOM
       txt3.innerHTML = '<img src="<?= $OUTPUT->getSpinnerUrl() ?>">';
       $(this).closest('.tdiscus-sub-comment-container').prepend(txt3);
       // Hide during the processing
       if ( click_done ) $('#'+click_done).click();
       $(this).find('textarea[name="comment"]').val('');
       $.post(addSession('<?= self::toolRoot() ?>/api/addsubcomment'), ser)
            .done( function(data) {
                console.log('data', data);
                // if ( comment.length > 0 ) txt3.innerHTML = htmlentities(comment);
                if ( comment.length > 0 ) txt3.innerHTML = data;

                $('.tdiscus-add-sub-comment-form').on('submit', handleSubmit);
                $('.tdiscus-toggle-api-call').click(toggleApiCall);
                $('.tdiscus-boolean-api-call').click(handleApiCall);
            })
            .error( function(xhr, status, error) {
                console.log(xhr);
                console.log(status);
                console.log(error);
                alert(error);
            });
    }

    $('.tdiscus-add-sub-comment-form').on('submit', handleSubmit);
    $('.tdiscus-boolean-api-call').click(handleApiCall);
    $('.tdiscus-toggle-api-call').click(toggleApiCall);

});
</script>
<?php
    }
}
