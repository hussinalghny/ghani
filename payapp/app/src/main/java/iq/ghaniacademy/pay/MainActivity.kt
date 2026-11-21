package iq.ghaniacademy.pay

import android.Manifest
import android.app.Activity
import android.app.AlertDialog
import android.content.ClipData
import android.content.ClipboardManager
import android.content.ComponentName
import android.content.Intent
import android.content.pm.PackageManager
import android.graphics.Color
import android.graphics.Typeface
import android.graphics.drawable.GradientDrawable
import android.net.Uri
import android.os.Build
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.os.PowerManager
import android.provider.Settings
import android.text.InputType
import android.view.Gravity
import android.view.View
import android.view.ViewGroup
import android.widget.Button
import android.widget.EditText
import android.widget.LinearLayout
import android.widget.ScrollView
import android.widget.TextView
import android.widget.Toast
import org.json.JSONArray
import org.json.JSONObject

/** غني باي: بوابة الدفع (قراءة الاستلام + لوحة الطلبات والتأكيد) */
class MainActivity : Activity() {
    private val BG = Color.rgb(11, 10, 16)
    private val CARD = Color.rgb(21, 19, 29)
    private val LINE = Color.argb(28, 255, 255, 255)
    private val GOLD = Color.rgb(212, 175, 106)
    private val TEXT = Color.rgb(245, 241, 232)
    private val TEXT2 = Color.rgb(185, 178, 198)
    private val GREEN = Color.rgb(52, 199, 123)
    private val RED = Color.rgb(240, 110, 120)

    private val ui = Handler(Looper.getMainLooper())
    private lateinit var body: LinearLayout
    private var showSettings = false
    private var lastFeed: JSONObject? = null
    private var online: Boolean? = null
    private val tick = object : Runnable { override fun run() { load(); ui.postDelayed(this, 6000) } }

    private val d get() = resources.displayMetrics.density
    private fun dp(v: Int) = (v * d).toInt()

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        window.decorView.layoutDirection = View.LAYOUT_DIRECTION_RTL
        window.statusBarColor = BG
        window.navigationBarColor = BG
        body = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL; setPadding(dp(16), dp(20), dp(16), dp(32)) }
        setContentView(ScrollView(this).apply { setBackgroundColor(BG); addView(body) })
        showSettings = !Sender.configured(this)
        render()
    }

    override fun onResume() {
        super.onResume()
        Sender.onChange = { load() }
        ui.post(tick)
        Heartbeat.schedule(this)
    }

    override fun onPause() {
        super.onPause()
        Sender.onChange = null
        ui.removeCallbacks(tick)
    }

    // ---------- عناصر ----------
    private fun round(color: Int, r: Int = 18, stroke: Int? = null) = GradientDrawable().apply {
        setColor(color); cornerRadius = dp(r).toFloat(); stroke?.let { setStroke(dp(1), it) }
    }

    private fun text(t: String, size: Float = 14f, color: Int = TEXT, bold: Boolean = false, ltr: Boolean = false) = TextView(this).apply {
        text = t; textSize = size; setTextColor(color); setLineSpacing(0f, 1.25f)
        if (bold) typeface = Typeface.DEFAULT_BOLD
        if (ltr) textDirection = View.TEXT_DIRECTION_LTR
    }

    private fun card(parent: ViewGroup = body, gap: Int = 12): LinearLayout = LinearLayout(this).apply {
        orientation = LinearLayout.VERTICAL; background = round(CARD, 20, LINE); setPadding(dp(16), dp(14), dp(16), dp(16))
        layoutParams = LinearLayout.LayoutParams(-1, -2).apply { bottomMargin = dp(gap) }
        parent.addView(this)
    }

    private fun row(parent: ViewGroup) = LinearLayout(this).apply {
        orientation = LinearLayout.HORIZONTAL; gravity = Gravity.CENTER_VERTICAL; parent.addView(this, LinearLayout.LayoutParams(-1, -2))
    }

    private fun btn(parent: ViewGroup, t: String, primary: Boolean = false, small: Boolean = false, on: () -> Unit): Button = Button(this).apply {
        text = t; isAllCaps = false; textSize = if (small) 13f else 15f
        setTextColor(if (primary) BG else TEXT)
        background = round(if (primary) GOLD else Color.argb(30, 212, 175, 106), 12)
        setPadding(dp(14), 0, dp(14), 0); minHeight = dp(if (small) 38 else 48); minimumHeight = minHeight
        stateListAnimator = null
        setOnClickListener { on() }
        parent.addView(this, LinearLayout.LayoutParams(if (small) -2 else -1, -2).apply { topMargin = dp(8); marginStart = if (small) dp(6) else 0 })
    }

    private fun section(t: String, count: Int? = null) {
        val r = row(body)
        r.setPadding(dp(4), dp(18), dp(4), dp(8))
        r.addView(text(t, 17f, TEXT, true), LinearLayout.LayoutParams(0, -2, 1f))
        if (count != null) r.addView(text(count.toString(), 13f, GOLD, true).apply { background = round(Color.argb(30, 212, 175, 106), 99); setPadding(dp(10), dp(2), dp(10), dp(2)) })
    }

    private fun money(v: Long) = String.format(java.util.Locale.US, "%,d", v)

    private fun copy(label: String, value: String) {
        (getSystemService(CLIPBOARD_SERVICE) as ClipboardManager).setPrimaryClip(ClipData.newPlainText(label, value))
        Toast.makeText(this, "تم نسخ $label", Toast.LENGTH_SHORT).show()
    }

    // ---------- الشاشة ----------
    private fun render() {
        body.removeAllViews()
        val head = row(body)
        val titles = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL }
        titles.addView(text("غني باي", 26f, GOLD, true))
        titles.addView(text("بوابة الدفع · غني أكاديمي", 13f, TEXT2))
        head.addView(titles, LinearLayout.LayoutParams(0, -2, 1f))
        btn(head, if (showSettings) "اللوحة" else "الإعدادات", small = true) { showSettings = !showSettings; render() }

        statusChips()
        if (showSettings) settings() else dashboard()
    }

    private fun statusChips() {
        val listening = Settings.Secure.getString(contentResolver, "enabled_notification_listeners")
            ?.contains(ComponentName(this, PayListenerService::class.java).flattenToString()) == true
        val battery = Build.VERSION.SDK_INT < 23 || (getSystemService(POWER_SERVICE) as PowerManager).isIgnoringBatteryOptimizations(packageName)
        val r = row(body)
        r.setPadding(0, dp(14), 0, dp(6))
        fun chip(t: String, ok: Boolean?) {
            val c = when (ok) { true -> GREEN; false -> RED; null -> TEXT2 }
            r.addView(text((if (ok == true) "● " else "○ ") + t, 12.5f, c).apply {
                background = round(Color.argb(22, Color.red(c), Color.green(c), Color.blue(c)), 99); setPadding(dp(10), dp(5), dp(10), dp(5))
            }, LinearLayout.LayoutParams(-2, -2).apply { marginEnd = dp(6) })
        }
        chip("الإشعارات", listening); chip("البطارية", battery); chip("المنصة", online)
    }

    private fun dashboard() {
        val f = lastFeed
        if (f == null) {
            card().addView(text(if (online == false) "ما گدرت أتصل بالمنصة. شيك الإعدادات والنت." else "جاري التحميل…", 14f, TEXT2))
            return
        }
        // المحافظ: الرقم مع زر نسخ
        val wallets = f.optJSONArray("wallets") ?: JSONArray()
        for (i in 0 until wallets.length()) {
            val w = wallets.getJSONObject(i)
            val c = card()
            c.addView(text(w.optString("label"), 13f, TEXT2))
            val r = row(c)
            r.addView(text(w.optString("number"), 22f, TEXT, true, ltr = true), LinearLayout.LayoutParams(0, -2, 1f))
            btn(r, "نسخ الرقم", primary = true, small = true) { copy("الرقم", w.optString("number").replace(" ", "")) }
        }

        val s = f.optJSONObject("stats") ?: JSONObject()
        val stats = row(body)
        fun stat(label: String, value: String, color: Int = TEXT) {
            val c = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL; background = round(CARD, 16, LINE); setPadding(dp(12), dp(10), dp(12), dp(12)) }
            c.addView(text(label, 12f, TEXT2)); c.addView(text(value, 17f, color, true, ltr = true))
            stats.addView(c, LinearLayout.LayoutParams(0, -2, 1f).apply { marginEnd = dp(8) })
        }
        stat("اليوم", money(s.optLong("today")), GOLD); stat("الشهر", money(s.optLong("month"))); stat("معلقة", s.optInt("pending").toString())

        // تحتاج مراجعة
        val review = f.optJSONArray("review") ?: JSONArray()
        if (review.length() > 0) {
            section("تحتاج مراجعة", review.length())
            for (i in 0 until review.length()) {
                val n = review.getJSONObject(i)
                val c = card()
                val r = row(c)
                r.addView(text(if (n.isNull("amount")) "—" else money(n.optLong("amount")), 22f, GOLD, true, ltr = true), LinearLayout.LayoutParams(0, -2, 1f))
                r.addView(text(n.optString("at"), 12f, TEXT2, ltr = true))
                c.addView(text(n.optString("text"), 12.5f, TEXT2))
                val sug = n.optJSONObject("suggest")
                val actions = row(c)
                if (sug != null) {
                    c.addView(text("مقترح: طلب #${sug.optInt("id")} · ${sug.optString("user")} · ${money(sug.optLong("amount"))}", 13f, TEXT), 2)
                    btn(actions, "ربط وتفعيل", primary = true, small = true) {
                        confirm("ربط الدفعة بطلب #${sug.optInt("id")} وتفعيله؟") { act("/api/v1/payments/console/notifications/${n.optInt("id")}/attach", JSONObject().put("order", sug.optInt("id")).toString()) }
                    }
                }
                btn(actions, "تجاهل", small = true) { act("/api/v1/payments/console/notifications/${n.optInt("id")}/ignore") }
            }
        }

        // بانتظار التحويل
        val pending = f.optJSONArray("pending") ?: JSONArray()
        section("بانتظار التحويل", pending.length())
        if (pending.length() == 0) card().addView(text("ماكو طلبات معلقة.", 14f, TEXT2))
        for (i in 0 until pending.length()) {
            val o = pending.getJSONObject(i)
            val c = card()
            val r = row(c)
            r.addView(text(money(o.optLong("amount")), 24f, TEXT, true, ltr = true), LinearLayout.LayoutParams(0, -2, 1f))
            btn(r, "نسخ المبلغ", small = true) { copy("المبلغ", o.optLong("amount").toString()) }
            c.addView(text("${o.optString("user")} · ${o.optString("phone")}", 14f, TEXT))
            c.addView(text("${o.optString("course")} · طلب #${o.optInt("id")} · ${o.optString("created")}", 12.5f, TEXT2))
            btn(c, "وصل التحويل؟ تأكيد يدوي") {
                confirm("تأكد إن ${money(o.optLong("amount"))} وصل لمحفظتك، وتفعيل طلب ${o.optString("user")}؟") { act("/api/v1/payments/console/orders/${o.optInt("id")}/approve") }
            }
        }

        // آخر المدفوعات
        val recent = f.optJSONArray("recent") ?: JSONArray()
        if (recent.length() > 0) {
            section("آخر المدفوعات")
            val c = card()
            for (i in 0 until recent.length()) {
                val o = recent.getJSONObject(i)
                val r = row(c)
                r.setPadding(0, dp(6), 0, dp(6))
                val left = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL }
                left.addView(text(o.optString("user"), 14f, TEXT))
                left.addView(text("${o.optString("course")} · ${o.optString("paid")} · ${if (o.optString("by") == "auto") "تلقائي" else "يدوي"}", 12f, TEXT2))
                r.addView(left, LinearLayout.LayoutParams(0, -2, 1f))
                r.addView(text(money(o.optLong("amount")), 15f, GREEN, true, ltr = true))
            }
        }

        section("السجل")
        card().addView(text(Sender.prefs(this).getString("log", "")!!.lines().take(12).joinToString("\n"), 11.5f, TEXT2).apply { typeface = Typeface.MONOSPACE })
    }

    private fun settings() {
        val p = Sender.prefs(this)
        val c = card()
        c.addView(text("ربط المنصة", 16f, TEXT, true))
        c.addView(text("من لوحة التحكم ← الدفع المباشر ← ربط تطبيق غني باي", 12.5f, TEXT2))
        fun field(label: String, value: String, secret: Boolean = false): EditText {
            c.addView(text(label, 13f, TEXT2).apply { setPadding(0, dp(12), 0, dp(4)) })
            return EditText(this).apply {
                setText(value); setTextColor(TEXT); textSize = 15f; textDirection = View.TEXT_DIRECTION_LTR
                background = round(BG, 12, LINE); setPadding(dp(12), dp(10), dp(12), dp(10))
                inputType = if (secret) InputType.TYPE_CLASS_TEXT or InputType.TYPE_TEXT_VARIATION_VISIBLE_PASSWORD else InputType.TYPE_TEXT_VARIATION_URI
                c.addView(this, LinearLayout.LayoutParams(-1, -2))
            }
        }
        val url = field("رابط المنصة", p.getString("url", "https://ghaniacademy.online")!!)
        val secret = field("المفتاح السري", p.getString("secret", "")!!, true)
        btn(c, "حفظ واختبار", primary = true) {
            p.edit().putString("url", url.text.toString().trim()).putString("secret", secret.text.toString().trim()).apply()
            Thread {
                val r = Sender.request(this, "GET", "/api/v1/payments/console/feed")
                ui.post {
                    val ok = r?.first == 200
                    Toast.makeText(this, if (ok) "متصل بالمنصة" else "فشل: ${r?.first ?: "ماكو اتصال"}", Toast.LENGTH_LONG).show()
                    if (ok) { lastFeed = JSONObject(r!!.second); online = true; showSettings = false; Heartbeat.now(this) }
                    render()
                }
            }.start()
        }

        val perms = card()
        perms.addView(text("الصلاحيات", 16f, TEXT, true))
        perms.addView(text("الثلاثة لازم تكون مفعلة حتى يقرأ ويؤكد بسرعة حتى والشاشة مطفية.", 12.5f, TEXT2))
        btn(perms, "قراءة الإشعارات") { startActivity(Intent(Settings.ACTION_NOTIFICATION_LISTENER_SETTINGS)) }
        btn(perms, "قراءة رسائل SMS") { if (Build.VERSION.SDK_INT >= 23) requestPermissions(arrayOf(Manifest.permission.RECEIVE_SMS), 1) }
        btn(perms, "عدم إيقافه لتوفير البطارية") {
            if (Build.VERSION.SDK_INT >= 23) startActivity(Intent(Settings.ACTION_REQUEST_IGNORE_BATTERY_OPTIMIZATIONS, Uri.parse("package:$packageName")))
        }
        val sms = Build.VERSION.SDK_INT < 23 || checkSelfPermission(Manifest.permission.RECEIVE_SMS) == PackageManager.PERMISSION_GRANTED
        perms.addView(text(if (sms) "● الرسائل مفعلة" else "○ الرسائل غير مفعلة (اختياري)", 12.5f, if (sms) GREEN else TEXT2).apply { setPadding(0, dp(8), 0, 0) })
    }

    // ---------- الشبكة ----------
    private fun load() {
        if (!Sender.configured(this)) return
        Thread {
            val r = Sender.request(this, "GET", "/api/v1/payments/console/feed")
            ui.post {
                online = r?.first == 200
                if (online == true) lastFeed = runCatching { JSONObject(r!!.second) }.getOrNull()
                if (!showSettings) render()
            }
        }.start()
        Sender.flush(this)
    }

    private fun act(path: String, json: String = "{}") {
        Thread {
            val r = Sender.request(this, "POST", path, json)
            ui.post {
                val ok = r?.first == 200
                Toast.makeText(this, if (ok) "تم" else "ما تم: " + (r?.second?.take(80) ?: "ماكو اتصال"), Toast.LENGTH_SHORT).show()
                load()
            }
        }.start()
    }

    private fun confirm(msg: String, yes: () -> Unit) {
        AlertDialog.Builder(this).setMessage(msg).setPositiveButton("تأكيد") { _, _ -> yes() }.setNegativeButton("إلغاء", null).show()
    }
}
