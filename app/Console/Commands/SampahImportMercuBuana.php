<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SampahImportMercuBuana extends Command
{
    protected $signature = 'sampah:import-mercu-buana
                            {--bsu=* : ID cms_users BSU tujuan (bisa diulang)}
                            {--all : Semua BSU (privilege 4) kecuali Mercubuana}
                            {--dry-run : Hanya tampilkan yang akan disalin}';

    protected $description = 'Salin kategori dan daftar harga sampah BSU Mercubuana ke BSU lain';

    const SOURCE = 5;
    const BSU_PRIVILEGE = 4;

    public function handle()
    {
        $dry = (bool) $this->option('dry-run');

        $query = DB::table('cms_users')
            ->where('id_cms_privileges', self::BSU_PRIVILEGE)
            ->where('id', '!=', self::SOURCE);

        if (!$this->option('all')) {
            $ids = array_map('intval', (array) $this->option('bsu'));
            if (empty($ids)) {
                $this->error('Pilih tujuan: --bsu=ID (boleh berulang) atau --all');
                return 1;
            }
            $query->whereIn('id', $ids);
        }

        $targets = $query->get(['id', 'name']);
        if ($targets->isEmpty()) {
            $this->error('Tidak ada BSU tujuan yang valid.');
            return 1;
        }

        $srcCats  = DB::table('sampah_categories')->where('admin_id', self::SOURCE)->get();
        $srcItems = DB::table('sampah')->where('admin_id', self::SOURCE)->get();
        $this->info("Sumber: {$srcCats->count()} kategori, {$srcItems->count()} item sampah");
        $this->info($dry ? 'MODE DRY-RUN (tidak ada data ditulis)' : 'MODE TULIS');

        foreach ($targets as $t) {
            $run = function () use ($t, $srcCats, $srcItems, $dry) {
                $now = now();

                // kategori yang sudah ada di tujuan, kunci = nama huruf kecil
                $catByName = [];
                foreach (DB::table('sampah_categories')->where('admin_id', $t->id)->get() as $c) {
                    $catByName[mb_strtolower(trim($c->name))] = $c->id;
                }

                $newCats = 0;
                $map = []; // id kategori sumber => id kategori tujuan
                foreach ($srcCats as $c) {
                    $key = mb_strtolower(trim($c->name));
                    if (isset($catByName[$key])) {
                        $map[$c->id] = $catByName[$key];
                        continue;
                    }
                    $newCats++;
                    if ($dry) {
                        $map[$c->id] = null;
                        continue;
                    }
                    $map[$c->id] = DB::table('sampah_categories')->insertGetId([
                        'admin_id'    => $t->id,
                        'name'        => $c->name,
                        'description' => $c->description,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ]);
                }

                $itemByName = [];
                foreach (DB::table('sampah')->where('admin_id', $t->id)->pluck('name') as $n) {
                    $itemByName[mb_strtolower(trim($n))] = true;
                }

                $newItems = 0;
                foreach ($srcItems as $s) {
                    $key = mb_strtolower(trim($s->name));
                    if (isset($itemByName[$key])) {
                        continue; // sudah ada, harga lama tidak ditimpa
                    }
                    $newItems++;
                    if ($dry) {
                        continue;
                    }
                    DB::table('sampah')->insert([
                        'category_id'  => $s->category_id ? ($map[$s->category_id] ?? null) : null,
                        'admin_id'     => $t->id,
                        'name'         => $s->name,
                        'deskripsi'    => $s->deskripsi,
                        'image'        => $s->image,
                        'price_per_kg' => $s->price_per_kg,
                        'poin_per_kg'  => $s->poin_per_kg,
                        'bsi_harga_id' => $s->bsi_harga_id,
                        'margin_type'  => $s->margin_type,
                        'margin_value' => $s->margin_value,
                        'auto_sync'    => $s->auto_sync,
                        'created_at'   => $now,
                        'updated_at'   => $now,
                    ]);
                }

                $this->line(sprintf('#%d %-40s +%d kategori, +%d item', $t->id, $t->name, $newCats, $newItems));
            };

            $dry ? $run() : DB::transaction($run);
        }

        return 0;
    }
}