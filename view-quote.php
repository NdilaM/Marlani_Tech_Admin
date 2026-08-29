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

// Logo path - keep original logo with transparency
$logo_path = 'img/company_logo.png';
$logo_html = '';
if (file_exists($logo_path)) {
    // Use the logo with white text version for dark background
    $logo_html = '<img src="' . $logo_path . '" alt="Company Logo" class="header-logo">';
} else {
    $logo_html = '<h1 style="margin:0; color:#fff; font-size:28px;">' . ($settings['company_name'] ?? 'Marlani Technologies') . '</h1>';
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

    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        /* ===== SIDEBAR LOADING FIXES - ORIGINAL STYLE ===== */
        #sidebar-container {
            min-height: 100vh;
        }

        #sidebar-container .sidebar {
            min-height: 100vh;
        }

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

        /* ===== PAGE LAYOUT - FILL PAGE ===== */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            background: #f8f9fc;
        }

        #wrapper {
            display: flex;
            min-height: 100vh;
            height: 100%;
        }

        #content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            height: 100%;
        }

        #content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .container-fluid {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 0.5rem 1rem;
            max-width: 100% !important;
            width: 100% !important;
        }

        /* ===== QUOTE CARD ===== */
        .quote-card {
            flex: 1;
            display: flex;
            flex-direction: column;
            margin-bottom: 0 !important;
            border-radius: 0.5rem !important;
            width: 100% !important;
        }

        .quote-card .card-body {
            flex: 1;
            padding: 0 !important;
            display: flex;
            flex-direction: column;
            width: 100% !important;
        }

        .quote-container {
            flex: 1;
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 auto;
            padding: 0;
            background: #fff;
            border-radius: 0.5rem;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* ===== QUOTE HEADER ===== */
        .quote-header {
            background: #003986;
            padding: 0.8rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 3px solid #4e73df;
            flex-shrink: 0;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .header-logo {
            height: 50px;
            width: auto;
            max-width: 180px;
            object-fit: contain;
        }

        .header-right {
            text-align: right;
        }

        .header-right .quote-badge {
            display: inline-block;
            padding: 0.35rem 1.2rem;
            background: #4e73df;
            color: #fff;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 2px;
        }

        .header-right .company-details {
            margin-top: 0.2rem;
            font-size: 0.75rem;
            line-height: 1.4;
            color: rgba(255,255,255,0.7);
        }

        /* ===== INFO BAR ===== */
        .info-bar {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 0.5rem;
            padding: 0.5rem 2rem;
            background: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            flex-shrink: 0;
        }

        .info-bar .item {
            font-size: 0.85rem;
            color: #555;
        }

        .info-bar .item strong {
            color: #1e3c72;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.7rem;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.75rem;
            line-height: 1.3;
        }

        .status-draft { background: #fef3c7; color: #d97706; }
        .status-sent { background: #dbeafe; color: #2563eb; }
        .status-accepted { background: #dcfce7; color: #16a34a; }
        .status-rejected { background: #fee2e2; color: #dc2626; }

        /* ===== BILLING - COMPACT ===== */
        .billing-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            padding: 0.6rem 2rem;
            align-items: start;
            border-bottom: 1px solid #e3e6f0;
            flex-shrink: 0;
        }

        .billing-box h4 {
            margin: 0 0 0.3rem;
            padding-bottom: 0.3rem;
            border-bottom: 2px solid #e3e6f0;
            color: #4e73df;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .billing-box .client-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e3c72;
            margin-bottom: 0.15rem;
        }

        .billing-box p {
            margin: 0.1rem 0;
            font-size: 0.9rem;
            line-height: 1.3;
            color: #555;
        }

        .billing-box.text-right {
            text-align: right;
        }

        /* ===== TABLES ===== */
        .quote-container table {
            width: 100%;
            margin: 0;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .quote-container th {
            padding: 0.5rem 0.8rem;
            background: #003986;
            color: #fff;
            text-align: left;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            vertical-align: middle;
        }

        .quote-container td {
            padding: 0.5rem 0.8rem;
            border-bottom: 1px solid #e3e6f0;
            font-size: 0.85rem;
            line-height: 1.3;
            vertical-align: top;
        }

        .quote-container th:nth-child(1),
        .quote-container td:nth-child(1) { width: 5%; }
        .quote-container th:nth-child(2),
        .quote-container td:nth-child(2) { width: 20%; }
        .quote-container th:nth-child(3),
        .quote-container td:nth-child(3) { width: 35%; }
        .quote-container th:nth-child(4),
        .quote-container td:nth-child(4) { width: 10%; }
        .quote-container th:nth-child(5),
        .quote-container td:nth-child(5) { width: 15%; }
        .quote-container th:nth-child(6),
        .quote-container td:nth-child(6) { width: 15%; }

        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }

        .item-name {
            font-weight: 700;
            color: #1e3c72;
            font-size: 0.9rem;
        }

        .item-desc {
            font-size: 0.8rem;
            color: #888;
        }

        /* ===== SECTIONS / TOTALS ===== */
        .section-title {
            padding: 0 2rem;
            margin: 0.6rem 0 0.2rem;
            padding-bottom: 0.2rem;
            border-bottom: 2px solid #e3e6f0;
            font-size: 0.9rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .section-title.one-time {
            color: #4e73df;
            border-color: #4e73df;
        }

        .section-title.subscription {
            color: #36b9cc;
            border-color: #36b9cc;
        }

        .table-wrapper {
            padding: 0 2rem;
            overflow-x: auto;
            flex: 1;
            width: 100%;
        }

        .totals-section {
            padding: 0.5rem 2rem;
            border-top: 2px solid #e3e6f0;
            text-align: right;
            flex-shrink: 0;
            margin-top: auto;
        }

        .totals-section p {
            margin: 0.1rem 0;
            font-size: 0.95rem;
        }

        .grand-total {
            font-size: 1.2rem;
            color: #1e3c72;
            font-weight: 800;
        }

        .subscription-total {
            font-size: 1.05rem;
            color: #36b9cc;
            font-weight: 700;
        }

        .one-time-total {
            font-size: 1.05rem;
            color: #4e73df;
            font-weight: 700;
        }

        /* ===== TERMS ===== */
        .terms {
            margin: 0 2rem 0.5rem;
            padding: 0.6rem 1rem;
            background: #f8f9fc;
            border-radius: 0.4rem;
            border-left: 4px solid #4e73df;
            flex-shrink: 0;
        }

        .terms h4 {
            margin: 0 0 0.2rem;
            color: #1e3c72;
            font-size: 0.85rem;
        }

        .terms ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .terms ul li {
            position: relative;
            padding: 0.05rem 0 0.05rem 1.2rem;
            font-size: 0.8rem;
            line-height: 1.3;
            color: #666;
        }

        .terms ul li::before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #4e73df;
            font-weight: 700;
        }

        /* ===== FOOTER ===== */
        .footer-text {
            padding: 0.4rem 2rem;
            border-top: 1px solid #e3e6f0;
            text-align: center;
            color: #999;
            font-size: 0.75rem;
            flex-shrink: 0;
        }

        .footer-text p {
            margin: 0.1rem 0;
        }

        .footer-text .brand {
            color: #1e3c72;
            font-weight: 600;
        }

        /* Remove the page footer */
        .sticky-footer {
            display: none !important;
        }

        /* ===== BUTTON HOVER FIX ===== */
        .btn:hover {
            color: #ffffff !important;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 991.98px) {
            .container-fluid {
                padding: 0.5rem;
            }

            .quote-header {
                padding: 0.6rem 1.5rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 0.3rem;
            }

            .header-right {
                text-align: left;
                width: 100%;
            }

            .info-bar {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                padding: 0.4rem 1.5rem;
            }

            .billing-section {
                grid-template-columns: 1fr;
                gap: 0.5rem;
                padding: 0.4rem 1.5rem;
            }

            .billing-box.text-right {
                text-align: left;
            }

            .section-title {
                padding: 0 1.5rem;
            }

            .table-wrapper {
                padding: 0 1.5rem;
            }

            .totals-section {
                padding: 0.4rem 1.5rem;
            }

            .terms {
                margin: 0 1.5rem 0.4rem;
                padding: 0.4rem 0.8rem;
            }

            .footer-text {
                padding: 0.4rem 1.5rem;
            }
        }

        @media (max-width: 767.98px) {
            #sidebar-container {
                position: fixed !important;
                top: 0;
                left: 0;
                width: 0 !important;
                min-width: 0 !important;
                flex-basis: 0 !important;
                height: 100vh;
                z-index: 1050;
                overflow: visible;
                background: transparent;
            }

            #sidebar-container .sidebar {
                width: 280px;
                height: 100vh;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                position: fixed;
                top: 0;
                left: 0;
                z-index: 1050;
                overflow-y: auto;
            }

            #sidebar-container.toggled {
                width: 280px !important;
                min-width: 280px !important;
                flex-basis: 280px !important;
            }

            #sidebar-container.toggled .sidebar {
                transform: translateX(0);
                box-shadow: 4px 0 20px rgba(0, 0, 0, 0.3);
            }

            #content-wrapper {
                width: 100% !important;
                margin-left: 0 !important;
                position: relative;
                z-index: 1;
            }

            .topbar {
                padding: 0 .75rem;
            }

            .topbar .h3 {
                font-size: 1rem !important;
            }

            .navbar-search {
                display: none !important;
            }

            .container-fluid {
                padding: 0.25rem;
            }

            .d-sm-flex {
                display: flex !important;
                flex-direction: column;
                align-items: flex-start !important;
                gap: 0.3rem;
                padding: 0.15rem 0;
            }

            .d-sm-flex > div:last-child {
                width: 100%;
                display: flex;
                flex-wrap: wrap;
                gap: 0.2rem;
            }

            .d-sm-flex > div:last-child .btn {
                flex: 1 1 auto;
                font-size: 0.6rem;
                padding: 0.1rem 0.3rem;
            }

            .quote-container {
                border-radius: 0.4rem;
            }

            .quote-header {
                padding: 0.4rem 0.8rem;
                gap: 0.2rem;
            }

            .header-logo {
                height: 30px;
                max-width: 80px;
            }

            .header-right .quote-badge {
                font-size: 0.65rem;
                padding: 0.2rem 0.6rem;
            }

            .header-right .company-details {
                font-size: 0.6rem;
            }

            .info-bar {
                grid-template-columns: 1fr 1fr;
                gap: 0.15rem;
                padding: 0.3rem 0.8rem;
            }

            .info-bar .item {
                font-size: 0.65rem;
            }

            .billing-section {
                padding: 0.3rem 0.8rem;
                gap: 0.3rem;
            }

            .billing-box .client-name {
                font-size: 0.9rem;
            }

            .billing-box p {
                font-size: 0.7rem;
                margin: 0.05rem 0;
            }

            .billing-box h4 {
                font-size: 0.65rem;
                margin-bottom: 0.15rem;
                padding-bottom: 0.15rem;
            }

            .section-title {
                padding: 0 0.8rem;
                font-size: 0.75rem;
                margin: 0.4rem 0 0.1rem;
            }

            .table-wrapper {
                padding: 0 0.8rem;
                overflow-x: auto;
            }

            .quote-container table {
                font-size: 0.65rem;
                min-width: 450px;
            }

            .quote-container th,
            .quote-container td {
                padding: 0.25rem 0.4rem;
                font-size: 0.6rem;
            }

            .quote-container th {
                font-size: 0.55rem;
            }

            .totals-section {
                padding: 0.3rem 0.8rem;
            }

            .totals-section p {
                font-size: 0.7rem;
                margin: 0.05rem 0;
            }

            .grand-total {
                font-size: 0.9rem;
            }

            .terms {
                margin: 0 0.8rem 0.3rem;
                padding: 0.3rem 0.6rem;
            }

            .terms h4 {
                font-size: 0.7rem;
            }

            .terms ul li {
                font-size: 0.65rem;
                padding: 0.03rem 0 0.03rem 0.9rem;
            }

            .footer-text {
                padding: 0.3rem 0.8rem;
                font-size: 0.6rem;
            }
        }

        @media (max-width: 480px) {
            .info-bar {
                grid-template-columns: 1fr;
            }

            .header-left {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .header-right {
                text-align: center;
            }

            .billing-box .client-name {
                font-size: 0.8rem;
            }

            .billing-box p {
                font-size: 0.65rem;
            }

            .quote-container th,
            .quote-container td {
                padding: 0.15rem 0.25rem;
                font-size: 0.55rem;
            }

            .totals-section p {
                font-size: 0.65rem;
            }

            .grand-total {
                font-size: 0.8rem;
            }
        }

        /* ===== LAPTOP VIEW - EXPAND HORIZONTALLY ===== */
        @media (min-width: 1200px) {
            .container-fluid {
                padding: 0.5rem 2rem;
                max-width: 100% !important;
            }

            .quote-container {
                max-width: 100% !important;
                width: 100% !important;
            }

            .billing-section {
                gap: 4rem;
                padding: 0.8rem 2.5rem;
            }

            .billing-box .client-name {
                font-size: 1.2rem;
            }

            .billing-box p {
                font-size: 1rem;
            }

            .info-bar {
                padding: 0.6rem 2.5rem;
            }

            .info-bar .item {
                font-size: 0.95rem;
            }

            .section-title {
                padding: 0 2.5rem;
                font-size: 1rem;
            }

            .table-wrapper {
                padding: 0 2.5rem;
            }

            .quote-container th {
                font-size: 0.85rem;
                padding: 0.6rem 1rem;
            }

            .quote-container td {
                font-size: 0.95rem;
                padding: 0.6rem 1rem;
            }

            .item-name {
                font-size: 1rem;
            }

            .item-desc {
                font-size: 0.9rem;
            }

            .totals-section {
                padding: 0.6rem 2.5rem;
            }

            .totals-section p {
                font-size: 1.05rem;
            }

            .grand-total {
                font-size: 1.3rem;
            }

            .terms {
                margin: 0 2.5rem 0.5rem;
                padding: 0.6rem 1.2rem;
            }

            .terms h4 {
                font-size: 0.95rem;
            }

            .terms ul li {
                font-size: 0.9rem;
            }

            .footer-text {
                padding: 0.5rem 2.5rem;
                font-size: 0.85rem;
            }

            .quote-header {
                padding: 1rem 2.5rem;
            }

            .header-logo {
                height: 55px;
            }

            .header-right .quote-badge {
                font-size: 0.95rem;
                padding: 0.4rem 1.5rem;
            }

            .header-right .company-details {
                font-size: 0.85rem;
            }
        }

        /* ===== PRINT ===== */
        @media print {
            @page {
                margin: 0;
                size: A4;
            }

            html, body {
                margin: 0;
                padding: 0;
                background: #fff !important;
                height: 100% !important;
            }

            #wrapper {
                height: 100% !important;
                min-height: 100% !important;
            }

            .sidebar,
            #sidebar-container,
            .topbar,
            .action-buttons,
            .no-print,
            .sticky-footer,
            .scroll-to-top,
            .btn,
            .d-sm-flex > div:last-child,
            .navbar,
            .topbar-divider,
            .nav-item,
            .dropdown,
            #userDropdown,
            .container-fluid > .d-sm-flex,
            .mb-4 {
                display: none !important;
            }

            .container-fluid {
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                height: 100% !important;
            }

            .row {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                height: 100% !important;
            }

            .col-12 {
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                height: 100% !important;
                flex: 0 0 100% !important;
                max-width: 100% !important;
            }

            .card,
            .quote-card {
                margin: 0 !important;
                padding: 0 !important;
                border: 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                width: 100% !important;
                height: 100% !important;
            }

            .card-body {
                padding: 0 !important;
                width: 100% !important;
                height: 100% !important;
            }

            .quote-container {
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
                border-radius: 0 !important;
                width: 100% !important;
                height: 100% !important;
            }

            .quote-header {
                background: #003986 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                border-radius: 0 !important;
                padding: 0.4rem 1rem !important;
            }

            .header-logo {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                height: 32px !important;
            }

            .header-right .quote-badge {
                font-size: 0.65rem !important;
                padding: 0.15rem 0.6rem !important;
            }

            .header-right .company-details {
                font-size: 0.55rem !important;
            }

            .info-bar {
                background: #f8f9fc !important;
                padding: 0.25rem 1rem !important;
                gap: 0.2rem !important;
            }

            .info-bar .item {
                font-size: 0.6rem !important;
            }

            .billing-section {
                padding: 0.25rem 1rem !important;
                gap: 0.5rem !important;
            }

            .billing-box h4 {
                font-size: 0.6rem !important;
            }

            .billing-box .client-name {
                font-size: 0.85rem !important;
            }

            .billing-box p {
                font-size: 0.65rem !important;
                margin: 0.05rem 0 !important;
            }

            .section-title {
                padding: 0 1rem !important;
                margin: 0.3rem 0 0.05rem !important;
                font-size: 0.7rem !important;
            }

            .table-wrapper {
                padding: 0 1rem !important;
            }

            .quote-container table {
                font-size: 0.6rem !important;
            }

            .quote-container th,
            .quote-container td {
                padding: 0.15rem 0.3rem !important;
                font-size: 0.55rem !important;
            }

            .quote-container th {
                font-size: 0.5rem !important;
                background: #003986 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color: #fff !important;
            }

            .totals-section {
                padding: 0.25rem 1rem !important;
            }

            .totals-section p {
                font-size: 0.65rem !important;
                margin: 0.05rem 0 !important;
            }

            .grand-total {
                font-size: 0.85rem !important;
            }

            .terms {
                margin: 0 1rem 0.25rem !important;
                padding: 0.25rem 0.6rem !important;
                background: #f8f9fc !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .terms h4 {
                font-size: 0.6rem !important;
            }

            .terms ul li {
                font-size: 0.55rem !important;
                padding: 0.03rem 0 0.03rem 0.8rem !important;
            }

            .footer-text {
                padding: 0.25rem 1rem !important;
                font-size: 0.5rem !important;
            }

            .footer-text p {
                margin: 0.03rem 0 !important;
            }

            .quote-header,
            .billing-section,
            .terms,
            table,
            tr {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <!-- Sidebar -->
        <div id="sidebar-container"></div>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <h1 class="h3 mb-0 text-gray-800" style="font-size:1.25rem;">
                        <i class="fas fa-file-invoice text-primary"></i> Quote Details
                    </h1>

                    <ul class="navbar-nav ml-auto">
                        <div class="topbar-divider d-none d-sm-block"></div>
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo htmlspecialchars($first_name); ?></span>
                                <img class="img-profile rounded-circle" src="img/undraw_profile.svg">
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i> Logout</a>
                            </div>
                        </li>
                    </ul>
                </nav>

                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-3">
                        <div class="action-buttons">
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

                    <div class="row" style="flex:1;">
                        <div class="col-12" style="display:flex; flex-direction:column;">
                            <div class="card shadow quote-card" style="flex:1;">
                                <div class="card-body" style="padding: 0; display:flex; flex-direction:column;">
                                    <div class="quote-container">
                                        <!-- Quote Header -->
                                        <div class="quote-header">
                                            <div class="header-left">
                                                <?php echo $logo_html; ?>
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
                                                <?php if (!empty($client['contact_person'])): ?>
                                                <p><?php echo htmlspecialchars($client['contact_person']); ?></p>
                                                <?php endif; ?>
                                                <p><?php echo htmlspecialchars($client['email']); ?></p>
                                                <?php if (!empty($client['phone'])): ?>
                                                <p><?php echo htmlspecialchars($client['phone']); ?></p>
                                                <?php endif; ?>
                                                <?php if (!empty($client['address'])): ?>
                                                <p><?php echo htmlspecialchars($client['address']); ?></p>
                                                <?php endif; ?>
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
                                        <div class="table-wrapper">
                                            <table>
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Item</th>
                                                        <th>Description</th>
                                                        <th style="text-align:center;">Qty</th>
                                                        <th style="text-align:right;">Unit Price</th>
                                                        <th style="text-align:right;">Total</th>
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
                                        </div>
                                        <?php endif; ?>

                                        <!-- Monthly Subscription/Maintenance Services Table -->
                                        <?php if (!empty($subscription_items)): ?>
                                        <div class="section-title subscription">
                                            <i class="fas fa-clock"></i> Monthly Subscription / Maintenance
                                        </div>
                                        <div class="table-wrapper">
                                            <table>
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Item</th>
                                                        <th>Description</th>
                                                        <th style="text-align:center;">Qty</th>
                                                        <th style="text-align:right;">Monthly Fee</th>
                                                        <th style="text-align:right;">Total</th>
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
                                        </div>
                                        <?php endif; ?>

                                        <!-- Totals -->
                                        <div class="totals-section">
                                            <?php if ($one_time_total > 0): ?>
                                            <p class="one-time-total"><strong>One-Time Total:</strong> R <?php echo number_format($one_time_total, 2); ?></p>
                                            <?php endif; ?>
                                            <?php if ($monthly_total > 0): ?>
                                            <p class="subscription-total"><strong>Monthly Total:</strong> R <?php echo number_format($monthly_total, 2); ?></p>
                                            <p style="font-size:0.7rem;color:#888;margin:0;"><i class="fas fa-info-circle"></i> Total monthly recurring fee</p>
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
                                        <div class="footer-text">
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
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>

    <script>
    $(function() {
        // Load sidebar from external file
        $("#sidebar-container").load("sidebar.html", function() {
            console.log("Sidebar loaded successfully");
            
            // Set active state for current page
            $('#sidebar-container .nav-item').removeClass('active');
            $('#sidebar-container .nav-item a[href*="view-quote"]').closest('.nav-item').addClass('active');
            
            // Find and highlight the parent menu if in submenu
            $('#sidebar-container .nav-item .collapse .collapse-item').each(function() {
                if ($(this).attr('href') && $(this).attr('href').includes('view-quote')) {
                    $(this).addClass('active');
                    $(this).closest('.collapse').addClass('show');
                    $(this).closest('.nav-item').find('.nav-link').removeClass('collapsed');
                }
            });
            
            // Sidebar toggle for mobile
            $('#sidebarToggleTop').on('click', function(e) {
                e.preventDefault();
                $('#sidebar-container').toggleClass('toggled');
                if ($('#sidebar-container').hasClass('toggled')) {
                    $('body').append('<div class="sidebar-overlay active"></div>');
                } else {
                    $('.sidebar-overlay').remove();
                }
            });
            
            // Close sidebar on overlay click
            $(document).on('click', '.sidebar-overlay', function() {
                $('#sidebar-container').removeClass('toggled');
                $('.sidebar-overlay').remove();
            });
            
            // Handle window resize - remove toggled state on desktop
            $(window).resize(function() {
                if ($(window).width() > 768) {
                    $('#sidebar-container').removeClass('toggled');
                    $('.sidebar-overlay').remove();
                }
            });
        });
    });
    </script>
</body>
</html>