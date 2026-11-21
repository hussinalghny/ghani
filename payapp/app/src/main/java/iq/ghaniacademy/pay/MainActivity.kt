package iq.ghaniacademy.pay

import android.Manifest
import android.app.Activity
import android.app.AlertDialog
import android.content.ClipData
import android.content.ClipboardManager
import android.content.ComponentName
import android.content.Intent
import android.content.pm.PackageManager
import android.content.res.ColorStateList
import android.graphics.Color
import android.graphics.Typeface
import android.graphics.drawable.GradientDrawable
import android.graphics.drawable.RippleDrawable
import android.net.Uri
import android.os.BatteryManager
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
import android.widget.EditText
import android.widget.FrameLayout
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
    private val CARD2 = Color.rgb(28, 25, 38)
    private val LINE = Color.argb(26, 255, 255, 255)
    private val GOLD = Color.rgb(212, 175, 106)
    private val GOLD2 = Color.rgb(240, 207, 140)
    private val TEXT = Color.rgb(245, 241, 232)
    private val TEXT2 = Color.rgb(160, 153, 175)
    private val GREEN = Color.rgb(52, 199, 123)
    private val RED = Color.rgb(240, 110, 120)
    private val AMBER = Color.rgb(245, 166, 35)

    private val ui = Handler(Looper.getMainLooper())
    private lateinit var body: LinearLayout
    private lateinit var scroll: ScrollView
    private lateinit var nav: LinearLayout
    private var tab = 0
    private var lastFeed: JSONObject? = null
    private var online: Boolean? = null
    private val tick = object : Runnable { override fun run() { load(); ui.postDelayed(this, 6000) } }

    private lateinit var head: Typeface
    private lateinit var reg: Typeface
    private lateinit var med: Typeface

    private val d get() = resources.displayMetrics.density
    private fun dp(v: Int) = (v * d).toInt()

    private fun font(file: String, bold: Boolean): Typeface =
        runCatching { Typeface.createFromAsset(assets, "fonts/$file") }.getOrNull() ?: if (bold) Typeface.DEFAULT_BOLD else Typeface.DEFAULT

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        head = font("Alexandria-Bold.ttf", true); reg = font("ReadexPro-Regular.ttf", false); med = font("ReadexPro-SemiBold.ttf", true)
        window.decorView.layoutDirection = View.LAYOUT_DIRECTION_RTL
        window.statusBarColor = BG
        window.navigationBarColor = CARD

        val root = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL; setBackgroundColor(BG) }
        body = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL; setPadding(dp(18), dp(18), dp(18), dp(28)) }
        scroll = ScrollView(this).apply { addView(body); isVerticalScrollBarEnabled = false; overScrollMode = View.OVER_SCROLL_NEVER }
        root.addView(scroll, LinearLayout.LayoutParams(-1, 0, 1f))
        nav = LinearLayout(this).apply {
            orientation = LinearLayout.HORIZONTAL; setBackgroundColor(CARD); setPadding(dp(10), dp(8), dp(10), dp(10))
            elevation = dp(8).toFloat()
        }
        root.addView(View(this).apply { setBackgroundColor(LINE) }, LinearLayout.LayoutParams(-1, 1))
        root.addView(nav, LinearLayout.LayoutParams(-1, -2))
        setContentView(root)
        if (!Sender.configured(this)) tab = 3
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

    private fun alpha(c: Int, a: Int) = Color.argb(a, Color.red(c), Color.green(c), Color.blue(c))

    private fun ripple(bg: GradientDrawable) = RippleDrawable(ColorStateList.valueOf(Color.argb(40, 255, 255, 255)), bg, null)

    private fun text(t: String, size: Float = 14f, color: Int = TEXT, face: Typeface? = null, ltr: Boolean = false) = TextView(this).apply {
        text = t; textSize = size; setTextColor(color); setLineSpacing(0f, 1.3f); typeface = face ?: reg
        includeFontPadding = false
        if (ltr) { textDirection = View.TEXT_DIRECTION_LTR }
    }

    private fun card(parent: ViewGroup = body, gap: Int = 12, color: Int = CARD, stroke: Int = LINE): LinearLayout = LinearLayout(this).apply {
        orientation = LinearLayout.VERTICAL; background = round(color, 22, stroke); setPadding(dp(18), dp(16), dp(18), dp(18))
        layoutParams = LinearLayout.LayoutParams(-1, -2).apply { bottomMargin = dp(gap) }
        parent.addView(this)
    }

    private fun row(parent: ViewGroup) = LinearLayout(this).apply {
        orientation = LinearLayout.HORIZONTAL; gravity = Gravity.CENTER_VERTICAL; parent.addView(this, LinearLayout.LayoutParams(-1, -2))
    }

    private fun space(parent: ViewGroup, h: Int) = parent.addView(View(this), LinearLayout.LayoutParams(1, dp(h)))

    /** primary ذهبي، وإلا زجاجي شفاف */
    private fun btn(parent: ViewGroup, t: String, primary: Boolean = false, small: Boolean = false, full: Boolean = !small, on: () -> Unit): TextView =
        text(t, if (small) 13f else 15f, if (primary) BG else TEXT, med).apply {
            gravity = Gravity.CENTER
            background = ripple(if (primary) GradientDrawable(GradientDrawable.Orientation.TL_BR, intArrayOf(GOLD2, GOLD)).apply { cornerRadius = dp(14).toFloat() }
                else round(Color.argb(14, 255, 255, 255), 14, LINE))
            setPadding(dp(16), 0, dp(16), 0); minHeight = dp(if (small) 40 else 50)
            isClickable = true; isFocusable = true
            setOnClickListener { it.animate().scaleX(.97f).scaleY(.97f).setDuration(70).withEndAction { it.animate().scaleX(1f).scaleY(1f).setDuration(90).start() }.start(); on() }
            parent.addView(this, LinearLayout.LayoutParams(if (full) -1 else -2, -2).apply { topMargin = dp(10); if (!full) marginStart = dp(8) })
        }

    private fun pill(t: String, color: Int) = text(t, 12f, color, med).apply {
        background = round(alpha(color, 30), 99); setPadding(dp(10), dp(5), dp(10), dp(5))
    }

    private fun section(t: String, count: Int? = null, color: Int = GOLD) {
        val r = row(body)
        r.setPadding(dp(4), dp(18), dp(4), dp(10))
        r.addView(text(t, 17f, TEXT, head), LinearLayout.LayoutParams(0, -2, 1f))
        if (count != null) r.addView(pill(count.toString(), color))
    }

    private fun avatar(name: String, color: Int = GOLD) = text(name.trim().take(1).ifEmpty { "؟" }, 16f, color, head).apply {
        gravity = Gravity.CENTER; background = GradientDrawable().apply { shape = GradientDrawable.OVAL; setColor(alpha(color, 34)) }
        layoutParams = LinearLayout.LayoutParams(dp(42), dp(42)).apply { marginEnd = dp(12) }
    }

    private fun money(v: Long) = String.format(java.util.Locale.US, "%,d", v)

    private fun copy(label: String, value: String) {
        (getSystemService(CLIPBOARD_SERVICE) as ClipboardManager).setPrimaryClip(ClipData.newPlainText(label, value))
        Toast.makeText(this, "تم نسخ $label", Toast.LENGTH_SHORT).show()
    }

    // ---------- الشاشة ----------
    private fun render() {
        val y = scroll.scrollY
        body.removeAllViews()
        header()
        when (tab) {
            0 -> home()
            1 -> orders()
            2 -> logs()
            else -> settings()
        }
        renderNav()
        scroll.post { scroll.scrollTo(0, y) }
    }

    private fun go(t: Int) { if (tab != t) { tab = t; scroll.scrollTo(0, 0); render() } }

    private fun renderNav() {
        nav.removeAllViews()
        val f = lastFeed
        val pendingCount = f?.optJSONArray("pending")?.length() ?: 0
        val reviewCount = f?.optJSONArray("review")?.length() ?: 0
        listOf("الرئيسية" to reviewCount, "الطلبات" to pendingCount, "السجل" to 0, "الإعدادات" to 0).forEachIndexed { i, (label, badge) ->
            val on = i == tab
            val cell = LinearLayout(this).apply {
                orientation = LinearLayout.VERTICAL; gravity = Gravity.CENTER; setPadding(0, dp(8), 0, dp(8))
                background = ripple(round(if (on) alpha(GOLD, 26) else Color.TRANSPARENT, 14))
                isClickable = true; setOnClickListener { go(i) }
            }
            val top = LinearLayout(this).apply { orientation = LinearLayout.HORIZONTAL; gravity = Gravity.CENTER }
            top.addView(View(this).apply { background = GradientDrawable().apply { shape = GradientDrawable.OVAL; setColor(if (on) GOLD else alpha(TEXT2, 120)) } },
                LinearLayout.LayoutParams(dp(6), dp(6)))
            if (badge > 0) top.addView(text(badge.toString(), 10f, BG, med).apply {
                background = round(if (i == 0) AMBER else GOLD, 99); setPadding(dp(6), dp(1), dp(6), dp(1))
            }, LinearLayout.LayoutParams(-2, -2).apply { marginStart = dp(4) })
            cell.addView(top)
            cell.addView(text(label, 12.5f, if (on) GOLD else TEXT2, if (on) med else reg).apply { gravity = Gravity.CENTER; setPadding(0, dp(5), 0, 0) })
            nav.addView(cell, LinearLayout.LayoutParams(0, -2, 1f).apply { marginStart = dp(3); marginEnd = dp(3) })
        }
    }

    private fun header() {
        val h = row(body)
        h.setPadding(0, 0, 0, dp(14))
        val mark = FrameLayout(this).apply {
            background = GradientDrawable(GradientDrawable.Orientation.TL_BR, intArrayOf(GOLD2, GOLD)).apply { cornerRadius = dp(14).toFloat() }
            addView(text("G", 20f, BG, head).apply { gravity = Gravity.CENTER }, FrameLayout.LayoutParams(-1, -1))
        }
        h.addView(mark, LinearLayout.LayoutParams(dp(44), dp(44)).apply { marginEnd = dp(12) })
        val titles = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL }
        titles.addView(text("غني باي", 22f, TEXT, head))
        titles.addView(text("بوابة الدفع · غني أكاديمي", 12.5f, TEXT2).apply { setPadding(0, dp(4), 0, 0) })
        h.addView(titles, LinearLayout.LayoutParams(0, -2, 1f))
        val ok = online == true && listening()
        h.addView(pill(if (online == null) "..." else if (ok) "● يعمل" else "● متوقف", if (online == null) TEXT2 else if (ok) GREEN else RED))
    }

    private fun listening() = Settings.Secure.getString(contentResolver, "enabled_notification_listeners")
        ?.contains(ComponentName(this, PayListenerService::class.java).flattenToString()) == true

    private fun home() {
        val f = lastFeed
        if (f == null) {
            card().addView(text(if (online == false) "ما گدرت أتصل بالمنصة. شيك الإعدادات والنت." else "جاري التحميل…", 14f, TEXT2))
            health()
            return
        }
        val s = f.optJSONObject("stats") ?: JSONObject()

        // بطاقة الأرقام
        val hero = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL; setPadding(dp(20), dp(18), dp(20), dp(20))
            background = GradientDrawable(GradientDrawable.Orientation.TL_BR, intArrayOf(Color.rgb(58, 46, 22), Color.rgb(30, 25, 18), CARD)).apply {
                cornerRadius = dp(26).toFloat(); setStroke(dp(1), alpha(GOLD, 70))
            }
        }
        body.addView(hero, LinearLayout.LayoutParams(-1, -2).apply { bottomMargin = dp(12) })
        hero.addView(text("مدفوعات اليوم", 13f, alpha(GOLD2, 220), med))
        val amt = row(hero)
        amt.setPadding(0, dp(8), 0, dp(16))
        amt.addView(text(money(s.optLong("today")), 38f, GOLD2, head, ltr = true))
        amt.addView(text("د.ع", 14f, TEXT2).apply { setPadding(dp(8), dp(10), 0, 0) })
        hero.addView(View(this).apply { setBackgroundColor(alpha(GOLD, 40)) }, LinearLayout.LayoutParams(-1, 1))
        val mini = row(hero)
        mini.setPadding(0, dp(14), 0, 0)
        fun stat(label: String, value: String, color: Int = TEXT) {
            val c = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL }
            c.addView(text(label, 12f, TEXT2))
            c.addView(text(value, 17f, color, head, ltr = true).apply { setPadding(0, dp(5), 0, 0) })
            mini.addView(c, LinearLayout.LayoutParams(0, -2, 1f))
        }
        stat("الشهر", money(s.optLong("month")))
        stat("معلقة", s.optInt("pending").toString())
        stat("مراجعة", s.optInt("review").toString(), if (s.optInt("review") > 0) AMBER else TEXT)

        health()

        // تحتاج مراجعة
        val review = f.optJSONArray("review") ?: JSONArray()
        if (review.length() > 0) {
            section("تحتاج مراجعة", review.length(), AMBER)
            for (i in 0 until review.length()) {
                val n = review.getJSONObject(i)
                val c = card(stroke = alpha(AMBER, 90))
                val r = row(c)
                r.addView(text(if (n.isNull("amount")) "—" else money(n.optLong("amount")), 24f, AMBER, head, ltr = true), LinearLayout.LayoutParams(0, -2, 1f))
                r.addView(text(n.optString("at"), 12f, TEXT2, ltr = true))
                c.addView(text(n.optString("text"), 12.5f, TEXT2).apply { setPadding(0, dp(8), 0, 0) })
                val sug = n.optJSONObject("suggest")
                if (sug != null) {
                    c.addView(text("مقترح: طلب #${sug.optInt("id")} · ${sug.optString("user")} · ${money(sug.optLong("amount"))}", 13f, TEXT, med).apply {
                        background = round(Color.argb(10, 255, 255, 255), 12); setPadding(dp(12), dp(10), dp(12), dp(10))
                        layoutParams = LinearLayout.LayoutParams(-1, -2).apply { topMargin = dp(10) }
                    })
                }
                val actions = row(c)
                if (sug != null) btn(actions, "ربط وتفعيل", primary = true, small = true) {
                    confirm("ربط الدفعة بطلب #${sug.optInt("id")} وتفعيله؟") { act("/api/v1/payments/console/notifications/${n.optInt("id")}/attach", JSONObject().put("order", sug.optInt("id")).toString()) }
                }
                btn(actions, "تجاهل", small = true) { act("/api/v1/payments/console/notifications/${n.optInt("id")}/ignore") }
            }
        }

        // المحافظ
        val wallets = f.optJSONArray("wallets") ?: JSONArray()
        if (wallets.length() > 0) section("المحافظ")
        for (i in 0 until wallets.length()) {
            val w = wallets.getJSONObject(i)
            val c = card(gap = 10)
            val r = row(c)
            val info = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL }
            info.addView(text(w.optString("label"), 12.5f, TEXT2))
            info.addView(text(w.optString("number"), 20f, TEXT, head, ltr = true).apply { setPadding(0, dp(6), 0, 0) })
            r.addView(info, LinearLayout.LayoutParams(0, -2, 1f))
            btn(r, "نسخ", primary = true, small = true) { copy("الرقم", w.optString("number").replace(" ", "")) }
        }

        // آخر المدفوعات
        recent(f, 5)
    }

    /** حالة الجهاز: الإشعارات، البطارية، المنصة، الشحن */
    private fun health() {
        val battery = Build.VERSION.SDK_INT < 23 || (getSystemService(POWER_SERVICE) as PowerManager).isIgnoringBatteryOptimizations(packageName)
        val bm = getSystemService(BATTERY_SERVICE) as BatteryManager
        val level = bm.getIntProperty(BatteryManager.BATTERY_PROPERTY_CAPACITY)
        val items = listOf(
            Triple("الإشعارات", if (listening()) "مفعلة" else "مطفية", listening()),
            Triple("المنصة", if (online == true) "متصل" else if (online == false) "منقطع" else "...", online),
            Triple("توفير البطارية", if (battery) "مستثنى" else "يوقفه", battery),
            Triple("الشحن", "$level%" + if (bm.isCharging) " · يشحن" else "", bm.isCharging || level > 30),
        )
        for (pair in items.chunked(2)) {
            val r = row(body)
            r.setPadding(0, 0, 0, dp(10))
            pair.forEachIndexed { i, (label, value, ok) ->
                val color = when (ok) { true -> GREEN; false -> RED; null -> TEXT2 }
                val c = LinearLayout(this).apply {
                    orientation = LinearLayout.HORIZONTAL; gravity = Gravity.CENTER_VERTICAL; background = round(CARD, 18, LINE); setPadding(dp(14), dp(12), dp(14), dp(12))
                    if (ok == false && i >= 0 && label != "المنصة") { isClickable = true; setOnClickListener { go(3) } }
                }
                c.addView(View(this).apply { background = GradientDrawable().apply { shape = GradientDrawable.OVAL; setColor(color) } },
                    LinearLayout.LayoutParams(dp(9), dp(9)).apply { marginEnd = dp(10) })
                val t = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL }
                t.addView(text(label, 11.5f, TEXT2))
                t.addView(text(value, 14f, TEXT, med).apply { setPadding(0, dp(4), 0, 0) })
                c.addView(t)
                r.addView(c, LinearLayout.LayoutParams(0, -2, 1f).apply { if (i == 0) marginEnd = dp(10) })
            }
        }
    }

    private fun orders() {
        val f = lastFeed ?: run { card().addView(text("جاري التحميل…", 14f, TEXT2)); return }
        val pending = f.optJSONArray("pending") ?: JSONArray()
        section("بانتظار التحويل", pending.length())
        if (pending.length() == 0) card().addView(text("ماكو طلبات معلقة.", 14f, TEXT2))
        for (i in 0 until pending.length()) {
            val o = pending.getJSONObject(i)
            val c = card()
            val top = row(c)
            top.addView(avatar(o.optString("user")))
            val who = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL }
            who.addView(text(o.optString("user"), 15f, TEXT, med))
            who.addView(text(o.optString("phone"), 12.5f, TEXT2, ltr = true).apply { setPadding(0, dp(4), 0, 0) })
            top.addView(who, LinearLayout.LayoutParams(0, -2, 1f))
            top.addView(text(o.optString("created"), 11.5f, TEXT2, ltr = true))

            val box = LinearLayout(this).apply {
                orientation = LinearLayout.HORIZONTAL; gravity = Gravity.CENTER_VERTICAL; background = round(CARD2, 16); setPadding(dp(14), dp(10), dp(10), dp(10))
            }
            c.addView(box, LinearLayout.LayoutParams(-1, -2).apply { topMargin = dp(14) })
            val a = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL }
            a.addView(text("${o.optString("course")} · #${o.optInt("id")}", 11.5f, TEXT2))
            a.addView(text(money(o.optLong("amount")), 24f, GOLD2, head, ltr = true).apply { setPadding(0, dp(5), 0, 0) })
            box.addView(a, LinearLayout.LayoutParams(0, -2, 1f))
            btn(box, "نسخ المبلغ", small = true) { copy("المبلغ", o.optLong("amount").toString()) }.apply {
                (layoutParams as LinearLayout.LayoutParams).topMargin = 0
            }
            btn(c, "وصل التحويل؟ تأكيد يدوي") {
                confirm("تأكد إن ${money(o.optLong("amount"))} وصل لمحفظتك، وتفعيل طلب ${o.optString("user")}؟") { act("/api/v1/payments/console/orders/${o.optInt("id")}/approve") }
            }
        }
        recent(f, 20)
    }

    private fun recent(f: JSONObject, limit: Int) {
        val recent = f.optJSONArray("recent") ?: JSONArray()
        if (recent.length() == 0) return
        section("آخر المدفوعات")
        val c = card()
        for (i in 0 until minOf(limit, recent.length())) {
            val o = recent.getJSONObject(i)
            if (i > 0) c.addView(View(this).apply { setBackgroundColor(LINE) }, LinearLayout.LayoutParams(-1, 1).apply { topMargin = dp(10); bottomMargin = dp(10) })
            val r = row(c)
            r.addView(avatar(o.optString("user"), GREEN))
            val left = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL }
            left.addView(text(o.optString("user"), 14f, TEXT, med))
            left.addView(text("${o.optString("course")} · ${if (o.optString("by") == "auto") "تلقائي" else "يدوي"}", 12f, TEXT2).apply { setPadding(0, dp(4), 0, 0) })
            r.addView(left, LinearLayout.LayoutParams(0, -2, 1f))
            val right = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL; gravity = Gravity.END }
            right.addView(text("+" + money(o.optLong("amount")), 15f, GREEN, head, ltr = true))
            right.addView(text(o.optString("paid"), 11f, TEXT2, ltr = true).apply { setPadding(0, dp(4), 0, 0) })
            r.addView(right)
        }
    }

    private fun logs() {
        section("السجل")
        val lines = Sender.prefs(this).getString("log", "")!!.lines().filter { it.isNotBlank() }.take(40)
        val c = card()
        if (lines.isEmpty()) c.addView(text("بعد ماكو شي.", 14f, TEXT2))
        lines.forEachIndexed { i, l ->
            if (i > 0) c.addView(View(this).apply { setBackgroundColor(LINE) }, LinearLayout.LayoutParams(-1, 1).apply { topMargin = dp(8); bottomMargin = dp(8) })
            val r = row(c)
            r.addView(text(l.take(8), 11.5f, GOLD, Typeface.MONOSPACE, ltr = true).apply { setPadding(0, 0, 0, 0) }, LinearLayout.LayoutParams(-2, -2).apply { marginEnd = dp(12) })
            r.addView(text(l.drop(8).trim(), 12.5f, TEXT2), LinearLayout.LayoutParams(0, -2, 1f))
        }
        btn(body, "إرسال إشعار تجربة للمنصة", small = false) {
            Thread {
                val r = Sender.request(this, "POST", "/api/v1/payments/hook", JSONObject().put("source", "test").put("text", "test").toString())
                ui.post { Toast.makeText(this, if (r?.first == 200) "المنصة استلمت التجربة" else "فشل: ${r?.first ?: "ماكو اتصال"}", Toast.LENGTH_LONG).show() }
            }.start()
        }
    }

    private fun settings() {
        val p = Sender.prefs(this)
        section("ربط المنصة")
        val c = card()
        c.addView(text("من لوحة التحكم ← الدفع المباشر ← ربط تطبيق غني باي", 12.5f, TEXT2))
        fun field(label: String, value: String, secret: Boolean = false): EditText {
            c.addView(text(label, 13f, TEXT2, med).apply { setPadding(0, dp(16), 0, dp(8)) })
            return EditText(this).apply {
                setText(value); setTextColor(TEXT); textSize = 15f; textDirection = View.TEXT_DIRECTION_LTR; typeface = reg
                background = round(BG, 14, LINE); setPadding(dp(14), dp(12), dp(14), dp(12)); minHeight = dp(50)
                inputType = if (secret) InputType.TYPE_CLASS_TEXT or InputType.TYPE_TEXT_VARIATION_VISIBLE_PASSWORD else InputType.TYPE_TEXT_VARIATION_URI
                setOnFocusChangeListener { v, has -> v.background = round(BG, 14, if (has) GOLD else LINE) }
                c.addView(this, LinearLayout.LayoutParams(-1, -2))
            }
        }
        val url = field("رابط المنصة", p.getString("url", "https://ghaniacademy.online")!!)
        val secret = field("المفتاح السري", p.getString("secret", "")!!, true)
        space(c, 4)
        btn(c, "حفظ واختبار", primary = true) {
            p.edit().putString("url", url.text.toString().trim()).putString("secret", secret.text.toString().trim()).apply()
            Thread {
                val r = Sender.request(this, "GET", "/api/v1/payments/console/feed")
                ui.post {
                    val ok = r?.first == 200
                    Toast.makeText(this, if (ok) "متصل بالمنصة" else "فشل: ${r?.first ?: "ماكو اتصال"}", Toast.LENGTH_LONG).show()
                    if (ok) { lastFeed = JSONObject(r!!.second); online = true; tab = 0; Heartbeat.now(this) }
                    render()
                }
            }.start()
        }

        section("الصلاحيات")
        val perms = card()
        perms.addView(text("الثلاثة لازم تكون مفعلة حتى يقرأ ويؤكد بسرعة حتى والشاشة مطفية. وخلي الموبايل على الشاحن.", 12.5f, TEXT2))
        val sms = Build.VERSION.SDK_INT < 23 || checkSelfPermission(Manifest.permission.RECEIVE_SMS) == PackageManager.PERMISSION_GRANTED
        val battery = Build.VERSION.SDK_INT < 23 || (getSystemService(POWER_SERVICE) as PowerManager).isIgnoringBatteryOptimizations(packageName)
        fun perm(label: String, ok: Boolean, on: () -> Unit) {
            val r = row(perms)
            r.setPadding(0, dp(12), 0, 0)
            r.addView(text(label, 14f, TEXT, med), LinearLayout.LayoutParams(0, -2, 1f))
            if (ok) r.addView(pill("مفعل", GREEN)) else btn(r, "تفعيل", primary = true, small = true, on = on).apply { (layoutParams as LinearLayout.LayoutParams).topMargin = 0 }
        }
        perm("قراءة الإشعارات", listening()) { startActivity(Intent(Settings.ACTION_NOTIFICATION_LISTENER_SETTINGS)) }
        perm("عدم إيقافه لتوفير البطارية", battery) {
            if (Build.VERSION.SDK_INT >= 23) startActivity(Intent(Settings.ACTION_REQUEST_IGNORE_BATTERY_OPTIMIZATIONS, Uri.parse("package:$packageName")))
        }
        perm("قراءة رسائل SMS (اختياري)", sms) { if (Build.VERSION.SDK_INT >= 23) requestPermissions(arrayOf(Manifest.permission.RECEIVE_SMS), 1) }

        val v = runCatching { packageManager.getPackageInfo(packageName, 0).versionName }.getOrNull() ?: ""
        body.addView(text("غني باي v$v · Ghani Code", 11.5f, alpha(TEXT2, 150), ltr = true).apply { gravity = Gravity.CENTER; setPadding(0, dp(18), 0, 0) },
            LinearLayout.LayoutParams(-1, -2))
    }

    override fun onRequestPermissionsResult(requestCode: Int, permissions: Array<out String>, grantResults: IntArray) {
        render()
    }

    // ---------- الشبكة ----------
    private fun load() {
        if (!Sender.configured(this)) return
        Thread {
            val r = Sender.request(this, "GET", "/api/v1/payments/console/feed")
            ui.post {
                online = r?.first == 200
                if (online == true) lastFeed = runCatching { JSONObject(r!!.second) }.getOrNull()
                if (tab != 3) render() else renderNav()
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
        AlertDialog.Builder(this, android.R.style.Theme_DeviceDefault_Dialog_Alert).setMessage(msg)
            .setPositiveButton("تأكيد") { _, _ -> yes() }.setNegativeButton("إلغاء", null).show()
    }
}
