<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DocumentItem;
use App\Models\Document;

class FixDocumentPricesCommand extends Command
{
    protected $signature = 'grafired:fix-prices {--dry-run : Show what would be fixed without making changes}';
    protected $description = 'Fix DocumentItems with zero prices by recalculating them automatically';

    public function handle()
    {
        $this->info('🔍 Scanning for DocumentItems with zero prices...');
        
        $zeroItems = DocumentItem::where('unit_price', 0)
            ->orWhere('total_price', 0)
            ->with('itemable')
            ->get();

        if ($zeroItems->isEmpty()) {
            $this->info('✅ No items with zero prices found. All good!');
            return 0;
        }

        $this->warn("Found {$zeroItems->count()} items with zero prices:");
        
        $this->table(
            ['ID', 'Document', 'Description', 'Type', 'Unit Price', 'Total Price'],
            $zeroItems->map(function ($item) {
                return [
                    $item->id,
                    $item->document_id,
                    \Illuminate\Support\Str::limit($item->description, 30),
                    class_basename($item->itemable_type ?? 'N/A'),
                    '$' . number_format($item->unit_price, 2),
                    '$' . number_format($item->total_price, 2)
                ];
            })
        );

        if ($this->option('dry-run')) {
            $this->info('🔍 DRY RUN MODE: No changes will be made.');
            return 0;
        }

        if (!$this->confirm('Do you want to fix these items?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->info('🔧 Fixing prices...');
        $bar = $this->output->createProgressBar($zeroItems->count());

        $fixed = 0;
        $errors = 0;
        $documentsToRecalculate = [];

        foreach ($zeroItems as $item) {
            if ($item->calculateAndUpdatePrices()) {
                $fixed++;
                $documentsToRecalculate[$item->document_id] = true;
            } else {
                $errors++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Recalculate document totals
        if (!empty($documentsToRecalculate)) {
            $this->info('📊 Recalculating document totals...');
            
            $documents = Document::whereIn('id', array_keys($documentsToRecalculate))->get();
            foreach ($documents as $document) {
                $document->recalculateTotals();
            }
            
            $this->info("   ✓ Recalculated {$documents->count()} documents");
        }

        // Summary
        $this->newLine();
        $this->info("✅ Price fixing completed!");
        $this->line("   • Fixed items: {$fixed}");
        
        if ($errors > 0) {
            $this->warn("   • Errors: {$errors}");
        }

        if ($fixed > 0) {
            $this->info('💡 Tip: You can now check the PDF generation at /documents/{id}/pdf');
        }

        return 0;
    }
}
