const fs = require('fs');
const path = require('path');

const srcDir = path.resolve(__dirname, '../out');
const destDir = path.resolve(__dirname, '../../backend/public');

// Files and folders in Laravel public that should NEVER be deleted by frontend cleanup
const PRESERVED_ITEMS = new Set([
  'index.php',
  '.htaccess',
  '.user.ini',
  'robots.txt',
  'css',
  'js',
  'storage',
  'build',
  'images',
  'videos',
  'icons.svg',
  '.git',
  '.gitignore',
]);

// Laravel core files that should NEVER be overwritten by frontend export
const PROTECTED_LARAVEL_ROOT_FILES = new Set([
  'index.php',
  '.htaccess',
  '.user.ini',
  'storage',
  '.git',
  '.gitignore',
]);

function cleanOldFrontendArtifacts(dest) {
  if (!fs.existsSync(dest)) return;

  const entries = fs.readdirSync(dest, { withFileTypes: true });
  for (const entry of entries) {
    if (PRESERVED_ITEMS.has(entry.name)) {
      continue;
    }
    const targetPath = path.join(dest, entry.name);
    try {
      if (entry.isDirectory()) {
        fs.rmSync(targetPath, { recursive: true, force: true });
      } else {
        fs.unlinkSync(targetPath);
      }
    } catch (err) {
      console.warn(`[Clean] Could not remove ${targetPath}:`, err.message);
    }
  }
}

function copyRecursive(src, dest) {
  if (!fs.existsSync(src)) {
    console.error(`[Error] Source directory does not exist: ${src}`);
    return;
  }

  if (!fs.existsSync(dest)) {
    fs.mkdirSync(dest, { recursive: true });
  }

  const entries = fs.readdirSync(src, { withFileTypes: true });

  for (const entry of entries) {
    const srcPath = path.join(src, entry.name);
    const destPath = path.join(dest, entry.name);

    // Skip protected Laravel core files at root level
    if (PROTECTED_LARAVEL_ROOT_FILES.has(entry.name) && dest === destDir) {
      continue;
    }

    if (entry.isDirectory()) {
      copyRecursive(srcPath, destPath);
    } else {
      fs.copyFileSync(srcPath, destPath);
    }
  }
}

console.log('🔄 Cleaning old frontend static files and syncing Next.js export...');
console.log(`📂 From: ${srcDir}`);
console.log(`📁 To:   ${destDir}`);

try {
  // 1. Purge obsolete chunks and stale HTML pages
  cleanOldFrontendArtifacts(destDir);

  // 2. Copy fresh Next.js build
  copyRecursive(srcDir, destDir);
  
  // 3. Ensure public/admin directory never exists so Nginx never shadows Laravel's admin routes
  const adminDir = path.join(destDir, 'admin');
  if (fs.existsSync(adminDir)) {
    fs.rmSync(adminDir, { recursive: true, force: true });
  }
  const knotteleAdminDir = path.join(destDir, 'knottele', 'admin');
  if (fs.existsSync(knotteleAdminDir)) {
    fs.rmSync(knotteleAdminDir, { recursive: true, force: true });
  }
  
  console.log('✅ Successfully exported Next.js frontend into Laravel public directory!');
} catch (error) {
  console.error('❌ Error during sync to Laravel public:', error);
  process.exit(1);
}
