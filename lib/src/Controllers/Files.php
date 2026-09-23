<?php

namespace Tsugi\Controllers;

use \Tsugi\Util\U;
use Tsugi\Core\LTIX;
use Tsugi\Core\Context;
use Tsugi\Core\Link;
use Tsugi\Blob\BlobUtil;
use Tsugi\Blob\Access;
require_once __DIR__ . '/../Services/Files/FileRepository.php';
use Tsugi\Services\Files\FileRepository;
use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Course files tool (Canvas Files / Sakai Resources style).
 *
 * Files are stored in the existing blob single-instance store (blob_file +
 * blob_blob / dataroot), tagged with the current context_id and a synthetic
 * lti_link for this tool. Folder membership lives in blob_file.json.
 *
 * Visibility:
 *   Student — students browsing the Files tool see only this folder's contents.
 *   Public — not browseable for students; anyone with the link can open the file
 *   without logging in.
 *   Private — instructors only; even a direct link is denied.
 *   Everything else is obscure — hidden from student browsing, but anyone
 *   in the course with the download link can fetch the file.
 *
 * Links copied from this tool are the course path (/files/{folder}/{name}).
 * /files/download/{sha256} remains for older content-addressed links.
 */
class Files extends Tool {

    const ROUTE = FileRepository::HREF_PREFIX;
    const NAME = 'Files';
    const REDIRECT = 'tsugi_controllers_files';

    const STUDENT_FILES_FOLDER = FileRepository::STUDENT_FILES_FOLDER;
    const PUBLIC_FOLDER = FileRepository::PUBLIC_FOLDER;
    const PRIVATE_FOLDER = FileRepository::PRIVATE_FOLDER;
    const KIND_FILE = FileRepository::KIND_FILE;
    const KIND_FOLDER = FileRepository::KIND_FOLDER;
    const BACKREF = FileRepository::BACKREF;
    const FOLDER_CONTENTTYPE = FileRepository::FOLDER_CONTENTTYPE;

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, 'Files@index');
        $app->router->get($prefix.'/', 'Files@index');
        $app->router->get('/'.self::REDIRECT, 'Files@index');
        $app->router->get($prefix.'/json', 'Files@json');
        $app->router->get($prefix.'/analytics', 'Files@analytics');
        $app->router->get($prefix.'/download/{sha256}', 'Files@download');
        $app->router->post($prefix.'/download/{sha256}', 'Files@download');
        $app->router->post($prefix.'/upload', 'Files@uploadPost');
        $app->router->post($prefix.'/mkdir', 'Files@mkdirPost');
        $app->router->post($prefix.'/delete/{id}', 'Files@deletePost');
        $app->router->get($prefix.'/replace/{id}', 'Files@replace');
        $app->router->post($prefix.'/replace/{id}', 'Files@replacePost');
        // Last: /files/{folder}/{name} so page HTML can link by path, not sha.
        $app->router->get($prefix.'/{path:.+}', 'Files@servePath');
        $app->router->post($prefix.'/{path:.+}', 'Files@servePath');
    }

    public function index(Request $request)
    {
        global $CFG, $OUTPUT;

        $this->requireAuth();
        $link_id = $this->ensureFilesLaunch();
        $is_instructor = $this->isInstructor();

        $this->lmsRecordLaunchAnalytics(self::ROUTE, self::NAME);

        $folder = $this->requestedFolder();
        if ( $folder === false ) {
            U::flashError('Invalid folder path');
            return new RedirectResponse($this->folderUrl(''));
        }
        if ( ! $is_instructor ) {
            if ( $folder === '' || strcasecmp($folder, self::STUDENT_FILES_FOLDER) === 0 ) {
                $folder = self::STUDENT_FILES_FOLDER;
            } else if ( ! FileRepository::isStudentFilesPath($folder) ) {
                return new RedirectResponse($this->folderUrl(''));
            }
        } else {
            FileRepository::ensureReservedFolders($link_id, U::currentContextId());
        }

        $items = FileRepository::listFolder($link_id, $folder, U::currentContextId());
        $tool_home = $this->toolHome(self::ROUTE);
        $max_upload = BlobUtil::maxUploadBytes();
        $crumbs = $this->breadcrumbs($folder, $is_instructor);
        $parent = $this->browseParent($folder, $is_instructor);

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        $csrf = $this->csrfField();
        ?>
        <main class="container" role="main" id="main-content">
            <h1 style="display: flex; justify-content: space-between; align-items: center;">
                <span>Files</span>
                <span>
                <?php if ( $is_instructor || $this->isAdmin() ): ?>
                    <a href="<?= htmlspecialchars($tool_home . '/analytics') ?>" class="btn btn-default" aria-label="<?= htmlspecialchars(__('View file analytics')) ?>">
                        <span class="glyphicon glyphicon-signal" aria-hidden="true"></span> Analytics
                    </a>
                <?php endif; ?>
                </span>
            </h1>

            <nav aria-label="Folder path">
                <ol class="breadcrumb">
                    <?php foreach ( $crumbs as $i => $crumb ): ?>
                        <?php $crumb_info = (isset($crumb['info']) && is_array($crumb['info'])) ? $crumb['info'] : null; ?>
                        <?php if ( $i === count($crumbs) - 1 ): ?>
                            <li class="active">
                                <?= htmlspecialchars($crumb['label']) ?>
                                <?= $this->infoButton($crumb['label'], $crumb_info) ?>
                                <?= ! empty($crumb['help']) ? $this->courseFilesHelpButton() : '' ?>
                            </li>
                        <?php else: ?>
                            <li>
                                <a href="<?= htmlspecialchars($crumb['url']) ?>"><?= htmlspecialchars($crumb['label']) ?></a>
                                <?= $this->infoButton($crumb['label'], $crumb_info) ?>
                                <?= ! empty($crumb['help']) ? $this->courseFilesHelpButton() : '' ?>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ol>
            </nav>

            <?php if ( $is_instructor ): ?>
                <div class="panel panel-default">
                    <div class="panel-body">
                        <form method="post" action="<?= htmlspecialchars($tool_home . '/upload') ?>" enctype="multipart/form-data" class="form-inline" style="margin-bottom: 10px;">
                            <?= $csrf ?>
                            <input type="hidden" name="folder" value="<?= htmlspecialchars($folder) ?>">
                            <div class="form-group">
                                <label for="uploads" class="sr-only">Upload files</label>
                                <input type="file" id="uploads" name="uploads[]" multiple required>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <span class="glyphicon glyphicon-upload" aria-hidden="true"></span> Upload
                            </button>
                            <span class="help-block" style="display: inline; margin-left: 8px;">Max <?= htmlspecialchars(U::displaySize($max_upload)) ?> per file</span>
                        </form>
                        <form method="post" action="<?= htmlspecialchars($tool_home . '/mkdir') ?>" class="form-inline">
                            <?= $csrf ?>
                            <input type="hidden" name="folder" value="<?= htmlspecialchars($folder) ?>">
                            <div class="form-group">
                                <label for="folder_name" class="sr-only">New folder name</label>
                                <input type="text" class="form-control" id="folder_name" name="name" placeholder="New folder name" required maxlength="128">
                            </div>
                            <button type="submit" class="btn btn-default">
                                <span class="glyphicon glyphicon-folder-close" aria-hidden="true"></span> Create folder
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ( count($items) === 0 ): ?>
                <div class="alert alert-info" role="status">
                    <?php if ( $is_instructor ): ?>
                        <p>This folder is empty. Upload a file or create a folder.</p>
                    <?php else: ?>
                        <p>No files have been shared yet.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <table class="table table-striped" role="table">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Size</th>
                            <th scope="col">Uploaded</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( $parent !== null ): ?>
                            <tr>
                                <td colspan="4">
                                        <a href="<?= htmlspecialchars($this->folderUrl($parent)) ?>">
                                        <span class="glyphicon glyphicon-arrow-up" aria-hidden="true"></span> Parent folder
                                    </a>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ( $items as $item ): ?>
                            <?php
                                $is_folder = ($item['kind'] === FileRepository::KIND_FOLDER);
                                $child_folder = FileRepository::joinFolder($folder, $item['name']);
                                $file_url = '';
                                $copy_url = '';
                                if ( ! $is_folder ) {
                                    $file_url = $this->filePathUrl(
                                        $folder,
                                        $item['name'],
                                        isset($item['file_sha256']) ? $item['file_sha256'] : ''
                                    );
                                    $copy_url = $this->absoluteUrl($file_url);
                                }
                                $is_reserved_root = $is_folder && $folder === '' && FileRepository::isReservedName($item['name']);
                                $info = null;
                                if ( $is_instructor && $folder === '' ) {
                                    $info = $this->accessInfoForPath($is_folder ? $item['name'] : '', $is_folder);
                                }
                            ?>
                            <tr>
                                <td>
                                    <?php if ( $is_folder ): ?>
                                        <a href="<?= htmlspecialchars($this->folderUrl($child_folder)) ?>">
                                            <span class="glyphicon glyphicon-folder-close" aria-hidden="true"></span>
                                            <?= htmlspecialchars($item['name']) ?>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= htmlspecialchars($file_url) ?>" target="_blank" rel="noopener noreferrer">
                                            <span class="glyphicon glyphicon-file" aria-hidden="true"></span>
                                            <?= htmlspecialchars($item['name']) ?>
                                        </a>
                                    <?php endif; ?>
                                    <?= $this->infoButton($item['name'], $info) ?>
                                </td>
                                <td>
                                    <?php if ( $is_folder ): ?>
                                        —
                                    <?php else: ?>
                                        <?= htmlspecialchars(U::displaySize((int)$item['bytelen'])) ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($this->formatStamp($item['created_at'])) ?></td>
                                <td>
                                    <?php if ( ! $is_folder ): ?>
                                        <button type="button" class="btn btn-xs btn-default btn-copy-link"
                                                data-url="<?= htmlspecialchars($copy_url) ?>"
                                                aria-label="Copy link to <?= htmlspecialchars($item['name']) ?>">Copy link</button>
                                    <?php endif; ?>
                                    <?php if ( $is_instructor && ! $is_folder ): ?>
                                        <a class="btn btn-xs btn-default" href="<?= htmlspecialchars($tool_home . '/replace/' . (int)$item['file_id'] . '?folder=' . rawurlencode($folder)) ?>">Replace</a>
                                    <?php endif; ?>
                                    <?php if ( $is_instructor && ! $is_reserved_root ): ?>
                                        <form method="post" action="<?= htmlspecialchars($tool_home . '/delete/' . (int)$item['file_id']) ?>" style="display: inline;"
                                              onsubmit="return confirm(<?= htmlspecialchars(json_encode('Delete '.$item['name'].'?'), ENT_QUOTES) ?>);">
                                            <?= $csrf ?>
                                            <input type="hidden" name="folder" value="<?= htmlspecialchars($folder) ?>">
                                            <button type="submit" class="btn btn-xs btn-danger" aria-label="Delete <?= htmlspecialchars($item['name']) ?>">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </main>
        <?php
        $OUTPUT->footerStart();
        ?>
        <style>
        button.files-info {
            padding: 0 4px;
            line-height: 1;
            vertical-align: middle;
        }
        button.files-info.files-info-student,
        button.files-info.files-info-student:hover,
        button.files-info.files-info-student:focus {
            color: #3c763d;
        }
        button.files-info.files-info-public,
        button.files-info.files-info-public:hover,
        button.files-info.files-info-public:focus {
            color: #31b0d5;
        }
        button.files-info.files-info-private,
        button.files-info.files-info-private:hover,
        button.files-info.files-info-private:focus {
            color: #a94442;
        }
        button.files-info.files-info-obscure,
        button.files-info.files-info-obscure:hover,
        button.files-info.files-info-obscure:focus {
            color: #337ab7;
        }
        button.files-info.files-help,
        button.files-info.files-help:hover,
        button.files-info.files-help:focus {
            color: #555;
        }
        .files-info-pop {
            position: absolute;
            z-index: 1060;
            max-width: 280px;
            padding: 8px 12px;
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-shadow: 0 6px 12px rgba(0,0,0,.175);
            font-size: 13px;
            line-height: 1.4;
        }
        .files-info-pop.files-info-pop-wide {
            max-width: 440px;
        }
        .files-info-pop p {
            margin: 0 0 8px;
        }
        .files-info-pop p:last-child {
            margin-bottom: 0;
        }
        </style>
        <script>
        (function() {
            var openPop = null;
            var openBtn = null;
            function closeInfo() {
                if (openPop) {
                    openPop.remove();
                    openPop = null;
                }
                if (openBtn) {
                    openBtn.setAttribute('aria-expanded', 'false');
                    openBtn = null;
                }
            }
            document.querySelectorAll('.files-info').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (openBtn === btn) {
                        closeInfo();
                        return;
                    }
                    closeInfo();
                    var pop = document.createElement('div');
                    pop.className = 'files-info-pop';
                    if (btn.classList.contains('files-help')) {
                        pop.classList.add('files-info-pop-wide');
                    }
                    pop.setAttribute('role', 'status');
                    pop.innerHTML = btn.getAttribute('data-info') || '';
                    document.body.appendChild(pop);
                    var r = btn.getBoundingClientRect();
                    pop.style.left = (window.scrollX + r.right + 8) + 'px';
                    pop.style.top = (window.scrollY + r.top - 4) + 'px';
                    pop.addEventListener('click', function(ev) { ev.stopPropagation(); });
                    openPop = pop;
                    openBtn = btn;
                    btn.setAttribute('aria-expanded', 'true');
                });
            });
            document.addEventListener('click', closeInfo);
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') closeInfo();
            });
            function copyText(text) {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    return navigator.clipboard.writeText(text);
                }
                return new Promise(function(resolve, reject) {
                    var input = document.createElement('input');
                    input.value = text;
                    document.body.appendChild(input);
                    input.select();
                    try {
                        document.execCommand('copy');
                        resolve();
                    } catch (e) {
                        reject(e);
                    }
                    document.body.removeChild(input);
                });
            }
            document.querySelectorAll('.btn-copy-link').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var url = btn.getAttribute('data-url');
                    var orig = btn.textContent;
                    copyText(url).then(function() {
                        btn.textContent = 'Copied';
                        setTimeout(function() { btn.textContent = orig; }, 1500);
                    }).catch(function() {
                        window.prompt('Copy this link', url);
                    });
                });
            });
        })();
        </script>
        <?php
        $OUTPUT->footerEnd();
    }

    public function json(Request $request)
    {
        $this->requireAuth();
        $link_id = $this->ensureFilesLaunch();
        $is_instructor = $this->isInstructor();

        $rows = FileRepository::allItems($link_id, U::currentContextId());
        $out = array();
        foreach ( $rows as $row ) {
            $meta = FileRepository::decodeMeta($row);
            if ( $meta['kind'] !== FileRepository::KIND_FILE ) {
                continue;
            }
            $folder = $meta['folder'];
            if ( ! $is_instructor && ! FileRepository::isStudentFilesPath($folder) ) {
                continue;
            }
            $path = FileRepository::joinFolder($folder, $row['file_name']);
            $item = FileRepository::lessonsFilePickerItem($row, $folder);
            $item['path'] = $path;
            $item['url'] = $this->filePathUrl(
                $folder,
                $row['file_name'],
                isset($row['file_sha256']) ? $row['file_sha256'] : ''
            );
            $out[] = $item;
        }
        usort($out, function($a, $b) {
            return strcasecmp($a['path'], $b['path']);
        });
        return new JsonResponse($out);
    }

    public function analytics(Request $request)
    {
        return $this->showAnalytics(self::ROUTE, self::NAME);
    }

    public function download(Request $request, $sha256)
    {
        $sha256 = is_string($sha256) ? strtolower($sha256) : '';
        if ( ! FileRepository::isSha256($sha256) ) {
            die('File not found');
        }

        $public = FileRepository::getPublicFileBySha256($sha256);
        if ( $public ) {
            $this->launchFromFileRow($public);
            $this->emitFile($public);
        }

        $this->requireAuth();
        $this->ensureFilesLaunch();
        $is_instructor = $this->isInstructor();

        $candidates = FileRepository::getFileRowsBySha256($sha256, U::currentContextId());
        if ( count($candidates) === 0 ) {
            die('File not found');
        }

        $row = null;
        foreach ( $candidates as $candidate ) {
            $meta = FileRepository::decodeMeta($candidate);
            if ( $is_instructor || ! FileRepository::isPrivatePath($meta['folder']) ) {
                $row = $candidate;
                break;
            }
        }
        if ( ! $row ) {
            die('File not found');
        }

        $this->emitFile($row);
    }

    /**
     * Serve a file by course folder path (/files/Student/notes.pdf).
     * Used by page HTML so links stay path-based instead of sha download URLs.
     */
    public function servePath(Request $request, $path)
    {
        $raw = is_string($path) ? trim(str_replace('\\', '/', $path), '/') : '';
        if ( preg_match('#^download/([a-fA-F0-9]{64})$#', $raw, $m) ) {
            $this->download($request, $m[1]);
            return;
        }
        $path = FileRepository::normalizeFilePath($path);
        if ( $path === null ) {
            die('File not found');
        }

        $public = FileRepository::getPublicFileByPath($path);
        if ( $public ) {
            $this->launchFromFileRow($public);
            $this->emitFile($public);
        }

        $this->requireAuth();
        $link_id = $this->ensureFilesLaunch();
        $is_instructor = $this->isInstructor();

        $row = FileRepository::getFileRowByPath($path, $link_id, U::currentContextId());
        if ( ! $row ) {
            die('File not found');
        }
        $meta = FileRepository::decodeMeta($row);
        if ( ! $is_instructor && FileRepository::isPrivatePath($meta['folder']) ) {
            die('File not found');
        }
        $this->emitFile($row);
    }

    /**
     * Phrase the reader must type before an HTML, zip, or other caution file opens.
     */
    const CAUTION_PHRASE = 'I am sure';

    /**
     * @param mixed $typed
     * @return bool
     */
    public static function cautionPhraseAccepted($typed)
    {
        return is_string($typed) && trim($typed) === self::CAUTION_PHRASE;
    }

    /**
     * Confirmation page for a file that can carry script or an archive.
     *
     * @param string $fileName
     * @param string $kind
     * @param string $action
     * @param string $csrfField
     * @param string $error
     * @return string
     */
    public static function cautionPageHtml($fileName, $kind, $action, $csrfField, $error = '')
    {
        $safeName = htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8');
        $safeKind = htmlspecialchars($kind, ENT_QUOTES, 'UTF-8');
        $safeAction = htmlspecialchars($action, ENT_QUOTES, 'UTF-8');
        $safeError = htmlspecialchars($error, ENT_QUOTES, 'UTF-8');
        $phrase = htmlspecialchars(self::CAUTION_PHRASE, ENT_QUOTES, 'UTF-8');
        $errorHtml = $safeError === '' ? '' : '<p class="error">'.$safeError.'</p>';
        return '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Confirm file</title>
<style>
body { font-family: sans-serif; background: #f4f4f4; margin: 0; color: #222; }
.box { max-width: 34rem; margin: 4rem auto; background: #fff; border: 1px solid #ccc; border-radius: 6px; padding: 1.5rem 1.75rem; }
h1 { font-size: 1.25rem; margin: 0 0 1rem; }
.error { color: #a94442; }
label { display: block; margin: 1rem 0 0.35rem; }
input[type="text"] { width: 100%; box-sizing: border-box; padding: 0.45rem; font: inherit; }
button { margin-top: 1rem; font: inherit; padding: 0.4rem 0.8rem; }
</style>
</head>
<body>
<main class="box" role="dialog" aria-labelledby="confirm-file-title">
<h1 id="confirm-file-title">Confirm file</h1>
<p>This '.$safeKind.' file ('.$safeName.') can contain dangerous information. Are you sure that you want to open or download this file? You can paste this text into an AI or a search engine to get a more detailed explanation.</p>
'.$errorHtml.'
<form method="post" action="'.$safeAction.'">
'.$csrfField.'
<label for="confirm_phrase">Type '.$phrase.' to continue.</label>
<input id="confirm_phrase" name="confirm_phrase" type="text" autocomplete="off" required>
<button type="submit">Open or download</button>
</form>
</main>
</body>
</html>';
    }

    /**
     * True when this POST typed the confirmation phrase. Otherwise an error
     * string, or '' when the request has not been submitted yet.
     *
     * @return true|string
     */
    private function cautionStatus()
    {
        if ( ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' ) {
            return '';
        }
        if ( ! self::csrfOk() ) {
            return 'That confirmation did not go through. Try again.';
        }
        if ( ! self::cautionPhraseAccepted(U::get($_POST, 'confirm_phrase', '')) ) {
            return 'Type '.self::CAUTION_PHRASE.' to continue.';
        }
        return true;
    }

    /**
     * @param string $fileName
     * @param string $kind
     * @param string $error
     */
    private function emitCautionPage($fileName, $kind, $error)
    {
        $action = (isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI']))
            ? $_SERVER['REQUEST_URI']
            : '';
        header('Content-Type: text/html; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
        echo self::cautionPageHtml($fileName, $kind, $action, self::csrfField(), $error);
    }

    /**
     * Serve file bytes. Caller must have attached Context/Link for Access.
     */
    private function emitFile($row)
    {
        global $TSUGI_LAUNCH;

        $file_id = (int)$row['file_id'];
        $filename = isset($row['file_name']) && is_string($row['file_name']) ? $row['file_name'] : '';
        $kind = BlobUtil::cautionFileKind($filename);
        if ( $kind !== null ) {
            $status = $this->cautionStatus();
            if ( $status !== true ) {
                $this->emitCautionPage($filename, $kind, is_string($status) ? $status : '');
                exit;
            }
        }

        $retval = Access::openContent($TSUGI_LAUNCH, $file_id, $kind !== null);
        if ( ! is_array($retval) ) {
            die($retval);
        }

        $lob = $retval[0];
        $type = BlobUtil::downloadContentType($filename, $retval[1]);
        $downloadName = str_replace(array("\r", "\n", '"'), '', $filename);
        if ( U::strlen($type) > 0 ) {
            header('Content-Type: '.$type);
        }
        header('X-Content-Type-Options: nosniff');
        if ( $kind !== null && BlobUtil::cautionFileOpensInline($kind) ) {
            header('Content-Security-Policy: sandbox allow-scripts allow-forms allow-popups allow-popups-to-escape-sandbox');
            header('Content-Disposition: inline; filename="'.$downloadName.'"');
        } else if ( $kind !== null ) {
            header('Content-Disposition: attachment; filename="'.$downloadName.'"');
        } else {
            header('Content-Disposition: inline; filename="'.$downloadName.'"');
        }
        if ( is_string($lob) ) {
            echo($lob);
        } else if ( $lob ) {
            fpassthru($lob);
        }
        exit;
    }

    public function uploadPost(Request $request)
    {
        $this->requireInstructor($this->toolHome(self::ROUTE));
        $link_id = $this->ensureFilesLaunch();
        $context_id = U::currentContextId();
        FileRepository::ensureReservedFolders($link_id, $context_id);

        $folder = $this->postedFolder();
        $redirect = $this->folderUrl($folder === false ? '' : $folder);
        if ( $folder === false ) {
            U::flashError('Invalid folder path');
            return new RedirectResponse($redirect);
        }
        $csrf = $this->requireCsrf($redirect);
        if ( $csrf ) {
            return $csrf;
        }

        if ( BlobUtil::emptyPost() ) {
            U::flashError('Upload failed — the file may be larger than the server allows');
            return new RedirectResponse($redirect);
        }

        $descriptors = $this->uploadedDescriptors();
        if ( count($descriptors) === 0 ) {
            U::flashError('Choose one or more files to upload');
            return new RedirectResponse($redirect);
        }

        $ok = 0;
        $errors = array();
        foreach ( $descriptors as $fdes ) {
            $name = isset($fdes['name']) ? basename($fdes['name']) : 'file';
            if ( FileRepository::nameExists($link_id, $folder, $name, $context_id) ) {
                $errors[] = $name.' already exists in this folder';
                continue;
            }
            $valid = BlobUtil::validateUpload($fdes, true);
            if ( is_string($valid) ) {
                $errors[] = $name.': '.$valid;
                continue;
            }
            $file_id = BlobUtil::uploadToBlob($fdes, true, FileRepository::BACKREF);
            if ( ! $file_id ) {
                $errors[] = $name.': could not store file';
                continue;
            }
            FileRepository::tagFileRow($file_id, $folder, isset($fdes['size']) ? (int)$fdes['size'] : null, $context_id);
            $ok++;
        }

        if ( $ok > 0 ) {
            U::flashSuccess($ok === 1 ? 'File uploaded' : $ok.' files uploaded');
        }
        if ( count($errors) > 0 ) {
            U::flashError(implode('; ', $errors));
        }
        return new RedirectResponse($redirect);
    }

    public function mkdirPost(Request $request)
    {
        $this->requireInstructor($this->toolHome(self::ROUTE));
        $link_id = $this->ensureFilesLaunch();
        $context_id = U::currentContextId();
        FileRepository::ensureReservedFolders($link_id, $context_id);

        $folder = $this->postedFolder();
        $redirect = $this->folderUrl($folder === false ? '' : $folder);
        if ( $folder === false ) {
            U::flashError('Invalid folder path');
            return new RedirectResponse($redirect);
        }
        $csrf = $this->requireCsrf($redirect);
        if ( $csrf ) {
            return $csrf;
        }

        $name = trim(U::get($_POST, 'name', ''));
        if ( ! FileRepository::isValidName($name) ) {
            U::flashError('Folder names can use letters, numbers, spaces, dots, dashes, and underscores');
            return new RedirectResponse($redirect);
        }
        if ( $folder === '' && FileRepository::isReservedName($name) ) {
            U::flashError(FileRepository::STUDENT_FILES_FOLDER.', '.FileRepository::PUBLIC_FOLDER.', and '.FileRepository::PRIVATE_FOLDER.' are reserved folder names');
            return new RedirectResponse($redirect);
        }
        if ( FileRepository::nameExists($link_id, $folder, $name, $context_id) ) {
            U::flashError('A file or folder with that name already exists');
            return new RedirectResponse($redirect);
        }

        FileRepository::createFolder($link_id, $folder, $name, $context_id);
        U::flashSuccess('Folder created');
        return new RedirectResponse($redirect);
    }

    public function deletePost(Request $request, $id)
    {
        $this->requireInstructor($this->toolHome(self::ROUTE));
        $link_id = $this->ensureFilesLaunch();
        $context_id = U::currentContextId();

        $folder = $this->postedFolder();
        $redirect = $this->folderUrl($folder === false ? '' : $folder);
        $csrf = $this->requireCsrf($redirect);
        if ( $csrf ) {
            return $csrf;
        }

        $file_id = (int)$id;
        $row = FileRepository::getItem($file_id, $context_id);
        if ( ! $row ) {
            U::flashError('File not found');
            return new RedirectResponse($redirect);
        }

        $meta = FileRepository::decodeMeta($row);
        if ( $meta['kind'] === FileRepository::KIND_FOLDER ) {
            $child_path = FileRepository::joinFolder($meta['folder'], $row['file_name']);
            if ( FileRepository::isReservedRootFolder($child_path) ) {
                U::flashError('The '.FileRepository::STUDENT_FILES_FOLDER.', '.FileRepository::PUBLIC_FOLDER.', and '.FileRepository::PRIVATE_FOLDER.' folders cannot be deleted');
                return new RedirectResponse($redirect);
            }
            if ( FileRepository::folderHasChildren($link_id, $child_path, $context_id) ) {
                U::flashError('Folder is not empty');
                return new RedirectResponse($redirect);
            }
            FileRepository::deleteFolderRow($file_id, $context_id);
            U::flashSuccess('Folder deleted');
        } else {
            BlobUtil::deleteBlob($file_id);
            U::flashSuccess('File deleted');
        }
        return new RedirectResponse($redirect);
    }

    public function replace(Request $request, $id)
    {
        global $OUTPUT;

        $this->requireInstructor($this->toolHome(self::ROUTE));
        $this->ensureFilesLaunch();
        $context_id = U::currentContextId();

        $folder = $this->requestedFolder();
        $back = $this->folderUrl($folder === false ? '' : $folder);
        $file_id = (int) $id;
        $row = FileRepository::getItem($file_id, $context_id);
        if ( ! is_array($row) ) {
            U::flashError('File not found');
            return new RedirectResponse($back);
        }
        $meta = FileRepository::decodeMeta($row);
        if ( $meta['kind'] === FileRepository::KIND_FOLDER ) {
            U::flashError('Folders cannot be replaced');
            return new RedirectResponse($back);
        }

        $name = isset($row['file_name']) ? (string) $row['file_name'] : 'file';
        $type = isset($row['contenttype']) && is_string($row['contenttype']) ? $row['contenttype'] : '';
        $ending = FileRepository::replacementEndingLabel($name);
        $tool_home = $this->toolHome(self::ROUTE);

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        $csrf = $this->csrfField();
        ?>
        <main class="container" role="main" id="main-content">
            <h1>Replacing <?= htmlspecialchars($name) ?></h1>
            <?php if ( $type === '' ): ?>
                <p>This file has no type on record, so it cannot be replaced.</p>
            <?php else: ?>
                <p>
                    The current file is <code><?= htmlspecialchars($type) ?></code>
                    <?php if ( $ending === 'no suffix' ): ?>
                        and has no suffix.
                    <?php else: ?>
                        and ends in <?= htmlspecialchars($ending) ?>.
                    <?php endif; ?>
                    The new file has to match both.
                    The name and the link stay the same.
                </p>
                <form method="post" action="<?= htmlspecialchars($tool_home . '/replace/' . $file_id) ?>" enctype="multipart/form-data">
                    <?= $csrf ?>
                    <input type="hidden" name="folder" value="<?= htmlspecialchars($folder === false ? '' : $folder) ?>">
                    <div class="form-group">
                        <label for="replacement">New file</label>
                        <input type="file" id="replacement" name="replacement" required>
                        <p class="help-block">Max <?= htmlspecialchars(U::displaySize(BlobUtil::maxUploadBytes())) ?></p>
                    </div>
                    <button type="submit" class="btn btn-primary">Replace</button>
                    <a class="btn btn-default" href="<?= htmlspecialchars($back) ?>">Cancel</a>
                </form>
            <?php endif; ?>
        </main>
        <?php
        $OUTPUT->footer();
    }

    public function replacePost(Request $request, $id)
    {
        $this->requireInstructor($this->toolHome(self::ROUTE));
        $this->ensureFilesLaunch();
        $context_id = U::currentContextId();

        $file_id = (int) $id;
        $folder = $this->postedFolder();
        $back = $this->folderUrl($folder === false ? '' : $folder);
        $again = $this->toolHome(self::ROUTE).'/replace/'.$file_id;
        if ( is_string($folder) && $folder !== '' && $folder !== false ) {
            $again .= '?folder='.rawurlencode($folder);
        }

        $csrf = $this->requireCsrf($again);
        if ( $csrf ) {
            return $csrf;
        }
        if ( BlobUtil::emptyPost() || BlobUtil::requestLargerThanPhpPostLimit() ) {
            U::flashError(BlobUtil::phpUploadTooLargeMessage());
            return new RedirectResponse($again);
        }
        if ( ! isset($_FILES['replacement']) || ! is_array($_FILES['replacement']) ) {
            U::flashError('Choose a file to upload');
            return new RedirectResponse($again);
        }

        $fdes = $_FILES['replacement'];
        $valid = BlobUtil::validateUpload($fdes, true);
        if ( is_string($valid) ) {
            U::flashError($valid);
            return new RedirectResponse($again);
        }

        $result = FileRepository::replaceFile($file_id, $context_id, $fdes);
        if ( $result !== true ) {
            U::flashError(is_string($result) ? $result : 'Could not store the replacement file');
            return new RedirectResponse($again);
        }

        $name = isset($fdes['name']) ? basename((string) $fdes['name']) : 'File';
        $row = FileRepository::getItem($file_id, $context_id);
        if ( is_array($row) && isset($row['file_name']) && is_string($row['file_name']) && $row['file_name'] !== '' ) {
            $name = $row['file_name'];
        }
        U::flashSuccess('Replaced '.$name);
        return new RedirectResponse($back);
    }

    /**
     * Attach Context + the Files synthetic Link so BlobUtil/Access use context_id and link_id.
     * Does not persist link_id into the session (other LMS tools keep their own link).
     *
     * @return int link_id
     */
    private function ensureFilesLaunch()
    {
        global $CONTEXT, $LINK, $TSUGI_LAUNCH;

        LTIX::getConnection();
        $context_id = U::currentContextId();
        if ( ! $context_id ) {
            die('Context required');
        }

        $link_id = FileRepository::ensureLink($context_id);
        if ( ! $link_id ) {
            die('Unable to create Files link');
        }

        if ( ! isset($TSUGI_LAUNCH) || ! is_object($TSUGI_LAUNCH) ) {
            $TSUGI_LAUNCH = new \Tsugi\Core\Launch();
        }

        $lti = (isset($_SESSION[TSUGI_SESSION_LTI]) && is_array($_SESSION[TSUGI_SESSION_LTI]))
            ? $_SESSION[TSUGI_SESSION_LTI] : array();

        if ( ! is_object($CONTEXT) || empty($CONTEXT->id) ) {
            $CONTEXT = new Context();
            $CONTEXT->id = $context_id;
            if ( isset($lti['context_title']) ) $CONTEXT->title = $lti['context_title'];
            if ( isset($lti['key_key']) ) $CONTEXT->key = $lti['key_key'];
            if ( isset($lti['secret']) ) $CONTEXT->secret = $lti['secret'];
            if ( isset($lti['context_key']) ) $CONTEXT->context_id = $lti['context_key'];
        }
        if ( ! isset($CONTEXT->key) || $CONTEXT->key === null || $CONTEXT->key === '' ) {
            $CONTEXT->key = isset($lti['key_key']) ? $lti['key_key'] : '';
        }
        $CONTEXT->launch = $TSUGI_LAUNCH;
        $TSUGI_LAUNCH->context = $CONTEXT;

        $LINK = new Link();
        $LINK->id = $link_id;
        $LINK->title = self::NAME;
        $LINK->launch = $TSUGI_LAUNCH;
        $TSUGI_LAUNCH->link = $LINK;

        Courses::wireLaunchConnection();

        return $link_id + 0;
    }

    /**
     * Attach Context + Link from a blob_file row so Access can read the blob
     * without a logged-in session (Public downloads).
     */
    private function launchFromFileRow($row)
    {
        global $CONTEXT, $LINK, $TSUGI_LAUNCH;

        LTIX::getConnection();
        if ( ! isset($TSUGI_LAUNCH) || ! is_object($TSUGI_LAUNCH) ) {
            $TSUGI_LAUNCH = new \Tsugi\Core\Launch();
        }

        $CONTEXT = new Context();
        $CONTEXT->id = $row['context_id'];
        $CONTEXT->key = '';
        $CONTEXT->launch = $TSUGI_LAUNCH;
        $TSUGI_LAUNCH->context = $CONTEXT;

        $LINK = new Link();
        $LINK->id = $row['link_id'];
        $LINK->title = self::NAME;
        $LINK->launch = $TSUGI_LAUNCH;
        $TSUGI_LAUNCH->link = $LINK;

        Courses::wireLaunchConnection();
    }

    private function requestedFolder()
    {
        return FileRepository::normalizeFolder(U::get($_GET, 'folder', ''));
    }

    private function postedFolder()
    {
        return FileRepository::normalizeFolder(U::get($_POST, 'folder', ''));
    }

    private function browseParent($folder, $is_instructor)
    {
        if ( ! $is_instructor && strcasecmp($folder, FileRepository::STUDENT_FILES_FOLDER) === 0 ) {
            return null;
        }
        return FileRepository::parentFolder($folder);
    }

    /**
     * Access help for a path: Student (green), Public (cyan), Private (red), or obscure (blue).
     *
     * @return array{text: string, class: string}
     */
    private function accessInfoForPath($path, $is_folder)
    {
        if ( FileRepository::isStudentFilesPath($path) ) {
            return array(
                'text' => 'Students see these files when they open the Files tool.',
                'class' => 'files-info-student'
            );
        }
        if ( FileRepository::isPublicPath($path) ) {
            return array(
                'text' => $is_folder
                    ? 'Files in Public are not browseable in the Files tool. Anyone with a link to a file can open it, even if they are not logged in.'
                    : 'Anyone with the link can open this file, even if they are not logged in. It is not browseable when students open the Files tool.',
                'class' => 'files-info-public'
            );
        }
        if ( FileRepository::isPrivatePath($path) ) {
            return array(
                'text' => 'No one except the instructor can view these files, even if they have a link. Instructors might stage files under Private and then move or copy them into Student to share them with students.',
                'class' => 'files-info-private'
            );
        }
        return array(
            'text' => $is_folder
                ? 'This folder is hidden when students browse Files. Any member of the course with a link to the file can access the file.'
                : 'This file is not shown to students who browse Files. Any member of the course with a link to the file can access the file.',
            'class' => 'files-info-obscure'
        );
    }

    /**
     * Circled-i button that shows access help on click.
     *
     * @param array{text: string, class: string}|null $info
     */
    private function infoButton($label, $info)
    {
        if ( ! is_array($info) || empty($info['text']) ) {
            return '';
        }
        $extra = isset($info['class']) ? $info['class'] : '';
        return '<button type="button" class="btn btn-link files-info '.htmlspecialchars($extra).'"'
            .' aria-label="About '.htmlspecialchars($label).'"'
            .' aria-expanded="false"'
            .' data-info="'.htmlspecialchars($info['text']).'">'
            .'<span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span>'
            .'</button>';
    }

    /**
     * Question-mark help next to Course files in the breadcrumb.
     */
    private function courseFilesHelpButton()
    {
        $html = '<p>This is where you store files for the course.</p>'
            .'<p>Files in <strong>Private</strong> are instructor only. No one except the instructor can view these files, even if they have a link. Instructors might stage files under Private and then move or copy them into Student to share them with students.</p>'
            .'<p>Files in <strong>Public</strong> are not browseable in the Files tool. Anyone with the link can open them, even if they are not logged in. You can put a file in Public, copy the link, and send it in email.</p>'
            .'<p>Files in the rest of Course files are accessible via a link to people in the course. Students cannot browse those files, or any subfolders outside the <strong>Student</strong> folder.</p>'
            .'<p>A Common Cartridge import places files at the top level or in subfolders—not in Student, Public, or Private. Link to them from Pages or Lessons, or move them into Student if you want students to see them when they open the Files tool.</p>';
        return '<button type="button" class="btn btn-link files-info files-help"'
            .' aria-label="Help about Course files"'
            .' aria-expanded="false"'
            .' data-info="'.htmlspecialchars($html).'">'
            .'<span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span>'
            .'</button>';
    }

    /**
     * Course-path URL for a file (/files/Student/notes.pdf).
     * Stable when the bytes are replaced. Falls back to the sha download
     * URL only when the path cannot be formed.
     *
     * @param string $folder
     * @param string $name
     * @param string $sha
     * @return string
     */
    private function filePathUrl($folder, $name, $sha = '')
    {
        $path = FileRepository::joinFolder($folder, $name);
        $href = FileRepository::hrefForPath($path);
        if ( is_string($href) && $href !== '' && strpos($href, self::ROUTE) === 0 ) {
            return rtrim($this->toolHome(self::ROUTE), '/') . substr($href, strlen(self::ROUTE));
        }
        if ( is_string($sha) && FileRepository::isSha256($sha) ) {
            return $this->toolHome(self::ROUTE) . '/download/' . strtolower($sha);
        }
        return rtrim($this->toolHome(self::ROUTE), '/');
    }

    private function folderUrl($folder)
    {
        $home = $this->toolHome(self::ROUTE);
        if ( $folder === '' ) {
            return $home;
        }
        if ( ! $this->isInstructor() && strcasecmp($folder, FileRepository::STUDENT_FILES_FOLDER) === 0 ) {
            return $home;
        }
        return $home . '?folder=' . rawurlencode($folder);
    }

    private function breadcrumbs($folder, $is_instructor = true)
    {
        $crumbs = array(
            array('label' => 'Course files', 'url' => $this->folderUrl(''))
        );
        if ( $is_instructor ) {
            $crumbs[0]['help'] = true;
        }
        if ( $folder === '' ) {
            return $crumbs;
        }
        $so_far = '';
        $parts = explode('/', $folder);
        foreach ( $parts as $i => $part ) {
            $so_far = FileRepository::joinFolder($so_far, $part);
            if ( ! $is_instructor && $i === 0 && strcasecmp($part, FileRepository::STUDENT_FILES_FOLDER) === 0 ) {
                continue;
            }
            $crumb = array('label' => $part, 'url' => $this->folderUrl($so_far));
            if ( $is_instructor && $i === 0 ) {
                $crumb['info'] = $this->accessInfoForPath($part, true);
            }
            $crumbs[] = $crumb;
        }
        return $crumbs;
    }

    private function absoluteUrl($path)
    {
        global $CFG;
        if ( strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0 ) {
            return $path;
        }
        $base = '';
        if ( isset($CFG->apphome) && preg_match('#^https?://#', $CFG->apphome) ) {
            $parts = parse_url($CFG->apphome);
            if ( $parts && isset($parts['scheme']) && isset($parts['host']) ) {
                $base = $parts['scheme'].'://'.$parts['host'];
                if ( ! empty($parts['port']) ) {
                    $base .= ':'.$parts['port'];
                }
            }
        }
        if ( $base === '' && isset($CFG->wwwroot) && preg_match('#^https?://#', $CFG->wwwroot) ) {
            $parts = parse_url($CFG->wwwroot);
            if ( $parts && isset($parts['scheme']) && isset($parts['host']) ) {
                $base = $parts['scheme'].'://'.$parts['host'];
                if ( ! empty($parts['port']) ) {
                    $base .= ':'.$parts['port'];
                }
            }
        }
        return $base . $path;
    }

    private function uploadedDescriptors()
    {
        if ( ! isset($_FILES['uploads']) ) {
            return array();
        }
        $bag = $_FILES['uploads'];
        if ( ! isset($bag['name']) ) {
            return array();
        }
        if ( ! is_array($bag['name']) ) {
            return array($bag);
        }
        $out = array();
        foreach ( $bag['name'] as $i => $name ) {
            $out[] = array(
                'name' => $bag['name'][$i],
                'type' => $bag['type'][$i],
                'tmp_name' => $bag['tmp_name'][$i],
                'error' => $bag['error'][$i],
                'size' => $bag['size'][$i]
            );
        }
        return $out;
    }

    private function formatStamp($stamp)
    {
        if ( empty($stamp) || $stamp === '1970-01-02 00:00:00' ) {
            return '';
        }
        $ts = strtotime($stamp);
        if ( $ts === false ) {
            return $stamp;
        }
        return date('Y-m-d H:i', $ts);
    }
}
