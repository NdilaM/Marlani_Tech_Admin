<?php
// sidebar.php — FRAGMENT (loaded via AJAX into #sidebar-container)
// Calls session_start() itself because AJAX is a separate HTTP request.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($staffType)) {
    $staffType = $_SESSION['staff_type'] ?? 'guest';
}
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;300;400;600;700;800;900&display=swap" rel="stylesheet">

<style>
/* ===== RESET & BASE ===== */
* { margin: 0; padding: 0; box-sizing: border-box; }

:root {
    --primary: #003986;
    --success: #1cc88a;
    --gray: #858796;
    --gray-dark: #5a5c69;
    --light: #f8f9fc;
}

html { position: relative; min-height: 100%; height: 100%; }

body {
    font-family: "Nunito", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    font-size: 1rem;
    font-weight: 400;
    line-height: 1.5;
    color: #858796;
    background-color: #fff;
    height: 100%;
    margin: 0;
}

a { color: #4e73df; text-decoration: none; }
a:hover { color: #224abe; text-decoration: underline; }

ul.navbar-nav { list-style: none; padding-left: 0; margin-bottom: 0; }

/* ===== UTILITIES ===== */
.bg-gradient-primary { background: linear-gradient(180deg, #003986 0%, #002a66 100%); }
.bg-white { background-color: #fff !important; }
.text-gray-600 { color: #858796 !important; }
.text-gray-800 { color: #5a5c69 !important; }
.text-gray-900 { color: #3a3b45 !important; }
.text-gray-300 { color: #dddfeb !important; }
.text-primary  { color: #003986 !important; }
.text-white-50 { color: rgba(255, 255, 255, 0.5) !important; }

.text-xs { font-size: .7rem; }
.text-uppercase { text-transform: uppercase !important; }
.text-center { text-align: center !important; }
.font-weight-bold { font-weight: 700 !important; }
.shadow { box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important; }

.d-flex { display: flex !important; }
.d-none { display: none !important; }
.d-md-inline { display: inline !important; }
.d-md-block { display: block !important; }
.align-items-center { align-items: center !important; }
.justify-content-center { justify-content: center !important; }

.mr-3 { margin-right: 1rem !important; }
.mb-2 { margin-bottom: 0.5rem !important; }
.my-0 { margin-top: 0 !important; margin-bottom: 0 !important; }
.py-2 { padding-top: 0.5rem !important; padding-bottom: 0.5rem !important; }
.p-0 { padding: 0 !important; }
.h-100 { height: 100% !important; }
.h5 { font-size: 1.25rem; font-weight: 400; line-height: 1.2; }
.small { font-size: 80%; font-weight: 400; }

.btn {
    display: inline-block;
    font-weight: 400;
    color: #858796;
    text-align: center;
    vertical-align: middle;
    user-select: none;
    background-color: transparent;
    border: 1px solid transparent;
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    line-height: 1.5;
    border-radius: 0.35rem;
    transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    cursor: pointer;
}
.btn:hover { color: #858796; text-decoration: none; }
.btn-success { color: #fff; background-color: #1cc88a; border-color: #1cc88a; }
.btn-success:hover { color: #fff; background-color: #17a673; border-color: #169b6b; }
.btn-sm { padding: 0.25rem 0.5rem; font-size: 0.875rem; line-height: 1.5; border-radius: 0.2rem; }

.rounded-circle { border-radius: 50% !important; }
.border-0 { border: 0 !important; }

/* ===== SIDEBAR ===== */
.sidebar {
    width: 6.5rem;
    min-height: 100vh;
    height: 100vh;
    top: 0;
    left: 0;
    z-index: 100;
    overflow-y: auto;
    overflow-x: hidden;
    transition: width 0.3s ease;
    box-shadow: 2px 0 15px rgba(0, 0, 0, 0.15);
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    position: sticky;
    align-self: flex-start;
}

.sidebar .sidebar-brand {
    height: 4.375rem;
    text-decoration: none;
    font-size: 1rem;
    font-weight: 800;
    padding: 0.75rem 1rem;
    text-align: center;
    z-index: 1;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    transition: all 0.3s ease;
    flex-shrink: 0;
}
.sidebar .sidebar-brand .sidebar-brand-icon i { font-size: 2rem; }
.sidebar .sidebar-brand .sidebar-brand-text { display: none; font-size: 0.85rem; letter-spacing: 1px; }

.sidebar-brand-image {
    height: 42px;
    width: auto;
    max-width: 100%;
    object-fit: contain;
    display: block;
    transition: all 0.3s ease;
}
.sidebar-brand-image:hover { transform: scale(1.05); }

.sidebar .sidebar-nav {
    flex: 1 1 auto;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 0.5rem 0;
    min-height: 0;
}

.sidebar hr.sidebar-divider {
    margin: 0.5rem 0.8rem;
    border: 0;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    flex-shrink: 0;
}

.sidebar .sidebar-heading {
    text-align: center;
    padding: 0.5rem 0.5rem;
    font-weight: 700;
    font-size: 0.55rem;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.3);
    letter-spacing: 0.5px;
}

.sidebar .nav-item {
    position: relative;
    margin: 2px 8px;
    border-radius: 8px;
    transition: all 0.2s ease;
    list-style: none;
}
.sidebar .nav-item:last-child { margin-bottom: 0.5rem; }

.sidebar .nav-item .nav-link {
    text-align: center;
    padding: 0.75rem 0.5rem;
    width: 100%;
    color: rgba(255, 255, 255, 0.7);
    display: flex;
    flex-direction: column;
    align-items: center;
    border-radius: 8px;
    transition: all 0.2s ease;
    font-size: 0.85rem;
    cursor: pointer;
    text-decoration: none;
}
.sidebar .nav-item .nav-link i {
    font-size: 1.3rem;
    margin-bottom: 4px;
    color: rgba(255, 255, 255, 0.5);
    transition: all 0.2s ease;
}
.sidebar .nav-item .nav-link span {
    font-size: 0.6rem;
    display: block;
    font-weight: 500;
    letter-spacing: 0.3px;
}
.sidebar .nav-item .nav-link:hover {
    color: #fff;
    background: rgba(255, 255, 255, 0.08);
    text-decoration: none;
}
.sidebar .nav-item .nav-link:hover i { color: #fff; transform: scale(1.1); }
.sidebar .nav-item.active .nav-link {
    color: #fff;
    background: rgba(255, 255, 255, 0.15);
    font-weight: 600;
}
.sidebar .nav-item.active .nav-link i { color: #fff; }

/* Collapse */
.sidebar .nav-item .collapse {
    display: none;
    position: absolute;
    left: calc(6.5rem + 1.5rem / 2);
    z-index: 1050;
    top: 2px;
}
.sidebar .nav-item .collapse.show {
    display: block;
}
.sidebar .nav-item .collapsing {
    display: block;
    height: 0;
    overflow: hidden;
    transition: height 0.2s ease;
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
.sidebar .nav-item .collapse .collapse-inner .collapse-header {
    margin: 0;
    white-space: nowrap;
    padding: 0.5rem 1.5rem;
    text-transform: uppercase;
    font-weight: 800;
    font-size: 0.65rem;
    color: #b7b9cc;
    border-bottom: 1px solid #e3e6f0;
}
.sidebar .nav-item .collapse .collapse-inner .collapse-item {
    padding: 0.5rem 1.5rem;
    margin: 0;
    display: block;
    color: #3a3b45;
    text-decoration: none;
    white-space: nowrap;
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
    font-weight: 500;
}
.sidebar .nav-item .collapse .collapse-inner .collapse-item:hover {
    background: #f0f4ff;
    color: #003986;
    text-decoration: none;
    border-left-color: #003986;
}
.sidebar .nav-item .collapse .collapse-inner .collapse-item.active {
    color: #ffffff;
    background: #003986;
    font-weight: 700;
    border-left-color: #ffffff;
}

/* Sidebar card */
.sidebar .sidebar-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    font-size: 0.75rem;
    border-radius: 10px;
    color: rgba(255, 255, 255, 0.8);
    margin: 0.5rem 0.8rem;
    padding: 1rem 0.8rem;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(4px);
    transition: all 0.3s ease;
    flex-shrink: 0;
}
.sidebar .sidebar-card:hover { background: rgba(255, 255, 255, 0.1); }

/* Sidebar Toggle */
.sidebar #sidebarToggle {
    width: 2.2rem;
    height: 2.2rem;
    text-align: center;
    margin: 0.5rem auto;
    cursor: pointer;
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    border-radius: 50%;
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
    flex-shrink: 0;
}
.sidebar #sidebarToggle:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: rotate(90deg);
}
.sidebar #sidebarToggle::after {
    font-weight: 900;
    content: '\f104';
    font-family: 'Font Awesome 6 Free';
    margin-right: 0.1rem;
}
.sidebar.toggled #sidebarToggle::after {
    content: '\f105';
    font-family: 'Font Awesome 6 Free';
    margin-left: 0.25rem;
}

.sidebar.toggled .sidebar-card { display: none; }

/* Dark theme */
.sidebar-dark .sidebar-brand { color: #fff; }
.sidebar-dark .nav-item .nav-link { color: rgba(255, 255, 255, 0.7); }
.sidebar-dark .nav-item .nav-link i { color: rgba(255, 255, 255, 0.5); }
.sidebar-dark .nav-item .nav-link:hover { color: #fff; }
.sidebar-dark .nav-item .nav-link:hover i { color: #fff; }
.sidebar-dark .nav-item.active .nav-link { color: #fff; }
.sidebar-dark .nav-item.active .nav-link i { color: #fff; }
.sidebar-dark .sidebar-heading { color: rgba(255, 255, 255, 0.3); }

.rotate-n-15 { transform: rotate(-15deg); }

/* ===== RESPONSIVE (Desktop) ===== */
@media (min-width: 768px) {
    .sidebar { width: 14rem !important; }

    .sidebar .nav-item { margin: 2px 12px; }

    .sidebar .nav-item .nav-link {
        flex-direction: row;
        padding: 0.7rem 1rem;
        text-align: left;
        gap: 12px;
    }
    .sidebar .nav-item .nav-link i {
        font-size: 1rem;
        margin-bottom: 0;
        width: 1.5rem;
        text-align: center;
    }
    .sidebar .nav-item .nav-link span { font-size: 0.85rem; display: inline; }

    .sidebar .sidebar-brand .sidebar-brand-icon i {
        font-size: 1.8rem;
        display: inline-block;
        margin-bottom: 0;
        margin-right: 8px;
    }
    .sidebar .sidebar-brand .sidebar-brand-text { display: inline; font-size: 0.85rem; }

    .sidebar .sidebar-heading {
        text-align: left;
        padding: 0.5rem 1.2rem;
        font-size: 0.6rem;
    }

    .sidebar .sidebar-card { margin: 0.5rem 1rem; padding: 1rem; }

    .sidebar .nav-item .collapse {
        position: relative;
        left: 0;
        z-index: 1;
        top: 0;
        display: none;
    }
    .sidebar .nav-item .collapse.show {
        display: block;
    }
    .sidebar .nav-item .collapse .collapse-inner {
        border-radius: 0;
        box-shadow: none;
        background: transparent;
        border: none;
        padding: 0.25rem 0;
        min-width: auto;
    }
    .sidebar .nav-item .collapse .collapse-inner .collapse-header {
        color: rgba(255, 255, 255, 0.4);
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        padding: 0.5rem 1rem;
        font-size: 0.6rem;
    }
    .sidebar .nav-item .collapse .collapse-inner .collapse-item {
        color: rgba(255, 255, 255, 0.7);
        padding: 0.4rem 1rem;
        border-left: 3px solid transparent;
        font-weight: 400;
    }
    .sidebar .nav-item .collapse .collapse-inner .collapse-item:hover {
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
        border-left-color: #fff;
    }
    .sidebar .nav-item .collapse .collapse-inner .collapse-item.active {
        background: rgba(255, 255, 255, 0.15);
        color: #fff;
        border-left-color: #fff;
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
        font-family: 'Font Awesome 6 Free';
        margin-left: auto;
    }
    .sidebar .nav-item .nav-link[data-toggle="collapse"].collapsed::after {
        content: '\f105';
    }

    .sidebar.toggled {
        overflow: visible;
        width: 6.5rem !important;
    }
    .sidebar.toggled .nav-item { margin: 2px 8px; }
    .sidebar.toggled .nav-item .nav-link {
        flex-direction: column;
        padding: 0.75rem 0.5rem;
        text-align: center;
        gap: 2px;
    }
    .sidebar.toggled .nav-item .nav-link i {
        font-size: 1.3rem;
        margin-bottom: 2px;
        width: auto;
    }
    .sidebar.toggled .nav-item .nav-link span { font-size: 0.6rem; display: block; }
    .sidebar.toggled .nav-item .nav-link[data-toggle="collapse"]::after { display: none; }

    .sidebar.toggled .nav-item .collapse {
        position: absolute;
        left: calc(6.5rem + 0.5rem);
        z-index: 1050;
        top: 0;
        margin: 0;
    }
    .sidebar.toggled .nav-item .collapse.show { display: block; }
    .sidebar.toggled .nav-item .collapse .collapse-inner {
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        border-radius: 0.35rem;
        background: #ffffff;
        border: 1px solid #e3e6f0;
        min-width: 12rem;
        padding: 0.5rem 0;
    }
    .sidebar.toggled .nav-item .collapse .collapse-inner .collapse-header {
        color: #b7b9cc;
        border-bottom: 1px solid #e3e6f0;
        padding: 0.5rem 1.5rem;
    }
    .sidebar.toggled .nav-item .collapse .collapse-inner .collapse-item {
        color: #3a3b45;
        padding: 0.5rem 1.5rem;
        border-left: 3px solid transparent;
    }
    .sidebar.toggled .nav-item .collapse .collapse-inner .collapse-item:hover {
        background: #f0f4ff;
        color: #003986;
        border-left-color: #003986;
    }
    .sidebar.toggled .nav-item .collapse .collapse-inner .collapse-item.active {
        background: #003986;
        color: #ffffff;
        border-left-color: #ffffff;
    }

    .sidebar.toggled .sidebar-brand .sidebar-brand-icon i {
        font-size: 2rem;
        display: block;
        margin-right: 0;
        margin-bottom: 4px;
    }
    .sidebar.toggled .sidebar-brand .sidebar-brand-text { display: none; }
    .sidebar.toggled .sidebar-heading { text-align: center; }
    .sidebar.toggled .sidebar-card { display: none; }
}

/* ===== RESPONSIVE (Mobile) ===== */
@media (max-width: 768px) {
    .sidebar {
        width: 0 !important;
        overflow: hidden;
        transition: width 0.3s ease;
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        z-index: 1050;
    }
    .sidebar.toggled {
        width: 14rem !important;
        overflow-y: auto;
        box-shadow: 4px 0 20px rgba(0, 0, 0, 0.2);
    }
    .sidebar.toggled .nav-item .nav-link {
        flex-direction: row;
        padding: 0.7rem 1rem;
        text-align: left;
        gap: 12px;
    }
    .sidebar.toggled .nav-item .nav-link i {
        font-size: 1rem;
        margin-bottom: 0;
        width: 1.5rem;
        text-align: center;
    }
    .sidebar.toggled .nav-item .nav-link span { font-size: 0.85rem; display: inline; }

    .sidebar.toggled .sidebar-brand .sidebar-brand-icon i {
        font-size: 1.8rem;
        display: inline-block;
        margin-bottom: 0;
        margin-right: 8px;
    }
    .sidebar.toggled .sidebar-brand .sidebar-brand-text { display: inline; font-size: 0.85rem; }
    .sidebar.toggled .sidebar-heading { text-align: left; padding: 0.5rem 1.2rem; }

    .sidebar.toggled .nav-item .collapse {
        position: relative;
        left: 0;
        top: 0;
        display: none;
    }
    .sidebar.toggled .nav-item .collapse.show { display: block; }
    .sidebar.toggled .nav-item .collapse .collapse-inner {
        background: rgba(255, 255, 255, 0.05);
        box-shadow: none;
        border: none;
        padding: 0.25rem 0;
        min-width: auto;
    }
    .sidebar.toggled .nav-item .collapse .collapse-inner .collapse-header {
        color: rgba(255, 255, 255, 0.4);
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    .sidebar.toggled .nav-item .collapse .collapse-inner .collapse-item {
        color: rgba(255, 255, 255, 0.7);
        padding: 0.4rem 1.5rem;
        border-left: 3px solid transparent;
    }
    .sidebar.toggled .nav-item .collapse .collapse-inner .collapse-item:hover {
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
        border-left-color: #fff;
    }
    .sidebar.toggled .nav-item .collapse .collapse-inner .collapse-item.active {
        background: rgba(255, 255, 255, 0.15);
        color: #fff;
        border-left-color: #fff;
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

.sidebar::-webkit-scrollbar { width: 4px; }
.sidebar::-webkit-scrollbar-track { background: transparent; }
.sidebar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
}
.sidebar::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.3); }

/* Pre-hide items with data-role until JS reveals them */
#accordionSidebar [data-role] { visibility: hidden; }
#accordionSidebar [data-role].role-visible { visibility: visible; }
</style>

<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion"
    id="accordionSidebar"
    data-staff-type="<?= htmlspecialchars($staffType, ENT_QUOTES, 'UTF-8') ?>">

    <!-- Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center"
       href="/Marlani_Tech_Admin/index.php">
        <img src="/Marlani_Tech_Admin/img/company_logo.png"
             alt="Brand Logo"
             class="sidebar-brand-image">
    </a>

    <hr class="sidebar-divider my-0">

    <!-- Scrollable nav wrapper -->
    <div class="sidebar-nav">

        <!-- Dashboard – all roles -->
        <li class="nav-item active" data-role="admin,manager,staff">
            <a class="nav-link" href="/Marlani_Tech_Admin/index.php">
                <i class="fas fa-fw fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <hr class="sidebar-divider">
        <div class="sidebar-heading">Interface</div>

        <!-- Client Management – admin & manager -->
        <li class="nav-item" data-role="admin,manager">
            <a class="nav-link collapsed" href="#" data-toggle="collapse"
               data-target="#collapseClients" aria-expanded="false"
               aria-controls="collapseClients">
                <i class="fas fa-fw fa-wrench"></i>
                <span>Client Management</span>
            </a>
            <div id="collapseClients" class="collapse" aria-labelledby="headingClients"
                 data-parent="#accordionSidebar">
                <div class="py-2 collapse-inner rounded">
                    <h6 class="collapse-header">Client Options:</h6>
                    <a class="collapse-item" href="/Marlani_Tech_Admin/clients/clients.php">Clients</a>
                    <a class="collapse-item" href="/Marlani_Tech_Admin/clients/client-add.php">Add Client</a>
                    <a class="collapse-item" href="/Marlani_Tech_Admin/clients/client-reports.php">Reports</a>
                </div>
            </div>
        </li>

         <hr class="sidebar-divider">
        <!-- Staff Management – admin only -->
        <li class="nav-item" data-role="admin">
            <a class="nav-link collapsed" href="#" data-toggle="collapse"
               data-target="#collapseStaff" aria-expanded="false"
               aria-controls="collapseStaff">
                <i class="fa-solid fa-user"></i>
                <span>Staff Management</span>
            </a>
            <div id="collapseStaff" class="collapse" aria-labelledby="headingStaff"
                 data-parent="#accordionSidebar">
                <div class="py-2 collapse-inner rounded">
                    <h6 class="collapse-header">Staff Options:</h6>
                    <a class="collapse-item" href="/Marlani_Tech_Admin/Staff/view-staff.php">View Staff</a>
                    <a class="collapse-item" href="/Marlani_Tech_Admin/Staff/staff-add.php">Add Staff</a>
                    <a class="collapse-item" href="/Marlani_Tech_Admin/Staff/update-staff.php">Update Staff</a>
                </div>
            </div>
        </li>

        <hr class="sidebar-divider">
        <!-- Finance – admin & manager only -->
        <li class="nav-item" data-role="admin,manager,superadmin">
            <a class="nav-link collapsed" href="#" data-toggle="collapse"
            data-target="#collapseFinance" aria-expanded="false"
            aria-controls="collapseFinance">
                <i class="fas fa-dollar-sign"></i>
                <span>Finance</span>
            </a>
            <div id="collapseFinance" class="collapse" aria-labelledby="headingFinance"
                data-parent="#accordionSidebar">
                <div class="py-2 collapse-inner rounded">
                    <h6 class="collapse-header">Finance Options:</h6>
                    <a class="collapse-item" href="/Marlani_Tech_Admin/Finances/Overview.php">Overview</a>
                    <a class="collapse-item" href="/Marlani_Tech_Admin/Finances/Invoices.php">Invoices</a>
                    <a class="collapse-item" href="/Marlani_Tech_Admin/Finances/Outstanding.php">Outstanding</a>
                    <a class="collapse-item" href="/Marlani_Tech_Admin/Finances/Payments.php">Payments</a>
                    <a class="collapse-item" href="/Marlani_Tech_Admin/Finances/finance_reports.php">Reports</a>
                    <a class="collapse-item" href="/Marlani_Tech_Admin/Finances/salaries.php">Salaries</a>
                </div>
            </div>
        </li>

        <hr class="sidebar-divider">
        <!-- Support – all roles -->
        <li class="nav-item" data-role="admin,manager,staff">
            <a class="nav-link collapsed" href="#" data-toggle="collapse"
            data-target="#collapseSupport" aria-expanded="false"
            aria-controls="collapseSupport">
                <i class="fas fa-headset"></i>
                <span>Support</span>
            </a>
            <div id="collapseSupport" class="collapse" aria-labelledby="headingSupport"
                data-parent="#accordionSidebar">
                <div class="py-2 collapse-inner rounded">
                    <h6 class="collapse-header">Support Options:</h6>

                    <!-- Everyone -->
                    <a class="collapse-item"
                    href="/Marlani_Tech_Admin/Support/create_ticket.php">Create Ticket</a>

                    <!-- Admin / manager only -->
                    <a class="collapse-item" data-role="admin,manager,superadmin"
                    href="/Marlani_Tech_Admin/Support/support.php">View Tickets</a>
                </div>
            </div>
        </li>

        <hr class="sidebar-divider">

        <div class="sidebar-heading">Addons</div>

        <!-- Pages – all roles -->
        <li class="nav-item" data-role="admin,manager,staff">
            <a class="nav-link collapsed" href="#" data-toggle="collapse"
               data-target="#collapsePages" aria-expanded="false"
               aria-controls="collapsePages">
                <i class="fas fa-fw fa-folder"></i>
                <span>Pages</span>
            </a>
            <div id="collapsePages" class="collapse" aria-labelledby="headingPages"
                 data-parent="#accordionSidebar">
                <div class="py-2 collapse-inner rounded">
                    <h6 class="collapse-header">Login Screens:</h6>
                    <a class="collapse-item" href="/Marlani_Tech_Admin/login.html">Login</a>
                    <a class="collapse-item" href="/Marlani_Tech_Admin/register.html">Register</a>
                    <a class="collapse-item" href="/Marlani_Tech_Admin/forgot-password.html">Forgot Password</a>
                    <div class="collapse-divider"></div>
                    <h6 class="collapse-header">Other Pages:</h6>
                    <a class="collapse-item" href="/Marlani_Tech_Admin/404.html">404 Page</a>
                    <a class="collapse-item" href="/Marlani_Tech_Admin/blank.html">Blank Page</a>
                </div>
            </div>
        </li>



        <!-- Charts – admin & manager -->
        <li class="nav-item" data-role="admin,manager">
            <a class="nav-link" href="/Marlani_Tech_Admin/charts.html">
                <i class="fas fa-fw fa-chart-area"></i>
                <span>Charts</span>
            </a>
        </li>

        <!-- Tables – all roles -->
        <li class="nav-item" data-role="admin,manager,staff">
            <a class="nav-link" href="/Marlani_Tech_Admin/tables.html">
                <i class="fas fa-fw fa-table"></i>
                <span>Tables</span>
            </a>
        </li>

        <hr class="sidebar-divider d-none d-md-block">

    </div><!-- /.sidebar-nav -->

    <!-- Sidebar Toggle -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle" aria-label="Toggle sidebar"></button>
    </div>

</ul>

<script>
(function () {
    /* ==========================================================
       1. ROLE-BASED VISIBILITY — reads from the <ul> itself
       ========================================================== */
    var ALL_ACCESS_ROLES = ['admin', 'superadmin'];

    var sidebarEl = document.getElementById('accordionSidebar');
    if (!sidebarEl) return;

    var currentStaffType = (sidebarEl.dataset.staffType || 'guest')
                           .toLowerCase().trim();

    var hasFullAccess = ALL_ACCESS_ROLES.indexOf(currentStaffType) !== -1;

    sidebarEl.querySelectorAll('[data-role]').forEach(function (el) {
        var allowed = el.getAttribute('data-role')
                        .split(',')
                        .map(function (s) { return s.trim().toLowerCase(); });

        if (hasFullAccess || allowed.indexOf(currentStaffType) !== -1) {
            el.classList.add('role-visible');   // reveal
        } else {
            el.style.display = 'none';          // hide
            el.classList.remove('role-visible');
        }
    });

    /* ==========================================================
       2. SIDEBAR TOGGLE + ACCORDION
       ========================================================== */
    var sidebar = sidebarEl;

    var toggleBtn = document.getElementById('sidebarToggle');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function (e) {
            e.preventDefault();
            document.body.classList.toggle('sidebar-toggled');
            sidebar.classList.toggle('toggled');
        });
    }

    sidebar.querySelectorAll('[data-toggle="collapse"]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var targetSel = this.getAttribute('data-target');
            var target = document.querySelector(targetSel);
            if (!target) return;

            var isOpen = target.classList.contains('show');

            sidebar.querySelectorAll('.collapse.show').forEach(function (open) {
                if (open !== target) {
                    open.classList.remove('show');
                    var sibLink = open.parentElement.querySelector('[data-toggle="collapse"]');
                    if (sibLink) {
                        sibLink.classList.add('collapsed');
                        sibLink.setAttribute('aria-expanded', 'false');
                    }
                }
            });

            if (isOpen) {
                target.classList.remove('show');
                this.classList.add('collapsed');
                this.setAttribute('aria-expanded', 'false');
            } else {
                target.classList.add('show');
                this.classList.remove('collapsed');
                this.setAttribute('aria-expanded', 'true');
            }
        });
    });
})();
</script>