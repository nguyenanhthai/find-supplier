<?php

namespace App\Console\Commands;

use App\Imports\SuppliersImport;
use App\Models\Supplier;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class FindSupplier extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "find:supplier {--fuzzy}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Find supplier name from JSON OCR result";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $start = microtime(true);
        $this->info("Reading JSON OCR result...");
        $content = File::get("database/xtracta/invoice.txt");
        $lines = preg_split('/\r\n|\r|\n/', $content);

        $this->info(sprintf("Reading %d json data words...", count($lines)));
        $data = [];
        foreach ($lines as $line) {
            $item = json_decode(str_replace('\'', '"', $line));
            $data[] = $item;
        }

        $collection = new Collection($data);
        $collection = $collection->sortBy([
            ['page_id', 'asc'],
            ['line_id', 'asc'],
            ['pos_id', 'asc'],
        ]);

        $text_content = "";
        foreach ($collection as $item) {
            $text_content .= $item->word . ' ';
        }

        $exact_mode = !$this->option('fuzzy');;

        $results = [];

        $this->info(sprintf("Searching supplier name in %s mode", $exact_mode ? 'EXACT' : 'FUZZY'));
        foreach ($collection as $index => $item) {
            $suppliers = Supplier::search($item->word)->get();
            if ($suppliers->count()) {
                foreach ($suppliers as $supplier) {
                    if ($exact_mode) {
                        if (strpos($text_content, $supplier->name) !== false) {
                            $results[$supplier->name] = 100;
                        }
                    } else {
                        // fuzy mode
                        // calculate point by comparing matching words
                        $words_in_name = explode(' ', $supplier->name);
                        $no_words = count($words_in_name);

                        // find offset of 1st match word
                        $offset = array_search($item->word, $words_in_name);

                        $total_distance = 0;
                        for ($i = 0; $i < $no_words; $i++) {
                            $word_in_document = $collection[$index - $offset + $i];

                            // https://www.php.net/manual/en/function.levenshtein.php
                            $l_distance = levenshtein($word_in_document->word, $words_in_name[$i]);
                            $total_distance += $l_distance;
                        }

                        $avg_point = 100 - ($total_distance / strlen($supplier->name)) * 100;
                        if (isset($results[$supplier->name])) {
                            // keep greatest point for supplier name
                            if ($avg_point > $results[$supplier->name]) {
                                $results[$supplier->name] = $avg_point;
                            }
                        } else {
                            $results[$supplier->name] = $avg_point;
                        }
                    }
                }
            }
        }

        arsort($results);

        if (!$exact_mode) {
            $this->info("Top 10 matching: ");
            $results = array_slice($results, 0, 10);
        }

        $rows = [];
        foreach ($results  as $name => $point) {
            $rows[] = [
                'Supplier Name' => $name,
                'Match Point' => $point,
            ];
        }

        $this->table(
            ['Supplier Name', 'Match Point'],
            $rows
        );
        $time = microtime(true) - $start;
        $this->info(sprintf("Cost: %d miliseconds.", $time * 1000));
        $this->info("DONE. Finished Test.");
    }
}
