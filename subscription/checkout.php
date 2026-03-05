<?php
$page_title = 'सदस्यता खरेदी करा';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/razorpay_config.php';
requireLogin();

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$db = getDB();
$school_id = $_SESSION['school_id'];
$school = getSchool();

$plan_id = intval($_GET['plan_id'] ?? 0);
if (!$plan_id) {
    flash('error', 'कृपया योजना निवडा');
    redirect(APP_URL . '/subscription/plans.php');
}

$stmt = $db->prepare("SELECT * FROM plans WHERE id = ? AND is_active = 1");
$stmt->execute([$plan_id]);
$plan = $stmt->fetch();

if (!$plan || $plan['price'] <= 0) {
    flash('error', 'अवैध योजना निवडली');
    redirect(APP_URL . '/subscription/plans.php');
}

$original_amount = floatval($plan['price']);
$discount_amount = 0;
$final_amount = $original_amount;
$coupon_applied = null;
$coupon_error = '';

// Handle AJAX API calls (accept both JSON body and form-urlencoded)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $json_input = json_decode($raw, true);
    // Use JSON body if available, fall back to $_POST
    $input = $json_input ?: $_POST;
    $action = $input['action'] ?? '';
    
    if ($action) {
    // CSRF validation for all AJAX actions
    $csrf = $input['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid CSRF token. Please refresh the page.']);
        exit;
    }
    header('Content-Type: application/json');
    
    if ($action === 'validate_coupon') {
        $code = strtoupper(trim($input['coupon_code'] ?? ''));
        $coupon = validateCoupon($db, $code);
        if ($coupon) {
            $result = applyCouponDiscount($original_amount, $coupon);
            echo json_encode([
                'valid' => true,
                'discount_percent' => $coupon['discount_percent'],
                'discount_amount' => $result['discount_amount'],
                'final_amount' => $result['final_amount'],
                'message' => $coupon['discount_percent'] . '% सवलत लागू!'
            ]);
        } else {
            echo json_encode(['valid' => false, 'message' => 'अवैध किंवा कालबाह्य कूपन कोड']);
        }
        exit;
    }
    
    if ($action === 'create_order') {
        $coupon_code = strtoupper(trim($input['coupon_code'] ?? ''));
        $amount = $original_amount;
        
        if ($coupon_code) {
            $coupon = validateCoupon($db, $coupon_code);
            if ($coupon) {
                $result = applyCouponDiscount($amount, $coupon);
                $amount = $result['final_amount'];
                $discount_amount = $result['discount_amount'];
            }
        }
        
        if ($amount <= 0) {
            // Free after coupon - activate directly
            $stmt = $db->prepare("UPDATE schools SET plan_id = ?, subscription_start = CURDATE(), subscription_end = DATE_ADD(CURDATE(), INTERVAL ? MONTH) WHERE id = ?");
            $stmt->execute([$plan_id, $plan['duration_months'], $school_id]);
            
            if ($coupon_code && isset($coupon)) {
                $db->prepare("UPDATE coupon_codes SET used_count = used_count + 1 WHERE id = ?")->execute([$coupon['id']]);
            }
            
            $stmt = $db->prepare("INSERT INTO payments (school_id, plan_id, amount, original_amount, discount_amount, coupon_code, payment_method, status) VALUES (?, ?, 0, ?, ?, ?, 'coupon', 'completed')");
            $stmt->execute([$school_id, $plan_id, $original_amount, $discount_amount, $coupon_code]);
            
            echo json_encode(['free' => true, 'redirect' => APP_URL . '/dashboard.php?upgraded=1']);
            exit;
        }
        
        // Create Razorpay order
        $receipt = 'order_' . $school_id . '_' . time();
        $order = createRazorpayOrder($amount, $receipt, [
            'school_id' => (string)$school_id,
            'plan_id' => (string)$plan_id,
            'coupon_code' => $coupon_code,
        ]);
        
        if ($order && isset($order['id'])) {
            // Save pending payment
            $stmt = $db->prepare("INSERT INTO payments (school_id, plan_id, amount, original_amount, discount_amount, coupon_code, razorpay_order_id, payment_method, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'razorpay', 'pending')");
            $stmt->execute([$school_id, $plan_id, $amount, $original_amount, $discount_amount, $coupon_code ?: null, $order['id']]);
            
            echo json_encode([
                'order_id' => $order['id'],
                'razorpay_order_id' => $order['id'],
                'amount' => $order['amount'],
                'currency' => $order['currency'],
                'key_id' => RAZORPAY_KEY_ID,
                'plan_name' => $plan['name_mr'] ?: $plan['name'],
            ]);
        } else {
            echo json_encode(['error' => 'Razorpay ऑर्डर तयार करता आली नाही. कृपया नंतर प्रयत्न करा.']);
        }
        exit;
    }
    
    if ($action === 'verify_payment') {
        $razorpay_order_id = $input['razorpay_order_id'] ?? '';
        $razorpay_payment_id = $input['razorpay_payment_id'] ?? '';
        $razorpay_signature = $input['razorpay_signature'] ?? '';
        
        if (verifyRazorpaySignature($razorpay_order_id, $razorpay_payment_id, $razorpay_signature)) {
            // Update payment record
            $stmt = $db->prepare("UPDATE payments SET razorpay_payment_id = ?, razorpay_signature = ?, status = 'completed' WHERE razorpay_order_id = ? AND school_id = ?");
            $stmt->execute([$razorpay_payment_id, $razorpay_signature, $razorpay_order_id, $school_id]);
            
            // Get payment to find plan
            $stmt = $db->prepare("SELECT * FROM payments WHERE razorpay_order_id = ? AND school_id = ?");
            $stmt->execute([$razorpay_order_id, $school_id]);
            $payment = $stmt->fetch();
            
            if ($payment) {
                // Update school plan
                $stmt = $db->prepare("SELECT duration_months FROM plans WHERE id = ?");
                $stmt->execute([$payment['plan_id']]);
                $plan_dur = $stmt->fetch();
                
                $stmt = $db->prepare("UPDATE schools SET plan_id = ?, subscription_start = CURDATE(), subscription_end = DATE_ADD(CURDATE(), INTERVAL ? MONTH) WHERE id = ?");
                $stmt->execute([$payment['plan_id'], $plan_dur['duration_months'] ?? 12, $school_id]);
                
                // Increment coupon usage if used
                if ($payment['coupon_code']) {
                    $db->prepare("UPDATE coupon_codes SET used_count = used_count + 1 WHERE code = ?")->execute([$payment['coupon_code']]);
                }
            }
            
            echo json_encode(['success' => true, 'redirect' => APP_URL . '/dashboard.php?upgraded=1']);
        } else {
            echo json_encode(['success' => false, 'error' => 'पेमेंट सत्यापन अयशस्वी. कृपया admin शी संपर्क करा.']);
        }
        exit;
    }
    } // end if ($action)
}

require_once __DIR__ . '/../includes/header.php';

// Escape values for safe JavaScript embedding (not sanitize which outputs HTML entities)
$js_school_name = addslashes($school['name_mr'] ?: $school['name']);
$js_plan_name = addslashes($plan['name_mr'] ?: $plan['name']);
$js_school_email = addslashes($school['email']);
$js_school_phone = addslashes($school['phone'] ?? '');
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h4 class="mb-0"><i class="bi bi-credit-card"></i> सदस्यता खरेदी करा</h4>
                </div>
                <div class="card-body">
                    <!-- Error/Success Messages -->
                    <div id="checkoutError" class="alert alert-danger d-none"></div>
                    <div id="checkoutSuccess" class="alert alert-success d-none"></div>

                    <!-- Plan Summary -->
                    <div class="alert alert-info">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="mb-1"><?= sanitize($plan['name_mr'] ?: $plan['name']) ?></h5>
                                <p class="mb-0 text-muted"><?= $plan['max_students'] >= 9999 ? 'अमर्यादित' : $plan['max_students'] ?> विद्यार्थी | <?= $plan['duration_months'] ?> महिने</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <h3 class="mb-0" id="display_amount">&#8377;<?= number_format($plan['price'], 0) ?></h3>
                            </div>
                        </div>
                    </div>

                    <!-- Coupon Code -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h6><i class="bi bi-ticket-perforated"></i> कूपन कोड (Coupon Code)</h6>
                            <div class="input-group">
                                <input type="text" class="form-control" id="coupon_code" placeholder="कूपन कोड टाका..." style="text-transform:uppercase;">
                                <button class="btn btn-outline-primary" type="button" id="apply_coupon">
                                    <i class="bi bi-check-lg"></i> लागू करा
                                </button>
                            </div>
                            <div id="coupon_message" class="mt-2 small"></div>
                        </div>
                    </div>

                    <!-- Price Breakdown -->
                    <div class="card mb-4" id="price_breakdown" style="display:none;">
                        <div class="card-body">
                            <table class="table table-sm mb-0">
                                <tr>
                                    <td>मूळ किंमत</td>
                                    <td class="text-end">&#8377;<span id="orig_price"><?= number_format($plan['price'], 0) ?></span></td>
                                </tr>
                                <tr class="text-success">
                                    <td>सवलत (<span id="discount_pct">0</span>%)</td>
                                    <td class="text-end">- &#8377;<span id="discount_amt">0</span></td>
                                </tr>
                                <tr class="fw-bold border-top">
                                    <td>एकूण रक्कम</td>
                                    <td class="text-end">&#8377;<span id="final_price"><?= number_format($plan['price'], 0) ?></span></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Pay Button -->
                    <div class="d-grid">
                        <button class="btn btn-success btn-lg" id="pay_btn" type="button">
                            <i class="bi bi-shield-lock"></i> &#8377;<span id="pay_amount"><?= number_format($plan['price'], 0) ?></span> भरा (Razorpay)
                        </button>
                    </div>

                    <!-- Payment Security Info -->
                    <div class="card mt-3 border-0" style="border-left: 3px solid #22c55e !important; background: #f0fdf4;">
                        <div class="card-body small text-muted py-2">
                            <p class="mb-1"><i class="bi bi-lock me-1"></i> Razorpay सुरक्षित पेमेंट गेटवे</p>
                            <p class="mb-1"><i class="bi bi-credit-card me-1"></i> UPI, Credit/Debit Card, Net Banking</p>
                            <p class="mb-0"><i class="bi bi-check-circle me-1"></i> पेमेंट यशस्वी झाल्यावर सदस्यता activate होईल</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
// Payment configuration
var originalAmount = <?= $plan['price'] ?>;
var finalAmount = originalAmount;
var appliedCoupon = '';
var csrfToken = '<?= $_SESSION['csrf_token'] ?>';
var checkoutUrl = '<?= APP_URL ?>/subscription/checkout.php?plan_id=<?= $plan_id ?>';
var btnLabel = '<i class="bi bi-shield-lock"></i> &#8377;' + originalAmount + ' भरा (Razorpay)';

function showError(msg) {
    var el = document.getElementById('checkoutError');
    el.textContent = msg;
    el.classList.remove('d-none');
    document.getElementById('checkoutSuccess').classList.add('d-none');
}
function hideError() {
    document.getElementById('checkoutError').classList.add('d-none');
}

// Coupon validation
document.getElementById('apply_coupon').addEventListener('click', function() {
    var code = document.getElementById('coupon_code').value.trim();
    if (!code) return;
    hideError();
    
    fetch(checkoutUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'validate_coupon',
            coupon_code: code,
            csrf_token: csrfToken
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        var msg = document.getElementById('coupon_message');
        if (data.error) { showError(data.error); return; }
        if (data.valid) {
            msg.innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> ' + data.message + '</span>';
            finalAmount = data.final_amount;
            appliedCoupon = code;
            document.getElementById('price_breakdown').style.display = 'block';
            document.getElementById('discount_pct').textContent = data.discount_percent;
            document.getElementById('discount_amt').textContent = data.discount_amount;
            document.getElementById('final_price').textContent = data.final_amount;
            document.getElementById('display_amount').innerHTML = '&#8377;' + data.final_amount + ' <small class="text-decoration-line-through text-muted">&#8377;' + originalAmount + '</small>';
            document.getElementById('pay_amount').textContent = data.final_amount;
            btnLabel = '<i class="bi bi-shield-lock"></i> &#8377;' + data.final_amount + ' भरा (Razorpay)';
        } else {
            msg.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle"></i> ' + data.message + '</span>';
            finalAmount = originalAmount;
            appliedCoupon = '';
            document.getElementById('price_breakdown').style.display = 'none';
            btnLabel = '<i class="bi bi-shield-lock"></i> &#8377;' + originalAmount + ' भरा (Razorpay)';
        }
    })
    .catch(function(err) {
        showError('कूपन तपासताना त्रुटी आली. कृपया पुन्हा प्रयत्न करा.');
    });
});

// Payment initiation (matching reference site pattern)
document.getElementById('pay_btn').addEventListener('click', function() {
    var btn = document.getElementById('pay_btn');
    hideError();
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> कृपया प्रतीक्षा करा...';

    // Step 1: Create Razorpay order on server
    fetch(checkoutUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'create_order',
            coupon_code: appliedCoupon,
            csrf_token: csrfToken
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.error) {
            showError(data.error);
            btn.disabled = false;
            btn.innerHTML = btnLabel;
            return;
        }
        if (data.free) {
            window.location.href = data.redirect;
            return;
        }

        // Step 2: Open Razorpay checkout
        var options = {
            key: data.key_id,
            amount: data.amount,
            currency: data.currency || 'INR',
            name: '<?= $js_school_name ?>',
            description: (data.plan_name || '<?= $js_plan_name ?>') + ' - HPC कार्ड SaaS',
            order_id: data.razorpay_order_id || data.order_id,
            prefill: {
                email: '<?= $js_school_email ?>',
                contact: '<?= $js_school_phone ?>'
            },
            theme: { color: '#1a73e8' },
            handler: function(response) {
                verifyPayment(response);
            },
            modal: {
                ondismiss: function() {
                    btn.disabled = false;
                    btn.innerHTML = btnLabel;
                    showError('पेमेंट रद्द केले. पुन्हा प्रयत्न करा.');
                }
            }
        };

        var rzp = new Razorpay(options);
        rzp.on('payment.failed', function(response) {
            btn.disabled = false;
            btn.innerHTML = btnLabel;
            showError('पेमेंट अयशस्वी: ' + (response.error.description || 'कृपया पुन्हा प्रयत्न करा'));
        });
        rzp.open();
    })
    .catch(function(err) {
        console.error('Checkout error:', err);
        showError('सर्व्हर त्रुटी. कृपया पृष्ठ रीफ्रेश करून पुन्हा प्रयत्न करा.');
        btn.disabled = false;
        btn.innerHTML = btnLabel;
    });
});

// Step 3: Verify payment
function verifyPayment(razorpayResponse) {
    var btn = document.getElementById('pay_btn');
    hideError();
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> पेमेंट verify होत आहे...';

    fetch(checkoutUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'verify_payment',
            razorpay_order_id: razorpayResponse.razorpay_order_id,
            razorpay_payment_id: razorpayResponse.razorpay_payment_id,
            razorpay_signature: razorpayResponse.razorpay_signature,
            csrf_token: csrfToken
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            document.getElementById('checkoutSuccess').textContent = 'पेमेंट यशस्वी! सदस्यता activate होत आहे...';
            document.getElementById('checkoutSuccess').classList.remove('d-none');
            window.location.href = data.redirect;
        } else {
            showError(data.error || 'पेमेंट verification अयशस्वी. कृपया admin शी संपर्क करा.');
            btn.disabled = false;
            btn.innerHTML = btnLabel;
        }
    })
    .catch(function(err) {
        showError('Verification त्रुटी. कृपया admin शी संपर्क करा.');
        btn.disabled = false;
        btn.innerHTML = btnLabel;
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
