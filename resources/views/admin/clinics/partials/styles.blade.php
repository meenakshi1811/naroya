<style>
    .cm {
        --cm-primary: #109014;
        --cm-primary-hover: #0d7811;
        --cm-primary-ink: #0f7f13;
        --cm-primary-50: #f1f9f1;
        --cm-primary-100: #e3f3e4;
        --cm-primary-200: #cfe8d1;
        --cm-ink: #111827;
        --cm-body: #374151;
        --cm-muted: #6b7280;
        --cm-subtle: #9ca3af;
        --cm-border: #e6eae7;
        --cm-border-strong: #d4dbd5;
        --cm-surface-alt: #f8faf9;
        --cm-radius: 14px;
        --cm-shadow: 0 1px 2px rgba(16, 24, 40, 0.04), 0 4px 18px rgba(16, 24, 40, 0.04);
        color: var(--cm-body);
        padding-bottom: 1rem;
    }

    .cm [hidden] {
        display: none !important;
    }

    /* ---------- Page header ---------- */
    .cm-page-header {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .cm-breadcrumb {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.82rem;
        color: var(--cm-subtle);
        margin-bottom: 0.4rem;
    }

    .cm-breadcrumb a {
        color: var(--cm-muted);
        text-decoration: none;
    }

    .cm-breadcrumb a:hover {
        color: var(--cm-primary-ink);
    }

    .cm-breadcrumb .bi {
        font-size: 0.65rem;
    }

    .cm-title {
        font-size: 1.6rem;
        font-weight: 700;
        color: var(--cm-ink);
        letter-spacing: -0.01em;
        margin: 0;
    }

    .cm-subtitle {
        color: var(--cm-muted);
        margin: 0.3rem 0 0;
        font-size: 0.95rem;
    }

    .cm-header-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    /* ---------- Buttons (theme only recolours .btn-primary) ---------- */
    .cm .btn {
        border-radius: 10px;
        font-weight: 600;
    }

    .cm .btn-sm {
        border-radius: 8px;
    }

    .cm .btn-outline-primary {
        --bs-btn-color: var(--cm-primary-ink);
        --bs-btn-border-color: var(--cm-primary);
        --bs-btn-hover-color: #fff;
        --bs-btn-hover-bg: var(--cm-primary);
        --bs-btn-hover-border-color: var(--cm-primary);
        --bs-btn-active-color: #fff;
        --bs-btn-active-bg: var(--cm-primary-hover);
        --bs-btn-active-border-color: var(--cm-primary-hover);
        --bs-btn-focus-shadow-rgb: 16, 144, 20;
    }

    .cm .btn-light {
        --bs-btn-bg: #fff;
        --bs-btn-border-color: var(--cm-border-strong);
        --bs-btn-hover-bg: var(--cm-surface-alt);
        --bs-btn-hover-border-color: var(--cm-border-strong);
        color: var(--cm-body);
    }

    .cm-icon-btn {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 9px;
        border: 1px solid var(--cm-border);
        background: #fff;
        color: var(--cm-muted);
        text-decoration: none;
        transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
    }

    .cm-icon-btn:hover {
        background: var(--cm-primary-50);
        border-color: var(--cm-primary-200);
        color: var(--cm-primary-ink);
    }

    .cm-icon-btn.is-danger:hover {
        background: #fef2f2;
        border-color: #fecaca;
        color: #dc2626;
    }

    .cm-icon-btn:disabled,
    .cm-icon-btn.disabled {
        opacity: 0.45;
        pointer-events: none;
    }

    .cm-row-actions {
        display: inline-flex;
        gap: 0.35rem;
    }

    /* ---------- Alerts ---------- */
    .cm-alert {
        display: flex;
        gap: 0.75rem;
        align-items: flex-start;
        border-radius: 12px;
        padding: 0.85rem 1rem;
        margin-bottom: 1.25rem;
        border: 1px solid;
        font-size: 0.92rem;
    }

    .cm-alert .bi {
        font-size: 1.1rem;
        line-height: 1.3;
    }

    .cm-alert-success {
        background: var(--cm-primary-50);
        border-color: var(--cm-primary-200);
        color: #14532d;
    }

    .cm-alert-error {
        background: #fef2f2;
        border-color: #fecaca;
        color: #991b1b;
    }

    .cm-alert ul {
        margin: 0.35rem 0 0;
        padding-left: 1.1rem;
    }

    .cm-alert .btn-close {
        margin-left: auto;
        font-size: 0.7rem;
        padding: 0.4rem;
    }

    /* ---------- Cards ---------- */
    .cm-card {
        background: #fff;
        border: 1px solid var(--cm-border);
        border-radius: var(--cm-radius);
        box-shadow: var(--cm-shadow);
    }

    .cm-card-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem 1rem;
        padding: 1.1rem 1.25rem;
        border-bottom: 1px solid var(--cm-border);
    }

    .cm-card-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--cm-ink);
        margin: 0;
    }

    .cm-card-subtitle {
        font-size: 0.85rem;
        color: var(--cm-muted);
        margin: 0.15rem 0 0;
    }

    .cm-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.85rem 1.25rem;
        border-bottom: 1px solid var(--cm-border);
        background: var(--cm-surface-alt);
    }

    /* ---------- Stat tiles ---------- */
    .cm-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    @media (max-width: 1199px) {
        .cm-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 575px) {
        .cm-stats {
            grid-template-columns: 1fr;
        }
    }

    .cm-stat {
        display: flex;
        align-items: center;
        gap: 0.9rem;
        background: #fff;
        border: 1px solid var(--cm-border);
        border-radius: 12px;
        padding: 1rem 1.15rem;
        box-shadow: var(--cm-shadow);
    }

    .cm-stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .cm-tone-green { background: var(--cm-primary-100); color: var(--cm-primary-ink); }
    .cm-tone-teal { background: #e0f4f1; color: #0f766e; }
    .cm-tone-blue { background: #e8effd; color: #1d4ed8; }
    .cm-tone-amber { background: #fdf3e2; color: #b45309; }

    .cm-stat-label {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--cm-muted);
    }

    .cm-stat-value {
        font-size: 1.55rem;
        font-weight: 700;
        color: var(--cm-ink);
        line-height: 1.15;
    }

    /* ---------- Search / filters ---------- */
    .cm-search {
        position: relative;
        min-width: 260px;
        flex: 1 1 260px;
        max-width: 380px;
    }

    .cm-search .bi {
        position: absolute;
        left: 0.8rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--cm-subtle);
        pointer-events: none;
    }

    .cm .cm-search .form-control {
        padding-left: 2.2rem;
    }

    .cm-filter-group {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
    }

    /* ---------- Segmented tabs ---------- */
    .cm-tabs {
        display: inline-flex;
        gap: 0.2rem;
        background: #fff;
        border: 1px solid var(--cm-border);
        border-radius: 10px;
        padding: 0.2rem;
    }

    .cm-tab {
        border: 0;
        background: transparent;
        padding: 0.42rem 0.8rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--cm-muted);
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        cursor: pointer;
    }

    .cm-tab:hover {
        color: var(--cm-ink);
    }

    a.cm-tab {
        text-decoration: none;
    }

    .cm-tabs-wrap {
        flex-wrap: wrap;
        max-width: 100%;
    }

    .cm-tab-count {
        background: #eef0ef;
        color: var(--cm-muted);
        border-radius: 999px;
        padding: 0.05rem 0.45rem;
        font-size: 0.72rem;
    }

    .cm-tab.is-active {
        background: var(--cm-primary-50);
        color: var(--cm-primary-ink);
    }

    .cm-tab.is-active .cm-tab-count {
        background: var(--cm-primary-100);
        color: var(--cm-primary-ink);
    }

    /* ---------- Tables ---------- */
    .cm-table {
        margin: 0;
        width: 100%;
    }

    .cm-table thead th {
        background: #fff !important;
        color: var(--cm-subtle) !important;
        border-top: 0 !important;
        border-bottom: 1px solid var(--cm-border) !important;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        padding: 0.8rem 1.25rem;
        white-space: nowrap;
    }

    .cm-table tbody td {
        padding: 0.85rem 1.25rem;
        border-color: #eef1ef;
        vertical-align: middle;
        color: var(--cm-body);
        font-size: 0.92rem;
    }

    .cm-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .cm-table tbody tr:hover td {
        background: #fbfcfb;
    }

    .cm-nowrap {
        white-space: nowrap;
    }

    .cm-muted {
        color: var(--cm-muted);
    }

    .cm-small {
        font-size: 0.82rem;
    }

    /* ---------- Identity cell / avatars ---------- */
    .cm-identity {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-width: 0;
    }

    .cm-identity-name {
        font-weight: 600;
        color: var(--cm-ink);
        text-decoration: none;
        display: block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    a.cm-identity-name:hover {
        color: var(--cm-primary-ink);
    }

    .cm-identity-meta {
        font-size: 0.8rem;
        color: var(--cm-muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .cm-avatar {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.85rem;
        background: var(--cm-primary-100);
        color: var(--cm-primary-ink);
        flex-shrink: 0;
        overflow: hidden;
        letter-spacing: 0.02em;
    }

    .cm-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .cm-avatar.is-round {
        border-radius: 50%;
    }

    .cm-avatar.is-employee {
        background: #e8effd;
        color: #1d4ed8;
    }

    .cm-avatar-sm {
        width: 32px;
        height: 32px;
        font-size: 0.72rem;
    }

    .cm-avatar-xl {
        width: 92px;
        height: 92px;
        border-radius: 22px;
        font-size: 1.9rem;
        border: 1px solid var(--cm-border);
    }

    /* ---------- Badges ---------- */
    .cm-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.24rem 0.6rem;
        border-radius: 999px;
        font-size: 0.74rem;
        font-weight: 600;
        line-height: 1.25;
        white-space: nowrap;
    }

    .cm-badge-dot::before {
        content: '';
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
    }

    .cm-badge-green { background: var(--cm-primary-50); color: var(--cm-primary-ink); }
    .cm-badge-gray { background: #f3f4f6; color: #4b5563; }
    .cm-badge-blue { background: #eef3fe; color: #1d4ed8; }
    .cm-badge-amber { background: #fff6e8; color: #b45309; }

    .cm-count {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-weight: 600;
        color: var(--cm-ink);
    }

    .cm-count .bi {
        color: var(--cm-subtle);
    }

    /* ---------- Empty state ---------- */
    .cm-empty {
        text-align: center;
        padding: 3.25rem 1.5rem;
    }

    .cm-empty-icon {
        width: 64px;
        height: 64px;
        border-radius: 18px;
        background: var(--cm-primary-50);
        color: var(--cm-primary-ink);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        margin-bottom: 1rem;
    }

    .cm-empty h3 {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--cm-ink);
        margin-bottom: 0.35rem;
    }

    .cm-empty p {
        color: var(--cm-muted);
        max-width: 420px;
        margin: 0 auto 1.25rem;
    }

    /* ---------- Forms ---------- */
    .cm .form-label {
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--cm-body);
        margin-bottom: 0.35rem;
    }

    .cm .form-label .cm-required {
        color: #dc2626;
        margin-left: 0.15rem;
    }

    .cm .form-label .cm-optional {
        color: var(--cm-subtle);
        font-weight: 500;
        margin-left: 0.25rem;
    }

    .cm .form-control,
    .cm .form-select {
        border-radius: 10px;
        border-color: var(--cm-border-strong);
        padding: 0.6rem 0.85rem;
        font-size: 0.93rem;
        color: var(--cm-ink);
    }

    .cm .form-select {
        padding-right: 2.25rem;
    }

    .cm .form-control::placeholder {
        color: #a3aab4;
    }

    .cm .form-control:focus,
    .cm .form-select:focus,
    .cm .form-check-input:focus {
        border-color: var(--cm-primary);
        box-shadow: 0 0 0 0.2rem rgba(16, 144, 20, 0.14);
    }

    .cm .form-control.is-invalid,
    .cm .form-select.is-invalid {
        border-color: #dc2626;
    }

    .cm .form-control:disabled,
    .cm .form-select:disabled {
        background-color: var(--cm-surface-alt);
        color: var(--cm-subtle);
    }

    .cm .form-text {
        color: var(--cm-subtle);
        font-size: 0.8rem;
        margin-top: 0.35rem;
    }

    .cm .input-group-text {
        background: var(--cm-surface-alt);
        border-color: var(--cm-border-strong);
        color: var(--cm-muted);
        font-weight: 600;
        font-size: 0.88rem;
    }

    .cm .form-check-input:checked {
        background-color: var(--cm-primary);
        border-color: var(--cm-primary);
    }

    .cm .form-switch .form-check-input {
        width: 2.6em;
        height: 1.4em;
        cursor: pointer;
    }

    .cm .form-switch .form-check-input:focus:not(:checked) {
        --bs-form-switch-bg: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%2386c788'/%3e%3c/svg%3e");
    }

    .cm-form-section {
        display: grid;
        grid-template-columns: minmax(200px, 280px) minmax(0, 1fr);
        gap: 1.25rem 2.5rem;
        padding: 1.6rem 1.5rem;
        border-bottom: 1px solid var(--cm-border);
    }

    @media (max-width: 991px) {
        .cm-form-section {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
    }

    .cm-section-heading {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        font-size: 0.98rem;
        font-weight: 700;
        color: var(--cm-ink);
        margin: 0 0 0.35rem;
    }

    .cm-section-heading .bi {
        width: 30px;
        height: 30px;
        border-radius: 9px;
        background: var(--cm-primary-50);
        color: var(--cm-primary-ink);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
    }

    .cm-section-desc {
        font-size: 0.86rem;
        color: var(--cm-muted);
        margin: 0;
        line-height: 1.5;
    }

    .cm fieldset[disabled] {
        display: none;
    }

    .cm-form-actions {
        position: sticky;
        bottom: 0;
        z-index: 5;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.9rem 1.5rem;
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(6px);
        /* Shadow instead of a border so it merges with the last section's divider. */
        box-shadow: 0 -1px 0 var(--cm-border);
        border-radius: 0 0 var(--cm-radius) var(--cm-radius);
    }

    .cm-form-actions-hint {
        font-size: 0.82rem;
        color: var(--cm-subtle);
    }

    /* ---------- Image uploader ---------- */
    .cm-uploader {
        display: flex;
        align-items: center;
        gap: 1.1rem;
        flex-wrap: wrap;
    }

    .cm-uploader-preview {
        width: 84px;
        height: 84px;
        border-radius: 20px;
        border: 1px dashed var(--cm-border-strong);
        background: var(--cm-surface-alt);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        color: var(--cm-subtle);
        font-size: 1.6rem;
        flex-shrink: 0;
    }

    .cm-uploader-preview.is-round {
        border-radius: 50%;
    }

    .cm-uploader-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .cm-uploader-preview.has-image {
        border-style: solid;
    }

    /* ---------- Role picker ---------- */
    .cm-role-picker {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.85rem;
    }

    @media (max-width: 575px) {
        .cm-role-picker {
            grid-template-columns: 1fr;
        }
    }

    .cm-role-option {
        position: relative;
        margin: 0;
    }

    .cm-role-option input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .cm-role-card {
        display: flex;
        align-items: flex-start;
        gap: 0.85rem;
        height: 100%;
        padding: 1rem 2.5rem 1rem 1rem;
        border: 1.5px solid var(--cm-border-strong);
        border-radius: 12px;
        background: #fff;
        cursor: pointer;
        transition: border-color 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
    }

    .cm-role-card:hover {
        border-color: var(--cm-primary-200);
    }

    .cm-role-icon {
        width: 40px;
        height: 40px;
        border-radius: 11px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
        background: var(--cm-surface-alt);
        color: var(--cm-muted);
    }

    .cm-role-title {
        display: block;
        font-weight: 700;
        color: var(--cm-ink);
        margin-bottom: 0.15rem;
    }

    .cm-role-desc {
        display: block;
        font-size: 0.83rem;
        color: var(--cm-muted);
        line-height: 1.45;
    }

    .cm-role-check {
        position: absolute;
        top: 0.9rem;
        right: 0.9rem;
        color: var(--cm-primary);
        font-size: 1.1rem;
        opacity: 0;
        transition: opacity 0.15s ease;
    }

    .cm-role-option input:checked + .cm-role-card {
        border-color: var(--cm-primary);
        background: var(--cm-primary-50);
        box-shadow: 0 0 0 3px rgba(16, 144, 20, 0.1);
    }

    .cm-role-option input:checked + .cm-role-card .cm-role-icon {
        background: var(--cm-primary);
        color: #fff;
    }

    .cm-role-option input:checked + .cm-role-card .cm-role-check {
        opacity: 1;
    }

    .cm-role-option input:focus-visible + .cm-role-card {
        outline: 2px solid var(--cm-primary);
        outline-offset: 2px;
    }

    /* ---------- Chips (multi-select) ---------- */
    .cm-chip-group {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .cm-chip {
        position: relative;
        margin: 0;
    }

    .cm-chip input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .cm-chip span {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.4rem 0.85rem;
        border: 1px solid var(--cm-border-strong);
        border-radius: 999px;
        background: #fff;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--cm-body);
        cursor: pointer;
        user-select: none;
        transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    }

    .cm-chip span .bi {
        display: none;
    }

    .cm-chip span:hover {
        border-color: var(--cm-primary-200);
    }

    .cm-chip input:checked + span {
        background: var(--cm-primary-50);
        border-color: var(--cm-primary);
        color: var(--cm-primary-ink);
    }

    .cm-chip input:checked + span .bi {
        display: inline-block;
    }

    .cm-chip input:focus-visible + span {
        outline: 2px solid var(--cm-primary);
        outline-offset: 2px;
    }

    /* ---------- Repeater ---------- */
    .cm-repeater {
        display: grid;
        gap: 0.85rem;
    }

    .cm-repeater-item {
        border: 1px solid var(--cm-border);
        border-radius: 12px;
        padding: 1rem;
        background: var(--cm-surface-alt);
    }

    .cm-repeater-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.75rem;
    }

    .cm-repeater-title {
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--cm-muted);
    }

    .cm-repeater-empty {
        border: 1px dashed var(--cm-border-strong);
        border-radius: 12px;
        padding: 1.1rem;
        text-align: center;
        color: var(--cm-muted);
        font-size: 0.88rem;
    }

    /* ---------- Clinic hours sessions ---------- */
    .cm-session-list {
        display: grid;
        gap: 0.6rem;
        counter-reset: cm-session;
    }

    .cm-session {
        display: grid;
        grid-template-columns: 82px minmax(0, 1fr) auto minmax(0, 1fr) auto;
        align-items: center;
        gap: 0.4rem 0.65rem;
        max-width: 560px;
        counter-increment: cm-session;
    }

    .cm-session-label {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--cm-muted);
    }

    .cm-session-label::before {
        content: 'Session ' counter(cm-session);
    }

    /* The time picker has its own icon; Bootstrap's invalid icon would sit on top of it. */
    .cm .cm-session .form-control.is-invalid {
        background-image: none;
        padding-right: 0.85rem;
    }

    .cm-session-sep {
        color: var(--cm-muted);
        font-size: 0.85rem;
    }

    .cm-session-error {
        grid-column: 2 / -1;
        color: #dc2626;
        font-size: 0.8rem;
    }

    @media (max-width: 575px) {
        .cm-session {
            grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr) auto;
        }

        .cm-session-label,
        .cm-session-error {
            grid-column: 1 / -1;
        }
    }

    .cm-hours {
        display: grid;
        gap: 0.1rem;
        white-space: nowrap;
    }

    /* ---------- Appointments & token board ---------- */
    .cm-card-body {
        padding: 1.25rem;
    }

    .cm-date-nav {
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    .cm .cm-date-nav .form-control {
        width: auto;
        padding: 0.45rem 0.75rem;
    }

    .cm-stat-suffix {
        font-size: 1rem;
        font-weight: 600;
        color: var(--cm-subtle);
    }

    .cm-stat-muted {
        font-size: 1rem;
        font-weight: 600;
        color: var(--cm-subtle);
    }

    .cm-badge-red { background: #fef2f2; color: #b91c1c; }

    .cm-row-muted td {
        opacity: 0.6;
    }

    .cm-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem 1rem;
        font-size: 0.8rem;
        color: var(--cm-muted);
    }

    .cm-legend span {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }

    .cm-token-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(52px, 1fr));
        gap: 0.55rem;
    }

    .cm-token,
    .cm-token-swatch {
        position: relative;
        border-radius: 10px;
        border: 1px dashed var(--cm-border-strong);
        background: #fff;
        color: var(--cm-subtle);
    }

    .cm-token {
        aspect-ratio: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.88rem;
        cursor: default;
    }

    .cm-token-swatch {
        display: inline-block;
        width: 16px;
        height: 16px;
        border-radius: 5px;
    }

    .cm-token.is-booked,
    .cm-token-swatch.is-booked {
        background: var(--cm-primary);
        border: 1px solid var(--cm-primary);
        color: #fff;
    }

    /* White dot on a booked token: the patient has arrived. */
    .cm-token.is-present::after,
    .cm-token-swatch.is-present::after {
        content: '';
        position: absolute;
        top: 4px;
        right: 4px;
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #fff;
    }

    .cm-token-swatch.is-present::after {
        top: 3px;
        right: 3px;
        width: 5px;
        height: 5px;
    }

    .cm-token.is-running,
    .cm-token-swatch.is-running {
        box-shadow: 0 0 0 3px #f59e0b;
        border-color: #f59e0b;
    }

    .cm-token-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 38px;
        padding: 0.2rem 0.5rem;
        border-radius: 8px;
        background: var(--cm-primary-50);
        color: var(--cm-primary-ink);
        font-weight: 700;
    }

    .cm-token-pill.is-running {
        background: #fff7e6;
        color: #b45309;
        box-shadow: 0 0 0 2px #f59e0b;
    }

    /* ---------- Clinic profile hero ---------- */
    .cm-hero {
        margin-bottom: 1.5rem;
    }

    .cm-hero-main {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1.25rem;
        padding: 1.5rem;
    }

    .cm-hero-identity {
        display: flex;
        gap: 1.25rem;
        align-items: flex-start;
        min-width: 0;
        flex: 1 1 420px;
    }

    .cm-hero-name-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.6rem;
    }

    .cm-hero-sub {
        color: var(--cm-muted);
        font-size: 0.9rem;
        margin-top: 0.25rem;
    }

    .cm-hero-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem 1.25rem;
        margin-top: 0.85rem;
        font-size: 0.9rem;
    }

    .cm-hero-meta span,
    .cm-hero-meta a {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        color: var(--cm-body);
        text-decoration: none;
    }

    .cm-hero-meta a:hover {
        color: var(--cm-primary-ink);
    }

    .cm-hero-meta .bi {
        color: var(--cm-subtle);
    }

    .cm-hero-about {
        margin: 0.9rem 0 0;
        color: var(--cm-muted);
        font-size: 0.92rem;
        line-height: 1.55;
        max-width: 760px;
        white-space: pre-line;
    }

    .cm-hero-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        border-top: 1px solid var(--cm-border);
    }

    .cm-hero-stats > div {
        padding: 1rem 1.5rem;
        border-right: 1px solid var(--cm-border);
    }

    .cm-hero-stats > div:last-child {
        border-right: 0;
    }

    .cm-hero-stat-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--cm-ink);
        margin-top: 0.15rem;
    }

    .cm-hero-stat-text {
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--cm-ink);
        margin-top: 0.35rem;
    }

    @media (max-width: 767px) {
        .cm-hero-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .cm-hero-stats > div:nth-child(2) {
            border-right: 0;
        }

        .cm-hero-stats > div:nth-child(-n + 2) {
            border-bottom: 1px solid var(--cm-border);
        }
    }

    /* ---------- Modals ---------- */
    .cm-modal .modal-content {
        border: 0;
        border-radius: 16px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
    }

    .cm-modal .modal-header {
        border-bottom: 1px solid var(--cm-border);
        padding: 1.15rem 1.5rem;
        align-items: flex-start;
    }

    .cm-modal .modal-title {
        font-weight: 700;
        color: var(--cm-ink);
        font-size: 1.1rem;
    }

    .cm-modal .modal-body {
        padding: 1.25rem 1.5rem;
    }

    .cm-modal .modal-footer {
        border-top: 1px solid var(--cm-border);
        padding: 0.9rem 1.5rem;
    }

    .cm-confirm-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        background: #fef2f2;
        color: #dc2626;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        margin-bottom: 0.9rem;
    }

    .cm-pick-list {
        max-height: 340px;
        overflow-y: auto;
        border: 1px solid var(--cm-border);
        border-radius: 12px;
    }

    .cm-pick-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.7rem 0.9rem;
        border-bottom: 1px solid #f0f2f1;
        cursor: pointer;
        margin: 0;
    }

    .cm-pick-item:last-child {
        border-bottom: 0;
    }

    .cm-pick-item:hover {
        background: var(--cm-surface-alt);
    }

    .cm-pick-item .form-check-input {
        margin: 0;
        flex-shrink: 0;
    }

    /* Reset password partial is written for Bootstrap 4 markup. */
    #resetPasswordModal .modal-content {
        border: 0;
        border-radius: 16px;
    }

    #resetPasswordModal .close {
        border: 0;
        background: transparent;
        font-size: 1.5rem;
        line-height: 1;
        color: var(--cm-muted);
        padding: 0 0.25rem;
        margin-left: auto;
    }

    #resetPasswordModal .form-group {
        margin-bottom: 1rem;
    }

    #resetPasswordModal label {
        font-weight: 600;
        font-size: 0.85rem;
        margin-bottom: 0.35rem;
    }

    /* ---------- Pagination ---------- */
    .cm .pagination {
        --bs-pagination-color: var(--cm-primary-ink);
        --bs-pagination-hover-color: var(--cm-primary-hover);
        --bs-pagination-focus-color: var(--cm-primary-hover);
        --bs-pagination-focus-box-shadow: 0 0 0 0.2rem rgba(16, 144, 20, 0.15);
        --bs-pagination-active-bg: var(--cm-primary);
        --bs-pagination-active-border-color: var(--cm-primary);
        margin-bottom: 0;
    }

    .cm-pagination {
        padding: 0.9rem 1.25rem;
        border-top: 1px solid var(--cm-border);
    }

    .cm-pagination p {
        margin: 0;
        font-size: 0.85rem;
        color: var(--cm-muted);
    }
</style>
