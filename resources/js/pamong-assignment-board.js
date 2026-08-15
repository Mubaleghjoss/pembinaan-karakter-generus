const UNASSIGNED = 'unassigned';

function copySet(value) {
    return new Set(Array.from(value || []).map(Number));
}

function sameSet(left, right) {
    if (left.size !== right.size) {
        return false;
    }

    return Array.from(left).every((value) => right.has(value));
}

function normalized(value) {
    return String(value || '').trim().toLocaleLowerCase('id-ID');
}

class PamongAssignmentBoard {
    constructor(root) {
        this.root = root;
        this.payload = JSON.parse(root.querySelector('[data-board-payload]')?.textContent || '{}');
        this.students = new Map((this.payload.students || []).map((student) => [Number(student.id), student]));
        this.pamongs = (this.payload.pamongs || []).map((pamong) => ({ ...pamong, id: Number(pamong.id) }));
        this.pamongById = new Map(this.pamongs.map((pamong) => [pamong.id, pamong]));
        this.initialAssignments = new Map();
        this.currentAssignments = new Map();
        this.history = [];
        this.version = this.payload.version;
        this.focusedPamongId = this.payload.focused_pamong_id ? Number(this.payload.focused_pamong_id) : null;
        this.didInitialFocus = false;
        this.actionContext = null;
        this.dragContext = null;
        this.touchDrag = null;
        this.saveUrl = root.dataset.saveUrl;
        this.reloadUrl = root.dataset.reloadUrl;
        this.elements = this.collectElements();

        this.students.forEach((student, id) => {
            const assignments = copySet(student.pamong_ids);
            this.initialAssignments.set(id, copySet(assignments));
            this.currentAssignments.set(id, copySet(assignments));
        });
    }

    collectElements() {
        const find = (selector) => this.root.querySelector(selector);

        return {
            loading: find('[data-board-loading]'),
            scroll: find('[data-board-scroll]'),
            columns: find('[data-board-columns]'),
            search: find('[data-board-search]'),
            grade: find('[data-board-grade]'),
            group: find('[data-board-group]'),
            status: find('[data-board-status-filter]'),
            pamong: find('[data-board-pamong-filter]'),
            filterSummary: find('[data-board-filter-summary]'),
            resetFilters: find('[data-board-reset-filters]'),
            draftBar: find('[data-board-draft-bar]'),
            dirtyCount: find('[data-board-dirty-count]'),
            changeSummary: find('[data-board-change-summary]'),
            undo: find('[data-board-undo]'),
            resetDraft: find('[data-board-reset-draft]'),
            openSave: find('[data-board-open-save]'),
            conflict: find('[data-board-conflict]'),
            reload: find('[data-board-reload]'),
            actionDialog: find('[data-board-action-dialog]'),
            actionName: find('[data-action-student-name]'),
            actionMeta: find('[data-action-student-meta]'),
            actionCurrent: find('[data-action-current-pamongs]'),
            actionTarget: find('[data-action-target]'),
            actionHelp: find('[data-action-help]'),
            actionMove: find('[data-action-move]'),
            actionAdd: find('[data-action-add]'),
            actionEnd: find('[data-action-end]'),
            saveDialog: find('[data-board-save-dialog]'),
            saveSummary: find('[data-save-summary]'),
            saveList: find('[data-save-list]'),
            saveCancel: find('[data-save-cancel]'),
            saveConfirm: find('[data-save-confirm]'),
        };
    }

    init() {
        this.bindControls();
        this.render();
        this.elements.loading?.classList.add('hidden');
        this.elements.scroll?.classList.remove('hidden');
        this.focusInitialPamong();
    }

    bindControls() {
        let searchTimer;
        this.elements.search?.addEventListener('input', () => {
            window.clearTimeout(searchTimer);
            searchTimer = window.setTimeout(() => this.renderColumns(), 100);
        });

        [this.elements.grade, this.elements.group, this.elements.status].forEach((element) => {
            element?.addEventListener('change', () => this.renderColumns());
        });

        this.elements.pamong?.addEventListener('change', () => {
            this.renderColumns();
            const pamongId = Number(this.elements.pamong.value);
            if (pamongId) {
                this.focusColumn(pamongId, true);
            }
        });

        this.elements.resetFilters?.addEventListener('click', () => {
            this.elements.search.value = '';
            this.elements.grade.value = '';
            this.elements.group.value = '';
            this.elements.status.value = '';
            this.elements.pamong.value = '';
            this.renderColumns();
            this.elements.search.focus();
        });

        this.elements.undo?.addEventListener('click', () => this.undo());
        this.elements.resetDraft?.addEventListener('click', () => this.resetDraft());
        this.elements.openSave?.addEventListener('click', () => this.openSaveDialog());
        this.elements.saveCancel?.addEventListener('click', () => this.elements.saveDialog?.close());
        this.elements.saveConfirm?.addEventListener('click', () => this.save());
        this.elements.reload?.addEventListener('click', () => window.location.assign(this.reloadUrl));

        this.elements.actionMove?.addEventListener('click', () => this.applyAction('move'));
        this.elements.actionAdd?.addEventListener('click', () => this.applyAction('add'));
        this.elements.actionEnd?.addEventListener('click', () => this.endCurrentAssignment());

        [this.elements.actionDialog, this.elements.saveDialog].forEach((dialog) => {
            dialog?.addEventListener('click', (event) => {
                if (event.target === dialog) {
                    dialog.close();
                }
            });
        });
    }

    render() {
        this.renderColumns();
        this.renderDraftBar();
    }

    filters() {
        return {
            search: normalized(this.elements.search?.value),
            grade: this.elements.grade?.value || '',
            group: this.elements.group?.value || '',
            status: this.elements.status?.value || '',
            pamong: Number(this.elements.pamong?.value) || null,
        };
    }

    studentMatches(student, filters) {
        const assignments = this.currentAssignments.get(Number(student.id)) || new Set();
        const isAssigned = assignments.size > 0;
        const searchValue = `${student.name} ${student.nis}`.toLocaleLowerCase('id-ID');
        const matchesGrade = !filters.grade
            || (filters.grade === 'unconfirmed' ? !student.school_grade : student.school_grade === filters.grade);

        return (!filters.search || searchValue.includes(filters.search))
            && matchesGrade
            && (!filters.group || student.kelompok === filters.group)
            && (!filters.status || (filters.status === 'assigned' ? isAssigned : !isAssigned));
    }

    renderColumns() {
        const filters = this.filters();
        const students = Array.from(this.students.values()).filter((student) => this.studentMatches(student, filters));
        let columns = [];

        if (filters.status !== 'assigned') {
            columns.push({ id: UNASSIGNED, name: 'Belum Memiliki Pamong', initials: '?' });
        }

        if (filters.status !== 'unassigned') {
            const visiblePamongs = filters.pamong
                ? this.pamongs.filter((pamong) => pamong.id === filters.pamong)
                : this.pamongs;
            columns = columns.concat(visiblePamongs);
        }

        this.elements.columns.replaceChildren(...columns.map((column) => this.createColumn(column, students)));

        const visibleStudentIds = new Set();
        this.elements.columns.querySelectorAll('[data-student-id]').forEach((card) => {
            visibleStudentIds.add(Number(card.dataset.studentId));
        });
        this.elements.filterSummary.textContent = `${visibleStudentIds.size} Generus ditampilkan dalam ${columns.length} kolom`;
    }

    createColumn(column, filteredStudents) {
        const columnId = column.id === UNASSIGNED ? UNASSIGNED : String(column.id);
        const students = filteredStudents
            .filter((student) => {
                const assignments = this.currentAssignments.get(Number(student.id)) || new Set();
                return column.id === UNASSIGNED ? assignments.size === 0 : assignments.has(Number(column.id));
            })
            .sort((left, right) => left.name.localeCompare(right.name, 'id'));
        const section = document.createElement('section');
        section.className = 'pkg-mentorship-column';
        section.dataset.columnId = columnId;
        section.setAttribute('aria-label', `${column.name}, ${students.length} Generus`);

        if (Number(column.id) === this.focusedPamongId) {
            section.classList.add('is-focused');
        }

        const header = document.createElement('header');
        header.className = 'pkg-mentorship-column-header';

        const identity = document.createElement('div');
        identity.className = 'flex min-w-0 items-center gap-3';
        const avatar = document.createElement('span');
        avatar.className = column.id === UNASSIGNED
            ? 'inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 font-bold text-amber-800 dark:bg-amber-900/40 dark:text-amber-200'
            : 'inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 font-bold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200';
        avatar.textContent = column.initials;
        const titleWrap = document.createElement('div');
        titleWrap.className = 'min-w-0';
        const title = document.createElement('h3');
        title.className = 'truncate text-sm font-bold text-slate-900 dark:text-white';
        title.textContent = column.name;
        const subtitle = document.createElement('p');
        subtitle.className = 'text-xs tabular-nums text-slate-500 dark:text-slate-400';
        subtitle.textContent = `${students.length} Generus`;
        titleWrap.append(title, subtitle);
        identity.append(avatar, titleWrap);
        header.append(identity);

        const list = document.createElement('div');
        list.className = 'pkg-mentorship-card-list';
        list.dataset.dropList = columnId;

        if (students.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'pkg-mentorship-empty';
            empty.innerHTML = '<svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4v16m8-8H4"/></svg><span>Jatuhkan Generus di sini</span>';
            list.append(empty);
        } else {
            students.forEach((student, index) => list.append(this.createStudentCard(student, column.id, index + 1)));
        }

        section.addEventListener('dragover', (event) => {
            if (!this.dragContext) {
                return;
            }
            event.preventDefault();
            section.classList.add('is-drop-target');
        });
        section.addEventListener('dragleave', (event) => {
            if (!section.contains(event.relatedTarget)) {
                section.classList.remove('is-drop-target');
            }
        });
        section.addEventListener('drop', (event) => {
            event.preventDefault();
            this.clearDropTargets();
            if (this.dragContext) {
                this.moveStudent(this.dragContext.studentId, this.dragContext.sourcePamongId, column.id);
            }
        });

        section.append(header, list);
        return section;
    }

    createStudentCard(student, sourcePamongId, number) {
        const studentId = Number(student.id);
        const card = document.createElement('article');
        card.className = 'pkg-mentorship-card';
        card.draggable = true;
        card.tabIndex = 0;
        card.dataset.studentId = String(studentId);
        card.dataset.sourcePamongId = sourcePamongId === UNASSIGNED ? UNASSIGNED : String(sourcePamongId);
        card.setAttribute('aria-label', `${student.name}, NIS ${student.nis}. Ketuk untuk mengatur Pamong.`);

        const row = document.createElement('div');
        row.className = 'flex items-start gap-2';
        const ordinal = document.createElement('span');
        ordinal.className = 'mt-0.5 inline-flex size-7 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs font-bold tabular-nums text-slate-600 dark:bg-slate-800 dark:text-slate-300';
        ordinal.textContent = String(number);
        const content = document.createElement('div');
        content.className = 'min-w-0 flex-1';
        const name = document.createElement('h4');
        name.className = 'truncate text-sm font-bold text-slate-900 dark:text-white';
        name.textContent = student.name;
        const meta = document.createElement('p');
        meta.className = 'mt-0.5 truncate text-xs tabular-nums text-slate-500 dark:text-slate-400';
        meta.textContent = `NIS ${student.nis} · ${student.kelompok_label}`;
        content.append(name, meta);

        const handle = document.createElement('button');
        handle.type = 'button';
        handle.className = 'pkg-mentorship-drag-handle';
        handle.dataset.dragHandle = '';
        handle.setAttribute('aria-label', `Geser ${student.name}`);
        handle.innerHTML = '<svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9h8M8 15h8"/></svg>';
        handle.addEventListener('click', (event) => event.stopPropagation());
        handle.addEventListener('pointerdown', (event) => this.startTouchDrag(event, card));
        row.append(ordinal, content, handle);

        const badges = document.createElement('div');
        badges.className = 'mt-3 flex flex-wrap gap-1.5';
        badges.append(this.createBadge(student.school_grade_label));
        const assignmentCount = this.currentAssignments.get(studentId)?.size || 0;
        if (assignmentCount > 1) {
            badges.append(this.createBadge(`${assignmentCount} Pamong`, true));
        }

        card.append(row, badges);
        card.addEventListener('click', (event) => {
            if (!event.target.closest('[data-drag-handle]')) {
                this.openActionDialog(studentId, sourcePamongId);
            }
        });
        card.addEventListener('keydown', (event) => {
            if ((event.key === 'Enter' || event.key === ' ') && !event.target.closest('[data-drag-handle]')) {
                event.preventDefault();
                this.openActionDialog(studentId, sourcePamongId);
            }
        });
        card.addEventListener('dragstart', (event) => {
            this.dragContext = { studentId, sourcePamongId };
            card.classList.add('is-dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', String(studentId));
        });
        card.addEventListener('dragend', () => {
            this.dragContext = null;
            card.classList.remove('is-dragging');
            this.clearDropTargets();
        });

        return card;
    }

    createBadge(label, neutral = false) {
        const badge = document.createElement('span');
        badge.className = neutral
            ? 'rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300'
            : 'rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-200';
        badge.textContent = label;
        return badge;
    }

    startTouchDrag(event, card) {
        if (event.pointerType === 'mouse' || event.button !== 0) {
            return;
        }

        event.preventDefault();
        const studentId = Number(card.dataset.studentId);
        const sourcePamongId = card.dataset.sourcePamongId === UNASSIGNED
            ? UNASSIGNED
            : Number(card.dataset.sourcePamongId);
        const ghost = card.cloneNode(true);
        ghost.className = 'pkg-mentorship-drag-ghost';
        ghost.removeAttribute('tabindex');
        ghost.setAttribute('aria-hidden', 'true');
        document.body.append(ghost);
        card.classList.add('is-dragging');
        event.currentTarget.setPointerCapture(event.pointerId);
        this.touchDrag = { studentId, sourcePamongId, ghost, card, target: null };
        this.positionGhost(event.clientX, event.clientY);

        const move = (moveEvent) => {
            if (!this.touchDrag) {
                return;
            }
            moveEvent.preventDefault();
            this.positionGhost(moveEvent.clientX, moveEvent.clientY);
            this.updateTouchDropTarget(moveEvent.clientX, moveEvent.clientY);
            this.autoScrollBoard(moveEvent.clientX);
        };
        const cleanupListeners = () => {
            event.currentTarget.removeEventListener('pointermove', move);
            event.currentTarget.removeEventListener('pointerup', end);
            event.currentTarget.removeEventListener('pointercancel', cancel);
        };
        const end = (endEvent) => {
            if (!this.touchDrag) {
                return;
            }
            endEvent.preventDefault();
            const target = this.touchDrag.target;
            const context = this.touchDrag;
            cleanupListeners();
            this.cleanupTouchDrag();
            if (target !== null) {
                this.moveStudent(context.studentId, context.sourcePamongId, target);
            }
        };
        const cancel = () => {
            cleanupListeners();
            this.cleanupTouchDrag();
        };

        event.currentTarget.addEventListener('pointermove', move);
        event.currentTarget.addEventListener('pointerup', end);
        event.currentTarget.addEventListener('pointercancel', cancel);
    }

    positionGhost(x, y) {
        if (this.touchDrag?.ghost) {
            this.touchDrag.ghost.style.transform = `translate3d(${x + 12}px, ${y + 12}px, 0)`;
        }
    }

    updateTouchDropTarget(x, y) {
        this.clearDropTargets();
        const target = document.elementFromPoint(x, y)?.closest('[data-column-id]');
        if (!target || !this.elements.columns.contains(target)) {
            this.touchDrag.target = null;
            return;
        }

        target.classList.add('is-drop-target');
        this.touchDrag.target = target.dataset.columnId === UNASSIGNED
            ? UNASSIGNED
            : Number(target.dataset.columnId);
    }

    autoScrollBoard(x) {
        const rect = this.elements.scroll.getBoundingClientRect();
        const edge = 56;
        if (x < rect.left + edge) {
            this.elements.scroll.scrollBy({ left: -24 });
        } else if (x > rect.right - edge) {
            this.elements.scroll.scrollBy({ left: 24 });
        }
    }

    cleanupTouchDrag() {
        this.touchDrag?.ghost?.remove();
        this.touchDrag?.card?.classList.remove('is-dragging');
        this.touchDrag = null;
        this.clearDropTargets();
    }

    clearDropTargets() {
        this.elements.columns.querySelectorAll('.is-drop-target').forEach((element) => {
            element.classList.remove('is-drop-target');
        });
    }

    moveStudent(studentId, sourcePamongId, targetPamongId) {
        const current = copySet(this.currentAssignments.get(studentId));
        const next = copySet(current);

        if (targetPamongId === UNASSIGNED) {
            if (sourcePamongId === UNASSIGNED || !next.has(Number(sourcePamongId))) {
                window.showNotification?.('Generus ini sudah tidak memiliki Pamong aktif.', 'info');
                return;
            }
            next.delete(Number(sourcePamongId));
        } else {
            const targetId = Number(targetPamongId);
            if (next.has(targetId)) {
                window.showNotification?.('Generus tersebut sudah dibina oleh Pamong tujuan.', 'info');
                return;
            }
            if (sourcePamongId !== UNASSIGNED) {
                next.delete(Number(sourcePamongId));
            }
            next.add(targetId);
        }

        this.commitStudentChange(studentId, next);
    }

    commitStudentChange(studentId, nextAssignments) {
        const previous = copySet(this.currentAssignments.get(studentId));
        if (sameSet(previous, nextAssignments)) {
            return;
        }

        this.history.push({ studentId, assignments: previous });
        this.currentAssignments.set(studentId, copySet(nextAssignments));
        this.render();
    }

    undo() {
        const latest = this.history.pop();
        if (!latest) {
            return;
        }

        this.currentAssignments.set(latest.studentId, copySet(latest.assignments));
        this.render();
    }

    resetDraft() {
        this.currentAssignments = new Map(
            Array.from(this.initialAssignments.entries()).map(([id, assignments]) => [id, copySet(assignments)])
        );
        this.history = [];
        this.elements.conflict.classList.add('hidden');
        this.render();
    }

    dirtyChanges() {
        return Array.from(this.students.keys()).flatMap((studentId) => {
            const initial = this.initialAssignments.get(studentId) || new Set();
            const current = this.currentAssignments.get(studentId) || new Set();
            if (sameSet(initial, current)) {
                return [];
            }

            return [{
                studentId,
                added: Array.from(current).filter((id) => !initial.has(id)),
                ended: Array.from(initial).filter((id) => !current.has(id)),
                pamongIds: Array.from(current).sort((left, right) => left - right),
            }];
        });
    }

    renderDraftBar() {
        const changes = this.dirtyChanges();
        const moved = changes.reduce(
            (total, change) => total + Math.min(change.added.length, change.ended.length),
            0
        );
        const extraAdded = changes.reduce(
            (total, change) => total + Math.max(0, change.added.length - change.ended.length),
            0
        );
        const extraEnded = changes.reduce(
            (total, change) => total + Math.max(0, change.ended.length - change.added.length),
            0
        );
        const summary = [];

        if (moved) summary.push(`${moved} dipindahkan`);
        if (extraAdded) summary.push(`${extraAdded} Pamong ditambahkan`);
        if (extraEnded) summary.push(`${extraEnded} binaan diakhiri`);

        this.elements.dirtyCount.textContent = String(changes.length);
        this.elements.changeSummary.textContent = summary.join(' · ') || 'Belum ada perubahan.';
        this.elements.undo.disabled = this.history.length === 0;
        this.elements.draftBar.classList.toggle('hidden', changes.length === 0);
    }

    openActionDialog(studentId, sourcePamongId) {
        const student = this.students.get(Number(studentId));
        const current = this.currentAssignments.get(Number(studentId)) || new Set();
        const available = this.pamongs.filter((pamong) => !current.has(pamong.id));
        this.actionContext = { studentId: Number(studentId), sourcePamongId };
        this.elements.actionName.textContent = student.name;
        this.elements.actionMeta.textContent = `NIS ${student.nis} · ${student.school_grade_label} · ${student.kelompok_label}`;
        this.elements.actionCurrent.textContent = current.size
            ? Array.from(current).map((id) => this.pamongName(id)).join(', ')
            : 'Belum memiliki Pamong';
        this.elements.actionTarget.replaceChildren();

        if (available.length === 0) {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'Tidak ada Pamong lain yang tersedia';
            this.elements.actionTarget.append(option);
        } else {
            available.forEach((pamong) => {
                const option = document.createElement('option');
                option.value = String(pamong.id);
                option.textContent = pamong.name;
                this.elements.actionTarget.append(option);
            });
        }

        const isUnassigned = sourcePamongId === UNASSIGNED;
        this.elements.actionTarget.disabled = available.length === 0;
        this.elements.actionMove.disabled = available.length === 0;
        this.elements.actionMove.textContent = isUnassigned ? 'Tetapkan Pamong' : 'Pindahkan';
        this.elements.actionAdd.classList.toggle('hidden', isUnassigned);
        this.elements.actionAdd.disabled = available.length === 0;
        this.elements.actionEnd.classList.toggle('hidden', isUnassigned);
        this.elements.actionHelp.textContent = isUnassigned
            ? 'Generus akan ditambahkan ke Pamong yang dipilih.'
            : 'Pindahkan akan mengakhiri binaan pada kolom asal. Tambahkan Pamong mempertahankan seluruh Pamong saat ini.';
        this.elements.actionDialog.showModal();
        this.elements.actionTarget.focus();
    }

    applyAction(mode) {
        if (!this.actionContext) {
            return;
        }
        const targetPamongId = Number(this.elements.actionTarget.value);
        if (!targetPamongId) {
            return;
        }

        const { studentId, sourcePamongId } = this.actionContext;
        if (mode === 'add') {
            const next = copySet(this.currentAssignments.get(studentId));
            next.add(targetPamongId);
            this.commitStudentChange(studentId, next);
        } else {
            this.moveStudent(studentId, sourcePamongId, targetPamongId);
        }
        this.elements.actionDialog.close();
    }

    async endCurrentAssignment() {
        if (!this.actionContext || this.actionContext.sourcePamongId === UNASSIGNED) {
            return;
        }

        const context = { ...this.actionContext };
        const student = this.students.get(context.studentId);
        const pamongName = this.pamongName(context.sourcePamongId);
        this.elements.actionDialog.close();
        const confirmed = await window.showConfirmation?.(
            `Akhiri binaan ${student.name} dari ${pamongName}? Perubahan baru diterapkan setelah disimpan.`,
            {
                title: 'Akhiri binaan',
                confirmText: 'Akhiri Binaan',
                cancelText: 'Batal',
                tone: 'danger',
            }
        );

        if (confirmed) {
            const next = copySet(this.currentAssignments.get(context.studentId));
            next.delete(Number(context.sourcePamongId));
            this.commitStudentChange(context.studentId, next);
        }
    }

    openSaveDialog() {
        const changes = this.dirtyChanges();
        if (!changes.length) {
            return;
        }

        this.elements.saveSummary.textContent = `${changes.length} Generus akan diperbarui. Histori binaan yang dilepas tetap disimpan.`;
        this.elements.saveList.replaceChildren(...changes.map((change) => {
            const item = document.createElement('li');
            item.className = 'pkg-card-soft p-3';
            const student = this.students.get(change.studentId);
            const title = document.createElement('p');
            title.className = 'text-sm font-bold text-slate-900 dark:text-white';
            title.textContent = student.name;
            const copy = document.createElement('p');
            copy.className = 'mt-1 text-xs text-pretty text-slate-500 dark:text-slate-400';
            const ended = change.ended.map((id) => this.pamongName(id));
            const added = change.added.map((id) => this.pamongName(id));
            const parts = [];
            if (ended.length) parts.push(`Lepas: ${ended.join(', ')}`);
            if (added.length) parts.push(`Tambah: ${added.join(', ')}`);
            copy.textContent = parts.join(' · ');
            item.append(title, copy);
            return item;
        }));
        this.elements.saveDialog.showModal();
        this.elements.saveCancel.focus();
    }

    async save() {
        const changes = this.dirtyChanges();
        if (!changes.length || this.elements.saveConfirm.disabled) {
            return;
        }

        this.elements.saveConfirm.disabled = true;
        const originalLabel = this.elements.saveConfirm.textContent;
        this.elements.saveConfirm.textContent = 'Menyimpan...';

        try {
            const response = await fetch(this.saveUrl, {
                method: 'PUT',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    version: this.version,
                    students: changes.map((change) => ({
                        siswa_id: change.studentId,
                        pamong_ids: change.pamongIds,
                    })),
                }),
            });
            const data = await response.json().catch(() => ({}));

            if (response.status === 409) {
                this.elements.saveDialog.close();
                this.elements.conflict.classList.remove('hidden');
                this.elements.conflict.scrollIntoView({ behavior: this.prefersReducedMotion() ? 'auto' : 'smooth', block: 'center' });
                window.showNotification?.(data.message || 'Data Binaan telah berubah. Muat ulang sebelum menyimpan.', 'warning');
                return;
            }

            if (!response.ok) {
                const validationMessage = Object.values(data.errors || {}).flat()[0];
                throw new Error(validationMessage || data.message || 'Pembagian belum dapat disimpan.');
            }

            this.version = data.version;
            this.initialAssignments = new Map(
                Array.from(this.currentAssignments.entries()).map(([id, assignments]) => [id, copySet(assignments)])
            );
            this.history = [];
            this.elements.conflict.classList.add('hidden');
            this.elements.saveDialog.close();
            this.renderDraftBar();
            window.showNotification?.(data.message || 'Pembagian Generus dan Pamong berhasil disimpan.', 'success');
        } catch (error) {
            window.showNotification?.(error.message || 'Pembagian belum dapat disimpan.', 'error');
        } finally {
            this.elements.saveConfirm.disabled = false;
            this.elements.saveConfirm.textContent = originalLabel;
        }
    }

    pamongName(id) {
        return this.pamongById.get(Number(id))?.name || `Pamong #${id}`;
    }

    focusInitialPamong() {
        if (this.didInitialFocus || !this.focusedPamongId) {
            return;
        }
        this.didInitialFocus = true;
        window.requestAnimationFrame(() => this.focusColumn(this.focusedPamongId, false));
    }

    focusColumn(pamongId, userInitiated) {
        const column = this.elements.columns.querySelector(`[data-column-id="${Number(pamongId)}"]`);
        if (!column) {
            return;
        }
        column.scrollIntoView({
            behavior: this.prefersReducedMotion() || !userInitiated ? 'auto' : 'smooth',
            block: 'nearest',
            inline: 'center',
        });
    }

    prefersReducedMotion() {
        return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }
}

export function initializePamongAssignmentBoards(scope = document) {
    scope.querySelectorAll('[data-pamong-assignment-board]').forEach((root) => {
        if (root.dataset.boardInitialized === 'true') {
            return;
        }
        root.dataset.boardInitialized = 'true';
        new PamongAssignmentBoard(root).init();
    });
}
