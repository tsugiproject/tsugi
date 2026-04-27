import { LitElement, html, css } from 'https://cdn.jsdelivr.net/gh/lit/dist@3/core/lit-core.min.js';

/**
 * Tsugi calendar due-summary chip (reads /calendar/json).
 *
 * Usage:
 *   <tsugi-calendar-due
 *       api-url="/app/calendar/json"
 *       calendar-url="/app/calendar">
 *   </tsugi-calendar-due>
 *
 * When due_soon + late > 0: shows a calendar icon with a badge (sum) and a popup
 * with counts plus a link to calendar. When both are zero: calendar icon is a plain
 * link to calendar (no badge, no popup).
 *
 * Popup lines: "Due:" is the due_soon count; "Late:" is the late count. Badge shows due_soon + late.
 */
class TsugiCalendarDue extends LitElement {
    static properties = {
        apiUrl: { type: String, attribute: 'api-url' },
        calendarUrl: { type: String, attribute: 'calendar-url' },
        lessonsUrl: { type: String, attribute: 'lessons-url' },
        dueSoon: { type: Number, state: true },
        late: { type: Number, state: true },
        popupOpen: { type: Boolean, state: true },
    };

    static styles = css`
        :host {
            display: inline-block;
            position: relative;
            padding: 0 0.05em;
            vertical-align: middle;
        }

        .calendar-icon-container {
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
        }

        a.calendar-link {
            display: inline-block;
            color: inherit;
            text-decoration: none;
        }

        a.calendar-link:hover,
        a.calendar-link:focus {
            color: inherit;
            opacity: 0.9;
        }

        .calendar-icon {
            width: 100%;
            height: 100%;
            fill: none !important;
            stroke: currentColor;
            stroke-width: 1.5;
        }

        .badge {
            position: absolute;
            top: -0.2em;
            right: -0.4em;
            background-color: #d9534f;
            color: white;
            border-radius: 0.35em;
            padding: 0.12em 0.35em;
            font-size: 0.6em;
            font-weight: bold;
            line-height: 1.2;
            min-width: 0.9em;
            text-align: center;
            box-shadow: 0 0.05em 0.15em rgba(0, 0, 0, 0.3);
        }

        .popup {
            position: absolute;
            top: 100%;
            margin-top: 8px;
            background: white;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            min-width: 220px;
            max-width: 320px;
            z-index: 1000;
            color: #333;
            text-align: left;
        }

        .popup.align-right {
            right: 0;
        }

        .popup.align-left {
            left: 0;
        }

        .popup-header {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            background-color: #f5f5f5;
            border-radius: 4px 4px 0 0;
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

        .popup-close:hover {
            color: #333;
        }

        .popup-body {
            padding: 12px 15px;
            font-size: 14px;
            line-height: 1.5;
        }

        .popup-counts {
            margin: 0 0 12px 0;
        }

        .popup-counts div {
            margin-bottom: 4px;
        }

        .popup-lessons {
            display: inline-block;
            margin-top: 4px;
        }
    `;

    constructor() {
        super();
        this.apiUrl = '';
        this.calendarUrl = '/calendar';
        this.lessonsUrl = '/lessons';
        this.dueSoon = 0;
        this.late = 0;
        this.popupOpen = false;
    }

    connectedCallback() {
        super.connectedCallback();
        this._onDocClick = this.handleOutsideClick.bind(this);
        document.addEventListener('click', this._onDocClick);
        this.loadData();
        this._refreshInterval = setInterval(() => this.loadData(), 30000);
    }

    disconnectedCallback() {
        super.disconnectedCallback();
        document.removeEventListener('click', this._onDocClick);
        if (this._refreshInterval) {
            clearInterval(this._refreshInterval);
        }
    }

    async loadData() {
        if (!this.apiUrl) {
            return;
        }
        try {
            const response = await fetch(this.apiUrl, { credentials: 'same-origin' });
            if (response.status === 401) {
                this.dueSoon = 0;
                this.late = 0;
                return;
            }
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            const data = await response.json();
            if (data.status === 'success') {
                this.dueSoon = typeof data.due_soon === 'number' ? data.due_soon : 0;
                this.late = typeof data.late === 'number' ? data.late : 0;
            }
        } catch (err) {
            console.error('[tsugi-calendar-due] Failed to load:', err);
        }
    }

    get totalAlert() {
        return this.dueSoon + this.late;
    }

    togglePopup(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        this.popupOpen = !this.popupOpen;
        if (this.popupOpen) {
            this.loadData();
            this.updateComplete.then(() => this.positionPopup());
        }
    }

    positionPopup() {
        const popup = this.shadowRoot?.querySelector('.popup');
        if (!popup) {
            return;
        }
        const rect = this.getBoundingClientRect();
        const popupWidth = 260;
        const margin = 15;
        const spaceOnRight = window.innerWidth - rect.right;
        popup.classList.remove('align-right', 'align-left');
        if (spaceOnRight < popupWidth + margin) {
            popup.classList.add('align-left');
        } else {
            popup.classList.add('align-right');
        }
    }

    handleOutsideClick(e) {
        if (!this.popupOpen) {
            return;
        }
        const path = e.composedPath();
        if (path.includes(this)) {
            return;
        }
        this.popupOpen = false;
    }

    render() {
        const calendar = this.calendarUrl || this.lessonsUrl || '/calendar';
        const icon = html`
            <svg
                class="calendar-icon"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
                viewBox="0 0 24 24"
                aria-hidden="true"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5a2.25 2.25 0 0 0 2.25-2.25m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5a2.25 2.25 0 0 1 2.25 2.25v7.5"
                />
            </svg>
        `;

        if (this.totalAlert < 1) {
            return html`
                <a class="calendar-link calendar-icon-container" href="${calendar}" title="Calendar" aria-label="Calendar">
                    ${icon}
                </a>
            `;
        }

        const total = this.totalAlert;
        return html`
            <span
                class="calendar-icon-container"
                role="button"
                tabindex="0"
                title="Calendar due summary"
                aria-label="Calendar due summary, ${total} total"
                @click=${this.togglePopup}
                @keydown=${(e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.togglePopup(e);
                    }
                }}
            >
                ${icon}
                <span class="badge">${total}</span>
            </span>
            ${this.popupOpen
                ? html`
                      <div class="popup" role="dialog" aria-label="Calendar due summary">
                          <div class="popup-header">
                              <strong>Calendar</strong>
                              <span class="popup-close" @click=${() => (this.popupOpen = false)}>×</span>
                          </div>
                          <div class="popup-body">
                              <div class="popup-counts">
                                  <div><strong>Due:</strong> ${this.dueSoon}</div>
                                  <div><strong>Late:</strong> ${this.late}</div>
                              </div>
                              <a class="popup-lessons" href="${calendar}">Calendar</a>
                          </div>
                      </div>
                  `
                : ''}
        `;
    }
}

customElements.define('tsugi-calendar-due', TsugiCalendarDue);
