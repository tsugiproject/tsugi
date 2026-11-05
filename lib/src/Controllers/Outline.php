<?php

namespace Tsugi\Controllers;

use Tsugi\Core\ReqScope;
use Tsugi\Grades\GradeUtil;
use Tsugi\Lumos\Application;
use Tsugi\Services\Lessons\LessonsService;
use Symfony\Component\HttpFoundation\Request;

/**
 * Every module on one page. Module headers and section headings open and close.
 *
 * The rows inside a module are {@see Lessons::renderSingle()}. There is no
 * pager, and none of the author controls (drag, add, delete, publish).
 */
class Outline extends Lessons {

    const ROUTE = '/outline';

    /** @var bool */
    private static $classroomOnce = false;

    /** @var bool */
    private static $allowToggle = false;

    /** @var string */
    private static $moduleKey = '';

    /** @var int */
    private static $headerIndex = 0;

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, 'Outline@get');
        $app->router->get($prefix.'/', 'Outline@get');
        $app->router->get($prefix.'/{anchor}', 'Outline@get');
        $app->router->get($prefix.'/{anchor}/{path:.*}', 'Outline@redirectToIndex');
        $app->router->get($prefix.'_launch/{anchor}', function(Request $request, $anchor = null) use ($app) {
            return Outline::launch($app, $anchor);
        });
    }

    /**
     * The whole document, even when the URL names one module.
     * That module is the one we scroll to.
     *
     * @param mixed $anchor
     * @return LessonsService|false
     */
    protected function loadViewLessons($anchor) {
        $all = \Tsugi\Core\Manifest::currentLessons(null);
        if ( $all && is_string($anchor) && $anchor !== '' ) {
            $all->anchor = $anchor;
        }
        return $all;
    }

    protected function openLessonsView() {
        echo('<div class="container">');
    }

    /**
     * Video and link modals exist on this page even when no single module is selected.
     *
     * @param \Tsugi\Services\Lessons\LessonsService $lessons
     * @param bool $buffer
     */
    public static function footer($lessons, $buffer=false) {
        $savedPosition = $lessons->position;
        $forced = false;
        if ( ! $lessons->isSingle() ) {
            $lessons->position = 0;
            $forced = true;
        }
        $html = parent::footer($lessons, true);
        if ( $forced ) {
            $lessons->position = $savedPosition;
        }
        if ( $buffer ) {
            return $html;
        }
        echo($html);
    }

    protected static function stepRootUsesId() {
        return false;
    }

    protected static function echoSingleModuleChrome($lessons, $module, $allgrades, $duedates) {
    }

    protected static function echoSingleClassroomChrome() {
        if ( self::$classroomOnce ) {
            return;
        }
        self::$classroomOnce = true;
        parent::echoSingleClassroomChrome();
    }

    protected static function classroomToggleMarkup($lessons) {
        if ( ! self::$allowToggle ) {
            return '';
        }
        return parent::classroomToggleMarkup($lessons);
    }

    /**
     * The heading stays an h2, with the expando inside it, so the rows under
     * it keep the same shape as Lessons and Content.
     *
     * @param object|array<string,mixed> $item
     */
    protected static function echoItemHeading($item) {
        if ( is_array($item) ) {
            $item = (object) $item;
        }
        $item = \Tsugi\Services\Lessons\LessonsNormalize::normalizeItemObject($item);
        self::$headerIndex++;
        $key = self::$moduleKey.'|'.self::$headerIndex;
        $text = isset($item->title) ? $item->title : (isset($item->text) ? $item->text : '');
        $extra = (isset($item->class) && is_string($item->class) && $item->class !== '') ? ' '.$item->class : '';
        echo('<h2 class="tsugi-tree-section-head'.htmlspecialchars($extra, ENT_QUOTES, 'UTF-8').'">');
        echo('<button type="button" class="tsugi-tree-toggle" data-tree-header="'.htmlspecialchars($key, ENT_QUOTES, 'UTF-8').'" aria-expanded="true" aria-label="'.htmlspecialchars(__('Expand or collapse section'), ENT_QUOTES, 'UTF-8').'"></button>');
        echo(htmlentities((string) $text)."</h2>\n");
    }

    /**
     * @param \Tsugi\Services\Lessons\LessonsService $lessons
     * @param bool $buffer
     */
    public static function render($lessons, $buffer=false) {
        if ( $lessons->isEmpty() && ! $lessons->isSingle() ) {
            return parent::render($lessons, $buffer);
        }
        if ( $buffer ) {
            ob_start();
        }
        self::$classroomOnce = false;
        self::$allowToggle = false;

        $focus = '';
        if ( is_string($lessons->anchor) && $lessons->anchor !== '' ) {
            $focus = $lessons->anchor;
        }
        $base = isset($lessons->toolHome) && is_string($lessons->toolHome) ? $lessons->toolHome : '';
        $savedModule = $lessons->module;
        $savedPosition = $lessons->position;

        echo('<div class="tsugi-tree" data-base="'.htmlspecialchars($base, ENT_QUOTES, 'UTF-8').'">'."\n");
        self::echoTreeStyle();
        self::echoTreeFoldScript();

        $title = (isset($lessons->lessons->title) && is_string($lessons->lessons->title)) ? $lessons->lessons->title : '';
        if ( $title !== '' ) {
            echo('<h1>'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8')."</h1>\n");
        }
        if ( isset($lessons->lessons->description) && is_string($lessons->lessons->description) && $lessons->lessons->description !== '' ) {
            echo('<p property="description">'.$lessons->lessons->description."</p>\n");
        }

        echo('<div class="tsugi-tree-toolbar">');
        echo('<button type="button" class="tsugi-tree-all" data-tree-all="open">'.htmlspecialchars(__('Expand all'), ENT_QUOTES, 'UTF-8').'</button>');
        echo('<button type="button" class="tsugi-tree-all" data-tree-all="shut">'.htmlspecialchars(__('Contract all'), ENT_QUOTES, 'UTF-8').'</button>');
        echo("</div>\n");

        $allgrades = array();
        foreach ( GradeUtil::loadGradesCurrentUser() as $row ) {
            $allgrades[$row['resource_link_id']] = $row['grade'];
        }
        $duedates = array();
        if ( ReqScope::currentContextId() !== 0 ) {
            $duedates = GradeUtil::loadDueDatesForDisplay(ReqScope::currentContextId());
        }

        $modules = (isset($lessons->lessons->modules) && is_array($lessons->lessons->modules))
            ? $lessons->lessons->modules : array();
        $index = 0;
        foreach ( $modules as $module ) {
            $index++;
            if ( ! is_object($module) || ! self::moduleShown($module, $focus) ) {
                continue;
            }
            $anchor = isset($module->anchor) ? (string) $module->anchor : '';
            $safe = preg_replace('/[^A-Za-z0-9_-]/', '', $anchor);
            if ( ! is_string($safe) || $safe === '' ) {
                $safe = 'n'.$index;
            }
            $domId = 'tsugi-tree-mod-'.$index.'-'.$safe;
            $bodyId = 'tsugi-tree-body-'.$index.'-'.$safe;
            $focused = ($focus !== '' && $anchor === $focus);

            echo('<div class="tsugi-tree-module" id="'.htmlspecialchars($domId, ENT_QUOTES, 'UTF-8').'" data-anchor="'.htmlspecialchars($anchor, ENT_QUOTES, 'UTF-8').'"'.($focused ? ' data-focus="1"' : '').'>');
            echo('<div class="tsugi-tree-module-head">');
            echo('<button type="button" class="tsugi-tree-toggle" aria-expanded="true" aria-controls="'.htmlspecialchars($bodyId, ENT_QUOTES, 'UTF-8').'" aria-label="'.htmlspecialchars(__('Expand or collapse module'), ENT_QUOTES, 'UTF-8').'"></button>');
            echo('<h2 class="tsugi-tree-module-title">');
            if ( isset($module->image) && is_string($module->image) && $module->image !== '' ) {
                $src = LessonsService::expandLink($module->image);
                echo('<img class="tsugi-tree-module-image" alt="" src="'.htmlspecialchars((string) $src, ENT_QUOTES, 'UTF-8').'">');
            }
            $moduleTitle = isset($module->title) ? (string) $module->title : '';
            echo(htmlspecialchars($moduleTitle, ENT_QUOTES, 'UTF-8'));
            $modProgress = $lessons->moduleLtiProgressPoints($module, $allgrades, $duedates);
            self::echoModuleAggregateProgressBadge(
                $lessons,
                $modProgress[0],
                $modProgress[1],
                $modProgress[2],
                $allgrades,
                $duedates,
                true
            );
            if ( $anchor !== '' ) {
                self::echoClassroomModuleInstall($anchor, $moduleTitle);
            }
            echo('</h2></div>');
            echo('<div class="tsugi-tree-module-body" id="'.htmlspecialchars($bodyId, ENT_QUOTES, 'UTF-8').'">');

            $lessons->module = $module;
            $lessons->position = $index;
            self::$moduleKey = $index.'-'.$safe;
            self::$headerIndex = 0;
            $html = static::renderSingle($lessons, true);
            if ( is_string($html) ) {
                echo($html);
            }
            echo('</div></div>'."\n");
            echo('<script>tsugiTreeFold(document.currentScript.previousElementSibling);</script>'."\n");
        }

        $focusModule = ($focus !== '') ? $lessons->getModuleByAnchor($focus) : null;
        $lessons->module = is_object($focusModule) ? $focusModule : $savedModule;
        self::$allowToggle = true;
        echo(self::classroomToggleMarkup($lessons));
        self::$allowToggle = false;

        self::echoTreeClickScript();
        echo("</div>\n");

        $lessons->module = $savedModule;
        $lessons->position = $savedPosition;

        if ( $buffer ) {
            return ob_get_clean();
        }
        return null;
    }

    /**
     * Hidden and login-only modules stay out of the page, unless the URL asked for one.
     *
     * @param object $module
     * @param string $focus
     */
    private static function moduleShown($module, $focus) {
        $anchor = isset($module->anchor) ? (string) $module->anchor : '';
        if ( $focus !== '' && $anchor === $focus ) {
            return true;
        }
        if ( isset($module->hidden) && $module->hidden ) {
            return false;
        }
        if ( isset($module->login) && $module->login && ! ReqScope::isLoggedIn() ) {
            return false;
        }
        return true;
    }

    private static function echoTreeStyle() {
        echo <<<'CSS'
<style>
.tsugi-tree-module {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin: 0 0 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    scroll-margin-top: 72px;
}
.tsugi-tree-module-head {
    background: #f8f9fa;
    padding: 12px 16px;
    border-bottom: 1px solid #ddd;
    display: flex;
    align-items: center;
    gap: 4px;
}
.tsugi-tree-module-head:hover { background: #e9ecef; }
.tsugi-tree-module-title {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}
.tsugi-tree-module-image {
    width: 1.6em;
    height: 1.6em;
    object-fit: contain;
}
.tsugi-tree-toolbar {
    display: flex;
    gap: 0.5rem;
    margin: 0 0 14px;
}
.tsugi-tree-toolbar button {
    font: inherit;
    font-size: 0.85rem;
    line-height: 1.4;
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 4px;
    padding: 0.12rem 0.55rem;
    cursor: pointer;
}
.tsugi-tree-toolbar button:hover { background: #f0f0f0; }
.tsugi-tree-module-body { padding: 16px 20px 20px; }
.tsugi-tree-module-body.is-collapsed { display: none; }
.tsugi-tree .tsugi-step-progress { position: static; top: auto; }
.tsugi-tree-toggle {
    cursor: pointer;
    color: #666;
    font-size: 14px;
    padding: 4px 8px;
    border: none;
    background: transparent;
    display: inline-flex;
    align-items: center;
    transition: transform 0.2s ease;
}
.tsugi-tree-toggle:hover { color: #333; }
.tsugi-tree-toggle.is-collapsed { transform: rotate(-90deg); }
.tsugi-tree-toggle::before { content: "▼"; font-size: 12px; }
h2.tsugi-tree-section-head {
    display: flex;
    align-items: center;
    gap: 4px;
    background: #f8f9fa;
    margin: 12px 0 0;
    padding: 4px 8px;
    font-size: 16px;
    font-weight: 600;
}
.tsugi-tree-fold.is-collapsed { display: none; }
</style>
CSS;
        echo("\n");
    }

    private static function echoTreeFoldScript() {
        echo <<<'JS'
<script>
function tsugiTreeStorageKey(tree, btn) {
    var base = tree ? (tree.getAttribute("data-base") || "") : "";
    var header = btn.getAttribute("data-tree-header");
    if (header) return "tsugi-tree:" + base + ":h:" + header;
    var mod = btn.closest(".tsugi-tree-module");
    var anchor = mod ? (mod.getAttribute("data-anchor") || mod.id) : "";
    return "tsugi-tree:" + base + ":m:" + anchor;
}
function tsugiTreeSectionNodes(head) {
    var nodes = [];
    var n = head ? head.nextElementSibling : null;
    while (n && !(n.classList && n.classList.contains("tsugi-tree-section-head"))) {
        nodes.push(n);
        n = n.nextElementSibling;
    }
    return nodes;
}
function tsugiTreeSetExpanded(btn, open, persist) {
    if (!btn) return;
    btn.setAttribute("aria-expanded", open ? "true" : "false");
    btn.classList.toggle("is-collapsed", !open);
    var header = btn.getAttribute("data-tree-header");
    if (header) {
        var head = btn.closest(".tsugi-tree-section-head");
        var nodes = tsugiTreeSectionNodes(head);
        for (var i = 0; i < nodes.length; i++) {
            nodes[i].classList.add("tsugi-tree-fold");
            nodes[i].classList.toggle("is-collapsed", !open);
        }
    } else {
        var body = document.getElementById(btn.getAttribute("aria-controls") || "");
        if (body) body.classList.toggle("is-collapsed", !open);
    }
    if (!persist) return;
    var tree = btn.closest(".tsugi-tree");
    try {
        var key = tsugiTreeStorageKey(tree, btn);
        if (open) localStorage.removeItem(key);
        else localStorage.setItem(key, "0");
    } catch (e) {}
}
function tsugiTreeReveal(node) {
    if (!node || !node.closest) return;
    var body = node.closest(".tsugi-tree-module-body");
    if (body && body.id) {
        var modBtn = document.querySelector('.tsugi-tree-module-head .tsugi-tree-toggle[aria-controls="' + body.id + '"]');
        if (modBtn) tsugiTreeSetExpanded(modBtn, true, true);
    }
    var prev = node.previousElementSibling;
    var start = node.parentElement;
    while (start && start !== body) {
        prev = start.previousElementSibling;
        while (prev) {
            if (prev.classList && prev.classList.contains("tsugi-tree-section-head")) {
                var sbtn = prev.querySelector(".tsugi-tree-toggle");
                if (sbtn) tsugiTreeSetExpanded(sbtn, true, true);
                return;
            }
            prev = prev.previousElementSibling;
        }
        start = start.parentElement;
    }
    prev = node.previousElementSibling;
    while (prev) {
        if (prev.classList && prev.classList.contains("tsugi-tree-section-head")) {
            var btn = prev.querySelector(".tsugi-tree-toggle");
            if (btn) tsugiTreeSetExpanded(btn, true, true);
            return;
        }
        prev = prev.previousElementSibling;
    }
}
function tsugiTreeFold(mod) {
    if (!mod || !mod.getAttribute) return;
    var tree = mod.closest ? mod.closest(".tsugi-tree") : null;
    var base = tree ? (tree.getAttribute("data-base") || "") : "";
    var anchor = mod.getAttribute("data-anchor") || mod.id;
    var head = mod.querySelector(".tsugi-tree-module-head");
    var btn = head ? head.querySelector(".tsugi-tree-toggle") : null;
    var shut = false;
    try {
        shut = localStorage.getItem("tsugi-tree:" + base + ":m:" + anchor) === "0";
    } catch (e) {}
    if (shut && btn) tsugiTreeSetExpanded(btn, false, false);
    var sections = mod.querySelectorAll(".tsugi-tree-toggle[data-tree-header]");
    for (var i = 0; i < sections.length; i++) {
        var hbtn = sections[i];
        var hk = hbtn.getAttribute("data-tree-header") || "";
        var hshut = false;
        try {
            hshut = localStorage.getItem("tsugi-tree:" + base + ":h:" + hk) === "0";
        } catch (err) {}
        if (hshut) tsugiTreeSetExpanded(hbtn, false, false);
    }
}
</script>
JS;
        echo("\n");
    }

    private static function echoTreeClickScript() {
        echo <<<'JS'
<script>
(function () {
    var root = document.querySelector(".tsugi-tree");
    if (!root) return;
    document.addEventListener("click", function (e) {
        var next = e.target && e.target.closest ? e.target.closest(".tsugi-step-next") : null;
        if (!next) return;
        var bar = next.closest(".tsugi-step-progress");
        var stepRoot = bar ? bar.nextElementSibling : null;
        if (!stepRoot) return;
        var boxes = stepRoot.querySelectorAll("input.tsugi-step-box");
        for (var i = 0; i < boxes.length; i++) {
            if (!boxes[i].checked) {
                tsugiTreeReveal(boxes[i]);
                break;
            }
        }
    }, true);
    document.addEventListener("focusin", function (e) {
        var box = e.target;
        if (box && box.classList && box.classList.contains("tsugi-step-box")) tsugiTreeReveal(box);
    }, true);
    root.addEventListener("click", function (e) {
        var all = e.target && e.target.closest ? e.target.closest(".tsugi-tree-all") : null;
        if (all && root.contains(all)) {
            var openAll = all.getAttribute("data-tree-all") === "open";
            var toggles = root.querySelectorAll(".tsugi-tree-toggle");
            for (var n = 0; n < toggles.length; n++) tsugiTreeSetExpanded(toggles[n], openAll, true);
            tsugiTreeSavePlace(root);
            return;
        }
        var btn = e.target && e.target.closest ? e.target.closest(".tsugi-tree-toggle") : null;
        if (!btn || !root.contains(btn)) return;
        var open = btn.getAttribute("aria-expanded") === "false";
        tsugiTreeSetExpanded(btn, open, true);
        tsugiTreeSavePlace(root);
    });

    var placeKey = "tsugi-tree:" + (root.getAttribute("data-base") || "") + ":place";
    var placeReady = false;
    var placeTimer = 0;
    var placeRestoring = false;
    if ("scrollRestoration" in history) history.scrollRestoration = "manual";

    function tsugiTreeModuleAtCenter() {
        var nav = document.getElementById("tsugi_main_nav_bar");
        var navH = nav && nav.offsetHeight ? nav.offsetHeight : 0;
        var y = Math.max(navH + 8, Math.min(window.innerHeight - 8, window.innerHeight / 2));
        var x = Math.max(8, Math.min(window.innerWidth - 8, window.innerWidth / 2));
        var el = document.elementFromPoint(x, y);
        var mod = el && el.closest ? el.closest(".tsugi-tree-module") : null;
        if (mod && root.contains(mod)) return mod;
        var centerDoc = window.scrollY + (window.innerHeight / 2);
        var mods = root.querySelectorAll(".tsugi-tree-module");
        for (var i = 0; i < mods.length; i++) {
            var top = mods[i].getBoundingClientRect().top + window.scrollY;
            if (centerDoc >= top && centerDoc <= top + mods[i].offsetHeight) return mods[i];
        }
        return null;
    }

    function tsugiTreeSavePlace(tree) {
        if (!placeReady || placeRestoring) return;
        var mod = tsugiTreeModuleAtCenter();
        if (!mod) return;
        var top = mod.getBoundingClientRect().top + window.scrollY;
        var place = {
            anchor: mod.getAttribute("data-anchor") || mod.id,
            center: Math.round((window.scrollY + (window.innerHeight / 2)) - top)
        };
        try { localStorage.setItem(placeKey, JSON.stringify(place)); } catch (err) {}
    }

    function tsugiTreeRestorePlace() {
        var raw = null;
        try { raw = localStorage.getItem(placeKey); } catch (err) { return false; }
        if (!raw) return false;
        var place = null;
        try { place = JSON.parse(raw); } catch (err2) { return false; }
        if (!place || !place.anchor) return false;
        var mods = root.querySelectorAll(".tsugi-tree-module");
        var mod = null;
        for (var i = 0; i < mods.length; i++) {
            var anchor = mods[i].getAttribute("data-anchor") || mods[i].id;
            if (anchor === place.anchor) { mod = mods[i]; break; }
        }
        if (!mod) return false;
        var top = mod.getBoundingClientRect().top + window.scrollY;
        var y = top + (place.center || 0) - (window.innerHeight / 2);
        placeRestoring = true;
        window.scrollTo(0, Math.max(0, y));
        window.setTimeout(function () { placeRestoring = false; }, 80);
        return true;
    }

    window.addEventListener("scroll", function () {
        if (placeRestoring || !placeReady) return;
        if (placeTimer) window.clearTimeout(placeTimer);
        placeTimer = window.setTimeout(function () { tsugiTreeSavePlace(root); }, 120);
    }, { passive: true });
    window.addEventListener("pagehide", function () { tsugiTreeSavePlace(root); });
    document.addEventListener("visibilitychange", function () {
        if (document.visibilityState === "hidden") tsugiTreeSavePlace(root);
    });

    function tsugiTreeFinishPlace(arm) {
        var restored = tsugiTreeRestorePlace();
        if (!restored) {
            var focus = root.querySelector('.tsugi-tree-module[data-focus="1"]');
            if (focus && focus.scrollIntoView) {
                placeRestoring = true;
                focus.scrollIntoView({ block: "center" });
                window.setTimeout(function () { placeRestoring = false; }, 80);
            }
        }
        if (arm) placeReady = true;
    }
    function tsugiTreeFinishPlaceSoon(arm) {
        window.requestAnimationFrame(function () {
            window.requestAnimationFrame(function () { tsugiTreeFinishPlace(arm); });
        });
    }
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", function () { window.setTimeout(function () { tsugiTreeFinishPlaceSoon(false); }, 0); });
    } else {
        window.setTimeout(function () { tsugiTreeFinishPlaceSoon(document.readyState === "complete"); }, 0);
    }
    window.addEventListener("load", function () { window.setTimeout(function () { tsugiTreeFinishPlace(true); }, 60); });
JS;
        echo("\n})();\n</script>\n");
    }
}
