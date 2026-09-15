import { LitElement, html, css } from 'https://cdn.jsdelivr.net/gh/lit/dist@3/core/lit-core.min.js';

/**
 * Sites switcher (Sakai waffle). Loads memberships after the flyout opens.
 *
 *   <tsugi-courses
 *       api-url="/tsugi/courses/json"
 *       all-url="/tsugi/courses"
 *       enter-url="/tsugi/courses">
 *   </tsugi-courses>
 */
class TsugiCourses extends LitElement {
    static properties = {
        apiUrl: { type: String, attribute: 'api-url' },
        allUrl: { type: String, attribute: 'all-url' },
        enterUrl: { type: String, attribute: 'enter-url' },
        courses: { type: Array, state: true },
        currentContextId: { type: Number, state: true },
        popupOpen: { type: Boolean, state: true },
        loading: { type: Boolean, state: true },
        error: { type: String, state: true },
    };

    static styles = css`
        :host {
            display: inline-block;
            position: relative;
            padding: 0 0.05em;
            vertical-align: middle;
        }
        .icon-wrap {
            font-size: 1em;
            cursor: pointer;
            user-select: none;
            position: relative;
            display: inline-block;
            width: 2em;
            height: 2em;
            line-height: 1;
            vertical-align: middle;
            padding: 0 0.15em;
            color: inherit;
        }
        .icon {
            width: 100%;
            height: 100%;
            fill: currentColor;
        }
        .popup {
            position: absolute;
            top: 100%;
            margin-top: 8px;
            background: white;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            min-width: 260px;
            max-width: 360px;
            z-index: 1000;
            max-height: 420px;
            overflow-y: auto;
            color: #222;
            text-align: left;
        }
        .popup.align-right { right: 0; }
        .popup.align-left { left: 0; }
        .popup-header {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            background-color: #f5f5f5;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .popup-close {
            font-size: 20px;
            line-height: 1;
            color: #999;
            cursor: pointer;
        }
        .popup-close:hover { color: #333; }
        .status, .empty {
            padding: 16px 15px;
            color: #666;
            font-size: 14px;
        }
        .course-list { list-style: none; margin: 0; padding: 0; }
        .course-list a {
            display: block;
            padding: 10px 15px;
            color: #222;
            text-decoration: none;
            border-bottom: 1px solid #f0f0f0;
        }
        .course-list a:hover { background: #f7f7f7; }
        .course-list a.current {
            font-weight: 600;
            background: #eef6fb;
        }
        .popup-footer {
            padding: 10px 15px;
            border-top: 1px solid #eee;
            background: #fafafa;
        }
        .popup-footer a { color: #337ab7; text-decoration: none; }
        .popup-footer a:hover { text-decoration: underline; }
    `;

    constructor() {
        super();
        this.apiUrl = '';
        this.allUrl = '/courses';
        this.enterUrl = '/courses';
        this.courses = null;
        this.currentContextId = 0;
        this.popupOpen = false;
        this.loading = false;
        this.error = '';
        this._outside = (e) => this.handleOutsideClick(e);
    }

    connectedCallback() {
        super.connectedCallback();
        document.addEventListener('click', this._outside);
    }

    disconnectedCallback() {
        super.disconnectedCallback();
        document.removeEventListener('click', this._outside);
    }

    togglePopup(e) {
        if (e) e.stopPropagation();
        this.popupOpen = !this.popupOpen;
        if (this.popupOpen) {
            this.loadData();
            this.updateComplete.then(() => this.positionPopup());
        }
    }

    positionPopup() {
        const popup = this.shadowRoot && this.shadowRoot.querySelector('.popup');
        if (!popup) return;
        const rect = this.getBoundingClientRect();
        const popupWidth = 320;
        const spaceOnRight = window.innerWidth - rect.right;
        popup.classList.remove('align-right', 'align-left');
        if (spaceOnRight < popupWidth + 15) {
            popup.classList.add('align-left');
        } else {
            popup.classList.add('align-right');
        }
    }

    handleOutsideClick(e) {
        if (this.popupOpen && !this.contains(e.target)) {
            this.popupOpen = false;
        }
    }

    async loadData() {
        if (!this.apiUrl) {
            this.error = 'Missing API URL';
            return;
        }
        this.loading = true;
        this.error = '';
        try {
            const response = await fetch(this.apiUrl, { credentials: 'same-origin' });
            if (!response.ok) {
                this.error = response.status === 403 ? 'Not available for this session.' : 'Could not load sites.';
                this.courses = [];
                return;
            }
            const data = await response.json();
            let rows = [];
            if (Array.isArray(data)) {
                rows = data;
            } else if (data && Array.isArray(data.courses)) {
                rows = data.courses;
                this.currentContextId = Number(data.current_context_id || 0);
            } else if (data && data.error) {
                this.error = String(data.error);
                this.courses = [];
                return;
            }
            this.courses = rows.map((row) => ({
                id: Number(row.context_id || row.id || 0),
                title: row.title || ('Site ' + (row.context_id || '')),
            })).filter((row) => row.id > 0);
        } catch (err) {
            console.error('[tsugi-courses] Failed to load', err);
            this.error = 'Could not load sites.';
            this.courses = [];
        } finally {
            this.loading = false;
        }
    }

    courseHref(id) {
        const base = (this.enterUrl || '/courses').replace(/\/+$/, '');
        return base + '/' + id + '/home';
    }

    render() {
        return html`
            <span class="icon-wrap" @click=${this.togglePopup} title="Sites" role="button" aria-haspopup="true" aria-expanded=${this.popupOpen ? 'true' : 'false'}>
                <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
                    <rect x="3" y="3" width="5" height="5" rx="1"/>
                    <rect x="9.5" y="3" width="5" height="5" rx="1"/>
                    <rect x="16" y="3" width="5" height="5" rx="1"/>
                    <rect x="3" y="9.5" width="5" height="5" rx="1"/>
                    <rect x="9.5" y="9.5" width="5" height="5" rx="1"/>
                    <rect x="16" y="9.5" width="5" height="5" rx="1"/>
                    <rect x="3" y="16" width="5" height="5" rx="1"/>
                    <rect x="9.5" y="16" width="5" height="5" rx="1"/>
                    <rect x="16" y="16" width="5" height="5" rx="1"/>
                </svg>
            </span>
            ${this.popupOpen ? html`
                <div class="popup">
                    <div class="popup-header">
                        <strong>Sites</strong>
                        <span class="popup-close" @click=${() => { this.popupOpen = false; }} aria-label="Close">&times;</span>
                    </div>
                    ${this.loading ? html`<div class="status">Loading…</div>` : ''}
                    ${!this.loading && this.error ? html`<div class="status">${this.error}</div>` : ''}
                    ${!this.loading && !this.error && this.courses && this.courses.length === 0 ? html`<div class="empty">No sites yet.</div>` : ''}
                    ${!this.loading && this.courses && this.courses.length > 0 ? html`
                        <ul class="course-list">
                            ${this.courses.map((c) => html`
                                <li>
                                    <a href=${this.courseHref(c.id)} class=${c.id === this.currentContextId ? 'current' : ''}>${c.title}</a>
                                </li>
                            `)}
                        </ul>
                    ` : ''}
                    ${this.allUrl ? html`
                        <div class="popup-footer">
                            <a href=${this.allUrl}>View all my sites</a>
                        </div>
                    ` : ''}
                </div>
            ` : ''}
        `;
    }
}

customElements.define('tsugi-courses', TsugiCourses);
