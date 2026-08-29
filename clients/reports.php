<?php
// reports.php - Client Reports Dashboard
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.html');
    exit();
}

require_once '../db.php';

$first_name = $_SESSION['first_name'] ?? 'User';

// Get all clients
$stmt = $conn->query("SELECT * FROM clients ORDER BY company_name");
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get client statistics
$stmt = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
        SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended,
        SUM(CASE WHEN client_type = 'business' THEN 1 ELSE 0 END) as business,
        SUM(CASE WHEN client_type = 'individual' THEN 1 ELSE 0 END) as individual,
        SUM(CASE WHEN client_type = 'government' THEN 1 ELSE 0 END) as government,
        SUM(CASE WHEN client_type = 'non-profit' THEN 1 ELSE 0 END) as non_profit
    FROM clients
");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get quotes by client
$stmt = $conn->query("
    SELECT c.id, c.company_name, c.client_code, 
           COUNT(q.id) as quote_count,
           SUM(q.grand_total) as total_value
    FROM clients c
    LEFT JOIN quotations q ON c.id = q.client_id
    GROUP BY c.id
    ORDER BY quote_count DESC
    LIMIT 10
");
$top_clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get status distribution for chart
$status_data = [
    'active' => $stats['active'] ?? 0,
    'pending' => $stats['pending'] ?? 0,
    'inactive' => $stats['inactive'] ?? 0,
    'suspended' => $stats['suspended'] ?? 0
];

// Get client type distribution
$type_data = [
    'business' => $stats['business'] ?? 0,
    'individual' => $stats['individual'] ?? 0,
    'government' => $stats['government'] ?? 0,
    'non-profit' => $stats['non_profit'] ?? 0
];

// Get monthly new clients (last 6 months)
$stmt = $conn->query("
    SELECT 
        DATE_FORMAT(created_at, '%M') as month,
        COUNT(*) as count
    FROM clients
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY created_at ASC
");
$monthly_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent activity
$stmt = $conn->query("
    SELECT 
        'new_client' as type,
        company_name as description,
        created_at as date
    FROM clients
    ORDER BY created_at DESC
    LIMIT 5
");
$recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get industry distribution
$stmt = $conn->query("
    SELECT industry, COUNT(*) as count
    FROM clients
    WHERE industry IS NOT NULL AND industry != ''
    GROUP BY industry
    ORDER BY count DESC
    LIMIT 10
");
$industry_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total quote value
$stmt = $conn->query("SELECT SUM(grand_total) as total FROM quotations");
$total_quote_value = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Calculate average quotes per client
$avg_quotes = ($stats['total'] > 0) ? round($total_quote_value / $stats['total'], 2) : 0;

// =============================================
// GENERATE REPORT FUNCTION - SAVES TO clients/reports/
// =============================================
function generateReport($data) {
    // Save to clients/reports/ folder (one level up from current file)
    $report_dir = __DIR__ . '/reports/';
    
    // Create reports directory if it doesn't exist
    if (!is_dir($report_dir)) {
        mkdir($report_dir, 0755, true);
    }
    
    $date = date('Y-m-d_H-i-s');
    $filename = 'client_report_' . $date . '.html';
    $filepath = $report_dir . $filename;
    
    // Build HTML content
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Client Report - ' . date('Y-m-d') . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; color: #333; }
        h1 { color: #003986; border-bottom: 3px solid #003986; padding-bottom: 10px; }
        h2 { color: #4e73df; margin-top: 30px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #003986; color: #fff; padding: 10px; text-align: left; }
        td { padding: 8px 10px; border-bottom: 1px solid #ddd; }
        tr:hover { background: #f5f5f5; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 20px 0; }
        .stat-box { background: #f8f9fc; padding: 15px; border-radius: 8px; border-left: 4px solid #4e73df; }
        .stat-box .number { font-size: 24px; font-weight: bold; color: #003986; }
        .stat-box .label { color: #858796; font-size: 12px; text-transform: uppercase; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #999; font-size: 12px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .badge-active { background: #dcfce7; color: #16a34a; }
        .badge-pending { background: #fef3c7; color: #d97706; }
        .badge-inactive { background: #fee2e2; color: #dc2626; }
        .badge-suspended { background: #f3f4f6; color: #6b7280; }
        .text-right { text-align: right; }
        .mt-20 { margin-top: 20px; }
        .mb-10 { margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>Client Report</h1>
    <p>Generated on: ' . date('Y-m-d H:i:s') . '</p>
    
    <div class="stats-grid">';
    
    $html .= '
        <div class="stat-box">
            <div class="number">' . number_format($data['stats']['total'] ?? 0) . '</div>
            <div class="label">Total Clients</div>
        </div>
        <div class="stat-box" style="border-left-color: #1cc88a;">
            <div class="number">' . number_format($data['stats']['active'] ?? 0) . '</div>
            <div class="label">Active Clients</div>
        </div>
        <div class="stat-box" style="border-left-color: #36b9cc;">
            <div class="number">R ' . number_format($data['total_quote_value'] ?? 0, 0) . '</div>
            <div class="label">Total Quote Value</div>
        </div>
        <div class="stat-box" style="border-left-color: #f6c23e;">
            <div class="number">R ' . number_format($data['avg_quotes'] ?? 0, 0) . '</div>
            <div class="label">Avg Quote Value</div>
        </div>
    </div>';
    
    // Client Status Table
    $html .= '
    <h2>Client Status Distribution</h2>
    <table>
        <thead>
            <tr>
                <th>Status</th>
                <th>Count</th>
                <th>Percentage</th>
            </tr>
        </thead>
        <tbody>';
    
    $total = array_sum($data['status_data']);
    foreach ($data['status_data'] as $status => $count) {
        $pct = ($total > 0) ? round(($count / $total) * 100) : 0;
        $html .= '
            <tr>
                <td><span class="badge badge-' . $status . '">' . ucfirst($status) . '</span></td>
                <td>' . $count . '</td>
                <td>' . $pct . '%</td>
            </tr>';
    }
    
    $html .= '
        </tbody>
    </table>';
    
    // Client Type Table
    $html .= '
    <h2>Client Type Distribution</h2>
    <table>
        <thead>
            <tr>
                <th>Type</th>
                <th>Count</th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($data['type_data'] as $type => $count) {
        $html .= '
            <tr>
                <td>' . ucfirst(str_replace('-', ' ', $type)) . '</td>
                <td>' . $count . '</td>
            </tr>';
    }
    
    $html .= '
        </tbody>
    </table>';
    
    // Top Clients
    $html .= '
    <h2>Top Clients by Quotes</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Client</th>
                <th>Code</th>
                <th>Quotes</th>
                <th>Total Value</th>
            </tr>
        </thead>
        <tbody>';
    
    $rank = 1;
    foreach ($data['top_clients'] as $client) {
        $html .= '
            <tr>
                <td>' . $rank++ . '</td>
                <td>' . htmlspecialchars($client['company_name']) . '</td>
                <td>' . htmlspecialchars($client['client_code']) . '</td>
                <td>' . $client['quote_count'] . '</td>
                <td class="text-right">R ' . number_format($client['total_value'] ?? 0, 2) . '</td>
            </tr>';
    }
    
    $html .= '
        </tbody>
    </table>';
    
    // Monthly Trend
    $html .= '
    <h2>Monthly New Client Registrations</h2>
    <table>
        <thead>
            <tr>
                <th>Month</th>
                <th>New Clients</th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($data['monthly_data'] as $month) {
        $html .= '
            <tr>
                <td>' . htmlspecialchars($month['month']) . '</td>
                <td>' . $month['count'] . '</td>
            </tr>';
    }
    
    $html .= '
        </tbody>
    </table>';
    
    $html .= '
    <div class="footer">
        <p>Generated by Marlani Admin System</p>
        <p>&copy; ' . date('Y') . ' Marlani Technologies - All Rights Reserved</p>
    </div>
</body>
</html>';
    
    // Save to file
    file_put_contents($filepath, $html);
    
    return [
        'filename' => $filename,
        'filepath' => $filepath,
        'url' => 'reports/' . $filename
    ];
}

// Check if report generation is requested
$report_message = '';
$report_link = '';
if (isset($_GET['generate']) && $_GET['generate'] == '1') {
    $data = [
        'stats' => $stats,
        'status_data' => $status_data,
        'type_data' => $type_data,
        'top_clients' => $top_clients,
        'monthly_data' => $monthly_data,
        'total_quote_value' => $total_quote_value,
        'avg_quotes' => $avg_quotes
    ];
    
    $result = generateReport($data);
    $report_message = 'Report generated successfully!';
    $report_link = $result['url'];
    
    // Store in session for display
    $_SESSION['report_generated'] = true;
    $_SESSION['report_filename'] = $result['filename'];
    $_SESSION['report_url'] = $result['url'];
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
    <title>Marlani Admin - Client Reports</title>

    <!-- Custom fonts for this template-->
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">

    <!-- Page level plugins -->
    <script src="../vendor/chart.js/Chart.min.js"></script>

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
        html, body {
            height: 100%;
            margin: 0;
        }

        #wrapper {
            display: flex !important;
            align-items: flex-start !important;
            min-height: 100vh;
            height: 100%;
        }

        #content-wrapper {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: 100vh;
            height: 100%;
        }

        #content {
            flex: 1 0 auto;
        }

        footer.sticky-footer {
            flex-shrink: 0;
            width: 100%;
            height: 36px;
            min-height: 36px;
            padding: 8px 0;
            background: #002a66;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: auto;
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

        /* ===== CUSTOM REPORT STYLES ===== */
        .stat-card {
            transition: all 0.3s ease;
            border-radius: 0.75rem;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.12) !important;
        }
        
        .stat-card .stat-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .stat-card .stat-icon.primary { background: rgba(78, 115, 223, 0.12); color: #4e73df; }
        .stat-card .stat-icon.success { background: rgba(28, 200, 138, 0.12); color: #1cc88a; }
        .stat-card .stat-icon.info { background: rgba(54, 185, 204, 0.12); color: #36b9cc; }
        .stat-card .stat-icon.warning { background: rgba(246, 194, 62, 0.12); color: #f6c23e; }
        .stat-card .stat-icon.danger { background: rgba(231, 74, 59, 0.12); color: #e74a3b; }
        
        .stat-card .stat-number {
            font-size: 1.75rem;
            font-weight: 800;
            color: #1e3c72;
            line-height: 1.2;
        }
        
        .stat-card .stat-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #858796;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-active { background: #dcfce7; color: #16a34a; }
        .badge-pending { background: #fef3c7; color: #d97706; }
        .badge-inactive { background: #fee2e2; color: #dc2626; }
        .badge-suspended { background: #f3f4f6; color: #6b7280; }

        .table-card .card-header {
            background: transparent;
            border-bottom: 2px solid #f0f0f0;
            padding: 1rem 1.25rem;
        }
        
        .table-card .card-header h6 {
            font-weight: 700;
            color: #1e3c72;
        }
        
        .table-card .table {
            font-size: 0.875rem;
            margin-bottom: 0;
        }
        
        .table-card .table thead th {
            background: #f8f9fc;
            color: #5a5c69;
            font-weight: 600;
            border-bottom: 2px solid #e3e6f0;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table-card .table tbody td {
            vertical-align: middle;
            padding: 0.6rem 0.75rem;
        }
        
        .table-card .table tbody tr:hover {
            background: #f8f9fc;
        }

        .chart-container {
            position: relative;
            height: 300px;
            min-height: 300px;
            width: 100%;
        }

        /* Report Generation Styles */
        .report-success {
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #16a34a;
            padding: 1rem 1.25rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .report-success a {
            color: #16a34a;
            font-weight: 600;
            text-decoration: underline;
        }

        .report-success a:hover {
            color: #0d7a30;
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
                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-chart-pie"></i> Client Reports
                    </h1>

                    <ul class="navbar-nav ml-auto">
                        <div class="topbar-divider d-none d-sm-block"></div>
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?php echo htmlspecialchars($first_name); ?>
                                </span>
                                <img class="img-profile rounded-circle" src="../img/undraw_profile.svg">
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
                                <a class="dropdown-item" href="../logout.php">
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
                            <a href="client-reports.php" class="btn btn-sm btn-secondary shadow-sm">
                                <i class="fas fa-arrow-left"></i> Back to Reports
                            </a>
                        </div>
                        <div>
                            <a href="?generate=1" class="btn btn-sm btn-success shadow-sm">
                                <i class="fas fa-file-export"></i> Generate Report
                            </a>
                        </div>
                    </div>

                    <!-- Report Generation Success Message -->
                    <?php if (isset($_SESSION['report_generated']) && $_SESSION['report_generated']): ?>
                    <div class="report-success">
                        <div>
                            <i class="fas fa-check-circle"></i>
                            <strong>Report Generated!</strong> 
                            <?php echo htmlspecialchars($_SESSION['report_filename']); ?>
                        </div>
                        <div>
                            <a href="<?php echo htmlspecialchars($_SESSION['report_url']); ?>" target="_blank" class="btn btn-sm btn-success">
                                <i class="fas fa-eye"></i> View Report
                            </a>
                            <a href="<?php echo htmlspecialchars($_SESSION['report_url']); ?>" download class="btn btn-sm btn-primary">
                                <i class="fas fa-download"></i> Download
                            </a>
                        </div>
                    </div>
                    <?php 
                    // Clear session after displaying
                    unset($_SESSION['report_generated']);
                    unset($_SESSION['report_filename']);
                    unset($_SESSION['report_url']);
                    endif; 
                    ?>

                    <!-- Stats Cards -->
                    <div class="row">
                        <!-- Total Clients -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card shadow stat-card border-left-primary">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon primary mr-3">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div>
                                            <div class="stat-number"><?php echo number_format($stats['total'] ?? 0); ?></div>
                                            <div class="stat-label">Total Clients</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Active Clients -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card shadow stat-card border-left-success">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon success mr-3">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                        <div>
                                            <div class="stat-number"><?php echo number_format($stats['active'] ?? 0); ?></div>
                                            <div class="stat-label">Active Clients</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Quote Value -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card shadow stat-card border-left-info">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon info mr-3">
                                            <i class="fas fa-rand"></i>
                                        </div>
                                        <div>
                                            <div class="stat-number">R <?php echo number_format($total_quote_value, 0); ?></div>
                                            <div class="stat-label">Total Quote Value</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Avg Quotes -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card shadow stat-card border-left-warning">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon warning mr-3">
                                            <i class="fas fa-file-invoice"></i>
                                        </div>
                                        <div>
                                            <div class="stat-number">R <?php echo number_format($avg_quotes, 0); ?></div>
                                            <div class="stat-label">Avg Quote Value</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row">
                        <!-- Status Distribution -->
                        <div class="col-xl-6 col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Client Status Distribution</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="statusChart"></canvas>
                                    </div>
                                    <div class="mt-3 text-center small">
                                        <?php foreach ($status_data as $status => $count): ?>
                                        <span class="mr-3">
                                            <i class="fas fa-circle" style="color: <?php 
                                                echo $status == 'active' ? '#1cc88a' : 
                                                    ($status == 'pending' ? '#f6c23e' : 
                                                    ($status == 'inactive' ? '#e74a3b' : '#858796')); 
                                            ?>;"></i>
                                            <?php echo ucfirst($status); ?>
                                        </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Client Type Distribution -->
                        <div class="col-xl-6 col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Client Type Distribution</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="typeChart"></canvas>
                                    </div>
                                    <div class="mt-3 text-center small">
                                        <?php foreach ($type_data as $type => $count): ?>
                                        <span class="mr-3">
                                            <i class="fas fa-circle" style="color: <?php 
                                                echo $type == 'business' ? '#4e73df' : 
                                                    ($type == 'individual' ? '#f6c23e' : 
                                                    ($type == 'government' ? '#e74a3b' : '#1cc88a')); 
                                            ?>;"></i>
                                            <?php echo ucfirst(str_replace('-', ' ', $type)); ?>
                                        </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Trend -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Monthly Client Registration Trend</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container" style="height: 250px; min-height: 250px;">
                                        <canvas id="monthlyChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Clients & Industry Distribution -->
                    <div class="row">
                        <!-- Top Clients by Quotes -->
                        <div class="col-lg-6">
                            <div class="card shadow table-card">
                                <div class="card-header">
                                    <h6 class="m-0"><i class="fas fa-trophy text-warning mr-2"></i> Top Clients by Quotes</h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Client</th>
                                                    <th>Code</th>
                                                    <th>Quotes</th>
                                                    <th>Total Value</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($top_clients)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-3">No clients with quotes yet.</td>
                                                </tr>
                                                <?php else: ?>
                                                <?php $rank = 1; foreach ($top_clients as $client): ?>
                                                <tr>
                                                    <td><?php echo $rank++; ?></td>
                                                    <td><?php echo htmlspecialchars($client['company_name']); ?></td>
                                                    <td><span class="badge badge-secondary"><?php echo htmlspecialchars($client['client_code']); ?></span></td>
                                                    <td><?php echo $client['quote_count']; ?></td>
                                                    <td><strong>R <?php echo number_format($client['total_value'] ?? 0, 2); ?></strong></td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Industry Distribution -->
                        <div class="col-lg-6">
                            <div class="card shadow table-card">
                                <div class="card-header">
                                    <h6 class="m-0"><i class="fas fa-building text-primary mr-2"></i> Industry Distribution</h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Industry</th>
                                                    <th>Count</th>
                                                    <th>%</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($industry_data)): ?>
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted py-3">No industry data available.</td>
                                                </tr>
                                                <?php else: ?>
                                                <?php $total = array_sum(array_column($industry_data, 'count')); ?>
                                                <?php foreach ($industry_data as $industry): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($industry['industry']); ?></td>
                                                    <td><?php echo $industry['count']; ?></td>
                                                    <td>
                                                        <div class="progress" style="height: 20px;">
                                                            <div class="progress-bar bg-primary" role="progressbar" 
                                                                 style="width: <?php echo ($total > 0) ? round(($industry['count'] / $total) * 100) : 0; ?>%;" 
                                                                 aria-valuenow="<?php echo ($total > 0) ? round(($industry['count'] / $total) * 100) : 0; ?>" 
                                                                 aria-valuemin="0" aria-valuemax="100">
                                                                <?php echo ($total > 0) ? round(($industry['count'] / $total) * 100) : 0; ?>%
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-clock mr-2"></i> Recent Activity
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Activity</th>
                                                    <th>Details</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($recent_activity)): ?>
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted py-3">No recent activity.</td>
                                                </tr>
                                                <?php else: ?>
                                                <?php foreach ($recent_activity as $activity): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge badge-success">
                                                            <i class="fas fa-user-plus"></i> New Client
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($activity['description']); ?></td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($activity['date'])); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
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

    <!-- Bootstrap core JavaScript-->
    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="../js/sb-admin-2.min.js"></script>

    <script>
    // Load sidebar from external file
    $(function() {
        $("#sidebar-container").load("../sidebar.html", function() {
            console.log("Sidebar loaded successfully");
            
            // Set active state for current page
            $('#sidebar-container .nav-item').removeClass('active');
            $('#sidebar-container .nav-item a[href*="reports"]').closest('.nav-item').addClass('active');
            
            // Find and highlight the parent menu if in submenu
            $('#sidebar-container .nav-item .collapse .collapse-item').each(function() {
                if ($(this).attr('href') && $(this).attr('href').includes('reports')) {
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

    // =============================================
    // CHARTS
    // =============================================

    // Status Distribution Chart
    var statusCtx = document.getElementById("statusChart");
    if (statusCtx) {
        var statusData = [
            <?php echo $status_data['active']; ?>, 
            <?php echo $status_data['pending']; ?>, 
            <?php echo $status_data['inactive']; ?>, 
            <?php echo $status_data['suspended']; ?>
        ];
        
        var statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ["Active", "Pending", "Inactive", "Suspended"],
                datasets: [{
                    data: statusData,
                    backgroundColor: ['#1cc88a', '#f6c23e', '#e74a3b', '#858796'],
                    hoverBackgroundColor: ['#17a673', '#dda20a', '#c0392b', '#6b6d7d'],
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }],
            },
            options: {
                maintainAspectRatio: false,
                tooltips: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyFontColor: "#858796",
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: false,
                    caretPadding: 10,
                    callbacks: {
                        label: function(tooltipItem, data) {
                            var dataset = data.datasets[tooltipItem.datasetIndex];
                            var total = dataset.data.reduce(function(previousValue, currentValue, currentIndex, array) {
                                return previousValue + currentValue;
                            });
                            var currentValue = dataset.data[tooltipItem.index];
                            var percentage = Math.floor(((currentValue/total) * 100) + 0.5);
                            return data.labels[tooltipItem.index] + ': ' + currentValue + ' (' + percentage + '%)';
                        }
                    }
                },
                legend: {
                    display: false
                },
                cutoutPercentage: 80,
            },
        });
    }

    // Client Type Distribution Chart
    var typeCtx = document.getElementById("typeChart");
    if (typeCtx) {
        var typeData = [
            <?php echo $type_data['business']; ?>, 
            <?php echo $type_data['individual']; ?>, 
            <?php echo $type_data['government']; ?>, 
            <?php echo $type_data['non_profit']; ?>
        ];
        
        var typeChart = new Chart(typeCtx, {
            type: 'doughnut',
            data: {
                labels: ["Business", "Individual", "Government", "Non-Profit"],
                datasets: [{
                    data: typeData,
                    backgroundColor: ['#4e73df', '#f6c23e', '#e74a3b', '#1cc88a'],
                    hoverBackgroundColor: ['#2e59d9', '#dda20a', '#c0392b', '#17a673'],
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }],
            },
            options: {
                maintainAspectRatio: false,
                tooltips: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyFontColor: "#858796",
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: false,
                    caretPadding: 10,
                    callbacks: {
                        label: function(tooltipItem, data) {
                            var dataset = data.datasets[tooltipItem.datasetIndex];
                            var total = dataset.data.reduce(function(previousValue, currentValue, currentIndex, array) {
                                return previousValue + currentValue;
                            });
                            var currentValue = dataset.data[tooltipItem.index];
                            var percentage = Math.floor(((currentValue/total) * 100) + 0.5);
                            return data.labels[tooltipItem.index] + ': ' + currentValue + ' (' + percentage + '%)';
                        }
                    }
                },
                legend: {
                    display: false
                },
                cutoutPercentage: 80,
            },
        });
    }

    // Monthly Trend Chart
    var monthlyCtx = document.getElementById("monthlyChart");
    if (monthlyCtx) {
        var labels = [];
        var data = [];
        <?php foreach ($monthly_data as $month): ?>
        labels.push("<?php echo $month['month']; ?>");
        data.push(<?php echo $month['count']; ?>);
        <?php endforeach; ?>
        
        var monthlyChart = new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: "New Clients",
                    lineTension: 0.3,
                    backgroundColor: "rgba(78, 115, 223, 0.05)",
                    borderColor: "rgba(78, 115, 223, 1)",
                    pointRadius: 3,
                    pointBackgroundColor: "rgba(78, 115, 223, 1)",
                    pointBorderColor: "rgba(78, 115, 223, 1)",
                    pointHoverRadius: 3,
                    pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                    pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    data: data,
                }],
            },
            options: {
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        left: 10,
                        right: 25,
                        top: 25,
                        bottom: 0
                    }
                },
                scales: {
                    xAxes: [{
                        time: {
                            unit: 'month'
                        },
                        gridLines: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            maxTicksLimit: 7
                        }
                    }],
                    yAxes: [{
                        ticks: {
                            maxTicksLimit: 5,
                            padding: 10,
                            stepSize: 1,
                            callback: function(value, index, values) {
                                return value;
                            }
                        },
                        gridLines: {
                            color: "rgb(234, 236, 244)",
                            zeroLineColor: "rgb(234, 236, 244)",
                            drawBorder: false,
                            borderDash: [2],
                            zeroLineBorderDash: [2]
                        }
                    }],
                },
                legend: {
                    display: false
                },
                tooltips: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyFontColor: "#858796",
                    titleMarginBottom: 10,
                    titleFontColor: '#6e707e',
                    titleFontSize: 14,
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: false,
                    intersect: false,
                    mode: 'index',
                    caretPadding: 10,
                    callbacks: {
                        label: function(tooltipItem, chart) {
                            var datasetLabel = chart.datasets[tooltipItem.datasetIndex].label || '';
                            return datasetLabel + ': ' + tooltipItem.yLabel;
                        }
                    }
                }
            }
        });
    }
    </script>
</body>
</html>