<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Reports\ReportService;

class GenerateAdminReport extends Command
{
    // ── Command signature ──────────────────────────────────────
    protected $signature = 'report:admin
                            {--type=sales    : Report type: sales, inventory, customers}
                            {--format=csv    : Output format: csv, json, pdf}';

    protected $description = 'Generate an admin report and save it to storage/app/reports/';

    // ── Valid options ──────────────────────────────────────────
    private const VALID_TYPES   = ['sales', 'inventory', 'customers'];
    private const VALID_FORMATS = ['csv', 'json', 'pdf'];

    public function __construct(private ReportService $reportService)
    {
        parent::__construct();
    }

    // ── Main handle ────────────────────────────────────────────
    public function handle(): int
    {
        $type   = strtolower($this->option('type'));
        $format = strtolower($this->option('format'));

        // 1. Validate options
        if (! $this->validate($type, $format)) {
            return self::FAILURE;
        }

        $this->info("Generating <comment>{$type}</comment> report as <comment>{$format}</comment>...");
        $this->newLine();

        // 2. Progress bar (5 steps)
        $bar = $this->output->createProgressBar(5);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $bar->start();

        $bar->setMessage('Initialising...');      $bar->advance(); sleep(0); // replace sleep() with real work
        $bar->setMessage('Querying database...'); $bar->advance();

        $data = $this->reportService->getData($type);

        $bar->setMessage('Processing data...');  $bar->advance();

        $summaryRows = $this->reportService->getSummaryRows($type, $data);

        $bar->setMessage('Formatting output...'); $bar->advance();

        $savedPath = $this->reportService->save($type, $format, $data);

        $bar->setMessage('Saving report...');     $bar->advance();
        $bar->finish();

        $this->newLine(2);

        // 3. Console summary table
        $this->info('📊 Report Summary');
        $this->table(
            ['Metric', 'Value'],
            $summaryRows
        );

        // 4. Success message
        $this->newLine();
        $this->info("✅ Report saved to: <comment>{$savedPath}</comment>");
        $this->newLine();

        return self::SUCCESS;
    }

    // ── Validation helper ──────────────────────────────────────
    private function validate(string $type, string $format): bool
    {
        if (! in_array($type, self::VALID_TYPES)) {
            $this->error("Invalid --type \"{$type}\". Allowed: " . implode(', ', self::VALID_TYPES));
            return false;
        }

        if (! in_array($format, self::VALID_FORMATS)) {
            $this->error("Invalid --format \"{$format}\". Allowed: " . implode(', ', self::VALID_FORMATS));
            return false;
        }

        return true;
    }
}