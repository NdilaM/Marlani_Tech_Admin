<?php
// clients.php - Clients Dashboard
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.html');
    exit();
}

require_once 'db.php';
$first_name = $_SESSION['first_name'] ?? 'User';

// Get all clients
$stmt = $conn->query("
    SELECT c.*
    FROM clients c 
    ORDER BY c.company_name
");
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stmt = $conn->query("SELECT COUNT(*) as total, 
                      SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                      SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
                      SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
                      FROM clients");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$total_clients = $stats['total'] ?? 0;
$active_clients = $stats['active'] ?? 0;
$inactive_clients = $stats['inactive'] ?? 0;
$pending_clients = $stats['pending'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Marlani Admin - Clients</title>

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

h1, h2, h3, h4, h5, h6 {
  margin-top: 0;
  margin-bottom: 0.5rem;
}

p {
  margin-top: 0;
  margin-bottom: 1rem;
}

a {
  color: #4e73df;
  text-decoration: none;
  background-color: transparent;
}

a:hover {
  color: #224abe;
  text-decoration: underline;
}

img {
  vertical-align: middle;
  border-style: none;
}

button {
  border-radius: 0;
}

button:focus:not(:focus-visible) {
  outline: 0;
}

input, button, select, optgroup, textarea {
  margin: 0;
  font-family: inherit;
  font-size: inherit;
  line-height: inherit;
}

button, input {
  overflow: visible;
}

button, select {
  text-transform: none;
}

button:not(:disabled), [type="button"]:not(:disabled), [type="reset"]:not(:disabled), [type="submit"]:not(:disabled) {
  cursor: pointer;
}

h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6 {
  margin-bottom: 0.5rem;
  font-weight: 400;
  line-height: 1.2;
}

h1, .h1 { font-size: 2.5rem; }
h2, .h2 { font-size: 2rem; }
h3, .h3 { font-size: 1.75rem; }
h4, .h4 { font-size: 1.5rem; }
h5, .h5 { font-size: 1.25rem; }
h6, .h6 { font-size: 1rem; }

hr {
  margin-top: 1rem;
  margin-bottom: 1rem;
  border: 0;
  border-top: 1px solid rgba(0, 0, 0, 0.1);
}

small, .small {
  font-size: 80%;
  font-weight: 400;
}

.container, .container-fluid {
  width: 100%;
  padding-right: 0.75rem;
  padding-left: 0.75rem;
  margin-right: auto;
  margin-left: auto;
}

.row {
  display: flex;
  flex-wrap: wrap;
  margin-right: -0.75rem;
  margin-left: -0.75rem;
}

.no-gutters {
  margin-right: 0;
  margin-left: 0;
}

.no-gutters > .col,
.no-gutters > [class*="col-"] {
  padding-right: 0;
  padding-left: 0;
}

.col-1, .col-2, .col-3, .col-4, .col-5, .col-6, .col-7, .col-8, .col-9, .col-10, .col-11, .col-12, .col,
.col-auto, .col-sm-1, .col-sm-2, .col-sm-3, .col-sm-4, .col-sm-5, .col-sm-6, .col-sm-7, .col-sm-8, .col-sm-9, .col-sm-10, .col-sm-11, .col-sm-12, .col-sm,
.col-sm-auto, .col-md-1, .col-md-2, .col-md-3, .col-md-4, .col-md-5, .col-md-6, .col-md-7, .col-md-8, .col-md-9, .col-md-10, .col-md-11, .col-md-12, .col-md,
.col-md-auto, .col-lg-1, .col-lg-2, .col-lg-3, .col-lg-4, .col-lg-5, .col-lg-6, .col-lg-7, .col-lg-8, .col-lg-9, .col-lg-10, .col-lg-11, .col-lg-12, .col-lg,
.col-lg-auto, .col-xl-1, .col-xl-2, .col-xl-3, .col-xl-4, .col-xl-5, .col-xl-6, .col-xl-7, .col-xl-8, .col-xl-9, .col-xl-10, .col-xl-11, .col-xl-12, .col-xl,
.col-xl-auto {
  position: relative;
  width: 100%;
  padding-right: 0.75rem;
  padding-left: 0.75rem;
}

.col {
  flex-basis: 0;
  flex-grow: 1;
  max-width: 100%;
}

.col-auto {
  flex: 0 0 auto;
  width: auto;
  max-width: 100%;
}

.col-1 { flex: 0 0 8.33333%; max-width: 8.33333%; }
.col-2 { flex: 0 0 16.66667%; max-width: 16.66667%; }
.col-3 { flex: 0 0 25%; max-width: 25%; }
.col-4 { flex: 0 0 33.33333%; max-width: 33.33333%; }
.col-5 { flex: 0 0 41.66667%; max-width: 41.66667%; }
.col-6 { flex: 0 0 50%; max-width: 50%; }
.col-7 { flex: 0 0 58.33333%; max-width: 58.33333%; }
.col-8 { flex: 0 0 66.66667%; max-width: 66.66667%; }
.col-9 { flex: 0 0 75%; max-width: 75%; }
.col-10 { flex: 0 0 83.33333%; max-width: 83.33333%; }
.col-11 { flex: 0 0 91.66667%; max-width: 91.66667%; }
.col-12 { flex: 0 0 100%; max-width: 100%; }

@media (min-width: 576px) {
  .col-sm { flex-basis: 0; flex-grow: 1; max-width: 100%; }
  .col-sm-auto { flex: 0 0 auto; width: auto; max-width: 100%; }
  .col-sm-1 { flex: 0 0 8.33333%; max-width: 8.33333%; }
  .col-sm-2 { flex: 0 0 16.66667%; max-width: 16.66667%; }
  .col-sm-3 { flex: 0 0 25%; max-width: 25%; }
  .col-sm-4 { flex: 0 0 33.33333%; max-width: 33.33333%; }
  .col-sm-5 { flex: 0 0 41.66667%; max-width: 41.66667%; }
  .col-sm-6 { flex: 0 0 50%; max-width: 50%; }
  .col-sm-7 { flex: 0 0 58.33333%; max-width: 58.33333%; }
  .col-sm-8 { flex: 0 0 66.66667%; max-width: 66.66667%; }
  .col-sm-9 { flex: 0 0 75%; max-width: 75%; }
  .col-sm-10 { flex: 0 0 83.33333%; max-width: 83.33333%; }
  .col-sm-11 { flex: 0 0 91.66667%; max-width: 91.66667%; }
  .col-sm-12 { flex: 0 0 100%; max-width: 100%; }
}

@media (min-width: 768px) {
  .col-md { flex-basis: 0; flex-grow: 1; max-width: 100%; }
  .col-md-auto { flex: 0 0 auto; width: auto; max-width: 100%; }
  .col-md-1 { flex: 0 0 8.33333%; max-width: 8.33333%; }
  .col-md-2 { flex: 0 0 16.66667%; max-width: 16.66667%; }
  .col-md-3 { flex: 0 0 25%; max-width: 25%; }
  .col-md-4 { flex: 0 0 33.33333%; max-width: 33.33333%; }
  .col-md-5 { flex: 0 0 41.66667%; max-width: 41.66667%; }
  .col-md-6 { flex: 0 0 50%; max-width: 50%; }
  .col-md-7 { flex: 0 0 58.33333%; max-width: 58.33333%; }
  .col-md-8 { flex: 0 0 66.66667%; max-width: 66.66667%; }
  .col-md-9 { flex: 0 0 75%; max-width: 75%; }
  .col-md-10 { flex: 0 0 83.33333%; max-width: 83.33333%; }
  .col-md-11 { flex: 0 0 91.66667%; max-width: 91.66667%; }
  .col-md-12 { flex: 0 0 100%; max-width: 100%; }
}

@media (min-width: 992px) {
  .col-lg { flex-basis: 0; flex-grow: 1; max-width: 100%; }
  .col-lg-auto { flex: 0 0 auto; width: auto; max-width: 100%; }
  .col-lg-1 { flex: 0 0 8.33333%; max-width: 8.33333%; }
  .col-lg-2 { flex: 0 0 16.66667%; max-width: 16.66667%; }
  .col-lg-3 { flex: 0 0 25%; max-width: 25%; }
  .col-lg-4 { flex: 0 0 33.33333%; max-width: 33.33333%; }
  .col-lg-5 { flex: 0 0 41.66667%; max-width: 41.66667%; }
  .col-lg-6 { flex: 0 0 50%; max-width: 50%; }
  .col-lg-7 { flex: 0 0 58.33333%; max-width: 58.33333%; }
  .col-lg-8 { flex: 0 0 66.66667%; max-width: 66.66667%; }
  .col-lg-9 { flex: 0 0 75%; max-width: 75%; }
  .col-lg-10 { flex: 0 0 83.33333%; max-width: 83.33333%; }
  .col-lg-11 { flex: 0 0 91.66667%; max-width: 91.66667%; }
  .col-lg-12 { flex: 0 0 100%; max-width: 100%; }
}

@media (min-width: 1200px) {
  .col-xl { flex-basis: 0; flex-grow: 1; max-width: 100%; }
  .col-xl-auto { flex: 0 0 auto; width: auto; max-width: 100%; }
  .col-xl-1 { flex: 0 0 8.33333%; max-width: 8.33333%; }
  .col-xl-2 { flex: 0 0 16.66667%; max-width: 16.66667%; }
  .col-xl-3 { flex: 0 0 25%; max-width: 25%; }
  .col-xl-4 { flex: 0 0 33.33333%; max-width: 33.33333%; }
  .col-xl-5 { flex: 0 0 41.66667%; max-width: 41.66667%; }
  .col-xl-6 { flex: 0 0 50%; max-width: 50%; }
  .col-xl-7 { flex: 0 0 58.33333%; max-width: 58.33333%; }
  .col-xl-8 { flex: 0 0 66.66667%; max-width: 66.66667%; }
  .col-xl-9 { flex: 0 0 75%; max-width: 75%; }
  .col-xl-10 { flex: 0 0 83.33333%; max-width: 83.33333%; }
  .col-xl-11 { flex: 0 0 91.66667%; max-width: 91.66667%; }
  .col-xl-12 { flex: 0 0 100%; max-width: 100%; }
}

.form-control {
  display: block;
  width: 100%;
  padding: 0.375rem 0.75rem;
  font-size: 1rem;
  font-weight: 400;
  line-height: 1.5;
  color: #6e707e;
  background-color: #fff;
  background-clip: padding-box;
  border: 1px solid #d1d3e2;
  border-radius: 0.35rem;
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus {
  color: #6e707e;
  background-color: #fff;
  border-color: #bac8f3;
  outline: 0;
  box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
}

.form-control::placeholder {
  color: #858796;
  opacity: 1;
}

.form-group {
  margin-bottom: 1rem;
}

.btn {
  display: inline-block;
  font-weight: 400;
  color: #858796;
  text-align: center;
  vertical-align: middle;
  user-select: none;
  border: 1px solid transparent;
  padding: 0.375rem 0.75rem;
  font-size: 1rem;
  line-height: 1.5;
  border-radius: 0.35rem;
  transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.btn:hover {
  color: #858796;
  text-decoration: none;
}

.btn-primary {
  color: #fff;
  background-color: #4e73df;
  border-color: #4e73df;
}

.btn-primary:hover {
  color: #fff;
  background-color: #2e59d9;
  border-color: #2653d4;
}

.btn-success {
  color: #fff;
  background-color: #1cc88a;
  border-color: #1cc88a;
}

.btn-success:hover {
  color: #fff;
  background-color: #17a673;
  border-color: #169b6b;
}

.btn-secondary {
  color: #fff;
  background-color: #858796;
  border-color: #858796;
}

.btn-secondary:hover {
  color: #fff;
  background-color: #717384;
  border-color: #6b6d7d;
}

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
  line-height: 1.5;
  border-radius: 0.2rem;
}

.btn-block {
  display: block;
  width: 100%;
}

.btn-block + .btn-block {
  margin-top: 0.5rem;
}

.btn-generate {
    color: #fff !important;
    background-color: #4e73df;
    border-color: #4e73df;
    transition: all 0.3s ease;
}

.btn-generate:hover {
    background-color: #2e59d9;
    color: #fff !important;
    border-color: #2653d4;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.fade {
  transition: opacity 0.15s linear;
}

.fade:not(.show) {
  opacity: 0;
}

.collapse:not(.show) {
  display: none;
}

.dropdown {
  position: relative;
}

.dropdown-toggle {
  white-space: nowrap;
}

.dropdown-toggle::after {
  display: inline-block;
  margin-left: 0.255em;
  vertical-align: 0.255em;
  content: "";
  border-top: 0.3em solid;
  border-right: 0.3em solid transparent;
  border-bottom: 0;
  border-left: 0.3em solid transparent;
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  left: 0;
  z-index: 1000;
  display: none;
  float: left;
  min-width: 10rem;
  padding: 0.5rem 0;
  margin: 0.125rem 0 0;
  font-size: 0.85rem;
  color: #858796;
  text-align: left;
  list-style: none;
  background-color: #fff;
  background-clip: padding-box;
  border: 1px solid #e3e6f0;
  border-radius: 0.35rem;
}

.dropdown-menu.show {
  display: block;
}

.dropdown-item {
  display: block;
  width: 100%;
  padding: 0.25rem 1.5rem;
  clear: both;
  font-weight: 400;
  color: #3a3b45;
  text-align: inherit;
  white-space: nowrap;
  background-color: transparent;
  border: 0;
}

.dropdown-item:hover {
  background-color: #eaecf4;
}

.dropdown-divider {
  height: 0;
  margin: 0.5rem 0;
  overflow: hidden;
  border-top: 1px solid #eaecf4;
}

.dropdown-header {
  display: block;
  padding: 0.5rem 1.5rem;
  margin-bottom: 0;
  font-size: 0.875rem;
  color: #858796;
  white-space: nowrap;
}

.input-group {
  position: relative;
  display: flex;
  flex-wrap: wrap;
  align-items: stretch;
  width: 100%;
}

.input-group-append {
  display: flex;
  margin-left: -1px;
}

.nav {
  display: flex;
  flex-wrap: wrap;
  padding-left: 0;
  margin-bottom: 0;
  list-style: none;
}

.nav-link {
  display: block;
  padding: 0.5rem 1rem;
}

.nav-link:hover, .nav-link:focus {
  text-decoration: none;
}

.navbar {
  position: relative;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  padding: 0.5rem 1rem;
}

.navbar-nav {
  display: flex;
  flex-direction: column;
  padding-left: 0;
  margin-bottom: 0;
  list-style: none;
}

.navbar-nav .nav-link {
  padding-right: 0;
  padding-left: 0;
}

.navbar-expand {
  flex-flow: row nowrap;
  justify-content: flex-start;
}

.navbar-expand .navbar-nav {
  flex-direction: row;
}

.navbar-expand .navbar-nav .nav-link {
  padding-right: 0.5rem;
  padding-left: 0.5rem;
}

.navbar-expand .navbar-collapse {
  display: flex !important;
  flex-basis: auto;
}

.navbar-light .navbar-nav .nav-link {
  color: rgba(0, 0, 0, 0.5);
}

.navbar-light .navbar-nav .nav-link:hover {
  color: rgba(0, 0, 0, 0.7);
}

.card {
  position: relative;
  display: flex;
  flex-direction: column;
  min-width: 0;
  word-wrap: break-word;
  background-color: #fff;
  background-clip: border-box;
  border: 1px solid #e3e6f0;
  border-radius: 0.35rem;
}

.card-body {
  flex: 1 1 auto;
  min-height: 1px;
  padding: 1.25rem;
}

.card-header {
  padding: 0.75rem 1.25rem;
  margin-bottom: 0;
  background-color: #f8f9fc;
  border-bottom: 1px solid #e3e6f0;
}

.card-header:first-child {
  border-radius: calc(0.35rem - 1px) calc(0.35rem - 1px) 0 0;
}

.badge {
  display: inline-block;
  padding: 0.25em 0.4em;
  font-size: 75%;
  font-weight: 700;
  line-height: 1;
  text-align: center;
  white-space: nowrap;
  vertical-align: baseline;
  border-radius: 0.35rem;
}

.badge-danger {
  color: #fff;
  background-color: #e74a3b;
}

.progress {
  display: flex;
  height: 1rem;
  overflow: hidden;
  font-size: 0.75rem;
  background-color: #eaecf4;
  border-radius: 0.35rem;
}

.progress-bar {
  display: flex;
  flex-direction: column;
  justify-content: center;
  overflow: hidden;
  color: #fff;
  text-align: center;
  white-space: nowrap;
  background-color: #4e73df;
  transition: width 0.6s ease;
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  z-index: 1050;
  display: none;
  width: 100%;
  height: 100%;
  overflow: hidden;
  outline: 0;
  background-color: rgba(0, 0, 0, 0.5);
}

.modal.show {
  display: block;
}

.modal-dialog {
  position: relative;
  width: auto;
  margin: 0.5rem;
  max-width: 500px;
  margin: 1.75rem auto;
}

.modal-content {
  position: relative;
  display: flex;
  flex-direction: column;
  width: 100%;
  pointer-events: auto;
  background-color: #fff;
  background-clip: padding-box;
  border: 1px solid rgba(0, 0, 0, 0.2);
  border-radius: 0.3rem;
  outline: 0;
}

.modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 1rem 1rem;
  border-bottom: 1px solid #e3e6f0;
  border-top-left-radius: calc(0.3rem - 1px);
  border-top-right-radius: calc(0.3rem - 1px);
}

.modal-header .close {
  padding: 1rem 1rem;
  margin: -1rem -1rem -1rem auto;
  background: none;
  border: none;
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1;
  color: #000;
  text-shadow: 0 1px 0 #fff;
  opacity: .5;
  cursor: pointer;
}

.modal-header .close:hover {
  opacity: .75;
}

.modal-title {
  margin-bottom: 0;
  line-height: 1.5;
}

.modal-body {
  position: relative;
  flex: 1 1 auto;
  padding: 1rem;
}

.modal-footer {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: flex-end;
  padding: 0.75rem;
  border-top: 1px solid #e3e6f0;
  border-bottom-right-radius: calc(0.3rem - 1px);
  border-bottom-left-radius: calc(0.3rem - 1px);
}

.modal-footer > * {
  margin: 0.25rem;
}

/* ===== SB ADMIN 2 CUSTOM STYLES ===== */
html {
  position: relative;
  min-height: 100%;
}

body {
  height: 100%;
}

a:focus {
  outline: none;
}

#wrapper {
  display: flex;
}

#wrapper #content-wrapper {
  background-color: #f8f9fc;
  width: 100%;
  overflow-x: hidden;
}

#wrapper #content-wrapper #content {
  flex: 1 0 auto;
}

.container-fluid {
  padding-left: 1.5rem;
  padding-right: 1.5rem;
}

/* Fix wrapper layout */
#wrapper {
    display: flex;
    min-height: 100vh;
}

#content-wrapper {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 100vh;
}

#content {
    flex: 1 0 auto;
}

/* Sidebar positioning */
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
}

#sidebar-container {
    display: flex;
    flex-shrink: 0;
}

.chart-area{
    position:relative;
    height:20rem;
    width:100%;
}

.chart-pie{
    position:relative;
    height:15rem;
    width:100%;
}

.chart-bar{
    position:relative;
    height:20rem;
    width:100%;
}

.topbar {
    height: 4.375rem;
    position: relative;
    z-index: 10;
}

#content {
    position: relative;
    z-index: 1;
}

/* Desktop sidebar width */
@media (min-width: 768px) {
    .sidebar {
        width: 14rem !important;
    }
}

/* Mobile sidebar - overlay */
@media (max-width: 768px) {
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        z-index: 1050;
        width: 0 !important;
        overflow: hidden;
    }
    
    .sidebar.toggled {
        width: 14rem !important;
        overflow: visible;
        box-shadow: 4px 0 20px rgba(0, 0, 0, 0.2);
    }
    
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
}

.scroll-to-top {
  position: fixed;
  right: 1rem;
  bottom: 1rem;
  display: none;
  width: 2.75rem;
  height: 2.75rem;
  text-align: center;
  color: #fff;
  background: rgba(90, 92, 105, 0.5);
  line-height: 46px;
  border-radius: 50%;
  z-index: 999;
}

.scroll-to-top:hover {
  color: white;
  background: #5a5c69;
}

.scroll-to-top i {
  font-weight: 800;
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

@keyframes fadeIn {
  0% { opacity: 0; }
  100% { opacity: 1; }
}

.animated--fade-in {
  animation-name: fadeIn;
  animation-duration: 200ms;
  animation-timing-function: opacity cubic-bezier(0, 1, 0.4, 1);
}

.bg-gradient-primary {
  background-color: #4e73df;
  background-image: linear-gradient(180deg, #4e73df 10%, #224abe 100%);
  background-size: cover;
}

.bg-white {
  background-color: #fff !important;
}

.bg-primary { background-color: #4e73df !important; }
.bg-success { background-color: #1cc88a !important; }
.bg-info { background-color: #36b9cc !important; }
.bg-warning { background-color: #f6c23e !important; }
.bg-danger { background-color: #e74a3b !important; }
.bg-light { background-color: #f8f9fc !important; }

.text-gray-100 { color: #f8f9fc !important; }
.text-gray-200 { color: #eaecf4 !important; }
.text-gray-300 { color: #dddfeb !important; }
.text-gray-400 { color: #d1d3e2 !important; }
.text-gray-500 { color: #b7b9cc !important; }
.text-gray-600 { color: #858796 !important; }
.text-gray-700 { color: #6e707e !important; }
.text-gray-800 { color: #5a5c69 !important; }
.text-gray-900 { color: #3a3b45 !important; }
.text-primary { color: #4e73df !important; }
.text-white-50 { color: rgba(255, 255, 255, 0.5) !important; }

.text-xs { font-size: .7rem; }
.text-uppercase { text-transform: uppercase !important; }
.text-center { text-align: center !important; }

.font-weight-bold { font-weight: 700 !important; }

.border-left-primary { border-left: 0.25rem solid #4e73df !important; }
.border-left-success { border-left: 0.25rem solid #1cc88a !important; }
.border-left-info { border-left: 0.25rem solid #36b9cc !important; }
.border-left-warning { border-left: 0.25rem solid #f6c23e !important; }
.border-0 { border: 0 !important; }

.shadow { box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important; }
.shadow-lg { box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.175) !important; }

.d-flex { display: flex !important; }
.d-none { display: none !important; }
.d-sm-inline-block { display: inline-block !important; }
.d-md-inline { display: inline !important; }
.d-lg-flex { display: flex !important; }

.flex-column { flex-direction: column !important; }
.align-items-center { align-items: center !important; }
.justify-content-center { justify-content: center !important; }
.justify-content-between { justify-content: space-between !important; }

.mr-2 { margin-right: 0.5rem !important; }
.mr-3 { margin-right: 1rem !important; }
.mb-0 { margin-bottom: 0 !important; }
.mb-2 { margin-bottom: 0.5rem !important; }
.mb-4 { margin-bottom: 1.5rem !important; }
.my-0 { margin-top: 0 !important; margin-bottom: 0 !important; }
.my-2 { margin-top: 0.5rem !important; margin-bottom: 0.5rem !important; }
.my-5 { margin-top: 3rem !important; margin-bottom: 3rem !important; }
.my-auto { margin-top: auto !important; margin-bottom: auto !important; }
.ml-auto { margin-left: auto !important; }
.ml-md-3 { margin-left: 1rem !important; }

.p-0 { padding: 0 !important; }
.p-5 { padding: 3rem !important; }
.py-2 { padding-top: 0.5rem !important; padding-bottom: 0.5rem !important; }
.py-3 { padding-top: 1rem !important; padding-bottom: 1rem !important; }
.px-3 { padding-left: 1rem !important; padding-right: 1rem !important; }

.h-100 { height: 100% !important; }
.w-100 { width: 100% !important; }
.mw-100 { max-width: 100% !important; }
.float-right { float: right !important; }

.h3 { font-size: 1.75rem; font-weight: 400; line-height: 1.2; }
.h5 { font-size: 1.25rem; font-weight: 400; line-height: 1.2; }
.h6 { font-size: 1rem; font-weight: 400; line-height: 1.2; }

.small { font-size: 80%; font-weight: 400; }

.o-hidden { overflow: hidden !important; }

.icon-circle {
  height: 2.5rem;
  width: 2.5rem;
  border-radius: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.progress-sm {
  height: .5rem;
}

.rotate-n-15 {
  transform: rotate(-15deg);
}

.rounded-circle {
  border-radius: 50% !important;
}

/* TOPBAR */
.topbar {
  height: 4.375rem;
}

.topbar .navbar-search {
    width: 25rem;
}

.topbar .navbar-search .input-group {
    position: relative;
    display: flex;
    flex-wrap: nowrap;
    align-items: stretch;
    width: 100%;
}

.topbar .navbar-search .form-control {
    font-size: 0.85rem;
    height: auto;
    padding-right: 3rem;
    border-radius: 10rem 0 0 10rem;
    border: 1px solid #d1d3e2;
    background-color: #f8f9fc;
}

.topbar .navbar-search .input-group-append {
    margin-left: -1px;
}

.topbar .navbar-search .input-group-append .btn {
    border-radius: 0 10rem 10rem 0;
    padding: 0.375rem 0.75rem;
    background-color: #4e73df;
    color: #fff;
    border: 1px solid #4e73df;
}

.topbar .navbar-search .input-group-append .btn:hover {
    background-color: #2e59d9;
    border-color: #2653d4;
}

.topbar .navbar-search input {
  font-size: 0.85rem;
  height: auto;
}

.topbar .topbar-divider {
  width: 0;
  border-right: 1px solid #e3e6f0;
  height: calc(4.375rem - 2rem);
  margin: auto 1rem;
}

.topbar .nav-item .nav-link {
  height: 4.375rem;
  display: flex;
  align-items: center;
  padding: 0 0.75rem;
  color: #d1d3e2;
  position: relative;
}

.topbar .nav-item .nav-link:hover {
  color: #b7b9cc;
}

.topbar .nav-item .nav-link .img-profile {
  height: 2rem;
  width: 2rem;
  border-radius: 50%;
}

.topbar .dropdown {
  position: relative;
}

.topbar .dropdown-menu {
    right: 0;
    left: auto;
    min-width: 18rem;
}

.topbar .dropdown-list {
  padding: 0;
  border: none;
  overflow: hidden;
}

.topbar .dropdown-list .dropdown-header {
  background-color: #4e73df;
  border: 1px solid #4e73df;
  padding-top: 0.75rem;
  padding-bottom: 0.75rem;
  color: #fff;
}

.topbar .dropdown-list .dropdown-item {
  white-space: normal;
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
  border-left: 1px solid #e3e6f0;
  border-right: 1px solid #e3e6f0;
  border-bottom: 1px solid #e3e6f0;
  line-height: 1.3rem;
}

.topbar .dropdown-list .dropdown-item .dropdown-list-image {
  position: relative;
  height: 2.5rem;
  width: 2.5rem;
}

.topbar .dropdown-list .dropdown-item .dropdown-list-image img {
  height: 2.5rem;
  width: 2.5rem;
  border-radius: 50%;
}

.topbar .dropdown-list .dropdown-item .dropdown-list-image .status-indicator {
  background-color: #eaecf4;
  height: 0.75rem;
  width: 0.75rem;
  border-radius: 100%;
  position: absolute;
  bottom: 0;
  right: 0;
  border: 0.125rem solid #fff;
}

.topbar .dropdown-list .dropdown-item .status-indicator.bg-success {
  background-color: #1cc88a !important;
}

.topbar .dropdown-list .dropdown-item .status-indicator.bg-warning {
  background-color: #f6c23e !important;
}

.badge-counter {
  position: absolute;
  transform: scale(0.7);
  transform-origin: top right;
  right: .25rem;
  margin-top: -.25rem;
}

.d-sm-flex {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    flex-wrap: wrap;
}

.d-sm-flex .h3 {
    margin-bottom: 0;
}

.d-sm-flex .btn {
    flex-shrink: 0;
    margin-left: 1rem;
    color: #fff;
    background-color: #4e73df;
    border-color: #4e73df;
}

.d-sm-flex .btn :hover {
    color: #fffb !important;
    background-color: #07153f;
    border-color: #4e73df;
}

@media (max-width: 576px) {
    .d-sm-flex {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 0.75rem;
    }
    
    .d-sm-flex .btn {
        margin-left: 0;
        width: 100%;
    }
}

/* FOOTER */
footer.sticky-footer {
    margin-top: auto;
    width: 100%;
    height: 36px;
    min-height: 36px;
    padding: 8px 0;
    background:#002a66;
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

/* RESPONSIVE */
@media (min-width: 576px) {
  .topbar .dropdown {
    position: relative;
  }
  .topbar .dropdown .dropdown-menu {
    width: auto;
    right: 0;
  }
  .topbar .dropdown-list {
    width: 20rem !important;
  }
  .d-sm-inline-block {
    display: inline-block !important;
  }
}

@media (min-width: 992px) {
  .d-lg-flex {
    display: flex !important;
  }
}

.btn-circle {
  border-radius: 100%;
  height: 2.5rem;
  width: 2.5rem;
  font-size: 1rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.btn-circle.btn-sm {
  height: 1.8rem;
  width: 1.8rem;
  font-size: 0.75rem;
}

.btn-google {
  color: #fff;
  background-color: #ea4335;
  border-color: #fff;
}

.btn-google:hover {
  color: #fff;
  background-color: #e12717;
  border-color: #e6e6e6;
}

.btn-facebook {
  color: #fff;
  background-color: #3b5998;
  border-color: #fff;
}

.btn-facebook:hover {
  color: #fff;
  background-color: #30497c;
  border-color: #e6e6e6;
}

form.user .form-control-user {
  font-size: 0.8rem;
  border-radius: 10rem;
  padding: 1.5rem 1rem;
}

form.user .btn-user {
  font-size: 0.8rem;
  border-radius: 10rem;
  padding: 0.75rem 1rem;
}

.bg-login-image {
  background: url(img/company_login.png);
  background-position: center;
  background-size: cover;
}

.bg-register-image {
  background: url(img/company_register.png);
  background-position: center;
  background-size: cover;
}

.bg-password-image {
  background: url(img/company_password.png);
  background-position: center;
  background-size: cover;
}

.card .card-header[data-toggle="collapse"] {
  text-decoration: none;
  position: relative;
  padding: 0.75rem 3.25rem 0.75rem 1.25rem;
}

.card .card-header[data-toggle="collapse"]::after {
  position: absolute;
  right: 0;
  top: 0;
  padding-right: 1.725rem;
  line-height: 51px;
  font-weight: 900;
  content: '\f107';
  font-family: 'Font Awesome 5 Free';
  color: #d1d3e2;
}

.card .card-header[data-toggle="collapse"].collapsed::after {
  content: '\f105';
}

/* ===== SIDEBAR FIX ===== */
#sidebar-container {
    display: flex;
    flex-shrink: 0;
    height: 100vh;
    position: sticky;
    top: 0;
    align-self: flex-start;
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

/* Ensure charts are visible and full width */
.chart-area {
    position: relative;
    height: 300px;
    width: 100%;
}

.chart-pie {
    position: relative;
    height: 300px;
    width: 100%;
}

.chart-area canvas,
.chart-pie canvas {
    width: 100% !important;
    height: 100% !important;
    max-height: 100%;
}

/* Make container push footer down */
#content {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.container-fluid {
    flex: 1 0 auto;
}

/* ===== CUSTOM STYLES FOR CLIENTS PAGE ===== */
.action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

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

.btn-danger {
    color: #fff;
    background-color: #e74a3b;
    border-color: #e74a3b;
}

.btn-danger:hover {
    color: #fff;
    background-color: #c0392b;
    border-color: #b93a2d;
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

.badge-active { background: #dcfce7; color: #16a34a; }
.badge-inactive { background: #fee2e2; color: #dc2626; }
.badge-pending { background: #fef3c7; color: #d97706; }
.badge-suspended { background: #f3f4f6; color: #6b7280; }
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
                            <i class="fas fa-users"></i> Clients
                        </h1>
                        <a href="client-add.php" class="btn btn-sm btn-success shadow-sm">
                            <i class="fas fa-plus fa-sm text-white-50"></i> Add Client
                        </a>
                    </div>

                    <!-- Stats Cards -->
                    <div class="row">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Clients</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_clients; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $active_clients; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_clients; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-danger shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Inactive</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $inactive_clients; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Clients Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">All Clients</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Client Code</th>
                                                    <th>Company</th>
                                                    <th>Contact</th>
                                                    <th>Email</th>
                                                    <th>Phone</th>
                                                    <th>Industry</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($clients)): ?>
                                                <tr>
                                                    <td colspan="8" class="text-center text-muted">
                                                        No clients found. <a href="client-add.php">Add your first client</a>
                                                    </td>
                                                </tr>
                                                <?php else: ?>
                                                <?php foreach ($clients as $client): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($client['client_code']); ?></td>
                                                    <td><strong><?php echo htmlspecialchars($client['company_name']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($client['contact_person'] ?? 'N/A'); ?></td>
                                                    <td><a href="mailto:<?php echo htmlspecialchars($client['email']); ?>"><?php echo htmlspecialchars($client['email']); ?></a></td>
                                                    <td><?php echo htmlspecialchars($client['phone'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($client['industry'] ?? 'N/A'); ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php echo $client['status']; ?>">
                                                            <?php echo ucfirst($client['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <a href="client-view.php?id=<?php echo $client['id']; ?>" class="btn btn-sm btn-primary" title="View">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="client-edit.php?id=<?php echo $client['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="generate-quote.php?client_id=<?php echo $client['id']; ?>" class="btn btn-sm btn-success" title="Create Quote">
                                                                <i class="fas fa-file-invoice"></i>
                                                            </a>
                                                            <a href="client-quote.php?id=<?php echo $client['id']; ?>" class="btn btn-sm btn-info" title="View Quotes">
                                                                <i class="fas fa-list"></i>
                                                            </a>
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
    // Load sidebar from external file
    $(function() {
        $("#sidebar-container").load("sidebar.html", function() {
            console.log("Sidebar loaded successfully");
            
            // Fix sidebar toggle for mobile - toggle the container
            $('#sidebarToggleTop').on('click', function() {
                $('#sidebar-container').toggleClass('toggled');
                // Add overlay for mobile
                if ($('#sidebar-container').hasClass('toggled')) {
                    $('body').append('<div class="sidebar-overlay active"></div>');
                } else {
                    $('.sidebar-overlay').remove();
                }
            });
            
            // Close sidebar when clicking overlay
            $(document).on('click', '.sidebar-overlay', function() {
                $('#sidebar-container').removeClass('toggled');
                $('.sidebar-overlay').remove();
            });
        });
    });
    </script>
</body>
</html>