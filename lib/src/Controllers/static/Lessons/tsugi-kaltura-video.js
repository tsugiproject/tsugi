import { LitElement, html, css } from 'https://cdn.jsdelivr.net/gh/lit/dist@3/core/lit-core.min.js';

/**
 * Tsugi Kaltura lesson video (trigger + modal overlay).
 *
 * Usage:
 *   <tsugi-kaltura-video
 *       title="DJ 01.01 …"
 *       embed-url="https://cdnapisec.kaltura.com/...&entry_id=1_xxx"
 *       tab-url="https://umsiali.mivideo.it.umich.edu/playlist/dedicated/.../1_xxx"
 *       show-icon>
 *   </tsugi-kaltura-video>
 *
 * Attributes:
 *   - title: Video title (trigger label + dialog aria + iframe title)
 *   - embed-url: Playkit iframe URL (lazy-loaded into the modal)
 *   - tab-url: Open-in-new-window URL (playlist context); falls back to embed-url
 *   - show-icon: When present, show a video type icon before the title
 *   - open-label: Optional override for "Open in a new window"
 */
class TsugiKalturaVideo extends LitElement {
    static properties = {
        title: { type: String },
        embedUrl: { type: String, attribute: 'embed-url' },
        tabUrl: { type: String, attribute: 'tab-url' },
        showIcon: { type: Boolean, attribute: 'show-icon', reflect: true },
        openLabel: { type: String, attribute: 'open-label' },
        open: { type: Boolean, state: true },
    };

    static styles = css`
        :host {
            display: inline;
        }

        .play-btn {
            background: none;
            border: none;
            padding: 0;
            margin: 0;
            font: inherit;
            color: inherit;
            cursor: pointer;
            text-align: left;
            display: inline-flex;
            align-items: center;
        }

        .type-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 3px;
            font-size: 14px;
            background-color: #28a745;
            margin-right: 8px;
            vertical-align: middle;
            flex-shrink: 0;
            color: #fff;
        }

        .type-icon svg {
            width: 14px;
            height: 14px;
            fill: currentColor;
        }

        .overlay {
            height: 100%;
            width: 100%;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            background-color: rgba(0, 0, 0, 0.4);
            overflow-x: hidden;
            text-align: center;
            display: none;
        }

        .overlay[data-open] {
            display: block;
        }

        .overlay-content {
            position: relative;
            background-color: #fff;
            top: 40px;
            margin: auto;
            z-index: 2001;
            width: min(90%, calc((100vh - 7rem) * 16 / 9));
            max-width: 90%;
            text-align: left;
        }

        .titlebar {
            display: flex;
            align-items: center;
            gap: 0.75em;
            padding: 0.5em 2.75em 0.5em 0.75em;
            border-bottom: 1px solid #ddd;
            background: #f5f5f5;
            position: relative;
        }

        .title-link {
            flex: 1 1 auto;
            font-size: 18px;
            text-align: center;
            padding: 0.2em;
            color: inherit;
            text-decoration: none;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .title-link:hover,
        .title-link:focus {
            text-decoration: underline;
        }

        .open-new {
            flex: 0 0 auto;
            font-size: 0.85em;
            white-space: nowrap;
        }

        .close {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            z-index: 10;
            background: rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.2);
            color: #333;
            font-size: 1.5rem;
            line-height: 1;
            width: 2rem;
            height: 2rem;
            padding: 0;
            cursor: pointer;
            border-radius: 4px;
        }

        .close:hover {
            background: rgba(0, 0, 0, 0.15);
        }

        .player {
            display: block;
            width: 100%;
            height: auto;
            aspect-ratio: 16 / 9;
            max-height: calc(100vh - 7rem);
            border: 0;
            background: #000;
        }
    `;

    constructor() {
        super();
        this.title = '';
        this.embedUrl = '';
        this.tabUrl = '';
        this.showIcon = false;
        this.openLabel = 'Open in a new window';
        this.open = false;
        this._onKeyDown = this._onKeyDown.bind(this);
    }

    connectedCallback() {
        super.connectedCallback();
        document.addEventListener('keydown', this._onKeyDown);
    }

    disconnectedCallback() {
        super.disconnectedCallback();
        document.removeEventListener('keydown', this._onKeyDown);
        this._stopPlayer();
    }

    get resolvedTabUrl() {
        return this.tabUrl || this.embedUrl;
    }

    _onKeyDown(event) {
        if (this.open && event.key === 'Escape') {
            this.closeOverlay();
        }
    }

    openOverlay() {
        this.open = true;
        // Lazy-load iframe after paint so data-src → src happens with open state.
        this.updateComplete.then(() => {
            const iframe = this.renderRoot.querySelector('iframe.player');
            if (iframe && this.embedUrl) {
                iframe.src = this.embedUrl;
            }
        });
    }

    closeOverlay() {
        this._stopPlayer();
        this.open = false;
    }

    _stopPlayer() {
        const iframe = this.renderRoot?.querySelector?.('iframe.player');
        if (iframe) {
            iframe.src = '';
        }
    }

    _onOverlayClick(event) {
        if (event.target === event.currentTarget) {
            this.closeOverlay();
        }
    }

    _videoIcon() {
        return html`
            <span class="type-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M17 10.5V7a2 2 0 0 0-2-2H5A2 2 0 0 0 3 7v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-3.5l4 4v-11l-4 4z"/>
                </svg>
            </span>
        `;
    }

    render() {
        const title = this.title || 'Video';
        const tab = this.resolvedTabUrl;
        return html`
            <div
                class="overlay"
                ?data-open=${this.open}
                role="dialog"
                aria-modal="true"
                aria-label=${'Video: ' + title}
                @click=${this._onOverlayClick}
            >
                <div class="overlay-content">
                    <div class="titlebar">
                        <a
                            class="title-link"
                            href=${tab}
                            target="_blank"
                            rel="noopener noreferrer"
                        >${title}</a>
                        <a
                            class="open-new"
                            href=${tab}
                            target="_blank"
                            rel="noopener noreferrer"
                            title=${this.openLabel}
                        >${this.openLabel}</a>
                        <button
                            type="button"
                            class="close"
                            aria-label="Close"
                            @click=${this.closeOverlay}
                        >×</button>
                    </div>
                    <iframe
                        class="player"
                        title=${title}
                        allowfullscreen
                        allow="autoplay *; fullscreen *; encrypted-media *; picture-in-picture *"
                    ></iframe>
                </div>
            </div>
            <button type="button" class="play-btn" @click=${this.openOverlay}>
                ${this.showIcon ? this._videoIcon() : null}${title}
            </button>
        `;
    }
}

customElements.define('tsugi-kaltura-video', TsugiKalturaVideo);
