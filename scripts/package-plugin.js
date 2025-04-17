/**
 * WordPress Plugin Packaging Script
 * 
 * This script packages the BookGPT WordPress plugin into a distributable ZIP file.
 * It copies only the necessary files and excludes development files.
 */

const fs = require('fs');
const path = require('path');
const archiver = require('archiver');

// Configuration
const pluginName = 'bookgpt-wp';
const sourceDir = path.join(__dirname, '..', 'wp-plugin');
const outputDir = path.join(__dirname, '..', 'dist');
const outputFile = path.join(outputDir, `${pluginName}.zip`);

// Ensure output directory exists
if (!fs.existsSync(outputDir)) {
  fs.mkdirSync(outputDir, { recursive: true });
}

// Create output stream
const output = fs.createWriteStream(outputFile);
const archive = archiver('zip', {
  zlib: { level: 9 } // Maximum compression
});

// Set up the archiver events
output.on('close', () => {
  console.log(`Plugin packaging complete: ${outputFile}`);
  console.log(`Final size: ${(archive.pointer() / 1024 / 1024).toFixed(2)} MB`);
});

output.on('end', () => {
  console.log('Data has been drained');
});

archive.on('warning', (err) => {
  if (err.code === 'ENOENT') {
    console.warn('Warning during archive creation:', err);
  } else {
    throw err;
  }
});

archive.on('error', (err) => {
  throw err;
});

// Pipe archive data to the output file
archive.pipe(output);

// Function to copy the Vercel-hosted script URLs to the plugin config
async function updateDeploymentConfig() {
  try {
    // This would typically read from a Vercel deployment info source
    // For now, we'll just use placeholder values that would be replaced by your CI process
    const apiUrl = process.env.VERCEL_URL 
      ? `https://${process.env.VERCEL_URL}/api/chat`
      : 'https://bookgpt.vercel.app/api/chat';
    
    const webhookSecret = process.env.WEBHOOK_SECRET || '';
    
    const configPath = path.join(sourceDir, 'deployment_config.json');
    const config = {
      api_url: apiUrl,
      webhook_secret: webhookSecret
    };
    
    fs.writeFileSync(configPath, JSON.stringify(config, null, 2));
    console.log(`Updated deployment config with API URL: ${apiUrl}`);
    
    return true;
  } catch (error) {
    console.error('Error updating deployment config:', error);
    return false;
  }
}

// Function to recursively add directory contents to the archive
function addDirectoryToArchive(directoryPath, archivePath) {
  const files = fs.readdirSync(directoryPath);
  
  for (const file of files) {
    const filePath = path.join(directoryPath, file);
    const stat = fs.statSync(filePath);
    
    if (stat.isDirectory()) {
      addDirectoryToArchive(filePath, path.join(archivePath, file));
    } else {
      // Skip ignored files
      if (shouldIgnoreFile(filePath)) continue;
      
      archive.file(filePath, { name: path.join(archivePath, file) });
      console.log(`Added: ${path.join(archivePath, file)}`);
    }
  }
}

// Function to determine if a file should be ignored
function shouldIgnoreFile(filePath) {
  const ignoredExtensions = ['.git', '.DS_Store', '.gitignore', '.vscode'];
  const ignoredFiles = ['deploy.sh.sample', '.git'];
  
  const fileName = path.basename(filePath);
  const ext = path.extname(filePath);
  
  return ignoredExtensions.includes(ext) || ignoredFiles.includes(fileName);
}

// Main execution
async function packPlugin() {
  console.log('Packaging BookGPT WordPress Plugin...');
  
  // Update deployment configuration with Vercel URLs
  await updateDeploymentConfig();
  
  // Add entire plugin directory to the archive
  addDirectoryToArchive(sourceDir, pluginName);
  
  // Finalize the archive
  archive.finalize();
}

packPlugin().catch(err => {
  console.error('Error packaging plugin:', err);
  process.exit(1);
});