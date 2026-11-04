(function () {
    'use strict';

    const cfg = window.MONPG_CONFIG || {};
    const strings = cfg.strings || {};
    const allowedMime = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
    const maxBytes = Number(cfg.maxFileBytes || 5242880);
    const maxFiles = Number(cfg.maxFiles || 8);

    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
        });
    }

    function cleanText(value) {
        return String(value || '').replace(/\u0000/g, '').trim();
    }

    function formatDate() {
        try {
            return new Intl.DateTimeFormat('bg-BG', { day: '2-digit', month: 'long', year: 'numeric' }).format(new Date());
        } catch (e) {
            return new Date().toLocaleDateString();
        }
    }

    function fieldValue(form, name) {
        const el = form.elements.namedItem(name);
        return el ? cleanText(el.value) : '';
    }

    function track(eventName) {
        if (!cfg.ajaxUrl || !cfg.nonce || !window.fetch) return;
        const body = new URLSearchParams();
        body.set('action', 'monpg_track');
        body.set('nonce', cfg.nonce);
        body.set('event', eventName);
        fetch(cfg.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString(),
            keepalive: true
        }).catch(function () { /* Statistics are optional; never interrupt the user flow. */ });
    }

    function collectData(app) {
        const form = app.querySelector('.monpg-form');
        const data = {};
        Array.from(form.elements).forEach(function (el) {
            if (!el.name || el.type === 'file' || el.type === 'checkbox' || el.name === 'confirm_local') return;
            if (el.name === 'credits') return;
            data[el.name] = cleanText(el.value);
        });
        data.credits = Array.from(app.querySelectorAll('[data-list="credits"] [data-row]')).map(function (row) {
            const item = {};
            row.querySelectorAll('[data-field]').forEach(function (input) {
                item[input.dataset.field] = cleanText(input.value);
            });
            return item;
        }).filter(function (item) {
            return Object.values(item).some(Boolean);
        });
        data.attachments = Array.from(app.querySelector('[data-attachments]').files || []);
        return data;
    }

    function renderPortfolio(data, attachmentDataUrls) {
        const sections = [
            {
                title: '1. Основна информация',
                fields: [
                    ['Име', data.first_name], ['Презиме', data.middle_name], ['Фамилия', data.last_name],
                    ['Длъжност', data.position], ['Учебен предмет / група', data.subject],
                    ['Училище / детска градина', data.institution], ['Населено място', data.location],
                    ['Общ трудов стаж (години)', data.total_experience], ['Педагогически стаж (години)', data.teaching_experience],
                    ['Любимо мото / педагогически цитат', data.motto]
                ]
            },
            {
                title: '2. Образование и квалификация',
                fields: [
                    ['Образователна степен', data.education], ['Специалност', data.specialty],
                    ['Придобита ПКС', data.pqs], ['Владеене на чужди езици', data.languages]
                ],
                credits: data.credits
            },
            {
                title: '3. Практическа дейност и философия',
                fields: [
                    ['Методи на преподаване и дигитални инструменти', data.methods],
                    ['Педагогическа философия / верую', data.philosophy],
                    ['Добри практики и открити уроци', data.practices]
                ]
            },
            {
                title: '4. Участие и членство',
                fields: [
                    ['Участие в проекти и програми', data.projects],
                    ['Научни изяви, конференции и форуми', data.events],
                    ['Членство в професионални организации', data.memberships]
                ]
            },
            {
                title: '5. Постижения и авторство',
                fields: [
                    ['Лични награди и отличия', data.awards],
                    ['Постижения и спечелени награди от ученици', data.student_achievements],
                    ['Авторство на учебници, помагала и статии', data.publications]
                ]
            }
        ];

        function fieldHtml(label, value) {
            if (!value) return '';
            return '<div class="monpg-pdf-field"><h4>' + escapeHtml(label) + '</h4><p>' +
                escapeHtml(value).replace(/\n/g, '<br>') + '</p></div>';
        }

        let html = '<div class="monpg-pdf-document" id="monpg-pdf-document">' +
            '<header class="monpg-pdf-cover"><div class="monpg-pdf-brand">ПРОФЕСИОНАЛНО ПОРТФОЛИО</div>' +
            '<div class="monpg-pdf-cover-rule"></div><h1>УЧИТЕЛСКО<br>ПОРТФОЛИО</h1>' +
            '<div class="monpg-pdf-cover-subtitle">Професионален профил • Педагогическа практика • Развитие</div>' +
            '<div class="monpg-pdf-cover-person">' + escapeHtml([data.first_name, data.middle_name, data.last_name].filter(Boolean).join(' ')) + '</div>' +
            '<div class="monpg-pdf-cover-role">' + escapeHtml(data.position || '') + '</div>' +
            '<div class="monpg-pdf-cover-school">' + escapeHtml(data.institution || '') + '</div>' +
            '<div class="monpg-pdf-cover-date">' + escapeHtml(formatDate()) + '</div></header>';

        sections.forEach(function (section) {
            const populated = section.fields.filter(function (pair) { return cleanText(pair[1]); });
            if (!populated.length && !(section.credits && section.credits.length)) return;
            html += '<section class="monpg-pdf-section"><h2>' + escapeHtml(section.title) + '</h2>';
            populated.forEach(function (pair) { html += fieldHtml(pair[0], pair[1]); });
            if (section.credits && section.credits.length) {
                html += '<div class="monpg-pdf-field"><h4>Квалификационни кредити и обучения</h4><table class="monpg-pdf-table"><thead><tr><th>Тема</th><th>Кредити</th><th>Код по МОН</th><th>Организация</th></tr></thead><tbody>';
                section.credits.forEach(function (item) {
                    html += '<tr><td>' + escapeHtml(item.topic) + '</td><td>' + escapeHtml(item.credits) + '</td><td>' + escapeHtml(item.code) + '</td><td>' + escapeHtml(item.organization) + '</td></tr>';
                });
                html += '</tbody></table></div>';
            }
            html += '</section>';
        });

        if (attachmentDataUrls && attachmentDataUrls.length) {
            html += '<section class="monpg-pdf-section"><h2>6. Приложения и доказателствени материали</h2>';
            attachmentDataUrls.forEach(function (item, index) {
                if (item.type.indexOf('image/') === 0) {
                    html += '<div class="monpg-pdf-attachment"><h4>Приложение ' + (index + 1) + '</h4><img src="' + item.url + '" alt="Приложен материал ' + (index + 1) + '"></div>';
                } else {
                    html += '<div class="monpg-pdf-attachment monpg-pdf-attachment-note"><h4>Приложение ' + (index + 1) + '</h4><p>Прикачен PDF документ: ' + escapeHtml(item.name) + '</p><p>Съдържанието на външния PDF не се вгражда автоматично. Представете оригиналния файл отделно.</p></div>';
                }
            });
            html += '</section>';
        }

        html += '<footer class="monpg-pdf-footer"><span>Учителско портфолио</span><span>Създадено локално от MON Portfolio Generator</span></footer></div>';
        return html;
    }

    function readFiles(files) {
        return Promise.all(files.map(function (file) {
            return new Promise(function (resolve, reject) {
                if (!file || !allowedMime.includes(file.type)) {
                    reject(new Error(strings.fileType || 'Неподдържан файлов формат.'));
                    return;
                }
                if (file.size > maxBytes) {
                    reject(new Error(strings.fileSize || 'Файлът е твърде голям.'));
                    return;
                }
                const reader = new FileReader();
                reader.onload = function () {
                    resolve({ name: file.name, type: file.type, url: reader.result });
                };
                reader.onerror = function () { reject(new Error('Неуспешно прочитане на файл.')); };
                reader.readAsDataURL(file);
            });
        }));
    }

    function initApp(app) {
        if (app.dataset.initialized === '1') return;
        app.dataset.initialized = '1';

        const form = app.querySelector('.monpg-form');
        const panels = Array.from(app.querySelectorAll('[data-step]'));
        const prevBtn = app.querySelector('[data-prev]');
        const nextBtn = app.querySelector('[data-next]');
        const progressBar = app.querySelector('[data-progress-bar]');
        const currentLabel = app.querySelector('[data-current-step]');
        const percentLabel = app.querySelector('[data-progress-percent]');
        const dots = Array.from(app.querySelectorAll('[data-jump-step]'));
        const errorBox = app.querySelector('.monpg-form-error');
        const status = app.querySelector('[data-status]');
        const fileInput = app.querySelector('[data-attachments]');
        const fileList = app.querySelector('[data-file-list]');
        const modal = app.querySelector('[data-modal]');
        const previewBody = app.querySelector('[data-preview-body]');
        let step = 0;
        let attachmentDataUrls = [];
        let lastObjectUrl = null;

        function showError(message) {
            errorBox.textContent = message || '';
            errorBox.hidden = !message;
            if (message) errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function showStatus(message) {
            status.textContent = message || '';
            status.classList.toggle('is-visible', Boolean(message));
        }

        function updateStep() {
            panels.forEach(function (panel, index) {
                panel.hidden = index !== step;
                panel.classList.toggle('is-active', index === step);
            });
            currentLabel.textContent = String(step + 1);
            const percent = Math.round(((step + 1) / panels.length) * 100);
            percentLabel.textContent = percent + '%';
            progressBar.style.width = percent + '%';
            prevBtn.disabled = step === 0;
            nextBtn.hidden = step === panels.length - 1;
            nextBtn.textContent = step === panels.length - 2 ? 'Към генериране →' : 'Продължи →';
            dots.forEach(function (dot, index) {
                dot.classList.toggle('is-active', index === step);
                dot.classList.toggle('is-complete', index < step);
                dot.setAttribute('aria-current', index === step ? 'step' : 'false');
            });
            showError('');
            showStatus('');
            app.querySelector('.monpg-progress-wrap').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function validateStep(index) {
            if (index !== 0 && index !== 5) return true;
            const required = index === 0
                ? ['first_name', 'last_name', 'position']
                : ['confirm_local'];
            for (const name of required) {
                const el = form.elements.namedItem(name);
                if (!el || (el.type === 'checkbox' ? !el.checked : !cleanText(el.value))) {
                    if (el) el.focus();
                    showError(strings.required || 'Моля, попълнете задължителните полета.');
                    return false;
                }
            }
            return true;
        }

        function validateAll() {
            for (let i = 0; i < panels.length; i++) {
                if (!validateStep(i)) {
                    step = i;
                    updateStep();
                    return false;
                }
            }
            return true;
        }

        function makePreview() {
            const data = collectData(app);
            return renderPortfolio(data, attachmentDataUrls);
        }

        async function openPreview() {
            if (!validateAll()) return;
            try {
                attachmentDataUrls = await readFiles(Array.from(fileInput.files || []));
                previewBody.innerHTML = makePreview();
                modal.hidden = false;
                document.body.classList.add('monpg-modal-open');
                track('previewed');
            } catch (err) {
                showError(err.message || strings.fileType);
            }
        }

        async function downloadPdf() {
            if (!validateAll()) return;
            if (!window.jspdf || !window.html2canvas) {
                showError(strings.pdfError || 'PDF библиотеките не са заредени. Проверете връзката и опитайте отново.');
                return;
            }
            try {
                showError('');
                showStatus('Подготвяне на PDF…');
                attachmentDataUrls = await readFiles(Array.from(fileInput.files || []));
                const html = makePreview();
                const staging = document.createElement('div');
                staging.className = 'monpg-pdf-staging';
                staging.innerHTML = html;
                document.body.appendChild(staging);

                const element = staging.querySelector('#monpg-pdf-document');
                const options = {
                    margin: [12, 12, 15, 12],
                    filename: 'Uchitelsko-portfolio.pdf',
                    image: { type: 'jpeg', quality: 0.94 },
                    html2canvas: {
                        scale: 1.6,
                        useCORS: true,
                        backgroundColor: '#ffffff',
                        logging: false,
                        scrollY: 0
                    },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait', compress: true },
                    pagebreak: { mode: ['css', 'legacy'], avoid: ['.monpg-pdf-field', '.monpg-pdf-attachment'] }
                };

                await window.html2pdf().set(options).from(element).save();
                staging.remove();
                showStatus(strings.pdfReady || 'PDF е генериран.');
                track('generated');
            } catch (err) {
                const staging = document.querySelector('.monpg-pdf-staging');
                if (staging) staging.remove();
                showError(strings.pdfError || 'Неуспешно генериране на PDF.');
                showStatus('');
                console.error('MONPG PDF generation error:', err);
            }
        }

        function renderFileList() {
            fileList.innerHTML = '';
            const files = Array.from(fileInput.files || []);
            if (files.length > maxFiles) {
                showError(strings.tooMany || 'Твърде много файлове.');
                fileInput.value = '';
                return;
            }
            files.forEach(function (file, index) {
                const item = document.createElement('div');
                item.className = 'monpg-file-item';
                const name = document.createElement('span');
                name.textContent = file.name + ' (' + Math.max(1, Math.round(file.size / 1024)) + ' KB)';
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.textContent = 'Премахни';
                remove.setAttribute('aria-label', 'Премахни ' + file.name);
                remove.addEventListener('click', function () {
                    const dt = new DataTransfer();
                    Array.from(fileInput.files).forEach(function (f, i) { if (i !== index) dt.items.add(f); });
                    fileInput.files = dt.files;
                    renderFileList();
                });
                item.appendChild(name);
                item.appendChild(remove);
                fileList.appendChild(item);
            });
        }

        function addCreditRow() {
            const list = app.querySelector('[data-list="credits"]');
            const first = list.querySelector('[data-row]');
            const clone = first.cloneNode(true);
            clone.querySelectorAll('input').forEach(function (input) { input.value = ''; });
            clone.querySelector('[data-remove-row]')?.remove();
            const remove = clone.querySelector('.monpg-remove-row');
            remove.disabled = false;
            remove.addEventListener('click', function () { clone.remove(); });
            list.appendChild(clone);
        }

        app.querySelectorAll('[data-add-row="credits"]').forEach(function (button) {
            button.addEventListener('click', addCreditRow);
        });
        app.querySelectorAll('.monpg-remove-row').forEach(function (button) {
            button.addEventListener('click', function () {
                const row = button.closest('[data-row]');
                if (row && app.querySelectorAll('[data-list="credits"] [data-row]').length > 1) row.remove();
            });
        });

        prevBtn.addEventListener('click', function () {
            if (step > 0) { step--; updateStep(); }
        });
        nextBtn.addEventListener('click', function () {
            if (!validateStep(step)) return;
            if (step < panels.length - 1) { step++; updateStep(); }
        });
        dots.forEach(function (dot) {
            dot.addEventListener('click', function () {
                const target = Number(dot.dataset.jumpStep);
                if (target <= step || validateStep(step)) { step = target; updateStep(); }
            });
        });

        app.querySelector('[data-preview]').addEventListener('click', openPreview);
        app.querySelector('[data-download]').addEventListener('click', downloadPdf);
        app.querySelector('[data-modal-download]').addEventListener('click', downloadPdf);
        app.querySelectorAll('[data-close-modal]').forEach(function (el) {
            el.addEventListener('click', function () {
                modal.hidden = true;
                document.body.classList.remove('monpg-modal-open');
            });
        });
        fileInput.addEventListener('change', renderFileList);
        form.addEventListener('submit', function (event) { event.preventDefault(); });

        updateStep();
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-monpg]').forEach(initApp);
    });
})();