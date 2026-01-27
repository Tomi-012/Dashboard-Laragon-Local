<?php
// --- LOGIC PHP ---
$currentDir = './';
// Handle PHP Info Request
if (isset($_GET['q']) && $_GET['q'] === 'phpinfo') {
    phpinfo();
    exit;
}
$items = array_diff(scandir($currentDir), ['.', '..']);

// --- HELPER FUNCTIONS ---

// 1. Project Type Detection (Smart Deep Scan & Priority)
// 1. Project Type Detection (Ultimate Edition)
function getProjectType($dirPath) {
    if (!is_dir($dirPath)) return [];
    
    $foundBadges = [];
    
    // --- Definitions: Type, Color, Icon, Priority (Higher = Leftmost) ---
    $detectors = [
        // --- PHP FRAMEWORKS & CMS (Priority 95-100) ---
        'artisan' => ['type' => 'Laravel', 'color' => 'bg-red-600', 'icon' => 'fa-brands fa-laravel', 'p' => 100],
        'bin/console' => ['type' => 'Symfony', 'color' => 'bg-slate-800 text-white', 'icon' => 'fa-solid fa-s', 'p' => 98],
        'yii' => ['type' => 'Yii 2', 'color' => 'bg-orange-500', 'icon' => 'fa-brands fa-php', 'p' => 95],
        'bin/cake' => ['type' => 'CakePHP', 'color' => 'bg-red-500', 'icon' => 'fa-solid fa-birthday-cake', 'p' => 95],
        'bin/magento' => ['type' => 'Magento', 'color' => 'bg-orange-600', 'icon' => 'fa-brands fa-magento', 'p' => 100],
        'core/lib/Drupal.php' => ['type' => 'Drupal', 'color' => 'bg-blue-500', 'icon' => 'fa-brands fa-drupal', 'p' => 100],
        'libraries/cms/version/version.php' => ['type' => 'Joomla', 'color' => 'bg-orange-500', 'icon' => 'fa-brands fa-joomla', 'p' => 100], // Joomla Check
        'config.php' => ['type' => 'OpenCart', 'color' => 'bg-cyan-500', 'icon' => 'fa-solid fa-cart-shopping', 'p' => 95], // OpenCart Check (Check admin/config.php usuallly safer but root config exists)
        'config/defines.inc.php' => ['type' => 'PrestaShop', 'color' => 'bg-pink-600', 'icon' => 'fa-solid fa-bag-shopping', 'p' => 95],
        'wp-config.php' => ['type' => 'WordPress', 'color' => 'bg-blue-600', 'icon' => 'fa-brands fa-wordpress', 'p' => 100],
        'spark' => ['type' => 'CI 4', 'color' => 'bg-orange-600', 'icon' => 'fa-solid fa-fire', 'p' => 95],
        'system/core/CodeIgniter.php' => ['type' => 'CI 3', 'color' => 'bg-orange-500', 'icon' => 'fa-solid fa-fire', 'p' => 95],
        
        // --- JS FRAMEWORKS & LIBS (Priority 80-95) ---
        'next' => ['type' => 'Next.js', 'color' => 'bg-black dark:bg-white dark:text-black', 'icon' => 'fa-brands fa-node-js', 'p' => 95],
        'nuxt' => ['type' => 'Nuxt', 'color' => 'bg-emerald-600', 'icon' => 'fa-solid fa-mountain', 'p' => 95],
        '@angular/core' => ['type' => 'Angular', 'color' => 'bg-red-700', 'icon' => 'fa-brands fa-angular', 'p' => 95],
        'astro' => ['type' => 'Astro', 'color' => 'bg-orange-500', 'icon' => 'fa-solid fa-rocket', 'p' => 90],
        'gatsby' => ['type' => 'Gatsby', 'color' => 'bg-purple-700', 'icon' => 'fa-solid fa-globe', 'p' => 90],
        '@remix-run/react' => ['type' => 'Remix', 'color' => 'bg-blue-600', 'icon' => 'fa-solid fa-record-vinyl', 'p' => 90],
        '@nestjs/core' => ['type' => 'NestJS', 'color' => 'bg-red-600', 'icon' => 'fa-solid fa-cat', 'p' => 90],
        'react' => ['type' => 'React', 'color' => 'bg-cyan-500', 'icon' => 'fa-brands fa-react', 'p' => 85],
        'vue' => ['type' => 'Vue.js', 'color' => 'bg-emerald-500', 'icon' => 'fa-brands fa-vuejs', 'p' => 85],
        'svelte' => ['type' => 'Svelte', 'color' => 'bg-orange-500', 'icon' => 'fa-solid fa-bolt', 'p' => 85],
        'express' => ['type' => 'Express', 'color' => 'bg-slate-500', 'icon' => 'fa-brands fa-node', 'p' => 80],
        
        // --- JS TOOLS (Priority 60-75) ---
        'vite' => ['type' => 'Vite', 'color' => 'bg-purple-500', 'icon' => 'fa-solid fa-bolt', 'p' => 75],
        'typescript' => ['type' => 'TypeScript', 'color' => 'bg-blue-600', 'icon' => 'fa-brands fa-js', 'p' => 75],
        'tailwindcss' => ['type' => 'Tailwind', 'color' => 'bg-cyan-400', 'icon' => 'fa-solid fa-wind', 'p' => 60],
        'bootstrap' => ['type' => 'Bootstrap', 'color' => 'bg-purple-600', 'icon' => 'fa-brands fa-bootstrap', 'p' => 60],
        
        // --- OTHER LANGS (Priority 90) ---
        'Gemfile' => ['type' => 'Ruby on Rails', 'color' => 'bg-red-700', 'icon' => 'fa-solid fa-gem', 'p' => 95],
        'pom.xml' => ['type' => 'Java/Spring', 'color' => 'bg-red-600', 'icon' => 'fa-brands fa-java', 'p' => 95],
        'Cargo.toml' => ['type' => 'Rust', 'color' => 'bg-orange-700', 'icon' => 'fa-solid fa-gear', 'p' => 95],
        'mix.exs' => ['type' => 'Elixir/Phoenix', 'color' => 'bg-purple-600', 'icon' => 'fa-brands fa-phoenix-framework', 'p' => 95],
        'appsettings.json' => ['type' => '.NET Core', 'color' => 'bg-purple-700', 'icon' => 'fa-brands fa-microsoft', 'p' => 95],
        'pubspec.yaml' => ['type' => 'Flutter', 'color' => 'bg-cyan-500', 'icon' => 'fa-brands fa-flutter', 'p' => 95],
        'Package.swift' => ['type' => 'Swift', 'color' => 'bg-orange-600', 'icon' => 'fa-brands fa-swift', 'p' => 95],
        'deno.json' => ['type' => 'Deno', 'color' => 'bg-slate-800 text-white', 'icon' => 'fa-solid fa-d', 'p' => 95],
        'bun.lockb' => ['type' => 'Bun', 'color' => 'bg-orange-200 text-slate-800', 'icon' => 'fa-solid fa-bread-slice', 'p' => 95],
        'build.sbt' => ['type' => 'Scala', 'color' => 'bg-red-700', 'icon' => 'fa-solid fa-s', 'p' => 95],
        'manage.py' => ['type' => 'Django', 'color' => 'bg-emerald-800', 'icon' => 'fa-brands fa-python', 'p' => 95],
        'app.py' => ['type' => 'Python/Flask', 'color' => 'bg-slate-600', 'icon' => 'fa-brands fa-python', 'p' => 85],
        'go.mod' => ['type' => 'Golang', 'color' => 'bg-cyan-600', 'icon' => 'fa-brands fa-golang', 'p' => 95],
        
        // --- GENERIC (Priority 20-40) ---
        'node' => ['type' => 'Node.js', 'color' => 'bg-green-600', 'icon' => 'fa-brands fa-node-js', 'p' => 40],
        'composer.json' => ['type' => 'PHP Project', 'color' => 'bg-indigo-500', 'icon' => 'fa-brands fa-php', 'p' => 35],
        'native_php' => ['type' => 'Native PHP', 'color' => 'bg-indigo-400', 'icon' => 'fa-brands fa-php', 'p' => 20],
        'html5' => ['type' => 'HTML5', 'color' => 'bg-orange-500', 'icon' => 'fa-brands fa-html5', 'p' => 15],
    ];

    // --- Helper: Scan a specific directory ---
    $scanDir = function($path) use ($detectors) {
        $matches = [];
        
        // 1. Check PHP File Indicators
        foreach ($detectors as $fileKey => $badge) {
            // Skip identifiers that start with @ (JS packages) or generic keys
            if ($fileKey[0] === '@' || in_array($fileKey, ['next', 'nuxt', 'react', 'vue', 'svelte', 'vite', 'tailwindcss', 'bootstrap', 'express', 'node', 'native_php', 'html5', 'typescript'])) continue;
            
            if (file_exists($path . '/' . $fileKey)) {
                $matches[] = $badge;
            }
        }
        
        // 2. Check JS (package.json)
        if (file_exists($path . '/package.json')) {
            $json = json_decode(file_get_contents($path . '/package.json'), true);
            $deps = array_merge($json['dependencies'] ?? [], $json['devDependencies'] ?? []);
            
            $hasJsFramework = false;
            
            // Map dependency keys to detector keys
            foreach ($deps as $dep => $ver) {
                if (isset($detectors[$dep])) {
                    $matches[] = $detectors[$dep];
                    if ($detectors[$dep]['p'] >= 80) $hasJsFramework = true;
                }
            }
            if (!$hasJsFramework) $matches[] = $detectors['node'];
        }
        
        // 3. Check Config Files (TS, etc)
        if (file_exists($path . '/tsconfig.json')) $matches[] = $detectors['typescript'];
        
        // 4. Native Checks Fallback
        if (empty($matches)) {
            if (file_exists($path . '/index.php')) {
                $matches[] = $detectors['native_php'];
            } elseif (file_exists($path . '/index.html')) {
                $matches[] = $detectors['html5'];
            } else {
                 $files = scandir($path);
                 foreach ($files as $f) {
                     if (pathinfo($f, PATHINFO_EXTENSION) === 'php') {
                         $matches[] = ['type' => 'PHP Script', 'color' => 'bg-slate-500', 'icon' => 'fa-brands fa-php', 'p' => 10];
                         break;
                     }
                 }
            }
        }
        return $matches;
    };

    // --- EXECUTE SCAN ---
    $foundBadges = array_merge($foundBadges, $scanDir($dirPath));

    // Deep Scan (1 Level Deep)
    $subDirs = array_filter(scandir($dirPath), function($item) use ($dirPath) {
        return $item !== '.' && $item !== '..' && $item !== 'node_modules' && $item !== 'vendor' && $item !== '.git' && is_dir($dirPath . '/' . $item);
    });

    foreach ($subDirs as $sub) {
        $foundBadges = array_merge($foundBadges, $scanDir($dirPath . '/' . $sub));
    }

    // --- CLEANUP ---
    $uniqueBadges = [];
    $seenTypes = [];
    foreach ($foundBadges as $b) {
        if (!in_array($b['type'], $seenTypes)) {
            $uniqueBadges[] = $b;
            $seenTypes[] = $b['type'];
        }
    }
    
    // Filter Redundant "PHP Script"
    $hasMajorPhp = false;
    foreach ($uniqueBadges as $b) {
        if (in_array($b['type'], ['Laravel', 'WordPress', 'Symfomy', 'Yii 2', 'Drupal', 'Magento', 'CI 4', 'CI 3', 'Native PHP', 'PHP Project'])) {
            $hasMajorPhp = true; break;
        }
    }
    if ($hasMajorPhp) {
        $uniqueBadges = array_filter($uniqueBadges, function($b) { return $b['type'] !== 'PHP Script'; });
    }
    
    // Sort
    usort($uniqueBadges, function($a, $b) {
        return $b['p'] <=> $a['p'];
    });
    
    return empty($uniqueBadges) ? [['type' => 'Folder', 'color' => 'bg-slate-500', 'icon' => 'fa-solid fa-folder']] : $uniqueBadges;
}

// 2. System Stats
function getDiskSpace() {
    $ds = disk_free_space(".");
    $ts = disk_total_space(".");
    $percent = round(($ds / $ts) * 100);
    return [
        'free' => formatSize($ds),
        'total' => formatSize($ts),
        'percent' => $percent,
        'used_percent' => 100 - $percent
    ];
}

// 3. Format Size
function formatSize($bytes) {
  if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
  elseif ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
  elseif ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
  elseif ($bytes > 1) return $bytes . ' bytes';
  else return '0 bytes';
}

function getFileIcon($filename) {
  $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
  // ... icon logic remains same, minimized for brevity in this replace block helper ...
  // Re-implementing logic here for safety
  $icons = [
    'php' => 'fa-brands fa-php text-indigo-500 dark:text-indigo-400',
    'html' => 'fa-brands fa-html5 text-orange-600 dark:text-orange-500',
    'css' => 'fa-brands fa-css3-alt text-blue-600 dark:text-blue-500',
    'js' => 'fa-brands fa-js text-yellow-500 dark:text-yellow-400',
    'sql' => 'fa-solid fa-database text-slate-500 dark:text-gray-400',
    'zip' => 'fa-solid fa-file-zipper text-rose-500 dark:text-rose-400',
    'png' => 'fa-regular fa-image text-purple-500 dark:text-purple-400',
    'jpg' => 'fa-regular fa-image text-purple-500 dark:text-purple-400',
  ];
  return $icons[$ext] ?? 'fa-solid fa-file text-slate-400 dark:text-gray-600';
}

// Prepare Data
$fileInfo = [];
foreach ($items as $item) {
  $path = $currentDir . $item;
  $projectType = getProjectType($path);
  
  $fileInfo[$item] = [
    'size' => is_dir($path) ? count(scandir($path)) - 2 . ' items' : formatSize(filesize($path)),
    'modified_raw' => filemtime($path),
    'modified' => date("d M Y", filemtime($path)), // Lebih ringkas
    'is_dir' => is_dir($path),
    'type' => is_dir($path) ? ($projectType['type'] ?? 'Folder') : pathinfo($item, PATHINFO_EXTENSION),
    'badge' => is_dir($path) ? $projectType : null
  ];
}

// Default Sorting (Folder first, then Name)
usort($items, function ($a, $b) use ($fileInfo) {
    if ($fileInfo[$a]['is_dir'] === $fileInfo[$b]['is_dir']) {
        return strnatcasecmp($a, $b);
    }
    return $fileInfo[$a]['is_dir'] ? -1 : 1;
});
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Laragon Dashboard</title>

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Tailwind CSS (Modern Styling) -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class', // Mengaktifkan dark mode manual via class
      theme: {
        extend: {
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'sans-serif'],
          },
          animation: {
            'fade-in-up': 'fadeInUp 0.5s ease-out forwards',
            'blob': 'blob 7s infinite',
          },
          keyframes: {
            fadeInUp: {
              '0%': {
                opacity: '0',
                transform: 'translateY(20px)'
              },
              '100%': {
                opacity: '1',
                transform: 'translateY(0)'
              },
            },
            blob: {
              '0%': {
                transform: 'translate(0px, 0px) scale(1)'
              },
              '33%': {
                transform: 'translate(30px, -50px) scale(1.1)'
              },
              '66%': {
                transform: 'translate(-20px, 20px) scale(0.9)'
              },
              '100%': {
                transform: 'translate(0px, 0px) scale(1)'
              },
            }
          }
        }
      }
    }
  </script>

  <style type="text/tailwindcss">
    @layer utilities {
            .glass-panel {
                @apply bg-white/70 dark:bg-slate-900/60 backdrop-blur-xl border border-slate-200/50 dark:border-white/10 shadow-sm dark:shadow-xl transition-colors duration-300;
            }
            .glass-card {
                @apply bg-white/60 dark:bg-slate-800/40 backdrop-blur-md border border-slate-200/60 dark:border-white/5 
                       hover:bg-white/90 dark:hover:bg-slate-700/50 
                       hover:border-indigo-300/50 dark:hover:border-indigo-500/30 
                       hover:shadow-lg dark:hover:shadow-indigo-500/10 
                       transition-all duration-300;
            }
            .text-glow {
                text-shadow: 0 0 20px rgba(99, 102, 241, 0.5);
            }
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            @apply bg-transparent;
        }
        ::-webkit-scrollbar-thumb {
            @apply bg-slate-300 dark:bg-slate-700 rounded-full hover:bg-slate-400 dark:hover:bg-slate-600;
        }
    </style>

  <!-- Script untuk mendeteksi preferensi tema sebelum render agar tidak kedip -->
  <script>
    if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }
  </script>
</head>

<body class="bg-slate-50 text-slate-800 dark:bg-[#0f172a] dark:text-slate-300 antialiased min-h-screen selection:bg-indigo-500 selection:text-white transition-colors duration-300 overflow-x-hidden relative pb-32">

  <!-- Ambient Background Blobs (Decoration) -->
  <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10">
    <div class="absolute top-0 left-1/4 w-96 h-96 bg-indigo-300/30 dark:bg-indigo-500/20 rounded-full mix-blend-multiply dark:mix-blend-screen filter blur-3xl opacity-70 animate-blob"></div>
    <div class="absolute top-0 right-1/4 w-96 h-96 bg-purple-300/30 dark:bg-purple-500/20 rounded-full mix-blend-multiply dark:mix-blend-screen filter blur-3xl opacity-70 animate-blob animation-delay-2000"></div>
    <div class="absolute -bottom-32 left-1/3 w-96 h-96 bg-pink-300/30 dark:bg-pink-500/20 rounded-full mix-blend-multiply dark:mix-blend-screen filter blur-3xl opacity-70 animate-blob animation-delay-4000"></div>
  </div>

  <!-- Navbar -->
  <nav class="sticky top-4 z-50 mx-4 md:mx-auto max-w-7xl rounded-2xl glass-panel mb-8">
    <div class="px-4 sm:px-6">
      <div class="flex items-center justify-between h-16">
        <!-- Logo -->
        <div class="flex items-center gap-3">
          <div class="relative group">
            <div class="absolute -inset-0.5 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl blur opacity-30 group-hover:opacity-60 transition duration-200"></div>
            <div class="relative w-10 h-10 bg-white dark:bg-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
              <i class="fas fa-layer-group text-indigo-600 dark:text-white text-xl transform group-hover:scale-110 transition-transform"></i>
            </div>
          </div>
          <div>
            <h1 class="text-slate-900 dark:text-white font-bold text-xl tracking-tight leading-none">Laragon<span class="text-indigo-600 dark:text-indigo-400">Dash</span></h1>
            <p class="text-[10px] uppercase font-semibold text-slate-500 dark:text-slate-400 tracking-wider mt-0.5">Local Environment</p>
          </div>
        </div>

        <!-- Search & Actions -->
        <div class="flex items-center gap-3">
          <!-- Search Bar -->
          <div class="hidden md:block relative group">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <i class="fas fa-search text-slate-400 group-focus-within:text-indigo-500 transition-colors"></i>
            </div>
            <input type="text" id="searchInput" onkeyup="filterItems()"
              class="bg-slate-100/50 dark:bg-slate-950/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 block w-64 pl-10 p-2.5 transition-all placeholder-slate-400 shadow-sm"
              placeholder="Cari project (Ctrl + /)">
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
              <kbd class="hidden sm:inline-block px-1.5 py-0.5 text-xs font-semibold text-slate-500 bg-slate-200 dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded">/</kbd>
            </div>
          </div>

          <!-- Theme Toggle -->
          <button id="theme-toggle" class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-yellow-400 border border-slate-200 dark:border-slate-700 transition-all hover:scale-105 active:scale-95 shadow-sm" title="Toggle Theme">
            <i id="theme-toggle-dark-icon" class="fas fa-moon hidden"></i>
            <i id="theme-toggle-light-icon" class="fas fa-sun hidden"></i>
          </button>

          <div class="h-8 w-px bg-slate-200 dark:bg-slate-700 mx-1"></div>

          <button onclick="createNewProject()" class="bg-indigo-600 hover:bg-indigo-500 text-white p-2.5 rounded-xl transition-all shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/40 active:scale-95 group" title="Project Baru">
            <i class="fas fa-plus transform group-hover:rotate-90 transition-transform duration-300"></i>
          </button>
          <button onclick="openSettings()" class="bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 p-2.5 rounded-xl transition-all active:scale-95" title="Pengaturan">
            <i class="fas fa-cog animate-spin-slow"></i>
          </button>
        </div>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
      <!-- Stat 1 -->
      <div class="glass-card p-5 rounded-2xl flex items-center justify-between group cursor-default animate-fade-in-up" style="animation-delay: 0ms;">
        <div>
          <p class="text-slate-500 dark:text-slate-400 text-xs font-bold uppercase tracking-wider">Total Folders</p>
          <h3 class="text-2xl font-extrabold text-slate-800 dark:text-white mt-1"><?php echo count(array_filter($items, 'is_dir')); ?></h3>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-blue-100 dark:bg-blue-500/20 flex items-center justify-center group-hover:scale-110 transition-transform duration-300 shadow-sm">
          <i class="fas fa-folder text-blue-600 dark:text-blue-400 text-xl"></i>
        </div>
      </div>

      <!-- Stat 2 -->
      <div class="glass-card p-5 rounded-2xl flex items-center justify-between group cursor-default animate-fade-in-up" style="animation-delay: 100ms;">
        <div>
          <p class="text-slate-500 dark:text-slate-400 text-xs font-bold uppercase tracking-wider">Total Files</p>
          <h3 class="text-2xl font-extrabold text-slate-800 dark:text-white mt-1"><?php echo count($items) - count(array_filter($items, 'is_dir')); ?></h3>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-emerald-100 dark:bg-emerald-500/20 flex items-center justify-center group-hover:scale-110 transition-transform duration-300 shadow-sm">
          <i class="fas fa-file-code text-emerald-600 dark:text-emerald-400 text-xl"></i>
        </div>
      </div>

      <!-- Stat 3 -->
      <div class="glass-card p-5 rounded-2xl flex items-center justify-between group cursor-default animate-fade-in-up" style="animation-delay: 200ms;">
        <div>
          <p class="text-slate-500 dark:text-slate-400 text-xs font-bold uppercase tracking-wider">PHP Version</p>
          <h3 class="text-xl font-extrabold text-slate-800 dark:text-white mt-1">v<?php echo phpversion(); ?></h3>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-indigo-100 dark:bg-indigo-500/20 flex items-center justify-center group-hover:scale-110 transition-transform duration-300 shadow-sm">
          <i class="fa-brands fa-php text-indigo-600 dark:text-indigo-400 text-xl"></i>
        </div>
      </div>

      <!-- Stat 4 (Disk Space) -->
      <div class="glass-card p-5 rounded-2xl flex items-center justify-between group cursor-default animate-fade-in-up" style="animation-delay: 300ms;">
        <div>
          <p class="text-slate-500 dark:text-slate-400 text-xs font-bold uppercase tracking-wider">Disk: <?php $d = getDiskSpace(); echo $d['free']; ?> Free</p>
          <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2.5 mt-2 overflow-hidden">
            <div class="bg-indigo-600 h-2.5 rounded-full" style="width: <?php echo $d['used_percent']; ?>%"></div>
          </div>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-orange-100 dark:bg-orange-500/20 flex items-center justify-center group-hover:scale-110 transition-transform duration-300 shadow-sm ml-4">
          <i class="fas fa-hard-drive text-orange-600 dark:text-orange-400 text-xl"></i>
        </div>
      </div>
    </div>

    <!-- Quick Links & Toolbar -->
    <div class="flex flex-col md:flex-row gap-4 mb-6 items-center justify-between">
        
        <!-- Quick Links -->
        <div class="flex gap-3 overflow-x-auto pb-2 md:pb-0 w-full md:w-auto">
            <a href="/phpmyadmin" target="_blank" class="glass-card px-4 py-2 rounded-xl flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:text-indigo-600 dark:hover:text-white whitespace-nowrap">
                <i class="fas fa-database text-orange-500"></i> phpMyAdmin
            </a>
            <a href="?q=phpinfo" target="_blank" class="glass-card px-4 py-2 rounded-xl flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:text-indigo-600 dark:hover:text-white whitespace-nowrap">
                <i class="fab fa-php text-indigo-500"></i> PHP Info
            </a>
            <!-- MailCatcher (Optional, hidden if not used usually) -->
            <!-- <a href="http://localhost:1080" target="_blank" class="...">Mail</a> -->
        </div>

        <!-- Sorting & Filtering -->
        <div class="flex gap-2">
            <select id="sortSelect" onchange="applySortAndFilter()" class="bg-white/50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl text-sm px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
                <option value="name_asc">Nama (A-Z)</option>
                <option value="name_desc">Nama (Z-A)</option>
                <option value="date_desc">Terbaru</option>
                <option value="date_asc">Terlama</option>
            </select>
            <div class="flex bg-white/50 dark:bg-slate-800/50 p-1 rounded-xl border border-slate-200 dark:border-slate-700">
                <button onclick="setFilter('all')" id="btn-all" class="px-3 py-1 rounded-lg text-xs font-bold bg-indigo-100 dark:bg-indigo-500 text-indigo-700 dark:text-white transition-all">ALL</button>
                <button onclick="setFilter('folder')" id="btn-folder" class="px-3 py-1 rounded-lg text-xs font-bold text-slate-500 hover:text-indigo-600 dark:text-slate-400 transition-all">PROJECTS</button>
                <button onclick="setFilter('file')" id="btn-file" class="px-3 py-1 rounded-lg text-xs font-bold text-slate-500 hover:text-indigo-600 dark:text-slate-400 transition-all">FILES</button>
            </div>
        </div>
    </div>

    <!-- File Grid -->
    <?php if (empty($items)): ?>
      <div class="text-center py-24 glass-panel rounded-3xl opacity-80 animate-fade-in-up">
        <div class="w-24 h-24 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner">
          <i class="fas fa-folder-open text-5xl text-slate-300 dark:text-slate-600"></i>
        </div>
        <h3 class="text-2xl font-bold text-slate-800 dark:text-slate-200">Direktori Kosong</h3>
        <p class="text-slate-500 dark:text-slate-400 mt-2">Mulai dengan membuat folder project baru.</p>
        <button onclick="createNewProject()" class="mt-6 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-medium rounded-xl shadow-lg shadow-indigo-500/30 transition-all active:scale-95">
          Buat Project
        </button>
      </div>
    <?php else: ?>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5" id="fileGrid">
        <?php
        $delay = 0;
        foreach ($items as $item):
          $info = $fileInfo[$item]; // Data pre-computed di atas
          $isDir = $info['is_dir'];
          $modTime = $info['modified'];
          $size = $info['size'];
          $badge = $info['badge'];
          $url = $item;
          
          // Data attributes untuk JS Sorting & Filtering
          $dataName = strtolower($item);
          $dataDate = $info['modified_raw'];
          $dataType = $isDir ? 'folder' : 'file';
          
          $delay += 50; 
        ?>
          <div class="group relative glass-card rounded-2xl p-5 file-item animate-fade-in-up transform transition-all duration-300 hover:-translate-y-1"
            style="animation-delay: <?php echo min($delay, 1000); ?>ms;"
            data-name="<?php echo $dataName; ?>"
            data-date="<?php echo $dataDate; ?>"
            data-type="<?php echo $dataType; ?>">



            <!-- Context Menu Trigger (Top Right) -->
            <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex gap-2 z-10">
              <!-- Quick Actions for Folders -->
              <?php if($isDir): ?>
              <button onclick="event.preventDefault(); event.stopPropagation(); openIdeSelector('<?php echo htmlspecialchars($item); ?>')" class="w-8 h-8 flex items-center justify-center rounded-lg bg-slate-700 hover:bg-slate-600 text-white shadow-sm transition-all" title="Open in IDE...">
                <i class="fas fa-code text-xs"></i>
              </button>
              <button onclick="event.preventDefault(); event.stopPropagation(); quickAction('open_explorer', '<?php echo htmlspecialchars($item); ?>')" class="w-8 h-8 flex items-center justify-center rounded-lg bg-yellow-500 hover:bg-yellow-600 text-white shadow-sm transition-all" title="Open in Explorer">
                <i class="fas fa-folder-open text-xs"></i>
              </button>
              <?php endif; ?>
              
              <!-- Edit/Delete -->
              <button onclick="event.preventDefault(); event.stopPropagation(); renameItem('<?php echo htmlspecialchars($item); ?>')" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white dark:bg-slate-800 hover:bg-indigo-50 dark:hover:bg-indigo-600 text-slate-400 hover:text-indigo-600 dark:hover:text-white border border-slate-200 dark:border-slate-700 shadow-sm transition-all" title="Rename">
                <i class="fas fa-pencil text-xs"></i>
              </button>
              <button onclick="event.preventDefault(); event.stopPropagation(); deleteItem('<?php echo htmlspecialchars($item); ?>')" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white dark:bg-slate-800 hover:bg-rose-50 dark:hover:bg-rose-600 text-slate-400 hover:text-rose-600 dark:hover:text-white border border-slate-200 dark:border-slate-700 shadow-sm transition-all" title="Delete">
                <i class="fas fa-trash text-xs"></i>
              </button>
            </div>

            <a href="<?php echo $url; ?>" class="block h-full block-link">
              <div class="flex items-start gap-4">
                <!-- Icon Container -->
                <div class="shrink-0 w-14 h-14 rounded-2xl flex items-center justify-center text-2xl shadow-sm transition-colors duration-300 relative
                                <?php echo $isDir
                                  ? 'bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 group-hover:bg-blue-100 dark:group-hover:bg-blue-500/20'
                                  : 'bg-slate-50 dark:bg-slate-700/50 group-hover:bg-white dark:group-hover:bg-slate-700'; ?>">
                  <?php if ($isDir): ?>
                    <?php if(!empty($badge) && isset($badge[0]['icon'])): ?>
                        <i class="<?php echo $badge[0]['icon']; ?> drop-shadow-md"></i>
                    <?php else: ?>
                        <i class="fas fa-folder drop-shadow-md"></i>
                    <?php endif; ?>
                  <?php else: ?>
                    <i class="<?php echo getFileIcon($item); ?> drop-shadow-sm"></i>
                  <?php endif; ?>
                  
                  <!-- Online Indicator for Web Projects (Visual Candy) -->
                   <?php if($isDir && !empty($badge) && $badge[0]['type'] !== 'Folder' && $badge[0]['type'] !== 'PHP Script'): ?>
                    <span class="absolute -bottom-1 -right-1 flex h-3 w-3">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    </span>
                   <?php endif; ?>
                </div>

                <!-- Text Info -->
                <div class="overflow-hidden flex flex-col justify-center h-14">
                  <h3 class="font-bold text-slate-700 dark:text-slate-200 truncate pr-6 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors text-base" title="<?php echo $item; ?>">
                    <?php echo $item; ?>
                  </h3>
                  <div class="flex flex-col">
                    <span class="text-[11px] font-medium text-slate-400 dark:text-slate-500 flex items-center gap-1.5 mt-0.5">
                      <i class="far fa-clock text-[10px]"></i> <?php echo $modTime; ?>
                    </span>
                  </div>
                </div>
              </div>
              
              <!-- Badge & Meta -->
              <div class="mt-4 pt-3 border-t border-slate-100 dark:border-white/5 relative min-h-[46px]">
                  <div class="flex gap-1.5 flex-wrap content-start pr-16">
                      <?php if ($isDir && !empty($badge)): ?>
                        <?php 
                            $maxBadges = 5;
                            // 1. Ambil Top 5 Priority
                            $visibleBadges = array_slice($badge, 0, $maxBadges);
                            $hiddenCount = count($badge) - $maxBadges;

                            // 2. Re-sort Top 5 by Length (Shortest First) for Visual Packing
                            usort($visibleBadges, function($a, $b) {
                                return strlen($a['type']) - strlen($b['type']);
                            });
                        ?>
                        <?php foreach($visibleBadges as $b): ?>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold text-white shadow-sm <?php echo $b['color']; ?>">
                                <?php echo $b['type']; ?>
                            </span>
                        <?php endforeach; ?>
                        
                        <?php if ($hiddenCount > 0): ?>
                            <?php 
                                $hiddenTypes = array_map(function($b) { return $b['type']; }, array_slice($badge, $maxBadges));
                                $tooltip = implode(', ', $hiddenTypes);
                            ?>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-600 cursor-help" title="More: <?php echo $tooltip; ?>">
                                +<?php echo $hiddenCount; ?>
                            </span>
                        <?php endif; ?>
                        
                      <?php elseif ($isDir): ?>
                         <span class="text-[10px] uppercase font-bold text-blue-400/80 tracking-wider">FOLDER</span>
                      <?php else: ?>
                        <span class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500 tracking-wider">
                            <?php echo $info['extension'] ?? 'FILE'; ?>
                        </span>
                      <?php endif; ?>
                  </div>
                  
                  <div class="absolute right-0 top-3">
                      <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800/50 px-2 py-0.5 rounded-md border border-slate-200 dark:border-slate-700/50">
                        <?php echo $size; ?>
                      </span>
                  </div>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>

  <!-- Footer -->
  <footer class="absolute bottom-0 w-full py-6 text-center border-t border-slate-200/50 dark:border-white/5 bg-white/70 dark:bg-slate-900/70 backdrop-blur-md">
    <p class="text-sm text-slate-500 dark:text-slate-400">
      &copy; <?php echo date('Y'); ?> Local Development Environment.
    </p>
    <p class="text-sm text-slate-400 dark:text-slate-500 mt-1">
      Powered by 
      <a href="https://github.com/MasReza354" target="_blank" rel="noopener noreferrer" 
         class="font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300 hover:underline transition-colors">
        Mas Reza
      </a>
      <span class="mx-1">&</span>
      <a href="https://github.com/Tomi-012" target="_blank" rel="noopener noreferrer" 
         class="font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300 hover:underline transition-colors">
        Mas Tomi
      </a>
    </p>
  </footer>

  <!-- Settings Modal -->
  <div id="settingsModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/20 dark:bg-black/80 backdrop-blur-sm transition-all opacity-0 pointer-events-none">
    <div class="glass-panel w-full max-w-lg rounded-2xl shadow-2xl transform scale-95 transition-all bg-white dark:bg-slate-900" id="modalContent">
      <div class="p-6 border-b border-slate-100 dark:border-white/10 flex justify-between items-center">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-500/20 flex items-center justify-center">
            <i class="fas fa-cog text-indigo-600 dark:text-indigo-400"></i>
          </div>
          <h3 class="text-lg font-bold text-slate-800 dark:text-white">Pengaturan</h3>
        </div>
        <button onclick="closeSettings()" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-rose-500 transition-colors">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="p-6 space-y-6">
        <div>
          <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">PHP Version (Active)</label>
          <div class="relative">
            <input type="text" readonly value="PHP v<?php echo phpversion(); ?>" 
                   class="w-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 font-mono rounded-xl px-4 py-2.5 outline-none cursor-not-allowed">
            <i class="fas fa-lock absolute right-4 top-3.5 text-slate-400 text-xs"></i>
          </div>
          <p class="text-xs text-slate-400 dark:text-slate-500 mt-2 ml-1">
            *Untuk mengganti versi PHP, gunakan menu <strong>Laragon Tray Icon</strong> di pojok kanan bawah Windows.
          </p>
        </div>

        <div class="p-4 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-500/20 flex items-start gap-3">
          <i class="fas fa-info-circle text-indigo-500 mt-0.5"></i>
          <p class="text-sm text-indigo-800 dark:text-indigo-200 leading-relaxed">
            Pengaturan lanjutan seperti VHOST dan Database dapat diakses melalui menu Laragon di system tray.
          </p>
        </div>
      </div>
      <div class="p-6 border-t border-slate-100 dark:border-white/10 flex justify-end bg-slate-50/50 dark:bg-slate-800/30 rounded-b-2xl">
        <button onclick="closeSettings()" class="px-4 py-2 text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white font-medium mr-2 transition-colors">Batal</button>
        <button onclick="closeSettings()" class="bg-indigo-600 hover:bg-indigo-500 text-white px-6 py-2 rounded-xl text-sm font-bold shadow-lg shadow-indigo-500/20 transition-all active:scale-95">Simpan Perubahan</button>
      </div>
    </div>
  </div>

  <!-- Notification Toast -->
  <div id="toast" class="fixed bottom-8 left-1/2 -translate-x-1/2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white px-6 py-4 rounded-2xl shadow-2xl transform translate-y-32 transition-all duration-300 z-[110] flex items-center gap-4 font-medium min-w-[300px]">
    <div id="toastIcon" class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-500/20 flex items-center justify-center shrink-0">
      <i class="fas fa-check text-emerald-600 dark:text-emerald-400"></i>
    </div>
    <div class="flex flex-col">
      <span id="toastTitle" class="text-sm font-bold">Success</span>
      <span id="toastMessage" class="text-xs text-slate-500 dark:text-slate-400">Action completed successfully.</span>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="fixed inset-0 z-[120] hidden items-center justify-center bg-slate-900/50 dark:bg-black/80 backdrop-blur-sm transition-all opacity-0 pointer-events-none flex">
    <div id="deleteModalContent" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 w-full max-w-md rounded-2xl shadow-2xl transform scale-95 transition-all">
      <!-- Header -->
      <div class="p-6 text-center">
        <div class="w-16 h-16 mx-auto rounded-full bg-rose-100 dark:bg-rose-500/20 flex items-center justify-center mb-4">
          <i class="fas fa-trash-alt text-2xl text-rose-600 dark:text-rose-400"></i>
        </div>
        <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-2">Hapus Item?</h3>
        <p class="text-slate-500 dark:text-slate-400 text-sm">
          Apakah kamu yakin ingin menghapus <strong id="deleteItemName" class="text-rose-600 dark:text-rose-400"></strong> secara permanen?
        </p>
        <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">
          <i class="fas fa-exclamation-triangle text-amber-500 mr-1"></i>
          Tindakan ini tidak dapat dibatalkan.
        </p>
      </div>
      
      <!-- Footer -->
      <div class="p-4 border-t border-slate-100 dark:border-slate-700/50 flex gap-3 bg-slate-50/50 dark:bg-slate-800/30 rounded-b-2xl">
        <button onclick="closeDeleteModal()" class="flex-1 px-4 py-2.5 text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white font-semibold border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">
          <i class="fas fa-times mr-2"></i>Batal
        </button>
        <button onclick="confirmDelete()" class="flex-1 px-4 py-2.5 bg-rose-600 hover:bg-rose-500 text-white font-semibold rounded-xl shadow-lg shadow-rose-500/20 transition-all active:scale-95">
          <i class="fas fa-trash mr-2"></i>Ya, Hapus
        </button>
      </div>
    </div>
  </div>

  <!-- Create Project Modal -->
  <div id="createModal" class="fixed inset-0 z-[120] hidden items-center justify-center bg-slate-900/50 dark:bg-black/80 backdrop-blur-sm transition-all opacity-0 pointer-events-none flex">
    <div id="createModalContent" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 w-full max-w-md rounded-2xl shadow-2xl transform scale-95 transition-all">
      <!-- Header -->
      <div class="p-6 border-b border-slate-100 dark:border-slate-700/50">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-500/20 flex items-center justify-center">
            <i class="fas fa-folder-plus text-indigo-600 dark:text-indigo-400"></i>
          </div>
          <div>
            <h3 class="text-lg font-bold text-slate-800 dark:text-white">Buat Project Baru</h3>
            <p class="text-xs text-slate-400 dark:text-slate-500">Folder akan dibuat di direktori www</p>
          </div>
        </div>
      </div>
      
      <!-- Body -->
      <div class="p-6">
        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Nama Folder</label>
        <input type="text" id="newProjectName" placeholder="contoh: my-awesome-project" 
               class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all placeholder-slate-400"
               onkeydown="if(event.key === 'Enter') submitCreateProject()">
        <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">
          <i class="fas fa-info-circle mr-1"></i>
          Gunakan huruf kecil, angka, dan tanda hubung (-)
        </p>
      </div>
      
      <!-- Footer -->
      <div class="p-4 border-t border-slate-100 dark:border-slate-700/50 flex gap-3 bg-slate-50/50 dark:bg-slate-800/30 rounded-b-2xl">
        <button onclick="closeCreateModal()" class="flex-1 px-4 py-2.5 text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white font-semibold border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">
          <i class="fas fa-times mr-2"></i>Batal
        </button>
        <button onclick="submitCreateProject()" class="flex-1 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl shadow-lg shadow-indigo-500/20 transition-all active:scale-95">
          <i class="fas fa-plus mr-2"></i>Buat Folder
        </button>
      </div>
    </div>
  </div>

  <!-- Rename Project Modal -->
  <div id="renameModal" class="fixed inset-0 z-[120] hidden items-center justify-center bg-slate-900/50 dark:bg-black/80 backdrop-blur-sm transition-all opacity-0 pointer-events-none flex">
    <div id="renameModalContent" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 w-full max-w-md rounded-2xl shadow-2xl transform scale-95 transition-all">
      <!-- Header -->
      <div class="p-6 border-b border-slate-100 dark:border-slate-700/50">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-500/20 flex items-center justify-center">
            <i class="fas fa-edit text-amber-600 dark:text-amber-400"></i>
          </div>
          <div>
            <h3 class="text-lg font-bold text-slate-800 dark:text-white">Ganti Nama Project</h3>
            <p class="text-xs text-slate-400 dark:text-slate-500">Ubah nama folder project ini</p>
          </div>
        </div>
      </div>
      
      <!-- Body -->
      <div class="p-6">
        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Nama Baru</label>
        <input type="text" id="renameProjectInput" 
               class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all placeholder-slate-400"
               onkeydown="if(event.key === 'Enter') submitRenameProject()">
        <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">
          <i class="fas fa-history mr-1"></i>
          Nama asli: <span id="originalProjectName" class="font-mono text-slate-600 dark:text-slate-300"></span>
        </p>
      </div>
      
      <!-- Footer -->
      <div class="p-4 border-t border-slate-100 dark:border-slate-700/50 flex gap-3 bg-slate-50/50 dark:bg-slate-800/30 rounded-b-2xl">
        <button onclick="closeRenameModal()" class="flex-1 px-4 py-2.5 text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white font-semibold border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">
          <i class="fas fa-times mr-2"></i>Batal
        </button>
        <button onclick="submitRenameProject()" class="flex-1 px-4 py-2.5 bg-amber-600 hover:bg-amber-500 text-white font-semibold rounded-xl shadow-lg shadow-amber-500/20 transition-all active:scale-95">
          <i class="fas fa-save mr-2"></i>Simpan
        </button>
      </div>
    </div>
  </div>

  <!-- IDE Selection Modal -->
  <div id="ideModal" class="fixed inset-0 z-[120] hidden items-center justify-center bg-slate-900/50 dark:bg-black/80 backdrop-blur-sm transition-all opacity-0 pointer-events-none flex">
    <div id="ideModalContent" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 w-full max-w-sm rounded-2xl shadow-2xl transform scale-95 transition-all">
      <div class="p-6">
        <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-1">Buka Project</h3>
        <p class="text-xs text-slate-400 dark:text-slate-500 mb-4">Pilih editor untuk <span id="ideTargetName" class="font-mono text-indigo-500"></span></p>
        
        <div class="space-y-3">
            <!-- VS Code -->
            <button onclick="launchIde('vscode')" class="w-full flex items-center p-3 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-blue-500 dark:hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 group transition-all">
                <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform">
                    <i class="fas fa-code text-lg"></i>
                </div>
                <div class="ml-3 text-left">
                    <div class="text-sm font-bold text-slate-700 dark:text-slate-200 group-hover:text-blue-600 dark:group-hover:text-blue-400">VS Code</div>
                    <div class="text-[10px] text-slate-400">Visual Studio Code</div>
                </div>
            </button>
            
            <!-- Kiro IDE -->
            <button onclick="launchIde('kiro')" class="w-full flex items-center p-3 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-purple-500 dark:hover:border-purple-500 hover:bg-purple-50 dark:hover:bg-purple-900/20 group transition-all">
                <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center text-purple-600 dark:text-purple-400 group-hover:scale-110 transition-transform">
                    <i class="fas fa-terminal text-lg"></i>
                </div>
                <div class="ml-3 text-left">
                    <div class="text-sm font-bold text-slate-700 dark:text-slate-200 group-hover:text-purple-600 dark:group-hover:text-purple-400">Kiro IDE</div>
                    <div class="text-[10px] text-slate-400">AI Power Editor</div>
                </div>
            </button>
            
            <!-- Antigravity -->
            <button onclick="launchIde('antigravity')" class="w-full flex items-center p-3 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-slate-500 dark:hover:border-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 group transition-all">
                <div class="w-10 h-10 rounded-lg bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-slate-700 dark:text-white group-hover:scale-110 transition-transform">
                    <i class="fas fa-rocket text-lg"></i>
                </div>
                <div class="ml-3 text-left">
                    <div class="text-sm font-bold text-slate-700 dark:text-slate-200 group-hover:text-slate-900 dark:group-hover:text-white">Antigravity</div>
                    <div class="text-[10px] text-slate-400">Next Gen AI Editor</div>
                </div>
            </button>
        </div>
      </div>
      <div class="p-4 bg-slate-50/50 dark:bg-slate-800/30 border-t border-slate-100 dark:border-slate-700/50 rounded-b-2xl">
        <button onclick="closeIdeModal()" class="w-full py-2 text-xs font-bold text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-white transition-colors">
            Ah, nanti saja
        </button>
      </div>
    </div>
  </div>

  <script>
    // --- Dark Mode Logic ---
    var themeToggleBtn = document.getElementById('theme-toggle');
    var darkIcon = document.getElementById('theme-toggle-dark-icon');
    var lightIcon = document.getElementById('theme-toggle-light-icon');

    // Change the icons inside the button based on previous settings
    if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      lightIcon.classList.remove('hidden');
    } else {
      darkIcon.classList.remove('hidden');
    }

    themeToggleBtn.addEventListener('click', function() {
      // toggle icons inside button
      darkIcon.classList.toggle('hidden');
      lightIcon.classList.toggle('hidden');

      // if set via local storage previously
      if (localStorage.getItem('color-theme')) {
        if (localStorage.getItem('color-theme') === 'light') {
          document.documentElement.classList.add('dark');
          localStorage.setItem('color-theme', 'dark');
        } else {
          document.documentElement.classList.remove('dark');
          localStorage.setItem('color-theme', 'light');
        }
      } else {
        // if NOT set via local storage previously
        if (document.documentElement.classList.contains('dark')) {
          document.documentElement.classList.remove('dark');
          localStorage.setItem('color-theme', 'light');
        } else {
          document.documentElement.classList.add('dark');
          localStorage.setItem('color-theme', 'dark');
        }
      }
    });

    // --- Keyboard Shortcuts ---
    document.addEventListener('keydown', (e) => {
      if (e.key === '/' && !e.ctrlKey) {
        e.preventDefault();
        document.getElementById('searchInput').focus();
      }
      if (e.key === 'Escape') {
        closeSettings();
        document.getElementById('searchInput').blur();
      }
    });

    // --- UI Functions ---
    // 1. Filter Search default
    function filterItems() {
      applySortAndFilter();
    }
    
    // 2. Main Sort & Filter Logic
    function applySortAndFilter() {
        const input = document.getElementById('searchInput');
        const searchFilter = input.value.toLowerCase();
        const grid = document.getElementById('fileGrid');
        const sortValue = document.getElementById('sortSelect').value;
        const typeFilter = document.querySelector('button[onclick*="setFilter"].bg-indigo-100')?.id.replace('btn-', '') || 'all';

        let cards = Array.from(grid.getElementsByClassName('file-item'));
        
        // A. Filtering & Visibility
        cards.forEach(card => {
            const name = card.getAttribute('data-name');
            const type = card.getAttribute('data-type');
            
            let visible = true;
            if (!name.includes(searchFilter)) visible = false;
            if (typeFilter !== 'all' && type !== typeFilter) visible = false;
            
            card.style.display = visible ? "" : "none";
        });
        
        // B. Sorting
        cards.sort((a, b) => {
            if (sortValue === 'name_asc') {
                return a.getAttribute('data-name').localeCompare(b.getAttribute('data-name'));
            } else if (sortValue === 'name_desc') {
                return b.getAttribute('data-name').localeCompare(a.getAttribute('data-name'));
            } else if (sortValue === 'date_desc') {
                return b.getAttribute('data-date') - a.getAttribute('data-date');
            } else if (sortValue === 'date_asc') {
                return a.getAttribute('data-date') - b.getAttribute('data-date');
            }
            return 0;
        });
        
        // Re-append to grid
        cards.forEach(card => grid.appendChild(card));
    }
    
    // 3. Set Active Filter Button
    function setFilter(type) {
        // Reset styles
        ['btn-all', 'btn-folder', 'btn-file'].forEach(id => {
            const btn = document.getElementById(id);
            btn.className = "px-3 py-1 rounded-lg text-xs font-bold text-slate-500 hover:text-indigo-600 dark:text-slate-400 transition-all";
        });
        
        // Set active style
        const activeBtn = document.getElementById('btn-' + type);
        activeBtn.className = "px-3 py-1 rounded-lg text-xs font-bold bg-indigo-100 dark:bg-indigo-500 text-indigo-700 dark:text-white transition-all";
        
        applySortAndFilter();
    }
    
    // 4. Quick Action Handler (Open Code/Explorer)
    async function quickAction(action, item) {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('item', item);
        
        try {
            const response = await fetch('api.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.status === 'success') {
                showToast(result.message, 'success');
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            console.error(error);
        }
    }
    
    // Initialize
    window.onload = function() {
        // Check localstorage theme is handled by inline script
    };

    const modal = document.getElementById('settingsModal');
    const modalContent = document.getElementById('modalContent');

    function openSettings() {
      modal.classList.remove('hidden', 'pointer-events-none');
      setTimeout(() => {
        modal.classList.remove('opacity-0');
        modalContent.classList.remove('scale-95');
        modalContent.classList.add('scale-100');
      }, 10);
    }

    function closeSettings() {
      modal.classList.add('opacity-0');
      modalContent.classList.remove('scale-100');
      modalContent.classList.add('scale-95');
      setTimeout(() => {
        modal.classList.add('hidden', 'pointer-events-none');
      }, 300);
    }

    function showToast(msg, type = 'success', title = 'Notifikasi') {
      const toast = document.getElementById('toast');
      const toastMsg = document.getElementById('toastMessage');
      const toastTitle = document.getElementById('toastTitle');
      const toastIcon = document.getElementById('toastIcon');

      toastMsg.innerText = msg;
      toastTitle.innerText = title;
      toast.classList.remove('translate-y-32');

      if (type === 'error') {
        toastIcon.className = "w-8 h-8 rounded-full bg-rose-100 dark:bg-rose-500/20 flex items-center justify-center shrink-0";
        toastIcon.innerHTML = '<i class="fas fa-times text-rose-600 dark:text-rose-400"></i>';
      } else {
        toastIcon.className = "w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-500/20 flex items-center justify-center shrink-0";
        toastIcon.innerHTML = '<i class="fas fa-check text-emerald-600 dark:text-emerald-400"></i>';
      }

      setTimeout(() => {
        toast.classList.add('translate-y-32');
      }, 3000);
    }

    // --- File Actions (API) ---
    let pendingDeleteItem = null;
    
    async function handleFileAction(action, item, newName = null) {
      // Use custom modal for delete
      if (action === 'delete') {
        pendingDeleteItem = item;
        openDeleteModal(item);
        return;
      }

      await executeFileAction(action, item, newName);
    }
    
    async function executeFileAction(action, item, newName = null) {
      const formData = new FormData();
      formData.append('action', action);
      formData.append('item', item);
      if (newName) formData.append('newName', newName);

      try {
        const response = await fetch('api.php', {
          method: 'POST',
          body: formData
        });
        
        // Check if response is ok
        if (!response.ok) {
          showToast(`HTTP Error: ${response.status}`, 'error', 'Error Sistem');
          return;
        }
        
        const text = await response.text();
        
        // Try to parse JSON
        let result;
        try {
          result = JSON.parse(text);
        } catch (parseError) {
          console.error('Response:', text);
          showToast('Response bukan JSON valid. Cek console untuk detail.', 'error', 'Error Sistem');
          return;
        }

        if (result.status === 'success') {
          showToast(result.message, 'success', 'Berhasil');
          setTimeout(() => window.location.reload(), 800);
        } else {
          showToast(result.message, 'error', 'Gagal');
        }
      } catch (error) {
        console.error('Fetch error:', error);
        showToast('Gagal menghubungi server. Cek koneksi.', 'error', 'Error Sistem');
      }
    }
    
    // Delete Modal Functions
    function openDeleteModal(item) {
        const modal = document.getElementById('deleteModal');
        const itemName = document.getElementById('deleteItemName');
        itemName.textContent = item;
        
        modal.classList.remove('hidden', 'pointer-events-none');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            document.getElementById('deleteModalContent').classList.remove('scale-95');
            document.getElementById('deleteModalContent').classList.add('scale-100');
        }, 10);
    }
    
    function closeDeleteModal() {
        const modal = document.getElementById('deleteModal');
        modal.classList.add('opacity-0');
        document.getElementById('deleteModalContent').classList.remove('scale-100');
        document.getElementById('deleteModalContent').classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden', 'pointer-events-none');
        }, 300);
        pendingDeleteItem = null;
    }
    
    async function confirmDelete() {
        if (pendingDeleteItem) {
            const itemToDelete = pendingDeleteItem; // Save before closing
            closeDeleteModal();
            await executeFileAction('delete', itemToDelete);
        }
    }

    // Create Modal Functions
    function openCreateModal() {
        const modal = document.getElementById('createModal');
        const input = document.getElementById('newProjectName');
        input.value = '';
        
        modal.classList.remove('hidden', 'pointer-events-none');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            document.getElementById('createModalContent').classList.remove('scale-95');
            document.getElementById('createModalContent').classList.add('scale-100');
            input.focus();
        }, 10);
    }
    
    function closeCreateModal() {
        const modal = document.getElementById('createModal');
        modal.classList.add('opacity-0');
        document.getElementById('createModalContent').classList.remove('scale-100');
        document.getElementById('createModalContent').classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden', 'pointer-events-none');
        }, 300);
    }
    
    async function submitCreateProject() {
        const name = document.getElementById('newProjectName').value.trim();
        if (name) {
            closeCreateModal();
            await executeFileAction('create', name);
        } else {
            showToast('Nama folder tidak boleh kosong', 'error', 'Validasi');
        }
    }

    function createNewProject() {
      openCreateModal();
    }

    // Rename Modal Functions
    let currentRenameItem = null;

    function openRenameModal(item) {
        currentRenameItem = item;
        const modal = document.getElementById('renameModal');
        const input = document.getElementById('renameProjectInput');
        const originalNameSpan = document.getElementById('originalProjectName');
        
        input.value = item;
        originalNameSpan.textContent = item;
        
        modal.classList.remove('hidden', 'pointer-events-none');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            document.getElementById('renameModalContent').classList.remove('scale-95');
            document.getElementById('renameModalContent').classList.add('scale-100');
            input.focus();
            input.select(); // Auto select text
        }, 10);
    }
    
    function closeRenameModal() {
        const modal = document.getElementById('renameModal');
        modal.classList.add('opacity-0');
        document.getElementById('renameModalContent').classList.remove('scale-100');
        document.getElementById('renameModalContent').classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden', 'pointer-events-none');
        }, 300);
        // Don't reset here, let submitRenameProject handle it
    }
    
    async function submitRenameProject() {
        const newName = document.getElementById('renameProjectInput').value.trim();
        const oldName = currentRenameItem; // Save before closing
        
        if (newName && newName !== oldName) {
            currentRenameItem = null; // Reset now
            closeRenameModal();
            await executeFileAction('rename', oldName, newName);
        } else if (newName === oldName) {
            currentRenameItem = null;
            closeRenameModal(); // No change
        } else {
            showToast('Nama folder tidak boleh kosong', 'error', 'Validasi');
        }
    }

    function renameItem(item) {
      openRenameModal(item);
    }

    function deleteItem(item) {
      handleFileAction('delete', item);
    }
    
    // --- IDE Selection Logic ---
    let currentIdeItem = null;
    
    function openIdeSelector(item) {
        currentIdeItem = item;
        document.getElementById('ideTargetName').innerText = item;
        
        const modal = document.getElementById('ideModal');
        modal.classList.remove('hidden', 'pointer-events-none');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            document.getElementById('ideModalContent').classList.remove('scale-95');
            document.getElementById('ideModalContent').classList.add('scale-100');
        }, 10);
    }
    
    function closeIdeModal() {
        const modal = document.getElementById('ideModal');
        modal.classList.add('opacity-0');
        document.getElementById('ideModalContent').classList.remove('scale-100');
        document.getElementById('ideModalContent').classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden', 'pointer-events-none');
            currentIdeItem = null;
        }, 300);
    }
    
    async function launchIde(app) {
        if (!currentIdeItem) return;
        
        const item = currentIdeItem;
        closeIdeModal(); // Close first for better UX
        
        const formData = new FormData();
        formData.append('action', 'open_ide');
        formData.append('item', item);
        formData.append('app', app);
        
        try {
            const response = await fetch('api.php', { method: 'POST', body: formData });
            const result = await response.json();
            
            // Show simple toast
            showToast(result.message, result.status);
            
        } catch (error) {
            console.error(error);
            showToast('Gagal menghubungi API', 'error');
        }
    }
    
    // Initialize
    window.onload = function() {
        // Check localstorage theme is handled by inline script
    };
  </script>
</body>

</html>