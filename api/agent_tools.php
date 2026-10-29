<?php
// api/agent_tools.php - RuXt Real System & Agentic Execution Engine (Antigravity-grade)
require_once __DIR__ . '/../config.php';

const HTDOCS_ROOT = 'C:\\xampp\\htdocs';

/**
 * Resolve and validate path within htdocs root
 */
function resolveHtdocsPath($path) {
    $clean = trim($path, " \t\n\r\0\x0B");
    
    // Normalize slashes
    $clean = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $clean);
    
    // Strip leading htdocs or c:\xampp\htdocs if included
    $prefixPatterns = [
        'C:' . DIRECTORY_SEPARATOR . 'xampp' . DIRECTORY_SEPARATOR . 'htdocs' . DIRECTORY_SEPARATOR,
        'c:' . DIRECTORY_SEPARATOR . 'xampp' . DIRECTORY_SEPARATOR . 'htdocs' . DIRECTORY_SEPARATOR,
        'htdocs' . DIRECTORY_SEPARATOR
    ];
    foreach ($prefixPatterns as $pfx) {
        if (stripos($clean, $pfx) === 0) {
            $clean = substr($clean, strlen($pfx));
            break;
        }
    }
    
    $clean = ltrim($clean, DIRECTORY_SEPARATOR);
    
    // Prevent directory traversal
    $parts = explode(DIRECTORY_SEPARATOR, $clean);
    $safeParts = [];
    foreach ($parts as $part) {
        if ($part === '' || $part === '.') continue;
        if ($part === '..') {
            array_pop($safeParts);
            continue;
        }
        $safeParts[] = $part;
    }
    
    $relative = implode(DIRECTORY_SEPARATOR, $safeParts);
    $fullPath = empty($relative) ? HTDOCS_ROOT : HTDOCS_ROOT . DIRECTORY_SEPARATOR . $relative;
    
    return [
        'relative' => $relative,
        'full_path' => $fullPath
    ];
}

/**
 * Format bytes to readable size
 */
function formatSizeUnits($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 1) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' bytes';
    } elseif ($bytes == 1) {
        return '1 byte';
    } else {
        return '0 bytes';
    }
}

/**
 * Create directory on disk
 */
function agentMkdir($path) {
    $resolved = resolveHtdocsPath($path);
    $fullPath = $resolved['full_path'];
    
    if (file_exists($fullPath)) {
        return [
            'action' => 'mkdir',
            'status' => 'success',
            'path' => $resolved['relative'],
            'full_path' => $fullPath,
            'message' => "Directory already exists: {$resolved['relative']}"
        ];
    }
    
    if (@mkdir($fullPath, 0777, true)) {
        return [
            'action' => 'mkdir',
            'status' => 'success',
            'path' => $resolved['relative'],
            'full_path' => $fullPath,
            'message' => "Created directory: {$resolved['relative']}"
        ];
    }
    
    return [
        'action' => 'mkdir',
        'status' => 'error',
        'path' => $resolved['relative'],
        'message' => "Failed to create directory: {$resolved['relative']}"
    ];
}

/**
 * Write file to disk
 */
function agentWriteFile($path, $content) {
    $resolved = resolveHtdocsPath($path);
    $fullPath = $resolved['full_path'];
    
    // Ensure parent dir exists
    $parentDir = dirname($fullPath);
    if (!file_exists($parentDir)) {
        @mkdir($parentDir, 0777, true);
    }
    
    $bytes = @file_put_contents($fullPath, $content);
    if ($bytes !== false) {
        $autoLinked = agentAutoLinkRoutes($resolved['relative']);
        return [
            'action' => 'write_file',
            'status' => 'success',
            'path' => $resolved['relative'],
            'full_path' => $fullPath,
            'bytes' => $bytes,
            'size_formatted' => formatSizeUnits($bytes),
            'auto_linked' => $autoLinked,
            'message' => "Wrote {$resolved['relative']} (" . formatSizeUnits($bytes) . ")" . (!empty($autoLinked) ? " & synchronized navigation" : "")
        ];
    }
    
    return [
        'action' => 'write_file',
        'status' => 'error',
        'path' => $resolved['relative'],
        'message' => "Failed to write file: {$resolved['relative']}"
    ];
}

/**
 * Edit existing file surgically by replacing exact target text
 */
function agentEditFile($path, $target, $replacement) {
    $resolved = resolveHtdocsPath($path);
    $fullPath = $resolved['full_path'];
    
    if (!file_exists($fullPath)) {
        return [
            'action' => 'edit_file',
            'status' => 'error',
            'path' => $resolved['relative'],
            'message' => "File not found: {$resolved['relative']}"
        ];
    }
    
    $content = file_get_contents($fullPath);
    if (strpos($content, $target) === false) {
        $trimmedTarget = trim($target);
        if ($trimmedTarget !== '' && strpos($content, $trimmedTarget) !== false) {
            $content = str_replace($trimmedTarget, $replacement, $content);
        } else {
            return [
                'action' => 'edit_file',
                'status' => 'error',
                'path' => $resolved['relative'],
                'message' => "Target substring not found in {$resolved['relative']}"
            ];
        }
    } else {
        $content = str_replace($target, $replacement, $content);
    }
    
    $bytes = @file_put_contents($fullPath, $content);
    if ($bytes !== false) {
        return [
            'action' => 'edit_file',
            'status' => 'success',
            'path' => $resolved['relative'],
            'full_path' => $fullPath,
            'bytes' => $bytes,
            'size_formatted' => formatSizeUnits($bytes),
            'message' => "Successfully edited {$resolved['relative']}"
        ];
    }
    
    return [
        'action' => 'edit_file',
        'status' => 'error',
        'path' => $resolved['relative'],
        'message' => "Failed to write edited content back to {$resolved['relative']}"
    ];
}

/**
 * Automatically synchronize and link newly created or updated pages across existing project files
 */
function agentAutoLinkRoutes($relativeFilePath) {
    $normalized = str_replace('\\', '/', $relativeFilePath);
    $parts = explode('/', $normalized);
    if (count($parts) < 2) return null; // Root file
    
    $folder = $parts[0];
    $filename = end($parts);
    
    // Ignore non-HTML files or index itself
    if (!preg_match('/\.html?$/i', $filename) || preg_match('/^index\.html?$/i', $filename)) {
        return null;
    }
    
    $baseName = pathinfo($filename, PATHINFO_FILENAME);
    $titleLabel = ucwords(str_replace(['-', '_'], ' ', $baseName));
    if (strtolower($baseName) === 'about') $titleLabel = 'About Us';
    if (strtolower($baseName) === 'contact') $titleLabel = 'Contact Us';
    
    $indexPath = resolveHtdocsPath($folder . '/index.html');
    $indexFullPath = $indexPath['full_path'];
    if (!file_exists($indexFullPath)) {
        $indexPath = resolveHtdocsPath($folder . '/index.php');
        $indexFullPath = $indexPath['full_path'];
    }
    if (!file_exists($indexFullPath)) return null;
    
    $indexContent = file_get_contents($indexFullPath);
    $modified = false;
    $updatedLinks = [];
    
    // 1. Check if index already has a link to this exact file
    if (stripos($indexContent, $filename) === false) {
        // Look for anchor links pointing to placeholder hash
        $patterns = [
            '/(<a\s+[^>]*href=["\'])#(?:' . preg_quote($baseName, '/') . '|' . preg_quote(strtolower($titleLabel), '/') . '|about-us)(["\'][^>]*>)/i',
            '/(<a\s+[^>]*href=["\'])#(["\'][^>]*>\s*' . preg_quote($titleLabel, '/') . '\s*<\/a>)/i'
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $indexContent)) {
                $indexContent = preg_replace($pattern, '$1' . $filename . '$2', $indexContent, 1);
                $modified = true;
                $updatedLinks[] = "Updated existing placeholder link to {$filename}";
                break;
            }
        }
        
        // 2. If still not linked, insert into nav-links or navbar-nav
        if (stripos($indexContent, $filename) === false) {
            if (preg_match('/(<nav\b[^>]*class=["\'][^"\']*nav-links[^"\']*["\'][^>]*>)(.*?)(<\/nav>)/is', $indexContent, $navMatch)) {
                $navBody = $navMatch[2];
                $newLink = "\n                <a href=\"{$filename}\">{$titleLabel}</a>";
                if (preg_match('/(<a\b[^>]*class=["\'][^"\']*btn-[^"\']*["\'][^>]*>)/is', $navBody, $btnMatch)) {
                    $newNavBody = str_replace($btnMatch[1], $newLink . "\n                " . $btnMatch[1], $navBody);
                } else {
                    $newNavBody = $navBody . $newLink;
                }
                $indexContent = str_replace($navMatch[0], $navMatch[1] . $newNavBody . $navMatch[3], $indexContent);
                $modified = true;
                $updatedLinks[] = "Injected {$titleLabel} link into main navigation bar";
            } elseif (preg_match('/(<ul\b[^>]*class=["\'][^"\']*navbar-nav[^"\']*["\'][^>]*>)(.*?)(<\/ul>)/is', $indexContent, $ulMatch)) {
                $newLi = "\n                <li class=\"nav-item\"><a class=\"nav-link\" href=\"{$filename}\">{$titleLabel}</a></li>";
                $newUlBody = $ulMatch[2] . $newLi;
                $indexContent = str_replace($ulMatch[0], $ulMatch[1] . $newUlBody . $ulMatch[3], $indexContent);
                $modified = true;
                $updatedLinks[] = "Injected {$titleLabel} into navbar list";
            }
        }
    }
    
    if ($modified) {
        @file_put_contents($indexFullPath, $indexContent);
        return [
            'target_file' => $indexPath['relative'],
            'linked_page' => $filename,
            'details' => $updatedLinks
        ];
    }
    
    return null;
}

/**
 * Inspect active project on disk and generate live context snapshot for Gemini
 */
function getProjectContextSnapshot($folder) {
    if (empty($folder)) return '';
    $resolved = resolveHtdocsPath($folder);
    $fullPath = $resolved['full_path'];
    if (!file_exists($fullPath) || !is_dir($fullPath)) return '';
    
    $files = [];
    $scan = @scandir($fullPath);
    if (!$scan) return '';
    
    foreach ($scan as $f) {
        if ($f === '.' || $f === '..') continue;
        $fp = $fullPath . DIRECTORY_SEPARATOR . $f;
        if (is_file($fp)) {
            $files[] = [
                'name' => $f,
                'size' => formatSizeUnits(filesize($fp))
            ];
        }
    }
    
    if (empty($files)) return '';
    
    $snapshot = "\n=== ACTIVE PROJECT CODEBASE SNAPSHOT (c:\\xampp\\htdocs\\{$resolved['relative']}) ===\n";
    $snapshot .= "- Project Root: C:\\xampp\\htdocs\\{$resolved['relative']}\n";
    $snapshot .= "- Live URL: http://localhost/{$resolved['relative']}/\n";
    $fileListStr = implode(', ', array_map(function($f) { return "{$f['name']} ({$f['size']})"; }, $files));
    $snapshot .= "- Existing Files: [{$fileListStr}]\n";
    
    // Inspect index.html if exists
    $indexPath = $fullPath . DIRECTORY_SEPARATOR . 'index.html';
    if (!file_exists($indexPath)) $indexPath = $fullPath . DIRECTORY_SEPARATOR . 'index.php';
    if (file_exists($indexPath)) {
        $idxContent = file_get_contents($indexPath);
        if (preg_match('/<title>(.*?)<\/title>/i', $idxContent, $tMatch)) {
            $snapshot .= "- Application Title: \"{$tMatch[1]}\"\n";
        }
        if (preg_match('/<nav\b[^>]*>(.*?)<\/nav>/is', $idxContent, $navMatch)) {
            if (preg_match_all('/<a\b[^>]*href=["\']([^"\']*)["\'][^>]*>(.*?)<\/a>/is', $navMatch[1], $linkMatches, PREG_SET_ORDER)) {
                $linksSummary = [];
                foreach ($linkMatches as $lm) {
                    $lText = trim(strip_tags($lm[2]));
                    $lHref = trim($lm[1]);
                    if (!empty($lText)) $linksSummary[] = "\"{$lText}\" -> {$lHref}";
                }
                if (!empty($linksSummary)) {
                    $snapshot .= "- Current Main Navigation Links in index.html: [" . implode(', ', $linksSummary) . "]\n";
                }
            }
        }
    }
    
    // Inspect style.css if exists
    $cssPath = $fullPath . DIRECTORY_SEPARATOR . 'style.css';
    if (file_exists($cssPath)) {
        $cssContent = file_get_contents($cssPath);
        if (preg_match('/:root\s*\{([^}]+)\}/is', $cssContent, $rootMatch)) {
            $tokens = trim(preg_replace('/\s+/', ' ', $rootMatch[1]));
            $snapshot .= "- Design System Theme Tokens (:root): {$tokens}\n";
        }
        $snapshot .= "- Design Mandate: Project uses custom CSS ('style.css'). When adding or modifying pages, ALWAYS link `<link rel=\"stylesheet\" href=\"style.css\">` and match the exact typography, header, and footer! DO NOT replace with foreign CSS frameworks.\n";
    }
    
    $snapshot .= "=== END PROJECT CODEBASE SNAPSHOT ===\n";
    return $snapshot;
}

/**
 * Read file from disk
 */
function agentReadFile($path, $maxLines = 500) {
    $resolved = resolveHtdocsPath($path);
    $fullPath = $resolved['full_path'];
    
    if (!file_exists($fullPath)) {
        return [
            'action' => 'read_file',
            'status' => 'error',
            'path' => $resolved['relative'],
            'message' => "File not found: {$resolved['relative']}"
        ];
    }
    
    $lines = file($fullPath, FILE_IGNORE_NEW_LINES);
    $totalLines = count($lines);
    $slice = array_slice($lines, 0, $maxLines);
    
    return [
        'action' => 'read_file',
        'status' => 'success',
        'path' => $resolved['relative'],
        'total_lines' => $totalLines,
        'content' => implode("\n", $slice),
        'truncated' => ($totalLines > $maxLines)
    ];
}

/**
 * List files and directories
 */
function agentListFiles($path = '') {
    $resolved = resolveHtdocsPath($path);
    $fullPath = $resolved['full_path'];
    
    if (!file_exists($fullPath) || !is_dir($fullPath)) {
        return [
            'action' => 'list_files',
            'status' => 'error',
            'path' => $resolved['relative'],
            'message' => "Directory not found: {$resolved['relative']}"
        ];
    }
    
    $items = [];
    $scan = @scandir($fullPath);
    if ($scan) {
        foreach ($scan as $file) {
            if ($file === '.' || $file === '..') continue;
            $filePath = $fullPath . DIRECTORY_SEPARATOR . $file;
            $isDir = is_dir($filePath);
            $items[] = [
                'name' => $file,
                'is_dir' => $isDir,
                'size' => $isDir ? 0 : @filesize($filePath),
                'size_formatted' => $isDir ? '-' : formatSizeUnits(@filesize($filePath)),
                'modified' => date('Y-m-d H:i', @filemtime($filePath))
            ];
        }
    }
    
    return [
        'action' => 'list_files',
        'status' => 'success',
        'path' => $resolved['relative'],
        'count' => count($items),
        'items' => $items
    ];
}

/**
 * Execute terminal shell command (PowerShell / Windows cmd)
 */
function agentRunCommand($command, $cwd = null) {
    $workDir = HTDOCS_ROOT;
    if (!empty($cwd)) {
        $resolved = resolveHtdocsPath($cwd);
        if (file_exists($resolved['full_path']) && is_dir($resolved['full_path'])) {
            $workDir = $resolved['full_path'];
        }
    }
    
    // Command safety blacklist check
    $dangerousPatterns = ['rmdir /s /q c:', 'del /f /s /q c:', 'format ', 'shutdown', 'drop database', 'rm -rf /'];
    foreach ($dangerousPatterns as $dp) {
        if (stripos($command, $dp) !== false) {
            return [
                'action' => 'run_command',
                'status' => 'error',
                'command' => $command,
                'message' => 'Command blocked by security safety policy.'
            ];
        }
    }
    
    $descriptorspec = [
        0 => ["pipe", "r"], // stdin
        1 => ["pipe", "w"], // stdout
        2 => ["pipe", "w"]  // stderr
    ];
    
    $cmdToRun = 'powershell.exe -NoProfile -ExecutionPolicy Bypass -Command ' . escapeshellarg($command);
    $process = proc_open($cmdToRun, $descriptorspec, $pipes, $workDir);
    
    if (is_resource($process)) {
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        
        $exitCode = proc_close($process);
        $output = trim($stdout . "\n" . $stderr);
        
        return [
            'action' => 'run_command',
            'status' => ($exitCode === 0) ? 'success' : 'failed',
            'command' => $command,
            'cwd' => $workDir,
            'exit_code' => $exitCode,
            'output' => mb_substr($output, 0, 4000)
        ];
    }
    
    return [
        'action' => 'run_command',
        'status' => 'error',
        'command' => $command,
        'message' => 'Failed to initialize process execution.'
    ];
}

/**
 * Generate default production-ready template files when creating a project
 */
function getStarterProjectFiles($folder, $title = 'Web Application', $template = 'tourism') {
    $cleanTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    
    if (stripos($template, 'tour') !== false || stripos($folder, 'tour') !== false || stripos($title, 'tour') !== false) {
        $indexHtml = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$cleanTitle} - Luxury Travel & Destinations</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <!-- Navigation -->
    <header class="navbar">
        <div class="nav-container">
            <a href="#" class="brand">
                <span class="brand-badge">🏝️</span>
                <span class="brand-text">{$cleanTitle}</span>
            </a>
            <nav class="nav-links">
                <a href="#destinations">Destinations</a>
                <a href="#packages">Tour Packages</a>
                <a href="#about">About Us</a>
                <a href="#reviews">Reviews</a>
                <a href="#contact" class="btn-book-nav">Book Tour</a>
            </nav>
            <button class="mobile-toggle" id="mobileToggle">
                <i data-lucide="menu"></i>
            </button>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <div class="hero-badge">
                <i data-lucide="compass"></i>
                <span>Global Travel & Adventure Portal</span>
            </div>
            <h1 class="hero-title">Experience the Wonder of <em>Paradise Destinations</em></h1>
            <p class="hero-subtitle">From misty mountain retreats to golden beaches, historic cities to untamed wilderness safaris. Your dream adventure begins here.</p>
            
            <!-- Quick Search Bar -->
            <div class="search-card">
                <div class="search-item">
                    <label><i data-lucide="map-pin"></i> Destination</label>
                    <select id="searchDest">
                        <option value="all">All Destinations</option>
                        <option value="sigiriya">Sigiriya & Cultural Triangle</option>
                        <option value="ella">Ella & Hill Country</option>
                        <option value="mirissa">Mirissa & Southern Coast</option>
                        <option value="yala">Yala Wildlife Safari</option>
                    </select>
                </div>
                <div class="search-item">
                    <label><i data-lucide="calendar"></i> Duration</label>
                    <select id="searchDuration">
                        <option value="all">Any Duration</option>
                        <option value="3">3 Days (Weekend Getaway)</option>
                        <option value="7">7 Days (Classic Explorer)</option>
                        <option value="14">14 Days (Grand Expedition)</option>
                    </select>
                </div>
                <div class="search-item">
                    <label><i data-lucide="users"></i> Travelers</label>
                    <select id="searchTravelers">
                        <option value="2">2 Adults (Couple)</option>
                        <option value="1">Solo Traveler</option>
                        <option value="4">Family (3 - 5)</option>
                        <option value="8">Group (6+)</option>
                    </select>
                </div>
                <button class="btn-search" onclick="filterPackages()">
                    <i data-lucide="search"></i>
                    <span>Find Tours</span>
                </button>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-bar">
        <div class="stat-box">
            <span class="stat-number">25,000+</span>
            <span class="stat-label">Happy Travelers</span>
        </div>
        <div class="stat-box">
            <span class="stat-number">98.9%</span>
            <span class="stat-label">5-Star Satisfaction</span>
        </div>
        <div class="stat-box">
            <span class="stat-number">45+</span>
            <span class="stat-label">Curated Destinations</span>
        </div>
        <div class="stat-box">
            <span class="stat-number">24/7</span>
            <span class="stat-label">Chauffeur & Local Guide</span>
        </div>
    </section>

    <!-- Featured Destinations -->
    <section id="destinations" class="section">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Global Destinations</span>
                <h2 class="section-title">Iconic Destinations</h2>
                <p class="section-desc">Handcrafted itineraries covering the most breathtaking corners of the world.</p>
            </div>

            <div class="dest-grid">
                <div class="dest-card" style="background-image: url('https://images.unsplash.com/photo-1588598198321-9735fd52455b?w=800&auto=format&fit=crop&q=80');">
                    <div class="dest-overlay"></div>
                    <div class="dest-info">
                        <span class="dest-badge">UNESCO World Heritage</span>
                        <h3>Sigiriya Rock Fortress</h3>
                        <p>Ancient sky palace perched high above emerald jungle canopies.</p>
                    </div>
                </div>

                <div class="dest-card" style="background-image: url('https://images.unsplash.com/photo-1546708973-b339540b5162?w=800&auto=format&fit=crop&q=80');">
                    <div class="dest-overlay"></div>
                    <div class="dest-info">
                        <span class="dest-badge">Hill Country</span>
                        <h3>Ella & Nine Arch Bridge</h3>
                        <p>Iconic scenic train rides, waterfalls, and panoramic tea plantations.</p>
                    </div>
                </div>

                <div class="dest-card" style="background-image: url('https://images.unsplash.com/photo-1586861635167-e5223aadc9fe?w=800&auto=format&fit=crop&q=80');">
                    <div class="dest-overlay"></div>
                    <div class="dest-info">
                        <span class="dest-badge">Southern Coast</span>
                        <h3>Galle Dutch Fort</h3>
                        <p>17th-century colonial ramparts, ocean sunset cafes, and boutique shopping.</p>
                    </div>
                </div>

                <div class="dest-card" style="background-image: url('https://images.unsplash.com/photo-1561731216-c3a4d99437d5?w=800&auto=format&fit=crop&q=80');">
                    <div class="dest-overlay"></div>
                    <div class="dest-info">
                        <span class="dest-badge">Wildlife Safari</span>
                        <h3>Yala National Park</h3>
                        <p>Highest leopard density in the world alongside wild elephant herds.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Tour Packages -->
    <section id="packages" class="section bg-light">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Best Value</span>
                <h2 class="section-title">Popular Tour Packages</h2>
                <p class="section-desc">All packages include luxury AC transport, star hotels, breakfast & entrance fees.</p>
            </div>

            <div class="packages-grid" id="packagesContainer">
                <!-- Package 1 -->
                <div class="pkg-card" data-category="cultural">
                    <div class="pkg-img" style="background-image: url('https://images.unsplash.com/photo-1588598198321-9735fd52455b?w=600&auto=format&fit=crop&q=80');">
                        <span class="pkg-tag">Bestseller</span>
                    </div>
                    <div class="pkg-body">
                        <div class="pkg-meta">
                            <span><i data-lucide="clock"></i> 7 Days / 6 Nights</span>
                            <span class="pkg-rating">★ 4.9 (128 reviews)</span>
                        </div>
                        <h3 class="pkg-title">Classic Ceylon Highlights</h3>
                        <p class="pkg-desc">Colombo • Sigiriya • Kandy • Nuwara Eliya • Galle Coastline</p>
                        <div class="pkg-price-row">
                            <div class="price">
                                <span class="price-val">$649</span>
                                <span class="price-unit">/ person</span>
                            </div>
                            <button class="btn-pkg-book" onclick="openBooking('Classic Ceylon Highlights', '$649')">Book Now</button>
                        </div>
                    </div>
                </div>

                <!-- Package 2 -->
                <div class="pkg-card" data-category="wildlife">
                    <div class="pkg-img" style="background-image: url('https://images.unsplash.com/photo-1561731216-c3a4d99437d5?w=600&auto=format&fit=crop&q=80');">
                        <span class="pkg-tag popular">Wild Adventure</span>
                    </div>
                    <div class="pkg-body">
                        <div class="pkg-meta">
                            <span><i data-lucide="clock"></i> 5 Days / 4 Nights</span>
                            <span class="pkg-rating">★ 4.8 (94 reviews)</span>
                        </div>
                        <h3 class="pkg-title">Wild Safari & Hill Country</h3>
                        <p class="pkg-desc">Udawalawe • Yala Safari • Ella Mountain Hikes • Scenic Train</p>
                        <div class="pkg-price-row">
                            <div class="price">
                                <span class="price-val">$520</span>
                                <span class="price-unit">/ person</span>
                            </div>
                            <button class="btn-pkg-book" onclick="openBooking('Wild Safari & Hill Country', '$520')">Book Now</button>
                        </div>
                    </div>
                </div>

                <!-- Package 3 -->
                <div class="pkg-card" data-category="beach">
                    <div class="pkg-img" style="background-image: url('https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=600&auto=format&fit=crop&q=80');">
                        <span class="pkg-tag">Relaxation</span>
                    </div>
                    <div class="pkg-body">
                        <div class="pkg-meta">
                            <span><i data-lucide="clock"></i> 6 Days / 5 Nights</span>
                            <span class="pkg-rating">★ 5.0 (82 reviews)</span>
                        </div>
                        <h3 class="pkg-title">Southern Beaches & Whales</h3>
                        <p class="pkg-desc">Bentota Watersports • Mirissa Whale Safari • Galle Ramparts</p>
                        <div class="pkg-price-row">
                            <div class="price">
                                <span class="price-val">$580</span>
                                <span class="price-unit">/ person</span>
                            </div>
                            <button class="btn-pkg-book" onclick="openBooking('Southern Beaches & Whales', '$580')">Book Now</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Booking Modal -->
    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeBooking()">&times;</button>
            <div class="modal-header">
                <span class="brand-badge">✈️</span>
                <h3 id="modalPkgTitle">Book Your Tour</h3>
                <p id="modalPkgPrice" class="text-accent"></p>
            </div>
            <form id="bookingForm" onsubmit="handleBookingSubmit(event)">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" id="custName" required placeholder="e.g. John Doe">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" id="custEmail" required placeholder="name@example.com">
                    </div>
                    <div class="form-group">
                        <label>WhatsApp / Phone</label>
                        <input type="tel" id="custPhone" required placeholder="+94 77 123 4567">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Travel Date</label>
                        <input type="date" id="custDate" required>
                    </div>
                    <div class="form-group">
                        <label>Guests</label>
                        <input type="number" id="custGuests" min="1" max="20" value="2" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Special Requests</label>
                    <textarea id="custNotes" rows="2" placeholder="Vegetarian meals, child seat, luxury pickup..."></textarea>
                </div>
                <button type="submit" class="btn-submit-booking">Confirm Booking Request</button>
            </form>
        </div>
    </div>

    <!-- Confirmation Toast -->
    <div id="toastSuccess" class="toast-success">
        <i data-lucide="check-circle-2"></i>
        <div id="toastMessage">Thank you! Your booking reservation has been received.</div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container footer-content">
            <div class="footer-col">
                <div class="brand">
                    <span class="brand-badge">🏝️</span>
                    <span class="brand-text">{$cleanTitle}</span>
                </div>
                <p class="footer-text">Built live by RuXt AI Agentic Framework on XAMPP. Real-time file system execution verified.</p>
            </div>
            <div class="footer-col">
                <h4>Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="#destinations">Destinations</a></li>
                    <li><a href="#packages">Tour Packages</a></li>
                    <li><a href="#bookingModal" onclick="openBooking('Custom Expedition', 'Inquire')">Custom Itinerary</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Contact Office</h4>
                <p>📍 Financial District, Global HQ</p>
                <p>📞 +1 (800) 555-0199</p>
                <p>✉️ concierge@luxurytravels.com</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 {$cleanTitle}. All Rights Reserved. Powered by RuXt Enterprise AI.</p>
        </div>
    </footer>

    <script src="app.js"></script>
    <script>lucide.createIcons();</script>
</body>
</html>
HTML;

        $styleCss = <<<CSS
/* style.css - {$cleanTitle} */
:root {
    --primary: #124e3f;
    --primary-dark: #0a3328;
    --accent: #d97706;
    --accent-light: #f59e0b;
    --dark: #1e293b;
    --light-bg: #f8fafc;
    --white: #ffffff;
    --border: #e2e8f0;
    --text-muted: #64748b;
    --radius: 16px;
    --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Outfit', sans-serif;
    color: var(--dark);
    background-color: var(--white);
    line-height: 1.6;
    overflow-x: hidden;
}

h1, h2, h3, .brand-text {
    font-family: 'Playfair Display', serif;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 24px;
}

/* Navbar */
.navbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid var(--border);
    z-index: 1000;
}

.nav-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 16px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.brand {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    color: var(--primary);
    font-size: 22px;
    font-weight: 700;
}

.brand-badge {
    font-size: 24px;
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 28px;
}

.nav-links a {
    text-decoration: none;
    color: var(--dark);
    font-weight: 500;
    font-size: 15px;
    transition: color 0.2s;
}

.nav-links a:hover {
    color: var(--primary);
}

.btn-book-nav {
    background: var(--primary);
    color: var(--white) !important;
    padding: 10px 22px;
    border-radius: 999px;
    transition: all 0.2s ease !important;
}

.btn-book-nav:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(18, 78, 63, 0.25);
}

.mobile-toggle {
    display: none;
    background: none;
    border: none;
    cursor: pointer;
}

/* Hero Section */
.hero {
    position: relative;
    min-height: 90vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: url('https://images.unsplash.com/photo-1546708973-b339540b5162?w=1600&auto=format&fit=crop&q=80') center/cover no-repeat;
    padding: 140px 24px 60px;
    color: var(--white);
    text-align: center;
}

.hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(10, 51, 40, 0.65) 0%, rgba(10, 51, 40, 0.85) 100%);
}

.hero-content {
    position: relative;
    z-index: 10;
    max-width: 880px;
    margin: 0 auto;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(8px);
    padding: 8px 18px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    margin-bottom: 24px;
}

.hero-title {
    font-size: 54px;
    line-height: 1.15;
    margin-bottom: 20px;
    font-weight: 800;
}

.hero-title em {
    font-style: italic;
    color: var(--accent-light);
}

.hero-subtitle {
    font-size: 18px;
    color: #e2e8f0;
    max-width: 680px;
    margin: 0 auto 40px;
}

/* Search Bar */
.search-card {
    background: rgba(255, 255, 255, 0.96);
    padding: 16px 20px;
    border-radius: var(--radius);
    box-shadow: 0 20px 35px rgba(0, 0, 0, 0.2);
    display: grid;
    grid-template-columns: repeat(3, 1fr) auto;
    gap: 16px;
    align-items: center;
    text-align: left;
}

.search-item label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    margin-bottom: 6px;
}

.search-item select {
    width: 100%;
    border: 1px solid var(--border);
    background: #f8fafc;
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 14px;
    color: var(--dark);
    font-weight: 500;
    outline: none;
}

.btn-search {
    background: var(--accent);
    color: var(--white);
    border: none;
    padding: 14px 28px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.2s;
    height: 100%;
    justify-content: center;
}

.btn-search:hover {
    background: #b45309;
    transform: translateY(-2px);
}

/* Stats Bar */
.stats-bar {
    max-width: 1100px;
    margin: -35px auto 40px;
    position: relative;
    z-index: 20;
    background: var(--white);
    border-radius: var(--radius);
    padding: 30px;
    box-shadow: var(--shadow);
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    border: 1px solid var(--border);
    text-align: center;
}

.stat-box {
    border-right: 1px solid var(--border);
    padding: 0 15px;
}

.stat-box:last-child {
    border-right: none;
}

.stat-number {
    display: block;
    font-size: 32px;
    font-weight: 800;
    color: var(--primary);
}

.stat-label {
    font-size: 13px;
    color: var(--text-muted);
    font-weight: 600;
}

/* Sections */
.section {
    padding: 80px 0;
}

.bg-light {
    background: var(--light-bg);
}

.section-header {
    text-align: center;
    max-width: 650px;
    margin: 0 auto 50px;
}

.section-tag {
    color: var(--accent);
    font-weight: 700;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.section-title {
    font-size: 38px;
    color: var(--dark);
    margin: 8px 0 12px;
}

.section-desc {
    color: var(--text-muted);
    font-size: 16px;
}

/* Destination Grid */
.dest-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 24px;
}

.dest-card {
    height: 380px;
    border-radius: var(--radius);
    position: relative;
    overflow: hidden;
    background-size: cover;
    background-position: center;
    box-shadow: var(--shadow);
    transition: transform 0.3s ease;
}

.dest-card:hover {
    transform: translateY(-8px);
}

.dest-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(0,0,0,0.1) 40%, rgba(0,0,0,0.85) 100%);
}

.dest-info {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 24px;
    color: var(--white);
    z-index: 5;
}

.dest-badge {
    display: inline-block;
    background: var(--accent);
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    padding: 4px 10px;
    border-radius: 6px;
    margin-bottom: 8px;
}

.dest-info h3 {
    font-size: 22px;
    margin-bottom: 6px;
}

.dest-info p {
    font-size: 13px;
    color: #cbd5e1;
}

/* Packages Grid */
.packages-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 30px;
}

.pkg-card {
    background: var(--white);
    border-radius: var(--radius);
    overflow: hidden;
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
    transition: all 0.3s ease;
}

.pkg-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 30px -10px rgba(0, 0, 0, 0.12);
}

.pkg-img {
    height: 220px;
    background-size: cover;
    background-position: center;
    position: relative;
    padding: 16px;
}

.pkg-tag {
    background: var(--primary);
    color: var(--white);
    padding: 6px 14px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
}

.pkg-body {
    padding: 24px;
}

.pkg-meta {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: var(--text-muted);
    margin-bottom: 12px;
}

.pkg-rating {
    color: var(--accent);
    font-weight: 700;
}

.pkg-title {
    font-size: 22px;
    margin-bottom: 8px;
    color: var(--dark);
}

.pkg-desc {
    font-size: 14px;
    color: var(--text-muted);
    margin-bottom: 24px;
}

.pkg-price-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 18px;
    border-top: 1px solid var(--border);
}

.price-val {
    font-size: 26px;
    font-weight: 800;
    color: var(--primary);
}

.price-unit {
    font-size: 13px;
    color: var(--text-muted);
}

.btn-pkg-book {
    background: var(--primary);
    color: var(--white);
    border: none;
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-pkg-book:hover {
    background: var(--primary-dark);
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 2000;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: var(--white);
    width: 100%;
    max-width: 520px;
    border-radius: 20px;
    padding: 32px;
    position: relative;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    animation: modalSlide 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes modalSlide {
    from { opacity: 0; transform: translateY(20px) scale(0.96); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.modal-close {
    position: absolute;
    top: 20px;
    right: 20px;
    background: none;
    border: none;
    font-size: 28px;
    cursor: pointer;
    color: var(--text-muted);
}

.modal-header {
    text-align: center;
    margin-bottom: 24px;
}

.modal-header h3 {
    font-size: 24px;
    color: var(--dark);
    margin: 6px 0;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    margin-bottom: 6px;
}

.form-group input, .form-group textarea {
    width: 100%;
    border: 1px solid var(--border);
    padding: 10px 14px;
    border-radius: 10px;
    font-family: inherit;
    font-size: 14px;
    outline: none;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

.btn-submit-booking {
    width: 100%;
    background: var(--primary);
    color: var(--white);
    border: none;
    padding: 14px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 16px;
    cursor: pointer;
    margin-top: 10px;
    transition: background 0.2s;
}

.btn-submit-booking:hover {
    background: var(--primary-dark);
}

/* Toast */
.toast-success {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: #065f46;
    color: var(--white);
    padding: 16px 24px;
    border-radius: 12px;
    display: none;
    align-items: center;
    gap: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    z-index: 3000;
}

.toast-success.active {
    display: flex;
    animation: toastFade 0.3s ease;
}

@keyframes toastFade {
    from { opacity: 0; transform: translateY(15px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Footer */
.footer {
    background: #0f172a;
    color: #94a3b8;
    padding: 60px 0 24px;
    border-top: 1px solid #1e293b;
}

.footer-content {
    display: grid;
    grid-template-columns: 2fr 1fr 1.5fr;
    gap: 40px;
    margin-bottom: 40px;
}

.footer-col h4 {
    color: var(--white);
    font-size: 18px;
    margin-bottom: 16px;
}

.footer-links {
    list-style: none;
}

.footer-links li {
    margin-bottom: 10px;
}

.footer-links a {
    color: #94a3b8;
    text-decoration: none;
    transition: color 0.2s;
}

.footer-links a:hover {
    color: var(--white);
}

.footer-bottom {
    text-align: center;
    padding-top: 24px;
    border-top: 1px solid #1e293b;
    font-size: 13px;
}

@media (max-width: 900px) {
    .search-card {
        grid-template-columns: 1fr;
    }
    .stats-bar {
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    .stat-box {
        border-right: none;
    }
    .footer-content {
        grid-template-columns: 1fr;
    }
}
CSS;

        $appJs = <<<JS
// app.js - {$cleanTitle}
document.addEventListener('DOMContentLoaded', () => {
    // Mobile navigation toggle
    const mobileToggle = document.getElementById('mobileToggle');
    const navLinks = document.querySelector('.nav-links');
    if (mobileToggle) {
        mobileToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });
    }

    // Set default travel date to 14 days from now
    const dateInput = document.getElementById('custDate');
    if (dateInput) {
        const d = new Date();
        d.setDate(d.getDate() + 14);
        dateInput.value = d.toISOString().split('T')[0];
    }
});

// Open Booking Modal
function openBooking(packageName, price) {
    const modal = document.getElementById('bookingModal');
    const titleElem = document.getElementById('modalPkgTitle');
    const priceElem = document.getElementById('modalPkgPrice');
    
    if (titleElem) titleElem.textContent = packageName;
    if (priceElem) priceElem.textContent = 'Estimated: ' + price;
    if (modal) modal.classList.add('active');
}

// Close Booking Modal
function closeBooking() {
    const modal = document.getElementById('bookingModal');
    if (modal) modal.classList.remove('active');
}

// Close on outside click
window.addEventListener('click', (e) => {
    const modal = document.getElementById('bookingModal');
    if (e.target === modal) {
        closeBooking();
    }
});

// Handle Booking Submission
function handleBookingSubmit(e) {
    e.preventDefault();
    const name = document.getElementById('custName').value;
    const email = document.getElementById('custEmail').value;
    const pkg = document.getElementById('modalPkgTitle').textContent;
    
    closeBooking();
    
    // Show toast
    const toast = document.getElementById('toastSuccess');
    const msg = document.getElementById('toastMessage');
    if (msg) {
        msg.textContent = `Aayubowan, \${name}! Reservation confirmed for "\${pkg}". Details sent to \${email}.`;
    }
    if (toast) {
        toast.classList.add('active');
        setTimeout(() => {
            toast.classList.remove('active');
        }, 5000);
    }
}

// Quick Search Filtering
function filterPackages() {
    const dest = document.getElementById('searchDest').value;
    const cards = document.querySelectorAll('.pkg-card');
    
    cards.forEach(card => {
        if (dest === 'all') {
            card.style.display = 'block';
        } else {
            const text = card.textContent.toLowerCase();
            card.style.display = text.includes(dest) ? 'block' : 'none';
        }
    });

    // Scroll smoothly to packages section
    const target = document.getElementById('packages');
    if (target) {
        target.scrollIntoView({ behavior: 'smooth' });
    }
}
JS;

        $readmeMd = <<<MD
# {$title}
> Real-Time Web Application created by **RuXt AI Assistant Engine** (Antigravity Architecture).

- **Location:** `C:\\xampp\\htdocs\\{$folder}`
- **Local Access URL:** [http://localhost/{$folder}/](http://localhost/{$folder}/)
- **Tech Stack:** HTML5, Modern CSS3 (Grid & Flexbox), Vanilla JavaScript (ES6+), Lucide Icons, Google Fonts.

### Included Features:
1. **Interactive Hero & Search Bar:** Filter destinations and travel dates.
2. **Featured Global Destinations:** Swiss Alps, Amalfi Coast, Kyoto, Serengeti National Park.
3. **Tour Packages & Pricing:** Complete itinerary details with booking trigger.
4. **Interactive Reservation Modal:** Instant guest & date booking confirmation toast.
5. **Responsive Design:** 100% fluid mobile & desktop support.
MD;

        return [
            ['path' => 'index.html', 'content' => $indexHtml],
            ['path' => 'style.css', 'content' => $styleCss],
            ['path' => 'app.js', 'content' => $appJs],
            ['path' => 'README.md', 'content' => $readmeMd]
        ];
    }

    // Default starter template
    $defaultIndex = "<!DOCTYPE html>\n<html>\n<head>\n<title>{$cleanTitle}</title>\n<link rel='stylesheet' href='style.css'>\n</head>\n<body>\n<h1>{$cleanTitle}</h1>\n<p>Project deployed by RuXt AI Assistant.</p>\n</body>\n</html>";
    $defaultCss = "body { font-family: sans-serif; padding: 40px; background: #f8fafc; color: #1e293b; }";
    return [
        ['path' => 'index.html', 'content' => $defaultIndex],
        ['path' => 'style.css', 'content' => $defaultCss]
    ];
}

/**
 * Create full project with directory and all files
 */
function agentCreateProject($params) {
    $folder = isset($params['folder']) ? trim($params['folder']) : '';
    if (empty($folder)) {
        return [
            'action' => 'create_project',
            'status' => 'error',
            'message' => 'Project folder name is required.'
        ];
    }
    
    // Resolve project folder
    $resolved = resolveHtdocsPath($folder);
    $folderPath = $resolved['full_path'];
    $folderRel = $resolved['relative'];
    
    // 1. Create main directory
    if (!file_exists($folderPath)) {
        @mkdir($folderPath, 0777, true);
    }
    
    $title = isset($params['title']) && !empty($params['title']) ? trim($params['title']) : ucwords(str_replace(['-', '_'], ' ', $folderRel));
    $template = isset($params['template']) ? $params['template'] : 'tourism';
    
    // 2. Determine files to write
    $filesToWrite = [];
    if (isset($params['files']) && is_array($params['files']) && !empty($params['files'])) {
        $filesToWrite = $params['files'];
    } else {
        $filesToWrite = getStarterProjectFiles($folderRel, $title, $template);
    }
    
    // 3. Write each file
    $createdFiles = [];
    foreach ($filesToWrite as $file) {
        $relFilePath = ltrim($file['path'], '/\\');
        $fullFilePath = $folderPath . DIRECTORY_SEPARATOR . $relFilePath;
        
        $parentDir = dirname($fullFilePath);
        if (!file_exists($parentDir)) {
            @mkdir($parentDir, 0777, true);
        }
        
        $bytes = @file_put_contents($fullFilePath, $file['content']);
        $createdFiles[] = [
            'path' => $relFilePath,
            'full_path' => $fullFilePath,
            'bytes' => $bytes !== false ? $bytes : 0,
            'size_formatted' => $bytes !== false ? formatSizeUnits($bytes) : '0 bytes',
            'status' => ($bytes !== false) ? 'created' : 'failed'
        ];
    }
    
    $localUrl = "http://localhost/" . str_replace('\\', '/', $folderRel) . "/";
    
    return [
        'action' => 'create_project',
        'status' => 'success',
        'folder' => $folderRel,
        'title' => $title,
        'full_path' => $folderPath,
        'url' => $localUrl,
        'files_count' => count($createdFiles),
        'files' => $createdFiles,
        'message' => "Successfully scaffolded project [{$title}] with " . count($createdFiles) . " files at {$folderPath}"
    ];
}

/**
 * Dispatch agent action by action name
 */
function executeAgentAction($actionName, $params) {
    switch (strtolower(trim($actionName))) {
        case 'create_project':
        case 'scaffold_project':
        case 'create_website':
            return agentCreateProject($params);
            
        case 'write_file':
        case 'create_file':
            $path = isset($params['path']) ? $params['path'] : '';
            $content = isset($params['content']) ? $params['content'] : '';
            return agentWriteFile($path, $content);
            
        case 'edit_file':
        case 'replace_text':
            $path = isset($params['path']) ? $params['path'] : '';
            $target = isset($params['target']) ? $params['target'] : '';
            $replacement = isset($params['replacement']) ? $params['replacement'] : '';
            return agentEditFile($path, $target, $replacement);
            
        case 'mkdir':
        case 'create_folder':
        case 'create_directory':
            $path = isset($params['path']) ? $params['path'] : '';
            return agentMkdir($path);
            
        case 'read_file':
            $path = isset($params['path']) ? $params['path'] : '';
            $maxLines = isset($params['max_lines']) ? intval($params['max_lines']) : 500;
            return agentReadFile($path, $maxLines);
            
        case 'list_files':
        case 'dir':
            $path = isset($params['path']) ? $params['path'] : '';
            return agentListFiles($path);
            
        case 'run_command':
        case 'execute_command':
        case 'terminal':
            $cmd = isset($params['command']) ? $params['command'] : (isset($params['cmd']) ? $params['cmd'] : '');
            $cwd = isset($params['cwd']) ? $params['cwd'] : null;
            return agentRunCommand($cmd, $cwd);
            
        default:
            return [
                'action' => $actionName,
                'status' => 'error',
                'message' => "Unsupported agent action: {$actionName}"
            ];
    }
}
