package iq.ghaniacademy.pay

import android.Manifest
import android.app.Activity
import android.content.ComponentName
import android.content.Intent
import android.content.pm.PackageManager
import android.graphics.Color
import android.graphics.Typeface
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
import android.widget.Button
import android.widget.EditText
import android.widget.LinearLayout
import android.widget.ScrollView
import android.widget.TextView
import org.json.JSONObject

class MainActivity : Activity() {
    private lateinit var status: TextView
    private lateinit var logView: TextView
    private val ui = Handler(Looper.getMainLooper())

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        window.decorView.layoutDirection = View.LAYOUT_DIRECTION_RTL
        val p = Sender.prefs(this)
        val pad = (18 * resources.displayMetrics.density).toInt()
        val root = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL; setPadding(pad, pad * 2, pad, pad); setBackgroundColor(Color.rgb(11, 10, 16)) }

        fun label(t: String, size: Float = 14f, color: Int = Color.rgb(185, 178, 198)) =
            TextView(this).apply { text = t; textSize = size; setTextColor(color); setPadding(0, pad / 2, 0, pad / 4) }

        root.addView(label("غني باي", 26f, Color.rgb(212, 175, 106)).apply { typeface = Typeface.DEFAULT_BOLD })
        root.addView(label("يقرأ إشعارات استلام الفلوس ويفعل طلبات الطلاب تلقائيا. خلي الموبايل شغال ومشحون ومربوط بالنت."))
        status = label("", 15f, Color.WHITE)
        root.addView(status)

        root.addView(label("رابط المنصة"))
        val url = EditText(this).apply { setText(p.getString("url", "https://ghaniacademy.online")); setTextColor(Color.WHITE); inputType = InputType.TYPE_TEXT_VARIATION_URI; textDirection = View.TEXT_DIRECTION_LTR }
        root.addView(url)
        root.addView(label("المفتاح السري (من لوحة التحكم ← الدفع المباشر)"))
        val secret = EditText(this).apply { setText(p.getString("secret", "")); setTextColor(Color.WHITE); inputType = InputType.TYPE_CLASS_TEXT or InputType.TYPE_TEXT_VARIATION_VISIBLE_PASSWORD; textDirection = View.TEXT_DIRECTION_LTR }
        root.addView(secret)

        fun button(t: String, on: () -> Unit) = Button(this).apply { text = t; setOnClickListener { on() } }.also { root.addView(it) }

        button("حفظ واختبار الاتصال") {
            p.edit().putString("url", url.text.toString().trim()).putString("secret", secret.text.toString().trim()).apply()
            Thread {
                val r = Sender.post(this, JSONObject().put("source", "test").put("text", "test"))
                Sender.log(this, "اختبار: " + (r ?: "فشل الاتصال"))
                ui.post { refresh() }
            }.start()
        }
        button("1) السماح بقراءة الإشعارات") { startActivity(Intent(Settings.ACTION_NOTIFICATION_LISTENER_SETTINGS)) }
        button("2) السماح بقراءة رسائل SMS") {
            if (Build.VERSION.SDK_INT >= 23) requestPermissions(arrayOf(Manifest.permission.RECEIVE_SMS), 1)
        }
        button("3) منع إيقافه لتوفير البطارية") {
            if (Build.VERSION.SDK_INT >= 23) {
                startActivity(Intent(Settings.ACTION_REQUEST_IGNORE_BATTERY_OPTIMIZATIONS, Uri.parse("package:$packageName")))
            }
        }
        button("إعادة إرسال المعلق") { Sender.flush(this); ui.postDelayed({ refresh() }, 3000) }

        root.addView(label("السجل"))
        logView = label("", 12f).apply { typeface = Typeface.MONOSPACE; gravity = Gravity.START }
        root.addView(logView)

        setContentView(ScrollView(this).apply { addView(root) })
    }

    override fun onResume() {
        super.onResume()
        refresh()
    }

    private fun refresh() {
        val listening = Settings.Secure.getString(contentResolver, "enabled_notification_listeners")
            ?.contains(ComponentName(this, PayListenerService::class.java).flattenToString()) == true
        val sms = Build.VERSION.SDK_INT < 23 || checkSelfPermission(Manifest.permission.RECEIVE_SMS) == PackageManager.PERMISSION_GRANTED
        val battery = Build.VERSION.SDK_INT < 23 || (getSystemService(POWER_SERVICE) as PowerManager).isIgnoringBatteryOptimizations(packageName)
        val queue = org.json.JSONArray(Sender.prefs(this).getString("queue", "[]")).length()
        fun mark(ok: Boolean) = if (ok) "✓" else "✗"
        status.text = "${mark(listening)} قراءة الإشعارات   ${mark(sms)} الرسائل   ${mark(battery)} البطارية" + if (queue > 0) "\nمعلق: $queue" else ""
        logView.text = Sender.prefs(this).getString("log", "")
    }
}
