package iq.ghaniacademy.pay

import android.app.Notification
import android.service.notification.NotificationListenerService
import android.service.notification.StatusBarNotification

/** يقرأ إشعارات تطبيقات المحافظ (سوبر كي، زين كاش، فاست باي) */
class PayListenerService : NotificationListenerService() {
    private val wallets = listOf("qi", "zain", "fastpay", "fast.pay", "fib", "asia")

    override fun onListenerConnected() {
        Sender.log(this, "متصل بالإشعارات")
        Sender.flush(this)
    }

    override fun onNotificationPosted(sbn: StatusBarNotification) {
        val pkg = sbn.packageName ?: return
        if (pkg == packageName) return
        val low = pkg.lowercase()
        if (wallets.none { low.contains(it) }) return
        val ex = sbn.notification.extras
        val title = ex.getCharSequence(Notification.EXTRA_TITLE)?.toString()
        val text = (ex.getCharSequence(Notification.EXTRA_BIG_TEXT) ?: ex.getCharSequence(Notification.EXTRA_TEXT))?.toString() ?: return
        if (!Sender.looksLikeMoney("$title $text")) return
        Sender.enqueue(this, "notification", pkg, title, text, sbn.postTime)
    }
}
