<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-modal-open]').forEach((button) => {
            button.addEventListener('click', () => {
                document.getElementById(button.dataset.modalOpen)?.classList.add('is-open');
            });
        });

        document.querySelectorAll('[data-modal-close]').forEach((button) => {
            button.addEventListener('click', () => {
                button.closest('.cpe-modal')?.classList.remove('is-open');
            });
        });

        const marcarItemAtivo = (button) => {
            if (!button.closest('.cpe-msg-list')) return;
            document.querySelectorAll('.cpe-msg-list [data-aside-open]').forEach((item) => {
                item.classList.toggle('is-active', item === button);
            });
        };

        document.querySelectorAll('[data-aside-open]').forEach((button) => {
            button.addEventListener('click', () => {
                const targetId = button.dataset.asideOpen;
                const aside = document.querySelector('.cpe-conversation__aside');
                if (!aside) return;

                aside.querySelectorAll('.cpe-aside-panel').forEach((panel) => {
                    panel.hidden = panel.id !== targetId;
                });

                marcarItemAtivo(button);
            });
        });

        // Na tela de conversa, abre a carta mais recente por padrao.
        const cpeAside = document.querySelector('.cpe-conversation__aside');
        if (cpeAside) {
            const paineis = cpeAside.querySelectorAll('.cpe-aside-panel:not(.cpe-aside-panel--default)');
            const ultimo = paineis[paineis.length - 1];
            if (ultimo) {
                cpeAside.querySelectorAll('.cpe-aside-panel').forEach((panel) => {
                    panel.hidden = panel.id !== ultimo.id;
                });
                const item = document.querySelector(`.cpe-msg-list [data-aside-open="${ultimo.id}"]`);
                if (item) {
                    item.classList.add('is-active');
                }
            }
        }

        document.querySelectorAll('[data-aside-close]').forEach((button) => {
            button.addEventListener('click', () => {
                const aside = button.closest('.cpe-conversation__aside');
                if (!aside) return;

                aside.querySelectorAll('.cpe-aside-panel').forEach((panel) => {
                    panel.hidden = panel.id !== 'asideDefault';
                });
            });
        });

        document.querySelectorAll('.cpe-modal__backdrop').forEach((backdrop) => {
            backdrop.addEventListener('click', () => {
                backdrop.closest('.cpe-modal')?.classList.remove('is-open');
            });
        });

        document.querySelectorAll('.cpe-modal__dialog').forEach((dialog) => {
            dialog.addEventListener('click', (event) => {
                event.stopPropagation();
            });
        });

        document.querySelectorAll('.cpe-upload input[type="file"]').forEach((input) => {
            const upload = input.closest('.cpe-upload');
            if (!upload) {
                return;
            }

            const preview = document.createElement('span');
            preview.className = 'cpe-upload-preview';
            preview.innerHTML = `
                <span class="cpe-upload-preview__thumb">ARQ</span>
                <span>
                    <span class="cpe-upload-preview__label">Arquivo selecionado</span>
                    <span class="cpe-upload-preview__name"></span>
                    <span class="cpe-upload-preview__meta"></span>
                </span>
            `;
            upload.appendChild(preview);

            // Elemento de erro (criado uma vez por input)
            let errorEl = upload.querySelector('.cpe-upload-error');
            if (!errorEl) {
                errorEl = document.createElement('span');
                errorEl.className = 'cpe-upload-error';
                errorEl.setAttribute('role', 'alert');
                errorEl.style.cssText = 'display:none;color:#dc3545;font-size:.8rem;margin-top:.25rem;font-weight:500;';
                upload.appendChild(errorEl);
            }

            const MAX_BYTES = 10 * 1024 * 1024; // 10 MB

            input.addEventListener('change', () => {
                const file = input.files?.[0];
                const thumb = preview.querySelector('.cpe-upload-preview__thumb');
                const name  = preview.querySelector('.cpe-upload-preview__name');
                const meta  = preview.querySelector('.cpe-upload-preview__meta');

                // Limpa estado de erro anterior
                errorEl.style.display = 'none';
                errorEl.textContent = '';

                if (!file) {
                    upload.classList.remove('has-file');
                    thumb.textContent = 'ARQ';
                    name.textContent = '';
                    meta.textContent = '';
                    return;
                }

                // Validação de tamanho no lado do cliente
                if (file.size > MAX_BYTES) {
                    // Reseta o input para não acumular arquivo inválido
                    try {
                        input.value = '';
                        const dt = new DataTransfer();
                        input.files = dt.files;
                    } catch (_) { input.value = ''; }

                    upload.classList.remove('has-file');
                    thumb.textContent = 'ARQ';
                    name.textContent = '';
                    meta.textContent = '';

                    errorEl.textContent = `⚠ Arquivo muito grande (${formatFileSize(file.size)}). O limite é 10 MB. Selecione outro PDF.`;
                    errorEl.style.display = 'block';
                    return;
                }

                // Garante que apenas 1 arquivo fica no input (substitui qualquer seleção anterior)
                try {
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    input.files = dt.files;
                } catch (_) { /* fallback: navegador mais antigo, aceita como está */ }

                upload.classList.add('has-file');
                name.textContent = file.name;
                meta.textContent = `${formatFileSize(file.size)} · PDF selecionado ✓ — clique para trocar`;
                thumb.innerHTML = '';
                thumb.textContent = 'PDF';
            });
        });

        const normalize = (value) => (value || '')
            .toString()
            .normalize('NFD')
            .replace(/[̀-ͯ]/g, '')
            .toLowerCase()
            .trim();

        document.querySelectorAll('[data-combobox]').forEach((combobox) => {
            const input = combobox.querySelector('[data-combobox-input]');
            const hidden = combobox.querySelector('[data-combobox-value]');
            const options = Array.from(combobox.querySelectorAll('.cpe-combobox__option'));
            const empty = combobox.querySelector('[data-combobox-empty]');

            if (!input || !hidden) {
                return;
            }

            const setActive = (option) => {
                options.forEach((item) => item.classList.remove('is-active'));
                if (option) {
                    option.classList.add('is-active');
                    option.scrollIntoView({ block: 'nearest' });
                }
            };

            const filter = () => {
                const term = normalize(input.value);
                const digits = input.value.replace(/\D/g, '');
                let visible = 0;

                options.forEach((option) => {
                    const cpf = option.dataset.cpf || '';
                    const match = term === ''
                        || normalize(option.dataset.label).includes(term)
                        || (digits.length >= 3 && cpf !== '' && cpf.includes(digits));
                    option.hidden = !match;
                    if (match) {
                        visible++;
                    }
                });

                if (empty) {
                    empty.hidden = visible > 0;
                }
            };

            const select = (option) => {
                hidden.value = option.dataset.value;
                input.value = option.dataset.label;
                combobox.classList.remove('is-open');
                input.setAttribute('aria-expanded', 'false');
                setActive(null);
            };

            const open = () => {
                filter();
                combobox.classList.add('is-open');
                input.setAttribute('aria-expanded', 'true');
            };

            const close = () => {
                combobox.classList.remove('is-open');
                input.setAttribute('aria-expanded', 'false');
                setActive(null);

                if (!hidden.value) {
                    const exact = options.find((option) => normalize(option.dataset.label) === normalize(input.value));
                    if (exact) {
                        select(exact);
                    } else {
                        input.value = '';
                    }
                }
            };

            input.addEventListener('focus', open);
            input.addEventListener('click', open);
            input.addEventListener('input', () => {
                hidden.value = '';
                open();
            });

            options.forEach((option) => {
                option.addEventListener('mousedown', (event) => {
                    event.preventDefault();
                    select(option);
                });
            });

            input.addEventListener('keydown', (event) => {
                const visibleOptions = options.filter((option) => !option.hidden);
                const current = combobox.querySelector('.cpe-combobox__option.is-active');
                const index = visibleOptions.indexOf(current);

                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    open();
                    setActive(visibleOptions[Math.min(index + 1, visibleOptions.length - 1)] || visibleOptions[0]);
                } else if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    setActive(visibleOptions[Math.max(index - 1, 0)]);
                } else if (event.key === 'Enter' && combobox.classList.contains('is-open')) {
                    event.preventDefault();
                    if (current) {
                        select(current);
                    }
                } else if (event.key === 'Escape') {
                    close();
                }
            });

            document.addEventListener('click', (event) => {
                if (!combobox.contains(event.target)) {
                    close();
                }
            });
        });

        document.querySelectorAll('[data-modo-form]').forEach((form) => {
            const radios = Array.from(form.querySelectorAll('input[name="modo_resposta"]'));
            const fields = Array.from(form.querySelectorAll('.cpe-modo-field'));

            if (!radios.length || !fields.length) {
                return;
            }

            const apply = () => {
                const selected = radios.find((radio) => radio.checked)?.value ?? null;

                fields.forEach((field) => {
                    field.hidden = selected !== field.dataset.modo;
                });
            };

            radios.forEach((radio) => radio.addEventListener('change', apply));
            apply();
        });

        let printFrame = null;
        document.querySelectorAll('[data-print-src]').forEach((button) => {
            button.addEventListener('click', () => {
                const src = button.dataset.printSrc;
                if (!src) {
                    return;
                }

                if (printFrame) {
                    printFrame.remove();
                }

                printFrame = document.createElement('iframe');
                printFrame.setAttribute('aria-hidden', 'true');
                Object.assign(printFrame.style, {
                    position: 'fixed',
                    right: '0',
                    bottom: '0',
                    width: '0',
                    height: '0',
                    border: '0',
                });

                printFrame.addEventListener('load', () => {
                    // O plugin de PDF precisa montar antes de imprimir.
                    setTimeout(() => {
                        try {
                            printFrame.contentWindow.focus();
                            printFrame.contentWindow.print();
                        } catch (error) {
                            window.open(src, '_blank');
                        }
                    }, 300);
                });

                printFrame.src = src;
                document.body.appendChild(printFrame);
            });
        });

        document.querySelectorAll('.cpe-floating-user').forEach((menu) => {
            const trigger = menu.querySelector('.cpe-user-trigger');
            trigger?.addEventListener('click', (event) => {
                event.stopPropagation();
                const isOpen = menu.classList.toggle('is-open');
                trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });
        });

        document.addEventListener('click', () => {
            document.querySelectorAll('.cpe-floating-user').forEach((menu) => {
                menu.classList.remove('is-open');
                menu.querySelector('.cpe-user-trigger')?.setAttribute('aria-expanded', 'false');
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                document.querySelectorAll('.cpe-modal.is-open').forEach((modal) => modal.classList.remove('is-open'));
                document.querySelectorAll('.cpe-floating-user').forEach((menu) => {
                    menu.classList.remove('is-open');
                    menu.querySelector('.cpe-user-trigger')?.setAttribute('aria-expanded', 'false');
                });
            }
        });

        function formatFileSize(bytes) {
            if (bytes < 1024 * 1024) {
                return `${Math.max(1, Math.round(bytes / 1024))} KB`;
            }

            return `${(bytes / 1024 / 1024).toFixed(1).replace('.', ',')} MB`;
        }
    });
</script>
