<?php
// Razorpay Configuration
// Keys are loaded from environment variables for security

// Check for environment variables first, then fall back to constants
$razorpay_key_id = getenv('RAZORPAY_KEY_ID') ?: '';
$razorpay_key_secret = getenv('RAZORPAY_KEY_SECRET') ?: '';

define('RAZORPAY_KEY_ID', $razorpay_key_id);
define('RAZORPAY_KEY_SECRET', $razorpay_key_secret);

// Razorpay API endpoints
define('RAZORPAY_API_URL', 'https://api.razorpay.com/v1');

/**
 * Create a Razorpay order
 * @param float $amount Amount in INR
 * @param string $receipt Receipt ID
 * @param array $notes Additional notes
 * @return array|false Order data or false on failure
 */
function createRazorpayOrder($amount, $receipt, $notes = []) {
    if (empty(RAZORPAY_KEY_ID) || empty(RAZORPAY_KEY_SECRET)) {
        return false;
    }
    
    $orderData = [
        'amount' => intval($amount * 100), // Razorpay expects amount in paise
        'currency' => 'INR',
        'receipt' => $receipt,
        'notes' => $notes,
    ];
    
    $ch = curl_init(RAZORPAY_API_URL . '/orders');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_USERPWD, RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        return json_decode($response, true);
    }
    
    return false;
}

/**
 * Verify Razorpay payment signature
 * @param string $orderId Razorpay order ID
 * @param string $paymentId Razorpay payment ID
 * @param string $signature Razorpay signature
 * @return bool
 */
function verifyRazorpaySignature($orderId, $paymentId, $signature) {
    $expectedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, RAZORPAY_KEY_SECRET);
    return hash_equals($expectedSignature, $signature);
}

/**
 * Validate a coupon code
 * @param PDO $db Database connection
 * @param string $code Coupon code
 * @return array|false Coupon data or false if invalid
 */
function validateCoupon($db, $code) {
    $code = strtoupper(trim($code));
    if (empty($code)) return false;
    
    $stmt = $db->prepare("SELECT * FROM coupon_codes WHERE code = ? AND is_active = 1");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();
    
    if (!$coupon) return false;
    
    // Check usage limit
    if ($coupon['used_count'] >= $coupon['max_uses']) return false;
    
    // Check date range
    $today = date('Y-m-d');
    if ($coupon['valid_from'] && $today < $coupon['valid_from']) return false;
    if ($coupon['valid_to'] && $today > $coupon['valid_to']) return false;
    
    return $coupon;
}

/**
 * Apply coupon discount
 * @param float $amount Original amount
 * @param array $coupon Coupon data from validateCoupon
 * @return array [final_amount, discount_amount]
 */
function applyCouponDiscount($amount, $coupon) {
    $discount = ($amount * $coupon['discount_percent']) / 100;
    $final = max(0, $amount - $discount);
    return ['final_amount' => round($final, 2), 'discount_amount' => round($discount, 2)];
}
