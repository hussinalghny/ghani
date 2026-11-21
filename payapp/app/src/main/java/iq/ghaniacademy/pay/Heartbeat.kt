package iq.ghaniacademy.pay

import android.app.AlarmManager
import android.app.PendingIntent
import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.os.BatteryManager
import android.os.SystemClock
import org.json.JSONArray
import org.json.JSONObject

/** نبض كل دقيقة للمنصة: إذا انقطع، المنصة تنبهك بالتيليگرام */
object Heartbeat {
    private const val EVERY = 60_000L

    fun schedule(c: Context) {
        val app = c.applicationContext
        val pi = PendingIntent.getBroadcast(app, 7, Intent(app, HeartbeatReceiver::class.java),
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE)
        (app.getSystemService(Context.ALARM_SERVICE) as AlarmManager)
            .setAndAllowWhileIdle(AlarmManager.ELAPSED_REALTIME_WAKEUP, SystemClock.elapsedRealtime() + EVERY, pi)
    }

    /** يرسل النبض الآن (على thread مو الرئيسي) */
    fun beat(c: Context) {
        val app = c.applicationContext
        if (!Sender.configured(app)) return
        val bm = app.getSystemService(Context.BATTERY_SERVICE) as BatteryManager
        val queue = JSONArray(Sender.prefs(app).getString("queue", "[]")).length()
        val version = runCatching { app.packageManager.getPackageInfo(app.packageName, 0).versionName }.getOrNull() ?: ""
        val body = JSONObject().put("battery", bm.getIntProperty(BatteryManager.BATTERY_PROPERTY_CAPACITY))
            .put("charging", bm.isCharging).put("queue", queue).put("version", version)
        val ok = Sender.request(app, "POST", "/api/v1/payments/console/heartbeat", body.toString())?.first == 200
        val p = Sender.prefs(app)
        if (p.getBoolean("hb_ok", true) != ok) {
            p.edit().putBoolean("hb_ok", ok).apply()
            Sender.log(app, if (ok) "رجع الاتصال بالمنصة" else "انقطع الاتصال بالمنصة")
        }
        if (ok && queue > 0) Sender.flush(app)
    }

    fun now(c: Context) { Thread { beat(c) }.start() }
}

/** المنبه + بعد تشغيل الموبايل */
class HeartbeatReceiver : BroadcastReceiver() {
    override fun onReceive(c: Context, intent: Intent) {
        Heartbeat.schedule(c)
        val pr = goAsync()
        Thread { try { Heartbeat.beat(c) } finally { pr.finish() } }.start()
    }
}
