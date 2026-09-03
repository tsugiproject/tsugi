<?php

namespace Tsugi\Controllers;

use \Tsugi\Util\U;
use Tsugi\Core\LTIX;
use Tsugi\Core\Context;
use Tsugi\Core\Link;
use Tsugi\Blob\BlobUtil;
use Tsugi\Blob\Access;
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
 * Download URLs are content-addressed (sha256 of the file bytes), not
 * blob_file.file_id, so they cannot be enumerated from the primary key.
 */
class Files extends Tool {

    const ROUTE = '/files';
    const NAME = 'Files';
    const REDIRECT = 'tsugi_controllers_files';

    /** Top-level folder whose contents students see when they open the Files tool. */
    const STUDENT_FILES_FOLDER = 'Student';

    /** Top-level folder: anyone with the link can open the file with no login. */
    const PUBLIC_FOLDER = 'Public';

    /** Top-level folder that students cannot access, even with a link. */
    const PRIVATE_FOLDER = 'Private';

    const KIND_FILE = 'file';
    const KIND_FOLDER = 'folder';
    const BACKREF = 'files';
    const FOLDER_CONTENTTYPE = 'inode/directory';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, 'Files@index');
        $app->router->get($prefix.'/', 'Files@index');
        $app->router->get('/'.self::REDIRECT, 'Files@index');
        $app->router->get($prefix.'/json', 'Files@json');
        $app->router->get($prefix.'/analytics', 'Files@analytics');
        $app->router->get($prefix.'/download/{sha256}', 'Files@download');
        $app->router->post($prefix.'/upload', 'Files@uploadPost');
        $app->router->post($prefix.'/mkdir', 'Files@mkdirPost');
        $app->router->post($prefix.'/delete/{id}', 'Files@deletePost');
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
            } else if ( ! $this->isStudentFilesPath($folder) ) {
                return new RedirectResponse($this->folderUrl(''));
            }
        } else {
            $this->ensureReservedFolders($link_id);
        }

        $items = $this->listFolder($link_id, $folder);
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
                                $is_folder = ($item['kind'] === self::KIND_FOLDER);
                                $child_folder = $this->joinFolder($folder, $item['name']);
                                $download_url = $this->downloadUrl($item);
                                $copy_url = $this->absoluteUrl($download_url);
                                $is_reserved_root = $is_folder && $folder === '' && $this->isReservedName($item['name']);
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
                                        <a href="<?= htmlspecialchars($download_url) ?>">
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

        $rows = $this->allItems($link_id);
        $out = array();
        foreach ( $rows as $row ) {
            $meta = $this->decodeMeta($row);
            if ( $meta['kind'] !== self::KIND_FILE ) {
                continue;
            }
            $folder = $meta['folder'];
            if ( ! $is_instructor && ! $this->isStudentFilesPath($folder) ) {
                continue;
            }
            $path = $this->joinFolder($folder, $row['file_name']);
            $download = $this->downloadUrl($row);
            $out[] = array(
                'id' => $row['file_sha256'],
                'title' => $row['file_name'],
                'folder' => $folder,
                'path' => $path,
                'url' => $download
            );
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
        if ( ! $this->isValidSha256($sha256) ) {
            die('File not found');
        }

        $public = $this->getPublicFileBySha256($sha256);
        if ( $public ) {
            $this->launchFromFileRow($public);
            $this->emitFile($public);
        }

        $this->requireAuth();
        $this->ensureFilesLaunch();
        $is_instructor = $this->isInstructor();

        $candidates = $this->getFileRowsBySha256($sha256);
        if ( count($candidates) === 0 ) {
            die('File not found');
        }

        $row = null;
        foreach ( $candidates as $candidate ) {
            $meta = $this->decodeMeta($candidate);
            if ( $is_instructor || ! $this->isPrivatePath($meta['folder']) ) {
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
     * Serve file bytes. Caller must have attached Context/Link for Access.
     */
    private function emitFile($row)
    {
        global $TSUGI_LAUNCH;

        $file_id = (int)$row['file_id'];
        $retval = Access::openContent($TSUGI_LAUNCH, $file_id);
        if ( ! is_array($retval) ) {
            die($retval);
        }

        $lob = $retval[0];
        $type = $retval[1];
        $filename = str_replace(array("\r", "\n", '"'), '', $row['file_name']);
        if ( U::strlen($type) > 0 ) {
            header('Content-Type: '.$type);
        }
        header('Content-Disposition: inline; filename="'.$filename.'"');
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
        $this->ensureReservedFolders($link_id);

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
            if ( $this->nameExists($link_id, $folder, $name) ) {
                $errors[] = $name.' already exists in this folder';
                continue;
            }
            $valid = BlobUtil::validateUpload($fdes, true);
            if ( is_string($valid) ) {
                $errors[] = $name.': '.$valid;
                continue;
            }
            $file_id = BlobUtil::uploadToBlob($fdes, true, self::BACKREF);
            if ( ! $file_id ) {
                $errors[] = $name.': could not store file';
                continue;
            }
            $this->tagFileRow($file_id, $folder, isset($fdes['size']) ? (int)$fdes['size'] : null);
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
        global $CFG, $PDOX, $CONTEXT, $LINK;

        $this->requireInstructor($this->toolHome(self::ROUTE));
        $link_id = $this->ensureFilesLaunch();
        $this->ensureReservedFolders($link_id);

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
        if ( ! $this->isValidName($name) ) {
            U::flashError('Folder names can use letters, numbers, spaces, dots, dashes, and underscores');
            return new RedirectResponse($redirect);
        }
        if ( $folder === '' && $this->isReservedName($name) ) {
            U::flashError(self::STUDENT_FILES_FOLDER.', '.self::PUBLIC_FOLDER.', and '.self::PRIVATE_FOLDER.' are reserved folder names');
            return new RedirectResponse($redirect);
        }
        if ( $this->nameExists($link_id, $folder, $name) ) {
            U::flashError('A file or folder with that name already exists');
            return new RedirectResponse($redirect);
        }

        $full = $this->joinFolder($folder, $name);
        $sha = hash('sha256', 'files-folder|'.$CONTEXT->id.'|'.$LINK->id.'|'.$full);
        $json = json_encode(array('kind' => self::KIND_FOLDER, 'folder' => $folder));
        $PDOX->queryDie(
            "INSERT INTO {$CFG->dbprefix}blob_file
                (context_id, link_id, file_sha256, file_name, contenttype, backref, json, created_at)
             VALUES
                (:CID, :LID, :SHA, :NAME, :TYPE, :BACKREF, :JSON, NOW())",
            array(
                ':CID' => $CONTEXT->id,
                ':LID' => $link_id,
                ':SHA' => $sha,
                ':NAME' => $name,
                ':TYPE' => self::FOLDER_CONTENTTYPE,
                ':BACKREF' => self::BACKREF,
                ':JSON' => $json
            )
        );
        U::flashSuccess('Folder created');
        return new RedirectResponse($redirect);
    }

    public function deletePost(Request $request, $id)
    {
        global $CFG, $PDOX;

        $this->requireInstructor($this->toolHome(self::ROUTE));
        $link_id = $this->ensureFilesLaunch();

        $folder = $this->postedFolder();
        $redirect = $this->folderUrl($folder === false ? '' : $folder);
        $csrf = $this->requireCsrf($redirect);
        if ( $csrf ) {
            return $csrf;
        }

        $file_id = (int)$id;
        $row = $this->getItem($file_id);
        if ( ! $row ) {
            U::flashError('File not found');
            return new RedirectResponse($redirect);
        }

        $meta = $this->decodeMeta($row);
        if ( $meta['kind'] === self::KIND_FOLDER ) {
            $child_path = $this->joinFolder($meta['folder'], $row['file_name']);
            if ( $this->isReservedRootFolder($child_path) ) {
                U::flashError('The '.self::STUDENT_FILES_FOLDER.', '.self::PUBLIC_FOLDER.', and '.self::PRIVATE_FOLDER.' folders cannot be deleted');
                return new RedirectResponse($redirect);
            }
            if ( $this->folderHasChildren($link_id, $child_path) ) {
                U::flashError('Folder is not empty');
                return new RedirectResponse($redirect);
            }
            $PDOX->queryDie(
                "DELETE FROM {$CFG->dbprefix}blob_file WHERE file_id = :ID AND context_id = :CID AND backref = :BR",
                array(':ID' => $file_id, ':CID' => U::currentContextId(), ':BR' => self::BACKREF)
            );
            U::flashSuccess('Folder deleted');
        } else {
            BlobUtil::deleteBlob($file_id);
            U::flashSuccess('File deleted');
        }
        return new RedirectResponse($redirect);
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

        $link_id = $this->lmsEnsureAnalyticsLink(
            $context_id,
            $this->lmsAnalyticsKey(self::ROUTE),
            self::NAME,
            self::ROUTE
        );
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

    private function ensureReservedFolders($link_id)
    {
        $this->ensureTopFolder($link_id, self::STUDENT_FILES_FOLDER);
        $this->ensureTopFolder($link_id, self::PUBLIC_FOLDER);
        $this->ensureTopFolder($link_id, self::PRIVATE_FOLDER);
    }

    private function ensureTopFolder($link_id, $name)
    {
        if ( $this->nameExists($link_id, '', $name) ) {
            return;
        }
        global $CFG, $PDOX, $CONTEXT;
        $sha = hash('sha256', 'files-folder|'.$CONTEXT->id.'|'.$link_id.'|'.$name);
        $json = json_encode(array('kind' => self::KIND_FOLDER, 'folder' => ''));
        $PDOX->queryDie(
            "INSERT INTO {$CFG->dbprefix}blob_file
                (context_id, link_id, file_sha256, file_name, contenttype, backref, json, created_at)
             VALUES
                (:CID, :LID, :SHA, :NAME, :TYPE, :BACKREF, :JSON, NOW())",
            array(
                ':CID' => $CONTEXT->id,
                ':LID' => $link_id,
                ':SHA' => $sha,
                ':NAME' => $name,
                ':TYPE' => self::FOLDER_CONTENTTYPE,
                ':BACKREF' => self::BACKREF,
                ':JSON' => $json
            )
        );
    }

    private function allItems($link_id)
    {
        global $CFG, $PDOX;
        return $PDOX->allRowsDie(
            "SELECT file_id, file_name, file_sha256, contenttype, json, bytelen, created_at, backref
             FROM {$CFG->dbprefix}blob_file
             WHERE context_id = :CID AND link_id = :LID AND backref = :BR
               AND (deleted IS NULL OR deleted = 0)
             ORDER BY file_name ASC",
            array(
                ':CID' => U::currentContextId(),
                ':LID' => $link_id,
                ':BR' => self::BACKREF
            )
        );
    }

    private function getItem($file_id)
    {
        global $CFG, $PDOX;
        return $PDOX->rowDie(
            "SELECT file_id, file_name, file_sha256, contenttype, json, bytelen, created_at, backref, link_id
             FROM {$CFG->dbprefix}blob_file
             WHERE file_id = :ID AND context_id = :CID AND backref = :BR
               AND (deleted IS NULL OR deleted = 0)",
            array(
                ':ID' => $file_id,
                ':CID' => U::currentContextId(),
                ':BR' => self::BACKREF
            )
        );
    }

    private function getFileRowsBySha256($sha256)
    {
        global $CFG, $PDOX;
        $rows = $PDOX->allRowsDie(
            "SELECT file_id, file_name, file_sha256, contenttype, json, bytelen, created_at, backref, link_id, context_id
             FROM {$CFG->dbprefix}blob_file
             WHERE file_sha256 = :SHA AND context_id = :CID AND backref = :BR
               AND (deleted IS NULL OR deleted = 0)",
            array(
                ':SHA' => $sha256,
                ':CID' => U::currentContextId(),
                ':BR' => self::BACKREF
            )
        );
        $out = array();
        foreach ( $rows as $row ) {
            $meta = $this->decodeMeta($row);
            if ( $meta['kind'] === self::KIND_FILE ) {
                $out[] = $row;
            }
        }
        return $out;
    }

    /**
     * A Public file with this content hash, in any course. No login required.
     */
    private function getPublicFileBySha256($sha256)
    {
        global $CFG, $PDOX;

        LTIX::getConnection();
        $rows = $PDOX->allRowsDie(
            "SELECT file_id, file_name, file_sha256, contenttype, json, bytelen, created_at, backref, link_id, context_id
             FROM {$CFG->dbprefix}blob_file
             WHERE file_sha256 = :SHA AND backref = :BR
               AND (deleted IS NULL OR deleted = 0)",
            array(
                ':SHA' => $sha256,
                ':BR' => self::BACKREF
            )
        );
        foreach ( $rows as $row ) {
            $meta = $this->decodeMeta($row);
            if ( $meta['kind'] === self::KIND_FILE && $this->isPublicPath($meta['folder']) ) {
                return $row;
            }
        }
        return null;
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

    private function listFolder($link_id, $folder)
    {
        $rows = $this->allItems($link_id);
        $folders = array();
        $files = array();
        foreach ( $rows as $row ) {
            $meta = $this->decodeMeta($row);
            if ( $meta['folder'] !== $folder ) {
                continue;
            }
            $item = array(
                'file_id' => $row['file_id'],
                'file_sha256' => $row['file_sha256'],
                'name' => $row['file_name'],
                'kind' => $meta['kind'],
                'bytelen' => $row['bytelen'],
                'created_at' => $row['created_at']
            );
            if ( $meta['kind'] === self::KIND_FOLDER ) {
                $folders[] = $item;
            } else {
                $files[] = $item;
            }
        }
        if ( $folder === '' ) {
            usort($folders, function($a, $b) {
                $rank = function($name) {
                    if ( strcasecmp($name, self::STUDENT_FILES_FOLDER) === 0 ) {
                        return 0;
                    }
                    if ( strcasecmp($name, self::PUBLIC_FOLDER) === 0 ) {
                        return 1;
                    }
                    if ( strcasecmp($name, self::PRIVATE_FOLDER) === 0 ) {
                        return 2;
                    }
                    return 3;
                };
                $ra = $rank($a['name']);
                $rb = $rank($b['name']);
                if ( $ra !== $rb ) {
                    return $ra - $rb;
                }
                return strcasecmp($a['name'], $b['name']);
            });
        } else {
            usort($folders, function($a, $b) { return strcasecmp($a['name'], $b['name']); });
        }
        usort($files, function($a, $b) { return strcasecmp($a['name'], $b['name']); });
        return array_merge($folders, $files);
    }

    private function nameExists($link_id, $folder, $name)
    {
        $rows = $this->allItems($link_id);
        foreach ( $rows as $row ) {
            $meta = $this->decodeMeta($row);
            if ( $meta['folder'] === $folder && strcasecmp($row['file_name'], $name) === 0 ) {
                return true;
            }
        }
        return false;
    }

    private function folderHasChildren($link_id, $folder)
    {
        $rows = $this->allItems($link_id);
        foreach ( $rows as $row ) {
            $meta = $this->decodeMeta($row);
            if ( $meta['folder'] === $folder ) {
                return true;
            }
            if ( strpos($meta['folder'], $folder.'/') === 0 ) {
                return true;
            }
        }
        return false;
    }

    private function tagFileRow($file_id, $folder, $bytelen)
    {
        global $CFG, $PDOX;
        $json = json_encode(array('kind' => self::KIND_FILE, 'folder' => $folder));
        $PDOX->queryDie(
            "UPDATE {$CFG->dbprefix}blob_file
             SET json = :JSON, backref = :BR, bytelen = :LEN
             WHERE file_id = :ID AND context_id = :CID",
            array(
                ':JSON' => $json,
                ':BR' => self::BACKREF,
                ':LEN' => $bytelen,
                ':ID' => $file_id,
                ':CID' => U::currentContextId()
            )
        );
    }

    private function decodeMeta($row)
    {
        $kind = self::KIND_FILE;
        $folder = '';
        if ( ! empty($row['json']) ) {
            $data = json_decode($row['json'], true);
            if ( is_array($data) ) {
                if ( isset($data['kind']) && $data['kind'] === self::KIND_FOLDER ) {
                    $kind = self::KIND_FOLDER;
                }
                if ( isset($data['folder']) && is_string($data['folder']) ) {
                    $folder = $data['folder'];
                }
            }
        } else if ( isset($row['contenttype']) && $row['contenttype'] === self::FOLDER_CONTENTTYPE ) {
            $kind = self::KIND_FOLDER;
        }
        return array('kind' => $kind, 'folder' => $folder);
    }

    private function requestedFolder()
    {
        return $this->normalizeFolder(U::get($_GET, 'folder', ''));
    }

    private function postedFolder()
    {
        return $this->normalizeFolder(U::get($_POST, 'folder', ''));
    }

    private function normalizeFolder($raw)
    {
        $raw = str_replace('\\', '/', (string)$raw);
        $raw = trim($raw, '/');
        if ( $raw === '' ) {
            return '';
        }
        $parts = explode('/', $raw);
        $clean = array();
        foreach ( $parts as $part ) {
            $part = trim($part);
            if ( $part === '' ) {
                continue;
            }
            if ( ! $this->isValidName($part) ) {
                return false;
            }
            $clean[] = $part;
        }
        if ( count($clean) === 0 ) {
            return '';
        }
        if ( count($clean) > 12 ) {
            return false;
        }
        if ( strcasecmp($clean[0], self::STUDENT_FILES_FOLDER) === 0 ) {
            $clean[0] = self::STUDENT_FILES_FOLDER;
        } else if ( strcasecmp($clean[0], self::PUBLIC_FOLDER) === 0 ) {
            $clean[0] = self::PUBLIC_FOLDER;
        } else if ( strcasecmp($clean[0], self::PRIVATE_FOLDER) === 0 ) {
            $clean[0] = self::PRIVATE_FOLDER;
        }
        return implode('/', $clean);
    }

    private function isValidName($name)
    {
        if ( ! is_string($name) ) {
            return false;
        }
        $name = trim($name);
        if ( $name === '' || $name === '.' || $name === '..' ) {
            return false;
        }
        if ( strlen($name) > 128 ) {
            return false;
        }
        if ( strpos($name, '/') !== false || strpos($name, '\\') !== false ) {
            return false;
        }
        return (bool) preg_match('/^[A-Za-z0-9._\\- ]+$/', $name);
    }

    private function joinFolder($parent, $name)
    {
        if ( $parent === '' || $parent === null ) {
            return $name;
        }
        return $parent . '/' . $name;
    }

    private function parentFolder($folder)
    {
        if ( $folder === '' ) {
            return null;
        }
        $pos = strrpos($folder, '/');
        if ( $pos === false ) {
            return '';
        }
        return substr($folder, 0, $pos);
    }

    private function browseParent($folder, $is_instructor)
    {
        if ( ! $is_instructor && strcasecmp($folder, self::STUDENT_FILES_FOLDER) === 0 ) {
            return null;
        }
        return $this->parentFolder($folder);
    }

    private function firstFolderSegment($folder)
    {
        if ( $folder === '' || $folder === null ) {
            return '';
        }
        $slash = strpos($folder, '/');
        if ( $slash === false ) {
            return $folder;
        }
        return substr($folder, 0, $slash);
    }

    private function isStudentFilesPath($folder)
    {
        return strcasecmp($this->firstFolderSegment($folder), self::STUDENT_FILES_FOLDER) === 0;
    }

    private function isPublicPath($folder)
    {
        return strcasecmp($this->firstFolderSegment($folder), self::PUBLIC_FOLDER) === 0;
    }

    private function isPrivatePath($folder)
    {
        return strcasecmp($this->firstFolderSegment($folder), self::PRIVATE_FOLDER) === 0;
    }

    private function isReservedName($name)
    {
        return strcasecmp($name, self::STUDENT_FILES_FOLDER) === 0
            || strcasecmp($name, self::PUBLIC_FOLDER) === 0
            || strcasecmp($name, self::PRIVATE_FOLDER) === 0;
    }

    private function isReservedRootFolder($path)
    {
        return strcasecmp($path, self::STUDENT_FILES_FOLDER) === 0
            || strcasecmp($path, self::PUBLIC_FOLDER) === 0
            || strcasecmp($path, self::PRIVATE_FOLDER) === 0;
    }

    /**
     * Access help for a path: Student (green), Public (cyan), Private (red), or obscure (blue).
     *
     * @return array{text: string, class: string}
     */
    private function accessInfoForPath($path, $is_folder)
    {
        if ( $this->isStudentFilesPath($path) ) {
            return array(
                'text' => 'Students see these files when they open the Files tool.',
                'class' => 'files-info-student'
            );
        }
        if ( $this->isPublicPath($path) ) {
            return array(
                'text' => $is_folder
                    ? 'Files in Public are not browseable in the Files tool. Anyone with a link to a file can open it, even if they are not logged in.'
                    : 'Anyone with the link can open this file, even if they are not logged in. It is not browseable when students open the Files tool.',
                'class' => 'files-info-public'
            );
        }
        if ( $this->isPrivatePath($path) ) {
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

    private function isValidSha256($sha)
    {
        return is_string($sha) && (bool) preg_match('/^[a-fA-F0-9]{64}$/', $sha);
    }

    private function downloadUrl($row)
    {
        $sha = isset($row['file_sha256']) ? $row['file_sha256'] : '';
        return $this->toolHome(self::ROUTE) . '/download/' . $sha;
    }

    private function folderUrl($folder)
    {
        $home = $this->toolHome(self::ROUTE);
        if ( $folder === '' ) {
            return $home;
        }
        if ( ! $this->isInstructor() && strcasecmp($folder, self::STUDENT_FILES_FOLDER) === 0 ) {
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
            $so_far = $this->joinFolder($so_far, $part);
            if ( ! $is_instructor && $i === 0 && strcasecmp($part, self::STUDENT_FILES_FOLDER) === 0 ) {
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
