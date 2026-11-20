package iq.ghaniacademy.pay

import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.provider.Telephony

/** رسائل SMS من المحافظ (مثل زين كاش) */
class SmsReceiver : BroadcastReceiver() {
    override fun onReceive(c: Context, intent: Intent) {
        if (intent.action != Telephony.Sms.Intents.SMS_RECEIVED_ACTION) return
        val parts = Telephony.Sms.Intents.getMessagesFromIntent(intent) ?: return
        if (parts.isEmpty()) return
        val from = parts[0].originatingAddress ?: ""
        val body = parts.joinToString("") { it.messageBody ?: "" }
        // نقبل من المرسلين بالاسم (ZainCash، Qi...) وليس من أرقام عادية
        if (from.any { it.isLetter() } && Sender.looksLikeMoney(body)) {
            Sender.enqueue(c, "sms", from, from, body, parts[0].timestampMillis)
        }
    }
}
