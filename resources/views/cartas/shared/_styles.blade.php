<style>
    :root {
        --cpe-bg: #f4f0ec;
        --cpe-ink: #111111;
        --cpe-muted: #666666;
        --cpe-line: #dedbd7;
        --cpe-purple: #a900d9;
        --cpe-blue: #008fbd;
        --cpe-pink-panel: #d8bdc0;
    }

    body {
        background: var(--cpe-bg);
    }

    .cpe-page {
        flex: 1;
        min-height: 80vh;
        box-sizing: border-box;
        background: var(--cpe-bg);
        color: var(--cpe-ink);
        font-family: 'Montserrat', system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
        position: relative;
    }

    .cpe-logo {
        width: 136px;
        height: auto;
        flex-shrink: 0;
    }

    .cpe-logo-top {
        display: flex;
        align-items: center;
        justify-content: center;
        padding-top: 28px;
    }

    .cpe-logo-top a {
        display: inline-flex;
        align-items: center;
    }

    .cpe-title {
        font-size: 36px;
        line-height: 1.12;
        font-weight: 800;
        letter-spacing: 0;
        margin: 0;
    }

    .cpe-button {
        height: 38px;
        border: 0;
        border-radius: 6px;
        background: var(--cpe-purple);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 22px;
        font-size: 14px;
        font-weight: 800;
        text-decoration: none;
        cursor: pointer;
    }

    .cpe-button:hover,
    .cpe-button:focus {
        color: #fff;
        background: #9600c7;
        outline: none;
    }

    .cpe-button--ghost {
        border: 1px solid #d1d1d1;
        background: #fff;
        color: #333;
    }

    .cpe-button--ghost:hover,
    .cpe-button--ghost:focus {
        border-color: var(--cpe-purple);
        color: var(--cpe-purple);
        background: #fff;
    }

    .cpe-button--danger {
        width: 100%;
        background: #9f1d1d;
        color: #fff;
    }

    .cpe-button--danger:hover,
    .cpe-button--danger:focus {
        background: #861717;
        color: #fff;
    }

    .cpe-field,
    .cpe-select,
    .cpe-textarea {
        width: 100%;
        border: 1px solid #d6d6d6;
        border-radius: 6px;
        background: #fff;
        color: #333;
        font-size: 14px;
        outline: none;
    }

    .cpe-field,
    .cpe-select {
        height: 38px;
        padding: 0 12px;
    }

    .cpe-textarea {
        min-height: 370px;
        resize: vertical;
        padding: 12px;
    }

    .cpe-field:focus,
    .cpe-select:focus,
    .cpe-textarea:focus {
        border-color: var(--cpe-purple);
        box-shadow: 0 0 0 3px rgba(169, 0, 217, .12);
    }

    .cpe-combobox {
        position: relative;
    }

    .cpe-combobox__input::-webkit-search-cancel-button {
        -webkit-appearance: none;
    }

    .cpe-combobox__list {
        position: absolute;
        left: 0;
        right: 0;
        top: calc(100% + 4px);
        z-index: 20;
        display: none;
        max-height: 232px;
        overflow-y: auto;
        margin: 0;
        padding: 4px;
        list-style: none;
        background: #fff;
        border: 1px solid #d6d6d6;
        border-radius: 6px;
        box-shadow: 0 18px 38px rgba(0, 0, 0, .14);
    }

    .cpe-combobox.is-open .cpe-combobox__list {
        display: block;
    }

    .cpe-combobox__option {
        padding: 9px 10px;
        border-radius: 5px;
        font-size: 13px;
        color: #333;
        cursor: pointer;
    }

    .cpe-combobox__option[hidden] {
        display: none;
    }

    .cpe-combobox__option:hover,
    .cpe-combobox__option.is-active {
        background: var(--cpe-bg);
        color: var(--cpe-purple);
    }

    .cpe-combobox__empty {
        padding: 9px 10px;
        font-size: 12px;
        color: #888;
    }

    .cpe-upload {
        min-height: 102px;
        border: 1px solid #dfdfdf;
        border-radius: 6px;
        background: #fff;
        display: grid;
        place-items: center;
        text-align: center;
        cursor: pointer;
    }

    .cpe-upload > span:not(.cpe-upload-preview) {
        display: grid;
        justify-items: center;
        gap: 5px;
        padding: 14px;
    }

    .cpe-upload.has-file {
        border-color: rgba(169, 0, 217, .42);
        background: #fff;
        box-shadow: 0 0 0 3px rgba(169, 0, 217, .08);
    }

    .cpe-upload input {
        display: none;
    }

    .cpe-upload__icon {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: #f6f6f6;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #555;
        font-weight: 800;
    }

    .cpe-upload__icon svg {
        display: block;
    }

    .cpe-upload__link {
        display: block;
        color: var(--cpe-blue);
        font-weight: 800;
        font-size: 13px;
        line-height: 1.2;
    }

    .cpe-upload__hint {
        display: block;
        color: #6f6f6f;
        font-size: 11px;
        line-height: 1.25;
        margin-top: 0;
    }

    .cpe-upload--compact {
        min-height: 74px;
        margin-top: 10px;
    }

    .cpe-upload-preview {
        width: min(100%, 520px);
        display: none;
        grid-template-columns: 58px minmax(0, 1fr);
        align-items: center;
        gap: 12px;
        padding: 12px;
        text-align: left;
    }

    .cpe-upload.has-file > span {
        display: none;
    }

    .cpe-upload.has-file .cpe-upload-preview {
        display: grid;
    }

    .cpe-upload-preview__thumb {
        width: 58px;
        height: 58px;
        border-radius: 6px;
        background: #f4f0ec;
        border: 1px solid #e2dfdb;
        display: grid;
        place-items: center;
        color: var(--cpe-purple);
        font-size: 12px;
        font-weight: 800;
        overflow: hidden;
    }

    .cpe-upload-preview__thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .cpe-upload-preview__label {
        display: block;
        color: var(--cpe-blue);
        font-size: 12px;
        font-weight: 800;
        margin-bottom: 4px;
    }

    /* Garante que a coluna de texto no grid não expanda além do espaço disponível */
    .cpe-upload-preview > span:last-child {
        min-width: 0;
        overflow: hidden;
    }

    .cpe-upload-preview__name {
        display: block;
        color: #222;
        font-size: 13px;
        font-weight: 800;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 100%;
    }

    .cpe-upload-preview__meta {
        display: block;
        color: #777;
        font-size: 11px;
        margin-top: 4px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 100%;
    }

    .cpe-table-card {
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 1px 0 rgba(0, 0, 0, .05);
    }

    .cpe-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    .cpe-table th {
        color: #555;
        font-size: 11px;
        font-weight: 600;
        text-align: left;
        padding: 14px 14px;
        border-bottom: 1px solid #e8e8e8;
    }

    .cpe-table td {
        padding: 18px 14px;
        border-bottom: 1px solid #e8e8e8;
        color: #0f0f0f;
        vertical-align: middle;
    }

    .cpe-table tr:last-child td {
        border-bottom: 0;
    }

    .cpe-truncate {
        display: block;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .cpe-link {
        color: var(--cpe-blue);
        border: 0;
        background: transparent;
        padding: 0;
        text-decoration: none;
        font-weight: 800;
        font-size: 13px;
    }

    .cpe-icon-button {
        width: 28px;
        height: 28px;
        border: 0;
        background: transparent;
        color: #555;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        cursor: pointer;
        text-decoration: none;
    }

    .cpe-icon-button:hover,
    .cpe-icon-button:focus {
        color: var(--cpe-purple);
        outline: none;
    }

    .cpe-icon-button--disabled {
        color: #a0a0a0 !important;
        cursor: not-allowed !important;
        opacity: 0.65;
        pointer-events: auto;
    }

    .cpe-pill {
        display: inline-flex;
        align-items: center;
        min-height: 20px;
        border-radius: 999px;
        padding: 0 9px;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
    }

    .cpe-pill--blue {
        color: #008bbc;
        background: rgba(0, 139, 188, 0.1);
    }

    .cpe-pill--green {
        color: #106f31;
        background: #eefdf2;
    }

    .cpe-pill--yellow {
        color: #715200;
        background: #fff7e0;
    }

    .cpe-pill--purple {
        color: #9602c7;
        background: rgba(150, 2, 199, 0.12);
    }

    .cpe-pill--red {
        color: #b42318;
        background: rgba(180, 35, 24, 0.12);
    }

    .cpe-pill--priority {
        color: #9a3412;
        background: #ffedd5;
        border: 1px solid #fed7aa;
    }

    .cpe-modal {
        position: fixed;
        inset: 0;
        z-index: 3000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 24px;
    }

    .cpe-modal.is-open {
        display: flex;
    }

    .cpe-modal__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, .64);
        backdrop-filter: blur(7px);
    }

    .cpe-modal__dialog {
        position: relative;
        width: min(100%, 600px);
        max-height: calc(100vh - 48px);
        overflow-y: auto;
        background: var(--cpe-bg);
        border-radius: 10px;
        padding: 32px;
        box-shadow: 0 22px 60px rgba(0, 0, 0, .32);
    }

    .cpe-modal__dialog--wide {
        width: min(100%, 780px);
    }

    .cpe-modal h2 {
        margin: 0 0 12px;
        font-size: 18px;
        font-weight: 800;
    }

    .cpe-modal p {
        color: #000000;
        font-size: 13px;
        margin: 0 0 16px;
    }

    .cpe-modal-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-top: 18px;
    }

    .cpe-option-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin: 16px 0;
    }

    .cpe-choice {
        border: 1px solid #dfdfdf;
        border-radius: 6px;
        background: #fff;
        padding: 12px;
        display: grid;
        grid-template-columns: 30px 1fr auto;
        align-items: center;
        gap: 10px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
    }

    .cpe-choice:has(input:checked) {
        border-color: #b26cff;
        box-shadow: 0 0 0 2px rgba(178, 108, 255, .16);
    }

    .cpe-choice__icon {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: #ffe6ff;
        color: var(--cpe-purple);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
    }

    .cpe-alert {
        border: 1px solid #b7deb7;
        border-radius: 6px;
        background: #f2fbf2;
        color: #2e7a2e;
        padding: 10px 12px;
        font-size: 13px;
        font-weight: 500;
        margin-bottom: 14px;
    }

    .cpe-alert--error {
        border-color: #d04b4b;
        color: #9f1d1d;
        background: #fffafa;
    }

    .cpe-letter-preview {
        min-height: 360px;
        max-height: 55vh;
        overflow-y: auto;
        background: var(--cpe-pink-panel);
        border-radius: 8px;
        color: #333;
        padding: 24px;
        line-height: 1.55;
        white-space: pre-wrap;
    }

    .cpe-letter-preview--media {
        padding: 0;
        overflow: hidden;
        display: grid;
        place-items: center;
        background: #fff;
        border: 1px solid #e1dedb;
    }

    .cpe-letter-preview--media img,
    .cpe-letter-preview--media iframe,
    .cpe-letter-preview--media object {
        width: 100%;
        height: min(55vh, 520px);
        border: 0;
        display: block;
    }

    .cpe-letter-preview--media img {
        height: auto;
        max-height: min(55vh, 520px);
        object-fit: contain;
    }

    .cpe-file-placeholder {
        width: 100%;
        min-height: 330px;
        display: grid;
        place-items: center;
        color: rgba(0, 0, 0, .45);
        font-weight: 700;
        text-align: center;
        padding: 24px;
    }

    .cpe-floating-user {
        position: absolute;
        right: 28px;
        top: 22px;
        z-index: 500;
    }

    .cpe-user-trigger {
        min-height: 38px;
        max-width: 260px;
        border: 1px solid rgba(0, 143, 189, .2);
        border-radius: 7px;
        background: #fff;
        color: var(--cpe-ink);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 0 12px;
        font-size: 13px;
        font-weight: 700;
        line-height: 1;
        box-shadow: 0 8px 22px rgba(0, 143, 189, .08);
        cursor: pointer;
    }

    .cpe-user-trigger span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .cpe-user-trigger:hover,
    .cpe-user-trigger:focus {
        border-color: var(--cpe-purple);
        color: #8000a5;
        outline: none;
    }

    .cpe-user-dropdown {
        position: absolute;
        right: 0;
        top: calc(100% + 8px);
        width: 190px;
        display: none;
        background: #fff;
        border-radius: 6px;
        border: 1px solid rgba(0, 0, 0, .08);
        box-shadow: 0 18px 38px rgba(0, 0, 0, .14);
        padding: 6px;
    }

    .cpe-floating-user.is-open .cpe-user-dropdown {
        display: block;
    }

    .cpe-user-dropdown a,
    .cpe-user-dropdown button {
        display: block;
        width: 100%;
        border: 0;
        background: transparent;
        text-align: left;
        border-radius: 5px;
        padding: 8px 10px;
        font-weight: 700;
        color: #333;
        text-decoration: none;
        font-size: 13px;
    }

    .cpe-user-dropdown a:hover,
    .cpe-user-dropdown button:hover {
        color: var(--cpe-purple);
        background: var(--cpe-bg);
    }

    @media (max-width: 900px) {
        .cpe-title {
            font-size: 30px;
        }

        .cpe-table-card {
            overflow-x: auto;
        }

        .cpe-option-grid,
        .cpe-modal-actions {
            grid-template-columns: 1fr;
        }

        .cpe-floating-user {
            right: 14px;
            top: 14px;
        }

        .cpe-user-trigger {
            max-width: 190px;
        }
    }
</style>
