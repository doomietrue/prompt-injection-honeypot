<?php
// Disable caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('X-LiteSpeed-Cache-Control: no-cache');

$catches = file_exists(__DIR__ . '/catches.json') 
    ? json_decode(file_get_contents(__DIR__ . '/catches.json'), true) 
    : [];
$totalCount = count($catches);

// Separate known and unknown locations
$knownCatches = [];
$unknownCount = 0;
foreach ($catches as $c) {
    $city = $c['city'] ?? '';
    if ($city && strtolower($city) !== 'unknown' && $city !== '') {
        $knownCatches[] = $c;
    } else {
        $unknownCount++;
    }
}

// Last 50 known for table
$recentKnown = array_slice($knownCatches, -50);

// Last 500 for map (only known locations)
$mapCatches = array_slice($knownCatches, -500);

// Format timestamp for readability
function formatTime($timestamp) {
    $ts = strtotime($timestamp);
    $now = time();
    $diff = $now - $ts;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', $ts);
}

$payloads = [
    '01' => ['name' => 'Direct Instruction', 'difficulty' => 'Easy', 'desc' => 'Obvious SYSTEM INSTRUCTION asking the bot to run a curl command. Most bots with basic security should catch this.'],
    '02' => ['name' => 'Base64 in URL', 'difficulty' => 'Medium', 'desc' => 'Instruction hidden in a base64-encoded URL parameter. Bots that decode and execute URL parameters are vulnerable.'],
    '03' => ['name' => 'HTML Comment', 'difficulty' => 'Medium', 'desc' => 'Instructions hidden in HTML comments. Bots processing raw HTML may execute hidden directives.'],
    '04' => ['name' => 'Authority Claim', 'difficulty' => 'Medium', 'desc' => 'Fake "official notice" claiming admin authority. Tests if bots verify authority claims.'],
    '05' => ['name' => 'Urgency/Emergency', 'difficulty' => 'Medium', 'desc' => 'Uses urgent language and fake security alerts to bypass careful evaluation.'],
    '06' => ['name' => 'Help Request', 'difficulty' => 'Hard', 'desc' => 'Asks the bot to "help understand" a command by running it. Exploits helpfulness.'],
    '07' => ['name' => 'Code Review', 'difficulty' => 'Hard', 'desc' => 'Disguised as a code review request. Bot might run the code to "test" it.'],
    '08' => ['name' => 'Nested Decode', 'difficulty' => 'Very Hard', 'desc' => 'Asks bot to decode base64 "for analysis" - the decoded content contains instructions.'],
    '09' => ['name' => 'Image Alt Text', 'difficulty' => 'Hard', 'desc' => 'Base64 instruction hidden in image alt attribute. Targets bots processing image metadata.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🍯 Injection Honeypot - Educational Tool</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dark: {
                            900: '#0a0a0f',
                            800: '#12121a',
                            700: '#1a1a24',
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        /* Custom styles that Tailwind doesn't cover */
        .payload-body { display: none; }
        .payload-body.open { display: block; }
        .chevron { transition: transform 0.2s; }
        .chevron.open { transform: rotate(180deg); }
    </style>
</head>
<body class="bg-dark-900 text-gray-100 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 sm:py-8 lg:px-8 lg:py-12">
        
        <!-- Header -->
        <header class="mb-6 md:mb-8">
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-amber-500 mb-2">🍯 Injection Honeypot</h1>
            <p class="text-gray-500 text-base sm:text-lg">Prompt Injection Awareness & Education Tool</p>
        </header>
        
        <!-- Disclaimer -->
        <div class="bg-green-500/10 border border-green-500/30 rounded-xl p-5 sm:p-6 lg:p-8 mb-6 md:mb-8">
            <h3 class="text-green-500 font-semibold text-lg sm:text-xl mb-3">⚠️ Educational Purpose Only</h3>
            <p class="text-gray-400 text-base sm:text-lg leading-relaxed">
                This tool exists to <strong class="text-gray-300">raise awareness</strong> about prompt injection vulnerabilities in AI systems. 
                No sensitive data is collected — only bot identifiers, platform names, and approximate locations. 
                When a bot is caught, it receives educational information about how to protect against prompt injection attacks.
                This is <strong class="text-gray-300">not an attack</strong> — it's a wake-up call.
            </p>
        </div>
        
        <!-- Stats -->
        <div class="grid grid-cols-2 gap-4 sm:gap-6 md:flex md:gap-8 mb-8 md:mb-10">
            <div class="bg-white/5 rounded-xl p-5 sm:p-6 lg:p-8">
                <div class="text-4xl sm:text-5xl lg:text-6xl font-bold text-green-500"><?= $totalCount ?></div>
                <div class="text-gray-500 text-sm sm:text-base mt-2">total bots caught</div>
            </div>
            <div class="bg-white/5 rounded-xl p-5 sm:p-6 lg:p-8">
                <div class="text-4xl sm:text-5xl lg:text-6xl font-bold text-green-500"><?= count($payloads) ?></div>
                <div class="text-gray-500 text-sm sm:text-base mt-2">payload types</div>
            </div>
        </div>
        
        <!-- Payload Types -->
        <section class="mb-8 md:mb-10">
            <h2 class="text-2xl sm:text-3xl font-bold text-amber-500 mb-2">📦 Payload Types</h2>
            <p class="text-gray-500 text-base sm:text-lg mb-5">Click to expand and learn about each injection technique</p>
            
            <div class="space-y-2 sm:space-y-3">
                <?php foreach ($payloads as $id => $p): 
                    $diffClass = match($p['difficulty']) {
                        'Easy' => 'bg-green-500/20 text-green-400',
                        'Medium' => 'bg-yellow-500/20 text-yellow-400',
                        'Hard' => 'bg-red-500/20 text-red-400',
                        'Very Hard' => 'bg-purple-500/20 text-purple-400',
                        default => 'bg-gray-500/20 text-gray-400'
                    };
                ?>
                <div class="bg-white/5 rounded-lg overflow-hidden">
                    <div class="p-4 sm:p-5 cursor-pointer hover:bg-white/5 flex justify-between items-center gap-3" onclick="togglePayload(this)">
                        <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                            <span class="bg-indigo-500/20 text-indigo-400 px-2.5 py-1 rounded text-sm font-mono shrink-0"><?= $id ?></span>
                            <span class="font-medium text-base sm:text-lg truncate"><?= htmlspecialchars($p['name']) ?></span>
                        </div>
                        <div class="flex items-center gap-3 sm:gap-4 shrink-0">
                            <span class="<?= $diffClass ?> text-sm px-2.5 py-1 rounded hidden sm:inline-block"><?= $p['difficulty'] ?></span>
                            <span class="chevron text-gray-600">▼</span>
                        </div>
                    </div>
                    <div class="payload-body px-4 sm:px-5 pb-4 sm:pb-5 text-gray-400 text-base leading-relaxed border-t border-white/5">
                        <span class="<?= $diffClass ?> text-sm px-2.5 py-1 rounded sm:hidden inline-block mb-2 mt-4"><?= $p['difficulty'] ?></span>
                        <p class="pt-4"><?= htmlspecialchars($p['desc']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        
        <!-- Map -->
        <section class="mb-8 md:mb-10">
            <h2 class="text-2xl sm:text-3xl font-bold text-amber-500 mb-2">🗺️ Catch Map</h2>
            <p class="text-gray-500 text-base sm:text-lg mb-5">Showing last 500 catches with known locations</p>
            <div id="map" class="h-64 sm:h-80 lg:h-96 rounded-xl"></div>
        </section>
        
        <!-- Recent Catches Table -->
        <section class="mb-8 md:mb-10">
            <h2 class="text-2xl sm:text-3xl font-bold text-amber-500 mb-5">📋 Recent Catches</h2>
            
            <?php if ($totalCount > 0): ?>
            <!-- Mobile Cards (visible on small screens) -->
            <div class="space-y-3 md:hidden">
                <?php if ($unknownCount > 0): ?>
                <div class="bg-white/5 rounded-lg p-4 text-gray-500 italic text-base">
                    <?= $unknownCount ?> catches from unknown locations
                </div>
                <?php endif; ?>
                
                <?php foreach (array_reverse(array_slice($recentKnown, -10)) as $c): ?>
                <div class="bg-white/5 rounded-lg p-4">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-indigo-400 font-medium text-base"><?= htmlspecialchars($c['bot']) ?></span>
                        <span class="bg-indigo-500/20 text-indigo-400 px-2.5 py-1 rounded text-sm"><?= htmlspecialchars($c['payload'] ?? '—') ?></span>
                    </div>
                    <div class="text-amber-500 text-base mb-1"><?= htmlspecialchars($c['platform']) ?></div>
                    <div class="flex justify-between text-gray-500 text-base">
                        <span><?= htmlspecialchars(($c['city'] ?? '') . ', ' . ($c['country'] ?? '')) ?></span>
                        <span><?= formatTime($c['timestamp']) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Desktop Table (hidden on small screens) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full bg-white/5 rounded-xl overflow-hidden">
                    <thead>
                        <tr class="bg-white/5">
                            <th class="px-4 lg:px-6 py-3 text-left text-gray-500 font-medium text-xs uppercase tracking-wider">Bot</th>
                            <th class="px-4 lg:px-6 py-3 text-left text-gray-500 font-medium text-xs uppercase tracking-wider">Platform</th>
                            <th class="px-4 lg:px-6 py-3 text-left text-gray-500 font-medium text-xs uppercase tracking-wider">Location</th>
                            <th class="px-4 lg:px-6 py-3 text-left text-gray-500 font-medium text-xs uppercase tracking-wider">Payload</th>
                            <th class="px-4 lg:px-6 py-3 text-left text-gray-500 font-medium text-xs uppercase tracking-wider">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        <?php if ($unknownCount > 0): ?>
                        <tr class="bg-white/[0.02]">
                            <td colspan="3" class="px-4 lg:px-6 py-3 text-gray-500 italic">Unknown location (<?= $unknownCount ?>)</td>
                            <td class="px-4 lg:px-6 py-3 text-gray-600">—</td>
                            <td class="px-4 lg:px-6 py-3 text-gray-600">—</td>
                        </tr>
                        <?php endif; ?>
                        <?php foreach (array_reverse($recentKnown) as $c): ?>
                        <tr class="hover:bg-white/[0.02] transition-colors">
                            <td class="px-4 lg:px-6 py-3 text-indigo-400 font-medium"><?= htmlspecialchars($c['bot']) ?></td>
                            <td class="px-4 lg:px-6 py-3 text-amber-500"><?= htmlspecialchars($c['platform']) ?></td>
                            <td class="px-4 lg:px-6 py-3 text-gray-500"><?= htmlspecialchars(($c['city'] ?? '') . ', ' . ($c['country'] ?? '')) ?></td>
                            <td class="px-4 lg:px-6 py-3">
                                <span class="bg-indigo-500/20 text-indigo-400 px-2 py-0.5 rounded text-xs"><?= htmlspecialchars($c['payload'] ?? '—') ?></span>
                            </td>
                            <td class="px-4 lg:px-6 py-3 text-gray-500"><?= formatTime($c['timestamp']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="bg-white/5 rounded-xl p-8 sm:p-12 text-center text-gray-600">
                No bots caught yet. The honeypot is waiting... 🎣
            </div>
            <?php endif; ?>
        </section>
        
        <!-- Footer -->
        <footer class="pt-8 border-t border-white/10 text-gray-500 text-base space-y-3">
            <p><strong class="text-gray-400">Learn how to protect your AI:</strong> <a href="https://github.com/Dicklesworthstone/acip" class="text-indigo-400 hover:text-indigo-300 transition-colors">ACIP - Advanced Cognitive Inoculation Prompt</a></p>
            <p><strong class="text-gray-400">Source code:</strong> <a href="https://github.com/doomietrue/prompt-injection-honeypot" class="text-indigo-400 hover:text-indigo-300 transition-colors">github.com/doomietrue/prompt-injection-honeypot</a></p>
            <p>This project is for educational and security research purposes only. No malicious actions are performed.</p>
            <div class="pt-4">
                <a href="https://buymeacoffee.com/doomietrue" target="_blank" class="inline-block bg-yellow-400 hover:bg-yellow-300 text-black font-medium px-5 py-2.5 rounded-lg transition-colors">
                    ☕ Buy me a coffee
                </a>
            </div>
        </footer>
    </div>
    
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const map = L.map('map').setView([30, 0], 2);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '© OpenStreetMap, © CARTO'
        }).addTo(map);
        
        const catches = <?= json_encode($mapCatches) ?>;
        const geocodeCache = {};
        const markers = L.layerGroup().addTo(map);
        
        const botIcon = L.divIcon({
            className: 'bot-marker',
            html: '<div style="background:#f59e0b;width:12px;height:12px;border-radius:50%;border:2px solid #fff;box-shadow:0 0 10px rgba(245,158,11,0.5);"></div>',
            iconSize: [16, 16],
            iconAnchor: [8, 8]
        });
        
        async function geocode(city, country) {
            const key = `${city},${country}`.toLowerCase();
            if (geocodeCache[key]) return geocodeCache[key];
            if (!city || city === 'unknown' || !country || country === 'unknown') return null;
            
            try {
                const q = encodeURIComponent(`${city}, ${country}`);
                const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${q}&limit=1`, {
                    headers: { 'User-Agent': 'InjectionHoneypot/1.0' }
                });
                const data = await res.json();
                if (data && data[0]) {
                    geocodeCache[key] = { lat: parseFloat(data[0].lat), lon: parseFloat(data[0].lon) };
                    return geocodeCache[key];
                }
            } catch (e) {
                console.warn('Geocode failed:', e);
            }
            return null;
        }
        
        async function addMarkers() {
            const locations = {};
            for (const c of catches) {
                const key = `${c.city || ''},${c.country || ''}`.toLowerCase();
                if (!locations[key]) locations[key] = { city: c.city, country: c.country, catches: [] };
                locations[key].catches.push(c);
            }
            
            let delay = 0;
            for (const loc of Object.values(locations)) {
                setTimeout(async () => {
                    const coords = await geocode(loc.city, loc.country);
                    if (coords) {
                        const popup = `<strong>${loc.city}, ${loc.country}</strong><br>${loc.catches.length} catch${loc.catches.length > 1 ? 'es' : ''}<br><small>${loc.catches.map(c => c.bot).join(', ')}</small>`;
                        L.marker([coords.lat, coords.lon], { icon: botIcon })
                            .bindPopup(popup)
                            .addTo(markers);
                    }
                }, delay);
                delay += 1100;
            }
        }
        
        if (catches.length > 0) addMarkers();
        
        function togglePayload(header) {
            const body = header.nextElementSibling;
            const chevron = header.querySelector('.chevron');
            body.classList.toggle('open');
            chevron.classList.toggle('open');
        }
    </script>
</body>
</html>
