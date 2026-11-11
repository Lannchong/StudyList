/**
 * STUDYLIST — Editorial Study Planner
 * Frontend Interactions, Right-Side Drawers & Interactive Calendar Engine
 */

document.addEventListener('DOMContentLoaded', function () {
  // --- DOM Elements ---
  const mobileNavToggle = document.getElementById('mobileNavToggle');
  const mobileNavMenu = document.getElementById('mobileNavMenu');

  // Drawers & Modals
  const addTaskDrawer = document.getElementById('addTaskDrawer');
  const editTaskDrawer = document.getElementById('editTaskDrawer');
  const deleteTaskModal = document.getElementById('deleteTaskModal');

  // Forms
  const addTaskForm = document.getElementById('addTaskForm');
  const editTaskForm = document.getElementById('editTaskForm');
  const deleteTaskForm = document.getElementById('deleteTaskForm');

  // Triggers
  const openAddTaskBtns = document.querySelectorAll('.js-open-add-drawer, .js-open-add-modal');
  const drawerCloseBtns = document.querySelectorAll('.js-close-drawer');
  const modalCloseBtns = document.querySelectorAll('.js-close-modal');
  const alertCloseBtns = document.querySelectorAll('.alert-close');

  // Filter Form Controls
  const filterForm = document.getElementById('filterForm');
  const searchInput = document.getElementById('searchInput');
  const filterSubject = document.getElementById('filterSubject');
  const filterStatus = document.getElementById('filterStatus');
  const filterPriority = document.getElementById('filterPriority');

  // --- Mobile Navigation Toggle ---
  if (mobileNavToggle && mobileNavMenu) {
    mobileNavToggle.addEventListener('click', function () {
      mobileNavMenu.classList.toggle('is-open');
    });

    // Close mobile nav when clicking outside
    document.addEventListener('click', function (e) {
      if (!mobileNavToggle.contains(e.target) && !mobileNavMenu.contains(e.target)) {
        mobileNavMenu.classList.remove('is-open');
      }
    });
  }

  // --- Right Side Drawer Utilities ---
  function openDrawer(drawer) {
    if (!drawer) return;
    drawer.classList.add('is-open');
    document.body.classList.add('drawer-open');
  }

  function closeDrawer(drawer) {
    if (!drawer) return;
    drawer.classList.remove('is-open');
    const anyDrawerOpen = document.querySelector('.drawer-overlay.is-open');
    if (!anyDrawerOpen) {
      document.body.classList.remove('drawer-open');
    }
  }

  // Modal Utilities (for Delete Confirmation)
  function openModal(modal) {
    if (!modal) return;
    modal.classList.add('is-open');
    document.body.classList.add('modal-open');
  }

  function closeModal(modal) {
    if (!modal) return;
    modal.classList.remove('is-open');
    const anyModalOpen = document.querySelector('.editorial-modal-overlay.is-open');
    if (!anyModalOpen) {
      document.body.classList.remove('modal-open');
    }
  }

  // Close Drawer Buttons
  drawerCloseBtns.forEach(btn => {
    btn.addEventListener('click', function () {
      closeDrawer(btn.closest('.drawer-overlay'));
    });
  });

  // Close Modal Buttons
  modalCloseBtns.forEach(btn => {
    btn.addEventListener('click', function () {
      closeModal(btn.closest('.editorial-modal-overlay'));
    });
  });

  // Close when clicking modal backdrop
  document.querySelectorAll('.editorial-modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) {
        closeModal(overlay);
      }
    });
  });

  // Escape key closes drawers and modals
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      const activeDrawer = document.querySelector('.drawer-overlay.is-open');
      if (activeDrawer) closeDrawer(activeDrawer);

      const activeModal = document.querySelector('.editorial-modal-overlay.is-open');
      if (activeModal) closeModal(activeModal);

      if (mobileNavMenu) mobileNavMenu.classList.remove('is-open');
    }
  });

  // --- Add Task Drawer Trigger ---
  openAddTaskBtns.forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      if (addTaskForm) {
        addTaskForm.reset();
        // Clear all inputs explicitly
        const titleInput = document.getElementById('addTitle');
        const descInput = document.getElementById('addDescription');
        if (titleInput) titleInput.value = '';
        if (descInput) descInput.value = '';
      }

      // If a calendar date is currently selected, prefill it!
      if (window.currentSelectedCalendarDate) {
        const dueDateInput = document.getElementById('addDueDate');
        if (dueDateInput) dueDateInput.value = window.currentSelectedCalendarDate;
      }

      openDrawer(addTaskDrawer);
      const titleInput = document.getElementById('addTitle');
      if (titleInput) setTimeout(() => titleInput.focus(), 150);
    });
  });

  // --- Edit Task Event Handler ---
  function attachEditTaskHandler(btn) {
    const id = btn.getAttribute('data-id');
    const title = btn.getAttribute('data-title') || '';
    const subjectId = btn.getAttribute('data-subject-id') || '';
    const description = btn.getAttribute('data-description') || '';
    const dueDate = btn.getAttribute('data-due-date') || '';
    const priority = btn.getAttribute('data-priority') || 'medium';
    const status = btn.getAttribute('data-status') || 'pending';

    const editIdInput = document.getElementById('editTaskId');
    const editTitleInput = document.getElementById('editTitle');
    const editSubjectInput = document.getElementById('editSubjectId');
    const editDescInput = document.getElementById('editDescription');
    const editDueDateInput = document.getElementById('editDueDate');
    const editPriorityInput = document.getElementById('editPriority');
    const editStatusInput = document.getElementById('editStatus');

    if (editIdInput) editIdInput.value = id;
    if (editTitleInput) editTitleInput.value = title;
    if (editSubjectInput) editSubjectInput.value = subjectId;
    if (editDescInput) editDescInput.value = description;
    if (editDueDateInput) editDueDateInput.value = dueDate;
    if (editPriorityInput) editPriorityInput.value = priority;
    if (editStatusInput) editStatusInput.value = status;

    openDrawer(editTaskDrawer);
    if (editTitleInput) setTimeout(() => editTitleInput.focus(), 150);
  }

  document.addEventListener('click', function (e) {
    const editBtn = e.target.closest('.js-edit-task');
    if (editBtn) {
      e.preventDefault();
      attachEditTaskHandler(editBtn);
      return;
    }

    const deleteBtn = e.target.closest('.js-delete-task');
    if (deleteBtn) {
      e.preventDefault();
      const id = deleteBtn.getAttribute('data-id');
      const title = deleteBtn.getAttribute('data-title') || 'tugas ini';

      const deleteIdInput = document.getElementById('deleteTaskId');
      const deleteTitleDisplay = document.getElementById('deleteTaskTitle');

      if (deleteIdInput) deleteIdInput.value = id;
      if (deleteTitleDisplay) deleteTitleDisplay.textContent = `"${title}"`;

      openModal(deleteTaskModal);
    }
  });

  // Prevent double submissions with loading state after validation
  if (addTaskForm) {
    addTaskForm.addEventListener('submit', function (e) {
      if (!this.checkValidity()) {
        e.preventDefault();
        this.reportValidity();
        return;
      }
      const btn = document.getElementById('addSubmitBtn');
      if (btn) {
        btn.disabled = true;
        btn.textContent = 'Menyimpan...';
      }
    });
  }

  if (editTaskForm) {
    editTaskForm.addEventListener('submit', function (e) {
      if (!this.checkValidity()) {
        e.preventDefault();
        this.reportValidity();
        return;
      }
      const btn = document.getElementById('editSubmitBtn');
      if (btn) {
        btn.disabled = true;
        btn.textContent = 'Memperbarui...';
      }
    });
  }

  if (deleteTaskForm) {
    deleteTaskForm.addEventListener('submit', function () {
      const btn = document.getElementById('deleteSubmitBtn');
      if (btn) {
        btn.disabled = true;
        btn.textContent = 'Menghapus...';
      }
    });
  }

  // --- Auto-Submit Filters on Select Change ---
  if (filterForm) {
    [filterSubject, filterStatus, filterPriority].forEach(elem => {
      if (elem) {
        elem.addEventListener('change', function () {
          filterForm.submit();
        });
      }
    });

    if (searchInput) {
      searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          filterForm.submit();
        }
      });
    }
  }

  // --- Flash Notification Auto-Dismiss ---
  alertCloseBtns.forEach(btn => {
    btn.addEventListener('click', function () {
      const banner = btn.closest('.alert-banner');
      if (banner) {
        banner.style.opacity = '0';
        setTimeout(() => banner.remove(), 250);
      }
    });
  });

  const autoDismissBanners = document.querySelectorAll('.alert-banner');
  if (autoDismissBanners.length > 0) {
    setTimeout(() => {
      autoDismissBanners.forEach(banner => {
        banner.style.transition = 'opacity 0.4s ease';
        banner.style.opacity = '0';
        setTimeout(() => banner.remove(), 400);
      });
    }, 6000);
  }

  // ========================================================
  // CALENDAR ENGINE (PURE VANILLA JS — EDITORIAL STYLE)
  // ========================================================
  const calendarDataScript = document.getElementById('calendarTasksData');
  if (calendarDataScript) {
    initCalendarEngine(calendarDataScript);
  }

  function initCalendarEngine(dataScript) {
    let tasks = [];
    try {
      tasks = JSON.parse(dataScript.textContent || '[]');
    } catch (err) {
      console.error('Failed to parse calendar tasks JSON:', err);
      tasks = [];
    }

    // Group tasks by due_date (YYYY-MM-DD)
    const tasksByDate = {};
    tasks.forEach(t => {
      if (!t.due_date) return;
      const dateKey = t.due_date.substring(0, 10);
      if (!tasksByDate[dateKey]) {
        tasksByDate[dateKey] = [];
      }
      tasksByDate[dateKey].push(t);
    });

    // Calendar state
    const today = new Date();
    let currentYear = today.getFullYear();
    let currentMonth = today.getMonth(); // 0 - 11

    function toDateString(y, m, d) {
      const mm = String(m + 1).padStart(2, '0');
      const dd = String(d).padStart(2, '0');
      return `${y}-${mm}-${dd}`;
    }

    const todayStr = toDateString(today.getFullYear(), today.getMonth(), today.getDate());
    let selectedDate = todayStr;
    window.currentSelectedCalendarDate = selectedDate;

    // DOM Elements
    const monthTitle = document.getElementById('calMonthYearTitle');
    const monthTaskCount = document.getElementById('calMonthTaskCount');
    const daysGrid = document.getElementById('calendarDaysGrid');
    const prevBtn = document.getElementById('calPrevBtn');
    const nextBtn = document.getElementById('calNextBtn');
    const todayBtn = document.getElementById('calTodayBtn');
    const selectedDateTitle = document.getElementById('selectedDateTitle');
    const selectedDateTasksList = document.getElementById('selectedDateTasksList');
    const btnAddTaskOnDate = document.getElementById('btnAddTaskOnDate');

    const monthNames = [
      'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    const dayNames = [
      'Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'
    ];

    function formatHumanDate(dateStr) {
      const parts = dateStr.split('-');
      if (parts.length !== 3) return dateStr;
      const dateObj = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
      const dayName = dayNames[dateObj.getDay()];
      const d = parseInt(parts[2]);
      const mName = monthNames[parseInt(parts[1]) - 1];
      const y = parts[0];
      return `${dayName}, ${d} ${mName} ${y}`;
    }

    // Render Calendar Month Grid
    function renderCalendar() {
      if (!daysGrid) return;
      daysGrid.innerHTML = '';

      if (monthTitle) {
        monthTitle.textContent = `${monthNames[currentMonth]} ${currentYear}`;
      }

      // First day of month & Days in month
      const firstDayObj = new Date(currentYear, currentMonth, 1);
      // Monday = 0, ..., Sunday = 6
      let startDayOfWeek = firstDayObj.getDay() - 1;
      if (startDayOfWeek === -1) startDayOfWeek = 6;

      const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
      const prevMonthDays = new Date(currentYear, currentMonth, 0).getDate();

      let monthTasksTotal = 0;

      // 1. Trailing days from previous month
      for (let i = startDayOfWeek - 1; i >= 0; i--) {
        const prevDayNum = prevMonthDays - i;
        const prevMonthVal = currentMonth === 0 ? 11 : currentMonth - 1;
        const prevYearVal = currentMonth === 0 ? currentYear - 1 : currentYear;
        const dateStr = toDateString(prevYearVal, prevMonthVal, prevDayNum);

        const cell = createDayCell(prevDayNum, dateStr, true);
        daysGrid.appendChild(cell);
      }

      // 2. Days of current month
      for (let day = 1; day <= daysInMonth; day++) {
        const dateStr = toDateString(currentYear, currentMonth, day);
        const dayTasks = tasksByDate[dateStr] || [];
        monthTasksTotal += dayTasks.length;

        const cell = createDayCell(day, dateStr, false);
        daysGrid.appendChild(cell);
      }

      // 3. Leading days of next month to complete 7 columns
      const totalCells = startDayOfWeek + daysInMonth;
      const remainingCells = (7 - (totalCells % 7)) % 7;
      for (let nextDay = 1; nextDay <= remainingCells; nextDay++) {
        const nextMonthVal = currentMonth === 11 ? 0 : currentMonth + 1;
        const nextYearVal = currentMonth === 11 ? currentYear + 1 : currentYear;
        const dateStr = toDateString(nextYearVal, nextMonthVal, nextDay);

        const cell = createDayCell(nextDay, dateStr, true);
        daysGrid.appendChild(cell);
      }

      if (monthTaskCount) {
        monthTaskCount.textContent = `${monthTasksTotal} Tugas`;
      }

      renderSelectedDateTasks();
    }

    // Helper: Create a single Day Cell DOM element
    function createDayCell(dayNum, dateStr, isOtherMonth) {
      const cell = document.createElement('div');
      cell.className = 'editorial-day-cell';
      if (isOtherMonth) cell.classList.add('is-other-month');
      if (dateStr === todayStr) cell.classList.add('is-today');
      if (dateStr === selectedDate) cell.classList.add('is-selected');

      const dayTasks = tasksByDate[dateStr] || [];

      // Top bar with day number & indicator dots
      const topDiv = document.createElement('div');
      topDiv.className = 'day-cell-top';

      const numSpan = document.createElement('span');
      numSpan.className = 'day-cell-number';
      numSpan.textContent = dayNum;
      topDiv.appendChild(numSpan);

      if (dayTasks.length > 0) {
        const indicators = document.createElement('div');
        indicators.className = 'day-indicators';

        const hasActive = dayTasks.some(t => t.status !== 'completed');
        const hasCompleted = dayTasks.some(t => t.status === 'completed');

        if (hasActive) {
          const dotActive = document.createElement('span');
          dotActive.className = 'cal-dot dot-active';
          dotActive.title = 'Tugas belum selesai';
          indicators.appendChild(dotActive);
        }

        if (hasCompleted) {
          const dotComp = document.createElement('span');
          dotComp.className = 'cal-dot dot-completed';
          dotComp.title = 'Tugas selesai';
          indicators.appendChild(dotComp);
        }

        topDiv.appendChild(indicators);
      }

      cell.appendChild(topDiv);

      // Bottom task pill count
      if (dayTasks.length > 0) {
        const badge = document.createElement('div');
        badge.className = 'day-task-count';
        const allCompleted = dayTasks.every(t => t.status === 'completed');
        if (allCompleted) badge.classList.add('all-completed');
        badge.textContent = `${dayTasks.length} tugas`;
        cell.appendChild(badge);
      }

      // Click event to select this date
      cell.addEventListener('click', function () {
        selectedDate = dateStr;
        window.currentSelectedCalendarDate = selectedDate;

        document.querySelectorAll('.editorial-day-cell').forEach(c => c.classList.remove('is-selected'));
        cell.classList.add('is-selected');

        renderSelectedDateTasks();
      });

      return cell;
    }

    // Render Side Panel for the currently selected date
    function renderSelectedDateTasks() {
      if (!selectedDateTitle || !selectedDateTasksList) return;

      selectedDateTitle.textContent = formatHumanDate(selectedDate);
      selectedDateTasksList.innerHTML = '';

      const dayTasks = tasksByDate[selectedDate] || [];

      if (dayTasks.length === 0) {
        selectedDateTasksList.innerHTML = `
          <div class="panel-empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            <p>Tidak ada tugas dengan tenggat pengumpulan pada tanggal ini.</p>
            <button type="button" class="btn btn-editorial-outline btn-sm js-add-selected-date" style="margin-top: 6px;">
              + Tambah Tugas di Tanggal Ini
            </button>
          </div>
        `;

        const quickAddBtn = selectedDateTasksList.querySelector('.js-add-selected-date');
        if (quickAddBtn) {
          quickAddBtn.addEventListener('click', openAddDrawerWithDate);
        }
        return;
      }

      // Render tasks list in pure Indonesian
      dayTasks.forEach(task => {
        const isCompleted = task.status === 'completed';
        const priorityLabels = { low: 'Rendah', medium: 'Sedang', high: 'Tinggi' };
        const statusLabels = { pending: 'Belum Dikerjakan', in_progress: 'Sedang Dikerjakan', completed: 'Selesai' };

        const taskItem = document.createElement('div');
        taskItem.className = `panel-task-card ${isCompleted ? 'is-completed' : ''}`;

        taskItem.innerHTML = `
          <div class="panel-card-top">
            <span class="subject-badge">${escapeHtml(task.subject_name)}</span>
            <span class="priority-chip priority-${task.priority}">${priorityLabels[task.priority] || task.priority}</span>
          </div>

          <h5 class="panel-task-title">${escapeHtml(task.title)}</h5>

          ${task.description ? `<p class="panel-task-desc">${escapeHtml(task.description)}</p>` : ''}

          <div class="panel-card-footer">
            <span class="status-chip status-${task.status}">
              ${statusLabels[task.status] || task.status}
            </span>

            <div class="panel-task-actions">
              <button type="button" class="row-action-btn edit js-edit-task" title="Edit Tugas"
                      data-id="${task.id}"
                      data-title="${escapeHtml(task.title)}"
                      data-subject-id="${task.subject_id}"
                      data-description="${escapeHtml(task.description || '')}"
                      data-due-date="${escapeHtml(task.due_date || '')}"
                      data-priority="${task.priority}"
                      data-status="${task.status}">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
              </button>

              <button type="button" class="row-action-btn delete js-delete-task" title="Hapus Tugas"
                      data-id="${task.id}"
                      data-title="${escapeHtml(task.title)}">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="3 6 5 6 21 6"></polyline>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                  <line x1="10" y1="11" x2="10" y2="17"></line>
                  <line x1="14" y1="11" x2="14" y2="17"></line>
                </svg>
              </button>
            </div>
          </div>
        `;

        selectedDateTasksList.appendChild(taskItem);
      });
    }

    function openAddDrawerWithDate() {
      if (addTaskForm) {
        addTaskForm.reset();
        const titleInput = document.getElementById('addTitle');
        const descInput = document.getElementById('addDescription');
        if (titleInput) titleInput.value = '';
        if (descInput) descInput.value = '';
      }
      const dueDateInput = document.getElementById('addDueDate');
      if (dueDateInput) dueDateInput.value = selectedDate;

      openDrawer(addTaskDrawer);
      const titleInput = document.getElementById('addTitle');
      if (titleInput) setTimeout(() => titleInput.focus(), 150);
    }

    if (btnAddTaskOnDate) {
      btnAddTaskOnDate.addEventListener('click', openAddDrawerWithDate);
    }

    // Prev / Next / Today Buttons
    if (prevBtn) {
      prevBtn.addEventListener('click', function () {
        currentMonth--;
        if (currentMonth < 0) {
          currentMonth = 11;
          currentYear--;
        }
        renderCalendar();
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', function () {
        currentMonth++;
        if (currentMonth > 11) {
          currentMonth = 0;
          currentYear++;
        }
        renderCalendar();
      });
    }

    if (todayBtn) {
      todayBtn.addEventListener('click', function () {
        currentYear = today.getFullYear();
        currentMonth = today.getMonth();
        selectedDate = todayStr;
        window.currentSelectedCalendarDate = selectedDate;
        renderCalendar();
      });
    }

    function escapeHtml(text) {
      if (!text) return '';
      const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      };
      return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    // Initial Calendar render
    renderCalendar();
  }
});
