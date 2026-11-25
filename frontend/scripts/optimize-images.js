const fs = require('fs');
const path = require('path');
const sharp = require('sharp');

const TARGET_DIRS = [
  path.join(__dirname, '../public/images'),
  path.join(__dirname, '../../backend/public/images')
];

async function getFiles(dir) {
  let results = [];
  if (!fs.existsSync(dir)) return results;
  const list = fs.readdirSync(dir);
  for (const file of list) {
    const fullPath = path.join(dir, file);
    const stat = fs.statSync(fullPath);
    if (stat && stat.isDirectory()) {
      results = results.concat(await getFiles(fullPath));
    } else {
      results.push(fullPath);
    }
  }
  return results;
}

async function optimizeFile(file) {
  const ext = path.extname(file).toLowerCase();
  if (!['.jpg', '.jpeg', '.png'].includes(ext)) return;

  const stat = fs.statSync(file);
  const sizeKB = stat.size / 1024;

  // Only optimize if larger than 120KB
  if (sizeKB <= 120) return;

  try {
    const inputBuffer = fs.readFileSync(file);
    const meta = await sharp(inputBuffer).metadata();
    let pipeline = sharp(inputBuffer);

    // Limit maximum dimension to 1440px for web performance
    if (meta.width > 1440 || meta.height > 1440) {
      pipeline = pipeline.resize({
        width: meta.width >= meta.height ? 1440 : undefined,
        height: meta.height > meta.width ? 1440 : undefined,
        withoutEnlargement: true
      });
    }

    let buffer;
    if (ext === '.png') {
      buffer = await pipeline
        .png({ quality: 80, compressionLevel: 9, adaptiveFiltering: true })
        .toBuffer();
    } else {
      buffer = await pipeline
        .jpeg({ quality: 80, mozjpeg: true, progressive: true })
        .toBuffer();
    }

    if (buffer.length < stat.size) {
      fs.writeFileSync(file, buffer);
      const newSizeKB = buffer.length / 1024;
      const saved = ((sizeKB - newSizeKB) / sizeKB * 100).toFixed(1);
      console.log(`✅ ${path.basename(file)}: ${sizeKB.toFixed(1)}KB -> ${newSizeKB.toFixed(1)}KB (-${saved}%)`);
    }
  } catch (err) {
    console.warn(`⚠️ Error on ${path.basename(file)}:`, err.message);
  }
}

async function main() {
  console.log('🚀 Running Buffer-Based Image Optimization...');
  for (const dir of TARGET_DIRS) {
    console.log(`Scanning: ${dir}`);
    const files = await getFiles(dir);
    for (const f of files) {
      await optimizeFile(f);
    }
  }
  console.log('✨ All images optimized!');
}

main();
