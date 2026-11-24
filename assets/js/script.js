// STUDYLIST — Frontend Script (Interaksi UI, Tema, Kalender, Drag & Drop)

document.addEventListener('DOMContentLoaded', function () {
  // --- 1. ELEMEN DOM & TOMBOL ---
  const mobileNavToggle = document.getElementById('mobileNavToggle'); // Tombol menu HP
  const mobileNavMenu = document.getElementById('mobileNavMenu');     // Menu navigasi HP

  const addTaskDrawer = document.getElementById('addTaskDrawer');     // Panel form tambah
  const editTaskDrawer = document.getElementById('editTaskDrawer');   // Panel form edit
  const deleteTaskModal = document.getElementById('deleteTaskModal'); // Popup konfirmasi hapus

  const addTaskForm = document.getElementById('addTaskForm');         // Form tambah tugas
  const editTaskForm = document.getElementById('editTaskForm');       // Form edit tugas
  const deleteTaskForm = document.getElementById('deleteTaskForm');   // Form hapus tugas

  const openAddTaskBtns = document.querySelectorAll('.js-open-add-drawer, .js-open-add-modal');
  const drawerCloseBtns = document.querySelectorAll('.js-close-drawer');
  const modalCloseBtns = document.querySelectorAll('.js-close-modal');
  const alertCloseBtns = document.querySelectorAll('.alert-close');

  const filterForm = document.getElementById('filterForm');           // Form filter & pencarian
  const searchInput = document.getElementById('searchInput');
  const filterSubject = document.getElementById('filterSubject');
  const filterStatus = document.getElementById('filterStatus');
  const filterPriority = document.getElementById('filterPriority');

  // --- 2. SCRIPT UBAH LIGHT MODE & DARK MODE ---
  const themeToggleBtn = document.getElementById('themeToggleBtn');
  if (themeToggleBtn) {
    themeToggleBtn.addEventListener('click', function () {
      const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
      const newTheme = (currentTheme === 'dark') ? 'light' : 'dark'; // Balikkan tema aktif
      document.documentElement.setAttribute('data-theme', newTheme); // Ubah tema di halaman
      localStorage.setItem('studylist_theme', newTheme);             // Simpan ke memori browser
    });
  }

  // --- 3. MENU NAVIGASI HP (MOBILE) ---
  if (mobileNavToggle && mobileNavMenu) {
    mobileNavToggle.addEventListener('click', function () {
      mobileNavMenu.classList.toggle('is-open'); // Buka/tutup menu di HP
    });

    document.addEventListener('click', function (e) {
      if (!mobileNavToggle.contains(e.target) && !mobileNavMenu.contains(e.target)) {
        mobileNavMenu.classList.remove('is-open'); // Tutup jika klik di luar menu
      }
    });
  }

  // --- 4. BUKA & TUTUP PANEL SAMPING (DRAWER) ---
  function openDrawer(drawer) {
    if (!drawer) return;
    drawer.classList.add('is-open');           // Munculkan panel
    document.body.classList.add('drawer-open');
  }

  function closeDrawer(drawer) {
    if (!drawer) return;
    drawer.classList.remove('is-open');        // Tutup panel
    const anyDrawerOpen = document.querySelector('.drawer-overlay.is-open');
    if (!anyDrawerOpen) {
      document.body.classList.remove('drawer-open');
    }
  }

  // --- 5. BUKA & TUTUP POPUP KONFIRMASI (MODAL) ---
  function openModal(modal) {
    if (!modal) return;
    modal.classList.add('is-open');            // Munculkan popup
    document.body.classList.add('modal-open');
  }

  function closeModal(modal) {
    if (!modal) return;
    modal.classList.remove('is-open');         // Tutup popup
    const anyModalOpen = document.querySelector('.editorial-modal-overlay.is-open');
    if (!anyModalOpen) {
      document.body.classList.remove('modal-open');
    }
  }

  // Tombol X atau batal untuk menutup drawer & modal
  drawerCloseBtns.forEach(btn => {
    btn.addEventListener('click', function () {
      closeDrawer(btn.closest('.drawer-overlay'));
    });
  });

  modalCloseBtns.forEach(btn => {
    btn.addEventListener('click', function () {
      closeModal(btn.closest('.editorial-modal-overlay'));
    });
  });

  // Tutup modal jika klik area luar yang gelap
  document.querySelectorAll('.editorial-modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) closeModal(overlay);
    });
  });

  // Tutup panel/modal dengan tombol Escape di keyboard
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      const activeDrawer = document.querySelector('.drawer-overlay.is-open');
      if (activeDrawer) closeDrawer(activeDrawer);

      const activeModal = document.querySelector('.editorial-modal-overlay.is-open');
      if (activeModal) closeModal(activeModal);

      if (mobileNavMenu) mobileNavMenu.classList.remove('is-open');
    }
  });

  // --- 6. TOMBOL BUKA FORM TAMBAH TUGAS ---
  openAddTaskBtns.forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      if (addTaskForm) {
        addTaskForm.reset(); // Kosongkan input form tambah
        const titleInput = document.getElementById('addTitle');
        const descInput = document.getElementById('addDescription');
        const dueTimeInput = document.getElementById('addDueTime');
        if (titleInput) titleInput.value = '';
        if (descInput) descInput.value = '';
        if (dueTimeInput) dueTimeInput.value = '';
      }

      // Jika sedang pilih tanggal di kalender, isi otomatis tanggalnya
      const addDueDateInput = document.getElementById('addDueDate');
      if (addDueDateInput && window.currentSelectedCalendarDate) {
        addDueDateInput.value = window.currentSelectedCalendarDate;
      }

      openDrawer(addTaskDrawer); // Tampilkan drawer tambah tugas
      const titleInput = document.getElementById('addTitle');
      if (titleInput) setTimeout(() => titleInput.focus(), 150);
    });
  });

  // --- 7. TAMPILKAN DATA KE FORM EDIT TUGAS ---
  function attachEditTaskHandler(btn) {
    const id = btn.getAttribute('data-id');
    const title = btn.getAttribute('data-title') || '';
    const subjectId = btn.getAttribute('data-subject-id') || '';
    const description = btn.getAttribute('data-description') || '';
    const dueDate = btn.getAttribute('data-due-date') || '';
    const dueTime = btn.getAttribute('data-due-time') || '';
    const priority = btn.getAttribute('data-priority') || 'medium';
    const status = btn.getAttribute('data-status') || 'pending';

    const editIdInput = document.getElementById('editTaskId');
    const editTitleInput = document.getElementById('editTitle');
    const editSubjectInput = document.getElementById('editSubjectId');
    const editDescInput = document.getElementById('editDescription');
    const editDueDateInput = document.getElementById('editDueDate');
    const editDueTimeInput = document.getElementById('editDueTime');
    const editPriorityInput = document.getElementById('editPriority');
    const editStatusInput = document.getElementById('editStatus');

    // Masukkan data lama tugas ke dalam kolom input edit
    if (editIdInput) editIdInput.value = id;
    if (editTitleInput) editTitleInput.value = title;
    if (editSubjectInput) editSubjectInput.value = subjectId;
    if (editDescInput) editDescInput.value = description;
    if (editDueDateInput) editDueDateInput.value = dueDate;
    if (editDueTimeInput) editDueTimeInput.value = dueTime ? dueTime.substring(0, 5) : '';
    if (editPriorityInput) editPriorityInput.value = priority;
    if (editStatusInput) editStatusInput.value = status;

    openDrawer(editTaskDrawer); // Buka panel edit tugas
    if (editTitleInput) setTimeout(() => editTitleInput.focus(), 150);
  }

  // --- 8. DETEKSI KLIK TOMBOL EDIT & HAPUS ---
  document.addEventListener('click', function (e) {
    // Tombol Edit diklik
    const editBtn = e.target.closest('.js-edit-task');
    if (editBtn) {
      e.preventDefault();
      attachEditTaskHandler(editBtn);
      return;
    }

    // Tombol Hapus diklik (munculkan modal konfirmasi hapus)
    const deleteBtn = e.target.closest('.js-delete-task');
    if (deleteBtn) {
      e.preventDefault();
      const id = deleteBtn.getAttribute('data-id');
      const title = deleteBtn.getAttribute('data-title') || 'tugas ini';

      const deleteIdInput = document.getElementById('deleteTaskId');
      const deleteTitleDisplay = document.getElementById('deleteTaskTitle');

      if (deleteIdInput) deleteIdInput.value = id;
      if (deleteTitleDisplay) deleteTitleDisplay.textContent = `"${title}"`;

      openModal(deleteTaskModal); // Tampilkan popup konfirmasi
    }
  });

  // Reset pesan validasi saat kolom tanggal diketik
  const globalAddDueDate = document.getElementById('addDueDate');
  if (globalAddDueDate) {
    globalAddDueDate.addEventListener('input', function () {
      this.setCustomValidity('');
    });
  }
  const globalEditDueDate = document.getElementById('editDueDate');
  if (globalEditDueDate) {
    globalEditDueDate.addEventListener('input', function () {
      this.setCustomValidity('');
    });
  }

  // --- 9. CEK VALIDASI FORM & CEGAH DOBEL KLIK TOMBOL SIMPAN ---
  // A. Form Tambah Tugas
  if (addTaskForm) {
    addTaskForm.addEventListener('submit', function (e) {
      const addDueDate = document.getElementById('addDueDate');
      const addDueTime = document.getElementById('addDueTime');

      // Validasi: jam tidak boleh diisi tanpa tanggal
      if (addDueTime && addDueTime.value.trim() !== '' && (!addDueDate || addDueDate.value.trim() === '')) {
        e.preventDefault();
        addDueDate.setCustomValidity('Tanggal pengumpulan wajib diisi jika jam pengumpulan ditentukan.');
        addDueDate.reportValidity();
        return;
      }

      if (!this.checkValidity()) {
        e.preventDefault();
        this.reportValidity();
        return;
      }
      const btn = document.getElementById('addSubmitBtn');
      if (btn) {
        btn.disabled = true; // Kunci tombol agar tidak tersimpan ganda
        btn.textContent = 'Menyimpan...';
      }
    });
  }

  // B. Form Edit Tugas
  if (editTaskForm) {
    editTaskForm.addEventListener('submit', function (e) {
      const editDueDate = document.getElementById('editDueDate');
      const editDueTime = document.getElementById('editDueTime');

      // Validasi: jam tidak boleh diisi tanpa tanggal
      if (editDueTime && editDueTime.value.trim() !== '' && (!editDueDate || editDueDate.value.trim() === '')) {
        e.preventDefault();
        editDueDate.setCustomValidity('Tanggal pengumpulan wajib diisi jika jam pengumpulan ditentukan.');
        editDueDate.reportValidity();
        return;
      }

      if (!this.checkValidity()) {
        e.preventDefault();
        this.reportValidity();
        return;
      }
      const btn = document.getElementById('editSubmitBtn');
      if (btn) {
        btn.disabled = true; // Kunci tombol saat proses update
        btn.textContent = 'Memperbarui...';
      }
    });
  }

  // C. Form Hapus Tugas
  if (deleteTaskForm) {
    deleteTaskForm.addEventListener('submit', function () {
      const btn = document.getElementById('deleteSubmitBtn');
      if (btn) {
        btn.disabled = true; // Kunci tombol saat proses hapus
        btn.textContent = 'Menghapus...';
      }
    });
  }

  // --- 10. FILTER & PENCARIAN TUGAS ---
  if (filterForm) {
    // Submit otomatis ketika filter dropdown dipilih
    [filterSubject, filterStatus, filterPriority].forEach(elem => {
      if (elem) {
        elem.addEventListener('change', function () {
          filterForm.submit();
        });
      }
    });

    // Pencarian saat tombol Enter ditekan atau kolom dikosongkan
    if (searchInput) {
      let activeQuery = searchInput.value.trim();

      searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          filterForm.submit();
        }
      });

      searchInput.addEventListener('input', function () {
        const currentVal = this.value.trim();
        if (currentVal === '' && activeQuery !== '') {
          activeQuery = '';
          filterForm.submit(); // Reset hasil pencarian otomatis
        }
      });
    }
  }

  // --- 11. NOTIFIKASI HILANG OTOMATIS (FLASH MESSAGE) ---
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
        setTimeout(() => banner.remove(), 400); // Hilang setelah 6 detik
      });
    }, 6000);
  }

  // --- 12. FITUR DRAG & DROP URUTAN TUGAS ---
  const taskListContainer = document.getElementById('taskListContainer');
  if (taskListContainer && taskListContainer.getAttribute('data-can-reorder') === 'true') {
    initTaskReordering(taskListContainer);
  }

  function initTaskReordering(container) {
    const hintBanner = document.querySelector('.drag-hint-banner');
    const defaultHintText = hintBanner ? (hintBanner.querySelector('span')?.textContent || 'Geser ikon untuk mengatur urutan tugas.') : 'Geser ikon untuk mengatur urutan tugas.';

    function setHintStatus(message, isSuccess = false, isError = false) {
      if (!hintBanner) return;
      const textSpan = hintBanner.querySelector('span');
      if (textSpan) textSpan.textContent = message;

      if (isSuccess) {
        hintBanner.style.borderColor = '#10B981';
        hintBanner.style.color = '#10B981';
      } else if (isError) {
        hintBanner.style.borderColor = '#EF4444';
        hintBanner.style.color = '#EF4444';
      } else {
        hintBanner.style.borderColor = '';
        hintBanner.style.color = '';
      }
    }

    function resetHintStatus() {
      if (!hintBanner) return;
      const textSpan = hintBanner.querySelector('span');
      if (textSpan) textSpan.textContent = defaultHintText;
      hintBanner.style.borderColor = '';
      hintBanner.style.color = '';
    }

    // SIMPAN URUTAN TUGAS BARU KE DATABASE (AJAX POST ke reorder.php)
    function persistReorder(newOrderIds, revertCallback) {
      setHintStatus('Menyimpan urutan tugas...');

      fetch('reorder.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ order: newOrderIds }) // Kirim susunan ID tugas baru
      })
      .then(response => {
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
      })
      .then(data => {
        if (data.success) {
          setHintStatus('✓ Urutan tugas berhasil disimpan.', true);
          setTimeout(() => resetHintStatus(), 2000);
        } else {
          throw new Error(data.message || 'Gagal menyimpan');
        }
      })
      .catch(err => {
        console.error('Reorder error:', err);
        setHintStatus('Gagal menyimpan urutan tugas. Mengembalikan ke posisi semula.', false, true);
        if (typeof revertCallback === 'function') revertCallback(); // Kembalikan posisi jika gagal
        setTimeout(() => resetHintStatus(), 3500);
      });
    }

    // Menggunakan SortableJS jika pustaka tersedia
    if (typeof Sortable !== 'undefined') {
      new Sortable(container, {
        handle: '.js-drag-handle', // Tarik lewat ikon grip-vertical
        animation: 200,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag',
        fallbackClass: 'sortable-fallback',
        forceFallback: false,
        onEnd: function (evt) {
          if (evt.oldIndex === evt.newIndex) return; // Posisi tidak berubah

          const rows = container.querySelectorAll('.editorial-task-row');
          const newOrderIds = Array.from(rows)
            .map(r => r.getAttribute('data-id'))
            .filter(Boolean)
            .map(Number);

          persistReorder(newOrderIds, () => {
            const items = Array.from(container.children);
            const item = evt.item;
            const targetRef = items[evt.oldIndex > evt.newIndex ? evt.oldIndex + 1 : evt.oldIndex];
            container.insertBefore(item, targetRef);
          });
        }
      });
    } else {
      // Fallback native drag & drop bawaan browser
      initNativeDragDrop(container, persistReorder);
    }
  }

  // Drag & drop bawaan browser (HTML5 standard)
  function initNativeDragDrop(container, persistReorder) {
    const rows = container.querySelectorAll('.editorial-task-row');
    let draggedRow = null;
    let oldIndex = -1;

    rows.forEach(row => {
      const handle = row.querySelector('.js-drag-handle');
      if (!handle) return;

      handle.setAttribute('draggable', 'true');

      handle.addEventListener('dragstart', function (e) {
        draggedRow = row;
        oldIndex = Array.from(container.children).indexOf(row);
        row.classList.add('sortable-chosen');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', row.getAttribute('data-id') || '');
      });

      handle.addEventListener('dragend', function () {
        row.classList.remove('sortable-chosen');
        row.classList.remove('sortable-drag');
        container.querySelectorAll('.editorial-task-row').forEach(r => r.classList.remove('sortable-ghost'));

        const newIndex = Array.from(container.children).indexOf(row);
        if (newIndex !== oldIndex && oldIndex !== -1) {
          const currentRows = container.querySelectorAll('.editorial-task-row');
          const newOrderIds = Array.from(currentRows)
            .map(r => r.getAttribute('data-id'))
            .filter(Boolean)
            .map(Number);

          persistReorder(newOrderIds, () => {
            const items = Array.from(container.children);
            const targetRef = items[oldIndex > newIndex ? oldIndex + 1 : oldIndex];
            container.insertBefore(row, targetRef);
          });
        }
        draggedRow = null;
        oldIndex = -1;
      });

      row.addEventListener('dragover', function (e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        if (!draggedRow || draggedRow === row) return;

        const bounding = row.getBoundingClientRect();
        const offset = bounding.y + (bounding.height / 2);
        if (e.clientY - offset > 0) {
          row.after(draggedRow);
        } else {
          row.before(draggedRow);
        }
      });
    });
  }

  // --- 13. SCRIPT KALENDER TUGAS ---
  const calendarDataScript = document.getElementById('calendarTasksData');
  if (calendarDataScript) {
    initCalendarEngine(calendarDataScript);
  }

  function initCalendarEngine(dataScript) {
    let tasks = [];
    try {
      tasks = JSON.parse(dataScript.textContent || '[]'); // Ambil data tugas dari backend PHP
    } catch (err) {
      console.error('Failed to parse calendar tasks JSON:', err);
      tasks = [];
    }

    // Kelompokkan tugas berdasarkan tanggal (YYYY-MM-DD)
    const tasksByDate = {};
    tasks.forEach(t => {
      if (!t.due_date) return;
      const dateKey = t.due_date.substring(0, 10);
      if (!tasksByDate[dateKey]) {
        tasksByDate[dateKey] = [];
      }
      tasksByDate[dateKey].push(t);
    });

    // Ambil tanggal hari ini zona waktu Jakarta (WIB)
    function getJakartaTodayStr() {
      try {
        const formatter = new Intl.DateTimeFormat('en-CA', {
          timeZone: 'Asia/Jakarta',
          year: 'numeric',
          month: '2-digit',
          day: '2-digit'
        });
        return formatter.format(new Date());
      } catch (e) {
        const d = new Date();
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
      }
    }

    function toDateString(y, m, d) {
      const mm = String(m + 1).padStart(2, '0');
      const dd = String(d).padStart(2, '0');
      return `${y}-${mm}-${dd}`;
    }

    const todayStr = getJakartaTodayStr();
    const todayParts = todayStr.split('-');
    let currentYear = parseInt(todayParts[0], 10);
    let currentMonth = parseInt(todayParts[1], 10) - 1; // 0 = Jan, 11 = Des

    let selectedDate = todayStr;
    window.currentSelectedCalendarDate = selectedDate;

    // Elemen DOM kalender
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

    // Format judul tanggal Indonesia
    function formatIndonesianHeading(dateStr) {
      const parts = dateStr.split('-');
      if (parts.length !== 3) return dateStr;
      const y = parseInt(parts[0], 10);
      const m = parseInt(parts[1], 10) - 1;
      const d = parseInt(parts[2], 10);

      const dt = new Date(y, m, d, 12, 0, 0);
      const dayName = dayNames[dt.getDay()];
      const monthName = monthNames[m];
      const fullDate = `${d} ${monthName} ${y}`;

      const curToday = getJakartaTodayStr();
      if (dateStr === curToday) return `Hari ini, ${fullDate}`;

      const todayDt = new Date(curToday + 'T12:00:00');
      const diffDays = Math.round((dt - todayDt) / (1000 * 60 * 60 * 24));
      if (diffDays === 1) return `Besok, ${fullDate}`;
      if (diffDays === -1) return `Kemarin, ${fullDate}`;

      return `${dayName}, ${fullDate}`;
    }

    // Tampilkan kotak-kotak tanggal kalender
    function renderCalendar() {
      if (!daysGrid) return;
      daysGrid.innerHTML = '';

      if (monthTitle) {
        monthTitle.textContent = `${monthNames[currentMonth]} ${currentYear}`;
      }

      const firstDayObj = new Date(currentYear, currentMonth, 1);
      let startDayOfWeek = firstDayObj.getDay() - 1; // Senin = 0, ..., Minggu = 6
      if (startDayOfWeek === -1) startDayOfWeek = 6;

      const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
      const prevMonthDays = new Date(currentYear, currentMonth, 0).getDate();

      let monthTasksTotal = 0;

      // 1. Kotak tanggal bulan sebelumnya
      for (let i = startDayOfWeek - 1; i >= 0; i--) {
        const prevDayNum = prevMonthDays - i;
        const prevMonthVal = currentMonth === 0 ? 11 : currentMonth - 1;
        const prevYearVal = currentMonth === 0 ? currentYear - 1 : currentYear;
        const dateStr = toDateString(prevYearVal, prevMonthVal, prevDayNum);

        const cell = createDayCell(prevDayNum, dateStr, true);
        daysGrid.appendChild(cell);
      }

      // 2. Kotak tanggal bulan sekarang
      for (let day = 1; day <= daysInMonth; day++) {
        const dateStr = toDateString(currentYear, currentMonth, day);
        const dayTasks = tasksByDate[dateStr] || [];
        monthTasksTotal += dayTasks.length;

        const cell = createDayCell(day, dateStr, false);
        daysGrid.appendChild(cell);
      }

      // 3. Kotak tanggal bulan berikutnya agar genap 7 kolom
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

      renderSelectedDateTasks(); // Tampilkan detail tugas tanggal terpilih
    }

    // Buat satu kotak tanggal
    function createDayCell(dayNum, dateStr, isOtherMonth) {
      const cell = document.createElement('div');
      cell.className = 'editorial-day-cell';
      cell.setAttribute('data-date', dateStr);
      if (isOtherMonth) cell.classList.add('is-other-month');
      if (dateStr === todayStr) cell.classList.add('is-today');
      if (dateStr === selectedDate) cell.classList.add('is-selected');

      const dayTasks = tasksByDate[dateStr] || [];

      // Header kotak: nomor tanggal & titik warna indikator
      const headerDiv = document.createElement('div');
      headerDiv.className = 'day-cell-header';

      const numSpan = document.createElement('span');
      numSpan.className = 'day-cell-number';
      numSpan.textContent = dayNum;
      headerDiv.appendChild(numSpan);

      // Titik indikator status tugas
      if (dayTasks.length > 0) {
        const indicators = document.createElement('div');
        indicators.className = 'day-indicators';

        const hasOverdue = dayTasks.some(t => t.status !== 'completed' && dateStr < todayStr);
        const hasToday = dayTasks.some(t => t.status !== 'completed' && dateStr === todayStr);
        const hasUpcoming = dayTasks.some(t => t.status !== 'completed' && dateStr > todayStr);
        const hasCompleted = dayTasks.some(t => t.status === 'completed');

        if (hasOverdue) {
          const dot = document.createElement('span');
          dot.className = 'cal-dot dot-overdue';
          dot.title = 'Tugas aktif terlewat';
          indicators.appendChild(dot);
        }

        if (hasToday) {
          const dot = document.createElement('span');
          dot.className = 'cal-dot dot-today';
          dot.title = 'Tugas aktif hari ini';
          indicators.appendChild(dot);
        }

        if (hasUpcoming) {
          const dot = document.createElement('span');
          dot.className = 'cal-dot dot-upcoming';
          dot.title = 'Tugas aktif mendatang';
          indicators.appendChild(dot);
        }

        if (hasCompleted) {
          const dot = document.createElement('span');
          dot.className = 'cal-dot dot-completed';
          dot.title = 'Tugas selesai';
          indicators.appendChild(dot);
        }

        headerDiv.appendChild(indicators);
      }

      cell.appendChild(headerDiv);

      // Footer kotak: badge jumlah tugas
      const footerDiv = document.createElement('div');
      footerDiv.className = 'day-cell-footer';

      if (dayTasks.length > 0) {
        const badge = document.createElement('span');
        badge.className = 'day-task-count';

        const hasOverdue = dayTasks.some(t => t.status !== 'completed' && dateStr < todayStr);
        const hasToday = dayTasks.some(t => t.status !== 'completed' && dateStr === todayStr);
        const allCompleted = dayTasks.every(t => t.status === 'completed');

        if (hasOverdue) {
          badge.classList.add('count-overdue');
        } else if (hasToday) {
          badge.classList.add('count-today');
        } else if (allCompleted) {
          badge.classList.add('count-completed');
        } else {
          badge.classList.add('count-upcoming');
        }

        badge.textContent = `${dayTasks.length} tugas`;
        footerDiv.appendChild(badge);
      }

      cell.appendChild(footerDiv);

      // Event saat kotak tanggal diklik
      cell.addEventListener('click', function () {
        selectedDate = dateStr;
        window.currentSelectedCalendarDate = selectedDate;

        document.querySelectorAll('.editorial-day-cell').forEach(c => c.classList.remove('is-selected'));
        cell.classList.add('is-selected');

        renderSelectedDateTasks();
      });

      return cell;
    }

    // Tampilkan daftar tugas di panel samping kalender
    function renderSelectedDateTasks() {
      if (!selectedDateTitle || !selectedDateTasksList) return;

      selectedDateTitle.textContent = formatIndonesianHeading(selectedDate);
      selectedDateTasksList.innerHTML = '';

      const dayTasks = tasksByDate[selectedDate] || [];

      // Update badge jumlah tugas di judul panel
      const panelHeaderTitles = document.querySelector('.panel-header-titles');
      if (panelHeaderTitles) {
        let countBadge = panelHeaderTitles.querySelector('.panel-task-counter');
        if (!countBadge) {
          countBadge = document.createElement('span');
          countBadge.className = 'panel-task-counter';
          panelHeaderTitles.appendChild(countBadge);
        }
        countBadge.textContent = `${dayTasks.length} Tugas Tercatat`;
      }

      // Tampilan jika tidak ada tugas di tanggal terpilih
      if (dayTasks.length === 0) {
        selectedDateTasksList.innerHTML = `
          <div class="panel-empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            <h5>Belum Ada Tugas</h5>
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

      // Format jam ke format WIB (misal: 14.30 WIB)
      function formatClockWIB(timeStr) {
        if (!timeStr) return '';
        const parts = timeStr.trim().split(':');
        if (parts.length < 2) return timeStr;
        const hh = parts[0].padStart(2, '0');
        const mm = parts[1].padStart(2, '0');
        return `${hh}.${mm} WIB`;
      }

      // Urutkan tugas: jam lebih awal di atas, tugas tanpa jam di bawah
      const sortedDayTasks = [...dayTasks].sort((a, b) => {
        const timeA = (a.due_time && a.due_time.trim() !== '') ? a.due_time.substring(0, 5) : '99:99';
        const timeB = (b.due_time && b.due_time.trim() !== '') ? b.due_time.substring(0, 5) : '99:99';

        if (timeA !== timeB) return timeA.localeCompare(timeB);
        return (parseInt(a.sort_order, 10) || 0) - (parseInt(b.sort_order, 10) || 0);
      });

      // Buat elemen kartu tugas di panel
      sortedDayTasks.forEach(task => {
        const isCompleted = task.status === 'completed';
        const priorityLabels = { low: 'Rendah', medium: 'Sedang', high: 'Tinggi' };
        const statusLabels = { pending: 'Belum Dikerjakan', in_progress: 'Sedang Dikerjakan', completed: 'Selesai' };

        const timeBadgeHtml = (task.due_time && task.due_time.trim() !== '') ? `
          <span class="panel-time-badge" title="Jam Pengumpulan">
            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            ${formatClockWIB(task.due_time)}
          </span>
        ` : '';

        const taskItem = document.createElement('div');
        taskItem.className = `panel-task-card ${isCompleted ? 'is-completed' : ''}`;

        taskItem.innerHTML = `
          <div class="panel-card-top">
            <div class="panel-badges">
              <span class="subject-badge">${escapeHtml(task.subject_name)}</span>
              ${timeBadgeHtml}
            </div>
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
                      data-due-time="${escapeHtml(task.due_time || '')}"
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

    // Buka drawer form tambah dengan tanggal yang sedang dipilih di kalender
    function openAddDrawerWithDate() {
      if (addTaskForm) {
        addTaskForm.reset();
        const titleInput = document.getElementById('addTitle');
        const descInput = document.getElementById('addDescription');
        const dueTimeInput = document.getElementById('addDueTime');
        if (titleInput) titleInput.value = '';
        if (descInput) descInput.value = '';
        if (dueTimeInput) dueTimeInput.value = '';
      }
      const dueDateInput = document.getElementById('addDueDate');
      if (dueDateInput) {
        dueDateInput.value = selectedDate;
      }

      openDrawer(addTaskDrawer);
      const titleInput = document.getElementById('addTitle');
      if (titleInput) setTimeout(() => titleInput.focus(), 150);
    }

    if (btnAddTaskOnDate) {
      btnAddTaskOnDate.addEventListener('click', openAddDrawerWithDate);
    }

    // Tombol pindah bulan (Sebelumnya & Berikutnya) & Tombol Hari Ini
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
        const curToday = getJakartaTodayStr();
        const parts = curToday.split('-');
        currentYear = parseInt(parts[0], 10);
        currentMonth = parseInt(parts[1], 10) - 1;
        selectedDate = curToday;
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

    // Render kalender saat halaman pertama kali dibuka
    renderCalendar();
  }
});
