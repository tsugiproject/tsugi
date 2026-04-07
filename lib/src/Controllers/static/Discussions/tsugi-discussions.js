import { LitElement, html, css } from 'https://cdn.jsdelivr.net/gh/lit/dist@3/core/lit-core.min.js';

class TsugiDiscussions extends LitElement {
    static properties = {
        apiUrl: { type: String, attribute: 'api-url' },
        discussionsUrl: { type: String, attribute: 'discussions-url' },
        personal: { type: Number, state: true },
        participating: { type: Number, state: true },
        globalCount: { type: Number, state: true },
        mainBadge: { type: Number, state: true },
    };

    static styles = css`
        :host { display: inline-block; position: relative; padding: 0 0.05em; vertical-align: middle; }
        .icon-wrap { font-size: 1em; cursor: pointer; user-select: none; position: relative; display: inline-block; width: 2em; height: 2em; line-height: 1; vertical-align: middle; padding: 0 0.15em; color: inherit; text-decoration: none; }
        .icon { width: 100%; height: 100%; fill: none !important; stroke: currentColor; stroke-width: 1.5; }
        .badge { position: absolute; top: -0.2em; right: -0.4em; border-radius: 0.35em; padding: 0.12em 0.35em; font-size: 0.6em; font-weight: bold; line-height: 1.2; min-width: 0.9em; text-align: center; box-shadow: 0 0.05em 0.15em rgba(0,0,0,0.3); border: 1px solid transparent; }
        .badge-personal { background-color: #d9534f; color: #fff; border-color: #c94440; }
        .badge-participating { background-color: #fbbf24; color: #111; border-color: #f59e0b; }
        .badge-global { background-color: var(--tsugi-lesson-due-ok-bg, #d4edda); color: var(--tsugi-lesson-due-ok-fg, #155724); border-color: var(--tsugi-lesson-due-ok-border, #b8dabc); }
    `;

    constructor() {
        super();
        this.apiUrl = '';
        this.discussionsUrl = '/discussions';
        this.personal = 0;
        this.participating = 0;
        this.globalCount = 0;
        this.mainBadge = 0;
    }

    connectedCallback() {
        super.connectedCallback();
        this.loadData();
        this._refreshInterval = setInterval(() => this.loadData(), 30000);
    }

    disconnectedCallback() {
        super.disconnectedCallback();
        if (this._refreshInterval) clearInterval(this._refreshInterval);
    }

    async loadData() {
        if (!this.apiUrl) return;
        try {
            const response = await fetch(this.apiUrl, { credentials: 'same-origin' });
            if (!response.ok) return;
            const data = await response.json();
            if (data.status !== 'success' || !data.totals) return;
            this.personal = Number(data.totals.personal || 0);
            this.participating = Number(data.totals.participating || 0);
            this.globalCount = Number(data.totals.global || 0);
            this.mainBadge = Number(data.totals.main_badge || this.personal || 0);
        } catch (e) {
            console.error('[tsugi-discussions] Failed to load', e);
        }
    }

    get badgeTier() {
        if (this.personal > 0) return 'personal';
        if (this.participating > 0) return 'participating';
        if (this.globalCount > 0) return 'global';
        return null;
    }

    get badgeCount() {
        if (this.personal > 0) return this.personal;
        if (this.participating > 0) return this.participating;
        if (this.globalCount > 0) return this.globalCount;
        return 0;
    }

    render() {
        const icon = html`
            <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75h6.75m-6.75 3h4.5M7.5 19.5h9A2.25 2.25 0 0 0 18.75 17.25v-10.5A2.25 2.25 0 0 0 16.5 4.5h-9A2.25 2.25 0 0 0 5.25 6.75v10.5A2.25 2.25 0 0 0 7.5 19.5Z"/>
            </svg>
        `;
        const tier = this.badgeTier;
        const count = this.badgeCount;
        const badgeClass = tier ? `badge badge-${tier}` : '';
        const ariaTier = tier ? `${tier} unread` : 'no unread discussions';
        return html`
            <a class="icon-wrap" href="${this.discussionsUrl}" title="Discussions" aria-label="Discussions ${count > 0 ? `${ariaTier}: ${count}` : ''}">
                ${icon}
                ${count > 0 ? html`<span class="${badgeClass}" title="${ariaTier}: ${count}" aria-label="${ariaTier}: ${count}">${count}</span>` : ''}
            </a>
        `;
    }
}

customElements.define('tsugi-discussions', TsugiDiscussions);
