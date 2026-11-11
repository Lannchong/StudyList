const { spawn, execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

// Port & Host configuration
const HOST = '127.0.0.1';
const PORT = 8000;
const PROJECT_ROOT = path.resolve(__dirname, '..');

/**
 * Find PHP executable path
 * Checks:
 * 1. process.env.PHP_BINARY
 * 2. C:\xampp\php\php.exe
 * 3. D:\xampp\php\php.exe
 * 4. System PATH 'php'
 * 5. Laragon PHP paths (as helpful fallback)
 */
function findPhpBinary() {
  // 1. Check environment variable PHP_BINARY
  if (process.env.PHP_BINARY && fs.existsSync(process.env.PHP_BINARY)) {
    return process.env.PHP_BINARY;
  }

  // 2. Check C:\xampp\php\php.exe
  const xamppC = 'C:\\xampp\\php\\php.exe';
  if (fs.existsSync(xamppC)) {
    return xamppC;
  }

  // 3. Check D:\xampp\php\php.exe
  const xamppD = 'D:\\xampp\\php\\php.exe';
  if (fs.existsSync(xamppD)) {
    return xamppD;
  }

  // 4. Check Windows PATH using 'php'
  try {
    const testOutput = execSync('php -v', { stdio: ['pipe', 'pipe', 'pipe'] }).toString();
    if (testOutput.toLowerCase().includes('php')) {
      return 'php';
    }
  } catch (err) {
    // php not found in PATH
  }

  // 5. Additional fallback: Laragon PHP paths if present
  const laragonPhpDir = 'C:\\laragon\\bin\\php';
  if (fs.existsSync(laragonPhpDir)) {
    try {
      const subdirs = fs.readdirSync(laragonPhpDir);
      for (const subdir of subdirs) {
        const candidate = path.join(laragonPhpDir, subdir, 'php.exe');
        if (fs.existsSync(candidate)) {
          return candidate;
        }
      }
    } catch (e) {
      // ignore
    }
  }

  return null;
}

const phpPath = findPhpBinary();

if (!phpPath) {
  console.error('\n======================================================');
  console.error(' [ERROR] PHP Executable Tidak Ditemukan!');
  console.error('======================================================');
  console.error('StudyList membutuhkan PHP untuk menjalankan backend.');
  console.error('Silakan pastikan salah satu dari hal berikut:');
  console.error('  1. XAMPP terpasang di C:\\xampp atau D:\\xampp.');
  console.error('  2. PHP sudah ditambahkan ke System Environment PATH Windows.');
  console.error('  3. Atau set variable lingkungan PHP_BINARY ke file php.exe Anda.');
  console.error('======================================================\n');
  process.exit(1);
}

console.log('\n======================================================');
console.log('           STUDYLIST — SCHOOL TASK MANAGER           ');
console.log('======================================================');
console.log(` -> PHP Binary   : ${phpPath}`);
console.log(` -> Document Root: ${PROJECT_ROOT}`);
console.log(` -> Server URL   : http://${HOST}:${PORT}`);
console.log('======================================================');
console.log(' Starting PHP built-in web server...\n');

// Start PHP built-in server without shell to properly handle paths with spaces
const server = spawn(phpPath, ['-S', `${HOST}:${PORT}`, '-t', PROJECT_ROOT], {
  cwd: PROJECT_ROOT,
  stdio: 'inherit',
  shell: false
});

server.on('error', (err) => {
  console.error(`[ERROR] Gagal menjalankan PHP server: ${err.message}`);
  process.exit(1);
});

server.on('close', (code) => {
  if (code !== 0 && code !== null) {
    console.log(`\nPHP Server dihentikan dengan kode: ${code}`);
  } else {
    console.log('\nPHP Server telah dihentikan.');
  }
});

// Graceful shutdown handling for Ctrl+C (SIGINT / SIGTERM)
function handleShutdown() {
  console.log('\n[INFO] Menghentikan server StudyList...');
  if (server) {
    if (process.platform === 'win32' && server.pid) {
      try {
        execSync(`taskkill /pid ${server.pid} /T /F`, { stdio: 'ignore' });
      } catch (e) {
        server.kill();
      }
    } else {
      server.kill('SIGINT');
    }
  }
  process.exit(0);
}

process.on('SIGINT', handleShutdown);
process.on('SIGTERM', handleShutdown);
