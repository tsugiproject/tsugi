<script>
function importReplaceMode(){
    var v = $('input[name="cc_replace_choice"]:checked').val();
    return v === 'replace' ? 'replace' : 'add';
}
function typedImportDomain(){
    var raw = $.trim($('#cc_replace_domain_input').val() || '');
    raw = raw.replace(/^[a-z][a-z0-9+.-]*:\/\//i, '');
    raw = raw.split('/')[0];
    raw = raw.split(':')[0];
    return raw.toLowerCase();
}
function typedImportTitle(){
    var raw = $.trim($('#cc_replace_title_input').val() || '');
    raw = raw.replace(/\s+/g, ' ');
    return raw.toLowerCase();
}
function typedImportMembers(){
    var raw = $.trim($('#cc_replace_members_input').val() || '');
    raw = raw.replace(/[,\s]/g, '');
    if ( ! /^\d+$/.test(raw) ) {
        return null;
    }
    return parseInt(raw, 10);
}
function importDomainMatches(){
    var expected = <?= json_encode($import_site_domain) ?>;
    return expected !== '' && typedImportDomain() === expected;
}
function importTitleMatches(){
    var expected = <?= json_encode($import_course_title_norm) ?>;
    return expected !== '' && typedImportTitle() === expected;
}
function importMembersMatch(){
    var expected = <?= json_encode($import_member_count) ?>;
    if ( expected === null || typeof expected !== 'number' ) {
        return false;
    }
    return typedImportMembers() === expected;
}
function importConfirmMatches(){
    return importDomainMatches() && importTitleMatches() && importMembersMatch();
}
function syncImportReplace(){
    var replace = importReplaceMode() === 'replace';
    var match = importConfirmMatches();
    $('input[name="cc_replace"]').val(replace && match ? 'replace' : 'add');
    $('input[name="cc_replace_domain"]').val($('#cc_replace_domain_input').val() || '');
    $('input[name="cc_replace_title"]').val($('#cc_replace_title_input').val() || '');
    $('input[name="cc_replace_members"]').val($('#cc_replace_members_input').val() || '');
    if ( replace ) {
        $('#import-replace-warning').show();
        $('.import-submit').removeClass('btn-primary').addClass('btn-danger');
    } else {
        $('#import-replace-warning').hide();
        $('.import-submit').removeClass('btn-danger').addClass('btn-primary');
    }
    if ( importAlreadyBusy() ) {
        return;
    }
    $('.import-submit').prop('disabled', replace && ! match);
}
function importAlreadyBusy(){
    return $('.import-submit[aria-busy="true"]').length > 0;
}
function startImportBusy(btn){
    var label = <?= json_encode($import_busy_label) ?>;
    $('.import-submit').prop('disabled', true).attr('aria-busy', 'true');
    if ( ! btn ) {
        btn = $('.import-submit').get(0);
    }
    if ( btn ) {
        $(btn).html('<i class="fa fa-spinner fa-spin" aria-hidden="true"></i> '+label);
    }
}
function confirmImportReplace(btn){
    if ( importAlreadyBusy() ) {
        return false;
    }
    syncImportReplace();
    if ( importReplaceMode() === 'replace' ) {
        if ( ! importDomainMatches() || ! importTitleMatches() || ! importMembersMatch() ) {
            alert(<?= json_encode(__('Type this site\'s domain, this course\'s title, and the number of members to confirm deleting this course\'s content.')) ?>);
            return false;
        }
        if ( ! confirm(<?= json_encode($import_replace_confirm) ?>) ) {
            return false;
        }
    }
    startImportBusy(btn);
    return true;
}
function importSelectedModules(btn){
    var keys = [];
    $('#void input.import-module-key[type="checkbox"]').each(function(){
         if ( ! $(this).is(':checked') ) return;
         var v = $(this).val();
         if ( v ) {
            keys.push(v);
         }
    });
    $("#import_modules_real").val(keys.join(','));
    if ( keys.length < 1 ) {
        alert(<?= json_encode(__('Please select at least one module')) ?>);
        return false;
    }
    if ( ! confirmImportReplace(btn) ) {
        return false;
    }
    $("#import-selected-real").submit();
    return false;
}
$(function(){
    $('input[name="cc_replace_choice"]').on('change', syncImportReplace);
    $('#cc_replace_domain_input').on('input', syncImportReplace);
    $('#cc_replace_title_input').on('input', syncImportReplace);
    $('#cc_replace_members_input').on('input', syncImportReplace);
    syncImportReplace();
});
</script>
