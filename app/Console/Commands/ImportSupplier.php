<?php

namespace App\Console\Commands;

use App\Imports\SuppliersImport;
use App\Models\Supplier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportSupplier extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "import:supplier";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Import suppliers from database/xtracta";

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
        ini_set("memory_limit", "1024M");
        $this->info("Truncating suppliers table");
        DB::table("suppliers")->truncate();
        $this->info("Done.");

        $this->info("Importing suppliers from database/xtracta/suppliernames.txt");
        $file_path = base_path("database/xtracta/suppliernames.txt");
        (new SuppliersImport())->withOutput($this->output)->import($file_path);
        $this->info("Done.");

        $no_dummy = 100000;
        $this->info(sprintf("Creating %d other suppliers", $no_dummy));
        Supplier::factory()->count($no_dummy)->create();
        $this->info("Done.");
    }
}
