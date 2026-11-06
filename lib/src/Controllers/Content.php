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
 * Module HTML is {@see Lessons::renderSingle()}. Previous and Next match the card
 * view, without the All button. Choosing a card also moves between modules.
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
            if ( ! self::contentModuleVisible($module) ) {
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

    protected static function echoModulePager($lessons, $showAll = true) {
        parent::echoModulePager($lessons, false);
    }

    /**
     * A module the Content sidebar shows.
     *
     * Hidden modules stay off the rail. A login-only module stays off until
     * the reader is signed in.
     *
     * @param mixed $module
     */
    private static function contentModuleVisible($module) {
        if ( ! is_object($module) ) {
            return false;
        }
        if ( isset($module->hidden) && $module->hidden ) {
            return false;
        }
        if ( isset($module->login) && $module->login && ! ReqScope::isLoggedIn() ) {
            return false;
        }
        return true;
    }

    /**
     * Previous and Next stay on the sidebar list, skipping hidden modules
     * and login-only modules when the reader is signed out.
     *
     * @param \Tsugi\Services\Lessons\LessonsService $lessons
     * @return array{0:?string,1:?string}
     */
    protected static function pagerStepAnchors($lessons) {
        $modules = (isset($lessons->lessons->modules) && is_array($lessons->lessons->modules))
            ? $lessons->lessons->modules : array();
        $visible = array();
        foreach ( $modules as $module ) {
            if ( ! self::contentModuleVisible($module) ) {
                continue;
            }
            if ( ! isset($module->anchor) || (string) $module->anchor === '' ) {
                continue;
            }
            $visible[] = (string) $module->anchor;
        }
        $current = isset($lessons->anchor) ? (string) $lessons->anchor : '';
        $index = array_search($current, $visible, true);
        if ( $index === false ) {
            return array(null, null);
        }
        $prev = $index > 0 ? $visible[$index - 1] : null;
        $next = ($index < count($visible) - 1) ? $visible[$index + 1] : null;
        return array($prev, $next);
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
        $base = rtrim((string) $lessons->toolHome, '/');
        $current = isset($lessons->anchor) ? (string) $lessons->anchor : '';

        self::echoStyles();
        echo('<div class="tsugi-outline">'."\n");
        echo('<nav class="tsugi-outline-nav" aria-label="'.htmlspecialchars(__('Modules'), ENT_QUOTES, 'UTF-8').'">'."\n");
        $count = 0;
        foreach ( $lessons->lessons->modules as $module ) {
            if ( ! self::contentModuleVisible($module) ) {
                continue;
            }
            $count++;
            $anchor = isset($module->anchor) ? (string) $module->anchor : '';
            $href = $base.'/'.urlencode($anchor);
            $selected = $anchor !== '' && $anchor === $current;
            self::echoModuleCard($lessons, $module, $count, $allgrades, $duedates, $href, $selected);
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
    bindCardHits(nav);
    bindDescriptions(nav);
    var pending = 0;
    var finish = null;
    nav.addEventListener("click", function (e) {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        if (e.target.closest(".tsugi-card-more, .tsugi-card-popup")) return;
        var card = e.target.closest("article.card");
        if (!card || !nav.contains(card)) return;
        var link = card.querySelector("a.tsugi-lessons-module-card-link");
        if (!link) return;
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

    var openPop = null;
    function hidePop() {
        if (!openPop) return;
        openPop.hidden = true;
        if (openPop._more) openPop._more.setAttribute("aria-expanded", "false");
        openPop = null;
    }
    function showPop(pop, more) {
        if (openPop && openPop !== pop) hidePop();
        pop.hidden = false;
        var rect = more.getBoundingClientRect();
        var left = Math.max(8, rect.left - 8);
        var top = rect.bottom + 4;
        pop.style.left = left + "px";
        pop.style.top = top + "px";
        var box = pop.getBoundingClientRect();
        if (box.right > window.innerWidth - 8) {
            pop.style.left = Math.max(8, window.innerWidth - box.width - 8) + "px";
        }
        if (box.bottom > window.innerHeight - 8) {
            pop.style.top = Math.max(8, rect.top - box.height - 4) + "px";
        }
        more.setAttribute("aria-expanded", "true");
        openPop = pop;
    }
    function bindCardHits(navEl) {
        navEl.querySelectorAll("article.card").forEach(function (card) {
            if (card.querySelector(".tsugi-card-hit")) return;
            var link = card.querySelector("a.tsugi-lessons-module-card-link");
            if (!link) return;
            var hit = document.createElement("a");
            hit.className = "tsugi-card-hit";
            hit.href = link.getAttribute("href") || "";
            hit.tabIndex = -1;
            hit.setAttribute("aria-hidden", "true");
            card.appendChild(hit);
        });
    }
    function bindDescriptions(navEl) {
        var label = '.json_encode(__('Full description')).';
        var popupId = 0;
        navEl.querySelectorAll(".tsugi-card-description").forEach(function (desc) {
            if (desc.scrollHeight <= desc.clientHeight + 1) return;
            var full = desc.textContent.replace(/\s+/g, " ").trim();
            if (!full) return;
            var card = desc.closest("article.card");
            if (!card) return;
            var id = "tsugi-card-popup-" + popupId++;
            var more = document.createElement("button");
            more.type = "button";
            more.className = "tsugi-card-more";
            more.textContent = "…";
            more.setAttribute("aria-label", label);
            more.setAttribute("aria-expanded", "false");
            more.setAttribute("aria-describedby", id);
            var pop = document.createElement("div");
            pop.id = id;
            pop.className = "tsugi-card-popup";
            pop.setAttribute("role", "tooltip");
            pop.hidden = true;
            pop.textContent = full;
            pop._more = more;
            document.body.appendChild(pop);
            card.appendChild(more);
            more.addEventListener("click", function (e) {
                e.preventDefault();
                e.stopPropagation();
                showPop(pop, more);
            });
            more.addEventListener("mouseenter", function () { showPop(pop, more); });
            more.addEventListener("focus", function () { showPop(pop, more); });
            more.addEventListener("blur", function () {
                window.setTimeout(function () {
                    if (openPop !== pop) return;
                    if (document.activeElement === more || pop.contains(document.activeElement)) return;
                    hidePop();
                }, 0);
            });
            more.addEventListener("mouseleave", function () {
                window.setTimeout(function () {
                    if (openPop === pop && !pop.matches(":hover") && document.activeElement !== more) hidePop();
                }, 180);
            });
            pop.addEventListener("mouseleave", function () {
                if (document.activeElement !== more) hidePop();
            });
        });
        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape") hidePop();
        });
        navEl.addEventListener("scroll", hidePop);
        document.addEventListener("click", function (e) {
            if (!openPop) return;
            if (e.target.closest(".tsugi-card-more, .tsugi-card-popup")) return;
            hidePop();
        });
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
    padding-left: 1rem;
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
    position: relative;
    width: auto;
    height: auto;
    max-height: none;
    margin: 0 0 0.5rem 0;
    border: 2px solid #444;
    border-radius: 0.4rem;
    cursor: pointer;
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
    position: relative;
    height: auto;
    max-height: none;
    overflow: visible;
}
.tsugi-outline-nav .tsugi-lessons-module-card-link {
    display: block;
    overflow: visible;
}
.tsugi-outline-nav .tsugi-lessons-module-card-link br {
    display: none;
}
.tsugi-outline-nav .tsugi-card-description {
    clear: both;
    margin: 0;
    line-height: 1.35;
    max-height: 2.7em;
    overflow: hidden;
    position: relative;
}
.tsugi-outline-nav .tsugi-card-hit {
    position: absolute;
    inset: 0;
    z-index: 1;
    border-radius: inherit;
}
.tsugi-outline-nav .tsugi-card-more {
    position: absolute;
    right: 0.5em;
    bottom: 0.45em;
    z-index: 2;
    margin: 0;
    padding: 0 0.1em 0 1.15em;
    border: 0;
    background: linear-gradient(90deg, rgba(255,255,255,0), #fff 0.7em);
    color: inherit;
    font: inherit;
    line-height: inherit;
    cursor: pointer;
}
.tsugi-outline-nav .card.tsugi-outline-selected .tsugi-card-more {
    background: linear-gradient(90deg, rgba(244,248,251,0), #f4f8fb 0.7em);
}
.tsugi-card-popup {
    position: fixed;
    z-index: 40;
    max-width: 20rem;
    max-height: 14rem;
    overflow: auto;
    margin: 0;
    padding: 0.5rem 0.7rem;
    background: #fff;
    color: #222;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-shadow: 0 4px 14px rgba(0,0,0,.16);
    font-size: 0.9rem;
    line-height: 1.4;
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
