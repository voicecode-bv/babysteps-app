package app.innerr.attribution

import android.content.pm.ApplicationInfo
import android.content.pm.PackageManager
import android.provider.Settings
import android.util.Log
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeFunction
import com.nativephp.mobile.bridge.BridgeResponse
import com.singular.sdk.Singular
import com.singular.sdk.SingularConfig
import org.json.JSONObject

/**
 * Privacy-first mobile attribution via the Singular SDK.
 *
 * Android attributes through the Google Play Install Referrer; no advertising id
 * (GAID) is collected because the plugin deliberately does NOT declare the
 * com.google.android.gms.permission.AD_ID permission. This keeps measurement in
 * line with the app's privacy positioning while still attributing paid installs.
 *
 * Singular's SDK key + secret are client-side keys (embedded in every install by
 * design). They are injected at build time from .env via the plugin manifest's
 * `secrets` + `meta_data` placeholders and read here from the app's <meta-data>.
 *
 * NOTE: verify the SDK method signatures against the pinned version at
 * developers.singular.net — Singular's API evolves between major versions.
 *
 * Namespace: "Attribution.*"
 */
object AttributionFunctions {

    private const val TAG = "Attribution"
    private const val META_API_KEY = "app.innerr.attribution.SINGULAR_API_KEY"
    private const val META_SECRET = "app.innerr.attribution.SINGULAR_SECRET"

    @Volatile
    private var initialised = false

    /**
     * Log to Logcat only on debuggable builds, so release builds stay silent.
     * Never logs key/secret values.
     */
    private fun debugLog(activity: FragmentActivity, message: String) {
        val debuggable = (activity.applicationInfo.flags and ApplicationInfo.FLAG_DEBUGGABLE) != 0
        if (debuggable) {
            Log.d(TAG, message)
        }
    }

    class Init(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            if (initialised) {
                debugLog(activity, "init: already initialised")
                return BridgeResponse.success(mapOf("success" to true))
            }

            val metaData = activity.packageManager
                .getApplicationInfo(activity.packageName, PackageManager.GET_META_DATA)
                .metaData
            val apiKey = metaData?.getString(META_API_KEY)
            val secret = metaData?.getString(META_SECRET)

            if (apiKey.isNullOrBlank() || secret.isNullOrBlank()) {
                debugLog(activity, "init: SINGULAR_API_KEY/SINGULAR_SECRET missing in <meta-data>")
                return BridgeResponse.error(
                    "SINGULAR_KEYS_MISSING",
                    "Singular keys missing; set SINGULAR_API_KEY and SINGULAR_SECRET in .env",
                )
            }

            val config = SingularConfig(apiKey, secret)
                // Limit downstream sharing of attribution data (privacy posture).
                .withLimitDataSharing(true)

            Singular.init(activity.applicationContext, config)
            initialised = true
            debugLog(activity, "init: Singular initialised (SKAN-only, no IDFA/ATT)")

            // ANDROID_ID is a non-advertising device id; safe to log in debug.
            // Register it as a test device in Singular's Testing Console.
            val androidId = Settings.Secure.getString(
                activity.contentResolver,
                Settings.Secure.ANDROID_ID,
            )
            debugLog(activity, "test device ANDROID_ID: $androidId")

            return BridgeResponse.success(mapOf("success" to true))
        }
    }

    class Event(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val name = parameters["name"] as? String
            if (name == null) {
                debugLog(activity, "event: missing name")
                return BridgeResponse.error("INVALID_PARAMETERS", "Attribution.Event requires a 'name'")
            }

            val value = (parameters["value"] as? Number)?.toDouble()
            val currency = parameters["currency"] as? String

            @Suppress("UNCHECKED_CAST")
            val attributes = parameters["attributes"] as? Map<String, Any>

            when {
                value != null && currency != null -> Singular.customRevenue(name, currency, value)
                attributes != null -> Singular.eventJSON(name, JSONObject(attributes))
                else -> Singular.event(name)
            }

            debugLog(activity, "event: $name")

            return BridgeResponse.success(mapOf("success" to true))
        }
    }

    class SetUserId(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val userId = parameters["userId"] as? String
            if (userId == null) {
                debugLog(activity, "setUserId: missing userId")
                return BridgeResponse.error("INVALID_PARAMETERS", "Attribution.SetUserId requires a 'userId'")
            }

            Singular.setCustomUserId(userId)
            debugLog(activity, "setUserId: set (${userId.length} chars)")

            return BridgeResponse.success(mapOf("success" to true))
        }
    }
}
