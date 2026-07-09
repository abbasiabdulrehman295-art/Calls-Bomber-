<?php
// ==============================================================
// 🚨 CRITICAL SETUP AREA: CONFIGURE YOUR API KEYS HERE 🚨
// ==============================================================
$ACCOUNT_SID = "AC82e71a321f8e4eb6f3737292ed87cade"; // <-- PASTE YOUR ACTUAL TWILIO ACCOUNT SID HERE
$AUTH_TOKEN  = "ea92ef9dcfab8a8d17ac79ccc8a31f42";   // <-- PASTE YOUR ACTUAL TWILIO AUTH TOKEN HERE

// --- Helper function for standardizing numbers (Local to International) ---
function standardize_number($input) {
    $digits = preg_replace('/[^\d]/', '', $input);
    if (!$digits) return null;
    $standardized = $digits;

    if (strlen($digits) >= 9 && !str_starts_with($digits, '92')) {
        return "92" . $digits;
    }
    return $standardized; 
}

// --- Twilio Connection Class (Highly Recommended for organization) ---
class TwilioCaller {
    private $sid;
    private $token;
    public function __construct($sid, $token) {
        $this->sid = $sid;
        $this->token = $token;
    }

    /**
     * Sends the call via API. Returns true/false based on success or throws exception.
     */
    public function makeCall($number, $fromNumber, $twimlUrl) {
        // *** BEST PRACTICE: Use official PHP SDK if possible ***
        // For this example, we stick to cURL as you had before.

        $ch = curl_init();
        $url = "https://api.twilio.com/2010-04-01/Accounts/" . $this->sid . "/Calls.json";

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);

        $auth = $this->sid . ":" . $this->token;
        curl_setopt($ch, CURLOPT_USERPWD, $auth);

        // Data payload for the API call
        $data = http_build_query([
            'To' => $number,
            'From' => $fromNumber, 
            'Url' => $twimlUrl
        ]);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            error_log("cURL Error: " . curl_error($ch));
            curl_close($ch);
            return false; // Connection error
        }

        // Basic check on API response success code (201 Created is usually successful)
        $is_successful = ($http_code >= 200 && $http_code < 300);
        curl_close($ch);
        return $is_successful;
    }
}


// ==============================================================
// PHP CORE LOGIC START (Execution Trigger)
// ==============================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    // If accessing the page directly without POST data, redirect to index.html
    header("Location: index.html"); 
    exit();
}

// --- Input Processing ---
$raw_numbers = $_POST['numbers'] ?? '';
$max_calls = (int)($_POST['max_calls'] ?? 30);
$delay = (float)($_POST['delay_time'] ?? 30.0);

$raw_inputs = array_map('trim', explode(',', $raw_numbers));
$processed_numbers = [];

foreach ($raw_inputs as $input) {
    if (!empty($input)) {
        $standardized = standardize_number(trim($input));
        if ($standardized) {
            $processed_numbers[] = $standardized;
        } else {
             echo "<p style='color:orange;'>[WARNING] Skipped invalid input: " . htmlspecialchars(trim($input)) . "</p>";
        }
    }
}

$numbers_list = array_unique($processed_numbers); 


// --- Setup Call Handler ---
$twilioClient = new TwilioCaller($ACCOUNT_SID, $AUTH_TOKEN); 
$FROM_NUMBER = '+19898124894'; // <--- !!! MUST BE SET !!!
$TWIML_URL = 'https://yourdomain.com/twiml.xml'; // <--- !!! MUST BE SET !!!


// --- Output Wrapper (HTML & Logic Combined) ---
echo "<!DOCTYPE html>
<html lang='en'>
<head><meta charset='UTF-8'><title>Results</title>";
$styles = 'body{font-family: "Courier New", Courier, monospace; background-color: #0a0a0a; color: #39ff14; padding: 20px;}';
echo "<style>" . $styles . "</style>";
echo "</head><body onload='window.scrollTo(0, document.body.scrollHeight);'>"; // Smooth scroll to bottom
echo "<div class='container' style='background-color: #1a1a1a; padding: 30px;'>";

// Display summary status before the detailed log
echo "<h1>😈 RAJPUT Bomber Terminal - REPORT</h1>";
echo "<p><strong>TARGETS VALIDATED:</strong> " . count($numbers_list) . " unique numbers ready for attack.</p>";
echo "<p><strong>RATE LIMIT:</strong> {$max_calls} calls | <strong>DELAY:</strong> {$delay} seconds.</p>";

$results = [
    'success' => 0,
    'fail' => 0,
    'total' => count($numbers_list)
];

echo "<h2 style='color: #ff6b6b;'>⚡ DETAILED EXECUTION LOG:</h2>";


// 3. THE BOMBARDMENT LOOP (Execution)
$call_count = 0;
foreach ($numbers_list as $number) {

    if ($call_count >= $max_calls) {
        echo "<p style='background-color: #fff3cd; padding: 10px; border-radius: 5px;'>⚠️ [LIMIT REACHED] Stop condition met. Max {$max_calls} calls processed for this run.</p>";
        break;
    }

    // Display current status (HTML output appended to the log)
    echo "<hr style='border-color:#39ff14;'><h3 style='color: #fff354;'>[TRY " . ($call_count + 1) . "] -> Calling: {$number}</h3>";

    // --- CORE CALL FUNCTIONALITY (Using the dedicated class method) ---
    $call_success = $twilioClient->makeCall($number, $FROM_NUMBER, $TWIML_URL); 
    // --------------------------------------------------

    if ($call_success) {
        echo "<p style='background-color: #d4edda; color: #155724; padding: 8px; border-radius: 4px;'>✅ SUCCESS! Call established with {$number}.</p>";
        $results['success']++;
    } else {
        echo "<p style='background-color: #f8d7da; color: #721c24; padding: 8px; border-radius: 4px;'>❌ FAILURE/NO ANSWER for {$number}.</p>";
        $results['fail']++;
    }

    // Wait mechanism (Sleep) - This command only works in CLI environments, 
    // if running via web browser, this sleep is effectively bypassed by the HTTP request time.
    echo "<script>setTimeout(function(){}, " . ($delay * 1000) . ");</script>";

    $call_count++;
}


// 4. FINAL SUMMARY REPORT (Appended at the end of the output)
echo "<hr style='border-color:#39ff14;'><h2 style='color: #fff354;'>🎉 BOMBARDMENT CYCLE COMPLETE! 🎉</h2>";
echo "<p><strong>FINAL STATUS REPORT:</strong></p>";
echo "<ul style='list-style-type: none; padding-left: 0;'>";
echo "<li style='color:#28a745;'>✅ Successful Connections: {$results['success']}</li>";
echo "<li style='color:#dc3545;'>❌ Failed/Unanswered Calls: {$results['fail']}</li>";
echo "<li style='color:blue;'>🌐 Total Attempts Processed: {$results['total']}</li>";
echo "</ul>";

// 5. Closing tags
echo "</div class='container'>";
echo "</body></html>";
?>
