<?php
// Guards
if (! extension_loaded('sodium')) {
    exit("[ERROR] ext-sodium not loaded. argon2id unavailable.\n");
}

$password = 'SuperSecurePassword123!';
$memories = [32768, 65536, 131072, 262144];
$times    = [1, 2, 3, 4, 5];
$threads  = [1, 2, 4];
$runs     = 5;

echo "=========================================================\n";
echo "Benchmarking Argon2id — " . date('Y-m-d H:i:s') . "\n";
echo "PHP " . PHP_VERSION . " | " . PHP_OS . "\n";
echo "=========================================================\n";
printf("%-12s | %-6s | %-8s | %-12s | %-12s\n", "Memory (KB)", "Time", "Threads", "Avg Time (ms)", "Status");
echo "---------------------------------------------------------\n";

$candidates = [];

foreach ($memories as $memory) {
    foreach ($times as $time) {
        foreach ($threads as $thread) {
            $options = [
                'memory_cost' => $memory,
                'time_cost'   => $time,
                'threads'     => $thread,
            ];

            password_hash($password, PASSWORD_ARGON2ID, $options); // warmup

            $total = 0;
            for ($i = 0; $i < $runs; $i++) {
                $start  = hrtime(true);                                         // ← hrtime
                password_hash($password, PASSWORD_ARGON2ID, $options);
                $total += hrtime(true) - $start;
            }

            $avgMs = ($total / $runs) / 1_000_000;

            $status = match(true) {
                $avgMs >= 250 && $avgMs <= 500 => 'Target Match',
                $avgMs > 500                   => 'Too Slow',
                default                        => 'Too Fast',
            };

            printf("%-12d | %-6d | %-8d | %-12.2f | %-12s\n",
                $memory, $time, $thread, $avgMs, $status);

            if ($status === 'Target Match') {
                $candidates[] = compact('memory', 'time', 'thread', 'avgMs');
            }
        }
    }
}

echo "=========================================================\n";

// ── Recommendation ────────────────────────────────────────
if (! empty($candidates)) {
    usort($candidates, fn($a, $b) => abs($a['avgMs'] - 375) <=> abs($b['avgMs'] - 375));
    $best = $candidates[0];
    echo "\n✅ Recommended (closest to 375ms midpoint):\n";
    printf("   ARGON2ID_MEMORY=%d\n", $best['memory']);
    printf("   ARGON2ID_TIME=%d\n",   $best['time']);
    printf("   ARGON2ID_THREADS=%d\n",$best['thread']);
    printf("   avg=%.2fms\n",          $best['avgMs']);
} else {
    echo "\n⚠️  No config hit 250–500ms. Adjust \$memories/\$times ranges.\n";
}