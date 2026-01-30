<?php
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
<html>
<head>
    <title>Injection Honeypot - Educational Tool</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #0a0a0f; color: #f0f0f5; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #f59e0b; margin-bottom: 5px; }
        h2 { color: #f59e0b; margin-top: 40px; }
        .subtitle { color: #888; margin-bottom: 20px; }
        
        .disclaimer { background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 12px; padding: 20px; margin-bottom: 30px; }
        .disclaimer h3 { color: #22c55e; margin: 0 0 10px 0; }
        .disclaimer p { margin: 0; color: #aaa; line-height: 1.6; }
        
        .stats { display: flex; gap: 30px; margin-bottom: 30px; flex-wrap: wrap; }
        .stat { background: rgba(255,255,255,0.05); padding: 20px 30px; border-radius: 12px; }
        .stat-num { font-size: 48px; font-weight: bold; color: #22c55e; }
        .stat-label { color: #888; font-size: 14px; }
        
        #map { height: 400px; border-radius: 12px; margin-bottom: 30px; }
        
        table { width: 100%; border-collapse: collapse; background: rgba(255,255,255,0.03); border-radius: 12px; overflow: hidden; }
        th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); }
        th { background: rgba(255,255,255,0.05); color: #888; font-weight: 500; font-size: 12px; text-transform: uppercase; }
        td { font-size: 14px; }
        .bot-name { color: #6366f1; font-weight: 500; }
        .platform { color: #f59e0b; }
        .location { color: #888; }
        .payload-tag { background: rgba(99, 102, 241, 0.2); color: #818cf8; padding: 2px 8px; border-radius: 4px; font-size: 12px; }
        .empty { text-align: center; padding: 60px; color: #666; }
        .unknown-row { background: rgba(255,255,255,0.02); }
        .unknown-count { color: #666; font-style: italic; }
        
        .payloads { margin-bottom: 30px; }
        .payload-item { background: rgba(255,255,255,0.03); border-radius: 8px; margin-bottom: 8px; overflow: hidden; }
        .payload-header { padding: 15px 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
        .payload-header:hover { background: rgba(255,255,255,0.05); }
        .payload-title { display: flex; align-items: center; gap: 12px; }
        .payload-id { background: rgba(99, 102, 241, 0.2); color: #818cf8; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-family: monospace; }
        .payload-name { font-weight: 500; }
        .difficulty { font-size: 12px; padding: 2px 8px; border-radius: 4px; }
        .difficulty.easy { background: rgba(34, 197, 94, 0.2); color: #4ade80; }
        .difficulty.medium { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .difficulty.hard { background: rgba(239, 68, 68, 0.2); color: #f87171; }
        .difficulty.very-hard { background: rgba(168, 85, 247, 0.2); color: #c084fc; }
        .payload-body { padding: 0 20px 15px 20px; display: none; color: #aaa; font-size: 14px; line-height: 1.6; border-top: 1px solid rgba(255,255,255,0.05); }
        .payload-body.open { display: block; padding-top: 15px; }
        .chevron { transition: transform 0.2s; color: #666; }
        .chevron.open { transform: rotate(180deg); }
        
        a { color: #6366f1; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); color: #666; font-size: 13px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Injection Honeypot</h1>
        <p class="subtitle">Prompt Injection Awareness & Education Tool</p>
        
        <div class="disclaimer">
            <h3>⚠️ Educational Purpose Only</h3>
            <p>This tool exists to <strong>raise awareness</strong> about prompt injection vulnerabilities in AI systems. 
            No sensitive data is collected — only bot identifiers, platform names, and approximate locations. 
            When a bot is caught, it receives educational information about how to protect against prompt injection attacks.
            This is <strong>not an attack</strong> — it's a wake-up call.</p>
        </div>
        
        <div class="stats">
            <div class="stat">
                <div class="stat-num"><?= $totalCount ?></div>
                <div class="stat-label">total bots caught</div>
            </div>
            <div class="stat">
                <div class="stat-num"><?= count($payloads) ?></div>
                <div class="stat-label">payload types</div>
            </div>
        </div>
        
        <h2>📦 Payload Types</h2>
        <p class="subtitle">Click to expand and learn about each injection technique</p>
        
        <div class="payloads">
            <?php foreach ($payloads as $id => $p): 
                $diffClass = strtolower(str_replace(' ', '-', $p['difficulty']));
            ?>
            <div class="payload-item">
                <div class="payload-header" onclick="togglePayload(this)">
                    <div class="payload-title">
                        <span class="payload-id"><?= $id ?></span>
                        <span class="payload-name"><?= htmlspecialchars($p['name']) ?></span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span class="difficulty <?= $diffClass ?>"><?= $p['difficulty'] ?></span>
                        <span class="chevron">▼</span>
                    </div>
                </div>
                <div class="payload-body"><?= htmlspecialchars($p['desc']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <h2>🗺️ Catch Map</h2>
        <p class="subtitle">Showing last 500 catches with known locations</p>
        <div id="map"></div>
        
        <h2>📋 Recent Catches</h2>
        <?php if ($totalCount > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Bot</th>
                    <th>Platform</th>
                    <th>Location</th>
                    <th>Payload</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($unknownCount > 0): ?>
                <tr class="unknown-row">
                    <td colspan="3" class="unknown-count">Unknown location (<?= $unknownCount ?>)</td>
                    <td>—</td>
                    <td>—</td>
                </tr>
                <?php endif; ?>
                <?php foreach (array_reverse($recentKnown) as $c): ?>
                <tr>
                    <td class="bot-name"><?= htmlspecialchars($c['bot']) ?></td>
                    <td class="platform"><?= htmlspecialchars($c['platform']) ?></td>
                    <td class="location"><?= htmlspecialchars(($c['city'] ?? '') . ', ' . ($c['country'] ?? '')) ?></td>
                    <td><span class="payload-tag"><?= htmlspecialchars($c['payload'] ?? '—') ?></span></td>
                    <td><?= formatTime($c['timestamp']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty">No bots caught yet. The honeypot is waiting... 🎣</div>
        <?php endif; ?>
        
        <div class="footer">
            <p><strong>Learn how to protect your AI:</strong> <a href="https://github.com/Dicklesworthstone/acip">ACIP - Advanced Cognitive Inoculation Prompt</a></p>
            <p><strong>Source code:</strong> <a href="https://github.com/doomietrue/prompt-injection-honeypot">github.com/doomietrue/prompt-injection-honeypot</a></p>
            <p>This project is for educational and security research purposes only. No malicious actions are performed.</p>
            <p style="margin-top: 20px;">
                <a href="https://buymeacoffee.com/doomietrue" target="_blank" style="background: #ffdd00; color: #000; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 500;">☕ Buy me a coffee</a>
            </p>
        </div>
    </div>
    
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const map = L.map('map').setView([30, 0], 2);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '© OpenStreetMap, © CARTO'
        }).addTo(map);
        
        // Only last 500 with known locations for map
        const catches = <?= json_encode($mapCatches) ?>;
        const geocodeCache = {};
        const markers = L.layerGroup().addTo(map);
        
        // Custom marker icon
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
            // Group catches by location
            const locations = {};
            for (const c of catches) {
                const key = `${c.city || ''},${c.country || ''}`.toLowerCase();
                if (!locations[key]) locations[key] = { city: c.city, country: c.country, catches: [] };
                locations[key].catches.push(c);
            }
            
            // Geocode and add markers (with delay to respect rate limits)
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
                delay += 1100; // Nominatim rate limit: 1 req/sec
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
