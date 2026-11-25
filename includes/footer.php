<?php
// FOOTER, DRAWER FORMULIR & MODAL KONFIRMASI HAPUS 
$currentView = $currentView ?? 'all';
$modalSubjects = getAllSubjects($pdo);
?>
    </div> <!-- .editorial-container -->
  </main> <!-- .editorial-main-content -->
</div> <!-- .editorial-site-wrapper -->

<!-- 1. DRAWER FORM TAMBAH TUGAS (POST ke create.php) -->
<div class="drawer-overlay" id="addTaskDrawer" role="dialog" aria-modal="true" aria-labelledby="addDrawerTitle">
  <div class="drawer-backdrop js-close-drawer"></div> <!-- Latar gelap untuk tutup drawer -->

  <aside class="drawer-panel">
    <div class="drawer-header">
      <div class="drawer-header-text">
        <h3 id="addDrawerTitle" class="drawer-title">Tambah Tugas</h3>
        <p class="drawer-subtitle">Catat tugas baru dan atur jadwal pengerjaannya</p>
      </div>
      <button type="button" class="drawer-close-btn js-close-drawer" aria-label="Tutup Panel">&times;</button>
    </div>

    <!-- Form input tambah tugas -->
    <form id="addTaskForm" action="create.php" method="POST" class="drawer-form">
      <input type="hidden" name="view" value="<?= e($currentView) ?>">

      <div class="drawer-body">
        <!-- Input nama tugas -->
        <div class="form-group">
          <label class="form-label" for="addTitle">Nama Tugas <span class="required">*</span></label>
          <input type="text" id="addTitle" name="title" class="form-control" required maxlength="150" autocomplete="off">
        </div>

        <!-- Pilihan mata pelajaran -->
        <div class="form-group">
          <div class="form-label-row">
            <label class="form-label" for="addSubjectId">Mata Pelajaran <span class="required">*</span></label>
            <button type="button" class="form-action-link js-open-subject-modal" title="Tambah Mata Pelajaran Baru">+ Tambah Mapel</button>
          </div>
          <div class="select-wrapper">
            <select id="addSubjectId" name="subject_id" class="form-control" required>
              <option value="">Pilih Mata Pelajaran</option>
              <?php foreach ($modalSubjects as $subj): ?>
                <option value="<?= (int)$subj['id'] ?>"><?= e($subj['subject_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Input deskripsi tugas -->
        <div class="form-group">
          <label class="form-label" for="addDescription">Deskripsi</label>
          <textarea id="addDescription" name="description" class="form-control" rows="4"></textarea>
        </div>

        <!-- Input tanggal & jam pengumpulan -->
        <div class="form-grid-2 deadline-grid">
          <!-- Tanggal tenggat -->
          <div class="form-group">
            <label class="form-label" for="addDueDate">Tanggal Pengumpulan</label>
            <input type="date" id="addDueDate" name="due_date" class="form-control">
          </div>

          <!-- Jam tenggat -->
          <div class="form-group">
            <label class="form-label" for="addDueTime">
              Jam Pengumpulan <span class="label-optional">(Opsional)</span>
            </label>
            <input type="time" id="addDueTime" name="due_time" class="form-control" placeholder="14:30">
          </div>
        </div>

        <!-- Input prioritas & status pengerjaan -->
        <div class="form-grid-2">
          <!-- Prioritas -->
          <div class="form-group">
            <label class="form-label" for="addPriority">Prioritas</label>
            <div class="select-wrapper">
              <select id="addPriority" name="priority" class="form-control">
                <option value="low">Rendah</option>
                <option value="medium" selected>Sedang</option>
                <option value="high">Tinggi</option>
              </select>
            </div>
          </div>

          <!-- Status pengerjaan -->
          <div class="form-group">
            <label class="form-label" for="addStatus">Status Pengerjaan</label>
            <div class="select-wrapper">
              <select id="addStatus" name="status" class="form-control">
                <option value="pending" selected>Belum Dikerjakan</option>
                <option value="in_progress">Sedang Dikerjakan</option>
                <option value="completed">Selesai</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Tombol aksi Simpan & Batal -->
      <div class="drawer-footer">
        <button type="button" class="btn btn-editorial-outline js-close-drawer">Batal</button>
        <button type="submit" id="addSubmitBtn" class="btn btn-editorial-primary">Simpan Tugas</button>
      </div>
    </form>
  </aside>
</div>

<!-- === 2. DRAWER FORM EDIT TUGAS (POST ke update.php) === -->
<div class="drawer-overlay" id="editTaskDrawer" role="dialog" aria-modal="true" aria-labelledby="editDrawerTitle">
  <div class="drawer-backdrop js-close-drawer"></div>

  <aside class="drawer-panel">
    <div class="drawer-header">
      <div class="drawer-header-text">
        <h3 id="editDrawerTitle" class="drawer-title">Edit Tugas</h3>
        <p class="drawer-subtitle">Perbarui informasi dan jadwal pengerjaan tugas</p>
      </div>
      <button type="button" class="drawer-close-btn js-close-drawer" aria-label="Tutup Panel">&times;</button>
    </div>

    <!-- Form update tugas -->
    <form id="editTaskForm" action="update.php" method="POST" class="drawer-form">
      <input type="hidden" name="id" id="editTaskId"> <!-- Hidden ID tugas yang diedit -->
      <input type="hidden" name="view" value="<?= e($currentView) ?>">

      <div class="drawer-body">
        <!-- Edit nama tugas -->
        <div class="form-group">
          <label class="form-label" for="editTitle">Nama Tugas <span class="required">*</span></label>
          <input type="text" id="editTitle" name="title" class="form-control" required maxlength="150" autocomplete="off">
        </div>

        <!-- Edit mata pelajaran -->
        <div class="form-group">
          <div class="form-label-row">
            <label class="form-label" for="editSubjectId">Mata Pelajaran <span class="required">*</span></label>
            <button type="button" class="form-action-link js-open-subject-modal" title="Tambah Mata Pelajaran Baru">+ Tambah Mapel</button>
          </div>
          <div class="select-wrapper">
            <select id="editSubjectId" name="subject_id" class="form-control" required>
              <option value="">Pilih Mata Pelajaran</option>
              <?php foreach ($modalSubjects as $subj): ?>
                <option value="<?= (int)$subj['id'] ?>"><?= e($subj['subject_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Edit deskripsi -->
        <div class="form-group">
          <label class="form-label" for="editDescription">Deskripsi</label>
          <textarea id="editDescription" name="description" class="form-control" rows="4"></textarea>
        </div>

        <!-- Edit tanggal & jam pengumpulan -->
        <div class="form-grid-2 deadline-grid">
          <div class="form-group">
            <label class="form-label" for="editDueDate">Tanggal Pengumpulan</label>
            <input type="date" id="editDueDate" name="due_date" class="form-control">
          </div>

          <div class="form-group">
            <label class="form-label" for="editDueTime">
              Jam Pengumpulan <span class="label-optional">(Opsional)</span>
            </label>
            <input type="time" id="editDueTime" name="due_time" class="form-control" placeholder="14:30">
          </div>
        </div>

        <!-- Edit prioritas & status -->
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label" for="editPriority">Prioritas</label>
            <div class="select-wrapper">
              <select id="editPriority" name="priority" class="form-control">
                <option value="low">Rendah</option>
                <option value="medium">Sedang</option>
                <option value="high">Tinggi</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="editStatus">Status Pengerjaan</label>
            <div class="select-wrapper">
              <select id="editStatus" name="status" class="form-control">
                <option value="pending">Belum Dikerjakan</option>
                <option value="in_progress">Sedang Dikerjakan</option>
                <option value="completed">Selesai</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Tombol aksi Simpan Perubahan & Batal -->
      <div class="drawer-footer">
        <button type="button" class="btn btn-editorial-outline js-close-drawer">Batal</button>
        <button type="submit" id="editSubmitBtn" class="btn btn-editorial-primary">Simpan Perubahan</button>
      </div>
    </form>
  </aside>
</div>

<!-- === 3. MODAL POPUP KONFIRMASI HAPUS TUGAS (POST ke delete.php) === -->
<div class="editorial-modal-overlay" id="deleteTaskModal" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
  <div class="editorial-modal-container">
    <div class="editorial-modal-header">
      <h3 id="deleteModalTitle" class="editorial-modal-title delete-title">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
          <line x1="12" y1="9" x2="12" y2="13"></line>
          <line x1="12" y1="17" x2="12.01" y2="17"></line>
        </svg>
        <span>Hapus Tugas</span>
      </h3>
      <button type="button" class="drawer-close-btn js-close-modal" aria-label="Tutup Dialog">&times;</button>
    </div>

    <!-- Form konfirmasi hapus -->
    <form id="deleteTaskForm" action="delete.php" method="POST">
      <input type="hidden" name="id" id="deleteTaskId"> <!-- Hidden ID tugas yang dihapus -->
      <input type="hidden" name="view" value="<?= e($currentView) ?>">

      <div class="editorial-modal-body">
        <p class="delete-confirm-text">
          Apakah Anda yakin ingin menghapus tugas <strong id="deleteTaskTitle"></strong>?
        </p>
        <p class="delete-note-text">
          Tindakan ini tidak dapat dibatalkan dan data tugas akan dihapus dari daftar.
        </p>
      </div>

      <!-- Tombol aksi Hapus Tugas & Batal -->
      <div class="editorial-modal-footer">
        <button type="button" class="btn btn-editorial-outline js-close-modal">Batal</button>
        <button type="submit" id="deleteSubmitBtn" class="btn btn-editorial-danger">Hapus Tugas</button>
      </div>
    </form>
  </div>
</div>

<!-- === 4. MODAL TAMBAH MATA PELAJARAN (POST ke create_subject.php) === -->
<div class="editorial-modal-overlay" id="addSubjectModal" role="dialog" aria-modal="true" aria-labelledby="addSubjectModalTitle">
  <div class="editorial-modal-container">
    <div class="editorial-modal-header">
      <h3 id="addSubjectModalTitle" class="editorial-modal-title">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
          <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
        </svg>
        <span>Tambah Mata Pelajaran</span>
      </h3>
      <button type="button" class="drawer-close-btn js-close-modal" aria-label="Tutup Dialog">&times;</button>
    </div>

    <!-- Form simpan mata pelajaran baru -->
    <form id="addSubjectForm" action="create_subject.php" method="POST">
      <input type="hidden" name="view" value="<?= e($currentView) ?>">

      <div class="editorial-modal-body">
        <div class="form-group" style="margin-bottom: 0;">
          <label class="form-label" for="newSubjectName">Nama Mata Pelajaran <span class="required">*</span></label>
          <input type="text" id="newSubjectName" name="subject_name" class="form-control" required maxlength="80" placeholder="Contoh: Fisika, Biologi, Kimia, dll." autocomplete="off">
          <small class="form-help" style="display:block; margin-top: 6px; font-size: 12px; color: var(--text-subtle);">Mata pelajaran akan otomatis tersimpan dan dapat langsung dipilih untuk tugas.</small>
          <div id="subjectFormFeedback" style="display: none; margin-top: 8px; font-size: 13px; font-weight: 500;"></div>
        </div>
      </div>

      <!-- Tombol aksi Simpan Mapel & Batal -->
      <div class="editorial-modal-footer">
        <button type="button" class="btn btn-editorial-outline js-close-modal">Batal</button>
        <button type="submit" id="addSubjectSubmitBtn" class="btn btn-editorial-primary">Simpan Mapel</button>
      </div>
    </form>
  </div>
</div>

<!-- Library SortableJS (Drag & Drop) -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<!-- File JavaScript Utama -->
<script src="assets/js/script.js"></script>
</body>
</html>
