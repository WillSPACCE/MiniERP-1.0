document.addEventListener('DOMContentLoaded', function () {
    requestAnimationFrame(function () {
        document.body.classList.add('page-ready');
    });

    // Esconde o overlay de carregamento após a página estar pronta.
    (function hideLoader() {
        const loader = document.getElementById('page-loader');
        if (!loader) return;
        // Aguarda o próximo frame para garantir transição suave
        const start = Date.now();
        requestAnimationFrame(() => {
            const minShow = 80; // ms
            const hide = () => {
                loader.classList.add('page-loader--hidden');
                setTimeout(() => { if (loader && loader.parentNode) loader.parentNode.removeChild(loader); }, 120);
            };
            const elapsed = Date.now() - start;
            if (elapsed >= minShow) hide(); else setTimeout(hide, minShow - elapsed);
        });
    })();

    // Intercept internal link clicks and form submits to show loader during navigation
    (function hookNavigationLoader() {
        const loader = document.getElementById('page-loader');
        if (!loader) return;

        function showLoaderBeforeNavigate() {
            loader.classList.remove('page-loader--hidden');
            // ensure it's in DOM
            if (!document.getElementById('page-loader')) document.body.appendChild(loader);
        }

        // Links
        document.addEventListener('click', function (e) {
            const a = e.target.closest('a');
            if (!a) return;
            const href = a.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
            // only internal navigations
            if (href.startsWith('http') && new URL(href, location.href).origin !== location.origin) return;

            // show loader and allow navigation
            showLoaderBeforeNavigate();
            // small delay to allow animation to show
            // do not prevent default for middle-click/ctrl-click
            if (e.ctrlKey || e.metaKey || e.button === 1) return;
            e.preventDefault();
            setTimeout(() => { location.href = href; }, 30);
        }, {capture: true});

        // Forms (submit)
        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!form || !(form instanceof HTMLFormElement)) return;
            // Capture happens before AJAX controllers call preventDefault().
            // Wait for propagation and cover only a real page navigation.
            setTimeout(function () {
                if (!e.defaultPrevented && !form.matches('[data-ajax-form], .md-form')) {
                    showLoaderBeforeNavigate();
                }
            }, 0);
        }, {capture: true});
    })();

    const toggle = document.querySelector('.hamburger-toggle');
    const shell = document.querySelector('.app-shell-left');
    if (toggle && shell) {
        toggle.setAttribute('aria-expanded', String(!shell.classList.contains('collapsed')));
        toggle.addEventListener('click', function () {
            shell.classList.toggle('collapsed');
            toggle.setAttribute('aria-expanded', String(!shell.classList.contains('collapsed')));
        });
    }

    // Smooth appearance
    document.body.classList.add('animated-ui');

    // initialize feather icons if present
    try { if (window.feather) window.feather.replace(); } catch (e) { /* ignore */ }

    // Submenu behavior for navbar items (hover + keyboard accessible)
    (function () {
        const items = document.querySelectorAll('.menu-item-wrapper');
        items.forEach(item => {
            const submenu = item.querySelector('.submenu');
            if (!submenu) return;

            function adjustPosition() {
                const rect = item.getBoundingClientRect();
                const submenuWidth = submenu.offsetWidth || 200;
                const viewportPadding = 8;
                const fitsRight = rect.right + submenuWidth + viewportPadding <= window.innerWidth;
                const fitsLeft = rect.left - submenuWidth >= viewportPadding;

                if (fitsRight) {
                    submenu.style.left = '0';
                    submenu.style.right = 'auto';
                    submenu.style.transform = 'none';
                } else if (fitsLeft) {
                    submenu.style.left = 'auto';
                    submenu.style.right = '0';
                    submenu.style.transform = 'none';
                } else {
                    submenu.style.left = '50%';
                    submenu.style.right = 'auto';
                    submenu.style.transform = 'translateX(-50%)';
                }
            }

            item.addEventListener('mouseenter', function () {
                requestAnimationFrame(adjustPosition);
            });
            item.addEventListener('focusin', function () {
                requestAnimationFrame(adjustPosition);
            });
            window.addEventListener('resize', adjustPosition);
            window.addEventListener('scroll', adjustPosition, { passive: true });
        });
    })();

    // Drawer hamburger behavior
    (function () {
        const hamburger = document.getElementById('hamburger');
        const drawer = document.getElementById('sidebar-drawer');
        const backdrop = document.getElementById('drawer-backdrop');
        // drawerClose button removed; compact mode on desktop will be handled by CSS/JS
        if (!hamburger || !drawer || !backdrop) return;

        function isMobile() { return window.innerWidth < 900; }

        function openDrawer() {
            drawer.classList.add('open');
            backdrop.classList.add('open');
            drawer.setAttribute('aria-hidden', 'false');
            hamburger.setAttribute('aria-expanded', 'true');
        }
        function closeDrawer() {
            drawer.classList.remove('open');
            backdrop.classList.remove('open');
            drawer.setAttribute('aria-hidden', 'true');
            hamburger.setAttribute('aria-expanded', 'false');
        }

        // Toggle only on mobile; on desktop drawer stays fixed open via CSS
        hamburger.addEventListener('click', function () {
            if (!isMobile()) return;
            if (drawer.classList.contains('open')) closeDrawer(); else openDrawer();
        });
        backdrop.addEventListener('click', function () { if (isMobile()) closeDrawer(); });

        // category toggles inside drawer (work on both mobile and desktop)
        drawer.querySelectorAll('.cat-toggle').forEach(btn => {
            btn.addEventListener('click', function () {
                const ul = btn.nextElementSibling;
                if (!ul) return;
                const open = ul.classList.toggle('open');
                btn.setAttribute('aria-expanded', String(open));
            });
        });

        // helpers to open/close all submenus with animation
        function openAllSubmenus() {
            drawer.querySelectorAll('.drawer-submenu').forEach((ul) => {
                ul.classList.add('open');
                Array.from(ul.querySelectorAll('li')).forEach((li, idx) => li.style.transitionDelay = (idx * 40) + 'ms');
                const btn = ul.previousElementSibling;
                if (btn && btn.classList.contains('cat-toggle')) btn.setAttribute('aria-expanded', 'true');
            });
        }
        function closeAllSubmenus() {
            drawer.querySelectorAll('.drawer-submenu').forEach((ul) => {
                ul.classList.remove('open');
                Array.from(ul.querySelectorAll('li')).forEach(li => li.style.transitionDelay = '');
                const btn = ul.previousElementSibling;
                if (btn && btn.classList.contains('cat-toggle')) btn.setAttribute('aria-expanded', 'false');
            });
        }

        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && isMobile()) closeDrawer(); });

        // Desktop click-to-expand and hover-to-expand: manage explicit vs hover expansion
        let explicitExpanded = false;
        let collapseTimer = null;
        let hoverReady = false;

        setTimeout(function () {
            hoverReady = true;
        }, 350);

        function scheduleDrawerCollapse() {
            clearTimeout(collapseTimer);
            collapseTimer = setTimeout(function () {
                if (isMobile() || explicitExpanded) return;
                drawer.classList.remove('expanded');
                drawer.classList.add('compact');
                closeAllSubmenus();
            }, 600);
        }

        drawer.addEventListener('click', function (e) {
            if (isMobile()) return;
            // only toggle when clicking empty area (not links, toggles, or submenu items)
            if (e.target.closest('a') || e.target.closest('.cat-toggle') || e.target.closest('.drawer-submenu')) return;
            clearTimeout(collapseTimer);
            const expanding = !drawer.classList.contains('expanded');
            explicitExpanded = expanding;
            if (expanding) {
                drawer.classList.add('expanded');
                drawer.classList.remove('compact');
                openAllSubmenus();
            } else {
                drawer.classList.remove('expanded');
                drawer.classList.add('compact');
                closeAllSubmenus();
            }
        });

        // hover behavior: when user moves mouse over drawer on desktop, expand temporarily
        drawer.addEventListener('mouseenter', function () {
            if (isMobile() || !hoverReady) return;
            clearTimeout(collapseTimer);
            if (explicitExpanded) return; // do nothing if user explicitly expanded
            drawer.classList.add('expanded');
            drawer.classList.remove('compact');
            openAllSubmenus();
        });
        drawer.addEventListener('mouseleave', function () {
            if (isMobile() || !hoverReady) return;
            if (explicitExpanded) return; // keep expanded if explicitly toggled
            // Não colapsar automaticamente ao remover o mouse.
            // A ação de colapso ocorrerá apenas quando o usuário clicar fora do drawer.
        });

        // Fecha (colapsa) o drawer apenas quando o usuário clicar fora dele.
        document.addEventListener('click', function (e) {
            if (isMobile()) return;
            if (!drawer.classList.contains('expanded')) return;
            // Se o clique foi dentro do drawer ou no botão hamburger, ignora.
            if (e.target.closest('#sidebar-drawer') || e.target.closest('#hamburger')) return;

            explicitExpanded = false;
            drawer.classList.remove('expanded');
            drawer.classList.add('compact');
            closeAllSubmenus();
        });

        // On load and resize, ensure drawer is compact on desktop and operable on mobile
        function applyResponsive() {
            if (isMobile()) {
                drawer.classList.remove('open');
                drawer.classList.remove('compact');
                drawer.classList.remove('expanded');
                backdrop.classList.remove('open');
                drawer.setAttribute('aria-hidden', 'true');
                hamburger.setAttribute('aria-expanded', 'false');
                hamburger.style.display = '';
                closeAllSubmenus();
            } else {
                // desktop: compact by default, visible, but without forcing the mobile "open" state
                drawer.classList.add('compact');
                drawer.classList.remove('expanded');
                drawer.classList.remove('open');
                drawer.setAttribute('aria-hidden', 'false');
                backdrop.classList.remove('open');
                hamburger.style.display = 'none';
                closeAllSubmenus();
            }
        }
        window.addEventListener('resize', applyResponsive);
        applyResponsive();
    })();

    document.querySelectorAll('.register-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            const target = tab.dataset.registerTab;
            if (!target) return;

            document.querySelectorAll('.register-tab').forEach((btn) => btn.classList.toggle('active', btn === tab));
            document.querySelectorAll('.register-panel').forEach((panel) => {
                panel.classList.toggle('active', panel.dataset.registerPanel === target);
            });
        });
    });

    document.querySelectorAll('[data-register-tab-switch]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const target = btn.dataset.registerTabSwitch;
            if (!target) return;
            const tab = document.querySelector('.register-tab[data-register-tab="' + target + '"]');
            if (tab) tab.click();
        });
    });

    const productListSearch = document.getElementById('product-list-search');
    const productStatusFilter = document.getElementById('product-status-filter');
    if (productListSearch || productStatusFilter) {
        const productRows = Array.from(document.querySelectorAll('[data-product-row]'));
        const emptyState = document.getElementById('product-list-empty');
        const filterProducts = function () {
            const term = (productListSearch ? productListSearch.value : '').trim().toLowerCase();
            const status = productStatusFilter ? productStatusFilter.value : 'all';
            let visible = 0;

            productRows.forEach(function (row) {
                const rowText = (row.dataset.productText || row.textContent || '').toLowerCase();
                const rowStatus = (row.dataset.productStatus || '').toLowerCase();
                const matchesText = !term || rowText.includes(term);
                const matchesStatus = status === 'all' || rowStatus === status;
                const show = matchesText && matchesStatus;
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            if (emptyState) emptyState.style.display = visible === 0 ? 'block' : 'none';
        };

        if (productListSearch) productListSearch.addEventListener('input', filterProducts);
        if (productStatusFilter) productStatusFilter.addEventListener('change', filterProducts);
        filterProducts();
    }

    // Hide duplicate fields in empresa form
    const empresaForm = document.querySelector('form input[name="action"][value="save_empresa"]')?.closest('form');
    if (empresaForm) {
        const inputs = Array.from(empresaForm.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], select'));
        // map name -> [{el,labelText}]
        const map = {};
        inputs.forEach(input => {
            const name = input.name || '';
            const label = input.closest('label');
            const labelText = label ? (label.textContent || '').trim().replace(/:\s*$/, '') : name;
            if (!map[name]) map[name] = [];
            map[name].push({ input, label, labelText });
        });

        // Compare values across different fields (not same name)
        const seen = {};
        inputs.forEach(item => {
            const val = (item.value || '').trim();
            if (!val) return;
            if (!seen[val]) seen[val] = [];
            seen[val].push(item);
        });

        let hiddenCount = 0;
        Object.keys(seen).forEach(val => {
            const group = seen[val];
            if (group.length <= 1) return;
            // keep first, hide the rest
            const keeper = group[0];
            for (let i = 1; i < group.length; i++) {
                const it = group[i];
                if (it.label) {
                    it.label.classList.add('hidden-duplicate');
                    hiddenCount++;
                } else {
                    it.input.style.display = 'none';
                    hiddenCount++;
                }
            }
            // add hint to keeper's label
            if (keeper.label) {
                let hint = keeper.label.querySelector('.duplicate-hint');
                if (!hint) {
                    hint = document.createElement('span');
                    hint.className = 'duplicate-hint';
                    hint.textContent = '(duplicados ocultos)';
                    keeper.label.appendChild(hint);
                }
            }
        });

        if (hiddenCount > 0) {
            const toggle = document.createElement('div');
            toggle.className = 'show-hidden-toggle';
            toggle.textContent = 'Mostrar campos ocultos';
            toggle.addEventListener('click', () => {
                const hidden = empresaForm.querySelectorAll('.hidden-duplicate');
                hidden.forEach(el => el.classList.toggle('hidden-duplicate'));
                toggle.textContent = toggle.textContent === 'Mostrar campos ocultos' ? 'Ocultar campos duplicados' : 'Mostrar campos ocultos';
            });
            empresaForm.appendChild(toggle);
        }
    }
});

// Pedidos: gerenciar itens, busca e totais
(function () {
    const search = document.getElementById('product-search');
    const itemsTable = document.getElementById('items-table');
    const tbody = itemsTable ? itemsTable.querySelector('tbody') : null;
    const btnClear = document.getElementById('btn-clear');
    const totalProdutos = document.getElementById('total-produtos');
    const valorTotal = document.getElementById('valor-total');
    let itemIndex = 0;

    function formatCurrencyBR(v) {
        return 'R$ ' + Number(v).toFixed(2).replace('.', ',');
    }

    function renderNoItems() {
        if (!tbody) return;
        if (tbody.querySelectorAll('tr.item-row').length === 0) {
            tbody.innerHTML = '<tr class="no-items"><td colspan="10">Nenhum registro encontrado</td></tr>';
        }
    }

    function addItem(prod, saved = null) {
        if (!tbody) return;
        // remove placeholder
        const placeholder = tbody.querySelector('tr.no-items');
        if (placeholder) placeholder.remove();

        const row = document.createElement('tr');
        row.className = 'item-row';
        const idx = itemIndex++;
        const subtotal = saved ? Number(saved.net_total) : prod.preco;
        const productTaxes = window.PRODUCT_TAXES || {};
        const defaultTaxes = Array.isArray(productTaxes)
            ? (productTaxes.find(item => String(item.product_id ?? item.id) === String(prod.id)) || {})
            : (productTaxes[String(prod.id)] || {});
        const currentTaxValue = (source, fallback = '') => {
            if (saved && source && Object.prototype.hasOwnProperty.call(saved, source)) return saved[source];
            if (!saved && defaultTaxes && Object.prototype.hasOwnProperty.call(defaultTaxes, source)) return defaultTaxes[source];
            return fallback;
        };

        row.innerHTML = `
            <td>${idx+1}</td>
            <td>${prod.codigo}</td>
            <td>${prod.nome}</td>
            <td>${prod.un || 'UN'}</td>
            <td><input type="number" name="itens[${idx}][quantidade]" value="${saved ? saved.quantity : 1}" min="0.0001" step="0.0001" class="item-qty"></td>
            <td><input type="number" step="0.0001" name="itens[${idx}][preco_unitario]" value="${saved ? saved.unit_price : prod.preco}" class="item-preco"></td>
            <td><input type="number" step="0.01" min="0" name="itens[${idx}][desconto]" value="${saved ? saved.discount_amount : 0}" class="item-discount"></td>
            <td class="item-subtotal">${formatCurrencyBR(subtotal)}</td>
            <td><span class="status-badge">Não avaliado</span></td>
            <td>
                <button type="button" class="link-button btn-edit">Editar</button>
                <button type="button" class="link-button btn-tax">Impostos</button>
                <button type="button" class="link-button btn-remove">Remover</button>
                <input type="hidden" name="itens[${idx}][produto_id]" value="${prod.id}">
                <input type="hidden" name="itens[${idx}][icms]" value="${String(currentTaxValue('icms', '')).replace(/"/g, '&quot;')}">
                <input type="hidden" name="itens[${idx}][ipi]" value="${String(currentTaxValue('ipi', '')).replace(/"/g, '&quot;')}">
                <input type="hidden" name="itens[${idx}][pis]" value="${String(currentTaxValue('pis', '')).replace(/"/g, '&quot;')}">
                <input type="hidden" name="itens[${idx}][cofins]" value="${String(currentTaxValue('cofins', '')).replace(/"/g, '&quot;')}">
            </td>
        `;

        tbody.appendChild(row);

        // attach events
        row.querySelector('.item-qty').addEventListener('input', computeTotals);
        row.querySelector('.item-preco').addEventListener('input', computeTotals);
        row.querySelector('.item-discount').addEventListener('input', computeTotals);
        row.querySelector('.btn-edit').addEventListener('click', function () {
            const productCell = row.querySelector('td:nth-child(3)');
            if (productCell) productCell.click();
        });
        row.querySelector('.btn-tax').addEventListener('click', function () {
            const modal = document.getElementById('product-taxes-modal');
            const body = document.getElementById('modal-body-content');
            const closeButton = document.getElementById('close-modal');
            if (!modal || !body || !closeButton) return;

            const title = (row.querySelector('td:nth-child(3)')?.textContent || 'Item').trim();
            const itemTaxes = {
                icms: row.querySelector('input[name$="[icms]"]').value || '',
                ipi: row.querySelector('input[name$="[ipi]"]').value || '',
                pis: row.querySelector('input[name$="[pis]"]').value || '',
                cofins: row.querySelector('input[name$="[cofins]"]').value || '',
            };

            body.innerHTML = `
                <div style="display:grid;gap:12px;">
                    <p><strong>Produto:</strong> ${String(title).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}</p>
                    <form id="item-tax-form" style="display:grid;gap:12px;">
                        <label>ICMS<input type="text" name="icms" value="${String(itemTaxes.icms).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}" placeholder="Ex.: 18,00"></label>
                        <label>IPI<input type="text" name="ipi" value="${String(itemTaxes.ipi).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}" placeholder="Ex.: 0,00"></label>
                        <label>PIS<input type="text" name="pis" value="${String(itemTaxes.pis).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}" placeholder="Ex.: 0,65"></label>
                        <label>COFINS<input type="text" name="cofins" value="${String(itemTaxes.cofins).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}" placeholder="Ex.: 3,00"></label>
                        <div class="form-actions">
                            <button type="submit" class="btn primary">Salvar imposto</button>
                            <button type="button" class="btn secondary" data-close-item-tax>Fechar</button>
                        </div>
                    </form>
                </div>
            `;

            const form = body.querySelector('#item-tax-form');
            form?.addEventListener('submit', function (event) {
                event.preventDefault();
                const formData = new FormData(form);
                const values = {
                    icms: String(formData.get('icms') || ''),
                    ipi: String(formData.get('ipi') || ''),
                    pis: String(formData.get('pis') || ''),
                    cofins: String(formData.get('cofins') || ''),
                };
                row.querySelector('input[name$="[icms]"]').value = values.icms;
                row.querySelector('input[name$="[ipi]"]').value = values.ipi;
                row.querySelector('input[name$="[pis]"]').value = values.pis;
                row.querySelector('input[name$="[cofins]"]').value = values.cofins;
                row.querySelector('.status-badge').textContent = Object.values(values).some(v => String(v).trim() !== '') ? 'Tributos OK' : 'Não avaliado';
                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
            });
            body.querySelector('[data-close-item-tax]')?.addEventListener('click', function () {
                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
            });

            closeButton.onclick = function () {
                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
            };
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
        });
        row.querySelector('.btn-remove').addEventListener('click', function () { row.remove(); computeTotals(); renderNoItems(); });

        computeTotals();
    }

    function findProduct(term) {
        if (!window.PRODUCTS) return null;
        term = (term || '').toString().toLowerCase().trim();
        if (!term) return null;
        // try exact code
        let p = window.PRODUCTS.find(x => x.codigo && x.codigo.toLowerCase() === term);
        if (p) return p;
        // try id
        p = window.PRODUCTS.find(x => String(x.id) === term);
        if (p) return p;
        // substring name
        p = window.PRODUCTS.find(x => x.nome && x.nome.toLowerCase().includes(term));
        return p || null;
    }

    // Autocomplete: cria dropdown de sugestões sob o campo de busca
    function createAutocomplete() {
        const searchInput = document.getElementById('product-search');
        if (!searchInput) return;
        let box = document.createElement('div');
        box.className = 'autocomplete-box';
        box.style.position = 'absolute';
        box.style.zIndex = 999;
        box.style.background = '#fff';
        box.style.border = '1px solid var(--border)';
        box.style.borderRadius = '6px';
        box.style.boxShadow = '0 6px 18px rgba(21,41,81,0.08)';
        box.style.maxHeight = '300px';
        box.style.overflow = 'auto';
        box.style.display = 'none';
        document.body.appendChild(box);

        let selected = -1;
        let results = [];

        function positionBox() {
            const rect = searchInput.getBoundingClientRect();
            box.style.left = (rect.left + window.scrollX) + 'px';
            box.style.top = (rect.bottom + window.scrollY + 6) + 'px';
            box.style.minWidth = rect.width + 'px';
        }

        function render(items) {
            results = items;
            selected = -1;
            if (!items.length) {
                box.style.display = 'none';
                return;
            }
            box.innerHTML = items.map((p, i) => `<div class="ac-item" data-idx="${i}" style="padding:8px;cursor:pointer;border-bottom:1px solid rgba(0,0,0,0.04);">` +
                `<div style="font-weight:600">${escapeHtml(p.nome)} <small style="color:#666">${escapeHtml(p.codigo || '')}</small></div>` +
                `<div style="font-size:0.9rem;color:#666">R$ ${Number(p.preco).toFixed(2)}</div>` +
                `</div>`).join('');
            box.style.display = 'block';
            positionBox();
        }

        function escapeHtml(s) { return String(s).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }

        function filter(term) {
            term = (term || '').toString().toLowerCase().trim();
            if (!term) return [];
            const list = window.PRODUCTS || [];
            const byCode = list.filter(p => p.codigo && p.codigo.toLowerCase().includes(term));
            const byName = list.filter(p => p.nome && p.nome.toLowerCase().includes(term));
            const merged = [];
            const seen = new Set();
            byCode.concat(byName).forEach(p => {
                if (!seen.has(p.id)) { seen.add(p.id); merged.push(p); }
            });
            return merged.slice(0, 12);
        }

        let debounceTimer = null;
        searchInput.addEventListener('input', function (e) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const items = filter(searchInput.value);
                render(items);
            }, 120);
        });

        document.addEventListener('click', function (ev) {
            if (!box.contains(ev.target) && ev.target !== searchInput) box.style.display = 'none';
        });

        box.addEventListener('click', function (ev) {
            const it = ev.target.closest('.ac-item');
            if (!it) return;
            const idx = Number(it.getAttribute('data-idx'));
            const p = results[idx];
            if (p) { addItem(p); searchInput.value = ''; box.style.display = 'none'; }
        });

        searchInput.addEventListener('keydown', function (ev) {
            if (box.style.display === 'none') return;
            const itemsEls = box.querySelectorAll('.ac-item');
            if (ev.key === 'ArrowDown') { ev.preventDefault(); selected = Math.min(selected + 1, itemsEls.length - 1); highlight(); }
            if (ev.key === 'ArrowUp') { ev.preventDefault(); selected = Math.max(selected - 1, 0); highlight(); }
            if (ev.key === 'Enter') { ev.preventDefault(); if (selected >= 0) { const p = results[selected]; if (p) { addItem(p); searchInput.value = ''; box.style.display = 'none'; } } }
            if (ev.key === 'Escape') { box.style.display = 'none'; }

            function highlight() {
                itemsEls.forEach((el, i) => el.style.background = i === selected ? 'rgba(0,0,0,0.04)' : 'transparent');
                if (selected >= 0 && itemsEls[selected]) itemsEls[selected].scrollIntoView({block:'nearest'});
            }
        });

        window.addEventListener('resize', positionBox);
    }

    // Inicializa autocomplete quando houver campo de busca
    createAutocomplete();
    (window.ORDER_ITEMS || []).forEach(saved => addItem({id:saved.id,codigo:saved.codigo,nome:saved.nome,un:saved.unidade,preco:saved.unit_price}, saved));

    function computeTotals() {
        if (!tbody) return;
        let total = 0;
        tbody.querySelectorAll('tr.item-row').forEach(row => {
            const qty = Number(row.querySelector('.item-qty').value) || 0;
            const price = Number(row.querySelector('.item-preco').value) || 0;
            const discount = Number(row.querySelector('.item-discount')?.value) || 0;
            const subtotal = Math.max(0, qty * price - discount);
            row.querySelector('.item-subtotal').textContent = formatCurrencyBR(subtotal);
            total += subtotal;
        });
        if (totalProdutos) totalProdutos.textContent = formatCurrencyBR(total);
        if (valorTotal) valorTotal.textContent = formatCurrencyBR(total);
    }

    if (search) {
        search.addEventListener('keydown', function (e) {
            if (e.key === 'F8') { e.preventDefault(); /* focus already */ }
            if (e.key === 'Enter') {
                e.preventDefault();
                const p = findProduct(search.value);
                if (p) addItem(p);
                search.value = '';
            }
        });
    }

    if (btnClear) btnClear.addEventListener('click', function () {
        if (!tbody) return;
        tbody.querySelectorAll('tr.item-row').forEach(r => r.remove());
        renderNoItems();
        computeTotals();
    });

    const testFillButton = document.querySelector('[data-order-test-fill]');
    if (testFillButton) testFillButton.addEventListener('click', function () {
        const form = document.getElementById('pedido-form');
        if (!form) return;
        const setValue = (name, value) => {
            const field = form.elements.namedItem(name);
            if (!field) return;
            field.value = value;
            field.dispatchEvent(new Event('change', { bubbles: true }));
            field.dispatchEvent(new Event('input', { bubbles: true }));
        };
        const selectFirst = name => {
            const field = form.elements.namedItem(name);
            if (!(field instanceof HTMLSelectElement)) return false;
            const option = [...field.options].find(item => item.value !== '' && !item.disabled);
            if (!option) return false;
            setValue(name, option.value);
            return true;
        };

        btnClear?.click();
        const direction = String(form.elements.namedItem('tipo')?.value || 'saida');
        selectFirst(direction === 'entrada' ? 'fornecedor_id' : 'cliente_id');
        selectFirst('cfop_id');
        setValue('fiscal_model', '55');
        setValue('purpose', 'NORMAL');
        setValue('presence_indicator', '1');
        setValue('codigo_interno', `TESTE-${new Date().toISOString().slice(0, 19).replace(/[-:T]/g, '')}`);
        setValue('condicao_pagamento', 'avista');
        setValue('documento', 'PIX');
        setValue('vencimento', new Date().toISOString().slice(0, 10));
        setValue('frete', '0');
        setValue('desconto_percent', '0');
        setValue('desconto_valor', '0');
        setValue('observacoes', 'PREENCHIMENTO DE TESTE — revisar antes de gravar ou preparar nota.');
        setValue('freight_mode', '9');

        const product = (window.PRODUCTS || []).find(item => item.status !== 'inativo' && Number(item.preco) > 0)
            || (window.PRODUCTS || []).find(item => item.status !== 'inativo');
        if (product && search) {
            search.value = String(product.codigo || product.id);
            search.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true }));
        }
        testFillButton.blur();
        window.AppToast?.show('Dados de teste preenchidos. Revise antes de gravar.', 'success', 4500);
    });

    // global keys
    document.addEventListener('keydown', function (e) {
        if (e.key === 'F8') { e.preventDefault(); const s = document.getElementById('product-search'); if (s) s.focus(); }
        if (e.key === 'F3') { e.preventDefault(); const ev = new Event('click'); if (btnClear) btnClear.dispatchEvent(ev); }
    });

    // on submit, ensure there is at least one item-row
    const pedidoForm = document.getElementById('pedido-form');
    if (pedidoForm) {
        pedidoForm.addEventListener('submit', function (e) {
            const rows = tbody ? tbody.querySelectorAll('tr.item-row') : [];
            if (rows.length === 0) {
                e.preventDefault();
                alert('Adicione ao menos um produto ao pedido.');
                return false;
            }
            // ensure item indices are sequential
            let i = 0;
            rows.forEach(row => {
                row.querySelectorAll('input').forEach(inp => {
                    if (inp.name) {
                        const newName = inp.name.replace(/itens\[\d+\]/, 'itens['+i+']');
                        inp.name = newName;
                    }
                });
                i++;
            });
        });
    }

})();

// Gráficos responsivos do Dashboard com escala linear real e tooltips em pt-BR.
(() => {
    if (typeof window.Chart === 'undefined') return;
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const money = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
    const rootStyle = getComputedStyle(document.documentElement);
    const textColor = rootStyle.getPropertyValue('--text').trim() || '#172033';
    const mutedColor = rootStyle.getPropertyValue('--muted').trim() || '#667085';
    const borderColor = rootStyle.getPropertyValue('--border').trim() || '#dce3ef';

    document.querySelectorAll('canvas.dashboard-chart[data-chart]').forEach(canvas => {
        let payload;
        try { payload = JSON.parse(canvas.dataset.chart || '{}'); } catch (_) { return; }
        const isMoney = payload.type === 'money';
        const context = canvas.getContext('2d');
        const gradient = context.createLinearGradient(0, 0, 0, 360);
        gradient.addColorStop(0, isMoney ? '#3b82f6' : '#22c98a');
        gradient.addColorStop(1, isMoney ? '#2457d6' : '#12845d');

        new window.Chart(context, {
            type: 'bar',
            data: {
                labels: payload.labels || [],
                datasets: [{
                    label: payload.label || '',
                    data: payload.values || [],
                    backgroundColor: gradient,
                    borderColor: isMoney ? '#2457d6' : '#12845d',
                    borderWidth: 1,
                    borderRadius: 7,
                    borderSkipped: false,
                    maxBarThickness: 42
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: reduced ? false : { duration: 380, easing: 'easeOutQuart' },
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        displayColors: false,
                        padding: 12,
                        callbacks: {
                            title: items => items[0]?.label || '',
                            label: item => isMoney ? `Faturamento: ${money.format(item.raw || 0)}` : `Quantidade: ${item.raw || 0}`,
                            afterLabel: item => isMoney && Array.isArray(payload.counts) ? `Vendas: ${payload.counts[item.dataIndex] || 0}` : ''
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: mutedColor, maxRotation: 0, autoSkip: true, maxTicksLimit: 12 } },
                    y: {
                        beginAtZero: true,
                        grace: '12%',
                        grid: { color: borderColor },
                        ticks: { color: mutedColor, precision: 0, callback: value => isMoney ? money.format(value) : value }
                    }
                }
            }
        });
        canvas.style.color = textColor;
    });
})();

// A Central de Notas reutiliza o preview seguro do pedido e mantém a Central aberta.
document.querySelectorAll('.notes-table tbody tr').forEach(function (row) {
    const orderText = row.querySelector('td small')?.textContent || '';
    const orderMatch = orderText.match(/#(\d+)/);
    const actions = row.querySelector('.row-actions');
    if (!orderMatch || !actions || actions.querySelector('[data-danfe-preview]')) return;
    const model = row.querySelector('[data-label="Modelo"]')?.textContent.trim();
    if (model !== '55' && model !== '65') return;
    const link = document.createElement('a');
    link.className = 'btn small';
    link.href = '/fiscal_danfe_preview.php?order_id=' + encodeURIComponent(orderMatch[1]);
    link.dataset.danfePreview = '';
    link.textContent = model === '65' ? 'Prévia DANFC-e' : 'Prévia DANFE';
    actions.querySelector('details')?.before(link);
});

(function () {
    document.querySelectorAll('.switch-control').forEach(function (control) {
        const input = control.querySelector('.switch-input');
        const state = control.querySelector('.switch-state');
        if (!input || !state) return;
        const sync = function () {
            const checked = input.checked;
            control.setAttribute('aria-checked', checked ? 'true' : 'false');
            state.textContent = checked ? 'Sim' : 'Não';
        };
        input.addEventListener('change', sync);
        control.addEventListener('keydown', function (event) {
            if (event.key === ' ' || event.key === 'Enter') {
                event.preventDefault();
                input.checked = !input.checked;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        sync();
    });

    document.querySelectorAll('.order-collapse-toggle').forEach(function (button) {
        const card = button.closest('.logistics-card');
        if (!card) return;
        const label = button.firstElementChild;
        const icon = button.lastElementChild;
        const sync = function () {
            const expanded = button.getAttribute('aria-expanded') === 'true';
            card.classList.toggle('is-collapsed', !expanded);
            if (label) label.textContent = expanded ? 'Recolher' : 'Expandir';
            if (icon) icon.textContent = expanded ? '↑' : '↓';
        };
        button.addEventListener('click', function () {
            const next = button.getAttribute('aria-expanded') !== 'true';
            button.setAttribute('aria-expanded', String(next));
            sync();
        });
        sync();
    });
})();

// Mostrar modal de impostos quando clicar no nome do produto na tabela de itens
document.addEventListener('click', function (e) {
    const previewLink = e.target.closest && e.target.closest('[data-danfe-preview]');
    if (previewLink) {
        e.preventDefault();
        const previewWindow = window.open('', '_blank');
        if (!previewWindow) {
            alert('O navegador bloqueou a nova guia. Permita pop-ups para abrir a prévia fiscal.');
            return;
        }
        previewWindow.document.write('<!doctype html><html lang="pt-BR"><meta charset="utf-8"><title>Gerando prévia DANFE</title><body style="font:16px system-ui;padding:32px"><p>Gerando prévia DANFE...</p></body></html>');
        previewWindow.location.href = previewLink.href;
        return;
    }

    const descriptionCell = e.target && e.target.closest ? e.target.closest('td') : null;
    if (!descriptionCell) return;

    const itemRow = descriptionCell.closest && descriptionCell.closest('tr.item-row');
    if (!itemRow) return;

    if (e.target && e.target.closest && e.target.closest('.btn-tax')) return;

    const productCell = itemRow.querySelector('td:nth-child(3)');
    if (!productCell || (descriptionCell !== productCell && !productCell.contains(descriptionCell))) return;

    const prodIdInput = itemRow.querySelector('input[type="hidden"][name$="[produto_id]"]');
    if (!prodIdInput) return;

    const pid = prodIdInput.value;
    const taxesMap = window.PRODUCT_TAXES || {};
    const taxes = Array.isArray(taxesMap)
        ? taxesMap.find(item => String(item.product_id ?? item.id) === String(pid)) || null
        : (taxesMap[pid] ?? null);

    const modal = document.getElementById('product-taxes-modal');
    const body = document.getElementById('modal-body-content');
    const closeButton = document.getElementById('close-modal');
    if (!modal || !body || !closeButton) return;

    if (!taxes) {
        body.innerHTML = '<p>Sem informações de impostos para este produto.</p>';
    } else {
        body.innerHTML = '<ul>' +
            '<li><strong>IPI:</strong> ' + (taxes.ipi || '-') + '</li>' +
            '<li><strong>ICMS:</strong> ' + (taxes.icms || '-') + '</li>' +
            '<li><strong>PIS:</strong> ' + (taxes.pis || '-') + '</li>' +
            '<li><strong>COFINS:</strong> ' + (taxes.cofins || '-') + '</li>' +
            '</ul>';
    }

    closeButton.onclick = function () {
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
    };
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
});
