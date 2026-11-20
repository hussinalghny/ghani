package iq.ghaniacademy.pay

import android.content.Context
import android.os.Handler
import android.os.Looper
import android.os.PowerManager
import org.json.JSONArray
import org.json.JSONObject
import java.net.HttpURLConnection
import java.net.URL
import javax.crypto.Mac
import javax.crypto.spec.SecretKeySpec

/** الاتصال بالمنصة (موقع HMAC) + طابور الإشعارات */
object Sender {
    private const val PREFS = "ghani_pay"
    private val lock = Any()
    private val main = Handler(Looper.getMainLooper())
    @Volatile var onChange: (() -> Unit)? = null   // الواجهة تتحدث أول ما يتأكد دفع

    private val money = Regex("(استلام|استلمت|إيداع|ايداع|أضيف|اضيف|IQD|دينار|د\\.ع|received|credited)", RegexOption.IGNORE_CASE)

    fun prefs(c: Context) = c.getSharedPreferences(PREFS, Context.MODE_PRIVATE)
    fun configured(c: Context) = prefs(c).getString("url", "")!!.isNotBlank() && prefs(c).getString("secret", "")!!.isNotBlank()
    fun looksLikeMoney(text: String) = money.containsMatchIn(text) && text.any { it.isDigit() || it in '٠'..'٩' }

    fun enqueue(c: Context, source: String, pkg: String?, title: String?, text: String, postedAt: Long) {
        val app = c.applicationContext
        val item = JSONObject().put("source", source).put("package", pkg ?: "").put("title", title ?: "")
            .put("text", text).put("posted_at", postedAt)
        log(app, "إشعار: " + text.take(70))
        // نرسله فورا بنفس اللحظة، والطابور بس للاحتياط إذا فشل
        Thread {
            val wl = (app.getSystemService(Context.POWER_SERVICE) as PowerManager)
                .newWakeLock(PowerManager.PARTIAL_WAKE_LOCK, "ghanipay:send").apply { acquire(60_000) }
            try {
                var res: String? = null
                for (attempt in 0 until 4) {
                    res = deliver(app, item)
                    if (res != null) break
                    Thread.sleep(if (attempt == 0) 700L else 2000L)
                }
                if (res == null) {
                    synchronized(lock) {
                        val q = JSONArray(prefs(app).getString("queue", "[]")).put(item)
                        prefs(app).edit().putString("queue", q.toString()).apply()
                    }
                    log(app, "ما وصل، يعيد كل 10 ثواني")
                    main.postDelayed({ flush(app) }, 10_000)
                }
            } finally { if (wl.isHeld) wl.release() }
        }.start()
    }

    fun flush(c: Context) {
        val app = c.applicationContext
        Thread {
            synchronized(lock) {
                val q = JSONArray(prefs(app).getString("queue", "[]"))
                if (q.length() == 0) return@Thread
                val left = JSONArray()
                for (i in 0 until q.length()) {
                    val item = q.getJSONObject(i)
                    if (deliver(app, item) == null) left.put(item)
                }
                prefs(app).edit().putString("queue", left.toString()).apply()
                if (left.length() > 0) main.postDelayed({ flush(app) }, 10_000)
            }
        }.start()
    }

    private fun deliver(c: Context, item: JSONObject): String? {
        val r = request(c, "POST", "/api/v1/payments/hook", item.toString()) ?: return null
        val (code, text) = r
        if (code in 500..599 || code == 429) return null
        val sec = (System.currentTimeMillis() - item.optLong("posted_at", System.currentTimeMillis())) / 1000.0
        val status = runCatching { JSONObject(text).optString("status") }.getOrDefault("")
        val label = when (status) { "matched" -> "تم التفعيل تلقائيا"; "review" -> "يحتاج مراجعة"; "ignored" -> "متجاهل (مو استلام)"; else -> "رد $code" }
        log(c, "وصل المنصة خلال ${"%.1f".format(sec)}s · $label")
        main.post { onChange?.invoke() }
        return text
    }

    /** طلب موقع للمنصة: يرجع (الكود، النص) أو null إذا ما اكو اتصال */
    fun request(c: Context, method: String, path: String, body: String = ""): Pair<Int, String>? {
        val base = prefs(c).getString("url", "")!!.trim().trimEnd('/')
        val secret = prefs(c).getString("secret", "")!!.trim()
        if (base.isEmpty() || secret.isEmpty()) return null
        return try {
            val ts = (System.currentTimeMillis() / 1000).toString()
            val mac = Mac.getInstance("HmacSHA256").apply { init(SecretKeySpec(secret.toByteArray(), "HmacSHA256")) }
            val sig = mac.doFinal("$ts.$body".toByteArray()).joinToString("") { "%02x".format(it) }
            val con = URL(base + path).openConnection() as HttpURLConnection
            con.requestMethod = method
            con.connectTimeout = 8000
            con.readTimeout = 12000
            con.setRequestProperty("Accept", "application/json")
            con.setRequestProperty("X-Ghani-Time", ts)
            con.setRequestProperty("X-Ghani-Sign", sig)
            if (method == "POST") {
                con.doOutput = true
                con.setRequestProperty("Content-Type", "application/json")
                con.outputStream.use { it.write(body.toByteArray()) }
            }
            val code = con.responseCode
            val text = (if (code < 400) con.inputStream else con.errorStream)?.bufferedReader()?.readText() ?: ""
            code to text
        } catch (e: Exception) {
            null
        }
    }

    fun log(c: Context, line: String) {
        val t = java.text.SimpleDateFormat("HH:mm:ss", java.util.Locale.US).format(java.util.Date())
        val old = prefs(c).getString("log", "")!!.lines().take(40).joinToString("\n")
        prefs(c).edit().putString("log", "$t  $line\n$old").apply()
    }
}
