<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * نسخة احتياطية لقاعدة البيانات بـ PHP فقط (هوستنجر يقفل mysqldump و proc_open):
 * ملف SQL مضغوط ينرفع لـ R2 بمجلد backups/، ونحتفظ بآخر 30 نسخة.
 */
class Backup
{
    public const KEEP = 30;
    public const FLAG = 'app/backup.last';

    /** جداول ما نحتاج بياناتها (تنحفظ بنيتها بس) */
    private const SKIP_DATA = ['sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'personal_access_tokens'];

    public static function last(): ?int
    {
        $f = storage_path(self::FLAG);

        return is_file($f) ? (int) trim((string) file_get_contents($f)) : null;
    }

    /** يشتغل بالخلفية بعد الطلبات: نسخة وحدة كل 24 ساعة (يغني عن الكرون) */
    public static function due(): bool
    {
        return R2::enabled() && (self::last() ?? 0) < time() - 86400;
    }

    public static function runIfDue(): void
    {
        if (! self::due()) {
            return;
        }
        $lock = cache()->lock('academy-backup', 1800);
        if (! $lock->get()) {
            return;
        }
        try {
            if (self::due()) {
                self::run();
            }
        } catch (\Throwable $e) {
            report($e);
            // محاولة ثانية بعد ساعة بدل كل طلب
            @file_put_contents(storage_path(self::FLAG), (string) (time() - 86400 + 3600));
        } finally {
            $lock->release();
        }
    }

    /** يسوي النسخة ويرفعها، ويرجع [key, size] */
    public static function run(): array
    {
        @set_time_limit(0);
        $tmp = storage_path('app/backup-'.date('Ymd-His').'.sql.gz');
        self::dump($tmp);
        $size = filesize($tmp);
        $key = 'backups/'.date('Y/m').'/ghani-academy-'.date('Y-m-d-His').'.sql.gz';
        try {
            R2::put($tmp, $key);
        } finally {
            @unlink($tmp);
        }
        file_put_contents(storage_path(self::FLAG), (string) time());
        self::prune();

        return [$key, $size];
    }

    public static function list(): array
    {
        return collect(R2::list('backups/', true)['files'])
            ->filter(fn ($f) => str_ends_with($f['key'], '.sql.gz'))
            ->sortByDesc('key')->values()->all();
    }

    private static function prune(): void
    {
        foreach (array_slice(self::list(), self::KEEP) as $old) {
            R2::delete($old['key']);
        }
    }

    /** يكتب ملف SQL مضغوط (MySQL، وSQLite للاختبار) */
    public static function dump(string $path): void
    {
        $db = DB::connection();
        $driver = $db->getDriverName();
        $pdo = $db->getPdo();
        $gz = gzopen($path, 'wb6');
        $w = fn (string $s) => gzwrite($gz, $s);
        $w("-- Ghani Academy backup ".date('c')."\n-- Restore: phpMyAdmin > Import (this .sql.gz file)\n");
        if ($driver === 'mysql' || $driver === 'mariadb') {
            $w("SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");
            $tables = array_map(fn ($r) => array_values((array) $r)[0], $db->select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"'));
        } else {
            $tables = array_map(fn ($r) => $r->name, $db->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"));
        }
        $q = fn (string $n) => $driver === 'sqlite' ? '"'.str_replace('"', '""', $n).'"' : '`'.str_replace('`', '``', $n).'`';

        foreach ($tables as $t) {
            $create = $driver === 'sqlite'
                ? $db->selectOne("SELECT sql FROM sqlite_master WHERE type='table' AND name = ?", [$t])->sql
                : array_values((array) $db->selectOne('SHOW CREATE TABLE '.$q($t)))[1];
            $w("\n-- ----------------------------\n-- $t\n-- ----------------------------\nDROP TABLE IF EXISTS ".$q($t).";\n$create;\n");
            if (in_array($t, self::SKIP_DATA, true)) {
                continue;
            }
            $stmt = $pdo->query('SELECT * FROM '.$q($t));
            $stmt->setFetchMode(\PDO::FETCH_ASSOC);
            $batch = [];
            $cols = null;
            foreach ($stmt as $row) {
                $cols ??= '('.implode(', ', array_map($q, array_keys($row))).')';
                $batch[] = '('.implode(', ', array_map(fn ($v) => $v === null ? 'NULL' : (is_int($v) || is_float($v) ? $v : $pdo->quote((string) $v)), $row)).')';
                if (count($batch) >= 200) {
                    $w('INSERT INTO '.$q($t).' '.$cols." VALUES\n".implode(",\n", $batch).";\n");
                    $batch = [];
                }
            }
            if ($batch) {
                $w('INSERT INTO '.$q($t).' '.$cols." VALUES\n".implode(",\n", $batch).";\n");
            }
        }
        if ($driver !== 'sqlite') {
            $w("\nSET FOREIGN_KEY_CHECKS=1;\n");
        }
        gzclose($gz);
    }
}
