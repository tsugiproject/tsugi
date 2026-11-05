<?php

namespace Tsugi\Controllers;

use Tsugi\Core\ReqScope;
use Tsugi\Grades\GradeUtil;
use Tsugi\Lumos\Application;
use Tsugi\Services\Lessons\LessonsService;
use Tsugi\Util\U;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lessons with the module cards in a left column and the selected module on the right.
 *
 * Module HTML is {@see Lessons::renderSingle()}. The previous / all / next pager is omitted.
 * Choosing a card is how you move between modules.
 */
class Content extends Lessons {

    const ROUTE = '/content';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, 'Content@get');
        $app->router->get($prefix.'/', 'Content@get');
        $app->router->get($prefix.'/{anchor}', 'Content@get');
        $app->router->get($prefix.'/{anchor}/{path:.*}', 'Content@redirectToIndex');
        $app->router->get($prefix.'_launch/{anchor}', function(Request $request, $anchor = null) use ($app) {
            return Content::launch($app, $anchor);
        });
    }

    /**
     * /content reopens the last card this browser selected, or the first visible module.
     *
     * The choice is in localStorage, so the index page picks it in the browser and
     * then loads that module. Without JavaScript it opens the first visible module.
     *
     * @param LessonsService $lessons
     * @param mixed $anchor
     * @return Response|null
     */
    protected function viewEntryRedirect($lessons, $anchor) {
        if ( $anchor !== null && $anchor !== '' ) {
            return null;
        }
        $targets = $this->visibleContentTargets($lessons);
        if ( count($targets) < 1 ) {
            return null;
        }
        $first = $targets[0];
        $hrefs = array();
        foreach ( $targets as $target ) {
            $hrefs[$target['anchor']] = $target['href'];
        }
        $base = rtrim($this->toolHome(static::ROUTE), '/');
        $firstUrl = U::addSession($first['href']);
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8">'
            .'<title>'.htmlspecialchars(__('Content'), ENT_QUOTES, 'UTF-8').'</title>'
            .'<script>'
            .'(function(){'
            .'var hrefs='.json_encode($hrefs, JSON_UNESCAPED_SLASHES).';'
            .'var first='.json_encode($first['anchor']).';'
            .'var anchor=first;'
            .'try{var saved=localStorage.getItem("tsugi-content:"+'.json_encode($base).');'
            .'if(saved&&Object.prototype.hasOwnProperty.call(hrefs,saved)){anchor=saved;}}catch(e){}'
            .'if(hrefs[anchor]){location.replace(hrefs[anchor]);}'
            .'})();'
            .'</script>'
            .'<noscript><meta http-equiv="refresh" content="0;url='.htmlspecialchars($firstUrl, ENT_QUOTES, 'UTF-8').'"></noscript>'
            .'</head><body></body></html>';
        return new Response($html);
    }

    /**
     * Visible module anchors in display order, with the card href for each.
     *
     * @param LessonsService $lessons
     * @return array<int, array{anchor: string, href: string}>
     */
    private function visibleContentTargets($lessons) {
        $out = array();
        if ( ! isset($lessons->lessons->modules) || ! is_array($lessons->lessons->modules) ) {
            return $out;
        }
        $base = rtrim($this->toolHome(static::ROUTE), '/');
        foreach ( $lessons->lessons->modules as $module ) {
            if ( isset($module->hidden) && $module->hidden ) {
                continue;
            }
            if ( isset($module->login) && $module->login && ! ReqScope::isLoggedIn() ) {
                continue;
            }
            if ( ! isset($module->anchor) || (string) $module->anchor === '' ) {
                continue;
            }
            $anchor = (string) $module->anchor;
            $out[] = array(
                'anchor' => $anchor,
                'href' => $base.'/'.urlencode($anchor),
            );
        }
        return $out;
    }

    protected function openLessonsView() {
        echo('<script>var b=document.getElementById("body_container");if(b){b.className="container-fluid tsugi-content-bleed";}</script>');
        echo('<div class="container-fluid tsugi-outline-page">');
    }

    protected static function echoModulePager($lessons) {
    }

    public static function render(LessonsService $lessons, $buffer=false) {
        if ( ! $lessons->isSingle() ) {
            return parent::render($lessons, $buffer);
        }
        if ( $buffer ) {
            ob_start();
        }
        self::echoSplit($lessons);
        if ( $buffer ) {
            return ob_get_clean();
        }
        return null;
    }

    /**
     * @param LessonsService $lessons
     */
    private static function echoSplit($lessons) {
        $allgrades = array();
        $rows = GradeUtil::loadGradesCurrentUser();
        foreach ( $rows as $row ) {
            $allgrades[$row['resource_link_id']] = $row['grade'];
        }
        $duedates = array();
        if ( ReqScope::currentContextId() !== 0 ) {
            $duedates = GradeUtil::loadDueDatesForDisplay(ReqScope::currentContextId());
        }
        $gclass = self::isGoogleClassroomInstructor();
        $base = rtrim((string) $lessons->toolHome, '/');
        $current = isset($lessons->anchor) ? (string) $lessons->anchor : '';

        self::echoStyles();
        echo('<div class="tsugi-outline">'."\n");
        echo('<nav class="tsugi-outline-nav" aria-label="'.htmlspecialchars(__('Modules'), ENT_QUOTES, 'UTF-8').'">'."\n");
        $count = 0;
        foreach ( $lessons->lessons->modules as $module ) {
            if ( isset($module->hidden) && $module->hidden ) {
                continue;
            }
            if ( isset($module->login) && $module->login && ! ReqScope::isLoggedIn() ) {
                continue;
            }
            $count++;
            $anchor = isset($module->anchor) ? (string) $module->anchor : '';
            $href = $base.'/'.urlencode($anchor);
            $selected = $anchor !== '' && $anchor === $current;
            self::echoModuleCard($lessons, $module, $count, $allgrades, $duedates, $gclass, $href, $selected);
        }
        echo("</nav>\n");
        echo('<div class="tsugi-outline-main">'."\n");
        parent::renderSingle($lessons, false);
        echo("</div>\n");
        echo("</div>\n");
        $storageBase = json_encode($base);
        $storageAnchor = json_encode($current);
        echo('<script>
(function () {
    var base = '.$storageBase.';
    var anchor = '.$storageAnchor.';
    if (base && anchor) {
        try { localStorage.setItem("tsugi-content:" + base, anchor); } catch (e) {}
    }
    var nav = document.querySelector(".tsugi-outline-nav");
    var main = document.querySelector(".tsugi-outline-main");
    var moved = false;
    try {
        moved = sessionStorage.getItem("tsugi-content-move") === "1";
        if (moved) sessionStorage.removeItem("tsugi-content-move");
    } catch (e) {}
    if (moved && main) main.classList.add("tsugi-outline-enter");
    placeSelected(nav, false);

    if (!nav) return;
    var pending = 0;
    var finish = null;
    nav.addEventListener("click", function (e) {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        var link = e.target.closest("a.tsugi-lessons-module-card-link");
        if (!link || !nav.contains(link)) return;
        var card = link.closest("article.card");
        if (!card) return;
        if (card.classList.contains("tsugi-outline-selected")) {
            e.preventDefault();
            return;
        }
        var href = link.getAttribute("href");
        if (!href) return;
        e.preventDefault();
        var reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
        markSelected(nav, card);
        if (main) main.classList.add("tsugi-outline-leave");
        try { sessionStorage.setItem("tsugi-content-move", "1"); } catch (err) {}
        if (finish) nav.removeEventListener("scrollend", finish);
        if (pending) window.clearTimeout(pending);
        finish = null;
        pending = 0;
        if (reduce) {
            placeSelected(nav, false);
            window.location.href = href;
            return;
        }
        var navRect = nav.getBoundingClientRect();
        var cardRect = card.getBoundingClientRect();
        var delta = (cardRect.top - navRect.top) - (nav.clientHeight / 2) + (cardRect.height / 2);
        var left = false;
        function go() {
            if (left) return;
            left = true;
            if (finish) nav.removeEventListener("scrollend", finish);
            if (pending) window.clearTimeout(pending);
            window.location.href = href;
        }
        if (Math.abs(delta) >= 2) {
            finish = go;
            nav.addEventListener("scrollend", go);
            nav.scrollTo({ top: Math.max(0, nav.scrollTop + delta), behavior: "smooth" });
            pending = window.setTimeout(go, 420);
            return;
        }
        pending = window.setTimeout(go, 180);
    });

    function markSelected(navEl, card) {
        var prev = navEl.querySelector(".tsugi-outline-selected");
        if (prev) {
            prev.classList.remove("tsugi-outline-selected");
            var old = prev.querySelector("a[aria-current]");
            if (old) old.removeAttribute("aria-current");
        }
        card.classList.add("tsugi-outline-selected");
        var current = card.querySelector("a.tsugi-lessons-module-card-link");
        if (current) current.setAttribute("aria-current", "page");
    }

    function placeSelected(navEl, smooth) {
        if (!navEl) return;
        var el = navEl.querySelector(".tsugi-outline-selected");
        if (!el) return;
        var navRect = navEl.getBoundingClientRect();
        var elRect = el.getBoundingClientRect();
        var delta = (elRect.top - navRect.top) - (navEl.clientHeight / 2) + (elRect.height / 2);
        if (Math.abs(delta) < 2) return;
        var top = Math.max(0, navEl.scrollTop + delta);
        if (smooth && navEl.scrollTo) {
            navEl.scrollTo({ top: top, behavior: "smooth" });
            return;
        }
        navEl.scrollTop = top;
    }
})();
</script>'."\n");
    }

    private static function echoStyles() {
        echo('<style>
#body_container.tsugi-content-bleed {
    max-width: none;
    padding-left: 0;
    padding-right: 0;
}
.tsugi-outline-page {
    margin-left: 0;
    margin-right: 0;
    padding-left: 0;
    padding-right: 0;
}
.tsugi-outline {
    display: flex;
    align-items: flex-start;
    gap: 1.25rem;
}
.tsugi-outline-nav {
    flex: 0 0 20rem;
    width: 20rem;
    max-height: calc(100vh - 8rem);
    overflow-y: auto;
    position: sticky;
    top: 6.5rem;
    padding-left: 0;
    padding-right: 0.75rem;
    border-right: 1px solid #ddd;
}
.tsugi-outline-main {
    flex: 1 1 auto;
    min-width: 0;
}
.tsugi-outline-nav .card {
    display: block;
    width: auto;
    height: auto;
    max-height: none;
    margin: 0 0 0.5rem 0;
    transition: background-color 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
}
.tsugi-outline-main.tsugi-outline-leave {
    opacity: 0.4;
    transition: opacity 0.22s ease;
}
.tsugi-outline-main.tsugi-outline-enter {
    animation: tsugi-outline-enter 0.32s ease;
}
@keyframes tsugi-outline-enter {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: none; }
}
@media (prefers-reduced-motion: reduce) {
    .tsugi-outline-nav .card,
    .tsugi-outline-main.tsugi-outline-leave {
        transition: none;
    }
    .tsugi-outline-main.tsugi-outline-enter {
        animation: none;
    }
}
.tsugi-outline-nav .card > div {
    height: auto;
    max-height: 7.5rem;
}
.tsugi-outline-nav .tsugi-lessons-module-card-link {
    display: block;
    overflow: hidden;
}
.tsugi-outline-nav .card.tsugi-outline-selected {
    border: 2px solid #337ab7;
    background: #f4f8fb;
    box-shadow: inset 3px 0 0 #337ab7;
}
@media (max-width: 767px) {
    .tsugi-outline {
        flex-direction: column;
    }
    .tsugi-outline-nav {
        position: relative;
        top: 0;
        flex: none;
        width: 100%;
        max-height: 40vh;
        border-right: 0;
        border-bottom: 1px solid #ddd;
        padding-right: 0;
        padding-bottom: 0.75rem;
    }
}
</style>'."\n");
    }
}
