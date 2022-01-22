<?php

namespace WHMCS\Module\Registrar\RRPproxy\Models;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * @property int $id
 * @property string $zone
 * @property string $periods
 * @property int $grace_days
 * @property int $redemption_days
 * @property boolean $epp_required
 * @property boolean $id_protection
 * @property boolean $supports_renewals
 * @property boolean $renews_on_transfer
 * @property boolean $handle_updatable
 * @property boolean $needs_trade
 * @property string $created_at
 * @property string $updated_at
 * @mixin Builder
 * @method static Builder where($column, $operator = null, $value = null, $boolean = 'and'): ZoneModel
 */
class ZoneModel extends Model
{
    /**
     * The zones model.
     *
     * @var string
     */
    protected $table = "mod_rrpproxy_zones";
    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    public $timestamps = true;
    /**
     * @var array<string>
     */
    protected $fillable = [
        "zone",
        "periods",
        "grace_days",
        "redemption_days",
        "epp_required",
        "id_protection",
        "supports_renewals",
        "renews_on_transfer",
        "handle_updatable",
        "needs_trade"
    ];

    /**
     * @var string
     */
    protected static string $tblName = "mod_rrpproxy_zones";

    /**
     * @return bool
     */
    private static function hasTable(): bool
    {
        $hasTable = DB::schema()->hasTable(self::$tblName);
        if ($hasTable) {
            $isLatest = DB::schema()->hasColumn(self::$tblName, 'supports_renewals');
            if (!$isLatest) {
                self::dropIfExists();
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * @return array<string,string>
     */
    public static function createTableIfNotExists(): array
    {
        if (self::hasTable()) {
            return [
                "status" => "success",
                "description" => ""
            ];
        }
        try {
            DB::schema()->create(self::$tblName, function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('zone', 45);
                $table->string('periods', 50);
                $table->integer('grace_days')->nullable();
                $table->integer('redemption_days')->nullable();
                $table->boolean('epp_required');
                $table->boolean('id_protection');
                $table->boolean('supports_renewals');
                $table->boolean('renews_on_transfer');
                $table->boolean('handle_updatable');
                $table->boolean('needs_trade');
                $table->timestamps();
                $table->unique('zone');
            });
            $mod_rrpproxy_zones = [];
            include_once __DIR__ . '/../../sql/mod_rrpproxy_zones.php';
            DB::table(self::$tblName)->insert($mod_rrpproxy_zones);
            DB::table(self::$tblName)->update([
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            return [
                "status" => "success",
                "description" => ""
            ];
        } catch (Exception $e) {
            return [
                "status" => "error",
                "description" => "Could not create table `" . self::$tblName . "`: " . $e->getMessage()
            ];
        }
    }

    /**
     * Drop table if existing
     */
    private static function dropIfExists(): void
    {
        DB::schema()->dropIfExists(self::$tblName);
    }
}
