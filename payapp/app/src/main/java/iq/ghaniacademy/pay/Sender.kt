package iq.ghaniacademy.pay

import android.content.Context
import org.json.JSONArray
import org.json.JSONObject
import java.net.HttpURLConnection
import java.net.URL
import javax.crypto.Mac
import javax.crypto.spec.SecretKeySpec

/** يرسل الإشعارات للمنصة بتوقيع HMAC، ويحتفظ بطابور إذا النت مقطوع */
object Sender {
    private const val PREFS = "ghani_pay"
    private val lock = Any()

    // كلمات تدل على مبلغ مالي (نخفف الإرسال، والمنصة تتحقق بدقة)
    private val money = Regex("(استلام|استلمت|إيداع|ايداع|أضيف|اضيف|IQD|دينار|د\\.ع|received|credited)", RegexOption.IGNORE_CASE)

    fun prefs(c: Context) = c.getSharedPreferences(PREFS, Context.MODE_PRIVATE)

    fun looksLikeMoney(text: String) = money.containsMatchIn(text) && text.any { it.isDigit() || it in '٠'..'٩' }

    fun enqueue(c: Context, source: String, pkg: String?, title: String?, text: String, postedAt: Long) {
        val item = JSONObject()
            .put("source", source).put("package", pkg ?: "").put("title", title ?: "")
            .put("text", text).put("posted_at", postedAt)
        synchronized(lock) {
            val q = JSONArray(prefs(c).getString("queue", "[]"))
            q.put(item)
            prefs(c).edit().putString("queue", q.toString()).apply()
        }
        log(c, "إشعار: " + text.take(70))
        flush(c)
    }

    private val retry = android.os.Handler(android.os.Looper.getMainLooper())

    fun flush(c: Context) {
        val app = c.applicationContext
        Thread {
            // نبقي المعالج صاحي لحد ما يوصل الطلب (حتى لو الشاشة مطفية)
            val wl = (app.getSystemService(Context.POWER_SERVICE) as android.os.PowerManager)
                .newWakeLock(android.os.PowerManager.PARTIAL_WAKE_LOCK, "ghanipay:send")
            wl.acquire(60_000)
            try { synchronized(lock) {
                val q = JSONArray(prefs(app).getString("queue", "[]"))
                val left = JSONArray()
                for (i in 0 until q.length()) {
                    val item = q.getJSONObject(i)
                    var res: String? = null
                    for (attempt in 0 until 3) {        // 3 محاولات سريعة قبل ما نأجله
                        res = post(app, item)
                        if (res != null) break
                        Thread.sleep(1500)
                    }
                    if (res == null) left.put(item) else {
                        val sec = (System.currentTimeMillis() - item.optLong("posted_at", System.currentTimeMillis())) / 1000
                        log(app, "وصل المنصة خلال ${sec}s: $res")
                    }
                }
                prefs(app).edit().putString("queue", left.toString()).apply()
                // المعلق نعيده كل 10 ثواني لحد ما يرجع النت
                if (left.length() > 0) retry.postDelayed({ flush(app) }, 10_000)
            } } finally { if (wl.isHeld) wl.release() }
        }.start()
    }

    /** يرجع رد المنصة، أو null إذا فشل ولازم يعيد */
    fun post(c: Context, item: JSONObject): String? {
        val base = prefs(c).getString("url", "")!!.trimEnd('/')
        val secret = prefs(c).getString("secret", "")!!
        if (base.isEmpty() || secret.isEmpty()) return null
        return try {
            val body = item.toString()
            val ts = (System.currentTimeMillis() / 1000).toString()
            val mac = Mac.getInstance("HmacSHA256")
            mac.init(SecretKeySpec(secret.toByteArray(), "HmacSHA256"))
            val sig = mac.doFinal("$ts.$body".toByteArray()).joinToString("") { "%02x".format(it) }
            val con = URL("$base/api/v1/payments/hook").openConnection() as HttpURLConnection
            con.requestMethod = "POST"
            con.connectTimeout = 15000
            con.readTimeout = 20000
            con.doOutput = true
            con.setRequestProperty("Content-Type", "application/json")
            con.setRequestProperty("Accept", "application/json")
            con.setRequestProperty("X-Ghani-Time", ts)
            con.setRequestProperty("X-Ghani-Sign", sig)
            con.outputStream.use { it.write(body.toByteArray()) }
            val code = con.responseCode
            val text = (if (code < 400) con.inputStream else con.errorStream)?.bufferedReader()?.readText() ?: ""
            when {
                code in 200..299 -> text.take(120)
                code == 401 || code == 422 -> "رفض ($code) " + text.take(80) // ما نعيد: المفتاح غلط أو بيانات خربانة
                else -> null
            }
        } catch (e: Exception) {
            null
        }
    }

    fun log(c: Context, line: String) {
        val t = java.text.SimpleDateFormat("MM/dd HH:mm:ss", java.util.Locale.US).format(java.util.Date())
        val old = prefs(c).getString("log", "")!!.lines().take(60).joinToString("\n")
        prefs(c).edit().putString("log", "$t  $line\n$old").apply()
    }
}
