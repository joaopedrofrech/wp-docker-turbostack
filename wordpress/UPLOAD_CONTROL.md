# Upload Control Configuration

## Overview
The stack is configured with conservative, WordPress-standard upload limits:

- **📁 Backup files**: 100MB (PHP limit - sufficient for most WordPress sites)
- **🖼️ Images & Documents**: 1MB maximum (JPG, PNG, GIF, WebP, SVG, PDF, DOC, DOCX)
- **🎥 Videos**: Blocked with redirect to YouTube

## How it works

### Unified 1MB Limit
Images and documents are limited to 1MB with helpful error messages for optimization.

**Supported file types:**
- **Images**: JPEG, PNG, GIF, WebP, SVG
- **Documents**: PDF, DOC, DOCX

### Backup Plugin Support
Backup plugins can upload files up to 100MB through PHP settings.

- **📁 Backup files**: 100MB (PHP limit - sufficient for most WordPress sites)
- **🖼️ Gallery images**: 1MB maximum (automatically enforced)
- **📄 Documents (PDF, DOC)**: 5MB maximum
- **🎥 Videos**: Blocked with redirect to YouTube

## How it works

### Image Upload Limit (1MB)
Images uploaded through Media Library are automatically limited to 1MB with a helpful error message encouraging compression.

**Supported image types:**
- JPEG/JPG
- PNG  
- GIF
- WebP

### Backup Plugin Support
Backup plugins like All-in-One WP Migration can upload files up to 500MB, which covers most website backups.

### Performance Optimizations
The configuration also includes:
- ✅ Automatic JPEG quality optimization (85%)
- ✅ Media trash management (30-day auto-cleanup)
- ✅ Removed emoji scripts (faster loading)
- ✅ Disabled unnecessary WordPress features

## Plugin Recommendations

### For Image Compression:
- **Smush** - Automatic image compression
- **ShortPixel** - Advanced compression with WebP
- **Imagify** - Real-time optimization

### For Advanced Upload Control:
- **Upload Max File Size** - Fine-tune limits per user role
- **File Upload Types** - Control allowed file extensions

## Troubleshooting

### "File too large" error
Users will see: *"Files must be smaller than 1MB. Please compress your file before uploading. See compression tools →"*

**Solutions:**
1. Click the provided link for compression tool recommendations
2. Use online compression tools
3. Install a compression plugin
4. Optimize file size before upload

### Backup plugin upload fails
If 500MB isn't enough for your backups:
1. Edit `wp-config-optimizations.php`
2. Change: `500 * 1024 * 1024` to `1024 * 1024 * 1024` (1GB)
3. Also update `wordpress/php.ini` to match

## Security Benefits
- Prevents large file attacks
- Reduces server storage abuse
- Improves website performance
- Maintains good user experience
