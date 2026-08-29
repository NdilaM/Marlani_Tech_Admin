<?php
// edit-quote.php - Edit an Existing Quote
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../login.html');
    exit();
}

require_once '../../db.php';

$quote_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($quote_id == 0) {
    header('Location: ../clients.php');
    exit();
}

// Get quote info
$stmt = $conn->prepare("SELECT * FROM quotations WHERE id = ?");
$stmt->execute([$quote_id]);
$quote = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quote) {
    header('Location: ../clients.php');
    exit();
}

// Get client info
$stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$quote['client_id']]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    header('Location: ../clients.php');
    exit();
}

// Get quote items
$stmt = $conn->prepare("SELECT * FROM quotation_items WHERE quotation_id = ?");
$stmt->execute([$quote_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get services for dropdown
$stmt = $conn->query("SELECT * FROM services WHERE is_active = 1 ORDER BY category, service_name");
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group services by category
$categories = [];
foreach ($services as $service) {
    $cat = $service['category'] ?? 'Uncategorized';
    if (!isset($categories[$cat])) {
        $categories[$cat] = [];
    }
    $categories[$cat][] = $service;
}

$first_name = $_SESSION['first_name'] ?? 'User';
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quote_number = trim($_POST['quote_number'] ?? '');
    $quote_date = $_POST['quote_date'] ?? date('Y-m-d');
    $expiry_date = $_POST['expiry_date'] ?? date('Y-m-d', strtotime('+14 days'));
    $status = $_POST['status'] ?? 'draft';
    $notes = trim($_POST['notes'] ?? '');
    
    // Validate
    if (empty($quote_number)) $errors[] = "Quote number is required";
    if (empty($quote_date)) $errors[] = "Quote date is required";
    if (empty($expiry_date)) $errors[] = "Expiry date is required";
    
    // Handle items
    $item_ids = $_POST['item_ids'] ?? [];
    $item_names = $_POST['item_names'] ?? [];
    $item_descriptions = $_POST['item_descriptions'] ?? [];
    $item_quantities = $_POST['item_quantities'] ?? [];
    $item_unit_prices = $_POST['item_unit_prices'] ?? [];
    
    if (empty($errors)) {
        try {
            // Start transaction
            $conn->beginTransaction();
            
            // Update quote header
            $stmt = $conn->prepare("
                UPDATE quotations SET 
                    quote_number = ?,
                    quote_date = ?,
                    expiry_date = ?,
                    status = ?,
                    notes = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $quote_number,
                $quote_date,
                $expiry_date,
                $status,
                $notes,
                $quote_id
            ]);
            
            // Delete existing items
            $stmt = $conn->prepare("DELETE FROM quotation_items WHERE quotation_id = ?");
            $stmt->execute([$quote_id]);
            
            // Insert updated items
            $subtotal = 0;
            for ($i = 0; $i < count($item_ids); $i++) {
                if (isset($item_names[$i]) && !empty($item_names[$i])) {
                    $qty = isset($item_quantities[$i]) ? (int)$item_quantities[$i] : 1;
                    $unit_price = isset($item_unit_prices[$i]) ? (float)$item_unit_prices[$i] : 0;
                    $total = $qty * $unit_price;
                    $subtotal += $total;
                    
                    $stmt = $conn->prepare("
                        INSERT INTO quotation_items (quotation_id, item_name, description, quantity, unit_price, total)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $quote_id,
                        trim($item_names[$i]),
                        trim($item_descriptions[$i] ?? ''),
                        $qty,
                        $unit_price,
                        $total
                    ]);
                }
            }
            
            // Update totals
            $grand_total = $subtotal;
            $stmt = $conn->prepare("
                UPDATE quotations SET 
                    total_amount = ?,
                    grand_total = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$subtotal, $grand_total, $quote_id]);
            
            // Commit transaction
            $conn->commit();
            
            $_SESSION['success'] = "Quote updated successfully!";
            header('Location: view-quote.php?id=' . $quote_id);
            exit();
            
        } catch(Exception $e) {
            $conn->rollBack();
            $errors[] = "Error: " . $e->getMessage();
        }
    }
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
    <title>Marlani Admin - Edit Quote</title>

    <!-- Custom fonts for this template-->
    <link href="../../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="../../css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        /* ===== SIDEBAR LOADING FIXES - ORIGINAL STYLE ===== */
        #sidebar-container {
            min-height: 100vh;
            position: sticky;
            top: 0;
            align-self: flex-start;
        }

        #sidebar-container .sidebar {
            min-height: 100vh;
        }

        /* Remove white background from subtabs - original style */
        #sidebar-container .sidebar .nav-item .collapse .collapse-inner {
            background: transparent !important;
        }

        #sidebar-container .sidebar .nav-item .collapse .collapse-inner .collapse-item {
            color: rgba(255,255,255,0.7) !important;
        }

        #sidebar-container .sidebar .nav-item .collapse .collapse-inner .collapse-item:hover {
            background: rgba(255,255,255,0.08) !important;
            color: #fff !important;
        }

        #sidebar-container .sidebar .nav-item .collapse .collapse-inner .collapse-item.active {
            background: rgba(255,255,255,0.15) !important;
            color: #fff !important;
        }

        #sidebar-container .sidebar .nav-item .collapse .collapse-inner .collapse-header {
            color: rgba(255,255,255,0.4) !important;
            border-bottom: 1px solid rgba(255,255,255,0.08) !important;
        }

        /* ===== SIDEBAR OVERLAY FIX ===== */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1040;
            display: none;
        }

        .sidebar-overlay.active {
            display: block;
        }

        /* ===== CUSTOM STYLES ===== */
        .error-messages {
            background: #fee2e2;
            color: #dc2626;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .error-messages ul { padding-left: 1.5rem; }

        .form-group .required { color: #e74a3b; }

        .item-row {
            background: #f8f9fc;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
            border: 1px solid #e3e6f0;
            position: relative;
        }

        .item-row .remove-item {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: none;
            border: none;
            color: #e74a3b;
            cursor: pointer;
            font-size: 1rem;
            padding: 0.25rem 0.5rem;
        }

        .item-row .remove-item:hover {
            color: #c0392b;
        }

        .service-item {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border: 1px solid #e3e6f0;
            border-radius: 0.5rem;
            margin-bottom: 0.3rem;
            transition: all 0.2s;
            cursor: pointer;
            background: #fff;
        }
        .service-item:hover {
            background: #f0f4ff;
            border-color: #4e73df;
        }
        .service-item .service-info { flex: 1; }
        .service-item .service-info .name { font-weight: 600; color: #1e3c72; }
        .service-item .service-info .desc { font-size: 0.8rem; color: #858796; }
        .service-item .service-price { font-weight: 700; color: #1e3c72; margin-right: 0.5rem; }

        .service-list-modal .modal-body {
            max-height: 400px;
            overflow-y: auto;
        }

        .category-header {
            font-weight: 700;
            color: #4e73df;
            padding: 0.5rem 0;
            margin-top: 0.5rem;
            border-bottom: 2px solid #e3e6f0;
        }

        /* ===== SIDEBAR COLLAPSE ON ALL SCREENS ===== */
        #sidebarToggleTop {
            display: inline-flex !important;
        }

        .sidebar.collapsed {
            width: 6.5rem !important;
        }

        .sidebar.collapsed .sidebar-brand-text {
            display: none !important;
        }

        .sidebar.collapsed .nav-link span {
            display: none !important;
        }

        .sidebar.collapsed .nav-link i {
            margin-right: 0 !important;
            font-size: 1.3rem !important;
        }

        .sidebar.collapsed .sidebar-heading {
            text-align: center !important;
            font-size: 0.55rem !important;
        }

        .sidebar.collapsed .sidebar-card {
            display: none !important;
        }

        /* ===== FOOTER ===== */
        footer.sticky-footer {
            margin-top: auto;
            width: 100%;
            height: 36px;
            min-height: 36px;
            padding: 8px 0;
            background: #002a66;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        footer.sticky-footer .copyright {
            margin: 0;
            font-size: 12px;
            line-height: 1;
        }

        /* ===== SIDEBAR FIX - STICKY ===== */
        #sidebar-container {
            display: flex;
            flex-shrink: 0;
            height: 100vh;
            position: sticky;
            top: 0;
            align-self: flex-start;
            overflow: hidden;
        }

        .sidebar {
            position: relative !important;
            height: 100vh !important;
            min-height: 100vh !important;
            width: 6.5rem !important;
            flex-shrink: 0 !important;
            display: flex !important;
            flex-direction: column !important;
        }

        #wrapper {
            display: flex !important;
            align-items: flex-start !important;
        }

        #wrapper #content-wrapper {
            flex: 1 1 auto !important;
            width: auto !important;
            min-width: 0 !important;
            overflow-x: hidden !important;
        }

        @media (min-width: 768px) {
            .sidebar {
                width: 14rem !important;
            }
        }

        @media (max-width: 768px) {
            #sidebar-container {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                height: 100vh !important;
                z-index: 1050 !important;
                width: 0 !important;
                overflow: hidden !important;
                transition: width 0.3s ease !important;
                align-self: auto !important;
            }
            
            #sidebar-container.toggled {
                width: 14rem !important;
            }
            
            .sidebar {
                width: 100% !important;
                height: 100vh !important;
                position: relative !important;
            }
        }

        /* ===== ORIGINAL SIDEBAR DROPDOWN STYLES ===== */
        .sidebar .nav-item .collapse {
            position: absolute;
            left: calc(6.5rem + 1.5rem / 2);
            z-index: 1;
            top: 2px;
        }

        .sidebar .nav-item .collapse .collapse-inner {
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            padding: 0.5rem 0;
            min-width: 12rem;
            font-size: 0.85rem;
            background: #ffffff;
            border: 1px solid #e3e6f0;
        }

        /* For desktop expanded sidebar */
        @media (min-width: 768px) {
            .sidebar .nav-item .collapse {
                position: relative;
                left: 0;
                z-index: 1;
                top: 0;
                animation: none;
            }

            .sidebar .nav-item .collapse .collapse-inner {
                border-radius: 0;
                box-shadow: none;
                background: transparent !important;
                border: none;
                padding: 0.25rem 0;
                min-width: auto;
            }

            .sidebar .nav-item .collapse .collapse-inner .collapse-header {
                color: rgba(255,255,255,0.4) !important;
                border-bottom: 1px solid rgba(255,255,255,0.08) !important;
                padding: 0.5rem 1rem;
                font-size: 0.6rem;
            }

            .sidebar .nav-item .collapse .collapse-inner .collapse-item {
                color: rgba(255,255,255,0.7) !important;
                padding: 0.4rem 1rem;
                border-left: 3px solid transparent;
                font-weight: 400;
            }

            .sidebar .nav-item .collapse .collapse-inner .collapse-item:hover {
                background: rgba(255,255,255,0.08) !important;
                color: #fff !important;
                border-left-color: #fff;
            }

            .sidebar .nav-item .collapse .collapse-inner .collapse-item.active {
                background: rgba(255,255,255,0.15) !important;
                color: #fff !important;
                border-left-color: #fff;
            }

            .sidebar .nav-item .collapsing {
                display: block;
                transition: height 0.15s ease;
            }

            .sidebar .nav-item .collapse,
            .sidebar .nav-item .collapsing {
                margin: 0 1rem;
            }

            .sidebar .nav-item .nav-link[data-toggle="collapse"]::after {
                width: 1rem;
                text-align: center;
                float: right;
                vertical-align: 0;
                border: 0;
                font-weight: 900;
                content: '\f107';
                font-family: 'Font Awesome 5 Free';
                margin-left: auto;
            }

            .sidebar .nav-item .nav-link[data-toggle="collapse"].collapsed::after {
                content: '\f105';
            }
        }

        /* For toggled/collapsed sidebar */
        @media (min-width: 768px) {
            .sidebar.toggled .nav-item .collapse {
                position: absolute;
                left: calc(6.5rem + 0.5rem);
                z-index: 1;
                top: 0;
                animation-name: growIn;
                animation-duration: 200ms;
                animation-timing-function: transform cubic-bezier(0.18, 1.25, 0.4, 1), opacity cubic-bezier(0, 1, 0.4, 1);
            }

            .sidebar.toggled .nav-item .collapse .collapse-inner {
                box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
                border-radius: 0.35rem;
                background: #ffffff !important;
                border: 1px solid #e3e6f0;
                min-width: 12rem;
                padding: 0.5rem 0;
            }

            .sidebar.toggled .nav-item .collapse .collapse-inner .collapse-header {
                color: #b7b9cc !important;
                border-bottom: 1px solid #e3e6f0 !important;
                padding: 0.5rem 1.5rem;
            }

            .sidebar.toggled .nav-item .collapse .collapse-inner .collapse-item {
                color: #3a3b45 !important;
                padding: 0.5rem 1.5rem;
                border-left: 3px solid transparent;
            }

            .sidebar.toggled .nav-item .collapse .collapse-inner .collapse-item:hover {
                background: #f0f4ff !important;
                color: #003986 !important;
                border-left-color: #003986;
            }

            .sidebar.toggled .nav-item .collapse .collapse-inner .collapse-item.active {
                background: #003986 !important;
                color: #ffffff !important;
                border-left-color: #ffffff;
            }

            .sidebar.toggled .nav-item .collapsing {
                display: none;
                transition: none;
            }

            .sidebar.toggled .nav-item .collapse,
            .sidebar.toggled .nav-item .collapsing {
                margin: 0;
            }

            .sidebar.toggled .nav-item .nav-link[data-toggle="collapse"]::after {
                display: none;
            }

            .sidebar.toggled .sidebar-brand .sidebar-brand-icon i {
                font-size: 2rem;
                display: block;
                margin-right: 0;
                margin-bottom: 4px;
            }

            .sidebar.toggled .sidebar-brand .sidebar-brand-text {
                display: none;
            }

            .sidebar.toggled .sidebar-heading {
                text-align: center;
            }

            .sidebar.toggled .sidebar-card {
                display: none;
            }
        }

        @keyframes growIn {
            0% { transform: scale(0.9); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        .animated--grow-in {
            animation-name: growIn;
            animation-duration: 200ms;
            animation-timing-function: transform cubic-bezier(0.18, 1.25, 0.4, 1), opacity cubic-bezier(0, 1, 0.4, 1);
        }
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
                    <!-- Sidebar Toggle (Topbar) - Always visible on all screens -->
                    <button id="sidebarToggleTop" class="btn btn-link rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-edit"></i> Edit Quote
                    </h1>

                    <ul class="navbar-nav ml-auto">
                        <div class="topbar-divider d-none d-sm-block"></div>
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?php echo htmlspecialchars($first_name); ?>
                                </span>
                                <img class="img-profile rounded-circle" src="../../img/undraw_profile.svg">
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
                                <a class="dropdown-item" href="../../logout.php">
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
                        <div>
                            <a href="view-quote.php?id=<?php echo $quote_id; ?>" class="btn btn-sm btn-secondary shadow-sm">
                                <i class="fas fa-arrow-left"></i> Back to Quote
                            </a>
                        </div>
                    </div>

                    <!-- Client Info -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-user"></i> <strong>Client:</strong> <?php echo htmlspecialchars($client['company_name']); ?>
                                (<?php echo htmlspecialchars($client['email']); ?>)
                                <span class="float-right">
                                    <i class="fas fa-tag"></i> <strong>Quote #:</strong> <?php echo htmlspecialchars($quote['quote_number']); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Edit Quote Details</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($errors)): ?>
                                    <div class="error-messages">
                                        <ul>
                                            <?php foreach ($errors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <?php endif; ?>

                                    <form method="POST" id="quoteForm">
                                        <!-- Quote Header Fields -->
                                        <div class="form-row">
                                            <div class="form-group col-md-4">
                                                <label>Quote Number <span class="required">*</span></label>
                                                <input type="text" class="form-control" name="quote_number" required value="<?php echo htmlspecialchars($quote['quote_number']); ?>">
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label>Quote Date <span class="required">*</span></label>
                                                <input type="date" class="form-control" name="quote_date" required value="<?php echo $quote['quote_date']; ?>">
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label>Expiry Date <span class="required">*</span></label>
                                                <input type="date" class="form-control" name="expiry_date" required value="<?php echo $quote['expiry_date']; ?>">
                                            </div>
                                        </div>

                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Status</label>
                                                <select class="form-control" name="status">
                                                    <option value="draft" <?php echo ($quote['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                                                    <option value="sent" <?php echo ($quote['status'] == 'sent') ? 'selected' : ''; ?>>Sent</option>
                                                    <option value="accepted" <?php echo ($quote['status'] == 'accepted') ? 'selected' : ''; ?>>Accepted</option>
                                                    <option value="rejected" <?php echo ($quote['status'] == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                                                    <option value="expired" <?php echo ($quote['status'] == 'expired') ? 'selected' : ''; ?>>Expired</option>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Notes</label>
                                                <input type="text" class="form-control" name="notes" value="<?php echo htmlspecialchars($quote['notes'] ?? ''); ?>" placeholder="Add notes to this quote">
                                            </div>
                                        </div>

                                        <hr>

                                        <!-- Quote Items -->
                                        <h6 class="font-weight-bold text-primary mb-3">Quote Items</h6>
                                        <div id="itemsContainer">
                                            <?php foreach ($items as $index => $item): ?>
                                            <div class="item-row">
                                                <button type="button" class="remove-item" onclick="removeItem(this)" title="Remove item">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                <input type="hidden" name="item_ids[]" value="<?php echo $item['id']; ?>">
                                                <div class="form-row">
                                                    <div class="form-group col-md-4">
                                                        <label>Item Name <span class="required">*</span></label>
                                                        <input type="text" class="form-control" name="item_names[]" required value="<?php echo htmlspecialchars($item['item_name']); ?>">
                                                    </div>
                                                    <div class="form-group col-md-4">
                                                        <label>Description</label>
                                                        <input type="text" class="form-control" name="item_descriptions[]" value="<?php echo htmlspecialchars($item['description']); ?>">
                                                    </div>
                                                    <div class="form-group col-md-1">
                                                        <label>Qty</label>
                                                        <input type="number" class="form-control" name="item_quantities[]" value="<?php echo $item['quantity']; ?>" min="1" onchange="updateTotal(this)">
                                                    </div>
                                                    <div class="form-group col-md-2">
                                                        <label>Unit Price (R)</label>
                                                        <input type="number" class="form-control" name="item_unit_prices[]" step="0.01" value="<?php echo number_format($item['unit_price'], 2); ?>" onchange="updateTotal(this)">
                                                    </div>
                                                    <div class="form-group col-md-1">
                                                        <label>Total</label>
                                                        <input type="text" class="form-control" value="R <?php echo number_format($item['total'], 2); ?>" readonly style="background:#f8f9fc; font-weight:bold;">
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <button type="button" class="btn btn-sm btn-primary mb-3" data-toggle="modal" data-target="#serviceModal">
                                            <i class="fas fa-plus"></i> Add Item from Services
                                        </button>
                                        <button type="button" class="btn btn-sm btn-secondary mb-3" onclick="addEmptyItem()">
                                            <i class="fas fa-plus"></i> Add Empty Item
                                        </button>

                                        <!-- Totals -->
                                        <div class="summary-box mt-3" style="background:#f8f9fc; padding:1rem; border-radius:0.5rem; border:2px dashed #d1d3e2;">
                                            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem;">
                                                <span>
                                                    <strong>Total Items:</strong> <span id="itemCount"><?php echo count($items); ?></span>
                                                </span>
                                                <span>
                                                    <strong>Grand Total:</strong>
                                                    <span style="font-size:1.3rem; font-weight:800; color:#1e3c72;" id="grandTotal">
                                                        R <?php echo number_format($quote['grand_total'], 2); ?>
                                                    </span>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div style="display:flex; gap:1rem; margin-top:1.5rem; padding-top:1rem; border-top:1px solid #e3e6f0;">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-save"></i> Update Quote
                                            </button>
                                            <a href="view-quote.php?id=<?php echo $quote_id; ?>" class="btn btn-secondary">
                                                <i class="fas fa-times"></i> Cancel
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End of container-fluid -->
            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-blue">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Marlani Technologies <?php echo date('Y'); ?></span>
                    </div>
                </div>
            </footer>
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Service Modal -->
    <div class="modal fade" id="serviceModal" tabindex="-1" role="dialog" aria-labelledby="serviceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="serviceModalLabel">Select Service</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body service-list-modal">
                    <?php foreach ($categories as $category => $category_services): ?>
                    <div class="category-header"><?php echo htmlspecialchars($category); ?></div>
                    <?php foreach ($category_services as $service): ?>
                    <div class="service-item" onclick="addService(<?php echo $service['id']; ?>, '<?php echo addslashes($service['service_name']); ?>', '<?php echo addslashes($service['description']); ?>', <?php echo $service['price']; ?>, <?php echo $service['monthly_subscription']; ?>)">
                        <div class="service-info">
                            <div class="name"><?php echo htmlspecialchars($service['service_name']); ?></div>
                            <div class="desc"><?php echo htmlspecialchars($service['description']); ?></div>
                        </div>
                        <div class="service-price">R <?php echo number_format($service['price'], 2); ?></div>
                        <?php if ($service['monthly_subscription'] > 0): ?>
                        <span class="badge badge-info">Monthly: R <?php echo number_format($service['monthly_subscription'], 2); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="../../vendor/jquery/jquery.min.js"></script>
    <script src="../../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../js/sb-admin-2.min.js"></script>

    <script>
    // Load sidebar from external file
    $(function() {
        $("#sidebar-container").load("../../sidebar.html", function() {
            console.log("Sidebar loaded successfully");
            
            // Set active state for current page
            $('#sidebar-container .nav-item').removeClass('active');
            $('#sidebar-container .nav-item a[href*="edit-quote"]').closest('.nav-item').addClass('active');
            
            // Find and highlight the parent menu if in submenu
            $('#sidebar-container .nav-item .collapse .collapse-item').each(function() {
                if ($(this).attr('href') && $(this).attr('href').includes('edit-quote')) {
                    $(this).addClass('active');
                    $(this).closest('.collapse').addClass('show');
                    $(this).closest('.nav-item').find('.nav-link').removeClass('collapsed');
                }
            });
            
            // Sidebar toggle for all screen sizes - no effects
            $('#sidebarToggleTop').on('click', function(e) {
                e.preventDefault();
                
                // Toggle the sidebar container
                $('#sidebar-container').toggleClass('toggled');
                
                // Toggle the sidebar collapse class for desktop
                $('.sidebar').toggleClass('collapsed');
                
                // Add overlay for mobile
                if ($('#sidebar-container').hasClass('toggled')) {
                    if ($(window).width() <= 768) {
                        $('body').append('<div class="sidebar-overlay active"></div>');
                    }
                } else {
                    $('.sidebar-overlay').remove();
                }
            });
            
            // Close sidebar on overlay click
            $(document).on('click', '.sidebar-overlay', function() {
                $('#sidebar-container').removeClass('toggled');
                $('.sidebar').removeClass('collapsed');
                $('.sidebar-overlay').remove();
            });
            
            // Handle window resize
            $(window).resize(function() {
                if ($(window).width() > 768) {
                    $('#sidebar-container').removeClass('toggled');
                    $('.sidebar-overlay').remove();
                }
            });
        });
    });

    let itemCounter = <?php echo count($items); ?>;

    function addEmptyItem() {
        const container = document.getElementById('itemsContainer');
        const row = document.createElement('div');
        row.className = 'item-row';
        row.innerHTML = `
            <button type="button" class="remove-item" onclick="removeItem(this)" title="Remove item">
                <i class="fas fa-times"></i>
            </button>
            <input type="hidden" name="item_ids[]" value="new_${itemCounter}">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Item Name <span class="required">*</span></label>
                    <input type="text" class="form-control" name="item_names[]" required placeholder="Enter item name">
                </div>
                <div class="form-group col-md-4">
                    <label>Description</label>
                    <input type="text" class="form-control" name="item_descriptions[]" placeholder="Enter description">
                </div>
                <div class="form-group col-md-1">
                    <label>Qty</label>
                    <input type="number" class="form-control" name="item_quantities[]" value="1" min="1" onchange="updateTotal(this)">
                </div>
                <div class="form-group col-md-2">
                    <label>Unit Price (R)</label>
                    <input type="number" class="form-control" name="item_unit_prices[]" step="0.01" value="0.00" onchange="updateTotal(this)">
                </div>
                <div class="form-group col-md-1">
                    <label>Total</label>
                    <input type="text" class="form-control" value="R 0.00" readonly style="background:#f8f9fc; font-weight:bold;">
                </div>
            </div>
        `;
        container.appendChild(row);
        itemCounter++;
        updateGrandTotal();
    }

    function removeItem(button) {
        const row = button.closest('.item-row');
        if (document.querySelectorAll('.item-row').length > 1) {
            row.remove();
            updateGrandTotal();
        } else {
            alert('You must have at least one item.');
        }
    }

    function addService(id, name, description, price, monthlyFee) {
        const container = document.getElementById('itemsContainer');
        const row = document.createElement('div');
        row.className = 'item-row';
        
        let descText = description;
        if (monthlyFee > 0) {
            descText += ` (Monthly: R ${parseFloat(monthlyFee).toFixed(2)}/month)`;
        }
        
        row.innerHTML = `
            <button type="button" class="remove-item" onclick="removeItem(this)" title="Remove item">
                <i class="fas fa-times"></i>
            </button>
            <input type="hidden" name="item_ids[]" value="service_${id}">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Item Name <span class="required">*</span></label>
                    <input type="text" class="form-control" name="item_names[]" required value="${name.replace(/"/g, '&quot;')}">
                </div>
                <div class="form-group col-md-4">
                    <label>Description</label>
                    <input type="text" class="form-control" name="item_descriptions[]" value="${descText.replace(/"/g, '&quot;')}">
                </div>
                <div class="form-group col-md-1">
                    <label>Qty</label>
                    <input type="number" class="form-control" name="item_quantities[]" value="1" min="1" onchange="updateTotal(this)">
                </div>
                <div class="form-group col-md-2">
                    <label>Unit Price (R)</label>
                    <input type="number" class="form-control" name="item_unit_prices[]" step="0.01" value="${parseFloat(price).toFixed(2)}" onchange="updateTotal(this)">
                </div>
                <div class="form-group col-md-1">
                    <label>Total</label>
                    <input type="text" class="form-control" value="R ${parseFloat(price).toFixed(2)}" readonly style="background:#f8f9fc; font-weight:bold;">
                </div>
            </div>
        `;
        container.appendChild(row);
        itemCounter++;
        updateGrandTotal();
        
        // Close modal
        $('#serviceModal').modal('hide');
    }

    function updateTotal(input) {
        const row = input.closest('.form-row');
        const qty = parseFloat(row.querySelector('input[name="item_quantities[]"]').value) || 1;
        const price = parseFloat(row.querySelector('input[name="item_unit_prices[]"]').value) || 0;
        const total = qty * price;
        const totalInput = row.querySelector('.form-group:last-child input');
        totalInput.value = 'R ' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        updateGrandTotal();
    }

    function updateGrandTotal() {
        const rows = document.querySelectorAll('.item-row');
        let grandTotal = 0;
        rows.forEach(row => {
            const qty = parseFloat(row.querySelector('input[name="item_quantities[]"]').value) || 1;
            const price = parseFloat(row.querySelector('input[name="item_unit_prices[]"]').value) || 0;
            grandTotal += qty * price;
        });
        document.getElementById('itemCount').textContent = rows.length;
        document.getElementById('grandTotal').textContent = 'R ' + grandTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    </script>
</body>
</html>