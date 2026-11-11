<?php
/**
 * Footer Component & Drawers — Editorial Study Planner
 * StudyList — School Task Manager
 */
$currentView = $currentView ?? 'overview';
$modalSubjects = getAllSubjects($pdo);
?>
    </div> <!-- .editorial-container -->
  </main> <!-- .editorial-main-content -->
</div> <!-- .editorial-site-wrapper -->

<!-- ========================================== -->
<!-- RIGHT SIDE DRAWER: TAMBAH TUGAS            -->
<!-- ========================================== -->
<div class="drawer-overlay" id="addTaskDrawer" role="dialog" aria-modal="true" aria-labelledby="addDrawerTitle">
  <div class="drawer-backdrop js-close-drawer"></div>
  <aside class="drawer-panel">
    <div class="drawer-header">
      <div class="drawer-header-text">
        <h3 id="addDrawerTitle" class="drawer-title">Tambah Tugas</h3>
        <p class="drawer-subtitle">Catat tugas baru dan atur jadwal pengerjaannya</p>
      </div>
      <button type="button" class="drawer-close-btn js-close-drawer" aria-label="Tutup Panel">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>

    <form id="addTaskForm" action="create.php" method="POST" class="drawer-form">
      <input type="hidden" name="view" value="<?= e($currentView) ?>">

      <div class="drawer-body">
        <!-- 1. Nama Tugas -->
        <div class="form-group">
          <label class="form-label" for="addTitle">Nama Tugas <span class="required">*</span></label>
          <input type="text" id="addTitle" name="title" class="form-control" required maxlength="150" autocomplete="off">
        </div>

        <!-- 2. Mata Pelajaran -->
        <div class="form-group">
          <label class="form-label" for="addSubjectId">Mata Pelajaran <span class="required">*</span></label>
          <div class="select-wrapper">
            <select id="addSubjectId" name="subject_id" class="form-control" required>
              <option value="">Pilih Mata Pelajaran</option>
              <?php foreach ($modalSubjects as $subj): ?>
                <option value="<?= (int)$subj['id'] ?>"><?= e($subj['subject_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- 3. Deskripsi -->
        <div class="form-group">
          <label class="form-label" for="addDescription">Deskripsi</label>
          <textarea id="addDescription" name="description" class="form-control" rows="4"></textarea>
        </div>

        <!-- 4. Tanggal Pengumpulan & 5. Prioritas -->
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label" for="addDueDate">Tanggal Pengumpulan</label>
            <input type="date" id="addDueDate" name="due_date" class="form-control">
          </div>

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
        </div>

        <!-- 6. Status Pengerjaan -->
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

      <div class="drawer-footer">
        <button type="button" class="btn btn-editorial-outline js-close-drawer">Batal</button>
        <button type="submit" id="addSubmitBtn" class="btn btn-editorial-primary">Simpan Tugas</button>
      </div>
    </form>
  </aside>
</div>

<!-- ========================================== -->
<!-- RIGHT SIDE DRAWER: EDIT TUGAS              -->
<!-- ========================================== -->
<div class="drawer-overlay" id="editTaskDrawer" role="dialog" aria-modal="true" aria-labelledby="editDrawerTitle">
  <div class="drawer-backdrop js-close-drawer"></div>
  <aside class="drawer-panel">
    <div class="drawer-header">
      <div class="drawer-header-text">
        <h3 id="editDrawerTitle" class="drawer-title">Edit Tugas</h3>
        <p class="drawer-subtitle">Perbarui informasi dan jadwal pengerjaan tugas</p>
      </div>
      <button type="button" class="drawer-close-btn js-close-drawer" aria-label="Tutup Panel">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>

    <form id="editTaskForm" action="update.php" method="POST" class="drawer-form">
      <input type="hidden" name="id" id="editTaskId">
      <input type="hidden" name="view" value="<?= e($currentView) ?>">

      <div class="drawer-body">
        <!-- 1. Nama Tugas -->
        <div class="form-group">
          <label class="form-label" for="editTitle">Nama Tugas <span class="required">*</span></label>
          <input type="text" id="editTitle" name="title" class="form-control" required maxlength="150" autocomplete="off">
        </div>

        <!-- 2. Mata Pelajaran -->
        <div class="form-group">
          <label class="form-label" for="editSubjectId">Mata Pelajaran <span class="required">*</span></label>
          <div class="select-wrapper">
            <select id="editSubjectId" name="subject_id" class="form-control" required>
              <option value="">Pilih Mata Pelajaran</option>
              <?php foreach ($modalSubjects as $subj): ?>
                <option value="<?= (int)$subj['id'] ?>"><?= e($subj['subject_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- 3. Deskripsi -->
        <div class="form-group">
          <label class="form-label" for="editDescription">Deskripsi</label>
          <textarea id="editDescription" name="description" class="form-control" rows="4"></textarea>
        </div>

        <!-- 4. Tanggal Pengumpulan & 5. Prioritas -->
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label" for="editDueDate">Tanggal Pengumpulan</label>
            <input type="date" id="editDueDate" name="due_date" class="form-control">
          </div>

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
        </div>

        <!-- 6. Status Pengerjaan -->
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

      <div class="drawer-footer">
        <button type="button" class="btn btn-editorial-outline js-close-drawer">Batal</button>
        <button type="submit" id="editSubmitBtn" class="btn btn-editorial-primary">Simpan Tugas</button>
      </div>
    </form>
  </aside>
</div>

<!-- ========================================== -->
<!-- MODAL: KONFIRMASI HAPUS TUGAS              -->
<!-- ========================================== -->
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
      <button type="button" class="drawer-close-btn js-close-modal" aria-label="Tutup Dialog">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>

    <form id="deleteTaskForm" action="delete.php" method="POST">
      <input type="hidden" name="id" id="deleteTaskId">
      <input type="hidden" name="view" value="<?= e($currentView) ?>">

      <div class="editorial-modal-body">
        <p class="delete-confirm-text">
          Apakah Anda yakin ingin menghapus tugas <strong id="deleteTaskTitle"></strong>?
        </p>
        <p class="delete-note-text">
          Tindakan ini tidak dapat dibatalkan dan data tugas akan dihapus dari daftar.
        </p>
      </div>

      <div class="editorial-modal-footer">
        <button type="button" class="btn btn-editorial-outline js-close-modal">Batal</button>
        <button type="submit" id="deleteSubmitBtn" class="btn btn-editorial-danger">Hapus Tugas</button>
      </div>
    </form>
  </div>
</div>

<script src="assets/js/script.js"></script>
</body>
</html>
