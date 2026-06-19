import Foundation
import Singular
import UIKit

#if canImport(AdServices)
    import AdServices
#endif

// MARK: - Attribution Function Namespace

/// Privacy-first mobile attribution via the Singular SDK.
///
/// Runs SKAdNetwork-only: `skAdNetworkEnabled` is on and Singular manages the
/// conversion-value model, while no IDFA is requested and no App Tracking
/// Transparency prompt is shown. This matches the app's privacy positioning.
/// Apple Search Ads is attributed separately and deterministically through
/// Apple's own AdServices framework, so nothing is needed here for that channel.
///
/// Singular's SDK key + secret are client-side keys (embedded in every install
/// by design). They are injected at build time from .env into Info.plist
/// (SingularApiKey / SingularSecret) via the plugin manifest and read at init.
///
/// NOTE: verify the SDK method signatures against the pinned pod version at
/// developers.singular.net — Singular's API evolves between major versions.
///
/// Namespace: "Attribution.*"
enum AttributionFunctions {

    private static var initialised = false

    /// Log to the console only on DEBUG builds, so release builds stay silent.
    /// Never logs key/secret values.
    private static func debugLog(_ message: String) {
        #if DEBUG
            // NSLog surfaces in Console.app and the Xcode console; print() only
            // reaches stdout, which `native:run` often does not capture.
            NSLog("[Attribution] %@", message)
        #endif
    }

    /// Initialise the Singular SDK (SKAdNetwork-only, no IDFA/ATT).
    /// Returns: success: boolean
    class Init: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard !AttributionFunctions.initialised else {
                AttributionFunctions.debugLog("init: already initialised")
                return ["success": true]
            }

            guard let apiKey = Bundle.main.object(forInfoDictionaryKey: "SingularApiKey") as? String,
                  let secret = Bundle.main.object(forInfoDictionaryKey: "SingularSecret") as? String,
                  !apiKey.isEmpty, !secret.isEmpty,
                  let config = SingularConfig(apiKey: apiKey, andSecret: secret) else {
                AttributionFunctions.debugLog("init: SingularApiKey/SingularSecret missing or invalid in Info.plist")
                return ["success": false]
            }

            // SKAdNetwork on, Singular-managed conversion values, no ATT wait.
            config.skAdNetworkEnabled = true
            config.manualSkanConversionManagement = false

            #if DEBUG
                // Verbose SDK logging: prints the Singular Device ID and the
                // session/event API requests + responses to the console.
                config.enableLogging = true
            #endif

            Singular.start(config)
            AttributionFunctions.initialised = true
            AttributionFunctions.debugLog("init: Singular started (SKAN-only, no IDFA/ATT)")

            // IDFV is a vendor-scoped, non-advertising id; safe to log in debug.
            // Register it as a test device in Singular's Testing Console.
            if let idfv = UIDevice.current.identifierForVendor?.uuidString {
                AttributionFunctions.debugLog("test device IDFV: \(idfv)")
            }

            #if canImport(AdServices)
                // Reference AdServices so the linker keeps it in EVERY build config
                // (not just DEBUG) — Singular reads the Apple Search Ads attribution
                // token (AAAttribution) from it for deterministic, no-IDFA ASA
                // attribution. There is no manifest key for system frameworks, so we
                // force the link here. Min iOS 18.2 always has AdServices.
                _ = AAAttribution.self

                #if DEBUG
                    if let token = try? AAAttribution.attributionToken() {
                        AttributionFunctions.debugLog(
                            "AdServices token OK (\(token.count) chars) — Apple Search Ads attribution available")
                    } else {
                        AttributionFunctions.debugLog(
                            "AdServices token unavailable (normal on simulator)")
                    }
                #endif
            #endif

            return ["success": true]
        }
    }

    /// Track a conversion event.
    /// Parameters:
    ///   - name: string (required) - prefer Singular standard event tokens
    ///   - value: (optional) double - monetary value
    ///   - currency: (optional) string - ISO 4217, required with value
    ///   - attributes: (optional) [String: Any] - non-PII extras
    /// Returns: success: boolean
    class Event: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let name = parameters["name"] as? String else {
                AttributionFunctions.debugLog("event: missing name")
                return ["success": false]
            }

            let value = parameters["value"] as? Double
            let currency = parameters["currency"] as? String
            let attributes = parameters["attributes"] as? [String: Any]

            if let value, let currency {
                Singular.customRevenue(name, currency: currency, amount: value)
            } else if let attributes {
                Singular.event(name, withArgs: attributes)
            } else {
                Singular.event(name)
            }

            AttributionFunctions.debugLog("event: \(name)")

            return ["success": true]
        }
    }

    /// Associate a hashed first-party user id (never PII).
    /// Parameters:
    ///   - userId: string (required)
    /// Returns: success: boolean
    class SetUserId: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let userId = parameters["userId"] as? String else {
                AttributionFunctions.debugLog("setUserId: missing userId")
                return ["success": false]
            }

            Singular.setCustomUserId(userId)
            AttributionFunctions.debugLog("setUserId: set (\(userId.count) chars)")

            return ["success": true]
        }
    }
}
