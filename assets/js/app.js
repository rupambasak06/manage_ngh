/**
 * Hotel Room Daily Checklist & Inventory System
 * Vanilla JS: Instant AJAX autosave, 10s live refresh polling, midnight rollover,
 * dark mode toggle, floor filtering, and mobile UX helpers.
 */

(function () {
    'use strict';

    const BN_DIGITS = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
    function toBn(val) {
        return String(val).replace(/\d/g, (d) => BN_DIGITS[Number(d)] || d);
    }

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // =========================================================================
    // Dark Mode Toggle (persisted in localStorage)
    // =========================================================================
    function applyThemeIcons() {
        const isDark = document.documentElement.classList.contains('dark');
        document.querySelectorAll('[data-theme-icon="sun"]').forEach((el) => el.classList.toggle('hidden', !isDark));
        document.querySelectorAll('[data-theme-icon="moon"]').forEach((el) => el.classList.toggle('hidden', isDark));
    }

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-toggle-theme]');
        if (!btn) return;
        const isDark = document.documentElement.classList.toggle('dark');
        try {
            localStorage.setItem('hcl_theme', isDark ? 'dark' : 'light');
        } catch (_) {}
        applyThemeIcons();
    });
    applyThemeIcons();

    // =========================================================================
    // Toast Notification
    // =========================================================================
    function showToast(message, type) {
        const container = document.getElementById('toast-container');
        if (!container) return;
        const el = document.createElement('div');
        const bg = type === 'error'
            ? 'bg-rose-600 text-white'
            : (type === 'info' ? 'bg-slate-800 text-white dark:bg-slate-700' : 'bg-emerald-600 text-white');
        el.className = `toast-item px-4 py-3 rounded-xl shadow-lg text-sm font-medium flex items-center gap-2 ${bg}`;
        el.textContent = message;
        container.appendChild(el);
        setTimeout(() => {
            el.style.opacity = '0';
            el.style.transition = 'opacity 200ms ease';
            setTimeout(() => el.remove(), 220);
        }, 3200);
    }

    // =========================================================================
    // Confirm Delete / Action Forms
    // =========================================================================
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (form && form.dataset && form.dataset.confirm) {
            if (!window.confirm(form.dataset.confirm)) {
                e.preventDefault();
            }
        }
    });

    // =========================================================================
    // Daily Checklist Interactive Controller
    // =========================================================================
    const root = document.getElementById('checklist-app');
    if (!root) return;

    const csrfToken    = root.dataset.csrf || '';
    const currentDate  = root.dataset.date || '';
    const todayDate    = root.dataset.today || '';
    const apiBase      = root.dataset.apiBase || '';
    const pollInterval = parseInt(root.dataset.pollInterval || '10000', 10);
    let lastSyncAt     = root.dataset.lastSync || '';
    let activeRequests = 0;
    const noteTimers   = new Map();

    const saveStatusEl = document.getElementById('save-status');
    const saveTextEl   = document.getElementById('save-status-text');
    const saveDotEl    = document.getElementById('save-status-dot');

    function setSaveState(state, customMsg) {
        if (!saveStatusEl || !saveTextEl || !saveDotEl) return;
        saveDotEl.className = 'w-2 h-2 rounded-full inline-block';
        if (state === 'saving') {
            saveDotEl.classList.add('bg-amber-400', 'animate-ping');
            saveTextEl.textContent = customMsg || 'সেভ হচ্ছে...';
            saveStatusEl.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200 border border-amber-200 dark:border-amber-800';
        } else if (state === 'error') {
            saveDotEl.classList.add('bg-rose-500');
            saveTextEl.textContent = customMsg || 'সেভ ব্যর্থ!';
            saveStatusEl.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-rose-50 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200 border border-rose-200 dark:border-rose-800';
        } else {
            saveDotEl.classList.add('bg-emerald-500', 'sync-dot-live');
            saveTextEl.textContent = customMsg || 'সংরক্ষিত';
            saveStatusEl.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200 border border-emerald-200 dark:border-emerald-800';
        }
    }

    async function postJson(route, payload) {
        activeRequests++;
        setSaveState('saving');
        try {
            const resp = await fetch(`${apiBase}?route=${encodeURIComponent(route)}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });
            const data = await resp.json();
            if (resp.status === 401 && data.auth_required) {
                window.location.href = `${apiBase}?route=login`;
                return null;
            }
            if (!resp.ok || !data.ok) {
                throw new Error(data.error || 'সেভ করতে সমস্যা হয়েছে!');
            }
            if (data.board) {
                lastSyncAt = data.board.last_sync_at || lastSyncAt;
                applyBoardState(data.board);
            }
            activeRequests = Math.max(0, activeRequests - 1);
            if (activeRequests === 0) {
                setSaveState('saved');
            }
            return data;
        } catch (err) {
            activeRequests = Math.max(0, activeRequests - 1);
            setSaveState('error');
            showToast(err.message || 'সেভ করতে সমস্যা হয়েছে! ইন্টারনেট চেক করুন।', 'error');
            return null;
        }
    }

    // =========================================================================
    // Click Handlers: Option Toggle, Option Counter, Extra Counter
    // =========================================================================
    root.addEventListener('click', (e) => {
        // 1. Checkbox option chip toggle
        const chip = e.target.closest('[data-opt-toggle]');
        if (chip) {
            const roomId   = parseInt(chip.dataset.roomId, 10);
            const optionId = parseInt(chip.dataset.optionId, 10);
            const curQty   = parseInt(chip.dataset.qty || '0', 10);
            const nextQty  = curQty > 0 ? 0 : 1;

            // Optimistic UI update
            updateOptionChipUI(chip, nextQty, parseInt(chip.dataset.expected || '1', 10));
            postJson('api/option', { date: currentDate, room_id: roomId, option_id: optionId, qty: nextQty });
            return;
        }

        // 2. Counter option − / +
        const optStep = e.target.closest('[data-opt-step]');
        if (optStep) {
            const box      = optStep.closest('[data-opt-counter]');
            if (!box) return;
            const roomId   = parseInt(box.dataset.roomId, 10);
            const optionId = parseInt(box.dataset.optionId, 10);
            const maxVal   = parseInt(box.dataset.max || '10', 10);
            const expected = parseInt(box.dataset.expected || '1', 10);
            const curQty   = parseInt(box.dataset.qty || '0', 10);
            const delta    = parseInt(optStep.dataset.optStep, 10);
            const nextQty  = Math.max(0, Math.min(maxVal, curQty + delta));
            if (nextQty === curQty) return;

            updateOptionCounterUI(box, nextQty, expected);
            postJson('api/option', { date: currentDate, room_id: roomId, option_id: optionId, qty: nextQty });
            return;
        }

        // 3. Extra counter − / +
        const exStep = e.target.closest('[data-extra-step]');
        if (exStep) {
            const box     = exStep.closest('[data-extra-counter]');
            if (!box) return;
            const roomId  = parseInt(box.dataset.roomId, 10);
            const extraId = parseInt(box.dataset.extraId, 10);
            const maxVal  = parseInt(box.dataset.max || '20', 10);
            const curQty  = parseInt(box.dataset.qty || '0', 10);
            const delta   = parseInt(exStep.dataset.extraStep, 10);
            const nextQty = Math.max(0, Math.min(maxVal, curQty + delta));
            if (nextQty === curQty) return;

            updateExtraCounterUI(box, nextQty);
            postJson('api/extra', { date: currentDate, room_id: roomId, extra_id: extraId, qty: nextQty });
            return;
        }

        // 4. Floor filter pill
        const floorBtn = e.target.closest('[data-floor-filter]');
        if (floorBtn) {
            const target = floorBtn.dataset.floorFilter;
            document.querySelectorAll('[data-floor-filter]').forEach((b) => {
                const active = b.dataset.floorFilter === target;
                b.classList.toggle('bg-indigo-600', active);
                b.classList.toggle('text-white', active);
                b.classList.toggle('border-indigo-600', active);
                b.classList.toggle('bg-white', !active);
                b.classList.toggle('dark:bg-slate-800', !active);
                b.classList.toggle('text-slate-700', !active);
                b.classList.toggle('dark:text-slate-200', !active);
            });
            document.querySelectorAll('[data-floor-section]').forEach((sec) => {
                const show = (target === 'all' || sec.dataset.floorSection === target);
                sec.classList.toggle('hidden', !show);
            });
            return;
        }
    });

    // Debounced room notes autosave
    root.addEventListener('input', (e) => {
        const ta = e.target.closest('[data-room-note]');
        if (!ta) return;
        const roomId = parseInt(ta.dataset.roomId, 10);
        if (noteTimers.has(roomId)) {
            clearTimeout(noteTimers.get(roomId));
        }
        setSaveState('saving');
        noteTimers.set(roomId, setTimeout(() => {
            noteTimers.delete(roomId);
            postJson('api/note', { date: currentDate, room_id: roomId, note: ta.value });
        }, 650));
    });

    // Save note immediately on blur if pending
    root.addEventListener('blur', (e) => {
        const ta = e.target.closest('[data-room-note]');
        if (!ta) return;
        const roomId = parseInt(ta.dataset.roomId, 10);
        if (noteTimers.has(roomId)) {
            clearTimeout(noteTimers.get(roomId));
            noteTimers.delete(roomId);
            postJson('api/note', { date: currentDate, room_id: roomId, note: ta.value });
        }
    }, true);

    // Toggle totals strip collapse on mobile
    const totalsToggle = document.getElementById('toggle-totals-btn');
    const totalsBody   = document.getElementById('totals-strip-body');
    if (totalsToggle && totalsBody) {
        totalsToggle.addEventListener('click', () => {
            totalsBody.classList.toggle('hidden');
            const chevron = totalsToggle.querySelector('[data-chevron]');
            if (chevron) chevron.classList.toggle('rotate-180');
        });
    }

    // =========================================================================
    // UI State Updaters (Surgical DOM Sync — No Full Page Reload)
    // =========================================================================
    function updateOptionChipUI(chip, qty, expected) {
        chip.dataset.qty = String(qty);
        const isDone = qty >= expected;
        const checkIcon = chip.querySelector('[data-chip-check]');
        if (checkIcon) {
            checkIcon.textContent = isDone ? '✓' : '';
        }
        chip.classList.remove(
            'bg-emerald-600', 'text-white', 'border-emerald-600', 'shadow-sm',
            'bg-slate-50', 'dark:bg-slate-800/80', 'text-slate-700', 'dark:text-slate-200', 'border-slate-200', 'dark:border-slate-700'
        );
        if (isDone) {
            chip.classList.add('bg-emerald-600', 'text-white', 'border-emerald-600', 'shadow-sm');
        } else {
            chip.classList.add('bg-slate-50', 'dark:bg-slate-800/80', 'text-slate-700', 'dark:text-slate-200', 'border-slate-200', 'dark:border-slate-700');
        }
    }

    function updateOptionCounterUI(box, qty, expected) {
        box.dataset.qty = String(qty);
        const valEl = box.querySelector('[data-counter-val]');
        if (valEl) valEl.textContent = toBn(qty);

        const isDone     = qty >= expected;
        const isMismatch = qty > 0 && qty !== expected;

        box.classList.remove(
            'bg-emerald-50', 'dark:bg-emerald-950/40', 'border-emerald-400', 'dark:border-emerald-700',
            'bg-amber-50', 'dark:bg-amber-950/40', 'border-amber-400', 'dark:border-amber-600',
            'bg-slate-50', 'dark:bg-slate-800/70', 'border-slate-200', 'dark:border-slate-700'
        );

        if (isMismatch) {
            box.classList.add('bg-amber-50', 'dark:bg-amber-950/40', 'border-amber-400', 'dark:border-amber-600');
        } else if (isDone) {
            box.classList.add('bg-emerald-50', 'dark:bg-emerald-950/40', 'border-emerald-400', 'dark:border-emerald-700');
        } else {
            box.classList.add('bg-slate-50', 'dark:bg-slate-800/70', 'border-slate-200', 'dark:border-slate-700');
        }

        const warnEl = box.querySelector('[data-mismatch-badge]');
        if (warnEl) {
            warnEl.classList.toggle('hidden', !isMismatch);
        }
    }

    function updateExtraCounterUI(box, qty) {
        box.dataset.qty = String(qty);
        const valEl = box.querySelector('[data-extra-val]');
        if (valEl) valEl.textContent = toBn(qty);

        box.classList.remove(
            'bg-indigo-50', 'dark:bg-indigo-950/40', 'border-indigo-300', 'dark:border-indigo-700',
            'bg-slate-50', 'dark:bg-slate-800/70', 'border-slate-200', 'dark:border-slate-700'
        );
        if (qty > 0) {
            box.classList.add('bg-indigo-50', 'dark:bg-indigo-950/40', 'border-indigo-300', 'dark:border-indigo-700');
        } else {
            box.classList.add('bg-slate-50', 'dark:bg-slate-800/70', 'border-slate-200', 'dark:border-slate-700');
        }
    }

    function applyBoardState(board) {
        // 1. Update stats
        const statChecked = document.getElementById('stat-checked');
        const statCheckout = document.getElementById('stat-checkout');
        if (statChecked && board.stats) {
            statChecked.textContent = `${toBn(board.stats.checked_count)}/${toBn(board.stats.total_rooms)}`;
        }
        if (statCheckout && board.stats) {
            statCheckout.textContent = `${toBn(board.stats.checkout_complete_count)}/${toBn(board.stats.total_rooms)}`;
        }

        // 2. Update top option totals strip
        if (Array.isArray(board.option_totals)) {
            board.option_totals.forEach((ot) => {
                const el = document.querySelector(`[data-option-total="${ot.id}"]`);
                if (el) el.textContent = toBn(ot.total);
            });
        }

        // 3. Update every room card
        if (Array.isArray(board.floors)) {
            board.floors.forEach((fl) => {
                fl.rooms.forEach((rm) => {
                    const card = document.querySelector(`[data-room-card="${rm.id}"]`);
                    if (!card) return;

                    // Checkout badge (auto, non-clickable)
                    const badge = card.querySelector('[data-checkout-badge]');
                    if (badge) {
                        badge.className = rm.checkout_complete
                            ? 'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-200 border border-emerald-300 dark:border-emerald-700 select-none'
                            : 'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700 select-none';
                        badge.innerHTML = rm.checkout_complete
                            ? '<span>✓</span> <span>চেকআউট সম্পূর্ণ</span>'
                            : `<span>○</span> <span>চেকআউট বাকি (${toBn(rm.checkout_req_done)}/${toBn(rm.checkout_req_total)})</span>`;
                    }

                    // Card border accent
                    card.classList.toggle('border-emerald-400', rm.checkout_complete);
                    card.classList.toggle('dark:border-emerald-700', rm.checkout_complete);

                    // Options
                    rm.sections.forEach((sec) => {
                        sec.options.forEach((opt) => {
                            if (opt.type === 'checkbox') {
                                const chip = card.querySelector(`[data-opt-toggle][data-option-id="${opt.id}"]`);
                                if (chip) updateOptionChipUI(chip, opt.actual_qty, opt.expected_qty);
                            } else {
                                const box = card.querySelector(`[data-opt-counter][data-option-id="${opt.id}"]`);
                                if (box) updateOptionCounterUI(box, opt.actual_qty, opt.expected_qty);
                            }
                        });
                    });

                    // Extras
                    rm.extras.forEach((ex) => {
                        const box = card.querySelector(`[data-extra-counter][data-extra-id="${ex.id}"]`);
                        if (box) updateExtraCounterUI(box, ex.qty);
                    });

                    // Note (only update if user is NOT actively typing in this textarea)
                    const ta = card.querySelector('[data-room-note]');
                    if (ta && document.activeElement !== ta && !noteTimers.has(rm.id)) {
                        ta.value = rm.note || '';
                    }

                    // Last updated by
                    const meta = card.querySelector('[data-room-updated]');
                    if (meta) {
                        meta.textContent = rm.updated_by_name ? `আপডেট: ${rm.updated_by_name}` : '';
                    }
                });
            });
        }

        // 4. Re-render bottom summary block
        if (board.summary) {
            renderSummary(board.summary);
        }
    }

    function renderSummary(summary) {
        // 1. Extras summary
        const extrasEl = document.getElementById('summary-extras-body');
        if (extrasEl) {
            let html = '';
            if (summary.extras_by_room.length === 0) {
                html += '<p class="text-sm text-slate-500 dark:text-slate-400">কোনো এক্সট্রা সামগ্রী দেওয়া হয়নি।</p>';
            } else {
                html += '<div class="space-y-2 mb-4">';
                summary.extras_by_room.forEach((r) => {
                    const badges = r.items.map((it) =>
                        `<span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200">${escapeHtml(it.label)}: ${toBn(it.qty)}</span>`
                    ).join(' ');
                    html += `<div class="flex flex-wrap items-center gap-2 text-sm py-1.5 border-b border-slate-100 dark:border-slate-800 last:border-0">
                        <span class="font-semibold text-slate-800 dark:text-slate-100 min-w-[70px]">রুম ${escapeHtml(r.room_number)}</span>
                        <div class="flex flex-wrap gap-1.5">${badges}</div>
                    </div>`;
                });
                html += '</div>';
            }
            const nonZeroTotals = summary.extras_totals.filter((x) => x.total > 0);
            if (nonZeroTotals.length > 0) {
                html += '<div class="pt-3 border-t border-slate-200 dark:border-slate-700"><div class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-2">সর্বমোট এক্সট্রা</div><div class="flex flex-wrap gap-2">';
                nonZeroTotals.forEach((t) => {
                    html += `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-100">${escapeHtml(t.label)}: <span class="text-indigo-600 dark:text-indigo-400">${toBn(t.total)}</span></span>`;
                });
                html += '</div></div>';
            }
            extrasEl.innerHTML = html;
        }

        // 2. Missing items + Unchecked rooms
        const missingEl = document.getElementById('summary-missing-body');
        if (missingEl) {
            let html = '';
            if (summary.missing_by_room.length === 0) {
                html += '<p class="text-sm text-emerald-700 dark:text-emerald-400 mb-3">চেক করা রুমগুলোতে কোনো আইটেম কম নেই।</p>';
            } else {
                html += '<div class="space-y-2 mb-4">';
                summary.missing_by_room.forEach((r) => {
                    const tags = r.items.map((it) => {
                        const txt = it.type === 'counter'
                            ? `${escapeHtml(it.label)} (${toBn(it.missing_qty)} কম)`
                            : `${escapeHtml(it.label)} (বাকি)`;
                        return `<span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-amber-50 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200 border border-amber-200 dark:border-amber-800">${txt}</span>`;
                    }).join(' ');
                    html += `<div class="flex flex-wrap items-center gap-2 text-sm py-1.5 border-b border-slate-100 dark:border-slate-800 last:border-0">
                        <span class="font-semibold text-slate-800 dark:text-slate-100 min-w-[70px]">রুম ${escapeHtml(r.room_number)}</span>
                        <div class="flex flex-wrap gap-1.5">${tags}</div>
                    </div>`;
                });
                html += '</div>';
            }

            html += '<div class="pt-3 border-t border-slate-200 dark:border-slate-700">';
            html += `<div class="text-xs font-semibold text-slate-500 dark:text-slate-400 mb-2">এখনও চেক করা হয়নি (${toBn(summary.unchecked_rooms.length)})</div>`;
            if (summary.unchecked_rooms.length === 0) {
                html += '<p class="text-sm text-emerald-700 dark:text-emerald-400">সব রুম চেক করা হয়েছে!</p>';
            } else {
                html += '<div class="flex flex-wrap gap-1.5">';
                summary.unchecked_rooms.forEach((u) => {
                    html += `<span class="px-2 py-0.5 rounded-md text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">${escapeHtml(u.room_number)}</span>`;
                });
                html += '</div>';
            }
            html += '</div>';
            missingEl.innerHTML = html;
        }

        // 3. Serial Notes
        const notesEl = document.getElementById('summary-notes-body');
        if (notesEl) {
            if (summary.notes_list.length === 0) {
                notesEl.innerHTML = '<p class="text-sm text-slate-500 dark:text-slate-400">কোনো মন্তব্য নেই।</p>';
            } else {
                let html = '<ol class="space-y-2 text-sm">';
                summary.notes_list.forEach((n, idx) => {
                    html += `<li class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                        <span class="font-bold text-indigo-600 dark:text-indigo-400">${toBn(idx + 1)}.</span>
                        <span class="font-semibold text-slate-800 dark:text-slate-100">রুম ${escapeHtml(n.room_number)}:</span>
                        <span class="text-slate-700 dark:text-slate-200">${escapeHtml(n.note)}</span>
                    </li>`;
                });
                html += '</ol>';
                notesEl.innerHTML = html;
            }
        }
    }

    // =========================================================================
    // Live Refresh Polling (every 10 seconds) + Midnight Auto-Rollover
    // =========================================================================
    async function pollServer() {
        if (document.hidden || activeRequests > 0) return;
        try {
            const url = `${apiBase}?route=api/poll&date=${encodeURIComponent(currentDate)}&since=${encodeURIComponent(lastSyncAt)}`;
            const resp = await fetch(url, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            if (resp.status === 401) {
                window.location.href = `${apiBase}?route=login`;
                return;
            }
            const data = await resp.json();
            if (!data || !data.ok) return;

            // Midnight auto-rollover: if user was viewing "today" and server crossed midnight into new day
            if (currentDate === todayDate && data.server_today && data.server_today !== todayDate) {
                window.location.href = `${apiBase}?date=${encodeURIComponent(data.server_today)}`;
                return;
            }

            if (data.changed && data.board) {
                lastSyncAt = data.last_sync_at || lastSyncAt;
                applyBoardState(data.board);
            }
        } catch (_) {
            // Silent retry on next tick
        }
    }

    if (pollInterval >= 3000) {
        setInterval(pollServer, pollInterval);
    }
})();
