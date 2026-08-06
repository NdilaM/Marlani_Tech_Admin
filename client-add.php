<?php
// client-add.php - Add New Client with Auto PDF Quotation
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.html');
    exit();
}

// Include database connection
require_once 'db.php';

// Get user data from session
$first_name = $_SESSION['first_name'] ?? 'User';
$staff_id = $_SESSION['staff_id'] ?? 1;
$errors = [];
$success = false;

// Function to get default quote items
function getDefaultQuoteItems() {
    return [
        ['item_name' => 'Website Development - Basic Package', 'description' => '5-page responsive website with CMS', 'quantity' => 1, 'unit_price' => 15000.00],
        ['item_name' => 'Custom Web Application', 'description' => 'Custom PHP/MySQL web application', 'quantity' => 1, 'unit_price' => 35000.00],
        ['item_name' => 'Hosting - Premium Package', 'description' => '1 year premium hosting with SSL', 'quantity' => 1, 'unit_price' => 8500.00],
        ['item_name' => 'Domain Registration', 'description' => '.co.za domain for 1 year', 'quantity' => 1, 'unit_price' => 150.00],
        ['item_name' => 'Email Hosting', 'description' => '5 business email accounts - 1 year', 'quantity' => 5, 'unit_price' => 360.00],
        ['item_name' => 'Maintenance & Support', 'description' => 'Monthly maintenance and support - 12 months', 'quantity' => 12, 'unit_price' => 1500.00]
    ];
}

// Function to generate quote number
function generateQuoteNumber($conn) {
    $stmt = $conn->prepare("SELECT setting_value FROM company_settings WHERE setting_key = 'quote_prefix'");
    $stmt->execute();
    $prefix = $stmt->fetch(PDO::FETCH_ASSOC);
    $prefix = $prefix['setting_value'] ?? 'Q-2026-';
    
    $stmt = $conn->query("SELECT MAX(id) as max_id FROM quotations");
    $max_id = $stmt->fetch(PDO::FETCH_ASSOC)['max_id'] ?? 0;
    return $prefix . str_pad(($max_id + 1), 4, '0', STR_PAD_LEFT);
}

// Function to generate client code
function generateClientCode($conn) {
    $stmt = $conn->query("SELECT MAX(id) as max_id FROM clients");
    $max_id = $stmt->fetch(PDO::FETCH_ASSOC)['max_id'] ?? 0;
    return 'CL-' . date('Y') . '-' . str_pad(($max_id + 1), 3, '0', STR_PAD_LEFT);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $company_name = trim($_POST['company_name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $country = trim($_POST['country'] ?? 'South Africa');
    $industry = trim($_POST['industry'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $client_type = $_POST['client_type'] ?? 'business';
    $notes = trim($_POST['notes'] ?? '');
    $generate_quote = isset($_POST['generate_quote']) ? true : false;
    
    // Validate
    if (empty($company_name)) $errors[] = "Company name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    
    // Check if email exists
    if (!empty($email)) {
        $stmt = $conn->prepare("SELECT id FROM clients WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Email already exists";
        }
    }
    
    // If no errors, insert client and generate quote
    if (empty($errors)) {
        try {
            // Start transaction
            $conn->beginTransaction();
            
            // Generate client code
            $client_code = generateClientCode($conn);
            
            // Insert client
            $stmt = $conn->prepare("
                INSERT INTO clients (client_code, company_name, contact_person, email, phone, 
                                   address, city, province, postal_code, country, industry, status, 
                                   client_type, notes, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $client_code, $company_name, $contact_person, $email, $phone,
                $address, $city, $province, $postal_code, $country, $industry, $status,
                $client_type, $notes, $staff_id
            ]);
            
            $client_id = $conn->lastInsertId();
            
            // Generate quote if requested
            $quote_id = null;
            $pdf_path = null;
            
            if ($generate_quote) {
                // Get default items
                $items = getDefaultQuoteItems();
                
                // Calculate totals (NO VAT)
                $subtotal = 0;
                foreach ($items as $item) {
                    $subtotal += $item['quantity'] * $item['unit_price'];
                }
                
                $grand_total = $subtotal;
                $quote_date = date('Y-m-d');
                $expiry_date = date('Y-m-d', strtotime('+14 days'));
                $quote_number = generateQuoteNumber($conn);
                
                // ============================================
                // FIXED: Match your actual table columns
                // Your table has: id, quote_number, client_id, quote_date, expiry_date, 
                // total_amount, grand_total, status, notes, terms, valid_until, 
                // pdf_path, created_at, updated_at, created_by
                // ============================================
                $stmt = $conn->prepare("
                    INSERT INTO quotations (
                        quote_number, 
                        client_id, 
                        quote_date, 
                        expiry_date, 
                        total_amount, 
                        grand_total, 
                        status, 
                        created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $quote_number, 
                    $client_id, 
                    $quote_date, 
                    $expiry_date,
                    $subtotal, 
                    $grand_total, 
                    'draft', 
                    $staff_id
                ]);
                
                $quote_id = $conn->lastInsertId();
                
                // Insert items
                foreach ($items as $item) {
                    $total = $item['quantity'] * $item['unit_price'];
                    $stmt = $conn->prepare("
                        INSERT INTO quotation_items (quotation_id, item_name, description, quantity, unit_price, total)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $quote_id, $item['item_name'], $item['description'], 
                        $item['quantity'], $item['unit_price'], $total
                    ]);
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            // Set success message
            $_SESSION['success'] = $generate_quote ? 
                "Client added successfully! Quotation #$quote_number generated." : 
                "Client added successfully!";
            
            header('Location: clients.php');
            exit();
            
        } catch(Exception $e) {
            $conn->rollBack();
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}

// Function to generate HTML for PDF
function generateQuoteHTML($client_id, $quote_id, $items, $settings, $conn) {
    // Get client info
    $stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get quote info
    $stmt = $conn->prepare("SELECT * FROM quotations WHERE id = ?");
    $stmt->execute([$quote_id]);
    $quote = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; padding: 40px; }
            .header { text-align: center; border-bottom: 2px solid #1e3c72; padding-bottom: 20px; margin-bottom: 30px; }
            .header h1 { color: #1e3c72; margin: 0; font-size: 24px; }
            .header h2 { color: #4e73df; margin: 5px 0; }
            .header p { color: #666; margin: 5px 0; }
            .info { display: flex; justify-content: space-between; margin-bottom: 30px; }
            .info-box { width: 45%; }
            .info-box h4 { color: #1e3c72; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-bottom: 10px; }
            .info-box p { margin: 5px 0; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th { background: #1e3c72; color: #fff; padding: 10px; text-align: left; }
            td { padding: 10px; border-bottom: 1px solid #ddd; }
            .text-right { text-align: right; }
            .total-section { text-align: right; margin-top: 20px; }
            .total-section p { margin: 5px 0; }
            .grand-total { font-size: 18px; color: #1e3c72; font-weight: bold; }
            .terms { margin-top: 30px; padding: 20px; background: #f8f9fc; border-radius: 5px; }
            .terms h4 { margin-top: 0; color: #1e3c72; }
            .terms p { margin: 5px 0; font-size: 12px; }
            .footer { margin-top: 40px; text-align: center; border-top: 1px solid #ddd; padding-top: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>' . ($settings['company_name'] ?? 'Marlani Technologies') . '</h1>
            <h2>QUOTATION</h2>
            <p>' . ($settings['company_address'] ?? '') . '</p>
            <p>Tel: ' . ($settings['company_phone'] ?? '') . ' | Email: ' . ($settings['company_email'] ?? '') . '</p>
            <p>Reg: ' . ($settings['company_reg_number'] ?? '') . '</p>
        </div>
        
        <div class="info">
            <div class="info-box">
                <h4>BILL TO</h4>
                <p><strong>' . htmlspecialchars($client['company_name']) . '</strong></p>
                <p>' . htmlspecialchars($client['contact_person'] ?? '') . '</p>
                <p>' . htmlspecialchars($client['email']) . '</p>
                <p>' . htmlspecialchars($client['phone'] ?? '') . '</p>
                <p>' . htmlspecialchars($client['address'] ?? '') . '</p>
                <p>' . htmlspecialchars($client['city'] ?? '') . ' ' . htmlspecialchars($client['province'] ?? '') . '</p>
            </div>
            <div class="info-box" style="text-align: right;">
                <h4>QUOTE DETAILS</h4>
                <p><strong>Quote No:</strong> ' . $quote['quote_number'] . '</p>
                <p><strong>Date:</strong> ' . date('d/m/Y', strtotime($quote['quote_date'])) . '</p>
                <p><strong>Valid Until:</strong> ' . date('d/m/Y', strtotime($quote['expiry_date'])) . '</p>
                <p><strong>Status:</strong> ' . ucfirst($quote['status']) . '</p>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th style="width:5%;">#</th>
                    <th style="width:25%;">Item</th>
                    <th style="width:30%;">Description</th>
                    <th style="width:10%;text-align:center;">Qty</th>
                    <th style="width:15%;text-align:right;">Unit Price</th>
                    <th style="width:15%;text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>';
    
    $counter = 1;
    foreach ($items as $item) {
        $html .= '
                <tr>
                    <td>' . $counter++ . '</td>
                    <td>' . htmlspecialchars($item['item_name']) . '</td>
                    <td>' . htmlspecialchars($item['description']) . '</td>
                    <td style="text-align:center;">' . $item['quantity'] . '</td>
                    <td style="text-align:right;">R ' . number_format($item['unit_price'], 2) . '</td>
                    <td style="text-align:right;">R ' . number_format($item['quantity'] * $item['unit_price'], 2) . '</td>
                </tr>';
    }
    
    $html .= '
            </tbody>
        </table>
        
        <div class="total-section">
            <p><strong>Subtotal:</strong> R ' . number_format($quote['total_amount'], 2) . '</p>
            <p class="grand-total"><strong>Grand Total:</strong> R ' . number_format($quote['grand_total'], 2) . '</p>
        </div>
        
        <div class="terms">
            <h4>Terms & Conditions</h4>
            <p>1. This quotation is valid for 14 days from the date of issue.</p>
            <p>2. Payment terms: 50% deposit required to commence work.</p>
            <p>3. All prices are in South African Rand (ZAR).</p>
            <p>4. Delivery timeline will be confirmed upon order confirmation.</p>
            <p>5. All goods remain the property of ' . ($settings['company_name'] ?? 'Marlani Technologies') . ' until full payment is received.</p>
        </div>
        
        <div class="footer">
            <p>Generated automatically upon client registration.</p>
            <p>Thank you for choosing ' . ($settings['company_name'] ?? 'Marlani Technologies') . '</p>
            <p>' . date('Y') . ' &copy; ' . ($settings['company_name'] ?? 'Marlani Technologies') . ' - All Rights Reserved</p>
        </div>
    </body>
    </html>';
    
    return $html;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Client - Marlani</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Nunito', sans-serif;
            background: #f8f9fc;
            min-height: 100vh;
            display: flex;
        }
        
        .sidebar {
            width: 14rem;
            background: #1e3c72;
            color: #fff;
            padding: 1.5rem 1rem;
            height: 100vh;
            position: sticky;
            top: 0;
            flex-shrink: 0;
            overflow-y: auto;
        }
        .sidebar-brand { 
            font-size: 1.5rem; 
            font-weight: 800; 
            padding-bottom: 1rem; 
            border-bottom: 1px solid rgba(255,255,255,0.1); 
            margin-bottom: 1.5rem; 
        }
        .sidebar-brand i { margin-right: 0.5rem; }
        .sidebar-nav { list-style: none; padding: 0; }
        .sidebar-nav li { margin-bottom: 0.3rem; }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 0.7rem 1rem;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }
        .sidebar-nav a:hover { background: rgba(255,255,255,0.1); color: #fff; }
        .sidebar-nav a i { width: 1.5rem; margin-right: 0.5rem; }
        .sidebar-nav a.active { background: rgba(255,255,255,0.1); color: #fff; }
        
        .main { flex: 1; padding: 2rem; }
        .topbar {
            background: #fff;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            border-radius: 0.75rem;
        }
        .topbar .user { display: flex; align-items: center; gap: 1rem; }
        .topbar .user img { width: 40px; height: 40px; border-radius: 50%; }
        
        .card {
            background: #fff;
            border-radius: 0.75rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 2rem;
            max-width: 900px;
            margin: 0 auto;
        }
        .card h2 { margin-bottom: 1.5rem; color: #1e3c72; }
        
        .form-group { margin-bottom: 1rem; }
        .form-group label { 
            display: block; 
            font-weight: 600; 
            margin-bottom: 0.3rem; 
            color: #5a5c69; 
            font-size: 0.9rem; 
        }
        .form-group .required { color: #e74a3b; }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d3e2;
            border-radius: 0.35rem;
            font-family: 'Nunito', sans-serif;
            transition: border-color 0.15s ease;
            font-size: 0.95rem;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.35rem;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Nunito', sans-serif;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary { background: #4e73df; color: #fff; }
        .btn-primary:hover { background: #2e59d9; }
        .btn-secondary { background: #858796; color: #fff; }
        .btn-secondary:hover { background: #6b6d7d; }
        .btn-success { background: #1cc88a; color: #fff; }
        .btn-success:hover { background: #17a673; }
        
        .error-messages {
            background: #fee2e2;
            color: #dc2626;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .error-messages ul { padding-left: 1.5rem; }
        
        .quote-option {
            margin: 1rem 0;
            padding: 1rem;
            background: #f0f4ff;
            border-radius: 0.5rem;
            border-left: 4px solid #4e73df;
        }
        .quote-option label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            font-weight: 600;
        }
        .quote-option input[type="checkbox"] {
            width: 1.2rem;
            height: 1.2rem;
            cursor: pointer;
        }
        .quote-option p {
            font-size: 0.85rem;
            color: #858796;
            margin-top: 0.3rem;
            margin-left: 2rem;
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 4rem; padding: 1rem 0.5rem; }
            .sidebar-brand span, .sidebar-nav a span { display: none; }
            .sidebar-nav a { justify-content: center; }
            .sidebar-nav a i { margin-right: 0; }
            .form-row { grid-template-columns: 1fr; }
            .topbar { flex-direction: column; gap: 0.5rem; }
            .card { padding: 1rem; }
            .main { padding: 1rem; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-building"></i>
            <span>Marlani</span>
        </div>
        <ul class="sidebar-nav">
            <li><a href="index.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
            <li><a href="clients.php"><i class="fas fa-users"></i><span>Clients</span></a></li>
            <li><a href="clients.php" class="active"><i class="fas fa-user-plus"></i><span>Add Client</span></a></li>
            <li><a href="#"><i class="fas fa-file-invoice"></i><span>Quotes</span></a></li>
            <li><a href="#"><i class="fas fa-cog"></i><span>Settings</span></a></li>
            <li style="margin-top:auto;border-top:1px solid rgba(255,255,255,0.1);padding-top:1rem;">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="main">
        <div class="topbar">
            <h4><i class="fas fa-user-plus"></i> Add New Client</h4>
            <div class="user">
                <span>Welcome, <?php echo htmlspecialchars($first_name); ?></span>
                <img src="img/undraw_profile.svg" alt="Profile">
            </div>
        </div>
        
        <div class="card">
            <h2>Client Information</h2>
            
            <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <ul>
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <!-- Company Name -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Company Name <span class="required">*</span></label>
                        <input type="text" name="company_name" required value="<?php echo $_POST['company_name'] ?? ''; ?>" placeholder="Enter company name">
                    </div>
                    <div class="form-group">
                        <label>Contact Person</label>
                        <input type="text" name="contact_person" value="<?php echo $_POST['contact_person'] ?? ''; ?>" placeholder="Enter contact person name">
                    </div>
                </div>
                
                <!-- Email & Phone -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Email <span class="required">*</span></label>
                        <input type="email" name="email" required value="<?php echo $_POST['email'] ?? ''; ?>" placeholder="Enter email address">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" value="<?php echo $_POST['phone'] ?? ''; ?>" placeholder="Enter phone number">
                    </div>
                </div>
                
                <!-- Address -->
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" rows="2" placeholder="Enter street address"><?php echo $_POST['address'] ?? ''; ?></textarea>
                </div>
                
                <!-- City, Province, Postal Code -->
                <div class="form-row">
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="city" value="<?php echo $_POST['city'] ?? ''; ?>" placeholder="Enter city">
                    </div>
                    <div class="form-group">
                        <label>Province</label>
                        <input type="text" name="province" value="<?php echo $_POST['province'] ?? ''; ?>" placeholder="Enter province">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Postal Code</label>
                        <input type="text" name="postal_code" value="<?php echo $_POST['postal_code'] ?? ''; ?>" placeholder="Enter postal code">
                    </div>
                    <div class="form-group">
                        <label>Country</label>
                        <input type="text" name="country" value="<?php echo $_POST['country'] ?? 'South Africa'; ?>" placeholder="Enter country">
                    </div>
                </div>
                
                <!-- Industry & Client Type -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Industry</label>
                        <input type="text" name="industry" value="<?php echo $_POST['industry'] ?? ''; ?>" placeholder="e.g. Technology, Finance, Healthcare">
                    </div>
                    <div class="form-group">
                        <label>Client Type</label>
                        <select name="client_type">
                            <option value="business" <?php echo (isset($_POST['client_type']) && $_POST['client_type'] == 'business') ? 'selected' : ''; ?>>Business</option>
                            <option value="individual" <?php echo (isset($_POST['client_type']) && $_POST['client_type'] == 'individual') ? 'selected' : ''; ?>>Individual</option>
                            <option value="government" <?php echo (isset($_POST['client_type']) && $_POST['client_type'] == 'government') ? 'selected' : ''; ?>>Government</option>
                            <option value="non-profit" <?php echo (isset($_POST['client_type']) && $_POST['client_type'] == 'non-profit') ? 'selected' : ''; ?>>Non-Profit</option>
                        </select>
                    </div>
                </div>
                
                <!-- Status & Notes -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="pending" <?php echo (isset($_POST['status']) && $_POST['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            <option value="suspended" <?php echo (isset($_POST['status']) && $_POST['status'] == 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" rows="2" placeholder="Additional notes about this client"><?php echo $_POST['notes'] ?? ''; ?></textarea>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div style="display:flex; gap:1rem; margin-top:1.5rem; padding-top:1rem; border-top:1px solid #e3e6f0;">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Save Client
                    </button>
                    <a href="clients.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>