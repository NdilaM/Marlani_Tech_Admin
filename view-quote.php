<?php
// view-quote.php - View a Single Quote with One-Time and Monthly Fees
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.html');
    exit();
}

require_once 'db.php';

$quote_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($quote_id == 0) {
    header('Location: clients.php');
    exit();
}

// Get quote info
$stmt = $conn->prepare("SELECT * FROM quotations WHERE id = ?");
$stmt->execute([$quote_id]);
$quote = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quote) {
    header('Location: clients.php');
    exit();
}

// Get client info
$stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$quote['client_id']]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

// Get quote items
$stmt = $conn->prepare("SELECT * FROM quotation_items WHERE quotation_id = ?");
$stmt->execute([$quote_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get company settings
$stmt = $conn->query("SELECT * FROM company_settings");
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$first_name = $_SESSION['first_name'] ?? 'User';

// ============================================
// SEPARATE ONE-TIME AND MONTHLY ITEMS
// ============================================
$one_time_items = [];
$subscription_items = [];
$one_time_total = 0;
$monthly_total = 0;

foreach ($items as $item) {
    // Check if item is a subscription (contains "months" in description or has monthly fee)
    $is_subscription = false;
    $monthly_fee = 0;
    $months = 0;
    
    // Check if description contains "months" (which means it's a subscription)
    if (strpos($item['description'], 'months') !== false) {
        // Extract monthly fee from description
        preg_match('/R([\d,]+\.\d{2}) x (\d+) months/', $item['description'], $matches);
        if (count($matches) >= 3) {
            $monthly_fee = floatval(str_replace(',', '', $matches[1]));
            $months = intval($matches[2]);
            $is_subscription = true;
        }
    }
    
    if ($is_subscription) {
        $subscription_items[] = [
            'item' => $item,
            'monthly_fee' => $monthly_fee,
            'months' => $months
        ];
        $monthly_total += $monthly_fee * $item['quantity'];
    } else {
        $one_time_items[] = $item;
        $one_time_total += $item['total'];
    }
}

// Logo path
$logo_path = 'img/company_logo.png';
$logo_html = '';
if (file_exists($logo_path)) {
    $logo_html = '<img src="' . $logo_path . '" alt="Company Logo" style="height:60px; width:auto; max-height:60px;">';
} else {
    $logo_html = '<h1 style="margin:0; color:#1e3c72; font-size:28px;">' . ($settings['company_name'] ?? 'Marlani Technologies') . '</h1>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Marlani Admin - View Quote</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

<style>
/* ===== ALL CSS FROM VIEW-QUOTE.PHP ===== */
:root {
  --blue: #002a66;
  --indigo: #6610f2;
  --purple: #6f42c1;
  --pink: #e83e8c;
  --red: #e74a3b;
  --orange: #fd7e14;
  --yellow: #f6c23e;
  --green: #1cc88a;
  --teal: #20c9a6;
  --cyan: #36b9cc;
  --white: #fff;
  --gray: #858796;
  --gray-dark: #5a5c69;
  --primary: #4e73df;
  --secondary: #858796;
  --success: #1cc88a;
  --info: #36b9cc;
  --warning: #f6c23e;
  --danger: #e74a3b;
  --light: #f8f9fc;
  --dark: #5a5c69;
  --breakpoint-xs: 0;
  --breakpoint-sm: 576px;
  --breakpoint-md: 768px;
  --breakpoint-lg: 992px;
  --breakpoint-xl: 1200px;
  --font-family-sans-serif: "Nunito", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
  --font-family-monospace: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
}

*, *::before, *::after {
  box-sizing: border-box;
}

html {
  font-family: sans-serif;
  line-height: 1.15;
  -webkit-text-size-adjust: 100%;
  -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
}

body {
  margin: 0;
  font-family: "Nunito", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
  font-size: 1rem;
  font-weight: 400;
  line-height: 1.5;
  color: #858796;
  text-align: left;
  background-color: #fff;
}

/* ===== SB ADMIN 2 CUSTOM STYLES ===== */
/* (Include all your existing CSS styles here - same as before) */
/* I'm including just the essential custom styles to keep it concise */

.quote-container {
  background: #fff;
  border-radius: 0.75rem;
  padding: 2rem;
}

.quote-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 3px solid #1e3c72;
  padding-bottom: 1.5rem;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1rem;
}
.header-left { display: flex; align-items: center; gap: 1rem; }
.header-left .logo { height: 60px; width: auto; max-height: 60px; }
.header-left .company-name { font-size: 1.8rem; font-weight: 800; color: #1e3c72; }
.header-left .company-name span { color: #4e73df; }
.header-left .tagline { font-size: 0.7rem; color: #888; letter-spacing: 2px; text-transform: uppercase; }
.header-right { text-align: right; }
.header-right .quote-badge { background: #4e73df; color: #fff; padding: 0.5rem 1.5rem; border-radius: 50px; font-size: 0.9rem; font-weight: 700; letter-spacing: 2px; display: inline-block; }
.header-right .company-details { font-size: 0.75rem; color: #888; margin-top: 0.5rem; line-height: 1.4; }

.status-badge { display: inline-block; padding: 0.5rem 1.5rem; border-radius: 999px; font-weight: 700; font-size: 0.9rem; }
.status-draft { background: #fef3c7; color: #d97706; }
.status-sent { background: #dbeafe; color: #2563eb; }
.status-accepted { background: #dcfce7; color: #16a34a; }
.status-rejected { background: #fee2e2; color: #dc2626; }

.info-bar {
  background: #f8f9fc;
  padding: 0.75rem 1.5rem;
  border-radius: 0.5rem;
  margin-bottom: 1.5rem;
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.5rem;
}
.info-bar .item { font-size: 0.85rem; color: #555; }
.info-bar .item strong { color: #1e3c72; }

.billing-section { display: flex; justify-content: space-between; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
.billing-box { flex: 1; min-width: 200px; }
.billing-box h4 { color: #4e73df; border-bottom: 2px solid #e3e6f0; padding-bottom: 0.5rem; margin-bottom: 0.75rem; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
.billing-box .client-name { font-size: 1.2rem; font-weight: 700; color: #1e3c72; }
.billing-box p { margin: 0.25rem 0; font-size: 0.9rem; color: #555; }
.billing-box.text-right { text-align: right; }

table { width: 100%; border-collapse: collapse; margin: 1.5rem 0; }
th { background: #1e3c72; color: #fff; padding: 0.75rem; text-align: left; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; }
td { padding: 0.75rem; border-bottom: 1px solid #e3e6f0; font-size: 0.9rem; }
.text-right { text-align: right; }
.text-center { text-align: center; }
.item-name { font-weight: 600; color: #1e3c72; }
.item-desc { font-size: 0.8rem; color: #888; }

.totals-section { 
  text-align: right; 
  margin-top: 1.5rem; 
  padding-top: 1.5rem; 
  border-top: 2px solid #e3e6f0; 
}
.totals-section p { margin: 0.25rem 0; font-size: 1rem; }
.grand-total { 
  font-size: 1.3rem; 
  color: #1e3c72; 
  font-weight: 800; 
}
.subscription-total { 
  font-size: 1.1rem; 
  color: #36b9cc; 
  font-weight: 700; 
}
.one-time-total { 
  font-size: 1.1rem; 
  color: #4e73df; 
  font-weight: 700; 
}

.section-title {
  font-size: 1rem;
  font-weight: 700;
  margin: 1.5rem 0 0.5rem 0;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #e3e6f0;
}
.section-title.one-time { color: #4e73df; border-color: #4e73df; }
.section-title.subscription { color: #36b9cc; border-color: #36b9cc; }

.terms { margin-top: 2rem; padding: 1.25rem 1.5rem; background: #f8f9fc; border-radius: 0.5rem; border-left: 4px solid #4e73df; }
.terms h4 { color: #1e3c72; margin-bottom: 0.5rem; font-size: 0.95rem; }
.terms ul { list-style: none; padding: 0; margin: 0; }
.terms ul li { padding: 0.25rem 0; padding-left: 1.5rem; position: relative; font-size: 0.85rem; color: #666; }
.terms ul li:before { content: "✓"; color: #4e73df; position: absolute; left: 0; font-weight: 700; }

.footer { margin-top: 2rem; text-align: center; border-top: 1px solid #e3e6f0; padding-top: 1.5rem; color: #999; font-size: 0.8rem; }
.footer .brand { color: #1e3c72; font-weight: 600; }

@media print { .sidebar, .topbar, .action-bar, .no-print { display: none !important; } }

.btn-warning {
  color: #fff;
  background-color: #f6c23e;
  border-color: #f6c23e;
}
.btn-warning:hover {
  color: #fff;
  background-color: #dda20a;
  border-color: #d39a0a;
}
.btn-info {
  color: #fff;
  background-color: #36b9cc;
  border-color: #36b9cc;
}
.btn-info:hover {
  color: #fff;
  background-color: #2c9faf;
  border-color: #2a96a5;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

/* Sidebar styles */
#wrapper { display: flex; min-height: 100vh; }
#sidebar-container { display: flex; flex-shrink: 0; height: 100vh; position: sticky; top: 0; }
.sidebar {
  width: 6.5rem;
  min-height: 100vh;
  height: 100vh;
  position: sticky;
  top: 0;
  flex-shrink: 0;
  z-index: 100;
  overflow-y: auto;
  overflow-x: hidden;
  transition: width 0.3s ease;
  box-shadow: 2px 0 15px rgba(0, 0, 0, 0.15);
  display: flex;
  flex-direction: column;
  background: #1e3c72;
  color: #fff;
  padding: 1.5rem 1rem;
}
.sidebar-brand { font-size: 1.5rem; font-weight: 800; padding-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 1.5rem; }
.sidebar-brand i { margin-right: 0.5rem; }
.sidebar-nav { list-style: none; padding: 0; flex: 1; }
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
.sidebar-nav a.active { background: rgba(255,255,255,0.15); color: #fff; font-weight: 600; }

@media (min-width: 768px) { .sidebar { width: 14rem !important; } }

@media (max-width: 768px) {
  .sidebar { width: 4rem !important; padding: 1rem 0.5rem; }
  .sidebar-brand span, .sidebar-nav a span { display: none; }
  .sidebar-nav a { justify-content: center; padding: 0.7rem 0.5rem; }
  .sidebar-nav a i { margin-right: 0; font-size: 1.2rem; }
  #sidebar-container { position: fixed; top: 0; left: 0; height: 100vh; z-index: 1050; width: 0; overflow: hidden; transition: width 0.3s ease; }
  #sidebar-container.toggled { width: 14rem; }
  .sidebar-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1040; display: none; }
  .sidebar-overlay.active { display: block; }
}

/* Topbar */
.topbar {
  height: 4.375rem;
  background: #fff;
  padding: 0 1.5rem;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  display: flex;
  justify-content: space-between;
  align-items: center;
}
#content-wrapper { flex: 1; }
#content { flex: 1; }
.container-fluid { padding: 1.5rem; }
</style>
</head>
<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <div id="sidebar-container"></div>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <!-- Topbar Search -->
                    <form class="form-inline mr-auto ml-3 navbar-search">
                        <div class="input-group">
                            <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..." aria-label="Search">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="button">
                                    <i class="fas fa-search fa-sm"></i>
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">
                        <!-- Nav Item - Alerts -->
                        <li class="nav-item dropdown no-arrow mx-1">
                            <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-bell fa-fw"></i>
                                <span class="badge badge-danger badge-counter">3+</span>
                            </a>
                            <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="alertsDropdown">
                                <h6 class="dropdown-header">Alerts Center</h6>
                                <a class="dropdown-item d-flex align-items-center" href="#">
                                    <div class="mr-3">
                                        <div class="icon-circle bg-primary">
                                            <i class="fas fa-file-alt text-white"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="small text-gray-500">December 12, 2019</div>
                                        <span class="font-weight-bold">A new monthly report is ready to download!</span>
                                    </div>
                                </a>
                                <a class="dropdown-item d-flex align-items-center" href="#">
                                    <div class="mr-3">
                                        <div class="icon-circle bg-success">
                                            <i class="fas fa-donate text-white"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="small text-gray-500">December 7, 2019</div>
                                        $290.29 has been deposited into your account!
                                    </div>
                                </a>
                                <a class="dropdown-item d-flex align-items-center" href="#">
                                    <div class="mr-3">
                                        <div class="icon-circle bg-warning">
                                            <i class="fas fa-exclamation-triangle text-white"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="small text-gray-500">December 2, 2019</div>
                                        Spending Alert: We've noticed unusually high spending for your account.
                                    </div>
                                </a>
                                <a class="dropdown-item text-center small text-gray-500" href="#">Show All Alerts</a>
                            </div>
                        </li>

                        <!-- Nav Item - Messages -->
                        <li class="nav-item dropdown no-arrow mx-1">
                            <a class="nav-link dropdown-toggle" href="#" id="messagesDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-envelope fa-fw"></i>
                                <span class="badge badge-danger badge-counter">7</span>
                            </a>
                            <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="messagesDropdown">
                                <h6 class="dropdown-header">Message Center</h6>
                                <a class="dropdown-item d-flex align-items-center" href="#">
                                    <div class="dropdown-list-image mr-3">
                                        <img class="rounded-circle" src="img/undraw_profile_1.svg" alt="...">
                                        <div class="status-indicator bg-success"></div>
                                    </div>
                                    <div class="font-weight-bold">
                                        <div class="text-truncate">Hi there! I am wondering if you can help me with a problem I've been having.</div>
                                        <div class="small text-gray-500">Emily Fowler · 58m</div>
                                    </div>
                                </a>
                                <a class="dropdown-item d-flex align-items-center" href="#">
                                    <div class="dropdown-list-image mr-3">
                                        <img class="rounded-circle" src="img/undraw_profile_2.svg" alt="...">
                                        <div class="status-indicator"></div>
                                    </div>
                                    <div>
                                        <div class="text-truncate">I have the photos that you ordered last month, how would you like them sent to you?</div>
                                        <div class="small text-gray-500">Jae Chun · 1d</div>
                                    </div>
                                </a>
                                <a class="dropdown-item d-flex align-items-center" href="#">
                                    <div class="dropdown-list-image mr-3">
                                        <img class="rounded-circle" src="img/undraw_profile_3.svg" alt="...">
                                        <div class="status-indicator bg-warning"></div>
                                    </div>
                                    <div>
                                        <div class="text-truncate">Last month's report looks great, I am very happy with the progress so far, keep up the good work!</div>
                                        <div class="small text-gray-500">Morgan Alvarez · 2d</div>
                                    </div>
                                </a>
                                <a class="dropdown-item text-center small text-gray-500" href="#">Read More Messages</a>
                            </div>
                        </li>

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?php echo htmlspecialchars($first_name); ?>
                                </span>
                                <img class="img-profile rounded-circle" src="img/undraw_profile.svg">
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Profile
                                </a>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Settings
                                </a>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-list fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Activity Log
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="logout.php">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-file-invoice"></i> Quote Details
                        </h1>
                        <div>
                            <a href="client-quote.php?id=<?php echo $quote['client_id']; ?>" class="btn btn-sm btn-secondary shadow-sm">
                                <i class="fas fa-arrow-left"></i> Back to Quotes
                            </a>
                            <?php if ($quote['pdf_path']): ?>
                            <a href="<?php echo htmlspecialchars($quote['pdf_path']); ?>" target="_blank" class="btn btn-sm btn-success shadow-sm">
                                <i class="fas fa-file-pdf"></i> Download PDF
                            </a>
                            <?php endif; ?>
                            <button onclick="window.print()" class="btn btn-sm btn-primary shadow-sm">
                                <i class="fas fa-print"></i> Print
                            </button>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow mb-4">
                                <div class="card-body">
                                    <div class="quote-container">
                                        <!-- Quote Header with Logo -->
                                        <div class="quote-header">
                                            <div class="header-left">
                                                <?php echo $logo_html; ?>
                                                <div>
                                                    <div class="company-name"><?php echo ($settings['company_name'] ?? 'Marlani'); ?><span>.</span></div>
                                                    <div class="tagline">Innovate · Integrate · Elevate</div>
                                                </div>
                                            </div>
                                            <div class="header-right">
                                                <div class="quote-badge">QUOTATION</div>
                                                <div class="company-details">
                                                    <?php echo ($settings['company_address'] ?? ''); ?><br>
                                                    Tel: <?php echo ($settings['company_phone'] ?? ''); ?> | Email: <?php echo ($settings['company_email'] ?? ''); ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Info Bar -->
                                        <div class="info-bar">
                                            <span class="item"><strong>Reg:</strong> <?php echo ($settings['company_reg_number'] ?? 'N/A'); ?></span>
                                            <span class="item"><strong>Quote:</strong> <?php echo htmlspecialchars($quote['quote_number']); ?></span>
                                            <span class="item"><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($quote['quote_date'])); ?></span>
                                            <span class="item"><strong>Valid:</strong> <?php echo date('d/m/Y', strtotime($quote['expiry_date'])); ?></span>
                                            <span class="item">
                                                <span class="status-badge status-<?php echo $quote['status']; ?>">
                                                    <?php echo strtoupper($quote['status']); ?>
                                                </span>
                                            </span>
                                        </div>

                                        <!-- Billing -->
                                        <div class="billing-section">
                                            <div class="billing-box">
                                                <h4><i class="fas fa-user"></i> Bill To</h4>
                                                <div class="client-name"><?php echo htmlspecialchars($client['company_name']); ?></div>
                                                <p><?php echo htmlspecialchars($client['contact_person'] ?? ''); ?></p>
                                                <p><?php echo htmlspecialchars($client['email']); ?></p>
                                                <p><?php echo htmlspecialchars($client['phone'] ?? ''); ?></p>
                                                <p><?php echo htmlspecialchars($client['address'] ?? ''); ?></p>
                                            </div>
                                            <div class="billing-box text-right">
                                                <h4><i class="fas fa-file-alt"></i> Quote Details</h4>
                                                <p><strong>Quote Number:</strong> <?php echo htmlspecialchars($quote['quote_number']); ?></p>
                                                <p><strong>Issue Date:</strong> <?php echo date('d/m/Y', strtotime($quote['quote_date'])); ?></p>
                                                <p><strong>Valid Until:</strong> <?php echo date('d/m/Y', strtotime($quote['expiry_date'])); ?></p>
                                                <p><strong>Status:</strong> <?php echo ucfirst($quote['status']); ?></p>
                                            </div>
                                        </div>

                                        <!-- One-Time Services Table -->
                                        <?php if (!empty($one_time_items)): ?>
                                        <div class="section-title one-time">
                                            <i class="fas fa-cube"></i> One-Time Services
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
                                            <tbody>
                                                <?php $counter = 1; foreach ($one_time_items as $item): ?>
                                                <tr>
                                                    <td><?php echo $counter++; ?></td>
                                                    <td><div class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></div></td>
                                                    <td><div class="item-desc"><?php echo htmlspecialchars($item['description']); ?></div></td>
                                                    <td class="text-center"><?php echo $item['quantity']; ?></td>
                                                    <td class="text-right">R <?php echo number_format($item['unit_price'], 2); ?></td>
                                                    <td class="text-right">R <?php echo number_format($item['total'], 2); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        <?php endif; ?>

                                        <!-- Monthly Subscription/Maintenance Services Table -->
                                        <?php if (!empty($subscription_items)): ?>
                                        <div class="section-title subscription">
                                            <i class="fas fa-clock"></i> Monthly Subscription / Maintenance
                                        </div>
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th style="width:5%;">#</th>
                                                    <th style="width:25%;">Item</th>
                                                    <th style="width:30%;">Description</th>
                                                    <th style="width:10%;text-align:center;">Qty</th>
                                                    <th style="width:15%;text-align:right;">Monthly Fee</th>
                                                    <th style="width:15%;text-align:right;">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $counter = 1; foreach ($subscription_items as $sub_item): ?>
                                                <tr>
                                                    <td><?php echo $counter++; ?></td>
                                                    <td><div class="item-name"><?php echo htmlspecialchars($sub_item['item']['item_name']); ?></div></td>
                                                    <td><div class="item-desc"><?php echo htmlspecialchars($sub_item['item']['description']); ?></div></td>
                                                    <td class="text-center"><?php echo $sub_item['item']['quantity']; ?></td>
                                                    <td class="text-right">R <?php echo number_format($sub_item['monthly_fee'], 2); ?></td>
                                                    <td class="text-right">R <?php echo number_format($sub_item['item']['total'], 2); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        <?php endif; ?>

                                        <!-- Totals -->
                                        <div class="totals-section">
                                            <?php if ($one_time_total > 0): ?>
                                            <p class="one-time-total"><strong>One-Time Total:</strong> R <?php echo number_format($one_time_total, 2); ?></p>
                                            <?php endif; ?>
                                            <?php if ($monthly_total > 0): ?>
                                            <p class="subscription-total"><strong>Monthly Total:</strong> R <?php echo number_format($monthly_total, 2); ?></p>
                                            <p><small class="text-muted"><i class="fas fa-info-circle"></i> This is the total monthly recurring fee</small></p>
                                            <?php endif; ?>
                                            <p class="grand-total"><strong>Grand Total:</strong> R <?php echo number_format($quote['grand_total'], 2); ?></p>
                                        </div>

                                        <!-- Terms -->
                                        <div class="terms">
                                            <h4><i class="fas fa-check-circle"></i> Terms &amp; Conditions</h4>
                                            <ul>
                                                <li>This quotation is valid for 14 days from the date of issue.</li>
                                                <li>One-time services are invoiced upfront.</li>
                                                <li>Monthly services will be invoiced on a recurring monthly basis.</li>
                                                <li>Payment terms: 50% deposit required to commence work.</li>
                                                <li>All prices are in South African Rand (ZAR).</li>
                                                <li>Delivery timeline will be confirmed upon order confirmation.</li>
                                                <li>All goods remain the property of <?php echo htmlspecialchars($settings['company_name'] ?? 'Marlani Technologies'); ?> until full payment is received.</li>
                                            </ul>
                                        </div>

                                        <!-- Footer -->
                                        <div class="footer">
                                            <p>Generated automatically upon client registration</p>
                                            <p>Thank you for choosing <span class="brand"><?php echo htmlspecialchars($settings['company_name'] ?? 'Marlani Technologies'); ?></span></p>
                                            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['company_name'] ?? 'Marlani Technologies'); ?> - All Rights Reserved</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End of container-fluid -->

                <!-- Footer -->
                <footer class="sticky-footer bg-blue">
                    <div class="copyright text-center">
                        <span>Copyright &copy; Marlani Technologies 2026</span>
                    </div>
                </footer>
            </div>
            <!-- End of Main Content -->
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

    <script>
    $(function() {
        $("#sidebar-container").load("sidebar.html", function() {
            console.log("Sidebar loaded successfully");
            
            $('#sidebarToggleTop').on('click', function() {
                $('#sidebar-container').toggleClass('toggled');
                if ($('#sidebar-container').hasClass('toggled')) {
                    $('body').append('<div class="sidebar-overlay active"></div>');
                } else {
                    $('.sidebar-overlay').remove();
                }
            });
            
            $(document).on('click', '.sidebar-overlay', function() {
                $('#sidebar-container').removeClass('toggled');
                $('.sidebar-overlay').remove();
            });
        });
    });
    </script>
</body>
</html>