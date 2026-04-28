<?php
require_once __DIR__ . '/auth.php';
requireAuth();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
$_msUser     = htmlspecialchars($_SESSION['display_name'] ?? $_SESSION['username'] ?? '', ENT_QUOTES);
$_msRole     = $_SESSION['role'] ?? 'user';
$_msUserId   = (int)($_SESSION['user_id'] ?? 0);
$_msUsername = htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <title>Market Sculpt — Product Workbook</title>
  <style>
    /* ── Reset & Variables ───────────────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:          #0d0f14;
      --surface:     #181b26;
      --surface2:    #222638;
      --border:      #3a3f5c;
      --text:        #f0f1f5;
      --text-muted:  #9ba3c0;
      --accent:      #6b93ff;
      --accent-glow: rgba(107,147,255,0.18);
      --karen-label: #a0bdff;
      --filled-bg:    rgba(52,211,153,0.14);
      --filled-border:rgba(52,211,153,0.40);
      --filled-label: #5ee8a0;
      --success:     #4ade80;
      --danger:      #fb7185;
      --radius:      10px;
      --radius-sm:   6px;
      --shadow:      0 4px 24px rgba(0,0,0,0.5);
      --sidebar-text: #E8751A;
      --sidebar-active-bg: rgba(255, 255, 255, 0.08);
      --sidebar-btn: #E8751A;
      --header-title: #E8751A;
      --product-name-color: #E8751A;
    }

    [data-theme="light"] {
      --bg:          #f0f2f8;
      --surface:     #ffffff;
      --surface2:    #f5f7ff;
      --border:      #d0d6e8;
      --text:        #1a1d2e;
      --text-muted:  #6b7494;
      --accent:      #3b68f0;
      --accent-glow: rgba(59,104,240,0.1);
      --karen-label: #3a6ae0;
      --filled-bg:    rgba(40,180,90,0.07);
      --filled-border:rgba(40,170,85,0.25);
      --filled-label: #2a9a50;
      --shadow:      0 4px 24px rgba(0,0,0,0.08);
      --sidebar-text: #4A4A4A;
      --sidebar-active-bg: rgba(232, 117, 26, 0.08);
      --sidebar-btn: #4A4A4A;
      --header-title: #4A4A4A;
      --product-name-color: #4A4A4A;
    }

    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
      font-size: 14px;
      line-height: 1.6;
      min-height: 100vh;
      transition: background 0.2s, color 0.2s;
    }

    /* ── Layout ──────────────────────────────────────────────────────────── */
    .app-layout {
      display: flex;
      min-height: 100vh;
    }

    /* ── Sidebar ─────────────────────────────────────────────────────────── */
    .sidebar {
      width: 180px;
      background: var(--surface);
      border-right: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      position: fixed;
      top: 0;
      left: 0;
      bottom: 0;
      z-index: 200;
      overflow-y: auto;
    }

    .sidebar-logo {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 16px 12px 20px;
      cursor: pointer;
    }

    .sidebar-logo img {
      max-width: 100px;
      height: auto;
    }

    /* ── Sidebar Nav ─────────────────────────────────────────────────────── */
    .sidebar-nav {
      flex: 1;
      padding: 0 0 8px;
      display: flex;
      flex-direction: column;
      gap: 0;
      overflow-y: auto;
    }

    /* Section headers (Starred, Clients, Orders, etc.) */
    .nav-section-header {
      display: flex;
      align-items: center;
      gap: 6px;
      padding: 6px 12px 4px;
      cursor: pointer;
      user-select: none;
      color: var(--text-muted);
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.07em;
    }
    .nav-section-header:hover { color: var(--text); }
    .nav-section-chevron {
      margin-left: auto;
      width: 16px;
      height: 16px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      color: var(--text-muted);
      transition: transform 0.2s ease;
      transform: rotate(90deg);
      opacity: 0.7;
      font-size: 12px;
      line-height: 1;
    }
    .nav-section.collapsed .nav-section-chevron { transform: rotate(0deg); }
    .nav-section.collapsed .nav-section-body { display: none; }
    .nav-section-body { display: flex; flex-direction: column; gap: 1px; padding: 0 8px 6px; }
    .nav-flat-link {
      display: flex; align-items: center; gap: 6px;
      padding: 6px 12px; border-radius: var(--radius-sm);
      color: var(--text-muted); font-size: 11px; font-weight: 600;
      text-transform: uppercase; letter-spacing: 0.07em;
      text-decoration: none; cursor: pointer;
      transition: background 0.12s, color 0.12s;
    }
    .nav-flat-link:hover { background: var(--surface2); color: var(--text); }
    .nav-flat-link.active { color: var(--accent); }
    .nav-sample-item {
      display: flex; align-items: center; gap: 6px;
      padding: 5px 10px 5px 16px; border-radius: var(--radius-sm);
      color: var(--text-muted); font-size: 13px; cursor: pointer;
      transition: background 0.12s, color 0.12s;
    }
    .nav-sample-item:hover { background: var(--surface2); color: var(--text); }
    .nav-sample-item.active { background: rgba(107,147,255,0.12); color: var(--accent); font-weight: 600; }
    .nav-sample-dot {
      width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0;
      background: var(--text-muted);
    }
    .nav-sample-dot.pending   { background: #6b93ff; }
    .nav-sample-dot.requested { background: #f59e0b; }
    .nav-sample-dot.received  { background: #4ade80; }
    .nav-sample-dot.approved  { background: #34d399; }
    .nav-badge {
      display: none;
      font-size: 10px; font-weight: 700; line-height: 1;
      background: var(--accent); color: #fff;
      border-radius: 10px; padding: 2px 6px;
      margin-left: 6px; flex-shrink: 0;
    }
    .nav-section.collapsed .nav-badge:not(:empty) { display: inline-flex; align-items: center; }

    /* Coming-soon placeholder items */
    .nav-placeholder {
      padding: 7px 12px;
      font-size: 12px;
      color: var(--text-muted);
      opacity: 0.5;
      font-style: italic;
    }

    .nav-item {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 7px 10px;
      border-radius: var(--radius-sm);
      color: var(--sidebar-text);
      font-size: 13px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.15s;
      text-decoration: none;
      border: none;
      background: none;
      width: 100%;
      text-align: left;
      box-sizing: border-box;
    }

    .nav-item:hover { background: var(--surface2); color: var(--text); }

    .nav-item.active {
      background: var(--sidebar-active-bg);
      color: #E8751A;
      font-weight: 600;
      border-left: 3px solid #E8751A;
    }

    /* Star button */
    .nav-star-btn {
      display: none;
      margin-left: auto;
      background: none;
      border: none;
      color: var(--text-muted);
      font-size: 13px;
      cursor: pointer;
      padding: 2px 4px;
      border-radius: 4px;
      line-height: 1;
      flex-shrink: 0;
      opacity: 0.5;
    }
    .nav-item:hover .nav-star-btn { display: inline-flex; }
    .nav-item .nav-star-btn.starred { display: inline-flex; color: #f5a623; opacity: 1; }
    .nav-star-btn:hover { opacity: 1 !important; }

    /* Delete button (shown on hover or when active) */
    .nav-item .client-delete-btn {
      display: none;
      background: none;
      border: none;
      color: var(--text-muted);
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      padding: 2px 5px;
      border-radius: var(--radius-sm);
      line-height: 1;
      flex-shrink: 0;
    }
    .nav-item.active .client-delete-btn,
    .nav-item:hover .client-delete-btn { display: inline-flex; }
    .nav-item .client-delete-btn:hover { color: #e53e3e; background: rgba(229,62,62,0.1); }

    /* When both star and delete are shown on active starred item */
    .nav-item.active .nav-star-btn { display: inline-flex; }

    .nav-item-icon { width: 16px; text-align: center; font-size: 14px; flex-shrink: 0; }
    .nav-item-chevron { margin-left: auto; font-size: 10px; color: var(--text-muted); transition: transform 0.2s; }
    .nav-item.expanded .nav-item-chevron { transform: rotate(90deg); }
    .nav-submenu { display: none; padding-left: 44px; flex-direction: column; gap: 2px; }
    .nav-submenu.open { display: flex; }
    .nav-submenu .nav-item { padding: 7px 12px; font-size: 13px; }

    .sidebar-bottom {
      padding: 12px;
      border-top: 1px solid var(--border);
      margin-top: auto;
    }

    .app-content {
      margin-left: 180px;
      flex: 1;
    }

    .app-header {
      background: var(--surface);
      border-bottom: 1px solid var(--border);
      padding: 0 28px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      height: 64px;
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: var(--shadow);
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .logo-mark {
      width: 34px;
      height: 34px;
      background: linear-gradient(135deg, var(--accent), #8b5cf6);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 900;
      font-size: 16px;
      color: #fff;
    }

    .logo-text {
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--text-muted);
    }

    .logo-sub {
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--text-muted);
      margin-top: -2px;
    }

    .header-actions {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* ── Buttons ─────────────────────────────────────────────────────────── */
    .btn {
      border: none;
      border-radius: var(--radius-sm);
      padding: 7px 14px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.15s;
    }
    .btn-ghost {
      background: transparent;
      border: 1px solid var(--border);
      color: var(--text-muted);
    }
    .btn-ghost:hover { background: var(--surface2); color: var(--text); }
    .btn-primary {
      background: var(--accent);
      color: #fff;
    }
    .btn-primary:hover { opacity: 0.88; }
    .btn-danger-ghost {
      background: transparent;
      border: 1px solid transparent;
      color: var(--danger);
      padding: 4px 8px;
    }
    .btn-danger-ghost:hover { background: rgba(248,113,113,0.1); }
    .btn-add {
      background: var(--surface2);
      border: 1px dashed var(--border);
      color: var(--text-muted);
      width: 100%;
      padding: 9px;
      border-radius: var(--radius-sm);
    }
    .btn-add:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-glow); }

    /* ── Status Filter Pills ────────────────────────────────────────────── */
    .status-filter-btn {
      background: var(--surface2);
      border: 1px solid var(--border);
      color: var(--text-muted);
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.15s;
      white-space: nowrap;
    }
    .status-filter-btn:hover { border-color: var(--accent); color: var(--accent); }
    .status-filter-btn.active {
      background: var(--accent);
      border-color: var(--accent);
      color: #fff;
      font-weight: 600;
    }

    /* ── Sortable Table Headers ─────────────────────────────────────────── */
    .sortable { cursor: pointer; user-select: none; }
    .sortable:hover { color: var(--accent); }
    .sort-arrow { font-size: 10px; opacity: 0.4; margin-left: 2px; }
    .sort-arrow::after { content: '⇅'; }
    .sortable.asc .sort-arrow { opacity: 1; }
    .sortable.asc .sort-arrow::after { content: '↑'; }
    .sortable.desc .sort-arrow { opacity: 1; }
    .sortable.desc .sort-arrow::after { content: '↓'; }

    /* ── Theme Toggle ────────────────────────────────────────────────────── */
    .theme-toggle {
      background: var(--surface2);
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      color: var(--text);
      font-size: 13px;
      font-weight: 600;
      padding: 7px 14px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.15s;
    }
    .theme-toggle:hover { background: var(--border); }

    /* ── User Dropdown ───────────────────────────────────────────────────── */
    .user-menu { position: relative; display: flex; align-items: center; }
    .user-menu-btn {
      display: flex; align-items: center; gap: 8px;
      padding: 6px 10px 6px 8px;
      background: none; border: 1px solid transparent;
      border-radius: var(--radius-sm); cursor: pointer;
      color: var(--text); font-family: inherit;
      transition: background 0.15s, border-color 0.15s;
      border-left: 1px solid var(--border);
      margin-left: 4px;
    }
    .user-menu-btn:hover { background: var(--surface2); border-color: var(--border); }
    .user-menu-btn .user-name { font-size: 13px; font-weight: 600; line-height: 1.2; }
    .user-menu-btn .user-label { font-size: 10px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; line-height: 1.2; }
    .user-dropdown {
      position: absolute; top: calc(100% + 6px); right: 0;
      background: var(--surface); border: 1px solid var(--border);
      border-radius: var(--radius); box-shadow: 0 8px 24px rgba(0,0,0,0.2);
      min-width: 200px; z-index: 2000; display: none;
      padding: 6px 0;
    }
    .user-dropdown.open { display: block; }
    .user-dropdown-item {
      display: flex; align-items: center; gap: 10px;
      padding: 9px 16px; font-size: 14px; color: var(--text);
      cursor: pointer; background: none; border: none;
      width: 100%; text-align: left; font-family: inherit;
      text-decoration: none; transition: background 0.12s;
    }
    .user-dropdown-item:hover { background: var(--surface2); }
    .user-dropdown-item.danger { color: var(--danger); }
    .user-dropdown-divider { border: none; border-top: 1px solid var(--border); margin: 4px 0; }

    /* ── Main Container ──────────────────────────────────────────────────── */
    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 32px 32px 60px;
    }

    /* ── Karen Legend ────────────────────────────────────────────────────── */
    .legend {
      display: flex;
      align-items: center;
      gap: 20px;
      padding: 12px 16px;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      margin-bottom: 28px;
      font-size: 13px;
    }
    .legend-title { color: var(--text-muted); font-weight: 600; }
    .legend-item {
      display: flex;
      align-items: center;
      gap: 7px;
    }
    .legend-dot {
      width: 12px; height: 12px;
      border-radius: 3px;
    }
    .dot-ms   { background: var(--accent); }
    .dot-karen { background: var(--karen-border); }

    /* ── Section Card ────────────────────────────────────────────────────── */
    .section-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      margin-bottom: 24px;
      overflow: hidden;
      box-shadow: var(--shadow);
    }

    .section-header {
      padding: 16px 22px;
      background: var(--surface2);
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .section-icon {
      width: 28px; height: 28px;
      border-radius: 6px;
      background: var(--accent-glow);
      border: 1px solid var(--accent);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
    }

    .section-title {
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--text-muted);
    }

    .section-badge {
      margin-left: auto;
      font-size: 11px;
      padding: 2px 8px;
      border-radius: 20px;
      background: var(--accent-glow);
      color: var(--accent);
      border: 1px solid var(--accent);
      font-weight: 600;
    }

    .section-body {
      padding: 22px;
    }

    /* ── Collapsible section cards ── */
    .section-header-collapsible {
      cursor: pointer;
      user-select: none;
    }
    .section-header-collapsible:hover {
      background: color-mix(in srgb, var(--surface2) 85%, var(--border));
    }
    .section-chevron {
      margin-left: auto;
      font-size: 20px;
      color: var(--text-muted);
      transition: transform 0.2s ease;
      display: inline-block;
      flex-shrink: 0;
      line-height: 1;
      transform: rotate(90deg);
    }
    .section-card.collapsed .section-chevron {
      transform: rotate(0deg);
    }
    .section-card.collapsed .section-body {
      display: none;
    }

    /* ── Sub-section ─────────────────────────────────────────────────────── */
    .subsection {
      margin-top: 24px;
      padding-top: 20px;
      border-top: 1px solid var(--border);
    }

    .subsection-title {
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--text-muted);
      margin-bottom: 16px;
    }

    /* ── Form Grid ───────────────────────────────────────────────────────── */
    .form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 14px;
    }

    .form-grid-2 { grid-template-columns: 1fr 1fr; }
    .form-grid-3 { grid-template-columns: 1fr 1fr 1fr; }
    .col-span-2 { grid-column: span 2; }
    .col-span-3 { grid-column: span 3; }
    .col-full   { grid-column: 1 / -1; }

    .field {
      display: flex;
      flex-direction: column;
      gap: 5px;
    }

    label {
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--text-muted);
    }


    input, textarea, select {
      background: rgba(107,147,255,0.08);
      border: 1px solid rgba(107,147,255,0.30);
      border-radius: var(--radius-sm);
      color: var(--text);
      font-size: 14px;
      font-family: inherit;
      padding: 8px 11px;
      transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
      width: 100%;
    }

    select {
      height: 38px;
      cursor: pointer;
    }

    .select-wrap, .select-wrapper {
      position: relative;
    }
    .select-wrap select, .select-wrapper select {
      -webkit-appearance: none;
      -moz-appearance: none;
      appearance: none;
      padding-right: 40px;
    }
    .select-wrap::after, .select-wrapper::after {
      content: '\25BE';
      position: absolute;
      right: 16px;
      top: 50%;
      transform: translateY(-50%);
      pointer-events: none;
      font-size: 18px;
      color: #888;
    }
    input:focus, textarea:focus, select:focus {
      outline: none;
      border-color: var(--accent);
      box-shadow: 0 0 0 3px var(--accent-glow);
    }
    input::placeholder, textarea::placeholder {
      color: var(--text-muted);
      opacity: 0.6;
    }
    input[readonly], textarea[readonly],
    .field-filled input[readonly],
    .field-filled textarea[readonly] {
      background: rgba(232, 117, 26, 0.08) !important;
      border-color: rgba(232, 117, 26, 0.2) !important;
      color: var(--text);
      cursor: default;
      opacity: 1 !important;
    }
    #duplicate-modal input[readonly] {
      background: var(--surface2) !important;
      border-color: var(--border) !important;
      opacity: 0.7 !important;
    }
    .lead-time-suffix {
      position: relative;
    }
    .lead-time-suffix::after {
      content: 'days';
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 11px;
      color: #b0b7c3;
      pointer-events: none;
    }
    .currency-prefix {
      position: relative;
    }
    .currency-prefix::before {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 13px;
      color: var(--text-muted);
      pointer-events: none;
      z-index: 1;
    }
    .currency-prefix input { padding-left: 28px !important; }
    .currency-rmb::before { content: '¥'; }
    .currency-usd::before { content: '$'; }

    /* ── Client Detail Card ─────────────────────────────── */
    .client-detail-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 28px 28px 24px;
      display: flex;
      gap: 28px;
      align-items: flex-start;
      margin-bottom: 18px;
    }
    .cdc-avatar {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: var(--accent);
      color: #fff;
      font-size: 22px;
      font-weight: 800;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      letter-spacing: -0.5px;
    }
    .cdc-left {
      display: flex;
      flex-direction: column;
      gap: 12px;
      min-width: 0;
      flex: 1;
    }
    .cdc-name {
      font-size: 20px;
      font-weight: 800;
      color: var(--text);
      line-height: 1.1;
      margin-bottom: 2px;
    }
    .cdc-fields {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 14px 24px;
    }
    @media (max-width: 900px) {
      .cdc-fields { grid-template-columns: repeat(2, 1fr); }
      .client-detail-card { flex-wrap: wrap; }
      .cdc-financial { width: 100%; }
    }
    @media (max-width: 600px) {
      .cdc-fields { grid-template-columns: 1fr; }
    }
    .cdc-field { display: flex; flex-direction: column; gap: 3px; }
    .cdc-label {
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.07em;
      color: var(--text-muted);
    }
    .cdc-value {
      font-size: 13px;
      color: var(--text);
      background: var(--surface2);
      border: 1px solid var(--border);
      border-radius: 7px;
      padding: 7px 10px;
      font-family: inherit;
      width: 100%;
      box-sizing: border-box;
      resize: none;
      outline: none;
      transition: border-color 0.15s;
      line-height: 1.4;
    }
    .cdc-value:focus { border-color: var(--accent); box-shadow: 0 0 0 2px rgba(232,117,26,0.15); }
    .cdc-value::placeholder { color: var(--text-muted); opacity: 0.6; }
    .cdc-financial {
      background: linear-gradient(135deg, rgba(107,147,255,0.12) 0%, rgba(107,147,255,0.06) 100%);
      border: 1px solid rgba(107,147,255,0.25);
      border-radius: 12px;
      padding: 20px 22px;
      min-width: 200px;
      flex-shrink: 0;
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    .cdc-fin-title {
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: #6b93ff;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .cdc-fin-stat {
      display: flex;
      flex-direction: column;
      margin-bottom: 10px;
    }
    .cdc-fin-stat:last-child { margin-bottom: 0; }
    .cdc-fin-stat-label {
      font-size: 11px;
      color: var(--text-muted);
      font-weight: 500;
    }
    .cdc-fin-stat-value {
      font-size: 22px;
      font-weight: 800;
      color: var(--text);
      line-height: 1.1;
    }
    .cdc-fin-stat-value.accent { color: #6b93ff; }
    .cdc-fin-stat-value.success { color: var(--success); }

    /* ── Inventory Table ─────────────────────────────────────────────── */
    .inv-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13px;
      table-layout: fixed;
    }
    .inv-table th {
      text-align: left;
      padding: 11px 14px;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--text-muted);
      border-bottom: 1px solid var(--border);
      white-space: nowrap;
    }
    .inv-table td {
      padding: 12px 14px;
      border-bottom: 1px solid var(--border);
      vertical-align: middle;
    }
    .inv-table tbody tr:hover { background: var(--surface2); }
    .inv-table th:nth-child(1) { width: 34%; } /* Product */
    .inv-table th:nth-child(2) { width: 18%; } /* SKU */
    .inv-table th:nth-child(3) { width: 16%; } /* Client */
    .inv-table th:nth-child(4) { width: 18%; } /* Source workbook */
    .inv-table th:nth-child(5) { width: 10%; } /* Date */
    .inv-table th:nth-child(6) { width: 44px; } /* Remove */
    .inv-product-name { font-weight: 600; color: var(--text); }
    .inv-variant-chip {
      display: inline-block;
      font-size: 11px;
      font-weight: 500;
      padding: 2px 7px;
      border-radius: 8px;
      background: var(--surface2);
      color: var(--text-muted);
      border: 1px solid var(--border);
      margin-top: 3px;
    }
    .inv-sku {
      color: var(--text);
      font-weight: 600;
    }
    /* Legacy — kept for non-clickable fallback */
    .inv-source-link {
      color: var(--primary);
      cursor: pointer;
      font-size: 12px;
      text-decoration: none;
    }
    .inv-source-link:hover { text-decoration: underline; }
    /* Clickable workbook pill in inventory table */
    .inv-wb-pill {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: 12px; font-weight: 500;
      padding: 4px 10px; border-radius: 20px;
      border: 1px solid rgba(107,147,255,0.35);
      background: rgba(107,147,255,0.1);
      color: var(--accent); cursor: pointer;
      transition: border-color 0.12s, background 0.12s;
      max-width: 100%;
    }
    .inv-wb-pill:hover { border-color: var(--accent); background: rgba(107,147,255,0.18); }
    .inv-wb-pill-text {
      overflow: hidden; text-overflow: ellipsis; white-space: nowrap; min-width: 0;
    }
    .inv-wb-pill-arrow { font-size: 11px; opacity: 0.75; flex-shrink: 0; }
    /* RFQ Queue table — scoped column widths (override global .dash-table nth-child rules) */
    #rfq-table { table-layout: fixed; }
    #rfq-table th:nth-child(1), #rfq-table td:nth-child(1) { width: 12%; }   /* Client */
    #rfq-table th:nth-child(2), #rfq-table td:nth-child(2) { width: 22%; }   /* Workbook */
    #rfq-table th:nth-child(3), #rfq-table td:nth-child(3) { width: 12%; }   /* Submitted */
    #rfq-table th:nth-child(4), #rfq-table td:nth-child(4) { width: 10%; }   /* Lines */
    #rfq-table th:nth-child(5), #rfq-table td:nth-child(5) { width: 12%; }   /* Qty */
    #rfq-table th:nth-child(6), #rfq-table td:nth-child(6) { width: 16%; }   /* RMB */
    #rfq-table th:nth-child(7), #rfq-table td:nth-child(7) { width: 16%; }   /* USD */
    #rfq-table th, #rfq-table td { padding-left: 12px; padding-right: 12px; }

    /* Samples table — scoped column widths so columns size proportionally to
       the available space (table-layout:fixed + percent widths). The global
       .dash-table nth-child rules from the workbook dashboard would otherwise
       win and squash these columns into the wrong shape. */
    #samples-table { table-layout: fixed; }
    #samples-table th:nth-child(1), #samples-table td:nth-child(1) { width: 22%; }  /* Item */
    #samples-table th:nth-child(2), #samples-table td:nth-child(2) { width: 16%; }  /* Client (pill) */
    #samples-table th:nth-child(3), #samples-table td:nth-child(3) { width: 22%; }  /* Workbook (pill) */
    #samples-table th:nth-child(4), #samples-table td:nth-child(4) { width: 8%;  }  /* Qty */
    #samples-table th:nth-child(5), #samples-table td:nth-child(5) { width: 9%;  }  /* RMB */
    #samples-table th:nth-child(6), #samples-table td:nth-child(6) { width: 9%;  }  /* USD */
    #samples-table th:nth-child(7), #samples-table td:nth-child(7) { width: 6%;  }  /* Lead */
    #samples-table th:nth-child(8), #samples-table td:nth-child(8) { width: 8%;  }  /* Status */
    #samples-table th, #samples-table td { padding-left: 12px; padding-right: 12px; vertical-align: middle; }
    #samples-table td { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    /* Pill cells need to keep their min-width:0 so flex/inline-flex children
       can shrink and ellipsis-trim instead of pushing the column wider. */
    #samples-table .samples-pill-cell { overflow: visible; }
    /* Reuse inv-client-chip but make it clickable for samples — hover gets a
       subtle lift so it reads as interactive. */
    #samples-table .inv-client-chip {
      cursor: pointer;
      transition: filter 0.12s, transform 0.12s;
      max-width: 100%;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    #samples-table .inv-client-chip:hover { filter: brightness(0.96); transform: translateY(-1px); }
    /* Client chip — distinct color, not clickable */
    .inv-client-chip {
      display: inline-flex; align-items: center;
      font-size: 12px; font-weight: 600;
      padding: 3px 10px; border-radius: 12px;
      background: rgba(139,92,246,0.12);
      color: #7c3aed;
      border: 1px solid rgba(139,92,246,0.3);
    }
    .inv-remove-btn {
      background: none;
      border: none;
      color: var(--text-muted);
      cursor: pointer;
      font-size: 16px;
      padding: 4px 6px;
      border-radius: 5px;
      line-height: 1;
      transition: color 0.15s, background 0.15s;
    }
    .inv-remove-btn:hover { color: var(--danger); background: rgba(239,68,68,0.1); }

    /* ── Inventory Dashboard ─────────────────────────────────────────── */
    .inv-tab-btn {
      background: transparent;
      border: none;
      border-bottom: 2px solid transparent;
      color: var(--text-muted);
      padding: 10px 16px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      margin-bottom: -1px;
      transition: color 0.15s, border-color 0.15s;
      font-family: inherit;
    }
    .inv-tab-btn:hover { color: var(--text); }
    .inv-tab-btn.active {
      color: var(--accent);
      border-bottom-color: var(--accent);
    }
    .inv-stat-card {
      background: var(--surface2);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 14px 16px;
      min-height: 78px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    .inv-stat-card-value { font-size: 22px; font-weight: 700; line-height: 1.1; }
    .inv-stat-card-label {
      font-size: 11px;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-top: 4px;
    }
    .inv-stat-card-sub {
      font-size: 11px;
      color: var(--text-muted);
      margin-top: 2px;
    }
    .inv-dash-row-title {
      font-size: 12px;
      font-weight: 700;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.06em;
      margin: 0 0 12px;
    }
    .inv-topclient-row {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 6px 0;
    }
    .inv-topclient-name {
      font-size: 13px;
      font-weight: 500;
      color: var(--text);
      min-width: 110px;
      max-width: 140px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .inv-topclient-bar-wrap {
      flex: 1;
      height: 8px;
      background: var(--surface2);
      border-radius: 4px;
      overflow: hidden;
    }
    .inv-topclient-bar {
      height: 100%;
      border-radius: 4px;
      background: linear-gradient(90deg, var(--accent), #a855f7);
      transition: width 0.3s;
    }
    .inv-topclient-count {
      font-size: 12px;
      font-weight: 600;
      color: var(--text-muted);
      min-width: 24px;
      text-align: right;
    }
    .inv-activity-row {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 8px 0;
      border-bottom: 1px solid var(--border);
    }
    .inv-activity-row:last-child { border-bottom: none; }
    .inv-activity-sku {
      font-size: 12px;
      font-weight: 600;
      color: var(--text);
      flex: 1;
      min-width: 0;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .inv-activity-meta { font-size: 11px; color: var(--text-muted); flex-shrink: 0; }
    .inv-sparkline {
      display: flex;
      align-items: flex-end;
      gap: 3px;
      height: 56px;
      padding: 4px 0;
    }
    .inv-sparkline-bar {
      flex: 1;
      background: linear-gradient(to top, var(--accent), color-mix(in srgb, var(--accent) 30%, transparent));
      border-radius: 3px 3px 0 0;
      min-height: 2px;
      position: relative;
      transition: opacity 0.15s;
    }
    .inv-sparkline-bar:hover { opacity: 0.75; }
    .inv-sparkline-labels {
      display: flex;
      justify-content: space-between;
      font-size: 10px;
      color: var(--text-muted);
      margin-top: 4px;
    }

    .sidebar-archive-btn {
      background: transparent;
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      color: var(--text-muted);
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 8px 12px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.15s;
      width: 100%;
      font-family: inherit;
    }
    .sidebar-archive-btn:hover {
      background: var(--surface2);
      color: var(--text);
    }

    .email-quote-btn {
      background: transparent;
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      color: var(--text-muted);
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 14px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.15s;
      white-space: nowrap;
    }
    .email-quote-btn:hover {
      background: var(--surface2);
      color: var(--text);
    }
    .email-icon {
      font-size: 16px;
    }
    textarea { resize: vertical; min-height: 80px; }

    /* Filled state — all fields */
    .field-filled input,
    .field-filled textarea,
    .field-filled select {
      background: var(--filled-bg);
      border-color: var(--filled-border);
    }
    .field-filled input:focus,
    .field-filled textarea:focus {
      box-shadow: 0 0 0 3px rgba(52,211,153,0.15);
    }

    /* ── Dimensions Block ────────────────────────────────────────────────── */
    .dim-block {
      display: flex;
      flex-direction: column;
      gap: 10px;
      margin-bottom: 20px;
    }

    .dim-title {
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--text-muted);
    }

    .dim-group {
      display: flex;
      gap: 16px;
    }

    .dim-field {
      flex: 1;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .dim-field label {
      font-size: 13px;
      font-weight: 600;
      color: var(--text);
      white-space: nowrap;
      margin: 0;
      text-transform: none;
      letter-spacing: 0;
      flex-shrink: 0;
    }

    .dim-field input {
      width: auto;
      flex: 1;
    }

    /* ── Image Gallery ───────────────────────────────────────────────────── */
    .image-gallery { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 8px; }
    .image-gallery-item {
      position: relative; width: 120px; height: 120px; border-radius: var(--radius);
      border: 1px solid var(--border); overflow: hidden; flex-shrink: 0;
    }
    .image-gallery-item img {
      width: 100%; height: 100%; object-fit: cover; cursor: pointer;
    }
    .image-gallery-item .img-remove {
      position: absolute; top: 4px; right: 4px; background: rgba(0,0,0,0.6); color: #fff;
      border: none; border-radius: 50%; width: 22px; height: 22px; font-size: 12px;
      cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 2;
    }
    .image-add-btn {
      width: 120px; height: 120px; border: 2px dashed var(--border); border-radius: var(--radius);
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      cursor: pointer; transition: all 0.15s; gap: 4px; flex-shrink: 0;
    }
    .image-add-btn:hover { border-color: var(--accent); background: var(--accent-glow); }
    .image-add-btn .add-icon { font-size: 24px; color: var(--text-muted); }
    .image-add-btn .add-text { font-size: 10px; color: var(--text-muted); }
    /* Lightbox */
    .lightbox-overlay {
      display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.85); z-index: 600; align-items: center; justify-content: center; cursor: pointer;
    }
    .lightbox-overlay.open { display: flex; }
    .lightbox-overlay img { max-width: 90vw; max-height: 90vh; object-fit: contain; border-radius: 8px; }
    /* Video gallery */
    .video-gallery { display: flex; flex-direction: column; gap: 8px; margin-top: 10px; }
    .video-item {
      display: flex; align-items: center; gap: 10px;
      background: var(--surface2); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 8px 10px;
    }
    .video-thumb { width: 80px; height: 45px; object-fit: cover; border-radius: 4px; flex-shrink: 0; cursor: pointer; }
    .video-thumb-placeholder {
      width: 80px; height: 45px; border-radius: 4px; flex-shrink: 0;
      background: var(--surface3); border: 1px solid var(--border);
      display: flex; align-items: center; justify-content: center;
      font-size: 18px; cursor: pointer;
    }
    .video-url-label { flex: 1; font-size: 12px; color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; cursor: pointer; }
    .video-url-label:hover { color: var(--accent); }
    .video-add-row { display: flex; gap: 8px; align-items: center; }
    .video-add-row input { flex: 1; }
    .video-lightbox-overlay {
      display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.9); z-index: 610; align-items: center; justify-content: center; cursor: pointer;
    }
    .video-lightbox-overlay.open { display: flex; }
    .video-lightbox-inner { position: relative; width: min(90vw, 854px); aspect-ratio: 16/9; cursor: default; }
    .video-lightbox-inner iframe, .video-lightbox-inner video { width: 100%; height: 100%; border-radius: 8px; border: none; }

    /* ── Secondary Category / Material dropdowns ─────────────────────────── */
    .secondary-select-wrap { margin-top: 8px; opacity: 0.3; pointer-events: none; transition: opacity 0.2s, border-color 0.2s; }
    .secondary-select-wrap.unlocked { opacity: 1; pointer-events: auto; }
    .secondary-select-wrap.unlocked select { border-color: #3b82f6 !important; }
    .secondary-select-wrap.unlocked.has-value select { border-color: #22c55e !important; }
    .secondary-select-label {
      font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em;
      color: var(--text-muted); margin-bottom: 4px; transition: color 0.2s;
    }
    .secondary-select-wrap.unlocked .secondary-select-label { color: #3b82f6; }
    .secondary-select-wrap.unlocked.has-value .secondary-select-label { color: #22c55e; }

    /* ── Color Swatch ────────────────────────────────────────────────────── */
    .color-row {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .color-swatch {
      width: 36px; height: 36px;
      border-radius: var(--radius-sm);
      border: 1px solid var(--border);
      cursor: pointer;
      flex-shrink: 0;
    }

    /* ── Table (shared by tier-table and rfq) ──────────────────────────── */
    .tier-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13px;
    }

    .tier-table th {
      text-align: left;
      padding: 10px 14px;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      background: var(--surface2);
      border-bottom: 1px solid var(--border);
      color: var(--text-muted);
    }

    .tier-table td {
      padding: 10px 14px;
      border-bottom: 1px solid var(--border);
      vertical-align: middle;
    }

    .tier-table tr:last-child td { border-bottom: none; }

    /* ── Tier selector bar (single row) ── */
    .sh-tier-bar { padding: 0 !important; overflow: hidden; }
    .sh-tier-row {
      display: flex;
      align-items: stretch;
      height: 52px;
    }
    .sh-tier-select-wrap {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 0 16px;
      flex-shrink: 0;
    }
    .sh-tier-row-label {
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--text-muted);
      white-space: nowrap;
    }
    .sh-tier-select-inner {
      position: relative;
    }
    .sh-tier-select-inner::after {
      content: '';
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      width: 0;
      height: 0;
      border-left: 4px solid transparent;
      border-right: 4px solid transparent;
      border-top: 5px solid var(--text-muted);
      pointer-events: none;
    }
    .sh-tier-select-inner select {
      height: 32px;
      padding: 0 28px 0 10px;
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      background: var(--surface);
      color: var(--text);
      font-size: 13px;
      font-family: inherit;
      outline: none;
      cursor: pointer;
      appearance: none;
      -webkit-appearance: none;
      min-width: 160px;
    }
    #sh-tier-details { display: none; }
    #sh-tier-details.visible { display: flex; }
    .sh-tier-stat {
      display: flex;
      flex-direction: column;
      justify-content: center;
      gap: 3px;
      padding: 0 20px;
    }
    .sh-tier-stat-label {
      font-size: 9px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      color: var(--text-muted);
    }
    .sh-tier-stat-val {
      font-size: 14px;
      font-weight: 700;
      color: var(--text);
    }

    .sh-tier-detail-item { display: flex; flex-direction: column; gap: 3px; }
    .sh-tier-detail-label {
      font-size: 10px;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.04em;
      font-weight: 600;
    }
    .sh-tier-detail-val {
      font-size: 14px;
      font-weight: 600;
      color: var(--text);
    }

    #rfq-table td {
      padding: 14px 12px;
      vertical-align: top;
    }

    #rfq-table input {
      padding: 10px 14px;
    }

    #rfq-table tfoot td {
      padding: 16px 12px;
    }

    /* Sample checkbox in RFQ table */
    .rfq-sample-label {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 4px;
      cursor: pointer;
      position: relative;
    }
    .rfq-sample-label input[type="checkbox"] {
      width: 16px;
      height: 16px;
      accent-color: var(--accent);
      cursor: pointer;
    }
    .rfq-sample-icon {
      font-size: 14px;
      line-height: 1;
      min-width: 18px;
      text-align: center;
    }
    .rfq-sample-row {
      background: rgba(107,147,255,0.06) !important;
      outline: 1px solid rgba(107,147,255,0.25);
      outline-offset: -1px;
    }
    .rfq-variant-row { background: rgba(232,117,26,0.03); }
    #rfq-table .rfq-variant-row td { padding: 8px 8px; vertical-align: top; }

    /* Samples dashboard status select */
    .sample-status-sel option {
      background: var(--surface);
      color: var(--text);
    }

    .remove-tier {
      cursor: pointer;
      user-select: none;
    }

    .karen-cell.field-filled input {
      background: var(--filled-bg);
      border-color: var(--filled-border);
    }

    .total-cell {
      font-weight: 700;
      color: var(--success);
      font-size: 14px;
    }


    /* ── Carton Grid ─────────────────────────────────────────────────────── */
    .carton-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 14px;
    }

    .carton-grid-2 {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 14px;
    }

    /* ── Three-column specs layout ── */
    .specs-three-col {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 0;
    }
    .specs-col {
      display: flex;
      flex-direction: column;
      gap: 16px;
      padding: 0 24px;
      border-right: 1px solid var(--border);
    }
    .specs-col:first-child { padding-left: 0; }
    .specs-col:last-child { padding-right: 0; border-right: none; }
    .specs-col-title {
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      color: var(--text-muted);
      padding-bottom: 8px;
      border-bottom: 1px solid var(--border);
      margin-bottom: 0;
    }
    .specs-col .field { margin-bottom: 0; }
    .specs-col .field input {
      box-sizing: border-box;
    }
    /* ── Specs dimension grid ── */
    .specs-dim-grid {
      display: grid;
      grid-template-columns: 52px 1fr 1fr;
      gap: 5px 8px;
      align-items: center;
    }
    .specs-unit-header {
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      color: var(--text-muted);
      text-align: center;
    }
    .specs-row-label {
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--text-muted);
      white-space: nowrap;
    }
    .specs-dim-grid input,
    .specs-dim-grid select {
      width: 100%;
      box-sizing: border-box;
    }
    .specs-input-wrap {
      position: relative;
      width: 100%;
    }
    .specs-input-wrap input {
      width: 100%;
      padding-right: 30px;
      box-sizing: border-box;
    }
    .specs-unit-tag {
      position: absolute;
      right: 9px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 11px;
      font-weight: 600;
      color: var(--text-muted);
      pointer-events: none;
      user-select: none;
      opacity: 0.55;
    }
    .specs-dim-divider {
      grid-column: 1 / -1;
      border: none;
      border-top: 1px solid var(--border);
      margin: 4px 0;
    }
    .specs-full-row {
      grid-column: 1 / -1;
    }
    .dim-dual {
      display: flex;
      gap: 8px;
    }
    .dim-dual-field {
      flex: 1;
      position: relative;
    }
    .dim-dual-field input {
      width: 100%;
      padding-right: 30px;
      box-sizing: border-box;
    }
    .dim-unit {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 11px;
      color: var(--text-muted);
      pointer-events: none;
    }
    /* Hide number spinners globally */
    input[type="number"]::-webkit-outer-spin-button,
    input[type="number"]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    input[type="number"] { -moz-appearance: textbox; }

    /* ── Info Pill ───────────────────────────────────────────────────────── */
    .info-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: var(--accent-glow);
      border: 1px solid var(--accent);
      border-radius: 20px;
      padding: 4px 12px;
      font-size: 12px;
      color: var(--accent);
      font-weight: 600;
      margin-bottom: 16px;
    }

    /* ── Views ──────────────────────────────────────────────────────────── */
    .view { display: none; }
    .view.active { display: block; }

    /* ── Dashboard Table ────────────────────────────────────────────────── */
    .dash-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 24px;
    }
    .dash-title {
      font-size: 22px;
      font-weight: 700;
      color: var(--text);
    }

    .dash-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 14px;
      table-layout: fixed;
    }

    .dash-table th {
      text-align: left;
      padding: 14px 8px;
      font-size: 13px;
      font-weight: 600;
      color: var(--text-muted);
      border-bottom: 1px solid var(--border);
    }
    .dash-table th:first-child { padding-left: 16px; }

    .dash-table th:nth-child(1) { width: 20%; }   /* Product */
    .dash-table th:nth-child(2) { width: 100px; } /* Date Created */
    .dash-table th:nth-child(3) { width: 100px; } /* Date Submitted */
    .dash-table th:nth-child(4) { width: 110px; } /* Order */
    .dash-table th:nth-child(5) { width: auto; }  /* Fulfilment (Flow) — takes remaining space */
    .dash-table th:nth-child(7) { width: 54px; }  /* Actions */

    .dash-table td {
      padding: 18px 8px;
      border-bottom: 1px solid var(--border);
      vertical-align: middle;
    }

    .dash-table tbody tr {
      cursor: pointer;
      transition: background 0.1s;
    }

    .dash-table tbody tr:hover {
      background: var(--surface2);
    }

    .dash-table tbody tr.row-complete {
      background: rgba(52,211,153,0.08);
    }

    .dash-table tbody tr.row-complete:hover {
      background: rgba(52,211,153,0.14);
    }

    .status-badge {
      display: inline-block;
      padding: 3px 10px;
      border-radius: 12px;
      font-size: 11px;
      font-weight: 600;
    }

    .status-badge.complete {
      background: rgba(52,211,153,0.15);
      color: var(--success);
    }

    .status-badge.in-progress {
      background: var(--accent-glow);
      color: var(--accent);
    }

    .col-order { white-space: nowrap; }

    .wb-order-pill {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-size: 11px;
      font-weight: 600;
      padding: 2px 8px;
      border-radius: 10px;
      background: rgba(107,147,255,0.15);
      color: #6b93ff;
      border: 1px solid rgba(107,147,255,0.3);
      cursor: pointer;
      width: fit-content;
      transition: background 0.15s;
      white-space: nowrap;
    }
    .wb-order-pill:hover { background: rgba(107,147,255,0.25); }
    .wb-order-pill--closed {
      background: rgba(52,211,153,0.12);
      color: var(--success);
      border-color: rgba(52,211,153,0.25);
    }
    .wb-order-pill--closed:hover { background: rgba(52,211,153,0.22); }

    .dash-table .product-name {
      font-weight: 600;
      color: var(--product-name-color, var(--text));
      padding-left: 16px !important;
    }

    .dash-table .desc-cell {
      color: var(--text-muted);
      max-width: 200px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    /* ── Fulfilment Flow Indicators ─────────────────────────────────────── */
    .flow-group {
      display: flex;
      gap: 6px;
    }

    .flow-step {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 4px;
    }

    .flow-bar {
      width: 46px;
      height: 8px;
      border-radius: 4px;
      background: var(--border);
    }

    .flow-bar.filled {
      background: var(--success);
    }

    .flow-label {
      font-size: 9px;
      color: var(--text-muted);
      white-space: nowrap;
      text-align: center;
    }

    /* ── Action Icon Button ─────────────────────────────────────────────── */
    .dash-table td:last-child,
    .dash-table th:last-child {
      position: relative;
      text-align: right;
      padding-right: 18px !important;
      width: 60px;
      min-width: 60px;
    }

    .action-icon-btn {
      background: none;
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      color: var(--text-muted);
      font-size: 18px;
      font-weight: 700;
      width: 32px;
      height: 32px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      transition: all 0.15s;
      line-height: 1;
    }

    .action-icon-btn:hover {
      background: var(--surface2);
      color: var(--text);
    }

    .action-menu {
      display: none;
      position: fixed;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      box-shadow: 0 4px 16px rgba(0,0,0,0.15);
      z-index: 9999;
      min-width: 100px;
      overflow: hidden;
    }

    .action-menu.open {
      display: block;
    }

    .action-menu a {
      display: block;
      padding: 8px 14px;
      font-size: 13px;
      color: var(--text);
      cursor: pointer;
      transition: background 0.1s;
      text-decoration: none;
    }

    .action-menu a:hover {
      background: var(--surface2);
    }

    /* ── Workbook Status Bar ───────────────────────────────────────────── */
    .status-bar {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 20px 24px;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 24px;
      box-shadow: var(--shadow);
    }

    .status-bar-left {
      display: flex;
      align-items: center;
      gap: 24px;
      flex: 1;
    }

    .status-label {
      font-size: 13px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--text-muted);
    }

    .status-label span {
      color: var(--success);
      font-size: 14px;
    }

    .status-flow {
      display: flex;
      gap: 8px;
      flex: 1;
      justify-content: center;
    }

    .status-step {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 5px;
    }

    .status-step-bar {
      width: 60px;
      height: 10px;
      border-radius: 5px;
      background: var(--border);
      transition: background 0.2s;
    }

    .status-step-bar.filled {
      background: var(--success);
    }

    .status-step-label {
      font-size: 11px;
      color: var(--text-muted);
      text-align: center;
    }

    .btn-advance {
      background: var(--accent);
      color: #fff;
      border: none;
      border-radius: var(--radius-sm);
      padding: 10px 20px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      transition: opacity 0.15s;
      white-space: nowrap;
      min-width: 180px;
    }

    .btn-advance:hover { opacity: 0.85; }

    .btn-back-step {
      background: var(--accent);
      border: 2px solid var(--accent);
      border-radius: var(--radius-sm);
      color: #ffffff;
      font-size: 18px;
      font-weight: 900;
      width: 36px;
      height: 38px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      transition: all 0.15s;
    }
    .btn-back-step:hover { opacity: 0.85; }
    .btn-back-step:disabled { opacity: 0.3; cursor: default; }

    .btn-advance:disabled {
      background: var(--success);
      opacity: 0.7;
      cursor: default;
    }

    /* ── Home View ──────────────────────────────────────────────────────── */
    .home-welcome {
      text-align: center;
      padding: 80px 20px;
      color: var(--text-muted);
    }
    .home-welcome h2 {
      font-size: 24px;
      color: var(--text);
      margin-bottom: 8px;
    }

    /* ── Segment Control (reusable tab style) ──────────────────────────── */
    .seg-control {
      display: flex;
      background: var(--surface2);
      border-radius: 8px;
      padding: 3px;
      gap: 3px;
    }

    .seg-tab {
      flex: 1;
      padding: 7px 14px;
      font-size: 13px;
      font-weight: 600;
      font-family: inherit;
      color: var(--text-muted);
      background: transparent;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.15s;
      white-space: nowrap;
      box-shadow: none;
    }

    .seg-tab.active {
      background: var(--surface);
      color: var(--text);
      box-shadow: 0 1px 3px rgba(0,0,0,0.15);
    }

    .seg-tab:not(.active):hover {
      color: var(--text);
    }

    /* ── Workbook Tabs ──────────────────────────────────────────────────── */
    .wb-tabs {
      display: flex;
      background: var(--surface2);
      border-radius: 8px;
      padding: 3px;
      gap: 3px;
      align-items: center;
    }

    .wb-tab {
      padding: 7px 20px;
      font-size: 13px;
      font-weight: 600;
      color: var(--text-muted);
      background: transparent;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.15s;
      white-space: nowrap;
      box-shadow: none;
    }

    .wb-tab.active {
      background: var(--surface);
      color: var(--text);
      box-shadow: 0 1px 3px rgba(0,0,0,0.15);
    }

    .wb-tab:not(.active):hover {
      color: var(--text);
    }

    .wb-tab-content {
      display: none;
    }

    .wb-tab-content.active {
      display: block;
    }

    /* ── Sticky Bar ─────────────────────────────────────────────────────── */
    .wb-sticky-bar {
      position: sticky;
      top: 64px;
      z-index: 90;
      background: var(--bg);
      padding: 16px 0 12px;
      margin: -32px 0 16px;
      padding-top: 32px;
    }

    /* ── Back Button ────────────────────────────────────────────────────── */
    .btn-back {
      background: none;
      border: none;
      color: var(--accent);
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 0 0 0 16px;
      margin-bottom: 16px;
    }
    .btn-back:hover { opacity: 0.7; }

    /* ── Modal ──────────────────────────────────────────────────────────── */
    .modal-overlay {
      display: none;
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.5);
      z-index: 500;
      align-items: center;
      justify-content: center;
    }

    .modal-overlay.open {
      display: flex;
    }

    .modal {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      box-shadow: 0 8px 40px rgba(0,0,0,0.3);
      width: 480px;
      max-width: 90vw;
      padding: 28px;
    }
    .diff-panel { background: var(--surface2); border-radius: 6px; padding: 10px 12px; margin-top: 8px; display: none; }
    .diff-panel.open { display: block; }
    .diff-row { display: flex; align-items: baseline; gap: 8px; padding: 4px 0; font-size: 12px; border-bottom: 1px solid var(--border); }
    .diff-row:last-child { border-bottom: none; }
    .diff-label { font-weight: 600; color: var(--text-muted); min-width: 120px; flex-shrink: 0; font-size: 11px; }
    .diff-old { color: #c0392b; text-decoration: line-through; }
    .diff-new { color: #27ae60; font-weight: 600; }
    .diff-arrow { color: var(--text-muted); margin: 0 2px; }
    .diff-toggle { background: none; border: none; color: var(--primary); font-size: 11px; cursor: pointer; padding: 4px 0; margin-top: 4px; }
    .diff-toggle:hover { text-decoration: underline; }
    .archive-item { display:flex; justify-content:space-between; align-items:center; padding:11px 10px; border-bottom:1px solid var(--border); border-radius:6px; margin:0 -10px; cursor:default; transition: background 0.12s; }
    .archive-item:hover { background: var(--surface2); }
    #sidebar-recent-dropdown a:hover { background: var(--surface2); }

    .modal-title {
      font-size: 18px;
      font-weight: 700;
      color: var(--text);
      margin-bottom: 20px;
    }

    .modal-field {
      margin-bottom: 16px;
    }

    .modal-field label {
      display: block;
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      color: var(--text-muted);
      margin-bottom: 6px;
    }

    .modal-field label .required {
      color: var(--danger);
    }

    .modal-field input,
    .modal-field select,
    .modal-field textarea {
      width: 100%;
      padding: 9px 12px;
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      background: var(--surface2);
      color: var(--text);
      font-size: 14px;
      font-family: inherit;
    }

    .modal-field textarea {
      min-height: 80px;
      resize: vertical;
    }

    .modal-field input:focus,
    .modal-field select:focus,
    .modal-field textarea:focus {
      outline: none;
      border-color: var(--accent);
      box-shadow: 0 0 0 3px var(--accent-glow);
    }

    .modal-actions {
      display: flex;
      gap: 10px;
      justify-content: flex-end;
      margin-top: 24px;
    }

    .btn-cancel {
      background: transparent;
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      padding: 9px 18px;
      font-size: 14px;
      font-weight: 600;
      color: var(--text-muted);
      cursor: pointer;
    }

    .btn-cancel:hover { background: var(--surface2); }

    .btn-create {
      background: var(--accent);
      color: #fff;
      border: none;
      border-radius: var(--radius-sm);
      padding: 9px 24px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
    }

    .btn-create:hover { opacity: 0.85; }

    .btn-danger {
      background: #e53e3e;
      color: #fff;
      border: none;
      border-radius: var(--radius-sm);
      padding: 9px 24px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
    }

    .btn-danger:hover { background: #c53030; }

    /* ── Add Workbook Button (sidebar) ─────────────────────────────────── */
    .sidebar-add-btn {
      display: flex;
      align-items: center;
      justify-content: flex-start;
      gap: 6px;
      margin: 0;
      padding: 9px 12px;
      background: var(--sidebar-btn);
      color: #fff;
      border: none;
      border-radius: var(--radius-sm);
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: opacity 0.15s;
    }

    .sidebar-add-btn:hover { opacity: 0.85; }

    /* ── Pricing Tab Summary ─────────────────────────────────────────────── */
    .pricing-summary-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
      margin-bottom: 16px;
    }
    @media (max-width: 680px) {
      .pricing-summary-grid { grid-template-columns: 1fr; }
    }
    .pricing-cost-block {
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 20px 22px;
      background: var(--bg);
    }
    .pricing-cost-block-title {
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--text-muted);
      margin-bottom: 14px;
    }
    .pricing-cost-row {
      display: flex;
      justify-content: space-between;
      align-items: baseline;
      padding: 6px 0;
      font-size: 13px;
      border-bottom: 1px solid var(--border);
    }
    .pricing-cost-row:last-child { border-bottom: none; }
    .pricing-cost-row-label { color: var(--text-muted); }
    .pricing-cost-row-value { font-weight: 600; color: var(--text); }
    .pricing-cost-subtotal {
      margin-top: 10px;
      padding-top: 10px;
      border-top: 2px solid var(--border);
    }
    .pricing-cost-subtotal .pricing-cost-row-label { font-weight: 700; color: var(--text); font-size: 13px; }
    .pricing-cost-subtotal .pricing-cost-row-value { font-size: 15px; color: var(--accent); }
    .pricing-grand-total-bar {
      background: var(--accent);
      color: #fff;
      border-radius: 10px;
      padding: 18px 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .pricing-grand-total-label { font-size: 13px; font-weight: 600; opacity: 0.88; }
    .pricing-grand-total-value { font-size: 26px; font-weight: 800; letter-spacing: -0.02em; }
    .qr-collapsed-summary {
      display: flex;
      gap: 32px;
      align-items: center;
      flex-wrap: wrap;
      margin: 0 16px 0 24px;
    }
    .qr-sum-item { display: flex; flex-direction: column; gap: 2px; }
    .qr-sum-label {
      font-size: 9px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      color: var(--text-muted);
    }
    .qr-sum-val { font-size: 13px; font-weight: 700; color: var(--text); }
    .qr-expand-toggle {
      display: flex;
      align-items: center;
      gap: 6px;
      flex-shrink: 0;
      margin-left: auto;
      padding-right: 6px;
    }
    .qr-expand-label {
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      color: var(--text-muted);
    }
    .qr-expand-toggle .section-chevron { margin-left: 0; padding-right: 2px; font-size: 14px; line-height: 1; vertical-align: middle; position: relative; top: -1px; }

    .pricing-quote-ref-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .pricing-quote-ref-table th {
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      color: var(--text-muted);
      padding: 6px 10px 8px;
      text-align: left;
      border-bottom: 2px solid var(--border);
      white-space: nowrap;
    }
    .pricing-quote-ref-table td {
      padding: 9px 10px;
      border-bottom: 1px solid var(--border);
      vertical-align: middle;
    }
    .pricing-quote-ref-table tbody tr:last-child td { border-bottom: none; }
    .pricing-quote-ref-table .main-row { background: rgba(232,117,26,0.05); }
    .pricing-no-selection {
      padding: 24px 0;
      color: var(--text-muted);
      font-size: 13px;
      font-style: italic;
      text-align: center;
    }

    /* ── Shipping Calculator ─────────────────────────────────────────────── */
    .freight-calc {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 24px;
    }

    .freight-panel {
      background: var(--surface2);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 18px;
    }

    .freight-panel-title {
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      color: var(--text-muted);
      margin-bottom: 14px;
      padding-bottom: 8px;
      border-bottom: 1px solid var(--border);
    }

    .freight-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      margin-bottom: 10px;
    }

    .freight-field label {
      display: block;
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.03em;
      color: var(--text-muted);
      margin-bottom: 4px;
    }

    .freight-field input,
    .freight-field select {
      width: 100%;
      padding: 8px 10px;
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      background: var(--surface);
      color: var(--text);
      font-size: 13px;
      font-family: 'SF Mono', 'Consolas', 'Monaco', monospace;
      height: 38px;
      box-sizing: border-box;
    }

    .freight-field select {
      font-family: inherit;
    }

    /* Outer carton info display */
    .freight-carton-info {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      padding: 10px 14px;
      margin-bottom: 12px;
    }

    .freight-info-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 5px 0;
      font-size: 13px;
      border-bottom: 1px solid var(--border);
    }

    .freight-info-row:last-child { border-bottom: none; }

    .freight-info-label {
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.03em;
      color: var(--text-muted);
      min-width: 52px;
    }

    .freight-info-vals {
      display: flex;
      align-items: center;
      gap: 4px;
      font-family: 'SF Mono', 'Consolas', monospace;
      font-size: 12px;
    }

    .freight-info-val {
      font-weight: 700;
      color: var(--text);
      min-width: 42px;
      text-align: right;
    }

    .freight-info-unit {
      font-size: 10px;
      color: var(--text-muted);
      font-weight: 600;
      min-width: 18px;
    }

    .freight-info-sep {
      color: var(--border);
      padding: 0 4px;
      font-weight: 300;
    }

    .freight-no-dims {
      font-size: 12px;
      color: var(--text-muted);
      font-style: italic;
      text-align: center;
      padding: 10px 0;
    }

    /* Rate row with USD equivalent */
    .freight-rate-row {
      display: flex;
      align-items: flex-end;
      gap: 10px;
      margin-bottom: 10px;
    }

    .freight-rate-row .freight-field { flex: 1; margin-bottom: 0; }

    .freight-rate-usd {
      display: flex;
      align-items: center;
      gap: 3px;
      padding-bottom: 9px;
      white-space: nowrap;
    }

    .freight-rate-usd-label {
      font-size: 13px;
      color: var(--text-muted);
    }

    .freight-rate-usd-val {
      font-family: 'SF Mono', 'Consolas', monospace;
      font-size: 13px;
      font-weight: 700;
      color: var(--success);
    }

    .freight-rate-usd-unit {
      font-size: 11px;
      color: var(--text-muted);
    }

    .freight-section-divider {
      margin: 16px 0;
      border: none;
      border-top: 1px solid var(--border);
    }

    /* ── Shipping tab layout ── */
    .sh-layout { display: flex; flex-direction: column; gap: 16px; }

    .sh-top-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
      align-items: stretch;
    }
    .sh-left-col { display: flex; flex-direction: column; gap: 16px; height: 100%; }
    .sh-left-col .sh-box:last-child { flex: 1; }

    .sh-box {
      background: var(--surface2);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 16px 18px;
    }
    .sh-box-title {
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      color: var(--text-muted);
      margin-bottom: 12px;
      padding-bottom: 8px;
      border-bottom: 1px solid var(--border);
    }
    .sh-results-box { /* inherits .sh-box */ }
    .sh-shipping-box { /* full-width bottom */ }

    @media (max-width: 700px) {
      .sh-top-row { grid-template-columns: 1fr; }
    }

    /* Compact dim rows */
    .sh-dim-table { display: flex; flex-direction: column; gap: 6px; }
    .sh-dim-row {
      display: flex;
      align-items: center;
      gap: 5px;
      font-family: 'SF Mono', 'Consolas', monospace;
      font-size: 13px;
    }
    .sh-dim-row-wt {
      margin-top: 4px;
      padding-top: 8px;
      border-top: 1px solid var(--border);
    }
    .sh-dim-lbl {
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      color: var(--text-muted);
      min-width: 18px;
    }
    .sh-dim-val { font-weight: 700; color: var(--text); min-width: 52px; text-align: right; }
    .sh-dim-unit { font-size: 10px; color: var(--text-muted); font-weight: 600; min-width: 16px; }
    .sh-dim-sep { color: var(--border); font-weight: 300; padding: 0 2px; }

    /* Pallet stats panel in shipping tab */
    .sh-pallet-stats-panel {
      background: var(--surface2);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 16px 20px;
    }
    .sh-pallet-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px 24px;
    }
    .sh-pallet-stat { }
    .sh-pallet-stat-val {
      font-size: 22px;
      font-weight: 700;
      color: var(--text);
      line-height: 1;
    }
    .sh-pallet-stat-val.accent { color: var(--accent); }
    .sh-pallet-stat-lbl {
      font-size: 11px;
      color: var(--text-muted);
      margin-top: 3px;
    }
    .sh-pallet-divider {
      grid-column: span 2;
      border: none;
      border-top: 1px solid var(--border);
      margin: 4px 0;
    }
    .sh-pallet-section-label {
      grid-column: span 2;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      color: var(--text-muted);
    }

    /* Shipping method bar */
    .freight-method-rate-row { margin-bottom: 14px; }
    .freight-method-label {
      display: block;
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.03em;
      color: var(--text-muted);
      margin-bottom: 5px;
    }
    .freight-method-bar {
      display: flex;
      align-items: center;
      gap: 8px;
      height: 40px;
    }
    .freight-method-bar-select {
      flex: 1;
      min-width: 0;
      height: 100%;
    }
    .freight-method-bar-select select {
      width: 100%;
      height: 100%;
      padding: 0 10px;
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      background: var(--surface);
      color: var(--text);
      font-size: 13px;
      font-family: inherit;
      outline: none;
      cursor: pointer;
      appearance: auto;
    }
    /* Pill badges */
    .freight-method-bar-rate {
      display: flex;
      align-items: center;
      gap: 5px;
      padding: 0 14px;
      height: 100%;
      border-radius: 20px;
      border: 1.5px solid var(--border);
      background: var(--surface2);
      white-space: nowrap;
    }
    .freight-method-bar-sym { font-size: 14px; font-weight: 700; }
    .freight-method-bar-val {
      font-family: 'SF Mono', 'Consolas', monospace;
      font-size: 14px;
      font-weight: 700;
    }
    .freight-method-bar-unit { font-size: 10px; color: var(--text-muted); font-weight: 600; }
    .freight-method-bar-rate.rmb { border-color: color-mix(in srgb, var(--accent) 40%, var(--border)); }
    .freight-method-bar-rate.rmb .freight-method-bar-sym,
    .freight-method-bar-rate.rmb .freight-method-bar-val { color: var(--accent); }
    .freight-method-bar-rate.usd { border-color: color-mix(in srgb, var(--success) 40%, var(--border)); }
    .freight-method-bar-rate.usd .freight-method-bar-sym,
    .freight-method-bar-rate.usd .freight-method-bar-val { color: var(--success); }

    /* Cartons display (read-only) */
    .sh-cartons-display {
      display: flex;
      align-items: baseline;
      gap: 6px;
      margin-top: 14px;
    }
    .sh-cartons-label {
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--text-muted);
    }
    .sh-cartons-val {
      font-size: 20px;
      font-weight: 700;
      color: var(--text);
      font-family: 'SF Mono', 'Consolas', monospace;
    }
    .sh-cartons-unit {
      font-size: 12px;
      color: var(--text-muted);
    }

    /* Comparison table with 5 columns */
    .freight-cmp-table th,
    .freight-cmp-table td {
      text-align: right;
      font-size: 11px;
    }
    .freight-cmp-table th:first-child,
    .freight-cmp-table td:first-child {
      text-align: left;
    }

    /* Collapsible shipping sub-section */
    .freight-subsection-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      cursor: pointer;
      user-select: none;
      margin: 0 -18px;
      padding: 8px 18px;
      border-top: 1px solid var(--border);
      border-bottom: 1px solid var(--border);
      background: var(--surface2);
      margin-bottom: 14px;
    }
    .freight-subsection-header:hover {
      background: color-mix(in srgb, var(--surface2) 85%, var(--border));
    }
    .freight-subsection-title {
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      color: var(--text-muted);
    }
    .freight-subsection-chevron {
      font-size: 16px;
      color: var(--text-muted);
      transition: transform 0.2s;
      line-height: 1;
    }
    .freight-subsection-header.collapsed .freight-subsection-chevron {
      transform: rotate(-90deg);
    }
    .freight-subsection-body {
      overflow: hidden;
      transition: max-height 0.25s ease, opacity 0.2s;
      max-height: 600px;
      opacity: 1;
    }
    .freight-subsection-body.collapsed {
      max-height: 0;
      opacity: 0;
    }

    .freight-result {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 0;
      border-bottom: 1px solid var(--border);
      font-size: 13px;
    }

    .freight-result:last-child {
      border-bottom: none;
    }

    .freight-result-label {
      color: var(--text-muted);
      font-weight: 500;
    }

    .freight-result-value {
      font-family: 'SF Mono', 'Consolas', 'Monaco', monospace;
      font-weight: 700;
      color: var(--text);
    }

    .freight-result-value.highlight {
      font-size: 16px;
      color: var(--accent);
    }

    .freight-result-value.cost {
      font-size: 16px;
      color: var(--success);
    }

    .freight-verdict {
      margin-top: 14px;
      padding: 12px 14px;
      border-radius: var(--radius-sm);
      font-size: 13px;
      font-weight: 600;
    }

    .freight-verdict.volumetric {
      background: rgba(251,191,36,0.1);
      border: 1px solid rgba(251,191,36,0.3);
      color: #f59e0b;
    }

    .freight-verdict.actual {
      background: rgba(52,211,153,0.1);
      border: 1px solid rgba(52,211,153,0.3);
      color: var(--success);
    }

    .freight-verdict.equal {
      background: var(--accent-glow);
      border: 1px solid var(--accent);
      color: var(--accent);
    }

    .freight-bars {
      display: flex;
      gap: 12px;
      align-items: flex-end;
      height: 80px;
      margin: 14px 0;
      padding: 0 10px;
    }

    .freight-bar-col {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 4px;
      height: 100%;
      justify-content: flex-end;
    }

    .freight-bar {
      width: 100%;
      border-radius: 4px 4px 0 0;
      transition: height 0.3s ease;
      min-height: 4px;
    }

    .freight-bar.actual-bar { background: var(--accent); }
    .freight-bar.vol-bar { background: #f59e0b; }
    .freight-bar.charge-bar { background: var(--success); }

    .freight-bar-label {
      font-size: 10px;
      color: var(--text-muted);
      text-align: center;
      white-space: nowrap;
    }

    .freight-bar-val {
      font-size: 11px;
      font-weight: 700;
      font-family: 'SF Mono', 'Consolas', monospace;
      color: var(--text);
    }

    .freight-tip {
      margin-top: 10px;
      padding: 10px 12px;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      font-size: 12px;
      color: var(--text-muted);
      line-height: 1.5;
    }

    .freight-ref-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 12px;
      margin-top: 14px;
    }

    .freight-ref-table th,
    .freight-ref-table td {
      padding: 6px 10px;
      border: 1px solid var(--border);
      text-align: left;
    }

    .freight-ref-table th {
      background: var(--surface2);
      color: var(--text-muted);
      font-weight: 700;
      font-size: 11px;
      text-transform: uppercase;
    }

    .freight-extra {
      margin-top: 8px;
      font-size: 12px;
      color: var(--text-muted);
    }

    .freight-extra span {
      font-weight: 700;
      color: #f59e0b;
    }

    @media (max-width: 768px) {
      .freight-calc { grid-template-columns: 1fr; }
    }

    /* ── Header currency calculator ─────────────────────────────────────── */
    .fx-calc {
      display: flex;
      align-items: center;
      gap: 4px;
      background: var(--surface2);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 0 10px;
      height: 30px;
      flex-shrink: 0;
    }
    .fx-calc-sym {
      font-size: 12px;
      font-weight: 700;
      color: var(--text-muted);
      flex-shrink: 0;
      line-height: 1;
    }
    .fx-calc-input {
      width: 90px;
      background: transparent;
      border: none;
      outline: none;
      font-size: 12px;
      font-weight: 600;
      color: var(--text);
      font-family: inherit;
      text-align: right;
      -moz-appearance: textfield;
      padding: 0;
    }
    .fx-calc-input::placeholder { color: var(--text-muted); opacity: 0.5; font-weight: 400; }
    .fx-calc-input::-webkit-outer-spin-button,
    .fx-calc-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .fx-calc-divider {
      width: 1px;
      height: 16px;
      background: var(--border);
      flex-shrink: 0;
      margin: 0 3px;
    }
    @media (max-width: 768px) { .fx-calc { display: none; } }

    /* ── Print ───────────────────────────────────────────────────────────── */
    @media print {
      .sidebar { display: none !important; }
      .app-content { margin-left: 0 !important; }
      .app-header, .header-actions, .btn-add, .btn-danger-ghost,
      .image-add-btn, .img-remove,
      .legend { display: none !important; }
      body { background: #fff; color: #000; }
      .section-card { box-shadow: none; border: 1px solid #ccc; break-inside: avoid; }
      .container { padding: 16px; }
    }

    /* ── Mobile: Base Helpers ────────────────────────────────────────────── */
    .hamburger-btn {
      display: none;
      align-items: center;
      justify-content: center;
      width: 44px;
      height: 44px;
      background: none;
      border: none;
      font-size: 24px;
      color: var(--text);
      cursor: pointer;
      flex-shrink: 0;
    }
    .sidebar-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.5);
      z-index: 199;
    }
    .sidebar-overlay.open { display: block; }
    body.sidebar-open { overflow: hidden; }
    .table-scroll-wrapper {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }
    .label-short { display: none; }
    .label-full { display: inline; }
    .tab-short { display: none; }
    .tab-full { display: inline; }
    .date-short { display: none; }
    .date-full { display: inline; }
    .col-mobile-status { display: none; }
    .mobile-status-badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 11px;
      font-weight: 600;
      white-space: nowrap;
    }
    .mobile-status-badge.complete {
      background: rgba(52, 211, 153, 0.15);
      color: #059669;
    }
    .mobile-status-badge.in-progress {
      background: rgba(99, 102, 241, 0.12);
      color: var(--accent);
    }

    /* ── Responsive ──────────────────────────────────────────────────────── */
    @media (max-width: 768px) {
      /* Hamburger visible */
      .hamburger-btn { display: flex; }

      /* Sidebar: slide-out drawer instead of hidden */
      .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
      }
      .sidebar.mobile-open {
        transform: translateX(0);
      }

      .app-content { margin-left: 0 !important; }

      /* Contain ALL content within viewport */
      html, body { overflow-x: hidden; max-width: 100vw; }
      .app-layout { overflow-x: hidden; max-width: 100vw; }
      .app-content { overflow-x: hidden; max-width: 100vw; width: 100%; }
      .view { overflow-x: hidden; max-width: 100%; }
      .container { padding: 16px 10px 40px !important; max-width: 100%; box-sizing: border-box; overflow-x: hidden; }
      .section-card { max-width: 100%; box-sizing: border-box; overflow: hidden !important; }
      .section-body { max-width: 100%; box-sizing: border-box; overflow: hidden !important; }
      .section-body[style*="padding:0"] { overflow-x: auto !important; }
      .table-scroll-wrapper { overflow-x: auto !important; -webkit-overflow-scrolling: touch; max-width: 100%; }
      .status-bar { max-width: 100%; box-sizing: border-box; }
      .wb-sticky-bar { max-width: 100%; box-sizing: border-box; }
      .form-grid, .form-grid-2, .form-grid-3 { max-width: 100%; box-sizing: border-box; }

      /* Compact header */
      .app-header {
        padding: 0 8px !important;
        height: 56px !important;
        gap: 6px;
        width: 100vw !important;
        max-width: 100vw !important;
        box-sizing: border-box !important;
        position: sticky !important;
        top: 0 !important;
        z-index: 100 !important;
      }
      #header-title { font-size: 13px !important; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1 1 0; min-width: 0; max-width: calc(100vw - 160px); padding-left: 8px !important; margin-left: 4px; }
      .header-actions { padding-right: 4px; }
      .header-actions { gap: 4px; flex-shrink: 0; }
      .header-actions .btn,
      .header-actions .theme-toggle {
        padding: 0 !important;
        font-size: 18px;
        white-space: nowrap;
        min-width: 38px !important;
        max-width: 38px !important;
        min-height: 38px !important;
        max-height: 38px !important;
        width: 38px !important;
        height: 38px !important;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--border) !important;
        border-radius: var(--radius-sm) !important;
        background: var(--surface2) !important;
        box-sizing: border-box !important;
      }
      .header-actions .btn .btn-label,
      .header-actions .theme-toggle .toggle-label { display: none; }
      .header-actions .btn.btn-ghost {
        background: var(--surface2) !important;
        border: 1px solid var(--border) !important;
      }

      /* Sticky bar offset for smaller header */
      .wb-sticky-bar {
        position: relative !important;
        top: auto !important;
        padding: 8px 0 4px !important;
        margin: 0 !important;
        max-width: 100%;
      }
      .wb-sticky-bar > div {
        flex-direction: column !important;
        gap: 6px;
        align-items: flex-start !important;
        max-width: 100%;
      }

      /* Scrollable tabs */
      .wb-tabs {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        max-width: 100%;
        width: 100%;
        gap: 2px;
      }
      .wb-tabs::-webkit-scrollbar { display: none; }
      .wb-tab { flex: 1; padding: 6px 4px; font-size: 11px; white-space: nowrap; text-align: center; min-width: 0; }
      .wb-tab .tab-full { display: none; }
      .wb-tab .tab-short { display: inline; }

      /* Status bar — compact mobile layout */
      .status-bar {
        flex-direction: column;
        padding: 10px 12px;
        gap: 8px;
        overflow: visible !important;
        box-sizing: border-box;
      }
      .status-bar-left {
        flex-direction: column;
        gap: 6px;
        align-items: flex-start;
        width: 100%;
      }
      .status-label { font-size: 11px; }
      .status-flow {
        display: flex;
        gap: 0;
        flex-wrap: nowrap;
        justify-content: space-between;
        width: 100%;
      }
      .status-step {
        flex: 1;
        align-items: center;
        flex-direction: column-reverse;
        gap: 3px;
      }
      .status-step-bar { width: 100%; max-width: 36px; height: 5px; border-radius: 3px; }
      .status-step-label { font-size: 8px; line-height: 1.1; text-align: center; }
      .label-full { display: none !important; }
      .label-short { display: inline !important; }
      .status-actions {
        width: 100%;
        box-sizing: border-box;
        display: flex;
        gap: 6px;
      }
      .btn-advance {
        min-width: unset !important;
        flex: 1;
        padding: 8px 12px;
        font-size: 12px;
        box-sizing: border-box;
      }
      .btn-back-step { flex-shrink: 0; width: 36px; height: 36px; }

      /* Form grids single column */
      .form-grid-2, .form-grid-3 { grid-template-columns: 1fr; }
      .col-span-2, .col-span-3 { grid-column: span 1; }

      /* Dashboard table — mobile card layout */
      .dash-table { table-layout: auto; min-width: unset !important; display: table; width: 100%; }
      .dash-table thead, .dash-table tbody { display: table-row-group; width: 100%; min-width: unset; }
      .col-flow, .col-date-submitted, .col-client { display: none !important; }
      .col-mobile-status { display: table-cell !important; text-align: right !important; }
      .date-full { display: none !important; }
      .date-short { display: inline !important; }
      .dash-table { table-layout: fixed !important; }
      .dash-table td, .dash-table th { padding: 12px 6px; font-size: 13px; vertical-align: middle; }
      .dash-table td:first-child, .dash-table th:first-child { padding-left: 14px; width: 38%; }
      .dash-table .col-date-created { width: 12%; text-align: left; padding-left: 0 !important; }
      .dash-table .col-mobile-status { width: 30%; text-align: right !important; padding-right: 4px !important; }
      .dash-table td:last-child, .dash-table th:last-child { width: 16%; padding-right: 14px; text-align: right; }
      .dash-table .product-name { font-size: 14px; }
      .dash-table .product-name .status-badge { display: none !important; }
      .dash-table .action-icon-btn { width: 32px; height: 32px; font-size: 16px; min-height: unset; }
      .action-menu { right: 0; min-width: 120px; }

      /* Touch targets */
      input, textarea, select { padding: 11px 12px; font-size: 16px !important; max-width: 100%; box-sizing: border-box; }
      select { height: 44px; }
      input[type="number"] { }
      .btn { padding: 10px 16px; font-size: 14px; min-height: 44px; }
      .btn-danger-ghost { padding: 8px 12px; min-height: 44px; }
      .action-icon-btn { width: 44px; height: 44px; font-size: 20px; }
      .nav-item { padding: 12px 12px; min-height: 44px; }
      .sidebar-add-btn { padding: 12px; min-height: 44px; font-size: 14px; }
      .btn-back { min-height: 44px; padding: 8px 0; }

      /* Modals */
      .modal { padding: 20px 16px; max-height: 90vh; overflow-y: auto; }
      .modal-overlay { padding: 16px; }
      .modal-title { font-size: 16px; margin-bottom: 16px; }
      .modal-actions { flex-direction: column; gap: 8px; }
      .modal-actions .btn-cancel,
      .modal-actions .btn-create,
      .modal-actions .btn-danger {
        width: 100%;
        text-align: center;
        justify-content: center;
        min-height: 44px;
      }

      /* Font sizes */
      label { font-size: 12px; }
      .flow-label { font-size: 10px; }
      .status-step-label { font-size: 10px; }
      .flow-bar { width: 36px; height: 6px; }
      .flow-step { gap: 2px; }
      .flow-group { gap: 4px; }

      /* Spacing */
      .section-body { padding: 14px; }
      .section-header { padding: 12px 14px; }
      .legend { flex-wrap: wrap; padding: 10px 12px; }
    }

    @media (max-width: 640px) {
      .form-grid-2, .form-grid-3 { grid-template-columns: 1fr; }
      .col-span-2, .col-span-3 { grid-column: span 1; }
      .carton-grid { grid-template-columns: 1fr; }
      .dim-group { flex-direction: column; }
      .specs-three-col { grid-template-columns: 1fr; gap: 20px; }
    }

    /* Quote & Invoice tabs mobile — single column, fit all fields */
    @media (max-width: 768px) {
      #wb-tab-quote .form-grid-2,
      #wb-tab-invoice .form-grid-2 {
        grid-template-columns: 1fr !important;
      }
      #wb-tab-quote input,
      #wb-tab-quote textarea,
      #wb-tab-invoice input,
      #wb-tab-invoice textarea {
        width: 100% !important;
        min-width: 0 !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
        font-size: 13px;
      }
      #wb-tab-quote input[type="date"],
      #wb-tab-invoice input[type="date"] {
        -webkit-appearance: none;
        appearance: none;
      }
    }

    /* Tier table mobile — hide # col, short labels, evenly spaced */
    @media (max-width: 768px) {
      .tier-col-num { display: none; }
      #tier-table .label-full { display: none; }
      #tier-table .label-short { display: inline; }
      #tier-table { width: 100%; table-layout: fixed; }
      #tier-table th { font-size: 9px; padding: 6px 4px; white-space: nowrap; text-align: center; }
      #tier-table td { padding: 4px 3px; text-align: center; }
      #tier-table input { width: 100% !important; min-width: 0; font-size: 11px; padding: 4px 4px; box-sizing: border-box; text-align: center; }
      /* Column proportions: Qty wider, RMB slimmer, USD/Total even, delete narrow */
      #tier-table colgroup { display: table-column-group; }
      #tier-table th:nth-child(2), #tier-table td:nth-child(2) { width: 25%; } /* Qty */
      #tier-table th:nth-child(3), #tier-table td:nth-child(3) { width: 20%; } /* RMB */
      #tier-table th:nth-child(4), #tier-table td:nth-child(4) { width: 22%; text-align: center; } /* Unit (USD) */
      #tier-table th:nth-child(5), #tier-table td:nth-child(5) { width: 25%; text-align: center; } /* Total */
      #tier-table th:last-child, #tier-table td:last-child { width: 8%; padding: 2px; }
      #tier-table .btn-danger-ghost { font-size: 11px; padding: 2px 5px; min-width: 0; }
      .tier-col-usd, .total-cell { font-size: 11px !important; }
      .email-quote-btn .email-label { display: none; }
      .email-quote-btn { padding: 6px 10px; }
      .email-icon { font-size: 20px; }
    }

    /* RFQ table mobile — horizontal scroll */
    @media (max-width: 768px) {
      #rfq-table { min-width: 700px; }
    }

    @media (max-width: 480px) {
      .header-actions .btn-ghost span { display: none; }
      .app-header { padding: 0 8px !important; }
      .modal { max-width: 95vw; }
      select { }
      input[type="number"] { }
    }

    /* ══ Shipments ══════════════════════════════════════════════════════ */
    .shipment-list-empty {
      text-align: center; padding: 60px 20px;
    }
    .shipment-list-empty-icon { font-size: 40px; margin-bottom: 12px; opacity: 0.3; }
    .shipment-list-empty-title { font-size: 16px; font-weight: 600; margin-bottom: 6px; }
    .shipment-list-empty-sub { font-size: 13px; color: var(--text-muted); }

    .shipment-cards { display: flex; flex-direction: column; gap: 6px; }
    .shipment-card {
      background: var(--surface2);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 12px 16px;
      cursor: pointer;
      display: flex;
      align-items: stretch;
      gap: 0;
      transition: box-shadow 0.15s, border-color 0.15s;
      min-width: 0;
    }
    .shipment-card:hover { box-shadow: var(--shadow); border-color: var(--accent); }
    /* Left: title + eta */
    .sc-left { display: flex; flex-direction: column; justify-content: center; gap: 4px; min-width: 130px; flex: 0 0 160px; padding-right: 16px; }
    .sc-title { font-size: 14px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sc-eta { font-size: 11px; color: var(--text-muted); white-space: nowrap; }
    .sc-eta strong { color: var(--text); font-weight: 600; }
    /* Center: workbook list */
    .sc-wb-list {
      flex: 0 0 300px; min-width: 0;
      display: flex; flex-direction: column; gap: 4px; justify-content: center;
      border-left: 2px solid var(--border);
      padding: 4px 14px;
    }
    .sc-wb-count { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: var(--text-muted); margin-bottom: 2px; }
    .sc-wb-pill {
      display: inline-flex; align-items: center; justify-content: space-between; gap: 6px;
      font-size: 12px; font-weight: 500;
      padding: 4px 10px; border-radius: 20px;
      border: 1px solid rgba(107,147,255,0.35); background: rgba(107,147,255,0.1);
      color: var(--accent); cursor: pointer;
      transition: border-color 0.12s, color 0.12s, background 0.12s;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
      max-width: 100%;
    }
    .sc-wb-pill:hover { border-color: var(--accent); background: rgba(107,147,255,0.18); }
    .sc-wb-pill-arrow { font-size: 11px; opacity: 0.75; flex-shrink: 0; }
    /* Right: stats + status inline */
    .sc-right-wrap {
      flex: 1;
      display: flex; align-items: center; justify-content: space-between; gap: 16px;
      border-left: 2px solid var(--border); padding-left: 16px;
    }
    .sc-stats-row { font-size: 12px; color: var(--text-muted); white-space: nowrap; display: flex; align-items: center; gap: 0; }
    .sc-stat-inline { color: var(--text); font-weight: 600; }
    .sc-divider { margin: 0 10px; opacity: 0.25; }
    .sc-right { display: flex; flex-direction: column; align-items: flex-start; gap: 4px; flex-shrink: 0; min-width: 90px; }
    .sc-right .ship-status-badge { width: 100%; text-align: center; box-sizing: border-box; }
    .sc-right .shipment-container-tag { width: 100%; text-align: center; }
    .ship-status-badge {
      font-size: 13px; font-weight: 800; border-radius: 8px; padding: 5px 14px;
      text-transform: capitalize; letter-spacing: 0.02em;
    }
    .shipment-container-tag {
      font-size: 10px; color: var(--text-muted); font-weight: 500; white-space: nowrap;
    }
    .sc-section-label {
      font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em;
      color: var(--text-muted); padding: 14px 0 4px;
    }
    .completed-month-label {
      font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em;
      color: var(--text-muted); padding: 8px 0 6px;
    }
    .ship-filter-bar { display: flex; gap: 4px; margin-bottom: 14px; flex-wrap: wrap; }
    .ship-filter-btn {
      font-size: 12px; font-weight: 600; padding: 5px 14px;
      border-radius: 20px; border: 1px solid var(--border);
      background: var(--surface2); color: var(--text-muted);
      cursor: pointer; transition: all 0.12s;
    }
    .ship-filter-btn:hover { border-color: var(--accent); color: var(--accent); }
    .ship-filter-btn.active { background: var(--accent); border-color: var(--accent); color: #fff; }
    .ship-status-planning  { background: rgba(107,147,255,0.1); color: #6b93ff; }
    .ship-status-booked    { background: rgba(251,175,52,0.12); color: #f59e0b; }
    .ship-status-in_transit { background: rgba(74,222,128,0.12); color: #4ade80; }
    .ship-status-delivered  { background: rgba(52,211,153,0.12); color: #34d399; }

    /* Detail header */
    .ship-detail-header {
      display: flex; align-items: flex-start; gap: 16px;
      margin-bottom: 20px; flex-wrap: wrap;
    }
    .ship-detail-name-wrap { flex: 1; min-width: 200px; }
    .ship-detail-name {
      font-size: 22px; font-weight: 700; border: none; background: transparent;
      color: var(--text); width: 100%; outline: none; padding: 2px 0;
      border-bottom: 2px solid transparent; transition: border-color 0.2s;
      font-family: inherit;
    }
    .ship-detail-name:focus { border-bottom-color: var(--accent); }
    .ship-detail-controls {
      display: flex; gap: 10px; align-items: center; flex-wrap: wrap; flex-shrink: 0;
    }
    .ship-select-wrap {
      position: relative; display: inline-block;
    }
    .ship-select-wrap::after {
      content: '';
      position: absolute;
      right: 10px; top: 50%;
      transform: translateY(-50%);
      width: 0; height: 0;
      border-left: 4px solid transparent;
      border-right: 4px solid transparent;
      border-top: 5px solid var(--text-muted);
      pointer-events: none;
    }
    .ship-detail-controls select {
      height: 34px; padding: 0 28px 0 12px; border: 1px solid var(--border);
      border-radius: var(--radius-sm); background: var(--surface2); color: var(--text);
      font-size: 13px; font-family: inherit; outline: none; cursor: pointer;
      appearance: none; -webkit-appearance: none;
    }

    /* Container type tabs */
    .container-type-row {
      display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap;
    }
    .container-type-btn {
      padding: 8px 18px; border-radius: var(--radius-sm);
      border: 1px solid var(--border); background: var(--surface2);
      color: var(--text-muted); font-size: 13px; font-weight: 600;
      cursor: pointer; transition: all 0.15s;
    }
    .container-type-btn:hover { border-color: var(--accent); color: var(--accent); }
    .container-type-btn.active {
      background: var(--accent); color: #fff; border-color: var(--accent);
    }

    /* Utilization bars */
    .ship-util-grid {
      display: grid; grid-template-columns: repeat(3, 1fr);
      gap: 16px; margin-bottom: 24px;
    }
    @media (max-width: 600px) { .ship-util-grid { grid-template-columns: 1fr; } }
    .ship-util-block {
      background: var(--surface2); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 14px 16px;
    }
    .ship-util-label {
      font-size: 10px; font-weight: 700; text-transform: uppercase;
      letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 4px;
    }
    .ship-util-values {
      display: flex; align-items: baseline; gap: 4px; margin-bottom: 8px;
    }
    .ship-util-current { font-size: 20px; font-weight: 700; }
    .ship-util-max { font-size: 13px; color: var(--text-muted); }
    .ship-util-track {
      height: 6px; background: var(--border); border-radius: 3px; overflow: hidden;
    }
    .ship-util-fill {
      height: 100%; border-radius: 3px; transition: width 0.4s ease;
      background: var(--accent);
    }
    .ship-util-fill.warn  { background: #f59e0b; }
    .ship-util-fill.danger { background: #ef4444; }
    .ship-util-pct { font-size: 11px; color: var(--text-muted); margin-top: 4px; }

    /* Workbook entries table */
    .ship-wb-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .ship-wb-table th {
      font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
      color: var(--text-muted); padding: 8px 12px; text-align: left;
      border-bottom: 1px solid var(--border);
    }
    .ship-wb-table td {
      padding: 12px 12px; border-bottom: 1px solid var(--border); vertical-align: middle;
    }
    .ship-wb-table tr:last-child td { border-bottom: none; }
    .ship-wb-product { font-weight: 600; }
    .ship-wb-link {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: 13px; font-weight: 600;
      padding: 4px 10px 4px 12px; border-radius: 20px;
      border: 1px solid var(--border); background: var(--surface);
      color: var(--text); cursor: pointer; text-decoration: none;
      transition: border-color 0.12s, color 0.12s, background 0.12s;
    }
    .ship-wb-link:hover { border-color: var(--accent); color: var(--accent); background: rgba(107,147,255,0.07); }
    .ship-wb-link-arrow { font-size: 11px; opacity: 0.5; transition: opacity 0.12s, transform 0.12s; }
    .ship-wb-link:hover .ship-wb-link-arrow { opacity: 1; transform: translateX(2px); }
    .ship-wb-client { color: var(--text-muted); font-size: 12px; margin-top: 2px; }
    .ship-wb-stat { font-weight: 600; }
    .ship-wb-stat-sub { font-size: 11px; color: var(--text-muted); }
    .ship-wb-remove {
      background: none; border: none; color: var(--text-muted); cursor: pointer;
      font-size: 16px; padding: 4px 8px; border-radius: var(--radius-sm);
      transition: color 0.15s, background 0.15s;
    }
    .ship-wb-remove:hover { color: #ef4444; background: rgba(239,68,68,0.08); }
    .ship-add-wb-btn {
      display: flex; align-items: center; gap: 8px;
      margin-top: 14px; padding: 10px 16px;
      border: 1.5px dashed var(--border); border-radius: var(--radius-sm);
      background: none; color: var(--text-muted); font-size: 13px; font-weight: 600;
      cursor: pointer; transition: all 0.15s; width: 100%;
    }
    .ship-add-wb-btn:hover { border-color: var(--accent); color: var(--accent); background: rgba(107,147,255,0.05); }

    /* Add Workbook Modal */
    .modal-wb-picker { max-height: 420px; overflow-y: auto; margin: 0 -6px; }
    .wb-picker-item {
      display: flex; align-items: center; gap: 12px;
      padding: 10px 10px; border-radius: var(--radius-sm); cursor: pointer;
      transition: background 0.12s;
    }
    .wb-picker-item:hover { background: var(--surface2); }
    .wb-picker-info { flex: 1; min-width: 0; }
    .wb-picker-product { font-size: 13px; font-weight: 600; }
    .wb-picker-client { font-size: 11px; color: var(--text-muted); }
    .wb-picker-tiers { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
    .wb-picker-qty {
      width: 80px; height: 30px; padding: 0 8px; border: 1px solid var(--border);
      border-radius: var(--radius-sm); background: var(--surface); color: var(--text);
      font-size: 12px; font-family: inherit; outline: none; text-align: right;
    }
    .wb-picker-group-label {
      font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em;
      color: var(--text-muted); padding: 10px 6px 4px; margin-top: 2px;
    }
    .wb-picker-group-label:first-child { padding-top: 4px; }
    .wb-picker-search {
      width: 100%; padding: 8px 12px; border: 1px solid var(--border);
      border-radius: var(--radius-sm); background: var(--surface); color: var(--text);
      font-size: 13px; font-family: inherit; outline: none; margin-bottom: 12px;
    }
    .wb-picker-search:focus { border-color: var(--accent); }
    .nav-shipment-item {
      display: flex; align-items: center; gap: 6px;
      padding: 5px 10px 5px 16px; border-radius: var(--radius-sm);
      color: var(--text-muted); font-size: 13px; cursor: pointer;
      transition: background 0.12s, color 0.12s;
    }
    .nav-shipment-item:hover { background: var(--surface2); color: var(--text); }
    .nav-shipment-item.active { background: rgba(107,147,255,0.12); color: var(--accent); font-weight: 600; }
    .nav-shipment-dot {
      width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0;
      background: var(--text-muted);
    }
    .nav-shipment-dot.planning   { background: #6b93ff; }
    .nav-shipment-dot.booked     { background: #f59e0b; }
    .nav-shipment-dot.in_transit { background: #4ade80; }
    .nav-shipment-dot.delivered  { background: #34d399; }

    /* ══ Orders ═════════════════════════════════════════════════════════ */
    .order-list-empty {
      text-align: center; padding: 60px 20px;
    }
    .order-list-empty-icon { font-size: 40px; margin-bottom: 12px; opacity: 0.3; }
    .order-list-empty-title { font-size: 16px; font-weight: 600; margin-bottom: 6px; }
    .order-list-empty-sub { font-size: 13px; color: var(--text-muted); }

    .order-cards { display: flex; flex-direction: column; gap: 6px; }
    .order-card {
      background: var(--surface2);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 12px 16px;
      cursor: pointer;
      display: flex;
      align-items: stretch;
      gap: 0;
      transition: box-shadow 0.15s, border-color 0.15s;
      min-width: 0;
    }
    .order-card:hover { box-shadow: var(--shadow); border-color: var(--accent); }
    .oc-left { display: flex; flex-direction: column; justify-content: center; gap: 2px; min-width: 130px; flex: 0 0 180px; padding-right: 16px; }
    .oc-client { font-size: 15px; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--text); }
    .oc-title { font-size: 12px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--text-muted); }
    .oc-date  { font-size: 11px; color: var(--text-muted); white-space: nowrap; }
    .oc-wb-list {
      flex: 1; min-width: 0;
      display: flex; flex-direction: column; gap: 4px; justify-content: center;
      border-left: 2px solid var(--border);
      padding: 4px 14px;
    }
    .oc-wb-count { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: var(--text-muted); margin-bottom: 2px; }
    .oc-wb-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .oc-wb-pill {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 20px;
      border: 1px solid rgba(107,147,255,0.35); background: rgba(107,147,255,0.1);
      color: var(--accent); cursor: pointer; white-space: nowrap;
      transition: border-color 0.12s, background 0.12s;
    }
    .oc-wb-pill:hover { border-color: var(--accent); background: rgba(107,147,255,0.18); }
    .oc-wb-prices { font-size: 12px; font-weight: 600; color: var(--text-muted); white-space: nowrap; }
    .oc-grand-total { display: flex; flex-direction: column; align-items: flex-end; gap: 2px; flex-shrink: 0; }
    .oc-grand-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: var(--text-muted); }
    .oc-grand-usd { font-size: 15px; font-weight: 800; color: var(--text); }
    .oc-grand-rmb { font-size: 13px; font-weight: 700; color: var(--text-muted); }
    .oc-right-wrap {
      flex: 0 0 160px;
      display: flex; align-items: center; justify-content: flex-end;
      border-left: 2px solid var(--border); padding-left: 16px;
    }
    .oc-right { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; flex-shrink: 0; min-width: 100px; }

    .order-status-badge {
      font-size: 13px; font-weight: 800; border-radius: 8px; padding: 5px 14px;
      text-transform: capitalize; letter-spacing: 0.02em;
    }
    .order-status-draft       { background: rgba(150,150,150,0.12); color: #9ca3af; }
    .order-status-confirmed   { background: rgba(107,147,255,0.1);  color: #6b93ff; }
    .order-status-in_production { background: rgba(251,175,52,0.12); color: #f59e0b; }
    .order-status-complete    { background: rgba(52,211,153,0.12);  color: #34d399; }

    /* Change-request badge on order cards */
    .oc-change-badge {
      display: inline-flex; align-items: center; gap: 4px;
      background: #fff7ed; border: 1px solid #fed7aa;
      color: #E8751A; font-size: 10px; font-weight: 700;
      text-transform: uppercase; letter-spacing: 0.06em;
      padding: 2px 8px; border-radius: 20px; margin-top: 5px;
      white-space: nowrap;
    }
    /* Highlight the whole card when it has an open change request */
    .order-card.has-change-request {
      border-color: rgba(232,117,26,0.45);
      box-shadow: 0 0 0 1px rgba(232,117,26,0.18);
    }

    /* Make nav-flat-link badges visible when they have content, right-aligned */
    .nav-flat-link .nav-badge:not(:empty) { display: inline-flex; align-items: center; gap: 4px; background: transparent !important; padding: 0; margin-left: auto; }
    /* Individual pills inside the badge container (supports orange + blue side-by-side) */
    .nav-badge-pill {
      display: inline-flex; align-items: center;
      font-size: 10px; font-weight: 700; line-height: 1;
      background: var(--accent); color: #fff;
      border-radius: 10px; padding: 2px 6px;
    }
    .nav-badge-pill.attention { background: #E8751A; animation: badge-pulse 2s ease-in-out infinite; }
    /* Orange attention variant (legacy single-badge) */
    .nav-badge.attention { background: #E8751A; animation: badge-pulse 2s ease-in-out infinite; }
    @keyframes badge-pulse {
      0%, 100% { opacity: 1; } 50% { opacity: 0.7; }
    }
    @keyframes presence-pulse {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.5; transform: scale(0.85); }
    }
    .presence-name-tag {
      position: absolute; top: -22px; left: 0;
      font-size: 10px; font-weight: 700; color: #fff;
      padding: 2px 6px; border-radius: 4px;
      white-space: nowrap; pointer-events: none; z-index: 1000;
      box-shadow: 0 2px 6px rgba(0,0,0,0.35);
    }

    .order-filter-bar { display: flex; gap: 4px; margin-bottom: 14px; flex-wrap: wrap; }
    .order-filter-btn {
      font-size: 12px; font-weight: 600; padding: 5px 14px;
      border-radius: 20px; border: 1px solid var(--border);
      background: var(--surface2); color: var(--text-muted);
      cursor: pointer; transition: all 0.12s;
    }
    .order-filter-btn:hover { border-color: var(--accent); color: var(--accent); }
    .order-filter-btn.active { background: var(--accent); border-color: var(--accent); color: #fff; }
    .order-filter-btn.attention-btn:hover, .order-filter-btn.attention-btn.active {
      background: #E8751A; border-color: #E8751A; color: #fff;
    }

    .order-total-value { font-size: 13px; font-weight: 700; color: var(--text); }
    .order-client-tag  { font-size: 11px; color: var(--text-muted); }

    /* Change request card in order detail */
    .cr-card {
      background: #fff;
      border: 1px solid rgba(232,117,26,0.45);
      border-radius: var(--radius);
      margin-bottom: 16px;
      overflow: hidden;
      box-shadow: 0 0 0 3px rgba(232,117,26,0.08);
    }
    .cr-card-head {
      display: flex; align-items: center; gap: 10px;
      padding: 14px 20px;
      background: rgba(232,117,26,0.07);
      border-bottom: 1px solid rgba(232,117,26,0.18);
    }
    .cr-card-head-icon {
      width: 28px; height: 28px; border-radius: 50%;
      background: rgba(232,117,26,0.15); color: #E8751A;
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .cr-card-title { font-size: 14px; font-weight: 800; color: #E8751A; }
    .cr-card-meta  { font-size: 12px; color: var(--text-muted); margin-left: auto; }
    .cr-card-body  { padding: 20px; display: flex; flex-direction: column; gap: 12px; }
    .cr-comment {
      padding: 12px 16px;
      background: rgba(232,117,26,0.05); border-left: 3px solid #E8751A;
      border-radius: 4px;
    }
    .cr-comment-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #E8751A; margin-bottom: 5px; }
    .cr-comment-text  { font-size: 13px; color: var(--text); line-height: 1.6; }
    .cr-items-label   { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 2px; }
    .cr-item {
      padding: 12px 16px;
      background: var(--surface2); border: 1px solid var(--border);
      border-left: 3px solid #E8751A; border-radius: 6px;
    }
    .cr-item-header { display: flex; align-items: center; gap: 6px; margin-bottom: 5px; }
    .cr-item-product { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-muted); }
    .cr-item-name    { font-size: 13px; font-weight: 700; color: var(--text); }
    .cr-item-note    { font-size: 13px; color: var(--text-muted); line-height: 1.55; }
    .cr-resolve-btn  {
      display: inline-flex; align-items: center; gap: 6px;
      margin-top: 4px; padding: 7px 16px;
      background: #f0fdf4; border: 1px solid #86efac;
      border-radius: 6px; color: #16a34a;
      font-size: 12px; font-weight: 700; cursor: pointer;
      font-family: inherit; transition: background 0.12s;
      align-self: flex-start;
    }
    .cr-resolve-btn:hover { background: #dcfce7; }

    /* Order detail */
    .order-detail-header { margin-bottom: 24px; }
    .order-detail-client-name {
      font-size: 30px; font-weight: 800; color: var(--text); line-height: 1.1; margin-bottom: 4px;
    }
    .order-detail-name-wrap { margin-bottom: 14px; }
    .order-detail-name {
      font-size: 16px; font-weight: 500; border: none; background: transparent;
      color: var(--text-muted); width: 100%; outline: none; padding: 2px 0;
      border-bottom: 2px solid transparent; transition: border-color 0.2s;
      font-family: inherit;
    }
    .order-detail-name:focus { border-bottom-color: var(--accent); color: var(--text); }
    .order-detail-controls {
      display: flex; gap: 10px; align-items: center; flex-wrap: wrap;
    }

    /* Order sheet table */
    .order-sheet-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .order-sheet-table th {
      font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
      color: var(--text-muted); padding: 8px 12px; text-align: left;
      border-bottom: 1px solid var(--border);
    }
    .order-sheet-table td {
      padding: 12px 12px; border-bottom: 1px solid var(--border); vertical-align: middle;
    }
    .order-sheet-table tfoot td {
      padding: 10px 12px; font-weight: 700; border-top: 2px solid var(--border);
      border-bottom: none;
    }
    .order-sheet-table tr:last-child td { border-bottom: none; }
    .order-sheet-product { font-weight: 600; }
    .order-sheet-product-link {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: 13px; font-weight: 600;
      padding: 4px 10px 4px 12px; border-radius: 20px;
      border: 1px solid var(--border); background: var(--surface);
      color: var(--text); cursor: pointer; text-decoration: none;
      transition: border-color 0.12s, color 0.12s, background 0.12s;
    }
    .order-sheet-product-link:hover { border-color: var(--accent); color: var(--accent); background: rgba(107,147,255,0.07); }
    .order-sheet-remove {
      background: none; border: none; color: var(--text-muted); cursor: pointer;
      font-size: 16px; padding: 4px 8px; border-radius: var(--radius-sm);
      transition: color 0.15s, background 0.15s;
    }
    .order-sheet-remove:hover { color: #ef4444; background: rgba(239,68,68,0.08); }
    .btn-danger {
      background: transparent; border: 1px solid rgba(239,68,68,0.4); color: #ef4444;
      font-size: 12px; font-weight: 600; padding: 5px 12px; border-radius: var(--radius-sm);
      cursor: pointer; transition: background 0.15s, border-color 0.15s;
    }
    .btn-danger:hover { background: rgba(239,68,68,0.08); border-color: #ef4444; }
    /* Shipment order cards */
    .ship-order-card {
      border: 1px solid var(--border); border-radius: var(--radius);
      background: var(--surface2); margin-bottom: 10px; overflow: hidden;
      cursor: pointer; transition: box-shadow 0.15s, border-color 0.15s;
    }
    .ship-order-card:last-child { margin-bottom: 0; }
    .ship-order-card:hover { box-shadow: var(--shadow); border-color: var(--accent); }
    .ship-order-card.has-change-request { border-color: rgba(232,117,26,0.5); box-shadow: 0 0 0 1px rgba(232,117,26,0.18); }
    /* Change-request banner inside an order card within shipment detail */
    .soc-cr-banner {
      display: flex; align-items: center; gap: 8px;
      padding: 8px 14px; background: rgba(232,117,26,0.07);
      border-top: 1px solid rgba(232,117,26,0.2);
      font-size: 12px; font-weight: 600; color: #E8751A;
    }
    .soc-cr-banner a {
      margin-left: auto; color: #E8751A; font-weight: 700;
      text-decoration: none; white-space: nowrap;
      display: inline-flex; align-items: center; gap: 3px;
    }
    .soc-cr-banner a:hover { text-decoration: underline; }
    /* Orange border on shipment list card when any order has a change request */
    .shipment-card.has-change-request { border-color: rgba(232,117,26,0.5); box-shadow: 0 0 0 1px rgba(232,117,26,0.15); }
    .ship-order-card-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 10px 14px 8px; border-bottom: 1px solid var(--border);
      background: var(--surface);
    }
    .soc-client { font-size: 15px; font-weight: 800; color: var(--text); }
    .soc-name   { font-size: 12px; color: var(--text-muted); margin-top: 1px; }
    .ship-order-card-body {
      display: flex; flex-wrap: wrap; gap: 20px;
      padding: 10px 14px 12px; align-items: flex-start;
    }
    .soc-workbooks { flex: 1; min-width: 160px; }
    .soc-wb-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: var(--text-muted); margin-bottom: 4px; }
    .soc-wb-pill {
      display: inline-flex; align-items: center; gap: 5px;
      font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 20px;
      border: 1px solid rgba(107,147,255,0.35); background: rgba(107,147,255,0.1);
      color: var(--accent); margin: 2px 4px 2px 0; cursor: pointer;
      transition: border-color 0.12s, background 0.12s;
    }
    .soc-wb-pill:hover { border-color: var(--accent); background: rgba(107,147,255,0.18); }
    .soc-stats { display: flex; flex-wrap: wrap; gap: 14px; align-items: center; }
    .soc-stat { display: flex; flex-direction: column; align-items: center; gap: 1px; }
    .soc-stat-val { font-size: 14px; font-weight: 700; color: var(--text); }
    .soc-stat-lbl { font-size: 10px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
    .soc-cost { display: flex; flex-direction: column; gap: 2px; align-items: flex-end; }
    .soc-cost-usd { font-size: 14px; font-weight: 700; color: var(--text); }
    .soc-cost-rmb { font-size: 12px; font-weight: 600; color: var(--text-muted); }
    .order-sheet-wb-header td {
      background: var(--surface); padding: 10px 12px 8px;
      border-bottom: 1px solid var(--border);
    }
    .order-sheet-wb-header:not(:first-child) td { border-top: 2px solid var(--border); }
    .order-sheet-line-row td { padding: 8px 12px; border-bottom: 1px solid var(--border); font-size: 13px; }
    .order-sheet-line-row:last-of-type td { border-bottom: none; }
    .split-badge {
      display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 600;
      padding: 2px 8px; border-radius: 20px; margin-left: 4px;
      background: rgba(107,147,255,0.1); border: 1px solid rgba(107,147,255,0.3); color: var(--accent);
    }

    /* Deposit tracking */
    .order-deposit-row {
      display: flex; align-items: center; gap: 14px;
      padding: 10px 0; border-bottom: 1px solid var(--border);
    }
    .order-deposit-row:last-child { border-bottom: none; }
    .order-deposit-label { font-size: 13px; font-weight: 600; flex: 1; }
    .order-deposit-amount { font-size: 14px; font-weight: 700; min-width: 100px; text-align: right; }
    .order-deposit-check { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-muted); flex-shrink: 0; }

    /* New order modal workbook checklist */
    .order-wb-check-item {
      display: flex; align-items: center; gap: 10px;
      padding: 8px 12px; border-bottom: 1px solid var(--border);
      cursor: pointer; transition: background 0.12s;
    }
    .order-wb-check-item:last-child { border-bottom: none; }
    .order-wb-check-item:hover { background: var(--surface); }
    .order-wb-check-item input[type="checkbox"] { flex-shrink: 0; }
    .order-wb-check-info { flex: 1; min-width: 0; }
    .order-wb-check-product { font-size: 13px; font-weight: 600; }
    .order-wb-check-status  { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
  </style>

  <!-- ── Viewport Scale ─────────────────────────────────────────────────────
       Scales the entire desktop layout proportionally as the viewport
       narrows, instead of immediately jumping to the mobile hamburger.
       Scaling stops when the effective body font would reach 8 px, at which
       point the viewport is ≤ 768 px and the existing mobile CSS takes over.

       Math:  DESIGN_W = 768 / (8/14) = 1344 px
              At 1344 px → scale 1.0  (full desktop, no zoom)
              At  768 px → scale 0.571  (14 px × 0.571 ≈ 8 px — hand off to mobile)
  ──────────────────────────────────────────────────────────────────────── -->
  <script>
  (function () {
    var DESIGN_W  = 1344;      // logical px width the layout is designed for
    var MOBILE_BP = 768;       // below this: mobile hamburger CSS takes over
    var MIN_SCALE = 8 / 14;    // ≈ 0.571 — stop scaling here (8 px body font)

    function applyViewportScale() {
      var vw = window.innerWidth;
      if (vw > MOBILE_BP && vw < DESIGN_W) {
        var scale = Math.max(MIN_SCALE, vw / DESIGN_W);
        document.documentElement.style.zoom = scale;
      } else {
        document.documentElement.style.zoom = '';
      }
    }

    applyViewportScale();
    window.addEventListener('resize', applyViewportScale, { passive: true });
  })();
  </script>
</head>
<body data-theme="light">

<div class="app-layout">

<!-- ── Sidebar ───────────────────────────────────────────────────────── -->
<aside class="sidebar">
  <a class="sidebar-logo" href="#/" style="text-decoration:none;">
    <img src="assets/logo.png" alt="Market Sculpt" />
  </a>

  <!-- Add buttons at top -->
  <div style="padding: 0 10px 10px; display: flex; flex-direction: column; gap: 5px;">
    <button class="sidebar-add-btn" onclick="openNewWorkbookModal()">+ Add Workbook</button>
    <button class="sidebar-add-btn" onclick="openAddClientModal()">+ Add Client</button>
  </div>
  <hr style="border:none; border-top:1px solid var(--border); margin: 0 0 6px;" />

  <!-- Search -->
  <div style="padding: 0 10px 8px;">
    <div style="position:relative;">
      <span style="position:absolute; left:9px; top:50%; transform:translateY(-50%); font-size:12px; color:var(--text-muted); pointer-events:none; z-index:2;">🔍</span>
      <div id="sidebar-search" contenteditable="true" spellcheck="false"
        onfocus="document.getElementById('sidebar-search-ph').style.display='none'; this.textContent=''; filterSidebarSearch(''); showRecentNav();"
        onblur="setTimeout(()=>{ if(!this.textContent.trim()){document.getElementById('sidebar-search-ph').style.display=''; this.textContent='';} hideRecentNav(); }, 150)"
        oninput="hideRecentNav(); filterSidebarSearch(this.textContent)"
        onkeydown="if(event.key==='Enter'){event.preventDefault();}"
        style="width:100%; box-sizing:border-box; padding:6px 8px 6px 28px; font-size:12px; font-family:inherit; border:1px solid var(--border); border-radius:6px; background:var(--surface2); color:var(--text); outline:none; cursor:text; min-height:28px; line-height:16px; white-space:nowrap; overflow:hidden;"></div>
      <span id="sidebar-search-ph" style="position:absolute; left:28px; top:50%; transform:translateY(-50%); font-size:12px; color:var(--text-muted); pointer-events:none; z-index:2;">Search</span>
      <div id="sidebar-recent-dropdown" style="display:none; position:absolute; top:calc(100% + 4px); left:0; right:0; background:var(--surface); border:1px solid var(--border); border-radius:8px; box-shadow:0 4px 16px rgba(0,0,0,0.15); z-index:100; overflow:hidden;"></div>
    </div>
  </div>

  <nav class="sidebar-nav" id="sidebar-nav">

    <!-- All Workbooks -->
    <a id="nav-all-workbooks" href="#/" onclick="event.preventDefault(); location.hash='#/'" class="nav-flat-link" style="font-size:12px; font-weight:700; padding:8px 12px;">
      <span>All Workbooks</span>
    </a>

    <!-- ★ Starred -->
    <div class="nav-section" id="nav-section-starred">
      <div class="nav-section-header" onclick="toggleNavSection('nav-section-starred')">
        <span>★ Starred</span>
        <span class="nav-badge" id="badge-starred"></span>
        <span class="nav-section-chevron">›</span>
      </div>
      <div class="nav-section-body" id="starred-list"></div>
    </div>

    <!-- Clients -->
    <div class="nav-section collapsed" id="nav-section-clients">
      <div class="nav-section-header" onclick="toggleNavSection('nav-section-clients')">
        <span>Clients</span>
        <span class="nav-badge" id="badge-clients"></span>
        <span class="nav-section-chevron">›</span>
      </div>
      <div class="nav-section-body" id="client-list"></div>
    </div>

    <!-- Orders -->
    <!-- Permanent SKU -->
    <a id="nav-inventory-link" href="#/inventory" onclick="event.preventDefault(); location.hash='#/inventory'" class="nav-flat-link">
      <span>Inventory</span>
      <span class="nav-badge" id="badge-inventory"></span>
    </a>

    <a id="nav-rfq-link" href="#/rfq" onclick="event.preventDefault(); location.hash='#/rfq'" class="nav-flat-link">
      <span>RFQ</span>
      <span class="nav-badge" id="badge-rfq"></span>
    </a>

    <a id="nav-orders-link" href="#/orders" onclick="event.preventDefault(); location.hash='#/orders'" class="nav-flat-link">
      <span>Orders</span>
      <span class="nav-badge" id="badge-orders"></span>
    </a>

    <!-- Samples -->
    <a id="nav-samples-link" href="#/samples" onclick="event.preventDefault(); location.hash='#/samples'" class="nav-flat-link">
      <span>Samples</span>
      <span class="nav-badge" id="badge-samples"></span>
    </a>

    <!-- Shipments -->
    <a id="nav-shipments-link" href="#/shipments" onclick="event.preventDefault(); location.hash='#/shipments'" class="nav-flat-link">
      <span>Shipments</span>
      <span class="nav-badge" id="badge-shipments"></span>
    </a>

    <!-- Billings -->
    <div class="nav-section collapsed" id="nav-section-billings">
      <div class="nav-section-header" onclick="toggleNavSection('nav-section-billings')">
        <span>Billings</span>
        <span class="nav-section-chevron">›</span>
      </div>
      <div class="nav-section-body">
        <div class="nav-placeholder">Coming soon…</div>
      </div>
    </div>

    <!-- Commissions -->
    <a id="nav-commissions-link" href="#/commissions" onclick="event.preventDefault(); location.hash='#/commissions'" class="nav-flat-link">
      <span>Commissions</span>
      <span class="nav-badge" id="badge-commissions"></span>
    </a>

  </nav>

  <div class="sidebar-bottom" style="padding: 8px;">
    <button class="nav-item" onclick="openArchiveModal()" style="width:100%;">
      <span>Archive</span>
    </button>
  </div>
</aside>
<div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleMobileSidebar()"></div>

<!-- ── App Content ──────────────────────────────────────────────────── -->
<div class="app-content">

<header class="app-header">
  <div class="logo">
    <button class="hamburger-btn" id="hamburger-btn" onclick="toggleMobileSidebar()" aria-label="Open menu">☰</button>
    <div class="logo-text" id="header-title" style="font-size:18px; font-weight:700; color:var(--header-title); border-left:3px solid #E8751A; padding-left:12px;">Market Sculpt</div>
  </div>
  <div class="header-actions">
    <span id="save-status" style="font-size:12px; opacity:0; transition:opacity 0.4s; margin-right:8px;"></span>
    <div class="fx-calc" title="Currency calculator — type in either field">
      <span class="fx-calc-sym">¥</span>
      <!-- name is randomized / data-*-ignore attrs added so browser autofill &
           password managers (1Password, LastPass, Dashlane, Bitwarden) leave
           this field alone — otherwise "Parker" etc. gets dropped in here. -->
      <input class="fx-calc-input" id="hdr-calc-rmb"
             type="text" inputmode="decimal" pattern="[0-9.,]*"
             placeholder="RMB"
             name="ms-fx-calc-rmb-nofill" autocomplete="off"
             data-lpignore="true" data-1p-ignore data-bwignore="true" data-form-type="other"
             oninput="hdrCalcRmbToUsd()" onpaste="setTimeout(() => hdrCalcRmbToUsd(), 0)" />
      <div class="fx-calc-divider"></div>
      <span class="fx-calc-sym">$</span>
      <input class="fx-calc-input" id="hdr-calc-usd"
             type="text" inputmode="decimal" pattern="[0-9.,]*"
             placeholder="USD"
             name="ms-fx-calc-usd-nofill" autocomplete="off"
             data-lpignore="true" data-1p-ignore data-bwignore="true" data-form-type="other"
             oninput="hdrCalcUsdToRmb()" onpaste="setTimeout(() => hdrCalcUsdToRmb(), 0)" />
    </div>
    <span id="fx-rate-display" title="Live CNY→USD exchange rate" style="font-size:11px; font-weight:600; color:var(--text-muted); background:var(--surface2); border:1px solid var(--border); border-radius:6px; padding:3px 8px; white-space:nowrap; letter-spacing:0.02em;">1 ¥ = $—</span>
    <div class="user-menu" id="user-menu">
      <button class="user-menu-btn" onclick="toggleUserDropdown()" title="Account">
        <span style="font-size:16px;">👤</span>
        <div style="display:flex; flex-direction:column; text-align:left;">
          <span class="user-label">Logged in as</span>
          <span class="user-name"><?= $_msUser ?></span>
        </div>
        <span style="font-size:10px; color:var(--text-muted); margin-left:2px;">▾</span>
      </button>
      <div class="user-dropdown" id="user-dropdown">
        <button class="user-dropdown-item" onclick="openChangePasswordModal(); closeUserDropdown()">Change Password</button>
        <button class="user-dropdown-item" onclick="openHistoryModal(); closeUserDropdown()">History</button>
        <button class="user-dropdown-item" id="theme-dropdown-btn" onclick="toggleTheme()">Light Mode</button>
        <?php if ($_msRole === 'admin'): ?>
        <button class="user-dropdown-item" onclick="openUsersModal(); closeUserDropdown()">Manage Users</button>
        <?php endif; ?>
        <button class="user-dropdown-item" onclick="window.print(); closeUserDropdown()">Print / Export</button>
        <hr class="user-dropdown-divider">
        <a class="user-dropdown-item danger" href="logout.php">Log Out</a>
      </div>
    </div>
  </div>
</header>

<!-- ══════════════════════════════════════════════════════════════════════
     VIEW: HOME
═══════════════════════════════════════════════════════════════════════ -->
<div id="view-home" class="view active">
  <main class="container">
    <div class="section-card">
      <div class="section-header" style="display:flex; align-items:center; flex-wrap:wrap; gap:10px;">
        <span class="section-title" style="margin-right:auto;">All Workbooks</span>
        <div style="display:flex; flex-wrap:wrap; gap:6px; align-items:center;">
          <button class="status-filter-btn active" onclick="filterByStatus('all')">All</button>
          <button class="status-filter-btn" onclick="filterByStatus('none')">No Status</button>
          <button class="status-filter-btn" onclick="filterByStatus('quoteChina')">Quote</button>
          <button class="status-filter-btn" onclick="filterByStatus('quoteSubmitted')">Submitted</button>
          <button class="status-filter-btn" onclick="filterByStatus('quoteClient')">To Client</button>
          <button class="status-filter-btn" onclick="filterByStatus('clientApproved')">Approved</button>
          <button class="status-filter-btn" onclick="filterByStatus('officeInvoice')">Invoice</button>
          <button class="status-filter-btn" onclick="filterByStatus('confirmedPayment')">Payment</button>
          <button class="status-filter-btn" onclick="filterByStatus('orderChina')">Order</button>
          <button class="status-filter-btn" onclick="filterByStatus('complete')">Complete</button>
        </div>
      </div>
      <div class="section-body" style="padding:0;">
        <div class="table-scroll-wrapper">
        <table class="dash-table">
          <thead>
            <tr>
              <th class="sortable" onclick="sortHomeTable('product')">Product <span class="sort-arrow"></span></th>
              <th class="col-client sortable" onclick="sortHomeTable('client')">Client <span class="sort-arrow"></span></th>
              <th class="col-date-created sortable" onclick="sortHomeTable('date')">Date Created <span class="sort-arrow"></span></th>
              <th class="col-flow">Fulfilment (Flow)</th>
              <th class="col-mobile-status sortable" onclick="sortHomeTable('status')">Status <span class="sort-arrow"></span></th>
              <th></th>
            </tr>
          </thead>
          <tbody id="recent-tbody">
            <!-- populated by JS -->
          </tbody>
        </table>
        </div>
        <div id="filter-empty" style="display:none; padding:40px 20px; text-align:center; color:var(--text-muted); font-size:14px;">No workbooks match this status.</div>
      </div>
    </div>
  </main>
</div>

<!-- ══════════════════════════════════════════════════════════════════════
     VIEW: CLIENT DASHBOARD
═══════════════════════════════════════════════════════════════════════ -->
<div id="view-dashboard" class="view">
  <main class="container">
    <div id="client-detail-card-wrap"></div>
    <div class="section-card">
      <div class="section-header" style="display:flex; align-items:center; gap:10px;">
        <span class="section-title" style="margin-right:auto;">Workbooks</span>
        <button class="btn-create" onclick="openNewWorkbookModal()" style="font-size:13px; padding:8px 16px;">+ Add Workbook</button>
      </div>
      <div class="section-body" style="padding:0;">
        <div class="table-scroll-wrapper">
        <table class="dash-table" id="dash-table">
          <thead>
            <tr>
              <th class="sortable" onclick="sortClientTable('product')">Product <span class="sort-arrow"></span></th>
              <th class="col-date-created sortable" onclick="sortClientTable('date')">Date Created <span class="sort-arrow"></span></th>
              <th class="col-date-submitted sortable" onclick="sortClientTable('dateSubmitted')">Date Submitted <span class="sort-arrow"></span></th>
              <th class="col-order">Order</th>
              <th class="col-flow">Fulfilment (Flow)</th>
              <th class="col-mobile-status sortable" onclick="sortClientTable('status')">Status <span class="sort-arrow"></span></th>
              <th></th>
            </tr>
          </thead>
          <tbody id="dash-tbody">
            <!-- rows injected by JS -->
          </tbody>
        </table>
        </div>
        <div id="dash-empty" style="display:none; text-align:center; padding:60px 24px;">
          <p style="color:var(--text-muted); font-size:16px; margin:0 0 16px;">No workbooks yet for this client.</p>
          <button class="btn-create" onclick="openNewWorkbookModal()" style="font-size:14px;">+ Add Workbook</button>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- ══════════════════════════════════════════════════════════════════════
     VIEW: WORKBOOK DETAIL
═══════════════════════════════════════════════════════════════════════ -->
<div id="view-workbook" class="view">
<main class="container">
  <div class="wb-sticky-bar">
    <div style="display:grid; grid-template-columns:1fr auto 1fr; align-items:center;">
      <button class="btn-back" id="btn-back" onclick="history.back()" style="margin-bottom:0; justify-self:start;">← Back to Workbooks</button>
      <div class="wb-tabs">
        <button class="wb-tab active" onclick="switchWbTab('workbook', this)"><span class="tab-full">Workbook</span><span class="tab-short">Work</span></button>
        <button class="wb-tab" onclick="switchWbTab('shipping', this)"><span class="tab-full">Shipping</span><span class="tab-short">Ship</span></button>
        <button class="wb-tab" onclick="switchWbTab('pricing', this)"><span class="tab-full">Pricing</span><span class="tab-short">Price</span></button>
        <button class="wb-tab" onclick="switchWbTab('art', this)"><span class="tab-full">Art</span><span class="tab-short">Art</span></button>
      </div>
      <div style="justify-self:end;"></div>
    </div>
  </div>

  <!-- ── Status Bar ── -->
  <div class="status-bar" id="status-bar">
    <div class="status-bar-left">
      <div class="status-label">Status</div>
      <div class="status-flow" id="status-flow">
        <!-- populated by JS -->
      </div>
    </div>
    <div class="status-actions" style="display:flex; gap:6px; align-items:center;">
      <button id="btn-notify-quote" onclick="openNotifyModal('quote_ready')"
        style="display:none; background:none; border:1px solid var(--accent); border-radius:8px; color:var(--accent); font-size:12px; font-weight:600; padding:6px 12px; cursor:pointer; font-family:inherit; align-items:center; gap:5px; white-space:nowrap; transition:opacity 0.15s;"
        title="Send quote notification to client">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        Notify Client
      </button>
      <button id="btn-send-rfq" onclick="toggleSendToRfq()"
        style="display:none; background:none; border:1px solid var(--accent); border-radius:8px; color:var(--accent); font-size:12px; font-weight:600; padding:6px 12px; cursor:pointer; font-family:inherit; align-items:center; gap:5px; white-space:nowrap; transition:opacity 0.15s;"
        title="Send this workbook to the RFQ queue for review">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>
        <span id="btn-send-rfq-label">RFQ</span>
      </button>
      <button class="btn-back-step" id="btn-back-step" onclick="revertStatus()" title="Go back one step">←</button>
      <button class="btn-advance" id="btn-advance" onclick="advanceStatus()">
        Mark as Entered →
      </button>
    </div>
  </div>

  <!-- ── Tab: Workbook ── -->
  <div id="wb-tab-workbook" class="wb-tab-content active">

  <!-- ══════════════════════════════════════════════════════════════════════
       SECTION 1 — PRODUCT OVERVIEW
  ═══════════════════════════════════════════════════════════════════════ -->
  <div class="section-card">
    <div class="section-header section-header-collapsible" onclick="toggleSection(this.closest('.section-card'))">
      <span class="section-title">Product Overview</span>
      <div style="margin-left:auto; display:flex; align-items:center; gap:8px;">
        <button onclick="event.stopPropagation(); promoteWorkbookToInventory();" id="btn-promote-sku"
          style="background:none; border:1px solid var(--border); border-radius:8px; color:var(--text-muted); font-size:12px; font-weight:600; padding:5px 10px; cursor:pointer; font-family:inherit; display:flex; align-items:center; gap:5px; white-space:nowrap; transition:border-color 0.15s, color 0.15s;"
          onmouseover="this.style.borderColor='var(--accent)'; this.style.color='var(--accent)';"
          onmouseout="this.style.borderColor='var(--border)'; this.style.color='var(--text-muted)';"
          title="Promote all RFQ SKUs from this workbook to permanent SKUs">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 21 12 17.77 5.82 21 7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
          Promote to Permanent SKU
        </button>
        <span class="section-chevron" style="margin-left:0;">›</span>
      </div>
    </div>
    <div class="section-body">

      <!-- Basic info grid -->
      <div class="form-grid form-grid-2">
        <div class="field">
          <label>Client Name</label>
          <input type="text" placeholder="e.g. Acme Corp" id="client-name" />
        </div>
        <div class="field">
          <label>Product Name</label>
          <input type="text" placeholder="e.g. Custom Tote Bag" id="product-name" />
        </div>
        <div class="field col-full">
          <label>Product Description</label>
          <textarea placeholder="Describe the product — intended use, key features, special requirements…" id="product-desc"></textarea>
        </div>
      </div>

      <!-- ── Technical Specifications ── -->
      <div class="subsection">
        <div class="subsection-title">Technical Specifications</div>

        <!-- Category / Subcategory -->
        <div class="form-grid form-grid-2">
          <div class="field">
            <label>Product Category</label>
            <div class="select-wrapper">
              <select id="product-category" onchange="updateSubcategories()">
                <option value="">Select category...</option>
                <option value="packaging">Packaging</option>
                <option value="apparel">Apparel</option>
                <option value="furniture">Furniture</option>
                <option value="electronics">Electronics</option>
                <option value="promotional">Promotional Products</option>
                <option value="food-beverage">Food & Beverage</option>
                <option value="toys">Toys & Games</option>
                <option value="beauty">Beauty & Personal Care</option>
                <option value="home-garden">Home & Garden</option>
                <option value="sports">Sports & Outdoors</option>
                <option value="stationery">Stationery & Office</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="secondary-select-wrap" id="cat2-wrap">
              <div class="secondary-select-label">Secondary Category</div>
              <div class="select-wrapper">
                <select id="product-category-2" disabled onchange="onSecondaryChange('cat2-wrap', this); updateSubcategories();">
                  <option value="">None</option>
                  <option value="packaging">Packaging</option>
                  <option value="apparel">Apparel</option>
                  <option value="furniture">Furniture</option>
                  <option value="electronics">Electronics</option>
                  <option value="promotional">Promotional Products</option>
                  <option value="food-beverage">Food &amp; Beverage</option>
                  <option value="toys">Toys &amp; Games</option>
                  <option value="beauty">Beauty &amp; Personal Care</option>
                  <option value="home-garden">Home &amp; Garden</option>
                  <option value="sports">Sports &amp; Outdoors</option>
                  <option value="stationery">Stationery &amp; Office</option>
                  <option value="other">Other</option>
                </select>
              </div>
            </div>
          </div>
          <div class="field">
            <label>Material Type</label>
            <div class="select-wrapper">
              <select id="product-subcategory" onchange="checkSecondaryLock()">
                <option value="">Select category first...</option>
              </select>
            </div>
            <div class="secondary-select-wrap" id="mat2-wrap">
              <div class="secondary-select-label">Secondary Material</div>
              <div class="select-wrapper">
                <select id="product-subcategory-2" disabled onchange="onSecondaryChange('mat2-wrap', this)">
                  <option value="">None</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Hidden fields to preserve existing data -->
        <input type="hidden" id="materials" />
        <input type="hidden" id="pantone-text" />
        <input type="hidden" id="cmyk" />
        <input type="hidden" id="color-notes" />
      </div>

      <!-- Product Images -->
      <div style="margin-top:18px;">
        <label style="display:block; margin-bottom:8px;">Product Images</label>
        <div class="image-gallery" id="imageGallery">
          <!-- images inserted dynamically -->
          <div class="image-add-btn" onclick="document.getElementById('imgInput').click()">
            <div class="add-icon">+</div>
            <div class="add-text">Image or Video</div>
          </div>
        </div>
        <div style="font-size:11px; color:var(--text-muted); margin-top:6px; opacity:0.7;">Drag &amp; drop images or videos here, or paste from clipboard (Ctrl/⌘ + V)</div>
        <input type="file" id="imgInput" accept="image/*" multiple onchange="handleImages(event)" style="display:none;" />
      </div>

      <!-- Product Videos -->
      <div style="margin-top:18px;">
        <label style="display:block; margin-bottom:8px;">Product Video(s)</label>
        <div class="video-add-row">
          <input type="text" id="videoUrlInput" placeholder="Paste YouTube, Vimeo, or direct video URL…" onkeydown="if(event.key==='Enter'){addProductVideo();event.preventDefault();}" />
          <button class="btn" onclick="addProductVideo()" type="button" style="white-space:nowrap; flex-shrink:0;">Add URL</button>
          <button class="btn" onclick="document.getElementById('videoFileInput').click()" type="button" style="white-space:nowrap; flex-shrink:0;">Browse</button>
        </div>
        <div class="video-gallery" id="videoGallery"></div>
        <input type="file" id="videoFileInput" accept="video/*,.mov,.m4v,.mkv" multiple onchange="handleVideoFiles(Array.from(this.files)); this.value='';" style="display:none;" />
      </div>

      <!-- Lightbox -->
      <div class="lightbox-overlay" id="lightboxOverlay" onclick="this.classList.remove('open')">
        <img id="lightboxImg" src="" alt="Full size" onclick="event.stopPropagation()" />
      </div>
      <!-- Video Lightbox -->
      <div class="video-lightbox-overlay" id="videoLightboxOverlay" onclick="if(event.target===this){document.getElementById('videoLightboxIframe').src='';document.getElementById('videoLightboxVideo').src='';this.classList.remove('open');}">
        <div class="video-lightbox-inner" onclick="event.stopPropagation()">
          <iframe id="videoLightboxIframe" src="" allowfullscreen allow="autoplay; encrypted-media"></iframe>
          <video id="videoLightboxVideo" src="" controls style="display:none;"></video>
        </div>
      </div>
    </div>
  </div>

  <!-- ── Card: RFQ ── -->
  <div class="section-card">
    <div class="section-header section-header-collapsible" onclick="toggleSection(this.closest('.section-card'))">
      <span class="section-title">RFQ</span>
      <div id="rfq-presence-bar" onclick="event.stopPropagation()" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin-left:12px;"></div>
      <span class="section-chevron">›</span>
    </div>
    <div class="section-body">
      <div class="subsection-title" style="margin-top:0;">Quote Details</div>
      <p style="color:var(--text-muted); font-size:13px; margin-bottom:12px;">Add line items for each product/component in this quote.</p>
      <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
      <table class="tier-table" id="rfq-table" style="min-width:860px; border-collapse:collapse;">
        <colgroup>
          <col style="width:40px;">
          <col style="width:50px;">
          <col style="width:13%;">
          <col style="width:14%;">
          <col style="width:8%;">
          <col style="width:15%;">
          <col style="width:13%;">
          <col style="width:14%;">
          <col style="width:11%;">
          <col style="width:36px;">
        </colgroup>
        <thead>
          <tr>
            <th>#</th>
            <th style="text-align:center;" title="Sample Request">SAMPLE</th>
            <th>SKU</th>
            <th>ITEM</th>
            <th>QTY</th>
            <th>UNIT PRICE (RMB)</th>
            <th style="text-align:right;">UNIT PRICE (USD)</th>
            <th style="text-align:right;">TOTAL (USD)</th>
            <th>LEAD TIME</th>
            <th></th>
          </tr>
        </thead>
        <tbody id="rfq-body"></tbody>
        <tfoot>
          <tr>
            <td style="padding:0; border-bottom:none;"></td>
            <td style="padding:0; border-bottom:none;"></td>
            <td style="padding:0; border-bottom:none;"></td>
            <td style="padding:4px 12px; border-bottom:none;">
              <button class="btn btn-add" style="width:100%; margin:4px 0;" onclick="addRfqRow()">+ Add Line Item</button>
            </td>
            <td colspan="6" style="padding:0; border-bottom:none;"></td>
          </tr>
          <tr style="background:var(--surface2);">
            <th style="padding:10px 14px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-muted); border-bottom:1px solid var(--border);">#</th>
            <th style="padding:10px 14px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-muted); border-bottom:1px solid var(--border); text-align:center;" title="Sample Request">SAMPLE</th>
            <th style="padding:10px 14px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-muted); border-bottom:1px solid var(--border);">SKU</th>
            <th style="padding:10px 14px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-muted); border-bottom:1px solid var(--border);">ITEM</th>
            <th style="padding:10px 14px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-muted); border-bottom:1px solid var(--border);">QTY</th>
            <th style="padding:10px 14px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-muted); border-bottom:1px solid var(--border);">UNIT PRICE (RMB)</th>
            <th style="padding:10px 14px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-muted); border-bottom:1px solid var(--border); text-align:right;">UNIT PRICE (USD)</th>
            <th style="padding:10px 14px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-muted); border-bottom:1px solid var(--border); text-align:right;">TOTAL (USD)</th>
            <th style="padding:10px 14px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-muted); border-bottom:1px solid var(--border);">LEAD TIME</th>
            <th style="border-bottom:1px solid var(--border);"></th>
          </tr>
          <tr id="rfq-totals" style="border-top:2px solid var(--border); font-weight:700; background:rgba(232, 117, 26, 0.08);">
            <td></td>
            <td></td>
            <td></td>
            <td style="font-weight:700; color:#374151; padding-left:26px;">TOTALS</td>
            <td id="rfq-total-qty" style="color:#374151; font-weight:700; padding-left:26px;">—</td>
            <td id="rfq-total-rmb" style="color:#374151; font-weight:700; padding-left:26px;">—</td>
            <td id="rfq-total-usd-sum" style="color:#374151; font-weight:700; text-align:right;">—</td>
            <td id="rfq-total-usd" style="color:#374151; font-weight:700; text-align:right;">—</td>
            <td id="rfq-max-lead" style="color:#374151; font-weight:700; padding-left:26px;">—</td>
            <td></td>
          </tr>
        </tfoot>
      </table>
      </div>
      <div class="form-grid form-grid-2" style="margin-top:16px;">
        <div class="field karen-field col-full">
          <label>Quote Notes</label>
          <textarea placeholder="Additional notes, special requirements, or instructions…" id="quote-qc" style="min-height:70px;"></textarea>
        </div>
      </div>
    </div>
  </div>

  <!-- ── Card: Tiered Pricing (Workbook tab) ── -->
  <div class="section-card">
    <div class="section-header section-header-collapsible" onclick="toggleSection(this.closest('.section-card'))">
      <span class="section-title">Tiered Pricing</span>
      <span class="section-chevron">›</span>
    </div>
    <div class="section-body">
      <p style="font-size:12px; color:var(--text-muted); margin-bottom:14px;">
        Karen fills in the Unit Price for each quantity tier. Total is calculated automatically.
      </p>
      <table class="tier-table" id="wb-tier-table">
        <thead>
          <tr>
            <th>#</th>
            <th><span class="label-full">Quantity</span><span class="label-short">Qty</span></th>
            <th><span class="label-full">Unit Price (RMB)</span><span class="label-short">RMB</span></th>
            <th class="tier-col-usd"><span class="label-full">Unit Price (USD)</span><span class="label-short">Unit (USD)</span></th>
            <th><span class="label-full">Total Price (USD)</span><span class="label-short">Total (USD)</span></th>
            <th></th>
          </tr>
        </thead>
        <tbody id="wb-tier-body">
        </tbody>
      </table>
      <button class="btn btn-add" style="margin-top:10px;" onclick="addWbTierRow()">+ Add Pricing Tier</button>
    </div>
  </div>

  <!-- ── Card: Additional Fees ── -->
  <div class="section-card">
    <div class="section-header section-header-collapsible" onclick="toggleSection(this.closest('.section-card'))">
      <span class="section-title">Additional Fees</span>
      <span class="section-chevron">›</span>
    </div>
    <div class="section-body">
      <div style="overflow-x:auto;">
      <table class="tier-table" style="width:100%;">
        <thead>
          <tr>
            <th style="text-align:left; width:18%;">Fee</th>
            <th style="text-align:left;">Description</th>
            <th style="width:22%;">Amount (RMB)</th>
            <th style="width:22%;">Amount (USD)</th>
            <th style="width:36px;"></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td style="padding:6px 12px; font-size:13px; color:var(--text-muted); white-space:nowrap;">Sample Fee(s)</td>
            <td style="padding:4px 8px;"><input type="text" class="form-input" placeholder="Description…" id="fee-sample-desc" oninput="calcAdditionalFees()" style="width:100%;" autocomplete="off" /></td>
            <td style="padding:4px 8px;"><div class="currency-prefix currency-rmb"><input type="number" step="0.01" min="0" placeholder="0.00" id="fee-sample-rmb" oninput="convertFee('sample','rmb')" style="width:100%;" autocomplete="off" /></div></td>
            <td style="padding:4px 8px;"><div class="currency-prefix currency-usd"><input type="number" step="0.01" min="0" placeholder="0.00" id="fee-sample-usd" oninput="convertFee('sample','usd')" style="width:100%;" autocomplete="off" /></div></td>
            <td style="padding:4px 8px; text-align:center;"><span class="remove-tier" onclick="openClearFeeModal('sample','Sample Fee(s)')" title="Clear">×</span></td>
          </tr>
          <tr>
            <td style="padding:6px 12px; font-size:13px; color:var(--text-muted); white-space:nowrap;">Tooling Fee(s)</td>
            <td style="padding:4px 8px;"><input type="text" class="form-input" placeholder="Description…" id="fee-tooling-desc" oninput="calcAdditionalFees()" style="width:100%;" autocomplete="off" /></td>
            <td style="padding:4px 8px;"><div class="currency-prefix currency-rmb"><input type="number" step="0.01" min="0" placeholder="0.00" id="fee-tooling-rmb" oninput="convertFee('tooling','rmb')" style="width:100%;" autocomplete="off" /></div></td>
            <td style="padding:4px 8px;"><div class="currency-prefix currency-usd"><input type="number" step="0.01" min="0" placeholder="0.00" id="fee-tooling-usd" oninput="convertFee('tooling','usd')" style="width:100%;" autocomplete="off" /></div></td>
            <td style="padding:4px 8px; text-align:center;"><span class="remove-tier" onclick="openClearFeeModal('tooling','Tooling Fee(s)')" title="Clear">×</span></td>
          </tr>
          <tr>
            <td style="padding:6px 12px; font-size:13px; color:var(--text-muted); white-space:nowrap;">Die Fee(s)</td>
            <td style="padding:4px 8px;"><input type="text" class="form-input" placeholder="Description…" id="fee-die-desc" oninput="calcAdditionalFees()" style="width:100%;" autocomplete="off" /></td>
            <td style="padding:4px 8px;"><div class="currency-prefix currency-rmb"><input type="number" step="0.01" min="0" placeholder="0.00" id="fee-die-rmb" oninput="convertFee('die','rmb')" style="width:100%;" autocomplete="off" /></div></td>
            <td style="padding:4px 8px;"><div class="currency-prefix currency-usd"><input type="number" step="0.01" min="0" placeholder="0.00" id="fee-die-usd" oninput="convertFee('die','usd')" style="width:100%;" autocomplete="off" /></div></td>
            <td style="padding:4px 8px; text-align:center;"><span class="remove-tier" onclick="openClearFeeModal('die','Die Fee(s)')" title="Clear">×</span></td>
          </tr>
          <tr>
            <td style="padding:6px 12px; font-size:13px; color:var(--text-muted); white-space:nowrap;">Plate Fee(s)</td>
            <td style="padding:4px 8px;"><input type="text" class="form-input" placeholder="Description…" id="fee-plate-desc" oninput="calcAdditionalFees()" style="width:100%;" autocomplete="off" /></td>
            <td style="padding:4px 8px;"><div class="currency-prefix currency-rmb"><input type="number" step="0.01" min="0" placeholder="0.00" id="fee-plate-rmb" oninput="convertFee('plate','rmb')" style="width:100%;" autocomplete="off" /></div></td>
            <td style="padding:4px 8px;"><div class="currency-prefix currency-usd"><input type="number" step="0.01" min="0" placeholder="0.00" id="fee-plate-usd" oninput="convertFee('plate','usd')" style="width:100%;" autocomplete="off" /></div></td>
            <td style="padding:4px 8px; text-align:center;"><span class="remove-tier" onclick="openClearFeeModal('plate','Plate Fee(s)')" title="Clear">×</span></td>
          </tr>
        </tbody>
        <tbody id="extra-fee-rows"></tbody>
        <tbody>
          <tr style="border-top:2px solid var(--border);">
            <td style="padding:6px 12px; font-size:13px; color:var(--text-muted); white-space:nowrap;">Design Fee(s)</td>
            <td style="padding:4px 8px;"><input type="text" class="form-input" placeholder="Description…" id="fee-design-desc" oninput="calcAdditionalFees()" style="width:100%;" autocomplete="off" /></td>
            <td style="padding:6px 12px; font-size:12px; color:var(--text-muted); font-style:italic;">USD only</td>
            <td style="padding:4px 8px;"><div class="currency-prefix currency-usd"><input type="number" step="0.01" min="0" placeholder="0.00" id="fee-design-usd" oninput="calcAdditionalFees()" style="width:100%;" autocomplete="off" /></div></td>
            <td style="padding:4px 8px; text-align:center;"><span class="remove-tier" onclick="openClearFeeModal('design','Design Fee(s)')" title="Clear">×</span></td>
          </tr>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="5" style="padding:8px 12px; border-bottom:none;">
              <button class="btn btn-add" style="width:100%; margin:4px 0;" onclick="openAddFeeModal()">+ Add Fee</button>
            </td>
          </tr>
        </tfoot>
      </table>
      </div>
    </div>
  </div>

  <!-- ── Card: Dimensions & Carton Specifications ── -->
  <div class="section-card">
    <div class="section-header section-header-collapsible" onclick="toggleSection(this.closest('.section-card'))">
      <span class="section-title">Dimensions & Carton Specifications</span>
      <span class="section-chevron">›</span>
    </div>
    <div class="section-body">
      <div class="specs-three-col">

        <!-- Column 1: Product Dimensions -->
        <div class="specs-col">
          <div class="specs-col-title">Product Dimensions</div>
          <div class="specs-dim-grid">
            <div></div>
            <div class="specs-unit-header">cm</div>
            <div class="specs-unit-header">in</div>
            <div class="specs-row-label">Length</div>
            <div class="specs-input-wrap"><input type="number" step="0.01" min="0" placeholder="—" id="dim-cm-l" oninput="convertDim('dim-cm-l','dim-in-l','cm'); autoCalcCartons()" /><span class="specs-unit-tag">cm</span></div>
            <div class="specs-input-wrap"><input type="number" step="0.01" min="0" placeholder="—" id="dim-in-l" oninput="convertDim('dim-in-l','dim-cm-l','in'); autoCalcCartons()" /><span class="specs-unit-tag">in</span></div>
            <div class="specs-row-label">Width</div>
            <div class="specs-input-wrap"><input type="number" step="0.01" min="0" placeholder="—" id="dim-cm-w" oninput="convertDim('dim-cm-w','dim-in-w','cm'); autoCalcCartons()" /><span class="specs-unit-tag">cm</span></div>
            <div class="specs-input-wrap"><input type="number" step="0.01" min="0" placeholder="—" id="dim-in-w" oninput="convertDim('dim-in-w','dim-cm-w','in'); autoCalcCartons()" /><span class="specs-unit-tag">in</span></div>
            <div class="specs-row-label">Height</div>
            <div class="specs-input-wrap"><input type="number" step="0.01" min="0" placeholder="—" id="dim-cm-h" oninput="convertDim('dim-cm-h','dim-in-h','cm'); autoCalcCartons()" /><span class="specs-unit-tag">cm</span></div>
            <div class="specs-input-wrap"><input type="number" step="0.01" min="0" placeholder="—" id="dim-in-h" oninput="convertDim('dim-in-h','dim-cm-h','in'); autoCalcCartons()" /><span class="specs-unit-tag">in</span></div>
            <hr class="specs-dim-divider" />
            <div></div>
            <div class="specs-unit-header">kg</div>
            <div class="specs-unit-header">lb</div>
            <div class="specs-row-label">Weight</div>
            <div class="specs-input-wrap"><input type="number" step="0.001" min="0" placeholder="—" id="dim-weight-kg" oninput="convertWeight('dim-weight-kg','dim-weight-lbs','kg'); autoCalcCartons()" /><span class="specs-unit-tag">kg</span></div>
            <div class="specs-input-wrap"><input type="text" placeholder="—" id="dim-weight-lbs" oninput="convertWeight('dim-weight-lbs','dim-weight-kg','lbs')" /><span class="specs-unit-tag">lb</span></div>
            <div class="specs-full-row" style="margin-top:6px;">
              <div class="specs-row-label" style="margin-bottom:5px;">Packaging Type</div>
              <div class="select-wrapper">
                <select id="dim-packaging">
                  <option value="">Select type…</option>
                  <option value="poly-bag">Poly Bag</option>
                  <option value="vinyl-bag">Vinyl Bag</option>
                  <option value="brown-box">Brown Box</option>
                  <option value="gift-box">Gift Box</option>
                  <option value="retail-box">Retail Box</option>
                  <option value="blister-pack">Blister Pack</option>
                  <option value="clamshell">Clamshell</option>
                  <option value="shrink-wrap">Shrink Wrap</option>
                  <option value="hang-tag">Hang Tag</option>
                  <option value="bulk">Bulk / No Packaging</option>
                  <option value="other">Other</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Column 2: Inner Carton -->
        <div class="specs-col">
          <div class="specs-col-title" style="display:flex; align-items:center; justify-content:space-between;">
            <span>Inner Carton</span>
            <span id="inner-calc-badge" style="display:none; font-size:10px; font-weight:600; color:var(--accent); opacity:0.8;">auto</span>
          </div>
          <div class="specs-dim-grid">
            <div></div>
            <div class="specs-unit-header">cm</div>
            <div class="specs-unit-header">in</div>
            <div class="specs-row-label">Length</div>
            <div class="specs-input-wrap"><input type="number" step="0.01" min="0" placeholder="—" id="carton-inner-l-cm" oninput="convertDim('carton-inner-l-cm','carton-inner-l-in','cm')" /><span class="specs-unit-tag">cm</span></div>
            <div class="specs-input-wrap"><input type="number" step="0.01" min="0" placeholder="—" id="carton-inner-l-in" oninput="convertDim('carton-inner-l-in','carton-inner-l-cm','in')" /><span class="specs-unit-tag">in</span></div>
            <div class="specs-row-label">Width</div>
            <div class="specs-input-wrap"><input type="number" step="0.01" min="0" placeholder="—" id="carton-inner-w-cm" oninput="convertDim('carton-inner-w-cm','carton-inner-w-in','cm')" /><span class="specs-unit-tag">cm</span></div>
            <div class="specs-input-wrap"><input type="number" step="0.01" min="0" placeholder="—" id="carton-inner-w-in" oninput="convertDim('carton-inner-w-in','carton-inner-w-cm','in')" /><span class="specs-unit-tag">in</span></div>
            <div class="specs-row-label">Height</div>
            <div class="specs-input-wrap"><input type="number" step="0.01" min="0" placeholder="—" id="carton-inner-h-cm" oninput="convertDim('carton-inner-h-cm','carton-inner-h-in','cm')" /><span class="specs-unit-tag">cm</span></div>
            <div class="specs-input-wrap"><input type="number" step="0.01" min="0" placeholder="—" id="carton-inner-h-in" oninput="convertDim('carton-inner-h-in','carton-inner-h-cm','in')" /><span class="specs-unit-tag">in</span></div>
            <hr class="specs-dim-divider" />
            <div></div>
            <div class="specs-unit-header">kg</div>
            <div class="specs-unit-header">lb</div>
            <div class="specs-row-label">Weight</div>
            <div class="specs-input-wrap"><input type="number" step="0.001" min="0" placeholder="—" id="carton-inner-weight" oninput="convertWeight('carton-inner-weight','carton-inner-weight-lbs','kg'); updateOuterWeightHint()" /><span class="specs-unit-tag">kg</span></div>
            <div class="specs-input-wrap"><input type="text" placeholder="—" id="carton-inner-weight-lbs" oninput="convertWeight('carton-inner-weight-lbs','carton-inner-weight','lbs'); updateOuterWeightHint()" /><span class="specs-unit-tag">lb</span></div>
            <div class="specs-full-row" style="margin-top:6px;">
              <div class="specs-row-label" style="margin-bottom:5px;">Qty <span style="font-weight:400; text-transform:none; font-size:11px;">(units / carton)</span></div>
              <input type="number" min="0" placeholder="e.g. 10" id="carton-inner-count" style="width:100%;" oninput="autoCalcCartons(); updateOuterWeightHint()" />
            </div>
            <div class="specs-full-row" id="inner-arrange-hint" style="display:none; margin-top:5px; font-size:11px; color:var(--accent); line-height:1.5;"></div>
          </div>
        </div>

        <!-- Column 3: Outer Carton -->
        <div class="specs-col">
          <div class="specs-col-title" style="display:flex; align-items:center; justify-content:space-between;">
            <span>Outer Carton</span>
            <span id="outer-calc-badge" style="display:none; font-size:10px; font-weight:600; color:var(--accent); opacity:0.8;">auto</span>
          </div>
          <div class="specs-dim-grid">
            <div></div>
            <div class="specs-unit-header">cm</div>
            <div class="specs-unit-header">in</div>
            <div class="specs-row-label">Length</div>
            <div class="specs-input-wrap"><input type="number" step="0.01" min="0" placeholder="—" id="carton-outer-l-cm" oninput="convertDim('carton-outer-l-cm','carton-outer-l-in','cm'); renderPalletViz()" /><span class="specs-unit-tag">cm</span></div>
            <div class="specs-input-wrap"><input type="number" step="0.01" min="0" placeholder="—" id="carton-outer-l-in" oninput="convertDim('carton-outer-l-in','carton-outer-l-cm','in'); renderPalletViz()" /><span class="specs-unit-tag">in</span></div>
            <div class="specs-row-label">Width</div>
            <div class="specs-input-wrap"><input type="number" step="0.01" min="0" placeholder="—" id="carton-outer-w-cm" oninput="convertDim('carton-outer-w-cm','carton-outer-w-in','cm'); renderPalletViz()" /><span class="specs-unit-tag">cm</span></div>
            <div class="specs-input-wrap"><input type="number" step="0.01" min="0" placeholder="—" id="carton-outer-w-in" oninput="convertDim('carton-outer-w-in','carton-outer-w-cm','in'); renderPalletViz()" /><span class="specs-unit-tag">in</span></div>
            <div class="specs-row-label">Height</div>
            <div class="specs-input-wrap"><input type="number" step="0.01" min="0" placeholder="—" id="carton-outer-h-cm" oninput="convertDim('carton-outer-h-cm','carton-outer-h-in','cm'); renderPalletViz()" /><span class="specs-unit-tag">cm</span></div>
            <div class="specs-input-wrap"><input type="number" step="0.01" min="0" placeholder="—" id="carton-outer-h-in" oninput="convertDim('carton-outer-h-in','carton-outer-h-cm','in'); renderPalletViz()" /><span class="specs-unit-tag">in</span></div>
            <hr class="specs-dim-divider" />
            <div></div>
            <div class="specs-unit-header">kg</div>
            <div class="specs-unit-header">lb</div>
            <div class="specs-row-label">Weight</div>
            <div class="specs-input-wrap"><input type="number" step="0.001" min="0" placeholder="—" id="carton-outer-weight" oninput="convertWeight('carton-outer-weight','carton-outer-weight-lbs','kg')" /><span class="specs-unit-tag">kg</span></div>
            <div class="specs-input-wrap"><input type="text" placeholder="—" id="carton-outer-weight-lbs" oninput="convertWeight('carton-outer-weight-lbs','carton-outer-weight','lbs')" /><span class="specs-unit-tag">lb</span></div>
            <div class="specs-full-row" style="margin-top:6px;">
              <div class="specs-row-label" style="margin-bottom:5px;">Qty <span style="font-weight:400; text-transform:none; font-size:11px;">(inner cartons / outer)</span></div>
              <input type="number" min="0" placeholder="e.g. 4" id="carton-outer-count" style="width:100%;" oninput="autoCalcCartons(); updateOuterWeightHint()" />
            </div>
            <div class="specs-full-row" id="outer-arrange-hint" style="display:none; margin-top:5px; font-size:11px; color:var(--accent); line-height:1.5;"></div>
            <div class="specs-full-row" id="outer-weight-hint" style="display:none; margin-top:8px; padding:8px 10px; background:var(--accent-glow); border:1px solid color-mix(in srgb, var(--accent) 30%, var(--border)); border-radius:var(--radius-sm); font-size:11px; color:var(--accent); line-height:1.6;"></div>
            <!-- Pallet inline stats — below qty -->
            <div class="specs-full-row" id="pallet-inline-stats" style="display:none; margin-top:8px; font-size:11px; color:var(--accent); line-height:1.6;"></div>
          </div>
        </div>

      </div>

      <!-- Hidden inputs kept for backward compatibility -->
      <input type="hidden" id="carton-unit-weight" />
      <input type="hidden" id="carton-unit-weight-lbs" />
    </div>
  </div>

  <!-- ── Card: Pallet Visualization ── -->
  <div class="section-card">
    <div class="section-header section-header-collapsible" onclick="toggleSection(this.closest('.section-card'))">
      <span class="section-title">Pallet View — 40 × 48 Standard</span>
      <span class="section-chevron">›</span>
    </div>
    <div class="section-body">
      <div style="display:flex; gap:32px; align-items:flex-start; flex-wrap:wrap;">
        <canvas id="pallet-canvas" width="480" height="360" style="flex-shrink:0; border-radius:8px; background:var(--surface2);"></canvas>
        <div style="flex:1; min-width:200px;">
          <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
            <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:var(--text-muted);">Pallet Stats</div>
            <div style="display:flex; align-items:center; gap:6px;">
              <label style="font-size:11px; color:var(--text-muted); white-space:nowrap;">Max height</label>
              <input type="number" id="pallet-max-height" value="60" min="1" max="120" step="1"
                style="width:56px; text-align:center; font-size:12px;"
                oninput="renderPalletViz()" />
              <span style="font-size:11px; color:var(--text-muted);">in</span>
            </div>
          </div>
          <div id="pallet-stats" style="color:var(--text-muted); font-size:13px;">Enter outer carton dimensions to calculate.</div>
          <div style="margin-top:20px; padding-top:16px; border-top:1px solid var(--border);">
            <label style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); display:block; margin-bottom:6px;">Total Outer Cartons to Ship</label>
            <input type="number" min="0" placeholder="e.g. 500" id="pallet-total-cartons"
              style="width:100%; box-sizing:border-box;"
              oninput="renderPalletViz(); syncShippingDims(); calcFreight();" />
            <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">Enter your total shipment carton count to calculate pallets needed.</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  </div><!-- /#wb-tab-workbook -->

  <!-- ── Tab: Shipping ── -->
  <div id="wb-tab-shipping" class="wb-tab-content">
  <div class="sh-layout">

    <!-- ══ PRICING TIER selector — above outer carton + results ══ -->
    <div class="sh-box sh-tier-bar" style="margin-bottom:0;">
      <div class="sh-tier-row">
        <div class="sh-tier-select-wrap">
          <span class="sh-tier-row-label">Pricing Tier</span>
          <div class="sh-tier-select-inner">
            <select id="sh-tier-select" onchange="onShippingTierSelect()">
              <option value="">— Select a tier —</option>
            </select>
          </div>
        </div>
        <div id="sh-tier-details" style="gap:0; align-items:stretch;">
          <div class="sh-tier-stat">
            <span class="sh-tier-stat-label">Unit (RMB)</span>
            <span class="sh-tier-stat-val" id="sh-td-rmb-full">—</span>
          </div>
          <div class="sh-tier-stat">
            <span class="sh-tier-stat-label">Unit (USD)</span>
            <span class="sh-tier-stat-val" id="sh-td-usd-full">—</span>
          </div>
          <div class="sh-tier-stat">
            <span class="sh-tier-stat-label">Total Product Cost</span>
            <span class="sh-tier-stat-val" id="sh-td-total">—</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ SHIPPING METHOD ══ -->
    <div class="sh-box sh-shipping-box">
      <div class="sh-box-title">Shipping</div>

      <!-- Method + rate single bar -->
      <div class="freight-method-rate-row">
        <label class="freight-method-label">Shipping Method</label>
        <div class="freight-method-bar">
          <div class="freight-method-bar-select">
            <select id="freight-mode" onchange="updateFreightRate(); calcFreight()">
              <option value="slow" selected>Slow Boat</option>
              <option value="fast">Fast Boat</option>
              <option value="airupp">Air + UPS</option>
              <option value="directair">Direct Air</option>
            </select>
          </div>
          <div class="freight-method-bar-rate rmb">
            <span class="freight-method-bar-sym">¥</span>
            <span class="freight-method-bar-val" id="freight-rate-rmb-display">12.00</span>
            <span class="freight-method-bar-unit">per kg</span>
          </div>
          <div class="freight-method-bar-rate usd">
            <span class="freight-method-bar-sym">$</span>
            <span class="freight-method-bar-val" id="freight-rate-usd-display">1.67</span>
            <span class="freight-method-bar-unit">per kg</span>
          </div>
        </div>
      </div>

      <!-- Comparison table — full width -->
      <table class="freight-ref-table freight-cmp-table">
        <thead>
          <tr><th>Method</th><th>Total Weight</th><th>¥ / kg</th><th>Cost (¥)</th><th>Cost ($)</th></tr>
        </thead>
        <tbody>
          <tr><td>Slow Boat</td><td id="freight-wt-slow">—</td><td>12.00</td><td id="freight-rmb-slow">—</td><td id="freight-usd-slow">—</td></tr>
          <tr><td>Fast Boat</td><td id="freight-wt-fast">—</td><td>14.00</td><td id="freight-rmb-fast">—</td><td id="freight-usd-fast">—</td></tr>
          <tr><td>Air + UPS</td><td id="freight-wt-airupp">—</td><td>44.00</td><td id="freight-rmb-airupp">—</td><td id="freight-usd-airupp">—</td></tr>
          <tr><td>Direct Air</td><td id="freight-wt-directair">—</td><td>65.00</td><td id="freight-rmb-directair">—</td><td id="freight-usd-directair">—</td></tr>
        </tbody>
      </table>
      <!-- Shipment Split -->
      <hr class="freight-section-divider" />
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
        <div>
          <div style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted);">Shipment Split</div>
          <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">Split qty across multiple shipping methods</div>
        </div>
        <button class="btn btn-ghost" style="font-size:12px; padding:4px 10px;" onclick="addShipmentSplit()">+ Add Split</button>
      </div>
      <div id="shipment-split-body"></div>
      <div id="shipment-split-summary" style="font-size:12px; color:var(--text-muted); margin-top:6px;"></div>
    </div><!-- /.sh-shipping-box -->

    <!-- ══ TOP ROW: left col (stacked) + right col (results) ══ -->
    <div class="sh-top-row">

      <!-- Left column: Outer Carton box + Pallet Stats box stacked -->
      <div class="sh-left-col">

        <!-- Box 1: Outer Carton dims + carton count -->
        <div class="sh-box">
          <div class="sh-box-title">Outer Carton</div>
          <div class="sh-dim-table">
            <div class="sh-dim-row">
              <span class="sh-dim-lbl">L</span>
              <span class="sh-dim-val" id="sh-l-cm">—</span><span class="sh-dim-unit">cm</span>
              <span class="sh-dim-sep">/</span>
              <span class="sh-dim-val" id="sh-l-in">—</span><span class="sh-dim-unit">in</span>
            </div>
            <div class="sh-dim-row">
              <span class="sh-dim-lbl">W</span>
              <span class="sh-dim-val" id="sh-w-cm">—</span><span class="sh-dim-unit">cm</span>
              <span class="sh-dim-sep">/</span>
              <span class="sh-dim-val" id="sh-w-in">—</span><span class="sh-dim-unit">in</span>
            </div>
            <div class="sh-dim-row">
              <span class="sh-dim-lbl">H</span>
              <span class="sh-dim-val" id="sh-h-cm">—</span><span class="sh-dim-unit">cm</span>
              <span class="sh-dim-sep">/</span>
              <span class="sh-dim-val" id="sh-h-in">—</span><span class="sh-dim-unit">in</span>
            </div>
            <div class="sh-dim-row sh-dim-row-wt">
              <span class="sh-dim-lbl">Wt</span>
              <span class="sh-dim-val" id="sh-wt-kg">—</span><span class="sh-dim-unit">kg</span>
              <span class="sh-dim-sep">/</span>
              <span class="sh-dim-val" id="sh-wt-lbs">—</span><span class="sh-dim-unit">lbs</span>
            </div>
          </div>
          <div class="sh-dim-table" style="margin-top:10px; padding-top:10px; border-top:1px solid var(--border);">
            <div class="sh-dim-row">
              <span class="sh-dim-lbl" style="min-width:90px; font-size:11px;">Inner / Outer</span>
              <span class="sh-dim-val" id="sh-inner-per-outer">—</span><span class="sh-dim-unit">inner cartons</span>
            </div>
            <div class="sh-dim-row">
              <span class="sh-dim-lbl" style="min-width:90px; font-size:11px;">Units / Outer</span>
              <span class="sh-dim-val" id="sh-units-per-outer">—</span><span class="sh-dim-unit">products</span>
            </div>
          </div>
          <div class="sh-cartons-display">
            <span class="sh-cartons-label">Cartons in Shipment</span>
            <span class="sh-cartons-val" id="sh-cartons-val">—</span>
            <span class="sh-cartons-unit">cartons</span>
          </div>
        </div>

        <!-- Box 2: Pallet Stats -->
        <div class="sh-box">
          <div class="sh-box-title">Pallet Stats</div>
          <div id="sh-pallet-stats-body">
            <span style="font-size:12px; color:var(--text-muted); font-style:italic;">Enter outer carton dimensions on the Workbook tab to see pallet stats.</span>
          </div>
        </div>

      </div><!-- /.sh-left-col -->

      <!-- Right column: Results -->
      <div class="sh-box sh-results-box">
        <div class="sh-box-title">Results</div>

        <div class="freight-result">
          <span class="freight-result-label">Actual Weight</span>
          <span class="freight-result-value" id="freight-out-actual">—</span>
        </div>
        <div class="freight-result">
          <span class="freight-result-label">Volumetric Weight</span>
          <span class="freight-result-value" id="freight-out-vol">—</span>
        </div>
        <div class="freight-result">
          <span class="freight-result-label">Chargeable Weight</span>
          <span class="freight-result-value highlight" id="freight-out-charge">—</span>
        </div>
        <div class="freight-result">
          <span class="freight-result-label">Formula Used</span>
          <span class="freight-result-value" id="freight-out-formula" style="font-size:12px;">—</span>
        </div>

        <div class="freight-bars">
          <div class="freight-bar-col">
            <span class="freight-bar-val" id="freight-bar-actual-val">—</span>
            <div class="freight-bar actual-bar" id="freight-bar-actual" style="height:50%"></div>
            <span class="freight-bar-label">Actual</span>
          </div>
          <div class="freight-bar-col">
            <span class="freight-bar-val" id="freight-bar-vol-val">—</span>
            <div class="freight-bar vol-bar" id="freight-bar-vol" style="height:100%"></div>
            <span class="freight-bar-label">Volumetric</span>
          </div>
          <div class="freight-bar-col">
            <span class="freight-bar-val" id="freight-bar-charge-val">—</span>
            <div class="freight-bar charge-bar" id="freight-bar-charge" style="height:100%"></div>
            <span class="freight-bar-label">Chargeable</span>
          </div>
        </div>

        <div class="freight-verdict" id="freight-verdict">—</div>

        <div class="freight-result" style="margin-top:14px;">
          <span class="freight-result-label">Estimated Shipping Cost</span>
          <span class="freight-result-value cost" id="freight-out-cost">—</span>
        </div>
        <div class="freight-extra" id="freight-extra" style="display:none;">
          Extra cost due to volumetric: <span></span>
        </div>
        <div class="freight-result">
          <span class="freight-result-label">Volume (CBM)</span>
          <span class="freight-result-value" id="freight-cmp-sea">—</span>
        </div>

        <!-- Harmonized Code -->
        <div class="sh-box-title" style="margin-top:16px;">Harmonized Code</div>
        <div style="margin-bottom:6px;">
          <input type="text" id="freight-hs-code" placeholder="e.g. 9403.20.0010"
            style="width:100%; padding:8px 10px; border:1px solid var(--border); border-radius:var(--radius-sm); background:var(--surface); color:var(--text); font-size:14px; font-family:'SF Mono','Consolas',monospace; box-sizing:border-box; letter-spacing:0.05em;"
            oninput="if(_appReady) autoSaveWorkbook()" />
        </div>
        <div style="font-size:11px; color:var(--text-muted); line-height:1.5;">The HTS/HS code used for customs classification and duty rate determination.</div>
      </div><!-- /.sh-results-box -->

    </div><!-- /.sh-top-row -->

  </div><!-- /.sh-layout -->
  </div><!-- /#wb-tab-shipping -->

  <!-- ── Tab: Pricing ── -->
  <div id="wb-tab-pricing" class="wb-tab-content">

  <!-- ── Card: Delivered Cost Summary ── -->
  <div class="section-card">
    <div class="section-header">
      <span class="section-title">Delivered Cost Summary</span>
    </div>
    <div class="section-body">
      <div id="pricing-no-selection-msg" class="pricing-no-selection">
        Select a pricing tier on the Shipping tab to see your delivered cost summary.
      </div>
      <div id="pricing-summary-view" style="display:none;">
        <div class="pricing-summary-grid">

          <!-- Product Cost -->
          <div class="pricing-cost-block">
            <div class="pricing-cost-block-title">Product Cost</div>
            <div class="pricing-cost-row">
              <span class="pricing-cost-row-label">Quantity</span>
              <span class="pricing-cost-row-value" id="ps-qty">—</span>
            </div>
            <div class="pricing-cost-row">
              <span class="pricing-cost-row-label">Unit Price (RMB)</span>
              <span class="pricing-cost-row-value" id="ps-unit-rmb">—</span>
            </div>
            <div class="pricing-cost-row">
              <span class="pricing-cost-row-label">Unit Price (USD)</span>
              <span class="pricing-cost-row-value" id="ps-unit-usd">—</span>
            </div>
            <div class="pricing-cost-subtotal">
              <div class="pricing-cost-row">
                <span class="pricing-cost-row-label">Total Product Cost</span>
                <span class="pricing-cost-row-value" id="ps-product-total">—</span>
              </div>
            </div>
          </div>

          <!-- Shipping Cost -->
          <div class="pricing-cost-block">
            <div class="pricing-cost-block-title">Shipping Cost</div>
            <div class="pricing-cost-row">
              <span class="pricing-cost-row-label">Method</span>
              <span class="pricing-cost-row-value" id="ps-sh-method">—</span>
            </div>
            <div class="pricing-cost-row">
              <span class="pricing-cost-row-label">Chargeable Weight</span>
              <span class="pricing-cost-row-value" id="ps-sh-weight">—</span>
            </div>
            <div class="pricing-cost-row">
              <span class="pricing-cost-row-label">Rate</span>
              <span class="pricing-cost-row-value" id="ps-sh-rate">—</span>
            </div>
            <div class="pricing-cost-subtotal">
              <div class="pricing-cost-row">
                <span class="pricing-cost-row-label">Total Shipping Cost</span>
                <span class="pricing-cost-row-value" id="ps-sh-total">—</span>
              </div>
            </div>
          </div>

        </div><!-- /.pricing-summary-grid -->

        <!-- Grand Total bar -->
        <div class="pricing-grand-total-bar">
          <div class="pricing-grand-total-label">Total Delivered Cost (USD)</div>
          <div class="pricing-grand-total-value" id="ps-grand-total">—</div>
        </div>

      </div><!-- /#pricing-summary-view -->
    </div>
  </div>

  <!-- ── Card: Quote Reference ── -->
  <div class="section-card collapsed" id="pricing-quote-ref-card">
    <div class="section-header section-header-collapsible" onclick="toggleSection(this.closest('.section-card'))">
      <span class="section-title">Quote Reference</span>
      <div class="qr-collapsed-summary" id="pricing-quote-ref-summary">
        <div class="qr-sum-item">
          <span class="qr-sum-label">Unit (RMB)</span>
          <span class="qr-sum-val" id="qrs-rmb">—</span>
        </div>
        <div class="qr-sum-item">
          <span class="qr-sum-label">Unit (USD)</span>
          <span class="qr-sum-val" id="qrs-usd">—</span>
        </div>
        <div class="qr-sum-item">
          <span class="qr-sum-label">Total (USD)</span>
          <span class="qr-sum-val" id="qrs-total">—</span>
        </div>
        <div class="qr-sum-item">
          <span class="qr-sum-label">Lead Time</span>
          <span class="qr-sum-val" id="qrs-lead">—</span>
        </div>
      </div>
      <div class="qr-expand-toggle">
        <span class="qr-expand-label">All Line Items</span>
        <span class="section-chevron">›</span>
      </div>
    </div>
    <div class="section-body" id="pricing-quote-ref-body">
      <span class="pricing-no-selection">Add items to Quote Details on the Workbook tab.</span>
    </div>
  </div>

  <!-- Hidden: legacy elements kept for calcAdditionalFees() compatibility -->
  <div style="display:none;" aria-hidden="true">
    <table><thead></thead><tbody id="tier-body"></tbody></table>
    <span id="pricing-fee-sample-desc"></span>
    <span id="pricing-fee-sample-rmb"></span>
    <span id="pricing-fee-sample"></span>
    <span id="pricing-fee-tooling-desc"></span>
    <span id="pricing-fee-tooling-rmb"></span>
    <span id="pricing-fee-tooling"></span>
    <span id="pricing-fee-die-desc"></span>
    <span id="pricing-fee-die-rmb"></span>
    <span id="pricing-fee-die"></span>
    <span id="pricing-fee-plate-desc"></span>
    <span id="pricing-fee-plate-rmb"></span>
    <span id="pricing-fee-plate"></span>
    <span id="pricing-fee-design-desc"></span>
    <span id="pricing-fee-design"></span>
    <tbody id="pricing-extra-fee-rows"></tbody>
    <span id="pricing-fee-total-rmb"></span>
    <span id="pricing-fee-total"></span>
    <table><tbody id="pricing-grand-total-body"></tbody></table>
    <div id="pricing-grand-total-section"></div>
  </div>

  </div><!-- /#wb-tab-pricing -->

  <!-- ── Tab: Quote for Client ── -->
  <div id="wb-tab-quote" class="wb-tab-content" style="display:none!important">
  <div class="section-card">
    <div class="section-header" style="display:flex; justify-content:space-between; align-items:center;">
      <span class="section-title">Quote for Client</span>
      <button class="email-quote-btn" onclick="emailQuote()" title="Email Quote">
        <span class="email-icon">&#9993;</span>
        <span class="email-label">Email Quote</span>
      </button>
    </div>
    <div class="section-body">
      <div class="form-grid form-grid-2">
        <div class="field">
          <label>Client Name</label>
          <input type="text" id="quote-client-name" placeholder="Auto-filled from workbook" readonly />
        </div>
        <div class="field">
          <label>Product Name</label>
          <input type="text" id="quote-product-name" placeholder="Auto-filled from workbook" readonly />
        </div>
        <div class="field">
          <label>Quote Date</label>
          <input type="date" id="quote-date" />
        </div>
        <div class="field">
          <label>Valid Until</label>
          <input type="date" id="quote-valid-until" />
        </div>
        <div class="field">
          <label>Quantity</label>
          <input type="number" min="0" placeholder="e.g. 500" id="quote-cl-qty" />
        </div>
        <div class="field">
          <label>Unit Price (USD)</label>
          <div class="currency-prefix currency-usd">
            <input type="number" step="0.01" min="0" placeholder="e.g. 5.50" id="quote-cl-unit-price" oninput="calcQuoteTotal()" />
          </div>
        </div>
        <div class="field">
          <label>Shipping Cost (USD)</label>
          <div class="currency-prefix currency-usd">
            <input type="number" step="0.01" min="0" placeholder="e.g. 250.00" id="quote-cl-shipping" oninput="calcQuoteTotal()" />
          </div>
        </div>
        <div class="field">
          <label>Total Quote (USD)</label>
          <div class="currency-prefix currency-usd">
            <input type="text" id="quote-cl-total" placeholder="auto" readonly style="font-weight:700;" />
          </div>
        </div>
        <div class="field col-full">
          <label>Notes / Terms</label>
          <textarea placeholder="Payment terms, delivery details, special conditions…" id="quote-cl-notes" style="min-height:80px;"></textarea>
        </div>
      </div>
    </div>
  </div>
  </div><!-- /#wb-tab-quote -->

  <!-- ── Tab: Art ── -->
  <div id="wb-tab-art" class="wb-tab-content">
  <div class="section-card">
    <div class="section-header">
      <span class="section-title">Art</span>
    </div>
    <div class="section-body">
      <p style="color:var(--text-muted); margin-bottom:16px;">Upload artwork files, logos, and design assets for this product.</p>

      <!-- Client Logo -->
      <div style="margin-bottom:20px; padding-bottom:20px; border-bottom:1px solid var(--border);">
        <label style="display:block; margin-bottom:10px; font-weight:600; font-size:12px; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-muted);">Client Logo</label>
        <div style="display:flex; align-items:center; gap:16px;">
          <div id="client-logo-preview" onclick="document.getElementById('clientLogoInput').click()" style="width:72px; height:72px; border-radius:10px; border:2px dashed var(--border); display:flex; align-items:center; justify-content:center; cursor:pointer; background:var(--surface2); overflow:hidden; flex-shrink:0; transition:border-color 0.15s;">
            <span id="client-logo-placeholder" style="font-size:24px; color:var(--text-muted);">🖼</span>
          </div>
          <div>
            <button onclick="document.getElementById('clientLogoInput').click()" class="btn-create" style="font-size:12px; padding:7px 14px;">Upload Logo</button>
            <button id="client-logo-remove-btn" onclick="removeClientLogo()" style="display:none; margin-left:8px; background:none; border:none; cursor:pointer; font-size:12px; color:var(--danger); font-family:inherit; font-weight:600;">Remove</button>
            <p style="font-size:11px; color:var(--text-muted); margin-top:6px; margin-bottom:0;">Used as the client avatar in the sidebar and search.</p>
          </div>
        </div>
        <input type="file" id="clientLogoInput" accept="image/*" onchange="handleClientLogoUpload(event)" style="display:none;" />
      </div>

      <div class="form-grid form-grid-2">
        <div class="field">
          <label>Art Status</label>
          <div class="select-wrapper">
            <select id="art-status">
              <option value="">Select status...</option>
              <option value="pending">Pending</option>
              <option value="received">Received from Client</option>
              <option value="in-review">In Review</option>
              <option value="approved">Approved</option>
              <option value="revision-needed">Revision Needed</option>
              <option value="sent-to-factory">Sent to Factory</option>
            </select>
          </div>
        </div>
        <div class="field">
          <label>Art Due Date</label>
          <input type="date" id="art-due-date" />
        </div>
      </div>

      <div class="field" style="margin-top:12px;">
        <label>Art Notes</label>
        <textarea placeholder="Design specifications, color requirements, placement instructions..." id="art-notes" style="min-height:80px;"></textarea>
      </div>

      <div style="margin-top:18px;">
        <label style="display:block; margin-bottom:8px;">Art Files</label>
        <div class="image-gallery" id="artGallery">
          <div class="image-add-btn" onclick="document.getElementById('artInput').click()">
            <div class="add-icon">+</div>
            <div class="add-text">Add File</div>
          </div>
        </div>
        <input type="file" id="artInput" accept="image/*" multiple onchange="handleArtFiles(event)" style="display:none;" />
      </div>
    </div>
  </div>
  </div><!-- /#wb-tab-art -->

  <!-- ── Tab: Office Invoice ── -->
  <div id="wb-tab-invoice" class="wb-tab-content" style="display:none!important">
  <div class="section-card">
    <div class="section-header">
      <span class="section-title">Office Invoice</span>
    </div>
    <div class="section-body">
      <div class="form-grid form-grid-2">
        <div class="field">
          <label>Invoice Number</label>
          <input type="text" placeholder="e.g. INV-2025-001" id="inv-number" />
        </div>
        <div class="field">
          <label>Invoice Date</label>
          <input type="date" id="inv-date" />
        </div>
        <div class="field">
          <label>Bill To (Client)</label>
          <input type="text" id="inv-bill-to" placeholder="Auto-filled from workbook" readonly />
        </div>
        <div class="field">
          <label>Payment Due Date</label>
          <input type="date" id="inv-due-date" />
        </div>
        <div class="field">
          <label>Product / Description</label>
          <input type="text" id="inv-product" placeholder="Auto-filled from workbook" readonly />
        </div>
        <div class="field">
          <label>Quantity</label>
          <input type="number" min="0" placeholder="e.g. 500" id="inv-qty" />
        </div>
        <div class="field">
          <label>Unit Price (USD)</label>
          <div class="currency-prefix currency-usd">
            <input type="number" step="0.01" min="0" placeholder="e.g. 5.50" id="inv-unit-price" oninput="calcInvoiceTotal()" />
          </div>
        </div>
        <div class="field">
          <label>Shipping (USD)</label>
          <div class="currency-prefix currency-usd">
            <input type="number" step="0.01" min="0" placeholder="e.g. 250.00" id="inv-shipping" oninput="calcInvoiceTotal()" />
          </div>
        </div>
        <div class="field">
          <label>Subtotal (USD)</label>
          <div class="currency-prefix currency-usd">
            <input type="text" id="inv-subtotal" placeholder="auto" readonly />
          </div>
        </div>
        <div class="field">
          <label>Total Due (USD)</label>
          <div class="currency-prefix currency-usd">
            <input type="text" id="inv-total" placeholder="auto" readonly style="font-weight:700; font-size:16px;" />
          </div>
        </div>
        <div class="field">
          <label>Payment Status</label>
          <div class="select-wrap"><select id="inv-status">
            <option value="unpaid">Unpaid</option>
            <option value="partial">Partially Paid</option>
            <option value="paid">Paid</option>
          </select></div>
        </div>
        <div class="field">
          <label>Payment Method</label>
          <div class="select-wrap"><select id="inv-method">
            <option value="">Select…</option>
            <option>Wire Transfer</option>
            <option>Credit Card</option>
            <option>PayPal</option>
            <option>Check</option>
          </select></div>
        </div>
        <div class="field col-full">
          <label>Notes</label>
          <textarea placeholder="Additional notes, PO numbers, references…" id="inv-notes" style="min-height:80px;"></textarea>
        </div>
      </div>
    </div>
  </div>
  </div><!-- /#wb-tab-invoice -->

  <!-- Footer note -->
  <div style="text-align:center; color:var(--text-muted); font-size:12px; margin-top:8px;">
    Market Sculpt Product Workbook · Generated <span id="gen-date"></span>
  </div>

</main>
</div><!-- /#view-workbook -->

<!-- ══════════════════════════════════════════════════════════════════════
     VIEW: SAMPLES DASHBOARD
═══════════════════════════════════════════════════════════════════════ -->
<div id="view-samples" class="view">
  <main class="container">

    <!-- Hero Header -->
    <div style="display:flex; align-items:center; gap:16px; margin-bottom:24px; padding:24px 0 8px;">
      <div style="width:48px; height:48px; border-radius:12px; background:linear-gradient(135deg, var(--accent), color-mix(in srgb, var(--accent) 60%, #a855f7)); flex-shrink:0;"></div>
      <div>
        <h1 style="font-size:22px; font-weight:700; color:var(--text); margin:0; line-height:1.2;">Sample Requests</h1>
        <p style="color:var(--text-muted); font-size:13px; margin:2px 0 0;">Track all sample requests across your workbooks</p>
      </div>
      <div style="margin-left:auto; display:flex; gap:8px; align-items:center;">
        <span id="samples-count-badge" style="background:var(--accent-glow); border:1px solid color-mix(in srgb, var(--accent) 40%, var(--border)); color:var(--accent); padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; white-space:nowrap;">0 samples</span>
      </div>
    </div>

    <!-- Stats Row -->
    <div id="samples-stats-row" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:12px; margin-bottom:20px;"></div>

    <!-- Samples Table -->
    <div class="section-card">
      <div class="section-header" style="display:flex; align-items:center; gap:10px;">
        <span class="section-title" style="margin-right:auto;">All Sample Requests</span>
        <div style="display:flex; gap:6px;">
          <button class="status-filter-btn active" id="samples-filter-all" onclick="filterSamples('all', this)">All</button>
          <button class="status-filter-btn" id="samples-filter-pending" onclick="filterSamples('pending', this)">Pending</button>
          <button class="status-filter-btn" id="samples-filter-requested" onclick="filterSamples('requested', this)">Requested</button>
          <button class="status-filter-btn" id="samples-filter-received" onclick="filterSamples('received', this)">Received</button>
          <button class="status-filter-btn" id="samples-filter-approved" onclick="filterSamples('approved', this)">Approved</button>
        </div>
      </div>
      <div class="section-body" style="padding:0;">
        <div class="table-scroll-wrapper">
        <table class="dash-table" id="samples-table">
          <thead>
            <tr>
              <th>ITEM</th>
              <th>CLIENT</th>
              <th>WORKBOOK</th>
              <th style="text-align:right;">QTY</th>
              <th style="text-align:right;">RMB</th>
              <th style="text-align:right;">USD</th>
              <th>LEAD</th>
              <th style="text-align:center;">STATUS</th>
            </tr>
          </thead>
          <tbody id="samples-tbody">
            <!-- populated by JS -->
          </tbody>
        </table>
        </div>
        <div id="samples-empty" style="display:none; padding:60px 20px; text-align:center;">
          <div style="font-size:16px; font-weight:600; color:var(--text); margin-bottom:8px;">No sample requests yet</div>
          <div style="font-size:13px; color:var(--text-muted); max-width:320px; margin:0 auto;">Check the <strong>Sample</strong> checkbox on RFQ line items in any workbook to track them here.</div>
        </div>
      </div>
    </div>

  </main>
</div><!-- /#view-samples -->

<!-- ══════════════════════════════════════════════════════════════════════
     VIEW: RFQ DASHBOARD
═══════════════════════════════════════════════════════════════════════ -->
<div id="view-rfq" class="view">
  <main class="container">

    <!-- Hero Header -->
    <div style="display:flex; align-items:center; gap:16px; margin-bottom:24px; padding:24px 0 8px;">
      <div style="width:48px; height:48px; border-radius:12px; background:linear-gradient(135deg, var(--accent), color-mix(in srgb, var(--accent) 60%, #10b981)); flex-shrink:0;"></div>
      <div>
        <h1 style="font-size:22px; font-weight:700; color:var(--text); margin:0; line-height:1.2;">RFQ Queue</h1>
        <p style="color:var(--text-muted); font-size:13px; margin:2px 0 0;">Workbooks submitted for quote review — oldest first</p>
      </div>
      <div style="margin-left:auto; display:flex; gap:8px; align-items:center;">
        <span id="rfq-count-badge" style="background:var(--accent-glow); border:1px solid color-mix(in srgb, var(--accent) 40%, var(--border)); color:var(--accent); padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; white-space:nowrap;">0 RFQs</span>
      </div>
    </div>

    <!-- Stats Row -->
    <div id="rfq-stats-row" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:12px; margin-bottom:20px;"></div>

    <!-- RFQ Table -->
    <div class="section-card">
      <div class="section-header" style="display:flex; align-items:center; gap:10px;">
        <span class="section-title" style="margin-right:auto;">Submitted RFQs</span>
      </div>
      <div class="section-body" style="padding:0;">
        <div class="table-scroll-wrapper">
        <table class="dash-table" id="rfq-table" style="width:100%;">
          <thead>
            <tr>
              <th>CLIENT</th>
              <th>WORKBOOK</th>
              <th>SUBMITTED</th>
              <th style="text-align:right;">LINES</th>
              <th style="text-align:right;">QTY</th>
              <th style="text-align:right;">RMB</th>
              <th style="text-align:right;">USD</th>
            </tr>
          </thead>
          <tbody id="rfq-tbody">
            <!-- populated by JS -->
          </tbody>
        </table>
        </div>
        <div id="rfq-empty" style="display:none; padding:60px 20px; text-align:center;">
          <div style="font-size:16px; font-weight:600; color:var(--text); margin-bottom:8px;">No RFQs in the queue</div>
          <div style="font-size:13px; color:var(--text-muted); max-width:360px; margin:0 auto;">Workbooks appear here when they're set to <strong>Quote Submitted</strong> and sent via the <strong>RFQ</strong> button on the status bar.</div>
        </div>
      </div>
    </div>

  </main>
</div><!-- /#view-rfq -->

<!-- ══════════════════════════════════════════════════════════════════════
     VIEW: SHIPMENTS LIST
═══════════════════════════════════════════════════════════════════════ -->
<div id="view-shipments" class="view">
  <main class="container">
    <div class="section-card">
      <div class="section-header" style="display:flex; align-items:center; gap:10px;">
        <span class="section-title" style="margin-right:auto;">Shipments</span>
        <button class="btn btn-primary" onclick="openNewShipmentModal()">+ New Shipment</button>
      </div>
      <div class="section-body">
        <div id="shipment-list-content">
          <div class="shipment-list-empty">
            <div class="shipment-list-empty-icon">🚢</div>
            <div class="shipment-list-empty-title">No shipments yet</div>
            <div class="shipment-list-empty-sub">Create a shipment to start consolidating workbooks into a container.</div>
          </div>
        </div>
      </div>
    </div>
  </main>
</div><!-- /#view-shipments -->

<!-- ══════════════════════════════════════════════════════════════════════
     VIEW: SHIPMENT DETAIL
═══════════════════════════════════════════════════════════════════════ -->
<div id="view-shipment-detail" class="view">
  <main class="container">

    <!-- Breadcrumb -->
    <div style="margin-bottom:16px;">
      <a href="#/shipments" onclick="event.preventDefault(); location.hash='#/shipments'" style="font-size:12px; color:var(--text-muted); text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
        ‹ All Shipments
      </a>
    </div>

    <!-- Header: name + status + dates -->
    <div class="ship-detail-header">
      <div class="ship-detail-name-wrap">
        <input type="text" class="ship-detail-name" id="ship-detail-name" placeholder="Shipment name…"
          oninput="onShipmentNameChange()" />
      </div>
      <div class="ship-detail-controls">
        <div class="ship-select-wrap">
        <select id="ship-detail-status" onchange="onShipmentStatusChange()">
          <option value="planning">Planning</option>
          <option value="booked">Booked</option>
          <option value="in_transit">In Transit</option>
          <option value="delivered">Delivered</option>
        </select>
        </div>
        <div style="display:flex; align-items:center; gap:6px;">
          <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--text-muted);">ETD</label>
          <input type="date" id="ship-detail-etd" style="height:34px; padding:0 10px; border:1px solid var(--border); border-radius:var(--radius-sm); background:var(--surface2); color:var(--text); font-size:13px; font-family:inherit; outline:none;" oninput="onShipmentDateChange()" />
        </div>
        <div style="display:flex; align-items:center; gap:6px;">
          <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--text-muted);">ETA</label>
          <input type="date" id="ship-detail-eta" style="height:34px; padding:0 10px; border:1px solid var(--border); border-radius:var(--radius-sm); background:var(--surface2); color:var(--text); font-size:13px; font-family:inherit; outline:none;" oninput="onShipmentDateChange()" />
        </div>
        <div id="ship-delivered-wrap" style="display:none; align-items:center; gap:6px;">
          <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:#34d399;">Delivered On</label>
          <input type="date" id="ship-detail-delivered" style="height:34px; padding:0 10px; border:1px solid rgba(52,211,153,0.4); border-radius:var(--radius-sm); background:rgba(52,211,153,0.08); color:var(--text); font-size:13px; font-family:inherit; outline:none;" oninput="onShipmentDateChange()" />
        </div>
        <button class="btn-danger" onclick="deleteShipment(_currentShipmentId)">Delete</button>
      </div>
    </div>

    <!-- Container type selector -->
    <div style="margin-bottom:6px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-muted);">Container Type</div>
    <div class="container-type-row" id="ship-container-btns">
      <button class="container-type-btn active" data-type="20ft" onclick="setContainerType('20ft')">20' Standard <span style="font-weight:400; opacity:0.7;">· 25 CBM · 21,700 kg</span></button>
      <button class="container-type-btn" data-type="40ft"  onclick="setContainerType('40ft')">40' Standard <span style="font-weight:400; opacity:0.7;">· 55 CBM · 26,500 kg</span></button>
      <button class="container-type-btn" data-type="40hc"  onclick="setContainerType('40hc')">40' High Cube <span style="font-weight:400; opacity:0.7;">· 65 CBM · 26,500 kg</span></button>
    </div>

    <!-- Container utilization -->
    <div class="ship-util-grid" id="ship-util-grid">
      <div class="ship-util-block">
        <div class="ship-util-label">Volume (CBM)</div>
        <div class="ship-util-values">
          <span class="ship-util-current" id="ship-util-cbm-cur">0</span>
          <span class="ship-util-max" id="ship-util-cbm-max">/ 25</span>
        </div>
        <div class="ship-util-track"><div class="ship-util-fill" id="ship-util-cbm-bar" style="width:0%"></div></div>
        <div class="ship-util-pct" id="ship-util-cbm-pct">0% full</div>
      </div>
      <div class="ship-util-block">
        <div class="ship-util-label">Weight (kg)</div>
        <div class="ship-util-values">
          <span class="ship-util-current" id="ship-util-wt-cur">0</span>
          <span class="ship-util-max" id="ship-util-wt-max">/ 21,700</span>
        </div>
        <div class="ship-util-track"><div class="ship-util-fill" id="ship-util-wt-bar" style="width:0%"></div></div>
        <div class="ship-util-pct" id="ship-util-wt-pct">0% full</div>
      </div>
      <div class="ship-util-block">
        <div class="ship-util-label">Pallets</div>
        <div class="ship-util-values">
          <span class="ship-util-current" id="ship-util-pal-cur">0</span>
          <span class="ship-util-max" id="ship-util-pal-max">/ 10</span>
        </div>
        <div class="ship-util-track"><div class="ship-util-fill" id="ship-util-pal-bar" style="width:0%"></div></div>
        <div class="ship-util-pct" id="ship-util-pal-pct">0% full</div>
      </div>
    </div>

    <!-- Order entries -->
    <div class="section-card" style="margin-bottom:0;">
      <div class="section-header">
        <div style="display:flex; align-items:center; gap:8px;">
          <span class="section-title">Orders in this Shipment</span>
          <span id="ship-order-count" style="font-size:12px; color:var(--text-muted);"></span>
        </div>
        <button class="btn btn-primary" style="font-size:12px; padding:5px 12px;" onclick="openAddOrderToShipmentModal()">+ Add Order</button>
      </div>
      <div class="section-body" style="padding:12px 16px;">
        <div id="ship-order-empty" style="padding:30px 0; text-align:center; color:var(--text-muted); font-size:13px;">
          No orders added yet.
        </div>
        <div id="ship-order-list"></div>
      </div>
    </div>

  </main>
</div><!-- /#view-shipment-detail -->

<!-- ══════════════════════════════════════════════════════════════════════
     VIEW: ORDERS LIST
═══════════════════════════════════════════════════════════════════════ -->
<div id="view-orders" class="view">
  <main class="container">
    <div class="section-card">
      <div class="section-header" style="display:flex; align-items:center; gap:10px;">
        <span class="section-title" style="margin-right:auto;">Orders</span>
        <button class="btn btn-ghost" onclick="exportAllOrdersCsv()" title="Export all orders to CSV" style="display:inline-flex; align-items:center; gap:5px; font-size:12px;">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Export All
        </button>
        <button class="btn btn-primary" onclick="openNewOrderModal()">+ Create Order</button>
      </div>
      <div class="section-body">
        <div id="order-list-content">
          <div class="order-list-empty">
            <div class="order-list-empty-icon">📋</div>
            <div class="order-list-empty-title">No orders yet</div>
            <div class="order-list-empty-sub">Create an order to track approved workbooks through production.</div>
          </div>
        </div>
      </div>
    </div>
  </main>
</div><!-- /#view-orders -->

<!-- ══════════════════════════════════════════════════════════════════════
     VIEW: INVENTORY
═══════════════════════════════════════════════════════════════════════ -->
<div id="view-inventory" class="view">
  <main class="container">

    <!-- Hero Header -->
    <div style="display:flex; align-items:center; gap:16px; margin-bottom:24px; padding:24px 0 8px;">
      <div>
        <h1 style="font-size:22px; font-weight:700; color:var(--text); margin:0; line-height:1.2;">Inventory</h1>
        <p style="color:var(--text-muted); font-size:13px; margin:2px 0 0;">Permanent SKUs promoted from your workbooks</p>
      </div>
      <div style="margin-left:auto; display:flex; gap:10px; align-items:center;">
        <label style="display:flex; align-items:center; gap:8px; font-size:12px; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:0.05em;">
          Client
          <span class="ship-select-wrap" style="min-width:200px;">
            <select id="inventory-client-filter" onchange="filterInventoryByClient(this.value)"
              style="width:100%; height:34px; padding:0 34px 0 12px; border:1px solid var(--border); border-radius:8px; background:var(--surface2); color:var(--text); font-size:13px; font-family:inherit; outline:none; cursor:pointer; appearance:none; -webkit-appearance:none; text-transform:none; letter-spacing:normal; font-weight:500;">
              <option value="all">All Clients</option>
            </select>
          </span>
        </label>
        <span id="inventory-count-badge" style="background:var(--accent-glow); border:1px solid color-mix(in srgb, var(--accent) 40%, var(--border)); color:var(--accent); padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; white-space:nowrap;">0 SKUs</span>
      </div>
    </div>

    <!-- Tab Switch -->
    <div style="display:flex; gap:6px; margin-bottom:20px; border-bottom:1px solid var(--border);">
      <button class="inv-tab-btn active" id="inv-tab-dashboard" onclick="switchInventoryTab('dashboard', this)">Dashboard</button>
      <button class="inv-tab-btn" id="inv-tab-skus" onclick="switchInventoryTab('skus', this)">All SKUs</button>
    </div>

    <!-- Dashboard Pane -->
    <div id="inventory-dashboard-pane">
      <div id="inventory-dashboard-content">
        <!-- populated by renderInventoryDashboard() -->
      </div>
    </div>

    <!-- All SKUs Pane -->
    <div id="inventory-skus-pane" style="display:none;">
      <div class="section-card">
        <div class="section-header" style="display:flex; align-items:center; gap:10px;">
          <span class="section-title" style="margin-right:auto;">All SKUs</span>
          <input type="text" id="inventory-search" placeholder="Search SKU or product…"
            oninput="filterInventory(this.value)"
            style="background:var(--surface2); border:1px solid var(--border); border-radius:8px; padding:7px 12px; font-size:13px; color:var(--text); font-family:inherit; outline:none; width:220px;" />
        </div>
        <div class="section-body" style="padding:0;">
          <div id="inventory-list-content">
            <div style="text-align:center; padding:60px 24px; color:var(--text-muted);">
              <div style="font-size:32px; margin-bottom:12px;">📦</div>
              <div style="font-size:16px; font-weight:600; margin-bottom:6px;">No SKUs promoted yet</div>
              <div style="font-size:13px;">Open a workbook and use "Promote to Permanent SKU" to add SKUs here.</div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </main>
</div><!-- /#view-inventory -->

<!-- ══════════════════════════════════════════════════════════════════════
     VIEW: COMMISSIONS
     Owner-facing reporting dashboard. Mirrors the Inventory dashboard
     pattern (KPI strip → cards) so visual rhythm stays consistent.
     ══════════════════════════════════════════════════════════════════════ -->
<div id="view-commissions" class="view">
  <main class="container">

    <!-- Hero Header -->
    <div style="display:flex; align-items:center; gap:16px; margin-bottom:24px; padding:24px 0 8px;">
      <div>
        <h1 style="font-size:22px; font-weight:700; color:var(--text); margin:0; line-height:1.2;">Commissions</h1>
        <p style="color:var(--text-muted); font-size:13px; margin:2px 0 0;">Account Manager &amp; Salesperson payouts on promoted workbooks</p>
      </div>
      <div style="margin-left:auto; display:flex; gap:10px; align-items:center;">
        <label style="display:flex; align-items:center; gap:8px; font-size:12px; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:0.05em;">
          Employee
          <span class="ship-select-wrap" style="min-width:200px;">
            <select id="commissions-employee-filter" onchange="filterCommissionsByEmployee(this.value)"
              style="width:100%; height:34px; padding:0 34px 0 12px; border:1px solid var(--border); border-radius:8px; background:var(--surface2); color:var(--text); font-size:13px; font-family:inherit; outline:none; cursor:pointer; appearance:none; -webkit-appearance:none; text-transform:none; letter-spacing:normal; font-weight:500;">
              <option value="all">All Employees</option>
              <option value="Parker Low">Parker Low</option>
              <option value="Jackson Hollberg">Jackson Hollberg</option>
              <option value="Karen">Karen</option>
            </select>
          </span>
        </label>
        <label style="display:flex; align-items:center; gap:8px; font-size:12px; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:0.05em;">
          Role
          <span class="ship-select-wrap" style="min-width:170px;">
            <select id="commissions-role-filter" onchange="filterCommissionsByRole(this.value)"
              style="width:100%; height:34px; padding:0 34px 0 12px; border:1px solid var(--border); border-radius:8px; background:var(--surface2); color:var(--text); font-size:13px; font-family:inherit; outline:none; cursor:pointer; appearance:none; -webkit-appearance:none; text-transform:none; letter-spacing:normal; font-weight:500;">
              <option value="all">All Roles</option>
              <option value="account_manager">Account Manager</option>
              <option value="salesperson">Salesperson</option>
              <option value="operations">Operations</option>
            </select>
          </span>
        </label>
        <span id="commissions-count-badge" style="background:var(--accent-glow); border:1px solid color-mix(in srgb, var(--accent) 40%, var(--border)); color:var(--accent); padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; white-space:nowrap;">0 rows</span>
      </div>
    </div>

    <div id="commissions-dashboard-content">
      <!-- populated by renderCommissionsDashboard() -->
    </div>

  </main>
</div><!-- /#view-commissions -->

<!-- ══════════════════════════════════════════════════════════════════════
     VIEW: ORDER DETAIL
═══════════════════════════════════════════════════════════════════════ -->
<div id="view-order-detail" class="view">
  <main class="container">

    <!-- Breadcrumb -->
    <div style="margin-bottom:16px;">
      <a href="#/orders" onclick="event.preventDefault(); location.hash='#/orders'" style="font-size:12px; color:var(--text-muted); text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
        ← Back to Orders
      </a>
    </div>

    <!-- Header: client name (big) → order name → controls -->
    <div class="order-detail-header">
      <div class="order-detail-client-name" id="order-detail-client-name"></div>
      <div class="order-detail-name-wrap">
        <input type="text" class="order-detail-name" id="order-detail-name" placeholder="Order name…"
          oninput="onOrderNameChange()" />
      </div>
      <div class="order-detail-controls">
        <div style="display:flex; align-items:center; gap:6px;">
          <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--text-muted);">PO #</label>
          <input type="text" id="order-detail-po" placeholder="PO number…"
            style="height:34px; padding:0 10px; border:1px solid var(--border); border-radius:var(--radius-sm); background:var(--surface2); color:var(--text); font-size:13px; font-family:inherit; outline:none; width:140px;"
            oninput="onOrderPoChange()" />
        </div>
        <div style="display:flex; align-items:center; gap:6px;">
          <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--text-muted);">Deposit %</label>
          <input type="number" id="order-detail-deposit-pct" min="0" max="100" step="1"
            style="height:34px; padding:0 10px; border:1px solid var(--border); border-radius:var(--radius-sm); background:var(--surface2); color:var(--text); font-size:13px; font-family:inherit; outline:none; width:70px;"
            oninput="onOrderDepositPctChange()" />
        </div>
        <div id="order-detail-date-tag" style="font-size:12px; color:var(--text-muted); padding:0 4px;"></div>
        <button id="btn-notify-order" onclick="openNotifyModal('order_status')"
          class="btn btn-ghost" style="display:inline-flex; align-items:center; gap:5px; font-size:12px; border-color:var(--accent); color:var(--accent);"
          title="Send status notification to client">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
          Notify Client
        </button>
        <button class="btn btn-ghost" onclick="exportOrderCsv(_currentOrderId)" title="Export this order to CSV" style="display:inline-flex; align-items:center; gap:5px; font-size:12px;">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Export CSV
        </button>
        <button class="btn-danger" onclick="deleteOrder(_currentOrderId)">Delete</button>
      </div>
    </div>

    <!-- Change Request Banner (shown only when client has requested changes) -->
    <div id="order-cr-card" class="cr-card" style="display:none;">
      <div class="cr-card-head">
        <div class="cr-card-head-icon">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
        </div>
        <span class="cr-card-title">Client Change Request</span>
        <span class="cr-card-meta" id="cr-meta"></span>
      </div>
      <div class="cr-card-body" id="cr-body">
        <div style="color:var(--text-muted); font-size:13px;">Loading…</div>
      </div>
    </div>

    <!-- Order Sheet -->
    <div class="section-card" style="margin-bottom:16px;">
      <div class="section-header">
        <div style="display:flex; align-items:center; gap:8px;">
          <span class="section-title">Order Sheet</span>
          <span id="order-wb-count" style="font-size:12px; color:var(--text-muted);"></span>
        </div>
        <button class="btn btn-primary" style="font-size:12px; padding:5px 12px;" onclick="openAddWorkbookToOrderModal()">+ Add Workbook</button>
      </div>
      <div class="section-body" style="padding:0;">
        <div id="order-sheet-empty" style="padding:40px 20px; text-align:center; color:var(--text-muted); font-size:13px;">
          No workbooks in this order.
        </div>
        <table class="order-sheet-table" id="order-sheet-table" style="display:none;">
          <thead>
            <tr>
              <th>Item</th>
              <th style="text-align:right;">Qty</th>
              <th style="text-align:right;">Unit (RMB)</th>
              <th style="text-align:right;">Unit (USD)</th>
              <th style="text-align:right;">Total (RMB)</th>
              <th style="text-align:right;">Total (USD)</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="order-sheet-tbody"></tbody>
          <tfoot id="order-sheet-tfoot"></tfoot>
        </table>
      </div>
    </div>

    <!-- Deposit Tracking -->
    <div class="section-card" style="margin-bottom:16px;" id="order-deposit-card">
      <div class="section-header">
        <span class="section-title">Payment Tracking</span>
      </div>
      <div class="section-body">
        <div id="order-deposit-rows"></div>
      </div>
    </div>

    <!-- Notes -->
    <div class="section-card" style="margin-bottom:0;">
      <div class="section-header">
        <span class="section-title">Notes</span>
      </div>
      <div class="section-body">
        <textarea id="order-detail-notes" placeholder="Add notes about this order…"
          style="width:100%; min-height:100px; border:1px solid var(--border); border-radius:var(--radius-sm); background:var(--surface2); color:var(--text); font-size:13px; font-family:inherit; padding:10px 12px; outline:none; resize:vertical; box-sizing:border-box;"
          oninput="onOrderNotesChange()"></textarea>
      </div>
    </div>

  </main>
</div><!-- /#view-order-detail -->

</div><!-- /.app-content -->
</div><!-- /.app-layout -->

<!-- ── New Shipment Modal ──────────────────────────────────────────────── -->
<div class="modal-overlay" id="modal-new-shipment" onclick="if(event.target===this)closeNewShipmentModal()" style="z-index:1000;">
  <div class="modal" style="max-width:400px;">
    <div class="modal-title">New Shipment</div>
    <form onsubmit="createShipment(event)">
      <div class="modal-field">
        <label>Shipment Name <span class="required">*</span></label>
        <input type="text" id="new-ship-name" placeholder="e.g. May 2026 Container" required />
      </div>
      <div class="modal-field">
        <label>Container Type</label>
        <div class="select-wrap">
          <select id="new-ship-container">
            <option value="20ft">20' Standard — 25 CBM · 21,700 kg</option>
            <option value="40ft">40' Standard — 55 CBM · 26,500 kg</option>
            <option value="40hc" selected>40' High Cube — 65 CBM · 26,500 kg</option>
          </select>
        </div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" onclick="closeNewShipmentModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Shipment</button>
      </div>
    </form>
  </div>
</div>

<!-- ── Add Order to Shipment Modal ────────────────────────────────────── -->
<div class="modal-overlay" id="modal-add-order-to-shipment" onclick="if(event.target===this)closeAddOrderToShipmentModal()" style="z-index:1000;">
  <div class="modal" style="max-width:560px;">
    <div class="modal-title">Add Order to Shipment</div>
    <div class="modal-field" style="margin-bottom:12px;">
      <label style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); display:block; margin-bottom:6px;">Filter by Client</label>
      <div class="ship-select-wrap" style="width:100%;">
        <select id="ship-order-picker-client" onchange="onShipOrderClientChange()"
          style="width:100%; height:38px; padding:0 28px 0 12px; border:1px solid var(--border); border-radius:var(--radius-sm); background:var(--surface2); color:var(--text); font-size:13px; font-family:inherit; outline:none; cursor:pointer; appearance:none; -webkit-appearance:none;">
          <option value="">All clients</option>
        </select>
      </div>
    </div>
    <input type="text" class="wb-picker-search" id="ship-order-picker-search" placeholder="Search orders…" oninput="filterShipOrderPicker(this.value)" />
    <div class="modal-wb-picker" id="ship-order-picker-list">
      <!-- populated by JS -->
    </div>
    <div class="modal-actions" style="margin-top:14px;">
      <button type="button" class="btn btn-ghost" onclick="closeAddOrderToShipmentModal()">Cancel</button>
      <button type="button" class="btn btn-primary" onclick="confirmAddOrderToShipment()">Add to Shipment</button>
    </div>
  </div>
</div>

<!-- ── Create Order Modal ─────────────────────────────────────────────── -->
<!-- ── Notification Modal ──────────────────────────────────────────────────── -->
<div class="modal-overlay" id="modal-notify" onclick="if(event.target===this)closeNotifyModal()" style="z-index:1100;">
  <div class="modal" style="max-width:480px;">
    <div class="modal-title" id="notify-modal-title">Send Notification</div>

    <!-- Recipients -->
    <div style="margin-bottom:18px;">
      <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); margin-bottom:8px;">Recipients</div>
      <div id="notify-recipients" style="display:flex; flex-direction:column; gap:6px;"></div>
    </div>

    <!-- Preview -->
    <div style="background:var(--surface2); border:1px solid var(--border); border-radius:10px; padding:16px; margin-bottom:16px;">
      <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); margin-bottom:8px;">Email Preview</div>
      <div style="font-size:13px; font-weight:700; color:var(--text); margin-bottom:4px;" id="notify-preview-subject"></div>
      <div style="font-size:12px; color:var(--text-muted); line-height:1.5;" id="notify-preview-body"></div>
    </div>
    <!-- Portal notice (shown for quote_ready and order_confirmed) -->
    <div id="notify-portal-notice" style="display:none; background:rgba(232,117,26,0.08); border:1px solid rgba(232,117,26,0.25); border-radius:8px; padding:10px 14px; margin-bottom:16px; font-size:12px; color:var(--accent); line-height:1.6;">
      <strong>Portal link will be generated.</strong> The client email will include a secure link where they can review, approve, or request changes. You'll receive the link after sending.
    </div>

    <div style="display:flex; gap:10px; justify-content:flex-end;">
      <button class="btn btn-ghost" onclick="closeNotifyModal()">Cancel</button>
      <button class="btn btn-primary" id="btn-notify-send" onclick="sendNotification()" style="display:inline-flex; align-items:center; gap:6px;">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        Send Notification
      </button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="modal-new-order" onclick="if(event.target===this)closeNewOrderModal()" style="z-index:1000;">
  <div class="modal" style="max-width:560px;">
    <div class="modal-title">Create Order</div>
    <div class="modal-field" style="margin-bottom:12px;">
      <label style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); display:block; margin-bottom:6px;">Client</label>
      <select id="order-picker-client" onchange="onOrderPickerClientChange()"
        style="width:100%; height:38px; padding:0 10px; border:1px solid var(--border); border-radius:var(--radius-sm); background:var(--surface2); color:var(--text); font-size:13px; font-family:inherit; outline:none;">
        <option value="">Select a client…</option>
      </select>
    </div>
    <div id="order-picker-wb-section" style="display:none;">
      <input type="text" class="wb-picker-search" id="order-picker-search" placeholder="Search workbooks…" oninput="filterOrderPicker(this.value)" />
      <div class="modal-wb-picker" id="order-picker-list">
        <!-- populated by JS -->
      </div>
    </div>
    <div class="modal-actions" style="margin-top:14px;">
      <button type="button" class="btn btn-ghost" onclick="closeNewOrderModal()">Cancel</button>
      <button type="button" class="btn btn-primary" onclick="createOrder()">Create Order</button>
    </div>
  </div>
</div>

<!-- ── Add Workbook to Order Modal ───────────────────────────────────── -->
<div class="modal-overlay" id="modal-add-wb-to-order" onclick="if(event.target===this)closeAddWorkbookToOrderModal()" style="z-index:1000;">
  <div class="modal" style="max-width:560px;">
    <div class="modal-title">Add Workbook to Order</div>
    <input type="text" class="wb-picker-search" id="order-add-wb-search" placeholder="Search products…" oninput="filterOrderAddPicker(this.value)" />
    <div class="modal-wb-picker" id="order-add-wb-list">
      <!-- populated by JS -->
    </div>
    <div class="modal-actions" style="margin-top:14px;">
      <button type="button" class="btn btn-ghost" onclick="closeAddWorkbookToOrderModal()">Cancel</button>
      <button type="button" class="btn btn-primary" onclick="confirmAddWorkbookToOrder()">Add to Order</button>
    </div>
  </div>
</div>

<!-- ── New Workbook Modal ─────────────────────────────────────────────── -->
<div class="modal-overlay" id="modal-overlay" onclick="if(event.target===this)closeModal()">
  <div class="modal">
    <div class="modal-title">New Workbook</div>
    <form id="new-workbook-form" onsubmit="createWorkbook(event)">
      <div class="modal-field">
        <label>Product Name <span class="required">*</span></label>
        <input type="text" id="modal-product" placeholder="e.g. Custom Tote Bag" required oninput="debounceSuggestCategories()" />
      </div>
      <div class="modal-field" id="modal-client-field">
        <label>Client <span class="required">*</span></label>
        <div class="select-wrap"><select id="modal-client">
          <option value="">Select a client…</option>
        </select></div>
      </div>
      <div class="modal-field" id="modal-client-display" style="display:none;">
        <label>Client</label>
        <div style="font-size:14px; font-weight:600; color:var(--text); padding:6px 0;" id="modal-client-label"></div>
      </div>
      <div class="modal-field">
        <label>Description <span class="required">*</span></label>
        <textarea id="modal-desc" placeholder="Brief description of the product…" required
          oninput="debounceSuggestCategories()"></textarea>
      </div>
      <div id="modal-suggest-area" style="margin-bottom:12px;"></div>
      <div class="modal-actions">
        <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn-create">Create Workbook</button>
      </div>
    </form>
  </div>
</div>

<!-- ── Add Client Modal ───────────────────────────────────────────────── -->
<div class="modal-overlay" id="client-modal-overlay" onclick="if(event.target===this)closeClientModal()">
  <div class="modal" style="width:560px;">
    <div class="modal-title">Add Client</div>
    <form id="add-client-form" onsubmit="createClient(event)">
      <div class="modal-field">
        <label>Client Name <span class="required">*</span></label>
        <input type="text" id="modal-client-name" placeholder="e.g. Acme Corp" required />
      </div>
      <div class="modal-field">
        <label>Primary Contact</label>
        <input type="text" id="modal-client-contact" placeholder="e.g. Jane Smith" />
      </div>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
        <div class="modal-field" style="margin-bottom:0;">
          <label>Email Address</label>
          <input type="email" id="modal-client-email" placeholder="contact@company.com" />
        </div>
        <div class="modal-field" style="margin-bottom:0;">
          <label>Phone</label>
          <input type="tel" id="modal-client-phone" placeholder="+1 (555) 000-0000" />
        </div>
      </div>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-top:16px;">
        <div class="modal-field" style="margin-bottom:0;">
          <label>Billing Address</label>
          <textarea id="modal-client-billing" placeholder="Street, City, State ZIP" rows="3"
            oninput="if(document.getElementById('modal-ship-same').checked){ document.getElementById('modal-client-shipping').value = this.value; }"></textarea>
        </div>
        <div class="modal-field" style="margin-bottom:0;">
          <label style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
            Shipping Address
            <span style="display:flex; align-items:center; gap:5px; font-size:11px; font-weight:500; text-transform:none; letter-spacing:0; color:var(--text-muted);">
              <input type="checkbox" id="modal-ship-same" style="width:13px; height:13px; accent-color:var(--accent); cursor:pointer; margin:0;"
                onchange="
                  const ta = document.getElementById('modal-client-shipping');
                  if(this.checked){ ta.value = document.getElementById('modal-client-billing').value; ta.disabled = true; ta.style.opacity='0.5'; }
                  else { ta.disabled = false; ta.style.opacity=''; }
                " />
              Same as billing
            </span>
          </label>
          <textarea id="modal-client-shipping" placeholder="Street, City, State ZIP" rows="3"></textarea>
        </div>
      </div>
      <!-- Account Manager + Salesperson + Operations assignment. Drives
           commission tracking on the Commissions dashboard once workbooks
           for this client are promoted/ordered. All three can be left as
           "— Unassigned —". Operations is the third commission slot —
           Karen lives there for now; rate defaults to 5%. AM/SP defaults
           are 0% (set the % manually on the client detail page after
           creating). -->
      <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:14px; margin-top:16px;">
        <div class="modal-field" style="margin-bottom:0;">
          <label>Account Manager</label>
          <!-- ship-select-wrap renders the same custom triangle arrow used
               everywhere else; appearance:none on the select hides the
               native OS arrow so we don't get a double-arrow. -->
          <span class="ship-select-wrap" style="display:block; width:100%;">
            <select id="modal-client-am" style="appearance:none; -webkit-appearance:none; -moz-appearance:none; padding-right:32px; cursor:pointer;">
              <option value="">— Unassigned —</option>
              <option value="Parker Low">Parker Low</option>
              <option value="Jackson Hollberg">Jackson Hollberg</option>
            </select>
          </span>
        </div>
        <div class="modal-field" style="margin-bottom:0;">
          <label>Salesperson</label>
          <span class="ship-select-wrap" style="display:block; width:100%;">
            <select id="modal-client-sp" style="appearance:none; -webkit-appearance:none; -moz-appearance:none; padding-right:32px; cursor:pointer;">
              <option value="">— Unassigned —</option>
              <option value="Parker Low">Parker Low</option>
              <option value="Jackson Hollberg">Jackson Hollberg</option>
            </select>
          </span>
        </div>
        <div class="modal-field" style="margin-bottom:0;">
          <label>Operations</label>
          <span class="ship-select-wrap" style="display:block; width:100%;">
            <select id="modal-client-op" style="appearance:none; -webkit-appearance:none; -moz-appearance:none; padding-right:32px; cursor:pointer;">
              <option value="">— Unassigned —</option>
              <option value="Karen">Karen</option>
            </select>
          </span>
        </div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn-cancel" onclick="closeClientModal()">Cancel</button>
        <button type="submit" class="btn-create">Create Client</button>
      </div>
    </form>
  </div>
</div>

<!-- ── Delete Confirmation Modal ──────────────────────────────────────── -->
<div class="modal-overlay" id="delete-modal-overlay" onclick="if(event.target===this)closeDeleteModal()">
  <div class="modal">
    <div class="modal-title">Delete Workbook</div>
    <p style="margin:0 0 8px; color:var(--text); font-size:14px;">Are you sure you want to delete <strong id="delete-product-name"></strong>?</p>
    <p style="margin:0; color:var(--text-muted); font-size:13px;">This action cannot be undone.</p>
    <div class="modal-actions">
      <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
      <button type="button" class="btn-danger" id="confirm-delete-btn">Delete</button>
    </div>
  </div>
</div>

<!-- ── Delete Client Modal ────────────────────────────────────────────── -->
<div class="modal-overlay" id="delete-client-modal-overlay" onclick="if(event.target===this)closeDeleteClientModal()">
  <div class="modal">
    <div class="modal-title">Delete Client</div>
    <p style="margin:0 0 8px; color:var(--text); font-size:14px;">Are you sure you want to delete <strong id="delete-client-name"></strong> and all their workbooks?</p>
    <p style="margin:0; color:var(--text-muted); font-size:13px;">This action cannot be undone.</p>
    <div class="modal-actions">
      <button type="button" class="btn-cancel" onclick="closeDeleteClientModal()">Cancel</button>
      <button type="button" class="btn-danger" id="confirm-delete-client-btn">Delete</button>
    </div>
  </div>
</div>

<!-- ── Users Modal (admin only) ──────────────────────────────────────────
     Two views inside one modal:
       #users-list-view    — list of all users + Add New User form
       #users-detail-view  — drill-in for a single user (edit display name,
                             email, role, change password, delete). The
                             list row click → showUserDetail(id) swaps which
                             pane is visible. Back arrow returns to list. -->
<div class="modal-overlay" id="users-modal-overlay" onclick="if(event.target===this)closeUsersModal()" style="display:none;">
  <div class="modal" style="max-width:520px; max-height:80vh; display:flex; flex-direction:column;">
    <div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; padding:16px 20px; border-bottom:1px solid var(--border);">
      <h3 id="users-modal-title" style="margin:0; font-size:16px; display:flex; align-items:center; gap:8px;">
        <button id="users-back-btn" onclick="showUsersList()" style="display:none; background:none; border:none; color:var(--text-muted); font-size:18px; cursor:pointer; padding:0; line-height:1;" title="Back to user list">←</button>
        <span id="users-title-text">User Management</span>
      </h3>
      <button onclick="closeUsersModal()" style="background:none; border:none; color:var(--text-muted); font-size:20px; cursor:pointer;">✕</button>
    </div>

    <!-- View 1: list of users + Add New User form -->
    <div id="users-list-view" style="padding:16px 20px; overflow-y:auto; flex:1;">
      <div id="users-list" style="margin-bottom:20px;"></div>
      <div style="border-top:1px solid var(--border); padding-top:16px;">
        <div style="font-size:13px; font-weight:600; margin-bottom:10px;">Add New User</div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:8px;">
          <input id="new-user-username" type="text" placeholder="Username" class="field-input" style="font-size:13px;" />
          <input id="new-user-display" type="text" placeholder="Display Name" class="field-input" style="font-size:13px;" />
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:8px;">
          <input id="new-user-email" type="email" placeholder="Email (optional)" class="field-input" style="font-size:13px;" />
          <span class="pw-wrap" style="display:block; position:relative;">
            <input id="new-user-password" type="password" placeholder="Password" class="field-input" style="font-size:13px; width:100%; padding-right:36px;" />
            <button type="button" onclick="togglePasswordField(this)" aria-label="Toggle password visibility" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#9ba3c0; padding:2px; line-height:0; display:flex; align-items:center;">
              <svg class="eye-show" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" display="block"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="eye-hide" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" display="none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </span>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:10px;">
          <span class="ship-select-wrap" style="display:block;">
            <select id="new-user-role" class="field-input" style="font-size:13px; appearance:none; -webkit-appearance:none; -moz-appearance:none; padding-right:32px; cursor:pointer; width:100%;">
              <option value="user">User</option>
              <option value="admin">Admin</option>
            </select>
          </span>
          <button class="btn-create" onclick="addUser()" style="font-size:13px; padding:8px 16px;">+ Add User</button>
        </div>
      </div>
    </div>

    <!-- View 2: detail/edit a single user. Hidden until a row is clicked. -->
    <div id="users-detail-view" style="display:none; padding:16px 20px; overflow-y:auto; flex:1;">
      <!-- All controls populated by renderUserDetail() against _userDetailCurrent -->
      <div id="users-detail-body"></div>
    </div>
  </div>
</div>

<!-- ── History Modal ── -->
<div class="modal-overlay" id="history-modal-overlay" onclick="if(event.target===this)closeHistoryModal()" style="display:none;">
  <div class="modal" style="max-width:560px; max-height:80vh; display:flex; flex-direction:column;">
    <div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; padding:16px 20px; border-bottom:1px solid var(--border);">
      <h3 style="margin:0; font-size:16px;">Revision History</h3>
      <button onclick="closeHistoryModal()" style="background:none; border:none; font-size:20px; cursor:pointer; color:var(--text-muted);">&times;</button>
    </div>
    <div id="history-list" style="overflow-y:auto; flex:1; padding:12px 20px;">
      <p style="color:var(--text-muted); text-align:center;">Loading...</p>
    </div>
  </div>
</div>

<!-- ── Archive Modal ── -->
<div class="modal-overlay" id="archive-modal-overlay" onclick="if(event.target===this)closeArchiveModal()" style="display:none;">
  <div class="modal" style="max-width:640px; max-height:80vh; display:flex; flex-direction:column;">
    <div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; padding:16px 20px; border-bottom:1px solid var(--border);">
      <h3 style="margin:0; font-size:16px;">&#128451; Archive</h3>
      <button onclick="closeArchiveModal()" style="background:none; border:none; font-size:20px; cursor:pointer; color:var(--text-muted);">&times;</button>
    </div>
    <div style="padding:12px 20px; border-bottom:1px solid var(--border);">
      <div class="seg-control">
        <button class="archive-tab seg-tab active" onclick="switchArchiveTab('workbooks', this)">Workbooks</button>
        <button class="archive-tab seg-tab" onclick="switchArchiveTab('clients', this)">Clients</button>
      </div>
    </div>
    <div id="archive-list" style="overflow-y:auto; flex:1; padding:12px 20px;">
      <p style="color:var(--text-muted); text-align:center;">Loading...</p>
    </div>
  </div>
</div>

<script>
  /* ── Session guard: require login on every hard refresh ─────────────── */
  (function() {
    const params = new URLSearchParams(window.location.search);
    if (params.get('new_login')) {
      sessionStorage.setItem('ms_auth', '1');
      const savedHash = params.get('hash');
      window.history.replaceState({}, '', window.location.pathname);
      if (savedHash) location.hash = savedHash;
    } else if (!sessionStorage.getItem('ms_auth')) {
      const h = location.hash;
      window.location.href = 'logout.php' + (h ? '?hash=' + encodeURIComponent(h) : '');
    }
  })();

  /* ── Sidebar Submenu Toggle ─────────────────────────────────────────── */
  function toggleSubmenu(btn) {
    btn.classList.toggle('expanded');
    const submenu = btn.nextElementSibling;
    if (submenu && submenu.classList.contains('nav-submenu')) {
      submenu.classList.toggle('open');
    }
  }
  /* ── Theme ─────────────────────────────────────────────────────────────── */
  /* ── Mobile Sidebar Toggle ─────────────────────────────────────────── */
  function toggleMobileSidebar() {
    document.querySelector('.sidebar').classList.toggle('mobile-open');
    document.getElementById('sidebar-overlay').classList.toggle('open');
    document.body.classList.toggle('sidebar-open');
  }
  // Close sidebar when a nav-item is clicked on mobile (event delegation)
  document.querySelector('.sidebar').addEventListener('click', (e) => {
    if (e.target.closest('.nav-item') && document.querySelector('.sidebar').classList.contains('mobile-open')) {
      toggleMobileSidebar();
    }
  });

  function toggleTheme() {
    const body = document.body;
    const isDark = body.dataset.theme === 'dark';
    body.dataset.theme = isDark ? 'light' : 'dark';
    const btn = document.getElementById('theme-dropdown-btn');
    if (btn) btn.textContent = isDark ? 'Dark Mode' : 'Light Mode';
  }

  /* ── User Dropdown ──────────────────────────────────────────────────────── */
  function toggleUserDropdown() {
    document.getElementById('user-dropdown').classList.toggle('open');
  }
  function closeUserDropdown() {
    document.getElementById('user-dropdown').classList.remove('open');
  }
  document.addEventListener('click', function(e) {
    const menu = document.getElementById('user-menu');
    if (menu && !menu.contains(e.target)) closeUserDropdown();
  });

  /* ── Change Password Modal ──────────────────────────────────────────────── */
  function openChangePasswordModal() {
    let modal = document.getElementById('change-password-modal');
    if (!modal) {
      modal = document.createElement('div');
      modal.id = 'change-password-modal';
      modal.className = 'modal-overlay';
      modal.style.cssText = 'display:flex;';
      modal.innerHTML = `
        <div class="modal" style="max-width:400px; width:100%;">
          <div class="modal-header">
            <h3 style="margin:0; font-size:16px;">Change Password</h3>
            <button onclick="document.getElementById('change-password-modal').remove()" style="background:none; border:none; font-size:20px; cursor:pointer; color:var(--text-muted);">&times;</button>
          </div>
          <div class="modal-body" style="display:flex; flex-direction:column; gap:14px;">
            <div id="cp-error" style="display:none; background:rgba(251,113,133,0.12); border:1px solid rgba(251,113,133,0.35); color:#fb7185; border-radius:8px; padding:8px 12px; font-size:13px;"></div>
            <div>
              <label style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-muted); display:block; margin-bottom:4px;">Current Password</label>
              <input id="cp-current" type="password" class="form-input" style="width:100%;" />
            </div>
            <div>
              <label style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-muted); display:block; margin-bottom:4px;">New Password</label>
              <input id="cp-new" type="password" class="form-input" style="width:100%;" />
            </div>
            <div>
              <label style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-muted); display:block; margin-bottom:4px;">Confirm New Password</label>
              <input id="cp-confirm" type="password" class="form-input" style="width:100%;" />
            </div>
            <button class="btn btn-primary" onclick="submitChangePassword()">Update Password</button>
          </div>
        </div>`;
      document.body.appendChild(modal);
    } else {
      modal.style.display = 'flex';
    }
  }
  async function submitChangePassword() {
    const current = document.getElementById('cp-current').value;
    const newPw = document.getElementById('cp-new').value;
    const confirm = document.getElementById('cp-confirm').value;
    const errEl = document.getElementById('cp-error');
    errEl.style.display = 'none';
    if (!current || !newPw || !confirm) { errEl.textContent = 'All fields are required.'; errEl.style.display = 'block'; return; }
    if (newPw !== confirm) { errEl.textContent = 'New passwords do not match.'; errEl.style.display = 'block'; return; }
    if (newPw.length < 6) { errEl.textContent = 'Password must be at least 6 characters.'; errEl.style.display = 'block'; return; }
    const res = await apiCall('change_password', { current_password: current, new_password: newPw });
    if (res.success) {
      document.getElementById('change-password-modal').remove();
      alert('Password updated successfully.');
    } else {
      errEl.textContent = res.error || 'Failed to update password.';
      errEl.style.display = 'block';
    }
  }

  /* ── Image Upload ──────────────────────────────────────────────────────── */
  let _productImages = []; // array of { url: 'uploads/28/abc.jpg' }

  async function handleImages(e) {
    const files = Array.from(e.target.files);
    if (!files.length || !currentWorkbookId) return;
    const dbId = dbWorkbookMap[`${currentClient}|${currentWorkbookId}`] || currentWorkbookId;
    for (const file of files) {
      if (!file.type.startsWith('image/')) continue;
      const formData = new FormData();
      formData.append('image', file);
      formData.append('workbook_id', dbId);
      try {
        const res = await fetch('api.php?action=upload_image', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
          _productImages.push({ url: data.url });
          renderImageGallery();
          saveImageList();
        }
      } catch (err) { console.warn('Upload failed:', err); }
    }
    e.target.value = '';
  }

  async function removeGalleryImage(idx, e) {
    e.stopPropagation();
    const img = _productImages[idx];
    if (!img) return;
    // Delete file from server
    try { await apiCall('delete_image', { url: img.url }); } catch (err) { /* ignore */ }
    _productImages.splice(idx, 1);
    renderImageGallery();
    saveImageList();
  }

  function renderImageGallery() {
    const gallery = document.getElementById('imageGallery');
    const addBtn = gallery.querySelector('.image-add-btn');
    // Remove existing image items
    gallery.querySelectorAll('.image-gallery-item').forEach(el => el.remove());
    // Add image items before the add button
    _productImages.forEach((img, idx) => {
      const item = document.createElement('div');
      item.className = 'image-gallery-item';
      item.innerHTML = `
        <img src="${img.url}" alt="Product image ${idx+1}" onclick="openLightbox('${img.url}')" />
        <button class="img-remove" onclick="removeGalleryImage(${idx}, event)" title="Remove">✕</button>
      `;
      gallery.insertBefore(item, addBtn);
    });
  }

  function openLightbox(url) {
    document.getElementById('lightboxImg').src = url;
    document.getElementById('lightboxOverlay').classList.add('open');
  }

  function saveImageList() {
    if (!currentClient || !currentWorkbookId) return;
    const key = `${currentClient}|${currentWorkbookId}`;
    if (!workbookDetail[key]) workbookDetail[key] = {};
    workbookDetail[key].productImages = _productImages.map(i => i.url);
    // Also update legacy field
    workbookDetail[key].productImage = _productImages.length > 0 ? _productImages[0].url : '';
    const dbId = dbWorkbookMap[key] || currentWorkbookId;
    const detail = collectWorkbookDetail();
    // Ensure image data from _productImages is preserved in the detail sent to API
    detail.productImages = _productImages.map(i => i.url);
    detail.productImage = _productImages.length > 0 ? _productImages[0].url : '';
    apiCall('save_workbook_detail', { id: dbId, detail: detail });
    saveToLocalStorage();
  }

  const VIDEO_EXTS = new Set(['mp4','mov','webm','avi','mkv','m4v','qt']);
  function isVideoFile(f) {
    if (f.type.startsWith('video/')) return true;
    const ext = f.name.split('.').pop().toLowerCase();
    return VIDEO_EXTS.has(ext);
  }

  // Drag and drop support for image gallery — accepts both images and videos
  const galleryEl = document.getElementById('imageGallery');
  galleryEl.addEventListener('dragover', e => { e.preventDefault(); galleryEl.style.outline = '2px solid var(--accent)'; });
  galleryEl.addEventListener('dragleave', e => { if (!galleryEl.contains(e.relatedTarget)) galleryEl.style.outline = ''; });
  galleryEl.addEventListener('drop', e => {
    e.preventDefault();
    galleryEl.style.outline = '';
    const allFiles = Array.from(e.dataTransfer.files);
    const imageFiles = allFiles.filter(f => f.type.startsWith('image/'));
    const videoFiles = allFiles.filter(f => !f.type.startsWith('image/') && isVideoFile(f));
    if (imageFiles.length) handleImages({ target: { files: imageFiles } });
    if (videoFiles.length) handleVideoFiles(videoFiles);
  });

  // Clipboard paste — upload images when workbook is open
  document.addEventListener('paste', function(e) {
    if (!currentWorkbookId) return;
    const active = document.activeElement;
    // Don't intercept when user is typing in a text/textarea field
    if (active && (active.tagName === 'TEXTAREA' || (active.tagName === 'INPUT' && active.type !== 'file' && active.id !== 'videoUrlInput'))) return;
    const items = Array.from(e.clipboardData?.items || []);
    const imageItems = items.filter(item => item.type.startsWith('image/'));
    if (!imageItems.length) return;
    e.preventDefault();
    imageItems.forEach(item => {
      const file = item.getAsFile();
      if (file) handleImages({ target: { files: [file] } });
    });
  });

  /* ── Product Videos ─────────────────────────────────────────────────────── */
  let _productVideos = [];

  function getVideoInfo(url) {
    const ytMatch = url.match(/(?:youtube\.com\/(?:watch\?v=|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
    if (ytMatch) {
      const id = ytMatch[1];
      return { thumb: 'https://img.youtube.com/vi/' + id + '/mqdefault.jpg', embedUrl: 'https://www.youtube.com/embed/' + id + '?autoplay=1', type: 'iframe' };
    }
    const vimeoMatch = url.match(/vimeo\.com\/(\d+)/);
    if (vimeoMatch) {
      return { thumb: null, embedUrl: 'https://player.vimeo.com/video/' + vimeoMatch[1] + '?autoplay=1', type: 'iframe' };
    }
    if (/\.(mp4|webm|ogg|mov|m4v)(\?|$)/i.test(url) || url.startsWith('uploads/')) {
      return { thumb: null, embedUrl: url, type: 'video' };
    }
    return { thumb: null, embedUrl: url, type: 'link' };
  }

  function addProductVideo() {
    const input = document.getElementById('videoUrlInput');
    const url = input.value.trim();
    if (!url) return;
    _productVideos.push(url);
    input.value = '';
    renderVideoGallery();
    saveVideoList();
  }

  async function handleVideoFiles(files) {
    if (!files.length || !currentWorkbookId) return;
    const dbId = dbWorkbookMap[`${currentClient}|${currentWorkbookId}`] || currentWorkbookId;
    for (const file of files) {
      if (!isVideoFile(file)) continue;
      const formData = new FormData();
      formData.append('video', file);
      formData.append('workbook_id', dbId);
      try {
        const res = await fetch('api.php?action=upload_video', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
          _productVideos.push(data.url);
          renderVideoGallery();
          saveVideoList();
        } else {
          console.warn('Video upload failed:', data.error);
        }
      } catch (err) { console.warn('Video upload error:', err); }
    }
  }

  async function removeProductVideo(idx) {
    const url = _productVideos[idx];
    if (!url) return;
    // Delete server-uploaded files from the uploads directory
    if (url.startsWith('uploads/')) {
      try { await apiCall('delete_image', { url }); } catch (err) { /* ignore */ }
    }
    _productVideos.splice(idx, 1);
    renderVideoGallery();
    saveVideoList();
  }

  function renderVideoGallery() {
    const gallery = document.getElementById('videoGallery');
    gallery.querySelectorAll('.video-item').forEach(el => el.remove());
    _productVideos.forEach((url, idx) => {
      const info = getVideoInfo(url);
      const item = document.createElement('div');
      item.className = 'video-item';
      const label = url.startsWith('uploads/') ? url.split('/').pop() : url;
      const shortLabel = label.length > 55 ? label.slice(0, 52) + '…' : label;
      const thumbHtml = info.thumb
        ? `<img class="video-thumb" src="${info.thumb}" alt="Video" onclick="openVideoLightbox(${idx})" />`
        : `<div class="video-thumb-placeholder" onclick="openVideoLightbox(${idx})">▶</div>`;
      item.innerHTML = `
        ${thumbHtml}
        <span class="video-url-label" onclick="openVideoLightbox(${idx})" title="${url}">${shortLabel}</span>
        <button class="img-remove" onclick="removeProductVideo(${idx})" title="Remove">✕</button>
      `;
      gallery.appendChild(item);
    });
  }

  function openVideoLightbox(idx) {
    const url = _productVideos[idx];
    const info = getVideoInfo(url);
    const overlay = document.getElementById('videoLightboxOverlay');
    const iframe = document.getElementById('videoLightboxIframe');
    const video = document.getElementById('videoLightboxVideo');
    if (info.type === 'video') {
      iframe.src = '';
      iframe.style.display = 'none';
      video.src = url;
      video.style.display = '';
    } else if (info.type === 'iframe') {
      video.src = '';
      video.style.display = 'none';
      iframe.src = info.embedUrl;
      iframe.style.display = '';
    } else {
      window.open(url, '_blank');
      return;
    }
    overlay.classList.add('open');
  }

  function saveVideoList() {
    if (!currentClient || !currentWorkbookId) return;
    const key = `${currentClient}|${currentWorkbookId}`;
    if (!workbookDetail[key]) workbookDetail[key] = {};
    workbookDetail[key].productVideos = _productVideos.slice();
    const dbId = dbWorkbookMap[key] || currentWorkbookId;
    const detail = collectWorkbookDetail();
    detail.productVideos = _productVideos.slice();
    apiCall('save_workbook_detail', { id: dbId, detail: detail });
    saveToLocalStorage();
  }

  /* ── Product Category / Subcategory ────────────────────────────────────── */
  const SUBCATEGORIES = {
    packaging: [
      'Polyethylene (PE)', 'Polypropylene (PP)', 'Polyvinyl Chloride (PVC)',
      'Polyethylene Terephthalate (PET)', 'Polylactic Acid (PLA)',
      'Polybutylene Adipate Terephthalate (PBAT)', 'Polyamide (PA / Nylon)',
      'Ethylene Vinyl Acetate (EVA)', 'Corrugated Cardboard', 'Kraft Paper',
      'Rigid Paperboard', 'Glass', 'Aluminum', 'Tin / Tinplate', 'Wood'
    ],
    apparel: [
      'Cotton', 'Polyester', 'Nylon', 'Silk', 'Wool', 'Linen', 'Rayon',
      'Spandex / Elastane', 'Bamboo Fiber', 'Hemp', 'Leather', 'Faux Leather',
      'Denim', 'Fleece', 'Mesh'
    ],
    furniture: [
      'Solid Wood', 'Plywood', 'MDF', 'Particle Board', 'Bamboo',
      'Steel', 'Aluminum', 'Wrought Iron', 'Rattan / Wicker',
      'Acrylic', 'Tempered Glass', 'Marble', 'Granite', 'Upholstered Fabric'
    ],
    electronics: [
      'ABS Plastic', 'Polycarbonate (PC)', 'Aluminum Alloy', 'Stainless Steel',
      'Silicone', 'TPU', 'FR-4 (PCB)', 'Copper', 'Glass (Display)', 'Ceramic'
    ],
    promotional: [
      'ABS Plastic', 'Silicone', 'Stainless Steel', 'Cotton', 'Polyester',
      'Neoprene', 'PVC', 'Rubber', 'Cork', 'Bamboo', 'Recycled Material'
    ],
    'food-beverage': [
      'Food-Grade PP', 'Food-Grade PE', 'Food-Grade Silicone', 'Stainless Steel (304/316)',
      'Borosilicate Glass', 'Ceramic', 'Bamboo', 'Kraft Paper (FDA)', 'PLA (Compostable)',
      'Aluminum (Food-Safe)'
    ],
    toys: [
      'ABS Plastic', 'PP', 'Plush (Polyester)', 'Wood (Beech/Maple)',
      'Silicone (BPA-Free)', 'EVA Foam', 'Cotton', 'Nylon', 'Rubber'
    ],
    beauty: [
      'Glass (Cosmetic)', 'PP (Cosmetic Grade)', 'PET', 'Aluminum',
      'Acrylic (PMMA)', 'Bamboo', 'PCR Plastic', 'Silicone', 'Ceramic'
    ],
    'home-garden': [
      'Ceramic', 'Terracotta', 'Stainless Steel', 'Cast Iron', 'Bamboo',
      'Cotton', 'Linen', 'Jute', 'Rattan', 'Teak', 'Resin', 'Concrete'
    ],
    sports: [
      'Nylon', 'Polyester', 'Neoprene', 'Silicone', 'EVA Foam', 'TPE',
      'Carbon Fiber', 'Aluminum', 'Rubber', 'Mesh', 'Spandex'
    ],
    stationery: [
      'Paper (Offset)', 'Kraft Paper', 'Recycled Paper', 'PU Leather',
      'PP', 'ABS', 'Bamboo', 'Wood', 'Metal (Brass/Steel)', 'Cork'
    ]
  };

  function updateSubcategories() {
    const cat  = document.getElementById('product-category').value;
    const cat2 = document.getElementById('product-category-2').value;
    const subSel  = document.getElementById('product-subcategory');
    const subSel2 = document.getElementById('product-subcategory-2');

    // Primary material — driven by primary category
    const prev = subSel.value;
    subSel.innerHTML = '';
    if (!cat || !SUBCATEGORIES[cat]) {
      subSel.innerHTML = '<option value="">Select category first...</option>';
    } else {
      subSel.innerHTML = '<option value="">Select material type...</option>' +
        SUBCATEGORIES[cat].map(s => `<option value="${s}">${s}</option>`).join('');
      if (prev) subSel.value = prev; // restore if still valid
    }

    // Secondary material — union of primary + secondary category options
    const prev2 = subSel2.value;
    const cats = [cat, cat2].filter(c => c && SUBCATEGORIES[c]);
    const matOpts = cats.length
      ? [...new Set(cats.flatMap(c => SUBCATEGORIES[c]))]
      : Object.values(SUBCATEGORIES).flat();
    subSel2.innerHTML = '<option value="">None</option>' +
      matOpts.map(s => `<option value="${s}">${s}</option>`).join('');
    if (prev2) subSel2.value = prev2;
    document.getElementById('mat2-wrap').classList.toggle('has-value', !!subSel2.value);
    checkSecondaryLock();
  }

  function checkSecondaryLock() {
    const cat = document.getElementById('product-category').value;
    const mat = document.getElementById('product-subcategory').value;
    const ready = !!(cat && mat);

    const cat2Wrap = document.getElementById('cat2-wrap');
    const mat2Wrap = document.getElementById('mat2-wrap');
    const cat2Sel  = document.getElementById('product-category-2');
    const mat2Sel  = document.getElementById('product-subcategory-2');

    if (ready) {
      cat2Wrap.classList.add('unlocked');
      mat2Wrap.classList.add('unlocked');
      cat2Sel.disabled = false;
      mat2Sel.disabled = false;
    } else {
      cat2Wrap.classList.remove('unlocked', 'has-value');
      mat2Wrap.classList.remove('unlocked', 'has-value');
      cat2Sel.disabled = true;  cat2Sel.value = '';
      mat2Sel.disabled = true;  mat2Sel.value = '';
    }
  }

  function onSecondaryChange(wrapId, sel) {
    document.getElementById(wrapId).classList.toggle('has-value', !!sel.value);
    if (_appReady) autoSaveWorkbook();
  }

  /* ── Art Tab ─────────────────────────────────────────────────────────────── */
  let _artImages = [];
  let _clientLogo = null;

  async function handleClientLogoUpload(e) {
    const file = e.target.files[0];
    if (!file || !currentWorkbookId) return;
    const dbId = dbWorkbookMap[`${currentClient}|${currentWorkbookId}`] || currentWorkbookId;
    const formData = new FormData();
    formData.append('image', file);
    formData.append('workbook_id', dbId);
    const res = await fetch('api.php?action=upload_image', { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) {
      _clientLogo = data.url;
      renderClientLogo();
      saveClientLogo();
      rebuildSidebar();
    }
    e.target.value = '';
  }

  async function removeClientLogo() {
    if (!_clientLogo) return;
    try { await apiCall('delete_image', { url: _clientLogo }); } catch {}
    _clientLogo = null;
    renderClientLogo();
    saveClientLogo();
    rebuildSidebar();
  }

  function renderClientLogo() {
    const preview = document.getElementById('client-logo-preview');
    const placeholder = document.getElementById('client-logo-placeholder');
    const removeBtn = document.getElementById('client-logo-remove-btn');
    if (!preview) return;
    if (_clientLogo) {
      preview.style.border = '2px solid var(--border)';
      placeholder.style.display = 'none';
      let img = preview.querySelector('img');
      if (!img) { img = document.createElement('img'); preview.appendChild(img); }
      img.src = _clientLogo;
      img.style.cssText = 'width:100%; height:100%; object-fit:contain;';
      if (removeBtn) removeBtn.style.display = '';
    } else {
      preview.style.border = '2px dashed var(--border)';
      placeholder.style.display = '';
      const img = preview.querySelector('img');
      if (img) img.remove();
      if (removeBtn) removeBtn.style.display = 'none';
    }
  }

  function saveClientLogo() {
    const key = `${currentClient}|${currentWorkbookId}`;
    if (!workbookDetail[key]) workbookDetail[key] = {};
    workbookDetail[key].clientLogo = _clientLogo || '';
    const dbId = dbWorkbookMap[key] || currentWorkbookId;
    apiCall('save_workbook_detail', { id: dbId, detail: collectWorkbookDetail() });
  }

  async function handleArtFiles(e) {
    const files = Array.from(e.target.files);
    if (!files.length || !currentWorkbookId) return;
    const dbId = dbWorkbookMap[`${currentClient}|${currentWorkbookId}`] || currentWorkbookId;
    for (const file of files) {
      if (!file.type.startsWith('image/')) continue;
      const formData = new FormData();
      formData.append('image', file);
      formData.append('workbook_id', dbId);
      try {
        const res = await fetch('api.php?action=upload_image', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
          _artImages.push({ url: data.url });
          renderArtGallery();
          saveArtList();
        }
      } catch (err) { console.warn('Art upload failed:', err); }
    }
    e.target.value = '';
  }

  async function removeArtImage(idx, e) {
    e.stopPropagation();
    const img = _artImages[idx];
    if (!img) return;
    try { await apiCall('delete_image', { url: img.url }); } catch (err) {}
    _artImages.splice(idx, 1);
    renderArtGallery();
    saveArtList();
  }

  function renderArtGallery() {
    const gallery = document.getElementById('artGallery');
    const addBtn = gallery.querySelector('.image-add-btn');
    gallery.querySelectorAll('.image-gallery-item').forEach(el => el.remove());
    _artImages.forEach((img, idx) => {
      const item = document.createElement('div');
      item.className = 'image-gallery-item';
      item.innerHTML = `
        <img src="${img.url}" alt="Art file ${idx+1}" onclick="openLightbox('${img.url}')" />
        <button class="img-remove" onclick="removeArtImage(${idx}, event)" title="Remove">✕</button>
      `;
      gallery.insertBefore(item, addBtn);
    });
  }

  function saveArtList() {
    if (!currentClient || !currentWorkbookId) return;
    const key = `${currentClient}|${currentWorkbookId}`;
    if (!workbookDetail[key]) workbookDetail[key] = {};
    workbookDetail[key].artImages = _artImages.map(i => i.url);
    const dbId = dbWorkbookMap[key] || currentWorkbookId;
    const detail = collectWorkbookDetail();
    apiCall('save_workbook_detail', { id: dbId, detail: detail });
    saveToLocalStorage();
  }

  /* ── Dimension Conversion ──────────────────────────────────────────────── */
  const IN_TO_MM = 25.4;
  let converting = false;

  function convertToMm(source) {
    if (converting) return;
    converting = true;
    ['l','w','h'].forEach(axis => {
      const inVal = parseFloat(document.getElementById(`dim-in-${axis}`).value);
      const mmEl  = document.getElementById(`dim-mm-${axis}`);
      mmEl.value  = isNaN(inVal) ? '' : (inVal * IN_TO_MM).toFixed(1);
    });
    converting = false;
  }

  function convertToIn(source) {
    if (converting) return;
    converting = true;
    ['l','w','h'].forEach(axis => {
      const mmVal = parseFloat(document.getElementById(`dim-mm-${axis}`).value);
      const inEl  = document.getElementById(`dim-in-${axis}`);
      inEl.value  = isNaN(mmVal) ? '' : (mmVal / IN_TO_MM).toFixed(3);
    });
    converting = false;
  }

  /* ── Weight Conversion (kg ↔ lbs) ─────────────────────────────────────── */
  const KG_TO_LBS = 2.20462;
  let convertingWeight = false;

  /* ── Carton Auto-Calculation ──────────────────────────────────────────── */
  function bestCartonDims(pL, pW, pH, qty, padding) {
    // Fill base layer first (side by side), then stack up
    // Primary score: minimize nz (layers stacked in height)
    // Secondary score: make base footprint (L×W) as square as possible
    // Returns { L, W, H, nx, ny, nz } where nx*ny*nz = qty
    let best = null, bestScore = Infinity;
    for (let a = 1; a <= qty; a++) {
      if (qty % a) continue;
      for (let b = a; b <= qty / a; b++) {
        if ((qty / a) % b) continue;
        const c = qty / a / b;
        [[a,b,c],[a,c,b],[b,a,c],[b,c,a],[c,a,b],[c,b,a]].forEach(([x,y,z]) => {
          const L = x * pL + padding, W = y * pW + padding, H = z * pH + padding;
          // z = items stacked in height — minimize this first, then square up the base
          const score = z * 1e8 + (L - W) * (L - W);
          if (score < bestScore) { bestScore = score; best = { L, W, H, nx: x, ny: y, nz: z }; }
        });
      }
    }
    return best;
  }

  function setCartonDimFields(prefix, L, W, H) {
    const r = v => Math.round(v * 100) / 100;
    const set = (id, val) => { const el = document.getElementById(id); if (el) el.value = r(val); };
    set(prefix + '-l-cm', L); convertDim(prefix + '-l-cm', prefix + '-l-in', 'cm');
    set(prefix + '-w-cm', W); convertDim(prefix + '-w-cm', prefix + '-w-in', 'cm');
    set(prefix + '-h-cm', H); convertDim(prefix + '-h-cm', prefix + '-h-in', 'cm');
  }

  function autoCalcCartons() {
    const pL = parseFloat(document.getElementById('dim-cm-l').value);
    const pW = parseFloat(document.getElementById('dim-cm-w').value);
    const pH = parseFloat(document.getElementById('dim-cm-h').value);
    const innerQty = parseInt(document.getElementById('carton-inner-count').value);  // products per inner carton
    const outerQty = parseInt(document.getElementById('carton-outer-count').value);  // inner cartons per outer carton
    const PADDING = 2; // 2cm carton wall allowance

    if (!pL || !pW || !pH) return;

    let innerDims = null;

    const productWeightKg = parseFloat(document.getElementById('dim-weight-kg').value);

    const setHint = (id, nx, ny, nz, itemWord) => {
      const el = document.getElementById(id);
      if (!el) return;
      el.style.display = '';
      const layerNote = nz > 1 ? ` × ${nz} layers` : ' (single layer)';
      el.textContent = `${nx} × ${ny}${layerNote}  =  ${nx * ny * nz} ${itemWord}`;
    };

    // ── Inner carton: sized to hold innerQty products ──
    if (innerQty >= 1) {
      innerDims = bestCartonDims(pL, pW, pH, innerQty, PADDING);
      setCartonDimFields('carton-inner', innerDims.L, innerDims.W, innerDims.H);
      setHint('inner-arrange-hint', innerDims.nx, innerDims.ny, innerDims.nz, innerQty === 1 ? 'unit' : 'units');
      if (!isNaN(productWeightKg) && productWeightKg > 0) {
        document.getElementById('carton-inner-weight').value = (productWeightKg * innerQty).toFixed(2);
        convertWeight('carton-inner-weight', 'carton-inner-weight-lbs', 'kg');
      }
      const badge = document.getElementById('inner-calc-badge');
      if (badge) badge.style.display = '';
    } else {
      const h = document.getElementById('inner-arrange-hint');
      if (h) h.style.display = 'none';
    }

    // ── Outer carton: sized to hold outerQty inner cartons ──
    // outerQty = number of INNER CARTONS per outer carton (not products)
    if (outerQty >= 1) {
      let outerDims;
      if (innerDims) {
        outerDims = bestCartonDims(innerDims.L, innerDims.W, innerDims.H, outerQty, PADDING);
        setHint('outer-arrange-hint', outerDims.nx, outerDims.ny, outerDims.nz, outerQty === 1 ? 'inner' : 'inners');
      } else {
        outerDims = bestCartonDims(pL, pW, pH, outerQty, PADDING);
        setHint('outer-arrange-hint', outerDims.nx, outerDims.ny, outerDims.nz, outerQty === 1 ? 'unit' : 'units');
      }
      setCartonDimFields('carton-outer', outerDims.L, outerDims.W, outerDims.H);
      if (!isNaN(productWeightKg) && productWeightKg > 0) {
        const productsPerOuter = innerDims && innerQty >= 1 ? innerQty * outerQty : outerQty;
        document.getElementById('carton-outer-weight').value = (productWeightKg * productsPerOuter).toFixed(2);
        convertWeight('carton-outer-weight', 'carton-outer-weight-lbs', 'kg');
      }
      const badge = document.getElementById('outer-calc-badge');
      if (badge) badge.style.display = '';
    } else {
      const h = document.getElementById('outer-arrange-hint');
      if (h) h.style.display = 'none';
    }
    renderPalletViz();
    updateOuterWeightHint();
  }

  function updateOuterWeightHint() {
    const hint = document.getElementById('outer-weight-hint');
    if (!hint) return;
    const innerKg  = parseFloat(document.getElementById('carton-inner-weight')?.value) || 0;
    const outerQty = parseInt(document.getElementById('carton-outer-count')?.value) || 0;
    if (!innerKg || !outerQty) { hint.style.display = 'none'; return; }
    const totalKg  = innerKg * outerQty;
    const totalLbs = totalKg * 2.20462;
    const fmtLbs = val => {
      const lbs = Math.floor(val);
      const oz  = Math.round((val - lbs) * 16);
      return oz > 0 ? `${lbs} lbs ${oz} oz` : `${lbs} lbs`;
    };
    hint.style.display = '';
    hint.innerHTML = `<strong>Est. total outer weight:</strong><br>${totalKg.toFixed(2)} kg &nbsp;/&nbsp; ${fmtLbs(totalLbs)}<br><span style="opacity:0.75;">${outerQty} inner carton${outerQty > 1 ? 's' : ''} × ${innerKg.toFixed(2)} kg each</span>`;
    // Always sync outer weight from inner weight × inner cartons per outer
    const outerWtEl = document.getElementById('carton-outer-weight');
    if (outerWtEl) {
      outerWtEl.value = totalKg.toFixed(2);
      convertWeight('carton-outer-weight', 'carton-outer-weight-lbs', 'kg');
      calcFreight();
    }
  }

  /* ── Pallet Visualization ─────────────────────────────────────────────── */
  const PALLET_L = 101.6;    // cm (40 in)
  const PALLET_W = 121.92;   // cm (48 in)
  const PALLET_DECK = 15;    // cm pallet deck height
  const MAX_LOAD_H_DEFAULT = 152.4; // cm default max stack height (60 in)
  const ISO_COS30 = Math.cos(Math.PI / 6); // 0.866
  const ISO_SIN30 = 0.5;

  // Camera from above: +x goes right-down, +z goes left-down, +y goes up
  function isoProj(wx, wy, wz, s, ox, oy) {
    return {
      x: ox + (wx - wz) * ISO_COS30 * s,
      y: oy + (wx + wz) * ISO_SIN30 * s - wy * s
    };
  }

  function isoFace(ctx, pts, fill, stroke) {
    ctx.beginPath();
    ctx.moveTo(pts[0].x, pts[0].y);
    for (let i = 1; i < pts.length; i++) ctx.lineTo(pts[i].x, pts[i].y);
    ctx.closePath();
    ctx.fillStyle = fill;
    ctx.fill();
    ctx.strokeStyle = stroke || 'rgba(0,0,0,0.13)';
    ctx.lineWidth = 0.6;
    ctx.stroke();
  }

  function shadeRgb(hex, amt) {
    const n = parseInt(hex.replace('#',''), 16);
    const r = Math.min(255, Math.max(0, (n>>16) + amt));
    const g = Math.min(255, Math.max(0, ((n>>8)&0xff) + amt));
    const b = Math.min(255, Math.max(0, (n&0xff) + amt));
    return `rgb(${r},${g},${b})`;
  }

  function drawIsoBox(ctx, wx, wy, wz, bw, bh, bd, s, ox, oy, hex) {
    const p = (x,y,z) => isoProj(x,y,z,s,ox,oy);
    // Top face (+y) — brightest
    isoFace(ctx, [p(wx,wy+bh,wz), p(wx+bw,wy+bh,wz), p(wx+bw,wy+bh,wz+bd), p(wx,wy+bh,wz+bd)], shadeRgb(hex, 25));
    // Right face (+x at wx+bw) — medium
    isoFace(ctx, [p(wx+bw,wy,wz), p(wx+bw,wy,wz+bd), p(wx+bw,wy+bh,wz+bd), p(wx+bw,wy+bh,wz)], hex);
    // Front face (+z at wz+bd) — darkest
    isoFace(ctx, [p(wx,wy,wz+bd), p(wx+bw,wy,wz+bd), p(wx+bw,wy+bh,wz+bd), p(wx,wy+bh,wz+bd)], shadeRgb(hex, -40));
  }

  function bestPalletOrientation(bL, bW) {
    const o1 = { cols: Math.floor(PALLET_L/bL), rows: Math.floor(PALLET_W/bW), bL, bW };
    const o2 = { cols: Math.floor(PALLET_L/bW), rows: Math.floor(PALLET_W/bL), bL: bW, bW: bL };
    return (o1.cols * o1.rows >= o2.cols * o2.rows) ? o1 : o2;
  }

  function renderPalletViz() {
    const canvas = document.getElementById('pallet-canvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const CW = canvas.width, CH = canvas.height;
    ctx.clearRect(0, 0, CW, CH);

    const bL = parseFloat(document.getElementById('carton-outer-l-cm').value);
    const bW = parseFloat(document.getElementById('carton-outer-w-cm').value);
    const bH = parseFloat(document.getElementById('carton-outer-h-cm').value);

    if (!bL || !bW || !bH) {
      ctx.fillStyle = '#aaa';
      ctx.font = '13px -apple-system, BlinkMacSystemFont, sans-serif';
      ctx.textAlign = 'center';
      ctx.fillText('Enter outer carton dimensions', CW/2, CH/2 - 8);
      ctx.fillText('to see pallet visualization', CW/2, CH/2 + 10);
      document.getElementById('pallet-stats').innerHTML = '<span style="color:var(--text-muted); font-size:13px;">Enter outer carton dimensions to calculate.</span>';
      const inlineEl = document.getElementById('pallet-inline-stats');
      if (inlineEl) { inlineEl.style.display = 'none'; inlineEl.textContent = ''; }
      return;
    }

    const layout = bestPalletOrientation(bL, bW);
    const perLayer = layout.cols * layout.rows;

    // Carton too large to fit on pallet — show warning instead of empty view
    if (perLayer === 0) {
      ctx.fillStyle = '#aaa';
      ctx.font = '13px -apple-system, BlinkMacSystemFont, sans-serif';
      ctx.textAlign = 'center';
      ctx.fillText('Outer carton is too large for a 40 × 48 pallet.', CW/2, CH/2 - 10);
      ctx.font = '11px -apple-system, BlinkMacSystemFont, sans-serif';
      ctx.fillText(`Carton: ${bL.toFixed(1)} × ${bW.toFixed(1)} cm — Pallet: ${PALLET_L} × ${PALLET_W} cm`, CW/2, CH/2 + 10);
      ctx.fillText('Try reducing the number of inner cartons per outer.', CW/2, CH/2 + 28);
      document.getElementById('pallet-stats').innerHTML = `
        <div style="color:#f59e0b; font-size:13px; font-weight:600; margin-bottom:8px;">⚠ Outer carton exceeds pallet dimensions</div>
        <div style="font-size:12px; color:var(--text-muted); line-height:1.6;">
          The outer carton footprint (${bL.toFixed(1)} × ${bW.toFixed(1)} cm) is larger than the 40 × 48 in pallet (101.6 × 121.9 cm).<br><br>
          Try reducing the <strong>inner cartons / outer</strong> qty so the outer carton fits on the pallet.
        </div>`;
      const inlineEl = document.getElementById('pallet-inline-stats');
      if (inlineEl) { inlineEl.style.display = ''; inlineEl.innerHTML = '<span style="color:#f59e0b;">⚠ Outer carton too large for pallet — reduce inner cartons / outer</span>'; }
      return;
    }

    const maxLoadHIn = parseFloat(document.getElementById('pallet-max-height').value) || 60;
    const maxLoadH = maxLoadHIn * 2.54; // convert inches to cm
    const maxLayers = Math.max(1, Math.floor(maxLoadH / bH));
    const showLayers = Math.min(maxLayers, 12); // cap visual layers

    // Scale to fit canvas — origin near top, stack grows upward
    const footprintSpan = PALLET_L + PALLET_W;
    const stackH = PALLET_DECK + showLayers * bH;
    const s = Math.min(
      (CW * 0.82) / (footprintSpan * ISO_COS30),
      (CH * 0.88) / (stackH + footprintSpan * ISO_SIN30)
    );
    const ox = CW / 2 + (PALLET_W - PALLET_L) * ISO_COS30 * s / 2;
    const oy = stackH * s + 10; // near top of canvas

    // Draw pallet deck
    drawIsoBox(ctx, 0, 0, 0, PALLET_L, PALLET_DECK, PALLET_W, s, ox, oy, '#a8a8a8');

    // Draw boxes back-to-front: diag 0 (back corner) → max (front corner)
    for (let layer = 0; layer < showLayers; layer++) {
      for (let diag = 0; diag <= layout.cols + layout.rows - 2; diag++) {
        for (let col = Math.max(0, diag - layout.rows + 1); col <= Math.min(diag, layout.cols - 1); col++) {
          const row = diag - col;
          drawIsoBox(ctx,
            col * layout.bL, PALLET_DECK + layer * bH, row * layout.bW,
            layout.bL, bH, layout.bW, s, ox, oy, '#E8751A');
        }
      }
    }

    // Pallet label
    ctx.fillStyle = '#888';
    ctx.font = `${Math.round(s * 8)}px -apple-system, sans-serif`;
    ctx.textAlign = 'center';
    const labelPt = isoProj(PALLET_L/2, 0, PALLET_W + 4, s, ox, oy);
    ctx.fillText('40 × 48 in', labelPt.x, labelPt.y + 4);

    // Stats
    const surfaceUse = Math.round((layout.cols * layout.bL * layout.rows * layout.bW) / (PALLET_L * PALLET_W) * 100);
    const totalPerPallet = perLayer * maxLayers;
    const totalCartons = parseInt(document.getElementById('pallet-total-cartons').value) || 0;
    const palletsNeeded = totalCartons > 0 ? Math.ceil(totalCartons / totalPerPallet) : null;

    // Derived product counts
    const innerQtyVal  = parseInt(document.getElementById('carton-inner-count').value) || 0; // products per inner
    const outerQtyVal  = parseInt(document.getElementById('carton-outer-count').value) || 0; // inner cartons per outer
    const productsPerOuter = (innerQtyVal > 0 && outerQtyVal > 0) ? innerQtyVal * outerQtyVal : 0;
    const totalInners  = (totalCartons > 0 && outerQtyVal > 0) ? totalCartons * outerQtyVal : 0;
    const totalProducts = (totalCartons > 0 && productsPerOuter > 0) ? totalCartons * productsPerOuter : 0;

    document.getElementById('pallet-stats').innerHTML = `
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
        <div><div style="font-size:22px; font-weight:700; color:var(--text);">${perLayer}</div><div style="font-size:11px; color:var(--text-muted); margin-top:2px;">outer cartons / layer</div></div>
        <div><div style="font-size:22px; font-weight:700; color:var(--text);">${maxLayers}</div><div style="font-size:11px; color:var(--text-muted); margin-top:2px;">max layers</div></div>
        <div><div style="font-size:22px; font-weight:700; color:var(--text);">${totalPerPallet}</div><div style="font-size:11px; color:var(--text-muted); margin-top:2px;">outer cartons / pallet</div></div>
        <div><div style="font-size:22px; font-weight:700; color:var(--text);">${surfaceUse}%</div><div style="font-size:11px; color:var(--text-muted); margin-top:2px;">surface coverage</div></div>
        ${productsPerOuter > 0 ? `
        <div><div style="font-size:22px; font-weight:700; color:var(--text);">${outerQtyVal}</div><div style="font-size:11px; color:var(--text-muted); margin-top:2px;">inner cartons / outer</div></div>
        <div><div style="font-size:22px; font-weight:700; color:var(--text);">${productsPerOuter}</div><div style="font-size:11px; color:var(--text-muted); margin-top:2px;">products / outer carton</div></div>` : ''}
      </div>
      ${totalCartons > 0 ? `
      <div style="margin-top:16px; padding-top:14px; border-top:1px solid var(--border);">
        <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); margin-bottom:10px;">Shipment of ${totalCartons} outer cartons</div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
          <div><div style="font-size:22px; font-weight:700; color:var(--accent);">${palletsNeeded}</div><div style="font-size:11px; color:var(--text-muted); margin-top:2px;">pallets needed</div></div>
          ${totalInners > 0 ? `<div><div style="font-size:22px; font-weight:700; color:var(--text);">${totalInners}</div><div style="font-size:11px; color:var(--text-muted); margin-top:2px;">total inner cartons</div></div>` : '<div></div>'}
          ${totalProducts > 0 ? `<div style="grid-column:span 2;"><div style="font-size:22px; font-weight:700; color:var(--text);">${totalProducts.toLocaleString()}</div><div style="font-size:11px; color:var(--text-muted); margin-top:2px;">total products</div></div>` : ''}
        </div>
      </div>` : ''}
      <div style="margin-top:14px; font-size:11px; color:var(--text-muted); padding-top:12px; border-top:1px solid var(--border);">
        Box orientation: ${layout.bL.toFixed(1)} × ${layout.bW.toFixed(1)} cm &nbsp;·&nbsp; ${layout.cols} × ${layout.rows} per layer
      </div>`;

    // Store pallet stats globally so shipping tab can read them
    window._palletStats = { perLayer, maxLayers, totalPerPallet, surfaceUse, outerQtyVal, productsPerOuter, totalCartons, palletsNeeded, totalInners, totalProducts };

    // Inline summary in the Outer Carton column header
    const inlineEl = document.getElementById('pallet-inline-stats');
    if (inlineEl) {
      inlineEl.style.display = '';
      const palletPart = palletsNeeded !== null ? ` &nbsp;·&nbsp; <strong style="color:var(--accent);">${palletsNeeded} pallets</strong>` : '';
      inlineEl.innerHTML = `<span style="opacity:0.75;">Pallet:</span> <strong>${perLayer}</strong> / layer &nbsp;·&nbsp; <strong>${totalPerPallet}</strong> / pallet${palletPart}`;
    }

    // Refresh shipping tab pallet stats if visible
    syncShippingPalletStats();
  }

  let convertingDim = false;
  function convertDim(sourceId, targetId, sourceUnit) {
    if (convertingDim) return;
    convertingDim = true;
    const val = parseFloat(document.getElementById(sourceId).value);
    const targetEl = document.getElementById(targetId);
    if (isNaN(val)) {
      targetEl.value = '';
    } else if (sourceUnit === 'in') {
      targetEl.value = (val * 2.54).toFixed(2);
    } else {
      targetEl.value = (val / 2.54).toFixed(2);
    }
    convertingDim = false;
    if (_appReady) autoSaveWorkbook();
  }

  function convertWeight(sourceId, targetId, sourceUnit) {
    if (convertingWeight) return;
    convertingWeight = true;
    const sourceEl = document.getElementById(sourceId);
    const targetEl = document.getElementById(targetId);
    if (sourceUnit === 'kg') {
      const val = parseFloat(sourceEl.value);
      if (isNaN(val)) {
        targetEl.value = '';
      } else {
        const totalLbs = val * KG_TO_LBS;
        const wholeLbs = Math.floor(totalLbs);
        const oz = Math.round((totalLbs - wholeLbs) * 16 * 10) / 10;
        targetEl.value = wholeLbs + ' lbs ' + oz.toFixed(1) + ' oz';
      }
    } else {
      const raw = sourceEl.value.trim();
      const parsed = raw.match(/^(\d+(?:\.\d+)?)\s*lbs?\s*(\d+(?:\.\d+)?)\s*oz/i);
      let totalLbs;
      if (parsed) {
        totalLbs = parseFloat(parsed[1]) + parseFloat(parsed[2]) / 16;
      } else {
        totalLbs = parseFloat(raw);
      }
      if (isNaN(totalLbs)) {
        targetEl.value = '';
      } else {
        targetEl.value = (totalLbs / KG_TO_LBS).toFixed(2);
      }
    }
    convertingWeight = false;
  }

  /* ── Pantone Swatch ────────────────────────────────────────────────────── */
  function syncPantone() {
    // swatch is just a helper; text field is the source of truth
  }

  /* ── RMB ↔ USD Live Exchange Rate ──────────────────────────────────────── */
  let USD_TO_RMB = 7.24; // fallback — replaced immediately by cache or live fetch
  const RATE_TTL = 30 * 60 * 1000; // 30 minutes in ms

  function applyNewRate(rate, source) {
    USD_TO_RMB = rate;
    // Recalc all open RFQ rows
    document.querySelectorAll('#rfq-body tr[id^="rfq-"]').forEach(tr => {
      const rowId = parseInt(tr.id.replace('rfq-', ''));
      if (!isNaN(rowId)) recalcRfqRow(rowId);
    });
    document.querySelectorAll('[id^="rfq-variant-"]').forEach(tr => {
      const rowId = parseInt(tr.id.replace('rfq-variant-', ''));
      if (!isNaN(rowId)) recalcRfqVariantRow(rowId);
    });
    // Update rate indicator in header  (shows: 1 ¥ = $X.XXXX)
    const el = document.getElementById('fx-rate-display');
    if (el) {
      const now = new Date();
      const time = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
      const cnyToUsd = (1 / rate).toFixed(4);
      el.textContent = `1 ¥ = $${cnyToUsd}`;
      el.title = `CNY → USD rate: $${cnyToUsd} per Yuan (source: ${source}, updated ${time})`;
    }
  }

  // ── Header currency calculator ───────────────────────────────────────────
  // Up to 4 decimals so small RMB→USD conversions stay precise (¥1 = $0.1462,
  // not $0.15). Big round numbers like $1 = ¥7.24 still render cleanly because
  // we let the trailing zeros drop (min 2, max 4).
  const _fmtCalc  = n => n.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 4});
  const _parseCalc = s => parseFloat(String(s).replace(/,/g, '')) || 0;

  // Permanently block letters / anything non-numeric from the calc inputs.
  // Covers browser autofill (e.g. "Parker" getting dropped into the field),
  // paste of mixed text, and accidental keystrokes. Keeps digits, a single
  // dot, and commas (commas are re-emitted by the live formatter).
  function _hdrSanitize(el) {
    const before = el.value;
    // Strip everything that isn't a digit, dot, or comma
    let cleaned = before.replace(/[^0-9.,]/g, '');
    // Collapse multiple dots down to the first one (keeps "1.2.3" from sticking)
    const firstDot = cleaned.indexOf('.');
    if (firstDot !== -1) {
      cleaned = cleaned.slice(0, firstDot + 1) + cleaned.slice(firstDot + 1).replace(/\./g, '');
    }
    if (cleaned !== before) {
      // Preserve caret position relative to the number of characters removed
      const cursor = el.selectionStart ?? cleaned.length;
      const removed = before.length - cleaned.length;
      el.value = cleaned;
      const newCursor = Math.max(0, cursor - removed);
      try { el.setSelectionRange(newCursor, newCursor); } catch (e) { /* input may not support selection */ }
    }
  }

  // Format the active input with commas as the user types, preserving cursor position.
  function _hdrLiveFormat(el) {
    _hdrSanitize(el); // scrub letters FIRST (defeats autofill + paste)
    const cursor = el.selectionStart;
    const old    = el.value;
    const raw    = old.replace(/,/g, '');
    if (!raw || raw === '.') return;
    const dot = raw.indexOf('.');
    let fmt;
    if (dot === -1) {
      const n = parseInt(raw, 10);
      if (isNaN(n)) return;
      fmt = n.toLocaleString('en-US');
    } else {
      const n = parseInt(raw.slice(0, dot) || '0', 10);
      if (isNaN(n)) return;
      fmt = n.toLocaleString('en-US') + '.' + raw.slice(dot + 1);
    }
    el.value = fmt;
    const newCursor = Math.max(0, cursor + (fmt.length - old.length));
    el.setSelectionRange(newCursor, newCursor);
  }

  function hdrCalcRmbToUsd() {
    const rmbEl = document.getElementById('hdr-calc-rmb');
    _hdrLiveFormat(rmbEl);
    const rmb   = _parseCalc(rmbEl.value);
    const usdEl = document.getElementById('hdr-calc-usd');
    usdEl.value = rmb > 0 ? _fmtCalc(rmb / USD_TO_RMB) : '';
  }
  function hdrCalcUsdToRmb() {
    const usdEl = document.getElementById('hdr-calc-usd');
    _hdrLiveFormat(usdEl);
    const usd   = _parseCalc(usdEl.value);
    const rmbEl = document.getElementById('hdr-calc-rmb');
    rmbEl.value = usd > 0 ? _fmtCalc(usd * USD_TO_RMB) : '';
  }

  async function fetchLiveRate() {
    // Check localStorage cache first (30-min TTL)
    try {
      const cached = JSON.parse(localStorage.getItem('fx_usd_cny') || 'null');
      if (cached && cached.rate && (Date.now() - cached.ts) < RATE_TTL) {
        applyNewRate(cached.rate, 'cached');
        return;
      }
    } catch(e) {}

    // Cache stale or missing — call PHP proxy (tries XE.com, falls back to open.er-api.com)
    try {
      const data = await apiCall('get_fx_rate');
      if (data && data.rate) {
        localStorage.setItem('fx_usd_cny', JSON.stringify({ rate: data.rate, ts: Date.now() }));
        applyNewRate(data.rate, data.source || 'live');
        return;
      }
    } catch(e) {}

    // Last-resort: fetch open.er-api.com directly from browser
    try {
      const res = await fetch('https://open.er-api.com/v6/latest/USD');
      const data = await res.json();
      if (data.result === 'success' && data.rates && data.rates.CNY) {
        const rate = data.rates.CNY;
        localStorage.setItem('fx_usd_cny', JSON.stringify({ rate, ts: Date.now() }));
        applyNewRate(rate, 'open.er-api');
      }
    } catch(e) {
      console.warn('Exchange rate fetch failed, using fallback:', USD_TO_RMB);
    }
  }

  // Initial fetch + refresh every 30 minutes
  fetchLiveRate();
  setInterval(fetchLiveRate, RATE_TTL);

  // Guard against autofill that fires BEFORE any user interaction — Chrome
  // sometimes silently fills the calc with the logged-in user's name. We run
  // a delayed scrub after first paint so any ghost-fill gets wiped.
  function _scrubFxCalc() {
    ['hdr-calc-rmb', 'hdr-calc-usd'].forEach(id => {
      const el = document.getElementById(id);
      if (el && el.value) _hdrSanitize(el);
    });
  }
  // Multiple scrub passes catch early/late autofill races (Chrome Autofill,
  // 1Password/LastPass extensions). Cheap to do; no visible cost.
  setTimeout(_scrubFxCalc, 50);
  setTimeout(_scrubFxCalc, 500);
  setTimeout(_scrubFxCalc, 2000);

  function convertRmbToUsd() {
    const rmb = parseFloat(document.getElementById('quote-unit-rmb').value);
    const usdEl = document.getElementById('quote-unit');
    usdEl.value = isNaN(rmb) ? '' : (rmb / USD_TO_RMB).toFixed(2);
  }

  /* ── RFQ Line Items ─────────────────────────────────────────────────── */
  let rfqCount = 0;
  let _rfqVarCount = 0;
  let _wbLocked = false;
  let _draggingVarId = null; // tracks which variant row is being dragged

  function addRfqRow(item = '', sku = '', qty = '', priceRmb = '', leadTime = '', sample = false, variants = []) {
    rfqCount++;
    const id = rfqCount;
    const tbody = document.getElementById('rfq-body');
    const isFirstRow = tbody.querySelectorAll('tr:not([data-rfq-parent]):not([data-rfq-add-for])').length === 0;
    const defaultItem = item;
    const tr = document.createElement('tr');
    tr.id = `rfq-${id}`;
    tr.ondragover = function(e) { e.preventDefault(); tr.style.borderTop='2px solid var(--accent)'; };
    tr.ondragleave = function() { tr.style.borderTop=''; };
    tr.ondrop = function(e) { e.preventDefault(); tr.style.borderTop=''; rfqDropRow(e, id); };
    const usdVal = priceRmb ? (parseFloat(priceRmb) / USD_TO_RMB).toFixed(2) : '';
    const totalVal = (qty && usdVal) ? (parseFloat(qty) * parseFloat(usdVal)).toFixed(2) : '';
    const inputStyle = 'width:100%; border:1px solid var(--border); border-radius:8px; padding:10px 14px; font-size:13px; box-sizing:border-box;';
    if (isFirstRow) {
      tr.style.background = 'rgba(232, 117, 26, 0.08)';
      tr.ondragover = null;
      tr.ondragleave = null;
      tr.ondrop = null;
    }
    if (sample) {
      tr.classList.add('rfq-sample-row');
    }
    const handleAttr = isFirstRow ? '' : `draggable="true" onmousedown="this.closest('tr').draggable=true" onmouseup="this.closest('tr').draggable=false" ondragstart="event.dataTransfer.setData('text/plain','${id}'); this.closest('tr').style.opacity='0.4'" ondragend="this.closest('tr').style.opacity='1'; this.closest('tr').draggable=false"`;
    tr.innerHTML = `
      <td class="tier-col-num" style="color:var(--text-muted); font-weight:600; text-align:center;${isFirstRow ? '' : ' cursor:grab;'}" ${isFirstRow ? '' : 'title="Drag to reorder"'} ${handleAttr}>${isFirstRow ? id : '☰ ' + id}</td>
      <td style="text-align:center; padding:24px 8px 4px;"><label class="rfq-sample-label" title="Mark as sample request"><input type="checkbox" class="rfq-sample-check" ${sample ? 'checked' : ''} onchange="toggleRfqSample(this)" /></label></td>
      <td><input type="text" placeholder="SKU" value="${sku}" title="${sku}" oninput="this.title=this.value; recalcRfqTotals()" style="${inputStyle}" /></td>
      <td>
        <input type="text" placeholder="Enter Item" value="${defaultItem}" oninput="recalcRfqTotals()" style="${inputStyle}" />
      </td>
      <td><input type="text" inputmode="numeric" placeholder="0" value="${qty}" oninput="recalcRfqRow(${id})" style="${inputStyle}" /></td>
      <td><div class="currency-prefix currency-rmb" style="position:relative;"><input type="text" inputmode="decimal" placeholder="0.00" value="${priceRmb}" oninput="recalcRfqRow(${id})" style="${inputStyle} padding-left:28px;" /></div></td>
      <td class="tier-col-usd" id="rfq-usd-${id}" style="color:var(--text); font-size:13px; text-align:right; font-weight:600;">${usdVal ? '$' + parseFloat(usdVal).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : '—'}</td>
      <td class="total-cell" id="rfq-total-${id}" style="text-align:right;">${totalVal ? '$' + parseFloat(totalVal).toLocaleString('en-US', {minimumFractionDigits:2}) : '—'}</td>
      <td><div class="lead-time-suffix" style="position:relative;"><input type="text" placeholder="0" value="${leadTime}" oninput="recalcRfqTotals()" style="${inputStyle} padding-right:40px;" /></div></td>
      <td style="white-space:nowrap;">
        ${isFirstRow ? '' : '<span class="remove-tier" onclick="removeRfqRow(' + id + ')" title="Remove">&times;</span>'}
      </td>
    `;
    tbody.appendChild(tr);
    variants.forEach(v => addRfqVariantRow(id, v.variant, v.qty, v.priceRmb, v.leadTime));
    _updateVarAddRow(id);   // place trailing button below last variant (or parent if none)
    recalcRfqTotals();
    if (_wbLocked) {
      tr.querySelectorAll('input, select, textarea, button, span.remove-tier').forEach(el => {
        if (el.tagName === 'SPAN') { el.style.pointerEvents = 'none'; el.style.opacity = '0.3'; }
        else { el.disabled = true; el.style.opacity = '0.6'; el.style.cursor = 'not-allowed'; }
      });
    }
  }

  function removeRfqRow(id) {
    const row = document.getElementById(`rfq-${id}`);
    if (row) row.remove();
    document.querySelectorAll(`[data-rfq-parent="${id}"]`).forEach(vr => vr.remove());
    document.querySelector(`[data-rfq-add-for="${id}"]`)?.remove();
    renumberRfqRows();
    recalcRfqTotals();
    if (!_filling) autoSaveWorkbook();
  }

  function renumberRfqRows() {
    const rows = document.querySelectorAll('#rfq-body tr:not([data-rfq-parent]):not([data-rfq-add-for])');
    rows.forEach((row, i) => {
      const td = row.querySelector('td');
      if (i === 0) {
        td.textContent = i + 1;
      } else {
        td.textContent = '☰ ' + (i + 1);
      }
    });
  }

  function recalcRfqRow(id) {
    const row = document.getElementById(`rfq-${id}`);
    if (!row) return;
    const inputs = row.querySelectorAll('input:not([type="checkbox"])');
    const qty = parseFloat(inputs[2]?.value) || 0;
    const rmb = parseFloat(inputs[3]?.value) || 0;
    const usd = rmb / USD_TO_RMB;
    const total = qty * usd;
    const usdEl = document.getElementById(`rfq-usd-${id}`);
    const totalEl = document.getElementById(`rfq-total-${id}`);
    if (usdEl) usdEl.textContent = rmb ? '$' + usd.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : '—';
    if (totalEl) totalEl.textContent = (qty && rmb) ? '$' + total.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : '—';
    recalcRfqTotals();
    if (!_filling) autoSaveWorkbook();
  }

  function recalcRfqTotals() { try { _recalcRfqTotalsInner(); } catch(e) { console.error('[MS recalcRfqTotals]', e); } }
  function _recalcRfqTotalsInner() {
    const fmt = (n, dec = 2) => n.toLocaleString('en-US', {minimumFractionDigits: dec, maximumFractionDigits: dec});

    // ── 1. Collect per-item / per-variant data ────────────────────────────
    // itemSummaries is a flat list: one entry per variant (when variants exist),
    // or one entry per parent row (when no variants). This drives both the
    // breakdown rows and the grand totals — no double-counting.
    const parentRows = document.querySelectorAll('#rfq-body tr:not([data-rfq-parent]):not([data-rfq-add-for])');
    let grandQty = 0, grandUsd = 0, grandRmb = 0, grandUsdUnit = 0, maxLead = 0;
    const itemSummaries = [];  // { label, qty, rmb, usd, total, lead, isVariant }

    parentRows.forEach(row => {
      const id      = parseInt(row.id.replace('rfq-', ''));
      const inputs  = row.querySelectorAll('input:not([type="checkbox"])');
      const name    = inputs[1]?.value?.trim() || '';
      const qty     = parseFloat(inputs[2]?.value) || 0;
      const rmb     = parseFloat(inputs[3]?.value) || 0;  // the "original" price
      const lead    = inputs[4]?.value || '';
      const leadNum = parseInt(lead);

      const varRows = document.querySelectorAll(`[data-rfq-parent="${id}"]`);

      if (varRows.length > 0) {
        // ── Has variants: group same-price variants under parent label;
        //    only break out variants whose RMB differs from the parent. ────
        if (!isNaN(leadNum) && leadNum > maxLead) maxLead = leadNum;

        const EPSILON = 0.005;  // treat prices within ½ cent as equal
        let sameQty = 0;
        const diffVariants = [];

        varRows.forEach(vr => {
          const vi       = vr.querySelectorAll('input');
          const vName    = vi[0]?.value?.trim() || '';
          const vQty     = parseFloat(vi[1]?.value) || 0;
          const vRmb     = parseFloat(vi[2]?.value) || 0;
          const vLead    = vi[3]?.value || '';
          const vLeadNum = parseInt(vLead);
          if (!isNaN(vLeadNum) && vLeadNum > maxLead) maxLead = vLeadNum;

          if (Math.abs(vRmb - rmb) < EPSILON) {
            // Same price as original → merge into the parent group
            sameQty += vQty;
          } else {
            // Different price → own breakdown line
            diffVariants.push({ vName, vQty, vRmb, vLead });
          }
        });

        // Combined same-price group (shown under the original item name)
        if (sameQty > 0) {
          const usd   = rmb / USD_TO_RMB;
          const total = sameQty * usd;
          grandQty     += sameQty;
          grandRmb     += rmb;
          grandUsdUnit += usd;
          grandUsd     += total;
          itemSummaries.push({ label: name || ('Item ' + id), qty: sameQty, rmb, usd, total, lead, isVariant: false });
        }

        // Individually-priced variants
        diffVariants.forEach(({ vName, vQty, vRmb, vLead }) => {
          const vUsd   = vRmb / USD_TO_RMB;
          const vTotal = vQty * vUsd;
          grandQty     += vQty;
          grandRmb     += vRmb;
          grandUsdUnit += vUsd;
          grandUsd     += vTotal;
          const label = name
            ? `${name}<span style="color:var(--text-muted);font-weight:400;"> — ${vName || 'Variant'}</span>`
            : (vName || 'Variant');
          itemSummaries.push({ label, qty: vQty, rmb: vRmb, usd: vUsd, total: vTotal, lead: vLead, isVariant: true });
        });

      } else {
        // ── No variants: one summary row for the parent ───────────────────
        const usd   = rmb / USD_TO_RMB;
        const total = qty * usd;

        grandQty     += qty;
        grandRmb     += rmb;
        grandUsdUnit += usd;
        if (qty && rmb) grandUsd += total;
        if (!isNaN(leadNum) && leadNum > maxLead) maxLead = leadNum;

        if (name || qty || rmb) {
          itemSummaries.push({ label: name || ('Item ' + id), qty, rmb, usd, total, lead, isVariant: false });
        }
      }
    });

    // ── 2. Inject per-item subtotal rows (only when useful) ──────────────
    document.querySelectorAll('.rfq-item-subtotal').forEach(r => r.remove());

    const totalsRow = document.getElementById('rfq-totals');
    const showBreakdown = itemSummaries.length > 1
                       || itemSummaries.some(s => s.isVariant);

    if (showBreakdown && totalsRow) {
      itemSummaries.forEach(item => {
        const tr = document.createElement('tr');
        tr.className = 'rfq-item-subtotal';
        tr.style.borderTop = '1px solid var(--border)';
        tr.innerHTML = `
          <td></td><td></td><td></td>
          <td style="font-size:12px;color:var(--text);padding:7px 8px 7px 26px;font-weight:500;">
            ${item.label}
          </td>
          <td style="font-size:12px;color:var(--text);font-weight:700;padding:7px 8px 7px 26px;">
            ${item.qty ? item.qty.toLocaleString('en-US') : '—'}
          </td>
          <td style="font-size:12px;color:var(--text-muted);padding:7px 8px 7px 26px;">
            ${item.rmb ? '¥' + fmt(item.rmb) : '—'}
          </td>
          <td style="font-size:12px;color:var(--text-muted);text-align:right;padding:7px 12px 7px 8px;">
            ${item.rmb ? '$' + fmt(item.usd) : '—'}
          </td>
          <td style="font-size:12px;color:var(--text);font-weight:700;text-align:right;padding:7px 12px 7px 8px;">
            ${item.total ? '$' + fmt(item.total) : '—'}
          </td>
          <td style="font-size:12px;color:var(--text-muted);padding:7px 8px 7px 26px;">
            ${item.lead ? item.lead + ' days' : '—'}
          </td>
          <td></td>`;
        totalsRow.before(tr);
      });
    }

    // ── 3. Grand total row label + values ────────────────────────────────
    const totalsLabel = totalsRow?.querySelector('td:nth-child(4)');
    if (totalsLabel) totalsLabel.textContent = showBreakdown ? 'GRAND TOTAL' : 'TOTALS';

    document.getElementById('rfq-total-qty').textContent     = grandQty     ? grandQty.toLocaleString('en-US') : '—';
    document.getElementById('rfq-total-rmb').textContent     = grandRmb     ? '¥ ' + fmt(grandRmb)             : '—';
    document.getElementById('rfq-total-usd-sum').textContent = grandUsdUnit ? '$'  + fmt(grandUsdUnit)         : '—';
    document.getElementById('rfq-total-usd').textContent     = grandUsd     ? '$'  + fmt(grandUsd)             : '—';
    document.getElementById('rfq-max-lead').textContent      = maxLead      ? maxLead + ' days'                : '—';

    applyRfqRmbToTiers(grandRmb);
  }  // end _recalcRfqTotalsInner

  function applyRfqRmbToTiers(totalRmb) {
    const rows = document.querySelectorAll('#wb-tier-body tr');
    if (!rows.length) return;
    const price = totalRmb > 0 ? parseFloat(totalRmb).toFixed(2) : '';
    // Cascade price to ALL tier rows (all are view-only, all show the same unit cost)
    rows.forEach(row => { row.dataset.price = price; });
    // Sync qty from first RFQ row into first tier row
    const rfqFirstRow = document.querySelector('#rfq-body tr:not([data-rfq-parent]):not([data-rfq-add-for]):first-child');
    const rfqInputs = rfqFirstRow ? rfqFirstRow.querySelectorAll('input:not([type="checkbox"])') : [];
    const rfqQty = rfqInputs[2]?.value;
    const firstTierQtyInput = rows[0].querySelector('input[type="number"]');
    if (firstTierQtyInput && rfqQty) firstTierQtyInput.value = rfqQty;
    // Recalc all rows
    _syncing = true;
    rows.forEach(row => {
      const id = parseInt(row.id.replace('wb-tier-', ''));
      recalcWbTier(id);
    });
    _syncing = false;
    syncTiersToPricing();
    populateTierDropdown();
  }

  function addRfqVariantRow(parentId, variant = '', qty = '', priceRmb = '', leadTime = '') {
    _rfqVarCount++;
    const vid = _rfqVarCount;
    const tbody = document.getElementById('rfq-body');
    // Inherit parent's RMB price if none supplied
    if (!priceRmb) {
      const parentRow = document.getElementById(`rfq-${parentId}`);
      if (parentRow) {
        const parentInputs = parentRow.querySelectorAll('input:not([type="checkbox"])');
        priceRmb = parentInputs[3]?.value || '';
      }
    }
    const usdVal = priceRmb ? (parseFloat(priceRmb) / USD_TO_RMB).toFixed(2) : '';
    const totalVal = (qty && usdVal) ? (parseFloat(qty) * parseFloat(usdVal)).toFixed(2) : '';
    const inputStyle = 'width:100%; border:1px solid var(--border); border-radius:8px; padding:10px 14px; font-size:13px; box-sizing:border-box; background:var(--surface2); color:var(--text); font-family:inherit;';
    const tr = document.createElement('tr');
    tr.id = `rfq-var-${vid}`;
    tr.classList.add('rfq-variant-row');
    tr.setAttribute('data-rfq-parent', parentId);
    // Drag events must live on the <tr> itself because draggable is set on <tr>
    // (dragstart fires on the draggable element, not on child <td>s)
    tr.ondragover  = function(e) { if (_draggingVarId !== null) { e.preventDefault(); tr.style.borderTop = '2px solid var(--accent)'; } };
    tr.ondragleave = function()  { tr.style.borderTop = ''; };
    tr.ondrop      = function(e) { e.preventDefault(); tr.style.borderTop = ''; rfqDropVariant(vid); };
    tr.ondragstart = function()  { _draggingVarId = vid; tr.style.opacity = '0.4'; };
    tr.ondragend   = function()  { _draggingVarId = null; tr.style.opacity = '1'; tr.draggable = false; };
    tr.innerHTML = `
      <td title="Drag to reorder" style="cursor:grab; color:var(--text-muted); text-align:center; padding:0 6px; user-select:none; font-size:12px;"
          onmousedown="this.closest('tr').draggable=true"
          onmouseup="this.closest('tr').draggable=false">☰</td>
      <td></td>
      <td></td>
      <td><input type="text" placeholder="e.g. Small / Red…" value="${variant}" oninput="recalcRfqVariantRow(${vid})" style="${inputStyle}" /></td>
      <td><input type="text" inputmode="numeric" placeholder="0" value="${qty}" oninput="recalcRfqVariantRow(${vid})" style="${inputStyle}" /></td>
      <td><div class="currency-prefix currency-rmb" style="position:relative;"><input type="text" inputmode="decimal" placeholder="0.00" value="${priceRmb}" oninput="recalcRfqVariantRow(${vid})" style="${inputStyle} padding-left:28px;" /></div></td>
      <td class="tier-col-usd" id="rfq-var-usd-${vid}" style="color:var(--text); font-size:13px; text-align:right; font-weight:600; padding-right:12px;">${usdVal ? '$' + parseFloat(usdVal).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : '—'}</td>
      <td class="total-cell" id="rfq-var-total-${vid}" style="text-align:right; font-size:13px; padding-right:12px;">${totalVal ? '$' + parseFloat(totalVal).toLocaleString('en-US', {minimumFractionDigits:2}) : '—'}</td>
      <td><div class="lead-time-suffix" style="position:relative;"><input type="text" placeholder="0" value="${leadTime}" oninput="recalcRfqTotals()" style="${inputStyle} padding-right:40px;" /></div></td>
      <td style="text-align:center;"><span class="remove-tier" onclick="removeRfqVariantRow(${vid})" title="Remove variant">&times;</span></td>
    `;
    // Insert after parent row and all its existing variants (but before any trailing add-row)
    const existingVars = [...document.querySelectorAll(`[data-rfq-parent="${parentId}"]`)];
    const anchor = existingVars.length ? existingVars[existingVars.length - 1] : document.getElementById(`rfq-${parentId}`);
    anchor.after(tr);
    _updateVarAddRow(parentId);   // move/create the trailing "+ Add Variant" row
    syncParentQtyFromVariants(parentId);
    recalcRfqTotals();
    if (!_filling) autoSaveWorkbook();
  }

  // Keep a "+ Add Variant" row just below the last variant (or the parent row when
  // no variants exist yet). The button on the parent row has been removed — this is
  // now the only way to add variants, and it always stays at the bottom.
  function _updateVarAddRow(parentId) {
    // Remove existing trailing add-row for this parent
    document.querySelector(`[data-rfq-add-for="${parentId}"]`)?.remove();

    const varRows = document.querySelectorAll(`[data-rfq-parent="${parentId}"]`);
    // Anchor: after last variant, or directly after the parent row if none exist yet
    const anchor = varRows.length > 0
      ? varRows[varRows.length - 1]
      : document.getElementById(`rfq-${parentId}`);
    if (!anchor) return;

    const tr = document.createElement('tr');
    tr.setAttribute('data-rfq-add-for', String(parentId));
    tr.className = 'rfq-var-add-row';
    // 3 empty leading cells (drag handle, sample checkbox, SKU) — matches "+ Add Line Item" layout
    for (let i = 0; i < 3; i++) {
      const e = document.createElement('td');
      e.style.cssText = 'padding:0;border-bottom:none;';
      tr.appendChild(e);
    }
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.textContent = '+ Add Variant';
    btn.className = 'btn btn-add';
    btn.style.cssText = 'width:100%;margin:4px 0;';
    btn.onclick = () => addRfqVariantRow(parentId);
    if (typeof _wbLocked !== 'undefined' && _wbLocked) {
      btn.disabled = true;
      btn.style.opacity = '0.4';
      btn.style.cursor  = 'default';
    }
    const td = document.createElement('td');
    td.style.cssText = 'padding:4px 12px;border-bottom:none;';
    td.appendChild(btn);
    tr.appendChild(td);
    // Remaining 6 columns
    const rest = document.createElement('td');
    rest.colSpan = 6;
    rest.style.cssText = 'padding:0;border-bottom:none;';
    tr.appendChild(rest);

    // Insert after anchor (last variant, or parent row when no variants exist)
    anchor.after(tr);
  }

  function syncParentQtyFromVariants(parentId) {
    const variantRows = document.querySelectorAll(`[data-rfq-parent="${parentId}"]`);
    const parentRow = document.getElementById(`rfq-${parentId}`);
    if (!parentRow) return;
    const parentInputs = parentRow.querySelectorAll('input:not([type="checkbox"])');
    const qtyInput = parentInputs[2];
    if (!qtyInput) return;
    if (variantRows.length > 0) {
      let sum = 0;
      variantRows.forEach(vr => {
        const vi = vr.querySelectorAll('input');
        sum += parseFloat(vi[1]?.value) || 0;
      });
      qtyInput.value = sum || '';
      qtyInput.readOnly = true;
      qtyInput.style.opacity = '0.6';
      qtyInput.style.cursor = 'default';
      qtyInput.title = 'Auto-summed from variants';
    } else {
      qtyInput.readOnly = false;
      qtyInput.style.opacity = '';
      qtyInput.style.cursor = '';
      qtyInput.title = '';
    }
    recalcRfqRow(parentId);
  }

  function removeRfqVariantRow(vid) {
    const row = document.getElementById(`rfq-var-${vid}`);
    const parentId = row ? parseInt(row.dataset.rfqParent) : null;
    row?.remove();
    if (parentId) {
      _updateVarAddRow(parentId);   // reposition or remove the trailing add-row
      syncParentQtyFromVariants(parentId);
    }
    recalcRfqTotals();
    if (!_filling) autoSaveWorkbook();
  }

  function rfqDropVariant(targetVid) {
    const srcVid = _draggingVarId;
    _draggingVarId = null;
    if (!srcVid || srcVid === targetVid) return;
    const srcRow = document.getElementById(`rfq-var-${srcVid}`);
    const tgtRow = document.getElementById(`rfq-var-${targetVid}`);
    if (!srcRow || !tgtRow) return;
    // Only allow reorder within the same parent line item
    if (srcRow.dataset.rfqParent !== tgtRow.dataset.rfqParent) return;
    tgtRow.before(srcRow);
    if (!_filling) autoSaveWorkbook();
  }

  function recalcRfqVariantRow(vid) {
    const row = document.getElementById(`rfq-var-${vid}`);
    if (!row) return;
    const inputs = row.querySelectorAll('input');
    const qty = parseFloat(inputs[1]?.value) || 0;
    const rmb = parseFloat(inputs[2]?.value) || 0;
    const usd = rmb / USD_TO_RMB;
    const total = qty * usd;
    const usdEl = document.getElementById(`rfq-var-usd-${vid}`);
    const totalEl = document.getElementById(`rfq-var-total-${vid}`);
    if (usdEl) usdEl.textContent = rmb ? '$' + usd.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : '—';
    if (totalEl) totalEl.textContent = (qty && rmb) ? '$' + total.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : '—';
    const parentId = parseInt(row.dataset.rfqParent);
    if (parentId) syncParentQtyFromVariants(parentId);
    recalcRfqTotals();
    if (!_filling) autoSaveWorkbook();
  }

  function collectRfqItems() {
    const rows = document.querySelectorAll('#rfq-body tr:not([data-rfq-parent]):not([data-rfq-add-for])');
    const items = [];
    rows.forEach(row => {
      const id = parseInt(row.id.replace('rfq-', ''));
      const inputs = row.querySelectorAll('input:not([type="checkbox"])');
      const sampleCheck = row.querySelector('.rfq-sample-check');
      const variantRows = document.querySelectorAll(`[data-rfq-parent="${id}"]`);
      const variants = [];
      variantRows.forEach(vr => {
        const vi = vr.querySelectorAll('input');
        variants.push({
          variant: vi[0]?.value || '',
          qty: vi[1]?.value || '',
          priceRmb: vi[2]?.value || '',
          leadTime: vi[3]?.value || ''
        });
      });
      items.push({
        sku: inputs[0]?.value || '',   // SKU is col 3 → inputs[0]
        item: inputs[1]?.value || '',  // Item is col 4 → inputs[1]
        qty: inputs[2]?.value || '',
        priceRmb: inputs[3]?.value || '',
        leadTime: inputs[4]?.value || '',
        sample: sampleCheck?.checked || false,
        variants
      });
    });
    return items;
  }

  function toggleRfqSample(checkbox) {
    const row = checkbox.closest('tr');
    const iconSpan = checkbox.nextElementSibling;
    if (checkbox.checked) {
      row.classList.add('rfq-sample-row');
    } else {
      row.classList.remove('rfq-sample-row');
      if (iconSpan) iconSpan.textContent = '';
    }
    if (_appReady) autoSaveWorkbook();
  }

  function rfqDropRow(e, targetId) {
    const draggedId = parseInt(e.dataTransfer.getData('text/plain'));
    const tbody = document.getElementById('rfq-body');
    const firstRow = tbody.querySelector('tr');
    const draggedRow = document.getElementById(`rfq-${draggedId}`);
    const targetRow = document.getElementById(`rfq-${targetId}`);
    // Don't allow dropping onto or moving the first row
    if (draggedRow === firstRow || targetRow === firstRow) return;
    if (draggedRow && targetRow && draggedRow !== targetRow) {
      tbody.insertBefore(draggedRow, targetRow);
      // Move variant rows immediately after the dragged parent
      [...document.querySelectorAll(`[data-rfq-parent="${draggedId}"]`)].forEach(vr => draggedRow.after(vr));
      renumberRfqRows();
      recalcRfqTotals();
      if (!_filling) autoSaveWorkbook();
    }
  }

  // Arrow key navigation within RFQ table
  document.addEventListener('keydown', function(e) {
    const el = document.activeElement;
    if (!el || !el.closest('#rfq-body')) return;
    if (!['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(e.key)) return;
    e.preventDefault();
    const row = el.closest('tr');
    const rows = Array.from(document.querySelectorAll('#rfq-body tr'));
    const inputs = Array.from(row.querySelectorAll('input'));
    const colIdx = inputs.indexOf(el);
    const rowIdx = rows.indexOf(row);
    if (e.key === 'ArrowLeft' && colIdx > 0) inputs[colIdx - 1].focus();
    if (e.key === 'ArrowRight' && colIdx < inputs.length - 1) inputs[colIdx + 1].focus();
    if (e.key === 'ArrowUp' && rowIdx > 0) {
      const prevInputs = rows[rowIdx - 1].querySelectorAll('input');
      if (prevInputs[colIdx]) prevInputs[colIdx].focus();
    }
    if (e.key === 'ArrowDown' && rowIdx < rows.length - 1) {
      const nextInputs = rows[rowIdx + 1].querySelectorAll('input');
      if (nextInputs[colIdx]) nextInputs[colIdx].focus();
    }
  });

  /* ── Tiered Pricing ────────────────────────────────────────────────────── */
  let tierCount = 3;
  let _filling = false; // true while fillWorkbook is running, prevents auto-save
  let _appReady = false; // true after init completes — blocks all saves until then

  function addTierRow(qty = '', unitPrice = '') {
    tierCount++;
    const id = tierCount;
    const tbody = document.getElementById('tier-body');
    const tr = document.createElement('tr');
    tr.id = `tier-${id}`;
    tr.innerHTML = `
      <td class="tier-col-num" style="color:var(--text-muted); font-weight:600;">${id}</td>
      <td style="color:var(--text); font-size:13px;">${qty ? parseFloat(qty).toLocaleString('en-US') : '—'}</td>
      <td style="color:var(--text); font-size:13px;">${unitPrice ? '¥ ' + parseFloat(unitPrice).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : '—'}</td>
      <td class="tier-col-usd" id="tier-usd-${id}" style="color:var(--text-muted); font-size:13px;">—</td>
      <td class="total-cell" id="tier-total-${id}">—</td>
      <td></td>
    `;
    // Store values as data attributes for collectTiers
    tr.dataset.qty = qty;
    tr.dataset.price = unitPrice;
    tbody.appendChild(tr);
    recalcTier(id);
    if (!_filling) autoSaveWorkbook();
  }

  function recalcTier(id) {
    const row    = document.getElementById(`tier-${id}`);
    if (!row) return;
    const qty    = parseFloat(row.dataset.qty);
    const rmb    = parseFloat(row.dataset.price);
    const usd    = rmb / USD_TO_RMB;
    const totalEl = document.getElementById(`tier-total-${id}`);
    const usdEl = document.getElementById(`tier-usd-${id}`);
    if (!isNaN(rmb)) {
      usdEl.textContent = '$' + usd.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
    } else {
      usdEl.textContent = '—';
    }
    if (!isNaN(qty) && !isNaN(rmb)) {
      totalEl.textContent = '$' + (qty * usd).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    } else {
      totalEl.textContent = '—';
    }
    if (!_filling && !_syncing) {
      syncTiersToWb();
      calcAdditionalFees();
      autoSaveWorkbook();
    }
  }

  function removeTierRow(id) {
    document.getElementById(`tier-${id}`)?.remove();
    syncTiersToWb();
    calcAdditionalFees();
    autoSaveWorkbook();
  }

  // ── Workbook tab tier pricing (synced with Pricing tab) ──
  let wbTierCount = 0;
  let _syncing = false;
  let _selectedTierId = 1;

  function addWbTierRow(qty = '', unitPrice = '') {
    wbTierCount++;
    const id = wbTierCount;
    const tbody = document.getElementById('wb-tier-body');
    const tr = document.createElement('tr');
    tr.id = `wb-tier-${id}`;
    // First row: read-only (driven by RFQ total). All others: inherit tier-1 price by default.
    const isFirst = (id === 1);
    // For new non-first rows with no saved price, inherit from tier 1
    if (!isFirst && !unitPrice) {
      const tier1 = document.getElementById('wb-tier-1');
      if (tier1 && tier1.dataset.price) unitPrice = tier1.dataset.price;
    }
    tr.dataset.price = unitPrice || '';
    // All price cells are view-only — driven by RFQ total
    const rmbCell = `<td id="wb-tier-rmb-${id}" style="font-size:13px; color:var(--text-muted);">
        <span id="wb-tier-rmb-val-${id}">—</span>
      </td>`;
    tr.innerHTML = `
      <td class="tier-col-num" style="color:var(--text-muted); font-weight:600;">${id}</td>
      <td>
        <input type="number" min="0" placeholder="e.g. 100" value="${qty}"
               oninput="recalcWbTier(${id})"
               style="width:110px;${isFirst ? ' background:var(--surface2); color:var(--text-muted); cursor:not-allowed;' : ''}"
               ${isFirst ? 'readonly title="Auto-populated from Quote Details qty"' : ''} />
      </td>
      ${rmbCell}
      <td class="tier-col-usd" id="wb-tier-usd-${id}" style="color:var(--text-muted); font-size:13px;">—</td>
      <td class="total-cell" id="wb-tier-total-${id}">—</td>
      <td>${isFirst ? '' : `<button class="btn btn-danger-ghost" onclick="removeWbTierRow(${id})">✕</button>`}</td>
    `;
    tbody.appendChild(tr);
    recalcWbTier(id);
  }

  function recalcWbTier(id) {
    const row = document.getElementById(`wb-tier-${id}`);
    const inputs = row.querySelectorAll('input');
    const qty = parseFloat(inputs[0].value);
    // All rows are view-only for price — read from dataset
    const rmb = parseFloat(row.dataset.price);
    const usd = rmb / USD_TO_RMB;
    const rmbValEl = document.getElementById(`wb-tier-rmb-val-${id}`);
    const usdEl = document.getElementById(`wb-tier-usd-${id}`);
    const totalEl = document.getElementById(`wb-tier-total-${id}`);
    if (rmbValEl) {
      rmbValEl.textContent = (!isNaN(rmb) && rmb > 0) ? '¥ ' + rmb.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : '—';
    }
    if (!isNaN(rmb) && rmb > 0) {
      usdEl.textContent = '$' + usd.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
    } else {
      usdEl.textContent = '—';
    }
    if (!isNaN(qty) && !isNaN(rmb) && rmb > 0) {
      totalEl.textContent = '$' + (qty * usd).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    } else {
      totalEl.textContent = '—';
    }
    if (!_filling && !_syncing) {
      syncTiersToPricing();
      populateTierDropdown();
      autoSaveWorkbook();
    }
  }

  function removeWbTierRow(id) {
    document.getElementById(`wb-tier-${id}`)?.remove();
    syncTiersToPricing();
    populateTierDropdown();
    autoSaveWorkbook();
  }

  function populateTierDropdown() {
    const sel = document.getElementById('sh-tier-select');
    if (!sel) return;
    const prev = _selectedTierId;
    sel.innerHTML = '<option value="">— Select a tier —</option>';
    document.querySelectorAll('#wb-tier-body tr').forEach(row => {
      const id = parseInt(row.id.replace('wb-tier-', ''));
      const inputs = row.querySelectorAll('input');
      const qty = inputs[0]?.value;
      const rmb = parseFloat(row.dataset.price);
      const usd = !isNaN(rmb) && rmb > 0 ? rmb / USD_TO_RMB : NaN;
      let label = `Tier ${id}`;
      if (qty) label += ` — ${parseInt(qty).toLocaleString('en-US')} units`;
      const opt = document.createElement('option');
      opt.value = id;
      opt.textContent = label;
      opt.dataset.qty = qty ? (parseInt(qty) || 0) : 0;
      sel.appendChild(opt);
    });
    // Restore previous selection if still valid
    if (prev && sel.querySelector(`option[value="${prev}"]`)) {
      sel.value = prev;
      renderShippingTierDetails(prev);
    } else {
      _selectedTierId = null;
      document.getElementById('sh-tier-details')?.classList.remove('visible');
    }
  }

  function onShippingTierSelect() {
    const sel = document.getElementById('sh-tier-select');
    const id = sel ? parseInt(sel.value) : null;
    _selectedTierId = id || null;
    renderShippingTierDetails(id);
    if (_appReady) autoSaveWorkbook();
  }

  function renderShippingTierDetails(id) {
    const detailBar = document.getElementById('sh-tier-details');
    const row = id ? document.getElementById(`wb-tier-${id}`) : null;
    if (!row) {
      if (detailBar) detailBar.classList.remove('visible');
      return;
    }
    const inputs = row.querySelectorAll('input');
    const qty = inputs[0]?.value || '';
    const rmb = parseFloat(row.dataset.price);
    const usd = !isNaN(rmb) && rmb > 0 ? rmb / USD_TO_RMB : NaN;
    const totalUsd = qty && !isNaN(usd) ? parseFloat(qty) * usd : NaN;

    // Inline details
    document.getElementById('sh-td-rmb-full').textContent = (!isNaN(rmb) && rmb > 0) ? '¥ ' + rmb.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : '—';
    document.getElementById('sh-td-usd-full').textContent = !isNaN(usd) ? '$' + usd.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : '—';
    document.getElementById('sh-td-total').textContent    = !isNaN(totalUsd) ? '$' + totalUsd.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : '—';
    if (detailBar) detailBar.classList.add('visible');
    renderPricingTab();

    // Recalculate total outer cartons for this tier qty and refresh freight
    const tierQty        = parseInt(qty) || 0;
    const unitsPerInner  = parseInt(document.getElementById('carton-inner-count')?.value) || 0;
    const innersPerOuter = parseInt(document.getElementById('carton-outer-count')?.value) || 0;
    const unitsPerOuter  = unitsPerInner * innersPerOuter;
    const innerWeightKg  = parseFloat(document.getElementById('carton-inner-weight')?.value) || 0;
    const unitWeightKg   = parseFloat(document.getElementById('dim-weight-kg')?.value) || 0;
    const outerWeightKg  = parseFloat(document.getElementById('carton-outer-weight')?.value) || 0;
    const palletEl = document.getElementById('pallet-total-cartons');

    // Derive effective unit weight: prefer explicit dim-weight-kg, fall back to inner_weight / inner_count
    const effectiveUnitWt = unitWeightKg > 0 ? unitWeightKg :
      (innerWeightKg > 0 && unitsPerInner > 0 ? innerWeightKg / unitsPerInner : 0);

    if (tierQty > 0 && palletEl) {
      let totalCartons = null;
      if (unitsPerOuter > 0) {
        // Best path: carton counts are fully configured
        totalCartons = Math.ceil(tierQty / unitsPerOuter);
      } else if (effectiveUnitWt > 0 && outerWeightKg > 0 && outerWeightKg >= effectiveUnitWt) {
        // Fallback: estimate units-per-outer from weight ratio
        const estimatedUnitsPerOuter = Math.max(1, Math.round(outerWeightKg / effectiveUnitWt));
        totalCartons = Math.ceil(tierQty / estimatedUnitsPerOuter);
      } else if (effectiveUnitWt > 0) {
        // Last resort: treat each unit as its own carton so total weight = unit_weight × tier_qty
        const outerWtEl = document.getElementById('carton-outer-weight');
        if (outerWtEl) {
          outerWtEl.value = effectiveUnitWt.toFixed(3);
          convertWeight('carton-outer-weight', 'carton-outer-weight-lbs', 'kg');
        }
        totalCartons = tierQty;
      }
      if (totalCartons !== null) {
        palletEl.value = totalCartons;
        renderPalletViz();
        calcFreight();
      }
    }
  }

  function collectTiersFrom(tbodyId) {
    const rows = document.querySelectorAll(`#${tbodyId} tr`);
    const tiers = [];
    rows.forEach(row => {
      const inputs = row.querySelectorAll('input');
      if (inputs.length >= 1) {
        tiers.push({ qty: inputs[0].value, price: row.dataset.price || '' });
      }
    });
    return tiers;
  }

  function syncTiersToPricing() {
    if (_syncing) return;
    _syncing = true;
    const tiers = collectTiersFrom('wb-tier-body');
    document.getElementById('tier-body').innerHTML = '';
    tierCount = 0;
    tiers.forEach(t => addTierRow(t.qty, t.price));
    _syncing = false;
  }

  function syncTiersToWb() {
    if (_syncing) return;
    _syncing = true;
    const tiers = collectTiersFrom('tier-body');
    document.getElementById('wb-tier-body').innerHTML = '';
    wbTierCount = 0;
    tiers.forEach(t => addWbTierRow(t.qty, t.price));
    _syncing = false;
  }

  /* ── Shipment Splits ───────────────────────────────────────────────────── */
  let _shipmentSplits = [];
  let _splitCounter = 0;

  const SPLIT_METHOD_LABELS = { slow: 'Slow Boat', fast: 'Fast Boat', airupp: 'Air + UPS', directair: 'Direct Air' };

  function calcSplitCost(method, qty) {
    qty = parseInt(qty) || 0;
    if (qty <= 0) return null;
    const get = id => parseFloat(document.getElementById(id)?.value) || 0;
    const lCm      = get('carton-outer-l-cm');
    const wCm      = get('carton-outer-w-cm');
    const hCm      = get('carton-outer-h-cm');
    const actualKg = get('carton-outer-weight');
    const innerCount = parseInt(document.getElementById('carton-inner-count')?.value) || 0;
    const outerCount = parseInt(document.getElementById('carton-outer-count')?.value) || 0;
    const unitsPerOuter = innerCount * outerCount;
    if (!lCm || !wCm || !hCm || !actualKg || unitsPerOuter <= 0) return null;
    const cartons  = Math.ceil(qty / unitsPerOuter);
    const rate     = freightMethodRates[method] || freightMethodRates.slow;
    const divisor  = freightMethodDivisors[method] || freightMethodDivisors.slow;
    const volWeight = (lCm * wCm * hCm) / divisor;
    const chargePerCarton = Math.max(actualKg, volWeight);
    const totalCharge = chargePerCarton * cartons;
    const rmb = totalCharge * rate;
    const usd = rmb / FREIGHT_EXCHANGE_RATE;
    const fmt2 = v => v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    return { rmbStr: `¥${fmt2(rmb)}`, usdStr: `$${fmt2(usd)}` };
  }

  function addShipmentSplit() {
    _splitCounter++;
    _shipmentSplits.push({ id: _splitCounter, method: 'slow', qty: '' });
    renderShipmentSplits();
    autoSaveWorkbook();
  }

  function removeShipmentSplit(id) {
    _shipmentSplits = _shipmentSplits.filter(r => r.id !== id);
    renderShipmentSplits();
    autoSaveWorkbook();
  }

  function onSplitChange(id, field, value) {
    const row = _shipmentSplits.find(r => r.id === id);
    if (!row) return;
    row[field] = value;
    // Update the cost span in-place so the qty input doesn't lose focus
    const costEl = document.getElementById(`split-cost-${id}`);
    if (costEl) {
      const cost = calcSplitCost(row.method, row.qty);
      costEl.innerHTML = cost ? `${cost.rmbStr} &nbsp;·&nbsp; ${cost.usdStr}` : '';
    }
    renderShipmentSplitSummary();
    autoSaveWorkbook();
  }

  function renderShipmentSplits() {
    const body = document.getElementById('shipment-split-body');
    if (!body) return;
    if (_shipmentSplits.length === 0) {
      body.innerHTML = `<div style="font-size:12px; color:var(--text-muted); font-style:italic; padding:4px 0;">No splits defined — full quantity ships as one.</div>`;
      renderShipmentSplitSummary();
      return;
    }
    body.innerHTML = _shipmentSplits.map(row => {
      const cost = calcSplitCost(row.method, row.qty);
      const costStr = `<span id="split-cost-${row.id}" style="font-size:12px; font-weight:600; color:var(--text-muted); white-space:nowrap;">${cost ? `${cost.rmbStr} &nbsp;·&nbsp; ${cost.usdStr}` : ''}</span>`;
      return `<div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
        <input type="number" min="0" step="1" value="${row.qty}"
          style="width:80px; height:32px; padding:0 8px; border:1px solid var(--border); border-radius:var(--radius-sm); background:var(--surface2); color:var(--text); font-size:13px; font-family:inherit; outline:none; flex-shrink:0;"
          oninput="onSplitChange(${row.id}, 'qty', this.value)" placeholder="Qty" />
        <span style="font-size:12px; color:var(--text-muted); white-space:nowrap; flex-shrink:0;">units →</span>
        <div class="ship-select-wrap" style="width:140px; flex-shrink:0;">
          <select onchange="onSplitChange(${row.id}, 'method', this.value)"
            style="width:100%; height:32px; padding:0 28px 0 10px; border:1px solid var(--border); border-radius:var(--radius-sm); background:var(--surface2); color:var(--text); font-size:13px; font-family:inherit; outline:none; cursor:pointer; appearance:none; -webkit-appearance:none;">
            ${Object.entries(SPLIT_METHOD_LABELS).map(([k,v]) => `<option value="${k}"${row.method===k?' selected':''}>${v}</option>`).join('')}
          </select>
        </div>
        <span style="flex:1;"></span>
        ${costStr}
        <button onclick="removeShipmentSplit(${row.id})"
          style="background:none; border:none; cursor:pointer; color:var(--text-muted); font-size:16px; padding:2px 6px; border-radius:var(--radius-sm); transition:color 0.15s; flex-shrink:0;"
          onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='var(--text-muted)'">×</button>
      </div>`;
    }).join('');
    renderShipmentSplitSummary();
  }

  function renderShipmentSplitSummary() {
    const el = document.getElementById('shipment-split-summary');
    if (!el) return;
    if (_shipmentSplits.length === 0) { el.textContent = ''; return; }
    const total = _shipmentSplits.reduce((s, r) => s + (parseInt(r.qty) || 0), 0);
    // Get selected tier qty — read from the data-qty attribute stored on the selected option
    const tierQty = (() => {
      const sel = document.getElementById('sh-tier-select');
      if (!sel || !sel.value) return 0;
      const opt = sel.options[sel.selectedIndex];
      return opt ? (parseInt(opt.dataset.qty) || 0) : 0;
    })();
    const parts = Object.entries(
      _shipmentSplits.reduce((acc, r) => {
        const m = r.method || 'slow';
        acc[m] = (acc[m] || 0) + (parseInt(r.qty) || 0);
        return acc;
      }, {})
    ).map(([m, q]) => `${q.toLocaleString('en-US')} ${SPLIT_METHOD_LABELS[m] || m}`).join(' · ');
    const isOver = tierQty > 0 && total > tierQty;
    const totalStyle = isOver ? 'font-weight:600; color:#ef4444;' : 'font-weight:600;';
    const overMsg = isOver ? ` <span style="color:#ef4444; font-weight:600;">⚠ exceeds tier qty of ${tierQty.toLocaleString('en-US')}</span>` : '';
    el.innerHTML = `<span style="${totalStyle}">${total.toLocaleString('en-US')} total</span> &nbsp;·&nbsp; ${parts}${overMsg}`;
    el.style.color = isOver ? '#ef4444' : '';
  }

  function collectShipmentSplits() {
    return _shipmentSplits.map(r => ({ method: r.method, qty: parseInt(r.qty) || 0 })).filter(r => r.qty > 0);
  }

  /* ── Additional Fees ───────────────────────────────────────────────────── */
  let _extraFeeRows = [];
  let _extraFeeCounter = 0;

  const FEE_TYPE_LABELS = {
    sample: 'Sample Fee(s)', tooling: 'Tooling Fee(s)', die: 'Die Fee(s)',
    plate: 'Plate Fee(s)', design: 'Design Fee(s)'
  };

  function openAddFeeModal() {
    let modal = document.getElementById('add-fee-modal');
    if (modal) modal.remove();
    modal = document.createElement('div');
    modal.id = 'add-fee-modal';
    modal.className = 'modal-overlay';
    modal.style.cssText = 'display:flex;';
    modal.innerHTML = `
      <div class="modal" style="max-width:420px; width:100%;">
        <div class="modal-header">
          <h3 style="margin:0; font-size:16px;">Add Fee</h3>
          <button onclick="document.getElementById('add-fee-modal').remove()" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--text-muted);">&times;</button>
        </div>
        <div class="modal-body" style="display:flex;flex-direction:column;gap:14px;">
          <div id="add-fee-error" style="display:none;background:rgba(251,113,133,0.12);border:1px solid rgba(251,113,133,0.35);color:#fb7185;border-radius:8px;padding:8px 12px;font-size:13px;"></div>
          <div>
            <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-muted);display:block;margin-bottom:4px;">Fee Type</label>
            <select id="add-fee-type" class="form-input" style="width:100%;" onchange="onAddFeeTypeChange()" autocomplete="off">
              <option value="sample">Sample Fee(s)</option>
              <option value="tooling">Tooling Fee(s)</option>
              <option value="die">Die Fee(s)</option>
              <option value="plate">Plate Fee(s)</option>
              <option value="design">Design Fee(s)</option>
            </select>
          </div>
          <div>
            <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-muted);display:block;margin-bottom:4px;">Description</label>
            <input id="add-fee-desc" type="text" class="form-input" style="width:100%;" placeholder="e.g. Rush sample, 2nd colour run…" autocomplete="off" />
          </div>
          <div id="add-fee-rmb-row">
            <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-muted);display:block;margin-bottom:4px;">Amount (RMB)</label>
            <div class="currency-prefix currency-rmb">
              <input id="add-fee-rmb" type="number" step="0.01" min="0" class="form-input" style="width:100%;padding-left:28px;" placeholder="0.00" oninput="onAddFeeRmbInput()" autocomplete="off" />
            </div>
          </div>
          <div>
            <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-muted);display:block;margin-bottom:4px;">Amount (USD)</label>
            <div class="currency-prefix currency-usd">
              <input id="add-fee-usd" type="number" step="0.01" min="0" class="form-input" style="width:100%;padding-left:28px;" placeholder="0.00" oninput="onAddFeeUsdInput()" autocomplete="off" />
            </div>
          </div>
          <button class="btn btn-primary" onclick="confirmAddFee()">Add Fee</button>
        </div>
      </div>`;
    document.body.appendChild(modal);
    setTimeout(() => document.getElementById('add-fee-desc')?.focus(), 50);
  }

  function onAddFeeTypeChange() {
    const type = document.getElementById('add-fee-type').value;
    const rmbRow = document.getElementById('add-fee-rmb-row');
    rmbRow.style.display = type === 'design' ? 'none' : '';
    if (type === 'design') {
      document.getElementById('add-fee-rmb').value = '';
    }
  }

  function onAddFeeRmbInput() {
    const rmb = parseFloat(document.getElementById('add-fee-rmb').value) || 0;
    document.getElementById('add-fee-usd').value = rmb > 0 ? (rmb / USD_TO_RMB).toFixed(2) : '';
  }

  function onAddFeeUsdInput() {
    const type = document.getElementById('add-fee-type').value;
    if (type === 'design') return;
    const usd = parseFloat(document.getElementById('add-fee-usd').value) || 0;
    document.getElementById('add-fee-rmb').value = usd > 0 ? (usd * USD_TO_RMB).toFixed(2) : '';
  }

  function confirmAddFee() {
    const type  = document.getElementById('add-fee-type').value;
    const desc  = document.getElementById('add-fee-desc').value.trim();
    const rmb   = parseFloat(document.getElementById('add-fee-rmb').value) || 0;
    const usd   = parseFloat(document.getElementById('add-fee-usd').value) || 0;
    const errEl = document.getElementById('add-fee-error');
    errEl.style.display = 'none';
    if (!desc) { errEl.textContent = 'Description is required.'; errEl.style.display = 'block'; return; }
    if (usd <= 0) { errEl.textContent = 'Please enter an amount.'; errEl.style.display = 'block'; return; }
    _extraFeeCounter++;
    _extraFeeRows.push({ id: _extraFeeCounter, type, desc, rmb, usd });
    document.getElementById('add-fee-modal').remove();
    renderExtraFeeRows();
    calcAdditionalFees();
  }

  function openClearFeeModal(name, label) {
    let modal = document.getElementById('clear-fee-modal');
    if (modal) modal.remove();
    modal = document.createElement('div');
    modal.id = 'clear-fee-modal';
    modal.className = 'modal-overlay';
    modal.style.cssText = 'display:flex;';
    modal.innerHTML = `
      <div class="modal" style="max-width:380px; width:100%;">
        <div class="modal-header">
          <h3 style="margin:0; font-size:16px;">Clear Fee</h3>
          <button onclick="document.getElementById('clear-fee-modal').remove()" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--text-muted);">&times;</button>
        </div>
        <div class="modal-body" style="display:flex;flex-direction:column;gap:16px;">
          <p style="font-size:14px; color:var(--text-muted); margin:0;">
            Clear all values for <strong style="color:var(--text);">${label}</strong>? This cannot be undone.
          </p>
          <div style="display:flex; gap:10px; justify-content:flex-end;">
            <button class="btn btn-ghost" onclick="document.getElementById('clear-fee-modal').remove()">Cancel</button>
            <button class="btn btn-danger-ghost" onclick="confirmClearFee('${name}')">Clear</button>
          </div>
        </div>
      </div>`;
    document.body.appendChild(modal);
  }

  function confirmClearFee(name) {
    ['desc', 'rmb', 'usd'].forEach(field => {
      const el = document.getElementById(`fee-${name}-${field}`);
      if (el) el.value = '';
    });
    document.getElementById('clear-fee-modal').remove();
    calcAdditionalFees();
    autoSaveWorkbook();
  }

  function openDeleteExtraFeeModal(id) {
    const row = _extraFeeRows.find(r => r.id === id);
    if (!row) return;
    const label = `${FEE_TYPE_LABELS[row.type]}${row.desc ? ': ' + row.desc : ''}`;
    let modal = document.getElementById('clear-fee-modal');
    if (modal) modal.remove();
    modal = document.createElement('div');
    modal.id = 'clear-fee-modal';
    modal.className = 'modal-overlay';
    modal.style.cssText = 'display:flex;';
    modal.innerHTML = `
      <div class="modal" style="max-width:380px; width:100%;">
        <div class="modal-header">
          <h3 style="margin:0; font-size:16px;">Remove Fee</h3>
          <button onclick="document.getElementById('clear-fee-modal').remove()" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--text-muted);">&times;</button>
        </div>
        <div class="modal-body" style="display:flex;flex-direction:column;gap:16px;">
          <p style="font-size:14px; color:var(--text-muted); margin:0;">
            Remove <strong style="color:var(--text);">${label}</strong>? This cannot be undone.
          </p>
          <div style="display:flex; gap:10px; justify-content:flex-end;">
            <button class="btn btn-ghost" onclick="document.getElementById('clear-fee-modal').remove()">Cancel</button>
            <button class="btn btn-danger-ghost" onclick="removeExtraFeeRow(${id})">Remove</button>
          </div>
        </div>
      </div>`;
    document.body.appendChild(modal);
  }

  function removeExtraFeeRow(id) {
    document.getElementById('clear-fee-modal')?.remove();
    _extraFeeRows = _extraFeeRows.filter(r => r.id !== id);
    renderExtraFeeRows();
    calcAdditionalFees();
  }

  function renderExtraFeeRows() {
    // Workbook tab — editable inputs
    const wb = document.getElementById('extra-fee-rows');
    if (wb) {
      wb.innerHTML = _extraFeeRows.map(r => {
        const isDesign = r.type === 'design';
        const typeLabel = FEE_TYPE_LABELS[r.type];
        const descEsc = r.desc.replace(/"/g, '&quot;');
        return `<tr id="extra-fee-tr-${r.id}">
          <td style="padding:6px 12px; font-size:13px; color:var(--text-muted); white-space:nowrap;">${typeLabel}</td>
          <td style="padding:4px 8px;">
            <input type="text" class="form-input" placeholder="Description…" value="${descEsc}"
              oninput="updateExtraFeeField(${r.id},'desc',this.value)" style="width:100%;" autocomplete="off" />
          </td>
          <td style="padding:4px 8px;">
            ${isDesign
              ? `<span style="font-size:12px;color:var(--text-muted);font-style:italic;padding:0 8px;">USD only</span>`
              : `<div class="currency-prefix currency-rmb"><input type="number" step="0.01" min="0" placeholder="0.00"
                  value="${r.rmb || ''}" oninput="updateExtraFeeField(${r.id},'rmb',this.value)"
                  style="width:100%;" /></div>`}
          </td>
          <td style="padding:4px 8px;">
            <div class="currency-prefix currency-usd"><input type="number" step="0.01" min="0" placeholder="0.00"
              value="${r.usd || ''}" oninput="updateExtraFeeField(${r.id},'usd',this.value)"
              style="width:100%;" /></div>
          </td>
          <td style="padding:4px 8px; text-align:center;">
            <span class="remove-tier" onclick="openDeleteExtraFeeModal(${r.id})" title="Remove">&times;</span>
          </td>
        </tr>`;
      }).join('');
    }

    // Pricing tab — read-only display
    const pr = document.getElementById('pricing-extra-fee-rows');
    if (pr) {
      pr.innerHTML = _extraFeeRows.map(r => {
        const rmbFmt = r.rmb > 0 ? '¥' + r.rmb.toLocaleString('en-US', {minimumFractionDigits:2}) : '—';
        const usdFmt = r.usd > 0 ? '$' + r.usd.toLocaleString('en-US', {minimumFractionDigits:2}) : '—';
        return `<tr>
          <td style="padding:6px 12px; font-size:13px; color:var(--text-muted); white-space:nowrap;">${FEE_TYPE_LABELS[r.type]}</td>
          <td style="padding:6px 12px; font-size:13px;">${r.desc || ''}</td>
          <td style="padding:6px 12px; text-align:right; font-size:13px;">${rmbFmt}</td>
          <td style="padding:6px 12px; text-align:right; font-size:13px; font-weight:600;">${usdFmt}</td>
        </tr>`;
      }).join('');
    }
  }

  function updateExtraFeeField(id, field, value) {
    const row = _extraFeeRows.find(r => r.id === id);
    if (!row) return;
    if (field === 'desc') {
      row.desc = value;
    } else if (field === 'rmb') {
      row.rmb = parseFloat(value) || 0;
      row.usd = row.rmb > 0 ? parseFloat((row.rmb / USD_TO_RMB).toFixed(2)) : 0;
      // Update the USD input without re-rendering
      const tr = document.getElementById(`extra-fee-tr-${id}`);
      if (tr) { const usdIn = tr.querySelectorAll('input[type="number"]')[1]; if (usdIn) usdIn.value = row.usd || ''; }
    } else if (field === 'usd') {
      row.usd = parseFloat(value) || 0;
      row.rmb = row.usd > 0 ? parseFloat((row.usd * USD_TO_RMB).toFixed(2)) : 0;
      const tr = document.getElementById(`extra-fee-tr-${id}`);
      if (tr) { const rmbIn = tr.querySelector('input[type="number"]'); if (rmbIn) rmbIn.value = row.rmb || ''; }
    }
    // Refresh only pricing tab display (don't re-render workbook rows — would lose focus)
    const pr = document.getElementById('pricing-extra-fee-rows');
    if (pr) {
      pr.innerHTML = _extraFeeRows.map(r => {
        const rmbFmt = r.rmb > 0 ? '¥' + r.rmb.toLocaleString('en-US', {minimumFractionDigits:2}) : '—';
        const usdFmt = r.usd > 0 ? '$' + r.usd.toLocaleString('en-US', {minimumFractionDigits:2}) : '—';
        return `<tr>
          <td style="padding:6px 12px;font-size:13px;color:var(--text-muted);white-space:nowrap;">${FEE_TYPE_LABELS[r.type]}</td>
          <td style="padding:6px 12px;font-size:13px;">${r.desc || ''}</td>
          <td style="padding:6px 12px;text-align:right;font-size:13px;">${rmbFmt}</td>
          <td style="padding:6px 12px;text-align:right;font-size:13px;font-weight:600;">${usdFmt}</td></tr>`;
      }).join('');
    }
    calcAdditionalFees();
  }

  function convertFee(name, from) {
    const rmbEl = document.getElementById(`fee-${name}-rmb`);
    const usdEl = document.getElementById(`fee-${name}-usd`);
    if (!rmbEl || !usdEl) return;
    if (from === 'rmb') {
      const rmb = parseFloat(rmbEl.value) || 0;
      usdEl.value = rmb > 0 ? (rmb / USD_TO_RMB).toFixed(2) : '';
    } else {
      const usd = parseFloat(usdEl.value) || 0;
      rmbEl.value = usd > 0 ? (usd * USD_TO_RMB).toFixed(2) : '';
    }
    calcAdditionalFees();
  }

  function toggleSection(card) {
    card.classList.toggle('collapsed');
  }

  function calcAdditionalFees() {
    const get    = id => parseFloat(document.getElementById(id)?.value) || 0;
    const sample  = get('fee-sample-usd');
    const tooling = get('fee-tooling-usd');
    const die     = get('fee-die-usd');
    const plate   = get('fee-plate-usd');
    const design  = get('fee-design-usd');
    const sampleRmb  = get('fee-sample-rmb');
    const toolingRmb = get('fee-tooling-rmb');
    const dieRmb     = get('fee-die-rmb');
    const plateRmb   = get('fee-plate-rmb');

    const extraUsd = _extraFeeRows.reduce((s, r) => s + r.usd, 0);
    const extraRmb = _extraFeeRows.reduce((s, r) => s + r.rmb, 0);
    const totalUsd = sample + tooling + die + plate + design + extraUsd;
    const totalRmb = sampleRmb + toolingRmb + dieRmb + plateRmb + extraRmb;

    const fmtUsd = v => v > 0 ? '$' + v.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : '—';
    const fmtRmb = v => v > 0 ? '¥' + v.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : '—';

    // Sync descriptions to pricing tab description cells
    ['sample','tooling','die','plate','design'].forEach(id => {
      const d = document.getElementById(`fee-${id}-desc`)?.value.trim() || '';
      const el = document.getElementById(`pricing-fee-${id}-desc`);
      if (el) el.textContent = d;
    });

    document.getElementById('pricing-fee-sample').textContent      = fmtUsd(sample);
    document.getElementById('pricing-fee-sample-rmb').textContent  = fmtRmb(sampleRmb);
    document.getElementById('pricing-fee-tooling').textContent     = fmtUsd(tooling);
    document.getElementById('pricing-fee-tooling-rmb').textContent = fmtRmb(toolingRmb);
    document.getElementById('pricing-fee-die').textContent         = fmtUsd(die);
    document.getElementById('pricing-fee-die-rmb').textContent     = fmtRmb(dieRmb);
    document.getElementById('pricing-fee-plate').textContent       = fmtUsd(plate);
    document.getElementById('pricing-fee-plate-rmb').textContent   = fmtRmb(plateRmb);
    document.getElementById('pricing-fee-design').textContent      = fmtUsd(design);
    document.getElementById('pricing-fee-total').textContent       = fmtUsd(totalUsd);
    document.getElementById('pricing-fee-total-rmb').textContent   = fmtRmb(totalRmb);

    // Grand total per tier
    const tbody = document.getElementById('pricing-grand-total-body');
    if (!tbody) return;
    tbody.innerHTML = '';
    document.querySelectorAll('#tier-body tr').forEach(row => {
      const qty   = parseFloat(row.dataset.qty);
      const price = parseFloat(row.dataset.price);
      if (isNaN(qty) || isNaN(price)) return;
      const tierTotal = qty * (price / USD_TO_RMB);
      const grand = tierTotal + totalUsd;
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td style="padding:8px 12px; font-size:13px;">${qty.toLocaleString()}</td>
        <td style="padding:8px 12px; text-align:right; font-size:13px;">$${tierTotal.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})}</td>
        <td style="padding:8px 12px; text-align:right; font-size:13px; color:var(--text-muted);">${fmtUsd(totalUsd)}</td>
        <td style="padding:8px 12px; text-align:right; font-size:13px; font-weight:700; color:var(--accent);">$${grand.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})}</td>
      `;
      tbody.appendChild(tr);
    });

    if (!_filling) autoSaveWorkbook();
  }

  /* ── Field Fill State ───────────────────────────────────────────────────── */
  function updateFilled(el) {
    const field = el.closest('.field, .dim-field, .karen-cell');
    if (!field) return;
    field.classList.toggle('field-filled', el.value.trim() !== '');
  }

  document.addEventListener('input', e => {
    if (e.target.matches('input, textarea, select')) updateFilled(e.target);
  });

  /* ── API Layer ────────────────────────────────────────────────────────── */
  const API_URL = 'api.php';

  async function apiCall(action, data = null) {
    try {
      const opts = data
        ? { method: 'POST', headers: {'Content-Type':'application/json', 'Accept':'application/json'}, body: JSON.stringify(data) }
        : { method: 'GET', headers: {'Accept':'application/json'} };
      const res = await fetch(`${API_URL}?action=${action}`, opts);
      if (res.status === 401) { window.location.href = 'login.php'; return { success: false }; }
      const json = await res.json();
      if (json.success) {
        // Cache to LocalStorage
        saveToLocalStorage();
      }
      return json;
    } catch (e) {
      console.warn('API call failed, using local data:', e);
      return { success: false, error: e.message };
    }
  }

  function saveToLocalStorage() {
    try {
      localStorage.setItem('ms_clientData', JSON.stringify(clientData));
      localStorage.setItem('ms_workbookDetail', JSON.stringify(workbookDetail));
      localStorage.setItem('ms_dbClientMap', JSON.stringify(dbClientMap));
      localStorage.setItem('ms_dbWorkbookMap', JSON.stringify(dbWorkbookMap));
      localStorage.setItem('ms_lastSaved', new Date().toISOString());
    } catch(e) { console.warn('LocalStorage save failed:', e); }
  }

  function loadFromLocalStorage() {
    try {
      const cd = localStorage.getItem('ms_clientData');
      const wd = localStorage.getItem('ms_workbookDetail');
      const cm = localStorage.getItem('ms_dbClientMap');
      const wm = localStorage.getItem('ms_dbWorkbookMap');
      if (cd) Object.assign(clientData, JSON.parse(cd));
      if (wd) {
        const parsed = JSON.parse(wd);
        // Skip empty arrays from bad saves
        for (const [k, v] of Object.entries(parsed)) {
          if (v && !Array.isArray(v) && typeof v === 'object' && v.client) {
            workbookDetail[k] = v;
          }
        }
      }
      if (cm) Object.assign(dbClientMap, JSON.parse(cm));
      if (wm) Object.assign(dbWorkbookMap, JSON.parse(wm));
      return !!cd;
    } catch(e) { return false; }
  }

  // Maps local client names to DB ids, and local "client|wbId" to DB workbook ids
  let dbClientMap = {};
  let dbWorkbookMap = {};
  // Stores per-client detail fields (email, phone, etc.)
  let clientDetails = {};

  // Tri-state return: true = DB loaded with workbooks, 'empty' = DB reachable but empty,
  // false = API call failed (network/server). Only 'empty' should trigger seeding.
  async function loadFromDatabase() {
    const result = await apiCall('get_all_data');
    if (!result.success) return false; // API failure — DO NOT seed, data may still exist

    // Only clear local data if DB has workbooks, otherwise keep hardcoded sample data
    const hasWorkbooks = result.workbooks && result.workbooks.length > 0;

    if (hasWorkbooks) {
      for (const key of Object.keys(clientData)) delete clientData[key];
      for (const key of Object.keys(workbookDetail)) delete workbookDetail[key];
    }

    // Rebuild client list (always update client map)
    result.clients.forEach(c => {
      dbClientMap[c.name] = c.id;
      if (!clientData[c.name]) clientData[c.name] = [];
      clientDetails[c.name] = {
        id: c.id,
        email: c.email || '',
        phone: c.phone || '',
        primary_contact: c.primary_contact || '',
        // Optional secondary POC — null/blank when not set, just like the
        // primary fields. Always defined locally so input handlers don't
        // have to existence-check before writing.
        email2:           c.email2           || '',
        phone2:           c.phone2           || '',
        primary_contact2: c.primary_contact2 || '',
        billing_address: c.billing_address || '',
        shipping_address: c.shipping_address || '',
        notes: c.notes || '',
        account_manager:   c.account_manager   || '',
        salesperson:       c.salesperson       || '',
        operations_person: c.operations_person || '',
        // Per-role commission rate overrides. NULL on the server → '' here →
        // empSelect renders an empty input → server falls back to the role's
        // default rate (AM/SP → 0%, Operations → 5%).
        account_manager_pct: (c.account_manager_pct === null || c.account_manager_pct === undefined) ? '' : c.account_manager_pct,
        salesperson_pct:     (c.salesperson_pct     === null || c.salesperson_pct     === undefined) ? '' : c.salesperson_pct,
        operations_pct:      (c.operations_pct      === null || c.operations_pct      === undefined) ? '' : c.operations_pct
      };
    });

    if (!hasWorkbooks) return 'empty'; // DB reachable but no workbooks — safe to seed

    // Rebuild workbook data
    result.workbooks.forEach(wb => {
      let flowStep = parseInt(wb.flow_step) || 0;
      const flow = {};
      flowSteps.forEach((s, i) => { flow[s] = i < flowStep; });

      const item = {
        id: parseInt(wb.id),
        product: wb.product_name,
        description: wb.description || '',
        dateCreated: new Date(wb.created_at).toLocaleDateString('en-GB', {day:'numeric', month:'short', year:'2-digit'}),
        dateSubmitted: '',
        flow: flow
      };

      if (!clientData[wb.client_name]) clientData[wb.client_name] = [];
      clientData[wb.client_name].push(item);

      // Rebuild detail if stored (skip empty arrays from bad saves)
      const detail = wb.detail;
      if (detail && !Array.isArray(detail) && typeof detail === 'object' && Object.keys(detail).length > 0) {
        workbookDetail[`${wb.client_name}|${wb.id}`] = detail;
      }

      dbWorkbookMap[`${wb.client_name}|${wb.id}`] = wb.id;
    });

    // Once we've confirmed the DB has workbooks, lock out seedDatabase permanently
    // on this device. This prevents duplication if a future get_all_data call
    // transiently returns an empty workbook list.
    try { localStorage.setItem('ms_db_seeded', '1'); } catch(e) {}

    // Rebuild sidebar
    rebuildSidebar();
    saveToLocalStorage();
    return true;
  }

  /* ── Starred Clients ─────────────────────────────────────────────────── */
  let _starredClients = new Set(JSON.parse(localStorage.getItem('ms_starred_clients') || '[]'));

  function toggleNavSection(sectionId) {
    const section = document.getElementById(sectionId);
    if (!section) return;
    section.classList.toggle('collapsed');
    // Persist collapsed state
    const collapsed = JSON.parse(localStorage.getItem('ms_nav_collapsed') || '{}');
    collapsed[sectionId] = section.classList.contains('collapsed');
    localStorage.setItem('ms_nav_collapsed', JSON.stringify(collapsed));
  }

  function toggleStarClient(name, e) {
    e.preventDefault();
    e.stopPropagation();
    if (_starredClients.has(name)) {
      _starredClients.delete(name);
    } else {
      _starredClients.add(name);
    }
    localStorage.setItem('ms_starred_clients', JSON.stringify([..._starredClients]));
    rebuildSidebar();
    // Re-apply active state
    const hash = location.hash;
    const m = hash.match(/^#\/client\/([^/]+)/);
    if (m) updateSidebarActive(decodeURIComponent(m[1]));
  }

  function restoreNavSectionStates() {
    const collapsed = JSON.parse(localStorage.getItem('ms_nav_collapsed') || '{}');
    Object.entries(collapsed).forEach(([id, isCollapsed]) => {
      const el = document.getElementById(id);
      if (!el) return;
      el.classList.toggle('collapsed', isCollapsed);
    });
  }

  function rebuildSidebar() {
    const searchEl = document.getElementById('sidebar-search');
    if (searchEl) { searchEl.textContent = ''; document.getElementById('sidebar-search-ph').style.display = ''; }
    const sorted = Object.keys(clientData).sort();

    // ── Starred list ──
    const starredList = document.getElementById('starred-list');
    starredList.innerHTML = '';
    const starredSection = document.getElementById('nav-section-starred');
    const starredNames = sorted.filter(n => _starredClients.has(n));
    if (starredNames.length === 0) {
      starredSection.style.display = 'none';
    } else {
      starredSection.style.display = '';
      starredNames.forEach(name => starredList.appendChild(makeClientNavItem(name)));
    }
    // Starred/Clients do not show badges — badges are reserved for actionable items only
    const starredBadge = document.getElementById('badge-starred');
    if (starredBadge) { starredBadge.textContent = ''; starredBadge.classList.remove('attention'); }

    // ── Full client list ──
    const clientList = document.getElementById('client-list');
    clientList.innerHTML = '';
    sorted.forEach(name => clientList.appendChild(makeClientNavItem(name)));
    const clientBadge = document.getElementById('badge-clients');
    if (clientBadge) { clientBadge.textContent = ''; clientBadge.classList.remove('attention'); }

    // ── Samples nav ──
    rebuildSamplesNav();

    // ── Orders nav ──
    rebuildOrdersNav();

    // Rebuild modal dropdown
    const select = document.getElementById('modal-client');
    if (select) {
      const firstOpt = select.options[0] ? select.options[0].cloneNode(true) : null;
      select.innerHTML = '';
      if (firstOpt) select.appendChild(firstOpt);
      sorted.forEach(name => {
        const opt = document.createElement('option');
        opt.textContent = name;
        select.appendChild(opt);
      });
    }
  }

  function filterSidebarSearch(query) {
    const q = query.trim().toLowerCase();
    const starredSection = document.getElementById('nav-section-starred');
    const clientSection = document.getElementById('nav-section-clients');

    // Filter starred list
    document.querySelectorAll('#starred-list .nav-item').forEach(el => {
      const name = el.querySelector('span')?.textContent?.toLowerCase() || '';
      el.style.display = (!q || name.includes(q)) ? '' : 'none';
    });

    // Filter client list
    document.querySelectorAll('#client-list .nav-item').forEach(el => {
      const name = el.querySelector('span')?.textContent?.toLowerCase() || '';
      el.style.display = (!q || name.includes(q)) ? '' : 'none';
    });

    // Expand clients section when searching so results are visible
    if (q) {
      clientSection.classList.remove('collapsed');
      if (starredSection.style.display !== 'none') starredSection.classList.remove('collapsed');
    }
  }

  /* ── Recent Nav ─────────────────────────────────────────────────────── */
  const RECENT_NAV_KEY = 'ms_recent_nav';
  const RECENT_NAV_MAX = 10;

  function getRecentNav() {
    try { return JSON.parse(localStorage.getItem(RECENT_NAV_KEY) || '[]'); } catch { return []; }
  }

  function addRecentNav(item) {
    let list = getRecentNav();
    // Remove duplicate
    list = list.filter(r => !(r.type === item.type && r.label === item.label && r.href === item.href));
    list.unshift(item);
    if (list.length > RECENT_NAV_MAX) list = list.slice(0, RECENT_NAV_MAX);
    localStorage.setItem(RECENT_NAV_KEY, JSON.stringify(list));
  }

  function showRecentNav() {
    const dropdown = document.getElementById('sidebar-recent-dropdown');
    if (!dropdown) return;
    const list = getRecentNav();
    if (!list.length) { dropdown.style.display = 'none'; return; }
    dropdown.innerHTML = list.map((r, i) => {
      const avatarClient = r.type === 'workbook' ? r.sub : r.label;
      const avatar = clientAvatarHTML(avatarClient, 20);
      return `
        <a href="${r.href}" onclick="hideRecentNav(); resetSidebarSearch();" style="display:flex; align-items:center; gap:8px; padding:7px 12px; font-size:12px; color:var(--text); text-decoration:none; border-radius:6px; margin:2px 4px;">
          ${avatar}
          <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${r.label}</span>
          ${r.sub ? `<span style="font-size:11px; color:var(--text-muted); margin-left:auto; flex-shrink:0; padding-left:6px;">${r.sub}</span>` : ''}
        </a>`;
    }).join('');
    dropdown.style.display = 'block';
  }

  function hideRecentNav() {
    const dropdown = document.getElementById('sidebar-recent-dropdown');
    if (dropdown) dropdown.style.display = 'none';
  }

  function resetSidebarSearch() {
    const el = document.getElementById('sidebar-search');
    const ph = document.getElementById('sidebar-search-ph');
    if (el) el.textContent = '';
    if (ph) ph.style.display = '';
    filterSidebarSearch('');
  }

  function getClientLogo(clientName) {
    const workbooks = clientData[clientName] || [];
    // Prefer dedicated clientLogo first
    for (const wb of workbooks) {
      const detail = workbookDetail[`${clientName}|${wb.id}`];
      if (detail && detail.clientLogo) return detail.clientLogo;
    }
    // Fall back to first art image
    for (const wb of workbooks) {
      const detail = workbookDetail[`${clientName}|${wb.id}`];
      if (detail && detail.artImages && detail.artImages.length > 0) return detail.artImages[0];
    }
    return null;
  }

  function clientAvatarHTML(clientName, size = 20) {
    const logo = getClientLogo(clientName);
    if (logo) {
      return `<img src="${logo}" style="width:${size}px; height:${size}px; border-radius:4px; object-fit:contain; flex-shrink:0; background:var(--surface);" />`;
    }
    const initials = clientName.trim().charAt(0).toUpperCase();
    return `<span style="width:${size}px; height:${size}px; border-radius:4px; background:var(--accent); color:#fff; font-size:${Math.round(size*0.55)}px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0;">${initials}</span>`;
  }

  function makeClientNavItem(name) {
    const a = document.createElement('a');
    a.className = 'nav-item';
    a.href = `#/client/${encodeURIComponent(name)}`;

    const avatar = document.createElement('span');
    avatar.innerHTML = clientAvatarHTML(name, 20);
    avatar.style.display = 'flex';
    avatar.style.flexShrink = '0';
    a.appendChild(avatar.firstElementChild || avatar);

    const span = document.createElement('span');
    span.textContent = name;
    span.style.flex = '1';
    span.style.overflow = 'hidden';
    span.style.textOverflow = 'ellipsis';
    span.style.whiteSpace = 'nowrap';
    a.appendChild(span);

    const starBtn = document.createElement('button');
    starBtn.className = 'nav-star-btn' + (_starredClients.has(name) ? ' starred' : '');
    starBtn.title = _starredClients.has(name) ? 'Unstar' : 'Star';
    starBtn.textContent = '★';
    starBtn.addEventListener('click', (e) => toggleStarClient(name, e));
    a.appendChild(starBtn);

    const delBtn = document.createElement('button');
    delBtn.className = 'client-delete-btn';
    delBtn.title = 'Delete client';
    delBtn.textContent = '✕';
    delBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      openDeleteClientModal(name);
    });
    a.appendChild(delBtn);

    return a;
  }

  function flowToStep(flow) {
    let step = 0;
    for (let i = 0; i < flowSteps.length; i++) {
      if (flow[flowSteps[i]]) step = i + 1;
      else break;
    }
    return step;
  }

  /* ── Sample Data (fallback) ────────────────────────────────────────── */
  let clientData = {
    'BAM': [
      { id: 1, product: 'Custom Tote Bag', description: 'Eco-friendly cotton tote with screen print logo', dateCreated: '15 Jan 25', dateSubmitted: '20 Jan 25', flow: { prepChina: true, chinaSubmits: true, shippingDims: true, quoteClient: true, clientApproved: true, officeInvoice: true, confirmedPayment: false, orderChina: false } },
      { id: 2, product: 'Branded Pen Set', description: 'Metal ballpoint pen with laser engraving', dateCreated: '22 Jan 25', dateSubmitted: '', flow: { prepChina: true, chinaSubmits: true, shippingDims: false, quoteClient: false, clientApproved: false, officeInvoice: false, confirmedPayment: false, orderChina: false } },
      { id: 3, product: 'Event Lanyard', description: 'Polyester lanyard with safety clip and badge holder', dateCreated: '5 Feb 25', dateSubmitted: '', flow: { prepChina: true, chinaSubmits: true, shippingDims: true, quoteClient: true, clientApproved: false, officeInvoice: false, confirmedPayment: false, orderChina: false } },
      { id: 4, product: 'USB Flash Drive', description: '16GB custom shaped USB with logo print', dateCreated: '12 Feb 25', dateSubmitted: '', flow: { prepChina: true, chinaSubmits: false, shippingDims: false, quoteClient: false, clientApproved: false, officeInvoice: false, confirmedPayment: false, orderChina: false } },
    ],
    'Bloom': [
      { id: 1, product: 'Ceramic Mug', description: 'White ceramic 11oz mug with full wrap print', dateCreated: '3 Jan 25', dateSubmitted: '10 Jan 25', flow: { prepChina: true, chinaSubmits: true, shippingDims: true, quoteClient: true, clientApproved: true, officeInvoice: true, confirmedPayment: true, orderChina: true } },
      { id: 2, product: 'Seed Paper Card', description: 'Plantable greeting card with wildflower seeds', dateCreated: '18 Jan 25', dateSubmitted: '', flow: { prepChina: true, chinaSubmits: true, shippingDims: true, quoteClient: true, clientApproved: true, officeInvoice: false, confirmedPayment: false, orderChina: false } },
      { id: 3, product: 'Bamboo Notebook', description: 'A5 hardcover notebook with bamboo front cover', dateCreated: '2 Feb 25', dateSubmitted: '', flow: { prepChina: true, chinaSubmits: false, shippingDims: false, quoteClient: false, clientApproved: false, officeInvoice: false, confirmedPayment: false, orderChina: false } },
    ],
    'Candy Pan': [
      { id: 1, product: 'Candy Tin Box', description: 'Custom printed tin with hinged lid', dateCreated: '10 Jan 25', dateSubmitted: '15 Jan 25', flow: { prepChina: true, chinaSubmits: true, shippingDims: true, quoteClient: true, clientApproved: true, officeInvoice: true, confirmedPayment: true, orderChina: true } },
      { id: 2, product: 'Paper Straw Set', description: 'Biodegradable paper straws in custom wrapper', dateCreated: '20 Jan 25', dateSubmitted: '', flow: { prepChina: true, chinaSubmits: true, shippingDims: true, quoteClient: true, clientApproved: false, officeInvoice: false, confirmedPayment: false, orderChina: false } },
      { id: 3, product: 'Gift Bag', description: 'Kraft paper bag with ribbon handles', dateCreated: '1 Feb 25', dateSubmitted: '', flow: { prepChina: true, chinaSubmits: true, shippingDims: true, quoteClient: true, clientApproved: true, officeInvoice: true, confirmedPayment: false, orderChina: false } },
    ],
    'Fresh Her': [
      { id: 1, product: 'Glass Water Bottle', description: 'Borosilicate glass bottle with silicone sleeve', dateCreated: '8 Jan 25', dateSubmitted: '', flow: { prepChina: true, chinaSubmits: true, shippingDims: true, quoteClient: true, clientApproved: true, officeInvoice: false, confirmedPayment: false, orderChina: false } },
      { id: 2, product: 'Yoga Mat', description: 'TPE eco yoga mat with custom print', dateCreated: '20 Jan 25', dateSubmitted: '', flow: { prepChina: true, chinaSubmits: true, shippingDims: false, quoteClient: false, clientApproved: false, officeInvoice: false, confirmedPayment: false, orderChina: false } },
    ],
    'Kids United': [
      { id: 1, product: 'Coloring Book', description: '32-page activity book with custom illustrations', dateCreated: '5 Jan 25', dateSubmitted: '12 Jan 25', flow: { prepChina: true, chinaSubmits: true, shippingDims: true, quoteClient: true, clientApproved: true, officeInvoice: true, confirmedPayment: true, orderChina: true } },
      { id: 2, product: 'Sticker Sheet', description: 'Die-cut vinyl stickers on A4 sheet', dateCreated: '15 Jan 25', dateSubmitted: '', flow: { prepChina: true, chinaSubmits: true, shippingDims: true, quoteClient: true, clientApproved: true, officeInvoice: true, confirmedPayment: false, orderChina: false } },
      { id: 3, product: 'Drawstring Bag', description: 'Polyester drawstring backpack with logo', dateCreated: '28 Jan 25', dateSubmitted: '', flow: { prepChina: true, chinaSubmits: false, shippingDims: false, quoteClient: false, clientApproved: false, officeInvoice: false, confirmedPayment: false, orderChina: false } },
    ],
    'Nut Garden': [
      { id: 1, product: 'Packaging Box', description: 'Corrugated box with full color print and window', dateCreated: '12 Jan 25', dateSubmitted: '', flow: { prepChina: true, chinaSubmits: true, shippingDims: true, quoteClient: true, clientApproved: false, officeInvoice: false, confirmedPayment: false, orderChina: false } },
      { id: 2, product: 'Jar Label', description: 'Waterproof vinyl label with metallic foil accent', dateCreated: '25 Jan 25', dateSubmitted: '2 Feb 25', flow: { prepChina: true, chinaSubmits: true, shippingDims: true, quoteClient: true, clientApproved: true, officeInvoice: true, confirmedPayment: true, orderChina: true } },
      { id: 3, product: 'Wooden Display Stand', description: 'Counter-top birch plywood stand', dateCreated: '3 Feb 25', dateSubmitted: '', flow: { prepChina: true, chinaSubmits: true, shippingDims: true, quoteClient: false, clientApproved: false, officeInvoice: false, confirmedPayment: false, orderChina: false } },
    ],
    'Salt': [
      { id: 1, product: 'Pouch Bag', description: 'Stand-up pouch with zip lock and matte finish', dateCreated: '6 Jan 25', dateSubmitted: '', flow: { prepChina: true, chinaSubmits: true, shippingDims: true, quoteClient: true, clientApproved: true, officeInvoice: false, confirmedPayment: false, orderChina: false } },
      { id: 2, product: 'Spice Grinder', description: 'Ceramic mechanism grinder with branded cap', dateCreated: '19 Jan 25', dateSubmitted: '', flow: { prepChina: true, chinaSubmits: false, shippingDims: false, quoteClient: false, clientApproved: false, officeInvoice: false, confirmedPayment: false, orderChina: false } },
    ],
    'Tweedle Dee': [
      { id: 1, product: 'Plush Toy', description: 'Custom character plush 20cm with embroidered face', dateCreated: '9 Jan 25', dateSubmitted: '16 Jan 25', flow: { prepChina: true, chinaSubmits: true, shippingDims: true, quoteClient: true, clientApproved: true, officeInvoice: true, confirmedPayment: true, orderChina: true } },
      { id: 2, product: 'Party Hat Set', description: 'Cone hats printed full color, pack of 8', dateCreated: '22 Jan 25', dateSubmitted: '', flow: { prepChina: true, chinaSubmits: true, shippingDims: true, quoteClient: true, clientApproved: false, officeInvoice: false, confirmedPayment: false, orderChina: false } },
      { id: 3, product: 'Wristband', description: 'Silicone wristband with debossed logo', dateCreated: '5 Feb 25', dateSubmitted: '', flow: { prepChina: true, chinaSubmits: true, shippingDims: false, quoteClient: false, clientApproved: false, officeInvoice: false, confirmedPayment: false, orderChina: false } },
    ],
  };

  // Detailed workbook data for pre-filling (keyed by "client|id")
  let workbookDetail = {
    'BAM|1': { client: 'BAM', product: 'Custom Tote Bag', desc: 'Eco-friendly cotton tote with screen print logo. Suitable for retail and events.', dimInL: '15', dimInW: '16', dimInH: '4', materials: '12oz Natural Cotton Canvas', pantone: 'PMS 286 C', cmyk: 'C:100 M:72 Y:0 K:18', colorNotes: 'Single color print on both sides', qty: '500', unitPriceRmb: '23.53', leadTime: '30 days after artwork approval', tiers: [{qty:250,price:'27.15'},{qty:500,price:'23.53'},{qty:1000,price:'20.27'}] },
    'BAM|2': { client: 'BAM', product: 'Branded Pen Set', desc: 'Premium metal ballpoint pen with twist mechanism and laser engraved logo.', dimInL: '5.5', dimInW: '0.4', dimInH: '0.4', materials: 'Brass body, chrome plating', pantone: 'PMS 877 C (Silver)', cmyk: '', colorNotes: 'Metallic silver finish, laser engrave logo on barrel', qty: '1000', unitPriceRmb: '13.39', leadTime: '25 days', tiers: [{qty:500,price:'15.20'},{qty:1000,price:'13.39'},{qty:2500,price:'10.86'}] },
    'Bloom|1': { client: 'Bloom', product: 'Ceramic Mug', desc: 'Classic white ceramic mug with full-wrap sublimation print. Dishwasher safe.', dimInL: '3.75', dimInW: '3.75', dimInH: '3.85', materials: 'White ceramic, AAA grade', pantone: 'PMS 348 C, PMS 7462 C', cmyk: 'Full color CMYK sublimation', colorNotes: 'Full wrap print, no bleed at handle', qty: '300', unitPriceRmb: '32.58', leadTime: '21 days after proof approval', tiers: [{qty:100,price:'39.82'},{qty:300,price:'32.58'},{qty:500,price:'28.24'}] },
    'Candy Pan|1': { client: 'Candy Pan', product: 'Candy Tin Box', desc: 'Custom printed hinged tin for candy packaging. Food-safe interior coating.', dimInL: '4', dimInW: '3', dimInH: '1.5', materials: 'Tinplate with food-safe lacquer interior', pantone: 'PMS 1787 C, PMS 109 C', cmyk: 'C:0 M:80 Y:5 K:0, C:0 M:6 Y:95 K:0', colorNotes: 'Glossy finish exterior, white interior', qty: '2000', unitPriceRmb: '8.69', leadTime: '35 days', tiers: [{qty:1000,price:'10.50'},{qty:2000,price:'8.69'},{qty:5000,price:'6.88'}] },
  };

  /* ── Status Bar ──────────────────────────────────────────────────────────── */
  const flowSteps = ['quoteChina', 'quoteSubmitted', 'quoteClient', 'clientApproved', 'officeInvoice', 'confirmedPayment', 'orderChina'];
  const flowLabels = ['Quote', 'Quote Submitted', 'Quote to Client', 'Client Approved', 'Office Invoice', 'Confirmed Payment', 'Order'];
  const flowLabelsShort = ['Quote', 'Submitted', 'Quote', 'Approved', 'Invoice', 'Payment', 'Order'];
  let currentClient = '';
  let currentWorkbookId = '';
  let _wbBackHash  = null;  // set before navigating into a workbook from a non-client context
  let _wbBackLabel = null;

  function renderStatusBar(flow) {
    flow = flow || {};  // defensive: never crash on missing flow
    const statusFlow = document.getElementById('status-flow');
    if (!statusFlow) return;
    statusFlow.innerHTML = flowSteps.map((s, i) => `
      <div class="status-step">
        <div class="status-step-bar ${flow[s] ? 'filled' : ''}"></div>
        <span class="status-step-label"><span class="label-full">${flowLabels[i]}</span><span class="label-short">${flowLabelsShort[i]}</span></span>
      </div>
    `).join('');

    // Find current status and next step
    let currentIdx = -1;
    for (let i = flowSteps.length - 1; i >= 0; i--) {
      if (flow[flowSteps[i]]) { currentIdx = i; break; }
    }

    const advanceBtn = document.getElementById('btn-advance');
    const backBtn = document.getElementById('btn-back-step');
    backBtn.disabled = currentIdx < 0;

    if (currentIdx < flowSteps.length - 1) {
      advanceBtn.textContent = flowLabels[currentIdx + 1];
      advanceBtn.disabled = false;
    } else {
      advanceBtn.textContent = 'Completed';
      advanceBtn.disabled = true;
    }

    // Show "Notify Client" button when Quote to Client step is active
    const notifyBtn = document.getElementById('btn-notify-quote');
    if (notifyBtn) {
      const show = !!flow.quoteClient;
      notifyBtn.style.display = show ? 'inline-flex' : 'none';
    }

    // Show "Send to RFQ" button only while status is at Quote Submitted (not advanced to Quote to Client)
    const sendRfqBtn = document.getElementById('btn-send-rfq');
    if (sendRfqBtn) {
      const show = !!flow.quoteSubmitted && !flow.quoteClient;
      sendRfqBtn.style.display = show ? 'inline-flex' : 'none';
      if (show) {
        const detail = workbookDetail[`${currentClient}|${currentWorkbookId}`] || {};
        const sent = !!detail.sentToRfq;
        const label = document.getElementById('btn-send-rfq-label');
        if (label) label.textContent = sent ? '✓ Sent to RFQ' : 'RFQ';
        sendRfqBtn.style.background = sent ? 'var(--accent)' : 'none';
        sendRfqBtn.style.color = sent ? '#fff' : 'var(--accent)';
      }
    }

    // Lock Product Overview tab once Quote to Client (index 2) or beyond
    lockWorkbookTab(currentIdx >= 2);
  }

  /* ── Notification system ─────────────────────────────────────────────────── */
  let _notifyPayload = null; // stores the data ready to send

  function openNotifyModal(triggerType) {
    _notifyPayload = null;

    if (triggerType === 'quote_ready') {
      const detail    = workbookDetail[`${currentClient}|${currentWorkbookId}`] || {};
      const clientDet = clientDetails[currentClient] || {};
      const clientEmail = clientDet.email || '';
      const contactName = clientDet.primary_contact || '';
      const product   = detail.product || document.getElementById('product-name')?.value || 'Product';
      const rfqItems  = (detail.rfqItems || collectRfqItems()).filter(i => i.item || i.qty || i.priceRmb);

      document.getElementById('notify-modal-title').textContent = 'Notify Client — Quote Ready';
      document.getElementById('notify-preview-subject').textContent = `Your Quote is Ready — ${product}`;
      document.getElementById('notify-preview-body').textContent =
        `Hi ${contactName || clientEmail || 'Client'},\n\nYour quote for ${product} is ready for review. ` +
        (rfqItems.length ? `(${rfqItems.length} line item${rfqItems.length !== 1 ? 's' : ''} included)` : '');

      const wbDbId  = dbWorkbookMap[`${currentClient}|${currentWorkbookId}`] || currentWorkbookId;
      const quoteAppUrl = `${window.location.origin}/#/client/${encodeURIComponent(currentClient)}/workbook/${wbDbId}`;

      _notifyPayload = {
        type: 'quote_ready', client_email: clientEmail, contact_name: contactName,
        client_name: currentClient, rate: USD_TO_RMB,
        details: { product, rfqItems, app_url: quoteAppUrl }
      };

      const pn = document.getElementById('notify-portal-notice');
      if (pn) pn.style.display = rfqItems.length > 0 ? 'block' : 'none';

      _renderNotifyRecipients(clientEmail, contactName);
    }

    else if (triggerType === 'order_status') {
      const o = orderData[_currentOrderId];
      if (!o) return;
      const clientDet   = clientDetails[o.clientName] || {};
      const clientEmail = clientDet.email || '';
      const contactName = clientDet.primary_contact || '';
      const statusMap   = { draft:'order_confirmed', confirmed:'order_confirmed', in_production:'order_in_production', complete:'order_complete' };
      const notifType   = statusMap[o.status] || 'order_confirmed';
      const labelMap    = { order_confirmed:'Order Confirmed', order_in_production:'Order In Production', order_complete:'Order Complete' };
      const label       = labelMap[notifType];
      const tot         = orderTotals(o);

      // Build full entries with rfqItems for detailed emails and portal
      const entriesWithDetail = (o.entries || []).map(e => {
        const key    = `${e.clientName}|${e.workbookId}`;
        const detail = workbookDetail[key] || {};
        const rfqItems = (detail.rfqItems || []).filter(i => i.item || i.qty || i.priceRmb);
        return {
          clientName: e.clientName,
          workbookId: e.workbookId,
          product:    detail.product || `Workbook #${e.workbookId}`,
          rfqItems
        };
      });

      document.getElementById('notify-modal-title').textContent = `Notify Client — ${label}`;
      document.getElementById('notify-preview-subject').textContent = `${label} — ${o.name}`;
      const itemCount = entriesWithDetail.reduce((acc, e) => acc + e.rfqItems.length, 0);
      document.getElementById('notify-preview-body').textContent =
        `Hi ${contactName || clientEmail || 'Client'},\n\nUpdate on ${o.name}` +
        (o.poNumber ? ` (PO: ${o.poNumber})` : '') +
        (itemCount > 0 ? ` · ${itemCount} line item${itemCount !== 1 ? 's' : ''}` : '') +
        (tot.totalUsd > 0 ? ` · $${tot.totalUsd.toLocaleString('en-US', {minimumFractionDigits:2})} USD` : '') + '.';

      const orderAppUrl = `${window.location.origin}/#/order/${_currentOrderId}`;

      _notifyPayload = {
        type: notifType, client_email: clientEmail, contact_name: contactName,
        client_name: o.clientName, rate: USD_TO_RMB,
        details: {
          order_name: o.name, po_number: o.poNumber || '',
          entries: entriesWithDetail,
          app_url: orderAppUrl
        }
      };

      // Show portal notice for order_confirmed
      const pn2 = document.getElementById('notify-portal-notice');
      if (pn2) pn2.style.display = (notifType === 'order_confirmed') ? 'block' : 'none';

      _renderNotifyRecipients(clientEmail, contactName);
    }

    document.getElementById('modal-notify').classList.add('open');
  }

  function _renderNotifyRecipients(clientEmail, contactName) {
    const wrap = document.getElementById('notify-recipients');
    const rows = [];
    if (clientEmail) {
      rows.push(`<div style="display:flex;align-items:center;gap:8px;font-size:13px;">
        <span style="background:var(--accent-glow);border:1px solid var(--accent);border-radius:5px;padding:2px 8px;font-size:11px;font-weight:700;color:var(--accent);">Client</span>
        <span>${contactName ? `${contactName} &lt;${clientEmail}&gt;` : clientEmail}</span>
      </div>`);
    } else {
      rows.push(`<div style="font-size:13px;color:#fb7185;">⚠️ No client email on file — internal only.</div>`);
    }
    rows.push(`<div style="display:flex;align-items:center;gap:8px;font-size:13px;">
      <span style="background:var(--surface2);border:1px solid var(--border);border-radius:5px;padding:2px 8px;font-size:11px;font-weight:700;color:var(--text-muted);">Internal</span>
      <span style="color:var(--text-muted);">jackson@marketsculpt.com, parker@marketsculpt.com</span>
    </div>`);
    wrap.innerHTML = rows.join('');
  }

  function closeNotifyModal() {
    document.getElementById('modal-notify').classList.remove('open');
    _notifyPayload = null;
  }

  async function sendNotification() {
    if (!_notifyPayload) return;
    const btn = document.getElementById('btn-notify-send');
    btn.disabled = true;
    btn.textContent = 'Sending…';
    try {
      const res = await apiCall('send_notification', _notifyPayload);
      if (res.success) {
        // Store portal token so we can poll for change requests later
        if (res.portal_token && _notifyPayload?.type === 'order_confirmed' && _currentOrderId) {
          orderData[_currentOrderId].portalToken = res.portal_token;
          orderData[_currentOrderId].changeRequested = false;
          saveOrders();
        }
        closeNotifyModal();
        const s = document.getElementById('save-status');
        if (s) { s.textContent = '✓ Notification sent'; s.style.color = 'var(--success)'; s.style.opacity = '1'; setTimeout(() => { s.style.opacity = '0'; s.textContent = ''; }, 4000); }
      } else {
        const err = res.results?.internal?.error || res.results?.client?.error || 'Unknown error';
        alert(`Failed to send: ${err}\n\nCheck that the Gmail App Password is correct.`);
      }
    } catch(e) {
      alert('Send failed: ' + e.message);
    }
    btn.disabled = false;
    btn.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Send Notification';
  }

  function _showPortalUrl(url) {
    // Show a modal with the portal link so it can be copied/shared
    const existing = document.getElementById('portal-url-modal');
    if (existing) existing.remove();
    const m = document.createElement('div');
    m.id = 'portal-url-modal';
    m.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:1200;display:flex;align-items:center;justify-content:center;padding:20px;';
    m.innerHTML = `
      <div style="background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:32px;max-width:520px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,0.5);">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
          <div style="width:32px;height:32px;border-radius:50%;background:rgba(39,174,96,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#27ae60" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
          </div>
          <div style="font-size:16px;font-weight:800;color:var(--text);">Notification Sent</div>
        </div>
        <p style="font-size:13px;color:var(--text-muted);margin:0 0 20px;line-height:1.6;">The client email has been sent. A unique approval portal link has been created — share it with your client or use it as a reference.</p>
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-muted);margin-bottom:6px;">Client Portal Link</div>
        <div style="display:flex;gap:8px;align-items:stretch;">
          <input id="portal-url-input" type="text" value="${url}" readonly
            style="flex:1;background:var(--surface2);border:1px solid var(--border);border-radius:8px;padding:10px 12px;font-size:12px;font-family:ui-monospace,monospace;color:var(--text);outline:none;min-width:0;" />
          <button onclick="(function(){const el=document.getElementById('portal-url-input');el.select();document.execCommand('copy');const b=document.getElementById('copy-portal-btn');b.textContent='Copied!';b.style.background='var(--success)';setTimeout(()=>{b.textContent='Copy';b.style.background='var(--accent)';},2000);})()"
            id="copy-portal-btn"
            style="background:var(--accent);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;padding:0 16px;cursor:pointer;font-family:inherit;white-space:nowrap;flex-shrink:0;">
            Copy
          </button>
        </div>
        <div style="display:flex;justify-content:flex-end;margin-top:20px;">
          <button onclick="document.getElementById('portal-url-modal').remove()"
            style="background:none;border:1px solid var(--border);border-radius:8px;color:var(--text-muted);font-size:13px;font-weight:600;padding:8px 18px;cursor:pointer;font-family:inherit;">
            Done
          </button>
        </div>
      </div>`;
    document.body.appendChild(m);
    m.addEventListener('click', e => { if (e.target === m) m.remove(); });
    // Auto-select the URL
    setTimeout(() => { const el = document.getElementById('portal-url-input'); if (el) el.select(); }, 50);
  }

  function lockWorkbookTab(locked) {
    _wbLocked = locked;
    const tab = document.getElementById('wb-tab-workbook');
    if (!tab) return;
    const fields = tab.querySelectorAll('input:not([type="hidden"]), textarea, select');
    fields.forEach(el => {
      el.disabled = locked;
      el.style.opacity = locked ? '0.6' : '';
      el.style.cursor = locked ? 'not-allowed' : '';
    });
    // Also disable RFQ table add/remove buttons and image upload
    tab.querySelectorAll('button:not(.wb-tab)').forEach(btn => {
      btn.disabled = locked;
      btn.style.opacity = locked ? '0.6' : '';
      btn.style.cursor = locked ? 'default' : '';
    });
    // Show/hide lock banner
    let banner = document.getElementById('wb-lock-banner');
    if (locked) {
      if (!banner) {
        banner = document.createElement('div');
        banner.id = 'wb-lock-banner';
        banner.style.cssText = 'background:rgba(251,113,133,0.1); border:1px solid rgba(251,113,133,0.3); color:var(--text-muted); font-size:12px; padding:6px 14px; border-radius:6px; margin-bottom:12px; text-align:center;';
        banner.textContent = '🔒 Fields are locked after Quote to Client. Use ← to go back and unlock.';
        tab.insertBefore(banner, tab.firstChild);
      }
    } else {
      if (banner) banner.remove();
    }
  }

  function revertStatus() {
    const items = clientData[currentClient];
    if (!items) return;
    const item = items.find(i => i.id === parseInt(currentWorkbookId));
    if (!item) return;

    for (let i = flowSteps.length - 1; i >= 0; i--) {
      if (item.flow[flowSteps[i]]) {
        item.flow[flowSteps[i]] = false;
        break;
      }
    }

    renderStatusBar(item.flow);
    const dbId = dbWorkbookMap[`${currentClient}|${currentWorkbookId}`] || currentWorkbookId;
    apiCall('update_flow', { id: dbId, flow_step: flowToStep(item.flow) });
    saveToLocalStorage();
    rebuildRfqNav();  // status moved — may enter/leave RFQ queue
  }

  function advanceStatus() {
    const items = clientData[currentClient];
    if (!items) return;
    const item = items.find(i => i.id === parseInt(currentWorkbookId));
    if (!item) return;

    for (let i = 0; i < flowSteps.length; i++) {
      if (!item.flow[flowSteps[i]]) {
        item.flow[flowSteps[i]] = true;
        break;
      }
    }

    renderStatusBar(item.flow);
    // Save flow to DB
    const dbId = dbWorkbookMap[`${currentClient}|${currentWorkbookId}`] || currentWorkbookId;
    apiCall('update_flow', { id: dbId, flow_step: flowToStep(item.flow) });
    saveToLocalStorage();
    rebuildRfqNav();  // status moved — may enter/leave RFQ queue
  }

  /* ── Workbook Tabs ────────────────────────────────────────────────────────── */
  function switchWbTab(tabName, btn) {
    document.querySelectorAll('.wb-tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.wb-tab').forEach(el => el.classList.remove('active'));
    document.getElementById('wb-tab-' + tabName).classList.add('active');
    btn.classList.add('active');
    if (tabName === 'shipping') { syncShippingDims(); syncShippingPalletStats(); calcFreight(); }
    if (tabName === 'pricing')  { renderPricingTab(); }
  }

  /* ── Shipping Calculator ─────────────────────────────────────────────────── */
  const freightMethodRates    = { slow: 12, fast: 14, airupp: 44, directair: 65 };
  const freightMethodDivisors = { slow: 6000, fast: 6000, airupp: 5000, directair: 5000 };

  const FREIGHT_EXCHANGE_RATE = 7.2; // ¥ per $1 USD

  function updateFreightRate() {
    const rawMode = document.getElementById('freight-mode').value;
    const mode = freightMethodRates[rawMode] ? rawMode : 'slow';
    const r = freightMethodRates[mode];
    const rmbEl = document.getElementById('freight-rate-rmb-display');
    const usdEl = document.getElementById('freight-rate-usd-display');
    if (rmbEl) rmbEl.textContent = r.toFixed(2);
    if (usdEl) usdEl.textContent = (r / FREIGHT_EXCHANGE_RATE).toFixed(2);
    renderPricingTab();
  }

  function toggleFreightShipping() {
    const header = document.getElementById('freight-shipping-header');
    const body   = document.getElementById('freight-shipping-body');
    header.classList.toggle('collapsed');
    body.classList.toggle('collapsed');
  }

  /* ── Pricing Tab Summary Renderer ──────────────────────────────────────── */
  function renderPricingTab() {
    // Quote Reference — read live from rfq-body
    const rfqRows = document.querySelectorAll('#rfq-body tr');
    const refEl = document.getElementById('pricing-quote-ref-body');
    if (refEl) {
      if (rfqRows.length === 0) {
        refEl.innerHTML = '<span class="pricing-no-selection">Add items to Quote Details on the Workbook tab.</span>';
      } else {
        let html = `<div style="overflow-x:auto;">
          <table class="pricing-quote-ref-table">
            <thead><tr>
              <th>#</th><th>Item</th>
              <th style="text-align:right;">Qty</th>
              <th style="text-align:right;">Unit (RMB)</th>
              <th style="text-align:right;">Unit (USD)</th>
              <th style="text-align:right;">Total (USD)</th>
              <th>Lead Time</th>
            </tr></thead><tbody>`;
        rfqRows.forEach((row, i) => {
          const inputs = row.querySelectorAll('input:not([type="checkbox"])');
          const item     = inputs[0]?.value || '—';
          const qty      = inputs[1]?.value || '';
          const priceRmb = inputs[2]?.value || '';
          const leadTime = inputs[3]?.value || '';
          const rowId    = row.id.replace('rfq-', '');
          const usd      = document.getElementById(`rfq-usd-${rowId}`)?.textContent   || '—';
          const total    = document.getElementById(`rfq-total-${rowId}`)?.textContent || '—';
          const isSample = row.classList.contains('rfq-sample-row');
          html += `<tr class="${i === 0 ? 'main-row' : ''}">
            <td style="color:var(--text-muted); white-space:nowrap;">
              ${i+1}${isSample ? ' <span style="font-size:10px;color:var(--accent);background:rgba(232,117,26,0.12);padding:1px 6px;border-radius:4px;font-weight:700;">SAMPLE</span>' : ''}
            </td>
            <td style="font-weight:500;">${item}</td>
            <td style="text-align:right;">${qty ? parseInt(qty).toLocaleString('en-US') : '—'}</td>
            <td style="text-align:right; color:var(--text-muted);">${priceRmb ? '¥' + parseFloat(priceRmb).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}) : '—'}</td>
            <td style="text-align:right;">${usd}</td>
            <td style="text-align:right; font-weight:600; color:var(--accent);">${total}</td>
            <td style="color:var(--text-muted);">${leadTime ? leadTime + ' days' : '—'}</td>
          </tr>`;
        });
        html += '</tbody></table></div>';
        refEl.innerHTML = html;
      }
    }

    // Populate collapsed summary from already-computed rfq totals
    const qrsRmb   = document.getElementById('qrs-rmb');
    const qrsUsd   = document.getElementById('qrs-usd');
    const qrsTotal = document.getElementById('qrs-total');
    const qrsLead  = document.getElementById('qrs-lead');
    if (qrsRmb)   qrsRmb.textContent   = document.getElementById('rfq-total-rmb')?.textContent     || '—';
    if (qrsUsd)   qrsUsd.textContent   = document.getElementById('rfq-total-usd-sum')?.textContent || '—';
    if (qrsTotal) qrsTotal.textContent = document.getElementById('rfq-total-usd')?.textContent     || '—';
    if (qrsLead)  qrsLead.textContent  = document.getElementById('rfq-max-lead')?.textContent      || '—';

    // Delivered Cost Summary
    const noSelEl  = document.getElementById('pricing-no-selection-msg');
    const summaryEl = document.getElementById('pricing-summary-view');
    if (!_selectedTierId) {
      if (noSelEl)  noSelEl.style.display  = '';
      if (summaryEl) summaryEl.style.display = 'none';
      return;
    }
    if (noSelEl)  noSelEl.style.display  = 'none';
    if (summaryEl) summaryEl.style.display = '';

    const e = id => document.getElementById(id);
    const fmtUsd = v => v > 0 ? '$' + v.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}) : '—';
    const fmtRmb = v => v > 0 ? '¥' + v.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}) : '—';

    // Tier data from Workbook tab
    const tierRow    = document.getElementById(`wb-tier-${_selectedTierId}`);
    const tierInputs = tierRow?.querySelectorAll('input');
    const tierQty    = parseInt(tierInputs?.[0]?.value) || 0;
    const tierRmb    = parseFloat(tierRow?.dataset.price) || 0;
    const tierUsd    = tierRmb > 0 ? tierRmb / USD_TO_RMB : 0;
    const productTotal = tierQty > 0 && tierUsd > 0 ? tierQty * tierUsd : 0;

    if (e('ps-qty'))          e('ps-qty').textContent          = tierQty > 0 ? tierQty.toLocaleString('en-US') + ' units' : '—';
    if (e('ps-unit-rmb'))     e('ps-unit-rmb').textContent     = fmtRmb(tierRmb);
    if (e('ps-unit-usd'))     e('ps-unit-usd').textContent     = tierUsd > 0 ? '$' + tierUsd.toFixed(4) : '—';
    if (e('ps-product-total')) e('ps-product-total').textContent = fmtUsd(productTotal);

    // Shipping data from freight results
    const mode = document.getElementById('freight-mode')?.value || 'slow';
    const modeNames = { slow: 'Slow Boat', fast: 'Fast Boat', airupp: 'Air + UPS', directair: 'Direct Air' };
    const rateRmb = freightMethodRates[mode] || 0;
    const rateUsd = rateRmb / FREIGHT_EXCHANGE_RATE;
    const weightText   = e('freight-wt-' + mode)?.textContent || '—';
    const chargeableKg = parseFloat(weightText) || 0;
    const shippingRmb  = chargeableKg > 0 ? chargeableKg * rateRmb : 0;
    const shippingUsd  = shippingRmb > 0 ? shippingRmb / FREIGHT_EXCHANGE_RATE : 0;

    if (e('ps-sh-method'))  e('ps-sh-method').textContent  = modeNames[mode] || '—';
    if (e('ps-sh-weight'))  e('ps-sh-weight').textContent  = chargeableKg > 0 ? chargeableKg.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}) + ' kg' : '—';
    if (e('ps-sh-rate'))    e('ps-sh-rate').textContent    = rateRmb > 0 ? `¥${rateRmb} / kg  ($${rateUsd.toFixed(2)}/kg)` : '—';
    if (e('ps-sh-total'))   e('ps-sh-total').textContent   = shippingUsd > 0 ? `${fmtRmb(shippingRmb)}  /  ${fmtUsd(shippingUsd)}` : '—';

    // Grand total
    const grandTotal = productTotal + shippingUsd;
    if (e('ps-grand-total')) e('ps-grand-total').textContent = grandTotal > 0 ? '$' + grandTotal.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}) : '—';
  }

  // Render pallet stats into the shipping tab panel
  function syncShippingPalletStats() {
    const el = document.getElementById('sh-pallet-stats-body');
    if (!el) return;
    const ps = window._palletStats;
    if (!ps) {
      el.innerHTML = '<span style="font-size:12px; color:var(--text-muted); font-style:italic;">Enter outer carton dimensions on the Workbook tab to see pallet stats.</span>';
      return;
    }
    const stat = (val, lbl, accent) =>
      `<div class="sh-pallet-stat">
        <div class="sh-pallet-stat-val${accent ? ' accent' : ''}">${val}</div>
        <div class="sh-pallet-stat-lbl">${lbl}</div>
      </div>`;

    let html = `<div class="sh-pallet-grid">
      ${stat(ps.perLayer,       'outer cartons / layer')}
      ${stat(ps.maxLayers,      'max layers')}
      ${stat(ps.totalPerPallet, 'outer cartons / pallet')}
      ${stat(ps.surfaceUse + '%', 'surface coverage')}
      ${ps.productsPerOuter > 0 ? stat(ps.outerQtyVal, 'inner cartons / outer') + stat(ps.productsPerOuter, 'products / outer carton') : ''}`;

    if (ps.totalCartons > 0) {
      html += `<hr class="sh-pallet-divider">
        <div class="sh-pallet-section-label">Shipment of ${ps.totalCartons} cartons</div>
        ${stat(ps.palletsNeeded, 'pallets needed', true)}
        ${ps.totalInners > 0 ? stat(ps.totalInners, 'total inner cartons') : '<div></div>'}
        ${ps.totalProducts > 0 ? `<div class="sh-pallet-stat" style="grid-column:span 2;"><div class="sh-pallet-stat-val">${ps.totalProducts.toLocaleString()}</div><div class="sh-pallet-stat-lbl">total products</div></div>` : ''}`;
    }
    html += '</div>';
    el.innerHTML = html;
  }

  // Sync outer carton dims/weight display from workbook fields
  function syncShippingDims() {
    const get = id => parseFloat(document.getElementById(id)?.value) || 0;
    const fmt = v => v > 0 ? v.toFixed(2) : '—';

    // Cartons in shipment (from pallet-total-cartons)
    const totalCartons = parseInt(document.getElementById('pallet-total-cartons')?.value) || 0;
    const cartonsEl = document.getElementById('sh-cartons-val');
    if (cartonsEl) cartonsEl.textContent = totalCartons > 0 ? totalCartons.toLocaleString() : '—';

    const lCm  = get('carton-outer-l-cm');
    const wCm  = get('carton-outer-w-cm');
    const hCm  = get('carton-outer-h-cm');
    const lIn  = get('carton-outer-l-in') || (lCm ? lCm / 2.54 : 0);
    const wIn  = get('carton-outer-w-in') || (wCm ? wCm / 2.54 : 0);
    const hIn  = get('carton-outer-h-in') || (hCm ? hCm / 2.54 : 0);
    const wtKg  = get('carton-outer-weight');
    const wtLbs = get('carton-outer-weight-lbs') || (wtKg ? wtKg * 2.20462 : 0);

    document.getElementById('sh-l-cm').textContent  = fmt(lCm);
    document.getElementById('sh-l-in').textContent  = fmt(lIn);
    document.getElementById('sh-w-cm').textContent  = fmt(wCm);
    document.getElementById('sh-w-in').textContent  = fmt(wIn);
    document.getElementById('sh-h-cm').textContent  = fmt(hCm);
    document.getElementById('sh-h-in').textContent  = fmt(hIn);
    document.getElementById('sh-wt-kg').textContent  = fmt(wtKg);
    document.getElementById('sh-wt-lbs').textContent = fmt(wtLbs);

    const innerPerOuter  = parseInt(document.getElementById('carton-outer-count')?.value) || 0;
    const unitsPerInner  = parseInt(document.getElementById('carton-inner-count')?.value) || 0;
    const unitsPerOuter  = innerPerOuter > 0 && unitsPerInner > 0 ? innerPerOuter * unitsPerInner : 0;
    const ipo = document.getElementById('sh-inner-per-outer');
    const upo = document.getElementById('sh-units-per-outer');
    if (ipo) ipo.textContent = innerPerOuter > 0 ? innerPerOuter : '—';
    if (upo) upo.textContent = unitsPerOuter > 0 ? unitsPerOuter : '—';
  }

  function calcFreight() {
    syncShippingDims();

    const get = id => parseFloat(document.getElementById(id)?.value) || 0;

    // Dims pulled from workbook outer carton (already in cm)
    const lCm      = get('carton-outer-l-cm');
    const wCm      = get('carton-outer-w-cm');
    const hCm      = get('carton-outer-h-cm');
    const actualKg = get('carton-outer-weight');  // kg per carton

    const rawMode  = document.getElementById('freight-mode').value;
    const mode     = freightMethodRates[rawMode] ? rawMode : 'slow'; // fallback to slow if mode unknown/empty
    const rate     = freightMethodRates[mode];
    const cartons  = parseInt(document.getElementById('pallet-total-cartons')?.value) || 1;
    const exchange = FREIGHT_EXCHANGE_RATE;

    // Update rate chip displays
    const rmbEl = document.getElementById('freight-rate-rmb-display');
    const usdEl = document.getElementById('freight-rate-usd-display');
    if (rmbEl) rmbEl.textContent = rate.toFixed(2);
    if (usdEl) usdEl.textContent = (rate / exchange).toFixed(2);

    if (!lCm || !wCm || !hCm) {
      ['freight-out-actual','freight-out-vol','freight-out-charge','freight-out-formula','freight-out-cost'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.textContent = '—';
      });
      return;
    }

    const volume    = lCm * wCm * hCm;  // cm³
    const divisor   = freightMethodDivisors[mode];
    const volWeight = volume / divisor;  // kg per carton (volumetric)

    const chargePerCarton = Math.max(actualKg, volWeight);
    const totalActual     = actualKg * cartons;
    const totalVol        = volWeight * cartons;
    const totalCharge     = chargePerCarton * cartons;
    const totalCostRmb    = totalCharge * rate;
    const totalCostUsd    = exchange > 0 ? totalCostRmb / exchange : 0;

    const formulaStr = `(${lCm.toFixed(0)} × ${wCm.toFixed(0)} × ${hCm.toFixed(0)}) ÷ ${divisor.toLocaleString()}`;

    // Results (total for shipment, dual units)
    document.getElementById('freight-out-actual').textContent  = totalActual.toFixed(2)  + ' kg  /  ' + (totalActual  * 2.20462).toFixed(2) + ' lbs';
    document.getElementById('freight-out-vol').textContent     = totalVol.toFixed(2)     + ' kg  /  ' + (totalVol     * 2.20462).toFixed(2) + ' lbs';
    document.getElementById('freight-out-charge').textContent  = totalCharge.toFixed(2)  + ' kg  /  ' + (totalCharge  * 2.20462).toFixed(2) + ' lbs';
    document.getElementById('freight-out-formula').textContent = formulaStr;
    document.getElementById('freight-out-cost').textContent    = '¥ ' + totalCostRmb.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) + '  /  $ ' + totalCostUsd.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});

    // Bar chart
    const maxWt = Math.max(totalActual, totalVol, 0.01);
    document.getElementById('freight-bar-actual').style.height = ((totalActual / maxWt) * 100) + '%';
    document.getElementById('freight-bar-vol').style.height    = ((totalVol    / maxWt) * 100) + '%';
    document.getElementById('freight-bar-charge').style.height = ((totalCharge / maxWt) * 100) + '%';
    document.getElementById('freight-bar-actual-val').textContent = totalActual.toFixed(1);
    document.getElementById('freight-bar-vol-val').textContent    = totalVol.toFixed(1);
    document.getElementById('freight-bar-charge-val').textContent = totalCharge.toFixed(1);

    // Verdict
    const verdictEl = document.getElementById('freight-verdict');
    const extraEl   = document.getElementById('freight-extra');

    if (volWeight > actualKg) {
      verdictEl.className = 'freight-verdict volumetric';
      verdictEl.textContent = 'Volumetric weight applies — package is bulky/light.';
      const extraCostRmb = (volWeight - actualKg) * rate * cartons;
      extraEl.innerHTML = 'Extra cost due to volumetric: <span>¥ ' + extraCostRmb.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) + '  /  $ ' + (extraCostRmb / exchange).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) + '</span>';
      extraEl.style.display = 'block';
    } else if (actualKg > volWeight) {
      verdictEl.className = 'freight-verdict actual';
      verdictEl.textContent = 'Actual weight applies — package is dense.';
      extraEl.style.display = 'none';
    } else {
      verdictEl.className = 'freight-verdict equal';
      verdictEl.textContent = 'Weights are equal — no volumetric surcharge.';
      extraEl.style.display = 'none';
    }

    // Method comparison — 5-column table + results panel
    [
      { key: 'slow',      div: 6000, r: freightMethodRates.slow },
      { key: 'fast',      div: 6000, r: freightMethodRates.fast },
      { key: 'airupp',    div: 5000, r: freightMethodRates.airupp },
      { key: 'directair', div: 5000, r: freightMethodRates.directair },
    ].forEach(m => {
      const vw = volume / m.div;
      const cw = Math.max(actualKg, vw) * cartons;  // total chargeable kg
      const cr = cw * m.r;
      const cu = cr / exchange;
      const combinedVal = '¥ ' + cr.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) + '  /  $ ' + cu.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
      // 5-column breakdown table
      const wtEl  = document.getElementById('freight-wt-'  + m.key);
      const rmbEl = document.getElementById('freight-rmb-' + m.key);
      const usdEl = document.getElementById('freight-usd-' + m.key);
      if (wtEl)  wtEl.textContent  = cw.toFixed(2) + ' kg';
      if (rmbEl) rmbEl.textContent = '¥ ' + cr.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
      if (usdEl) usdEl.textContent = '$ ' + cu.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
      // Results panel comparison
      const resEl = document.getElementById('freight-res-' + m.key);
      if (resEl) resEl.textContent = combinedVal;
    });

    const cbm = volume / 1000000;
    document.getElementById('freight-cmp-sea').textContent = (cbm * cartons).toFixed(2) + ' CBM';
  }

  /* ── Quote & Invoice Calcs ───────────────────────────────────────────────── */
  function calcQuoteTotal() {
    const qty = parseFloat(document.getElementById('quote-cl-qty')?.value) || 0;
    const unit = parseFloat(document.getElementById('quote-cl-unit-price')?.value) || 0;
    const shipping = parseFloat(document.getElementById('quote-cl-shipping')?.value) || 0;
    const total = (qty * unit) + shipping;
    document.getElementById('quote-cl-total').value = total > 0 ? '$' + total.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : '';
  }

  function emailQuote() {
    const client = document.getElementById('quote-client-name').value || 'Client';
    const product = document.getElementById('quote-product-name').value || 'Product';
    const quoteDate = document.getElementById('quote-date').value || '';
    const validUntil = document.getElementById('quote-valid-until').value || '';
    const qty = document.getElementById('quote-cl-qty').value || '';
    const unitPrice = document.getElementById('quote-cl-unit-price').value || '';
    const shipping = document.getElementById('quote-cl-shipping').value || '';
    const total = document.getElementById('quote-cl-total').value || '';
    const notes = document.getElementById('quote-cl-notes').value || '';

    const subject = encodeURIComponent(`Quote for ${product} - ${client}`);
    const body = encodeURIComponent(
      `Hi ${client},\n\nWe have finalized your quote for the following product. We are ready to move forward, let us know when you would like to meet and discuss.\n\nProduct: ${product}\nQuantity: ${qty}\nUnit Price: $${unitPrice}\nShipping: $${shipping}\n\nTOTAL: ${total}\n\nThanks,\nMarket Sculpt`
    );
    window.location.href = `mailto:?subject=${subject}&body=${body}`;
  }

  function calcInvoiceTotal() {
    const qty = parseFloat(document.getElementById('inv-qty')?.value) || 0;
    const unit = parseFloat(document.getElementById('inv-unit-price')?.value) || 0;
    const shipping = parseFloat(document.getElementById('inv-shipping')?.value) || 0;
    const subtotal = qty * unit;
    const total = subtotal + shipping;
    document.getElementById('inv-subtotal').value = subtotal > 0 ? '$' + subtotal.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : '';
    document.getElementById('inv-total').value = total > 0 ? '$' + total.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : '';
  }

  function fillQuoteInvoice(clientName, productName) {
    document.getElementById('quote-client-name').value = clientName || '';
    document.getElementById('quote-product-name').value = productName || '';
    document.getElementById('inv-bill-to').value = clientName || '';
    document.getElementById('inv-product').value = productName || '';
  }

  /* ── New Workbook Modal ──────────────────────────────────────────────────── */
  /* ── New Workbook category suggestions ─────────────────────────────────── */
  const CATEGORY_KEYWORDS = {
    packaging:      ['bag','bags','pouch','pouches','box','boxes','container','bottle','jar','jars','wrap','tube','tubes','sachet','envelope','mailer','packaging','package','zipper','mylar','poly bag','stand-up','flat pack'],
    apparel:        ['shirt','tee','t-shirt','hoodie','jacket','pants','dress','hat','cap','sock','socks','clothing','wear','uniform','jersey','sweater','shorts','leggings','polo','vest','gloves','scarf','beanie'],
    furniture:      ['chair','table','desk','shelf','shelving','cabinet','sofa','bench','wardrobe','drawer','stool','rack','stand','bookcase','ottoman','furniture'],
    electronics:    ['speaker','headphone','headphones','charger','cable','case','earbuds','led','battery','wireless','bluetooth','electronic','device','adapter','hub','power bank','watch','camera'],
    promotional:    ['pen','pens','mug','mugs','keychain','lanyard','badge','award','promo','promotional','giveaway','branded','merchandise','swag','corporate gift','logo'],
    'food-beverage':['food','beverage','drink','snack','coffee','water bottle','lunch box','meal','bento','thermos','flask','cutlery','utensil','edible','food-grade'],
    toys:           ['toy','toys','game','puzzle','doll','plush','stuffed animal','play','kids','children','baby','infant','educational','building blocks','action figure','fidget'],
    beauty:         ['cream','serum','lotion','makeup','cosmetic','cosmetics','brush','skincare','haircare','perfume','beauty','lipstick','foundation','mascara','nail','spa','facial','moisturizer'],
    'home-garden':  ['candle','vase','pillow','cushion','towel','mat','rug','plant','garden','kitchen','home decor','decoration','frame','clock','curtain','coaster','tray','basket','planter'],
    sports:         ['gym','fitness','yoga','sport','sports','workout','outdoor','camping','hiking','exercise','training','athletic','running','cycling','swim','ball','racket','resistance band'],
    stationery:     ['notebook','notepad','pencil','planner','journal','calendar','sticky','clipboard','folder','binder','stationery','office','school supply','marker','highlighter','agenda']
  };

  const CATEGORY_LABELS = {
    packaging:'Packaging', apparel:'Apparel', furniture:'Furniture',
    electronics:'Electronics', promotional:'Promotional', 'food-beverage':'Food & Beverage',
    toys:'Toys & Games', beauty:'Beauty', 'home-garden':'Home & Garden',
    sports:'Sports & Outdoor', stationery:'Stationery'
  };

  let _modalSuggestedCat = null;
  let _modalSuggestedSub = null;
  let _suggestTimer = null;
  let _pendingWorkbookCategory = null; // applied after navigation to new workbook

  function debounceSuggestCategories() {
    clearTimeout(_suggestTimer);
    _suggestTimer = setTimeout(renderWorkbookSuggestions, 300);
  }

  function scoreSuggestions() {
    const name = (document.getElementById('modal-product')?.value || '').toLowerCase();
    const desc = (document.getElementById('modal-desc')?.value || '').toLowerCase();
    const text = name + ' ' + desc;
    if (text.trim().length < 4) return [];
    const scores = {};
    Object.entries(CATEGORY_KEYWORDS).forEach(([cat, keywords]) => {
      let score = 0;
      keywords.forEach(kw => { if (text.includes(kw)) score += kw.includes(' ') ? 2 : 1; });
      if (score > 0) scores[cat] = score;
    });
    return Object.entries(scores).sort((a, b) => b[1] - a[1]).map(([cat]) => cat);
  }

  function suggestSubs(cats) {
    const name = (document.getElementById('modal-product')?.value || '').toLowerCase();
    const desc = (document.getElementById('modal-desc')?.value || '').toLowerCase();
    const text = name + ' ' + desc;
    const seen = new Set();
    const result = [];
    cats.slice(0, 2).forEach(cat => {
      (SUBCATEGORIES[cat] || []).forEach(sub => {
        const key = sub.toLowerCase();
        const firstWord = key.split(/[\s(]/)[0];
        if (!seen.has(sub) && (text.includes(firstWord) || text.includes(key))) {
          seen.add(sub);
          result.push(sub);
        }
      });
    });
    return result.slice(0, 4);
  }

  function renderWorkbookSuggestions() {
    const area = document.getElementById('modal-suggest-area');
    if (!area) return;
    const cats = scoreSuggestions();
    if (cats.length === 0) { area.innerHTML = ''; return; }
    const subs = suggestSubs(cats);
    const chip = (label, selected, onclick) =>
      `<button type="button" onclick="${onclick}"
        style="padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; cursor:pointer; transition:all 0.15s; font-family:inherit;
        ${selected
          ? 'background:var(--accent); color:#fff; border:1px solid var(--accent);'
          : 'background:var(--surface2); color:var(--text); border:1px solid var(--border);'}">${label}</button>`;
    let html = `<div style="padding:10px 12px; background:var(--surface2); border:1px solid var(--border); border-radius:var(--radius-sm);">
      <div style="font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:var(--text-muted); margin-bottom:6px;">✦ Suggested Category</div>
      <div style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:${subs.length ? '10px' : '0'};">`;
    cats.slice(0, 3).forEach(cat => {
      html += chip(CATEGORY_LABELS[cat] || cat, _modalSuggestedCat === cat, `selectModalCat('${cat}')`);
    });
    html += `</div>`;
    if (subs.length) {
      html += `<div style="font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:var(--text-muted); margin-bottom:6px;">Material Type</div>
        <div style="display:flex; flex-wrap:wrap; gap:6px;">`;
      subs.forEach(sub => {
        html += chip(sub, _modalSuggestedSub === sub, `selectModalSub('${sub.replace(/'/g,"\\'")}')`)
      });
      html += `</div>`;
    }
    html += `</div>`;
    area.innerHTML = html;
  }

  function selectModalCat(cat) {
    _modalSuggestedCat = _modalSuggestedCat === cat ? null : cat;
    renderWorkbookSuggestions();
  }

  function selectModalSub(sub) {
    _modalSuggestedSub = _modalSuggestedSub === sub ? null : sub;
    renderWorkbookSuggestions();
  }

  function openNewWorkbookModal() {
    document.getElementById('new-workbook-form').reset();

    const sel = document.getElementById('modal-client');
    const dropdownField = document.getElementById('modal-client-field');
    const displayField  = document.getElementById('modal-client-display');
    const displayLabel  = document.getElementById('modal-client-label');

    // Populate dropdown from live client list
    sel.innerHTML = '<option value="">Select a client…</option>';
    Object.keys(clientData).sort().forEach(name => {
      const opt = document.createElement('option');
      opt.value = name;
      opt.textContent = name;
      sel.appendChild(opt);
    });

    // Resolve the client context from the URL, not the `currentClient`
    // global. `currentClient` only updates when opening a workbook detail —
    // it stays stale when the user navigates between client dashboards, which
    // would prefill the modal with the wrong client.
    const ctxClient = (() => {
      const m = location.hash.match(/^#\/client\/([^/]+)/);
      if (m) return decodeURIComponent(m[1]);
      return currentClient || '';
    })();

    if (ctxClient && clientData[ctxClient]) {
      // Already on a client — pre-fill and hide the dropdown
      sel.value = ctxClient;
      sel.required = false;
      dropdownField.style.display = 'none';
      displayField.style.display  = '';
      displayLabel.textContent    = ctxClient;
    } else {
      // No client context — show the dropdown
      sel.required = true;
      dropdownField.style.display = '';
      displayField.style.display  = 'none';
    }

    // Reset suggestions
    _modalSuggestedCat = null;
    _modalSuggestedSub = null;
    document.getElementById('modal-suggest-area').innerHTML = '';

    document.getElementById('modal-overlay').classList.add('open');
    setTimeout(() => document.getElementById('modal-product').focus(), 50);
  }

  function closeModal() {
    document.getElementById('modal-overlay').classList.remove('open');
  }

  async function createWorkbook(e) {
    e.preventDefault();
    const product = document.getElementById('modal-product').value.trim();
    const client = document.getElementById('modal-client').value;
    const desc = document.getElementById('modal-desc').value.trim();

    if (!product || !client || !desc) return;

    // Save to DB
    const clientId = dbClientMap[client];
    let newId;
    if (clientId) {
      // allow_duplicate:true — user explicitly created this from the UI.
      // The server-side dedup guard is intended to block seed-retry loops,
      // not legitimate user creations (e.g. "Box v1", "Box v2" at the same name).
      const result = await apiCall('add_workbook', {
        client_id: clientId,
        product_name: product,
        description: desc,
        allow_duplicate: true
      });
      if (result.success) {
        newId = parseInt(result.id);
      }
    }

    // Add to local data
    const items = clientData[client] || [];
    if (!newId) {
      newId = items.length > 0 ? Math.max(...items.map(i => i.id)) + 1 : 1;
    }
    const today = new Date();
    const dateStr = today.getDate() + ' ' + today.toLocaleString('en-US', { month: 'short' }) + ' ' + String(today.getFullYear()).slice(2);

    items.push({
      id: newId,
      product: product,
      description: desc,
      dateCreated: dateStr,
      dateSubmitted: '',
      flow: { prepChina: true, chinaSubmits: false, shippingDims: false, quoteClient: false, clientApproved: false, officeInvoice: false, confirmedPayment: false, orderChina: false }
    });

    clientData[client] = items;
    dbWorkbookMap[`${client}|${newId}`] = newId;
    saveToLocalStorage();

    // Store any suggested category to apply after navigation
    if (_modalSuggestedCat || _modalSuggestedSub) {
      _pendingWorkbookCategory = { cat: _modalSuggestedCat, sub: _modalSuggestedSub };
    }

    closeModal();

    // Navigate to the new workbook
    location.hash = `#/client/${encodeURIComponent(client)}/workbook/${newId}`;
  }

  /* ── Add Client Modal ───────────────────────────────────────────────────── */
  function openAddClientModal() {
    document.getElementById('add-client-form').reset();
    // Reset shipping textarea state in case checkbox was left checked
    const shipTa = document.getElementById('modal-client-shipping');
    if (shipTa) { shipTa.disabled = false; shipTa.style.opacity = ''; }
    document.getElementById('client-modal-overlay').classList.add('open');
    setTimeout(() => document.getElementById('modal-client-name').focus(), 50);
  }

  function closeClientModal() {
    document.getElementById('client-modal-overlay').classList.remove('open');
  }

  async function createClient(e) {
    e.preventDefault();
    const name    = document.getElementById('modal-client-name').value.trim();
    const contact = document.getElementById('modal-client-contact').value.trim();
    const email   = document.getElementById('modal-client-email').value.trim();
    const phone   = document.getElementById('modal-client-phone').value.trim();
    const billing = document.getElementById('modal-client-billing').value.trim();
    const sameChk = document.getElementById('modal-ship-same');
    const shipping= sameChk && sameChk.checked ? billing : document.getElementById('modal-client-shipping').value.trim();
    const am      = (document.getElementById('modal-client-am')?.value || '').trim();
    const sp      = (document.getElementById('modal-client-sp')?.value || '').trim();
    if (!name) return;

    // Check if client already exists
    if (clientData[name]) {
      alert('Client "' + name + '" already exists.');
      return;
    }

    // ── Populate local state immediately so the UI renders right away ──
    clientData[name] = [];
    clientDetails[name] = { id: null, email, phone, primary_contact: contact, email2: '', phone2: '', primary_contact2: '', billing_address: billing, shipping_address: shipping, notes: '', account_manager: am, salesperson: sp, operations_person: '', account_manager_pct: '', salesperson_pct: '', operations_pct: '' };
    rebuildSidebar(); // handles nav items (with avatar/star/delete) + modal dropdown

    // ── Close modal and navigate immediately — user sees the page now ──
    closeClientModal();
    location.hash = `#/client/${encodeURIComponent(name)}`;

    // ── Save to DB and show toast ──
    const result = await apiCall('add_client', { name, account_manager: am, salesperson: sp });
    const s = document.getElementById('save-status');
    if (result.success) {
      dbClientMap[name] = result.id;
      clientDetails[name].id = result.id;
      if (contact || email || phone || billing || shipping) {
        await apiCall('save_client_detail', { id: result.id, email, phone, primary_contact: contact, billing_address: billing, shipping_address: shipping, notes: '' });
      }
      if (s) { s.textContent = `✓ Client "${name}" created`; s.style.color = 'var(--success)'; s.style.opacity = '1'; setTimeout(() => { s.style.opacity = '0'; s.textContent = ''; }, 3500); }
    } else {
      const errMsg = result.error || 'Unknown error';
      if (s) { s.textContent = `⚠ ${errMsg}`; s.style.color = 'var(--danger)'; s.style.opacity = '1'; setTimeout(() => { s.style.opacity = '0'; s.textContent = ''; }, 5000); }
      // If the client already exists in the DB (e.g. from a previous attempt),
      // try to recover by reloading from DB so it shows up properly
      if (errMsg.toLowerCase().includes('already exists')) {
        await loadFromDatabase();
        rebuildSidebar();
        updateSidebarActive(name);
      }
    }

    saveToLocalStorage();
  }

  /* ── Delete Workbook Modal ─────────────────────────────────────────────── */
  let pendingDelete = null;

  async function duplicateWorkbook(clientName, workbookId) {
    document.querySelectorAll('.action-menu.open').forEach(m => m.classList.remove('open'));
    const dbId = dbWorkbookMap[`${clientName}|${workbookId}`] || workbookId;
    // Get source product name and detail
    const srcItem = (clientData[clientName] || []).find(i => i.id === parseInt(workbookId));
    const srcName = srcItem ? srcItem.product + ' (Copy)' : 'Workbook (Copy)';
    const srcDetail = workbookDetail[`${clientName}|${workbookId}`] || {};
    const srcQty       = srcDetail.quoteClQty       || '';
    const srcUnitPrice = srcDetail.quoteClUnitPrice  || '';
    const srcCost      = srcDetail.quoteClShipping   || '';
    const _q = parseFloat(srcQty) || 0;
    const _u = parseFloat(srcUnitPrice) || 0;
    const _s = parseFloat(srcCost) || 0;
    const srcTotal = (_q * _u + _s) > 0
      ? '$' + (_q * _u + _s).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})
      : '—';

    // Build client options
    const clientOptions = Object.keys(clientData).sort().map(n =>
      `<option value="${n.replace(/"/g,'&quot;')}"${n === clientName ? ' selected' : ''}>${n}</option>`
    ).join('');

    // Show modal
    let modal = document.getElementById('duplicate-modal');
    if (modal) modal.remove();
    modal = document.createElement('div');
    modal.id = 'duplicate-modal';
    modal.className = 'modal-overlay';
    modal.style.cssText = 'display:flex;';
    modal.innerHTML = `
      <div class="modal" style="max-width:460px; width:100%;">
        <div style="display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:18px;">
          <h3 style="margin:0; font-size:18px; font-weight:700;">Duplicate Workbook</h3>
          <button onclick="document.getElementById('duplicate-modal').remove()" style="background:none;border:none;font-size:22px;line-height:1;cursor:pointer;color:var(--text-muted);padding:0;margin:-2px -2px 0 0;">&times;</button>
        </div>
        <div class="modal-body" style="display:flex;flex-direction:column;gap:14px;">
          <div id="dup-error" style="display:none;background:rgba(251,113,133,0.12);border:1px solid rgba(251,113,133,0.35);color:#fb7185;border-radius:8px;padding:8px 12px;font-size:13px;"></div>
          <div>
            <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-muted);display:block;margin-bottom:4px;">Client</label>
            <select id="dup-client" class="form-input" style="width:100%;" autocomplete="off">${clientOptions}</select>
          </div>
          <div>
            <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-muted);display:block;margin-bottom:4px;">Product Name</label>
            <input id="dup-name" type="text" class="form-input" style="width:100%;" placeholder="${srcName.replace(/"/g,'&quot;')}" autocomplete="off" />
          </div>
          <div style="border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;">
            <div style="display:grid;grid-template-columns:1fr 1fr;">
              <div style="padding:12px 16px;border-right:1px solid var(--border);border-bottom:1px solid var(--border);">
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-muted);margin-bottom:5px;">Quantity</div>
                <div id="dup-qty" style="font-size:15px;color:var(--text);">${srcQty || '—'}</div>
              </div>
              <div style="padding:12px 16px;border-bottom:1px solid var(--border);">
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-muted);margin-bottom:5px;">Unit Price (USD)</div>
                <div style="font-size:15px;color:var(--text);">${srcUnitPrice ? '$' + srcUnitPrice : '—'}</div>
              </div>
              <div style="padding:12px 16px;border-right:1px solid var(--border);">
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-muted);margin-bottom:5px;">Shipping Cost (USD)</div>
                <div id="dup-cost" style="font-size:15px;color:var(--text);">${srcCost ? '$' + srcCost : '—'}</div>
              </div>
              <div style="padding:12px 16px;">
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-muted);margin-bottom:5px;">Total Quote (USD)</div>
                <div style="font-size:16px;font-weight:700;color:var(--text);">${srcTotal}</div>
              </div>
            </div>
          </div>
          <button class="btn btn-primary" onclick="confirmDuplicate('${clientName.replace(/'/g,"\\'")}', ${dbId})">Create Workbook</button>
        </div>
      </div>`;
    document.body.appendChild(modal);
    setTimeout(() => document.getElementById('dup-name')?.focus(), 50);
  }

  async function confirmDuplicate(originalClient, srcDbId) {
    const newClient = document.getElementById('dup-client').value;
    const nameEl    = document.getElementById('dup-name');
    const newName   = nameEl.value.trim() || nameEl.placeholder;
    const qty       = document.getElementById('dup-qty').textContent.replace('—','').trim();
    const cost      = document.getElementById('dup-cost').textContent.replace('$','').replace('—','').trim();
    const errEl     = document.getElementById('dup-error');
    errEl.style.display = 'none';
    if (!newName) { errEl.textContent = 'Product name is required.'; errEl.style.display = 'block'; return; }

    // Create duplicate with flow reset to 0
    const r = await apiCall('duplicate_workbook', { id: srcDbId, target_client: newClient, product_name: newName, qty, cost });
    if (!r.success) { errEl.textContent = r.error || 'Duplicate failed.'; errEl.style.display = 'block'; return; }

    document.getElementById('duplicate-modal').remove();
    await loadFromDatabase();
    rebuildSidebar();
    location.hash = `#/client/${encodeURIComponent(newClient)}/workbook/${r.id}`;
  }

  function openDeleteModal(clientName, workbookId, productName) {
    document.querySelectorAll('.action-menu.open').forEach(m => m.classList.remove('open'));
    document.getElementById('delete-product-name').textContent = productName;
    pendingDelete = { clientName, workbookId };
    document.getElementById('delete-modal-overlay').classList.add('open');
  }

  function closeDeleteModal() {
    document.getElementById('delete-modal-overlay').classList.remove('open');
    pendingDelete = null;
  }

  document.getElementById('confirm-delete-btn').addEventListener('click', async function() {
    if (!pendingDelete) return;
    const { clientName, workbookId } = pendingDelete;

    // Call API to delete
    try {
      const dbId = dbWorkbookMap[`${clientName}|${workbookId}`] || workbookId;
      await apiCall('delete_workbook', { id: dbId });
    } catch (e) {
      console.log('API delete failed, removing locally:', e);
    }

    // Remove from clientData
    if (clientData[clientName]) {
      clientData[clientName] = clientData[clientName].filter(item => String(item.id) !== String(workbookId));
    }

    // Remove from workbookDetail
    delete workbookDetail[`${clientName}|${workbookId}`];
    delete dbWorkbookMap[`${clientName}|${workbookId}`];

    saveToLocalStorage();
    closeDeleteModal();

    // Re-render current view
    const hash = location.hash;
    if (hash.startsWith('#/client/') && !hash.includes('/workbook/')) {
      renderDashboard(clientName);
    } else {
      renderRecentWorkbooks();
      showView('view-home');
    }
  });

  /* ── Delete Client Modal ──────────────────────────────────────────────── */
  let pendingClientDelete = null;

  function openDeleteClientModal(clientName) {
    document.getElementById('delete-client-name').textContent = clientName;
    pendingClientDelete = clientName;
    document.getElementById('delete-client-modal-overlay').classList.add('open');
  }

  function closeDeleteClientModal() {
    document.getElementById('delete-client-modal-overlay').classList.remove('open');
    pendingClientDelete = null;
  }

  document.getElementById('confirm-delete-client-btn').addEventListener('click', async function() {
    if (!pendingClientDelete) return;
    const clientName = pendingClientDelete;

    // Delete all workbooks for this client from API
    try {
      const items = clientData[clientName] || [];
      for (const item of items) {
        const dbId = dbWorkbookMap[`${clientName}|${item.id}`] || item.id;
        await apiCall('delete_workbook', { id: dbId });
      }
      // Delete the client itself
      const clientDbId = dbClientMap[clientName];
      if (clientDbId) {
        await apiCall('delete_client', { id: clientDbId });
      }
    } catch (e) {
      console.log('API client delete failed, removing locally:', e);
    }

    // Remove from local data
    const items = clientData[clientName] || [];
    items.forEach(item => {
      delete workbookDetail[`${clientName}|${item.id}`];
      delete dbWorkbookMap[`${clientName}|${item.id}`];
    });
    delete clientData[clientName];
    delete dbClientMap[clientName];

    saveToLocalStorage();
    closeDeleteClientModal();
    rebuildSidebar();

    // Navigate to home
    location.hash = '';
    renderRecentWorkbooks();
    showView('view-home');
  });

  /* ── History Modal ──────────────────────────────────────────────────────── */
  const FIELD_LABELS = {
    client:'Client', product:'Product', desc:'Description', productCategory:'Category', productSubcategory:'Material Type',
    dimInL:'Length', dimInW:'Width', dimInH:'Height',
    materials:'Materials', pantone:'Pantone', cmyk:'CMYK', colorNotes:'Color Notes',
    rfqItems:'RFQ Line Items', qcNotes:'Quote Notes',
    cartonUnitWeight:'Unit Weight', cartonInnerWeight:'Inner Carton Weight', cartonInnerCount:'Inner Carton Qty',
    cartonOuterWeight:'Outer Carton Weight', cartonOuterCount:'Outer Carton Qty',
    freightMode:'Shipping Method', freightHsCode:'HS Code',
    quoteDate:'Quote Date', quoteValidUntil:'Valid Until', quoteClQty:'Quote Qty',
    quoteClUnitPrice:'Quote Unit Price', quoteClShipping:'Quote Shipping', quoteClNotes:'Quote Notes',
    invNumber:'Invoice #', invDate:'Invoice Date', invDueDate:'Due Date',
    invQty:'Invoice Qty', invUnitPrice:'Invoice Unit Price', invShipping:'Invoice Shipping',
    invStatus:'Payment Status', invMethod:'Payment Method', invNotes:'Invoice Notes',
    artStatus:'Art Status', artDueDate:'Art Due Date', artNotes:'Art Notes'
  };

  let _historyRevisions = []; // store fetched revisions for diff comparison

  function openHistoryModal() {
    const overlay = document.getElementById('history-modal-overlay');
    overlay.style.display = 'flex';
    const list = document.getElementById('history-list');
    if (!currentWorkbookId) {
      list.innerHTML = '<p style="color:var(--text-muted); text-align:center; padding:20px;">Open a workbook to view its revision history.</p>';
      return;
    }
    list.innerHTML = '<p style="color:var(--text-muted); text-align:center;">Loading...</p>';

    const dbId = dbWorkbookMap[`${currentClient}|${currentWorkbookId}`] || currentWorkbookId;
    fetch(`api.php?action=get_revisions&workbook_id=${dbId}`)
      .then(r => r.json())
      .then(data => {
        if (!data.success || !data.data.length) {
          list.innerHTML = '<p style="color:var(--text-muted); text-align:center; padding:20px;">No revision history yet. Changes are tracked automatically as you edit.</p>';
          return;
        }
        _historyRevisions = data.data;
        list.innerHTML = data.data.map((rev, idx) => {
          const summary = (rev.summary || []).slice(0, 6).map(s =>
            `<span style="display:inline-block; background:var(--surface2); border-radius:4px; padding:2px 6px; font-size:10px; color:var(--text-muted); margin:2px 2px 0 0;">${s.field}: ${s.value.length > 20 ? s.value.substring(0, 20) + '…' : s.value}</span>`
          ).join('');
          const moreCount = (rev.summary || []).length - 6;
          const moreTag = moreCount > 0 ? `<span style="font-size:10px; color:var(--text-muted);">+${moreCount} more</span>` : '';
          return `
          <div style="padding:10px 0; border-bottom:1px solid var(--border);">
            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
              <div style="flex:1; min-width:0;">
                <div style="font-size:13px; font-weight:600;">${new Date(rev.created_at).toLocaleString()}</div>
                <div style="font-size:11px; color:var(--text-muted); margin-bottom:4px;">${rev.changed_by || 'Unknown user'}</div>
                <div style="line-height:1.6;">${summary}${moreTag}</div>
                <button class="diff-toggle" onclick="toggleDiff(${rev.id}, ${idx})">&#9654; View Changes</button>
                <div class="diff-panel" id="diff-panel-${rev.id}"><p style="color:var(--text-muted);font-size:11px;">Loading...</p></div>
              </div>
              <button class="btn-back-step" onclick="restoreRevision(${rev.id}, ${rev.workbook_id})" title="Restore this version" style="font-size:11px; width:auto; padding:4px 10px; flex-shrink:0; margin-left:8px;">Restore</button>
            </div>
          </div>`;
        }).join('');
      })
      .catch(() => {
        list.innerHTML = '<p style="color:var(--text-muted); text-align:center;">Failed to load history.</p>';
      });
  }

  async function toggleDiff(revId, idx) {
    const panel = document.getElementById(`diff-panel-${revId}`);
    if (panel.classList.contains('open')) {
      panel.classList.remove('open');
      return;
    }
    panel.classList.add('open');
    panel.innerHTML = '<p style="color:var(--text-muted);font-size:11px;">Loading...</p>';

    try {
      // This revision (the OLD version — what was saved before the change)
      const oldRes = await fetch(`api.php?action=get_revision_detail&revision_id=${revId}`).then(r => r.json());
      const oldDetail = oldRes.data?.detail || {};

      // The NEWER version: previous revision in the list (idx-1), or current workbook data
      let newDetail;
      if (idx === 0) {
        // Most recent revision — compare against current live data
        const key = `${currentClient}|${currentWorkbookId}`;
        newDetail = workbookDetail[key] || {};
      } else {
        const newerRevId = _historyRevisions[idx - 1].id;
        const newRes = await fetch(`api.php?action=get_revision_detail&revision_id=${newerRevId}`).then(r => r.json());
        newDetail = newRes.data?.detail || {};
      }

      const diffs = buildDiff(oldDetail, newDetail);
      if (diffs.length === 0) {
        panel.innerHTML = '<p style="color:var(--text-muted);font-size:11px;">No changes detected.</p>';
      } else {
        panel.innerHTML = diffs.map(d => `
          <div class="diff-row">
            <span class="diff-label">${d.label}</span>
            <span><span class="diff-old">${escHtml(d.old || '—')}</span> <span class="diff-arrow">&rarr;</span> <span class="diff-new">${escHtml(d.new || '—')}</span></span>
          </div>
        `).join('');
      }
    } catch (e) {
      panel.innerHTML = '<p style="color:#c0392b;font-size:11px;">Failed to load diff.</p>';
    }
  }

  function buildDiff(oldD, newD) {
    const diffs = [];
    const allKeys = new Set([...Object.keys(oldD), ...Object.keys(newD)]);

    for (const key of allKeys) {
      if (key === 'productImage' || key === 'productImages' || key === 'artImages') {
        if (key === 'productImages') {
          const oldImgs = Array.isArray(oldD.productImages) ? oldD.productImages.length : 0;
          const newImgs = Array.isArray(newD.productImages) ? newD.productImages.length : 0;
          if (oldImgs !== newImgs) {
            diffs.push({ label: 'Product Images', old: oldImgs + ' image(s)', new: newImgs + ' image(s)' });
          }
        }
        if (key === 'artImages') {
          const oldArt = Array.isArray(oldD.artImages) ? oldD.artImages.length : 0;
          const newArt = Array.isArray(newD.artImages) ? newD.artImages.length : 0;
          if (oldArt !== newArt) {
            diffs.push({ label: 'Art Files', old: oldArt + ' file(s)', new: newArt + ' file(s)' });
          }
        }
        continue;
      }
      if (key === 'tiers') {
        const oldTiers = Array.isArray(oldD.tiers) ? oldD.tiers : [];
        const newTiers = Array.isArray(newD.tiers) ? newD.tiers : [];
        const maxLen = Math.max(oldTiers.length, newTiers.length);
        for (let i = 0; i < maxLen; i++) {
          const oq = oldTiers[i]?.qty?.toString() || '';
          const nq = newTiers[i]?.qty?.toString() || '';
          const op = oldTiers[i]?.price?.toString() || '';
          const np = newTiers[i]?.price?.toString() || '';
          if (oq !== nq) diffs.push({ label: `Tier ${i+1} Qty`, old: oq, new: nq });
          if (op !== np) diffs.push({ label: `Tier ${i+1} Price`, old: op, new: np });
        }
        continue;
      }
      const oldVal = (oldD[key] || '').toString();
      const newVal = (newD[key] || '').toString();
      if (oldVal !== newVal) {
        diffs.push({ label: FIELD_LABELS[key] || key, old: oldVal, new: newVal });
      }
    }
    return diffs;
  }

  function escHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  // ── User Management modal ────────────────────────────────────────────
  // The modal hosts two views — list and per-user detail. _usersCache is
  // the last server response so the detail view can render synchronously
  // after a row click; refreshed every time the list view is shown so
  // edits made elsewhere don't go stale.
  let _usersCache = [];
  let _userDetailCurrent = null; // currently-selected user object in detail view

  // Show/hide toggle for password inputs. Stored bcrypt hashes can't be
  // reversed so we can't reveal an existing password — this only flips
  // the input type so the operator can verify what they just typed in
  // a new-password field. Mirrors the login.php toggle exactly so the
  // eye icons feel identical across pages: same 15px SVGs, same path,
  // same display attribute swap pattern.
  function togglePasswordField(btn) {
    const wrap = btn.closest('.pw-wrap');
    if (!wrap) return;
    const input = wrap.querySelector('input');
    if (!input) return;
    const wasHidden = (input.type === 'password');
    input.type = wasHidden ? 'text' : 'password';
    const showEye = btn.querySelector('.eye-show');
    const hideEye = btn.querySelector('.eye-hide');
    if (showEye) showEye.style.display = wasHidden ? 'none' : 'block';
    if (hideEye) hideEye.style.display = wasHidden ? 'block' : 'none';
    btn.setAttribute('aria-label', wasHidden ? 'Hide password' : 'Show password');
  }

  function openUsersModal() {
    document.getElementById('users-modal-overlay').style.display = 'flex';
    showUsersList();
  }
  function closeUsersModal() {
    document.getElementById('users-modal-overlay').style.display = 'none';
  }

  // Swap to the list pane. Re-fetches so newly-added/edited rows appear.
  function showUsersList() {
    document.getElementById('users-list-view').style.display = '';
    document.getElementById('users-detail-view').style.display = 'none';
    document.getElementById('users-back-btn').style.display = 'none';
    document.getElementById('users-title-text').textContent = 'User Management';
    _userDetailCurrent = null;
    loadUsers();
  }

  // Swap to the detail pane for the user with the given id. Pulls from
  // _usersCache (populated by loadUsers) so the click → render hop is
  // instant and we don't blink a Loading… state for data we already have.
  function showUserDetail(id) {
    const u = _usersCache.find(x => x.id === id);
    if (!u) { showUsersList(); return; }
    _userDetailCurrent = u;
    document.getElementById('users-list-view').style.display = 'none';
    document.getElementById('users-detail-view').style.display = '';
    document.getElementById('users-back-btn').style.display = '';
    document.getElementById('users-title-text').textContent = u.display_name || u.username;
    renderUserDetail();
  }

  function loadUsers() {
    const list = document.getElementById('users-list');
    list.innerHTML = '<p style="color:var(--text-muted); font-size:13px;">Loading…</p>';
    apiCall('get_users').then(data => {
      if (!data.success) { list.innerHTML = '<p style="color:var(--danger);">Failed to load users.</p>'; return; }
      _usersCache = data.data || [];
      if (!_usersCache.length) { list.innerHTML = '<p style="color:var(--text-muted);">No users yet.</p>'; return; }
      const sessionId = (window.MS_SESSION && window.MS_SESSION.id);
      list.innerHTML = `
        <div style="font-size:13px; font-weight:600; margin-bottom:8px;">Current Users</div>
        ${_usersCache.map(u => `
          <div onclick="showUserDetail(${u.id})" style="display:flex; align-items:center; gap:8px; padding:10px 8px; border-bottom:1px solid var(--border); cursor:pointer; border-radius:4px;" onmouseover="this.style.background='var(--surface2)'" onmouseout="this.style.background='transparent'">
            <span style="flex:1; font-size:13px;">
              ${escHtml(u.display_name || u.username)}
              <span style="color:var(--text-muted); font-size:11px;"> (${escHtml(u.username)})</span>
              ${u.email ? `<div style="color:var(--text-muted); font-size:11px; margin-top:2px;">${escHtml(u.email)}</div>` : ''}
            </span>
            <span style="font-size:11px; background:var(--surface2); padding:2px 8px; border-radius:4px; color:${u.role==='admin'?'var(--accent)':'var(--text-muted)'};">${u.role}</span>
            <span style="color:var(--text-muted); font-size:14px;">›</span>
          </div>`).join('')}`;
    });
  }

  // Render the per-user detail pane. Pulls from _userDetailCurrent — set
  // by showUserDetail so we don't have to refetch on every keystroke
  // (each input writes back into _userDetailCurrent on blur via Save).
  function renderUserDetail() {
    const u = _userDetailCurrent;
    if (!u) return;
    const sessionId = (window.MS_SESSION && window.MS_SESSION.id);
    const isSelf = (u.id === sessionId);
    const created  = u.created_at ? new Date(u.created_at).toLocaleDateString() : '—';
    const lastSeen = u.last_login ? new Date(u.last_login).toLocaleString()      : 'Never';
    const body = document.getElementById('users-detail-body');
    body.innerHTML = `
      <!-- Profile fields. Username is read-only after creation; touching
           it would orphan login attempts. Role is locked when editing
           your own row so an admin can't accidentally demote themselves. -->
      <div style="display:grid; grid-template-columns:1fr; gap:12px; margin-bottom:18px;">
        <div>
          <label style="font-size:12px; font-weight:600; color:var(--text-muted); display:block; margin-bottom:4px;">Username</label>
          <input type="text" value="${escHtml(u.username)}" class="field-input" style="font-size:13px; background:var(--surface2); color:var(--text-muted);" readonly />
        </div>
        <div>
          <label style="font-size:12px; font-weight:600; color:var(--text-muted); display:block; margin-bottom:4px;">Display Name</label>
          <input id="udetail-display" type="text" value="${escHtml(u.display_name || '')}" class="field-input" style="font-size:13px;" />
        </div>
        <div>
          <label style="font-size:12px; font-weight:600; color:var(--text-muted); display:block; margin-bottom:4px;">Email</label>
          <input id="udetail-email" type="email" value="${escHtml(u.email || '')}" placeholder="user@example.com" class="field-input" style="font-size:13px;" />
        </div>
        <div>
          <label style="font-size:12px; font-weight:600; color:var(--text-muted); display:block; margin-bottom:4px;">Role${isSelf ? ' <span style="font-weight:400; font-style:italic;">(can&#39;t change your own role)</span>' : ''}</label>
          <span class="ship-select-wrap" style="display:block;">
            <select id="udetail-role" class="field-input" style="font-size:13px; appearance:none; -webkit-appearance:none; -moz-appearance:none; padding-right:32px; cursor:pointer; width:100%;" ${isSelf ? 'disabled' : ''}>
              <option value="user"  ${u.role==='user'  ? 'selected' : ''}>User</option>
              <option value="admin" ${u.role==='admin' ? 'selected' : ''}>Admin</option>
            </select>
          </span>
        </div>
      </div>

      <!-- Read-only meta — quick context for who this user actually is. -->
      <div style="font-size:11px; color:var(--text-muted); margin-bottom:18px; display:flex; gap:16px; flex-wrap:wrap;">
        <span>Created: ${created}</span>
        <span>Last login: ${lastSeen}</span>
      </div>

      <!-- Save button for the profile fields above. Password is its own
           section below so a typo in display name doesn't reset the
           password input mid-edit. -->
      <div style="display:flex; gap:8px; margin-bottom:24px;">
        <button class="btn-create" onclick="saveUserDetail()" style="font-size:13px; padding:8px 16px;">Save Changes</button>
        <button class="btn-cancel" onclick="showUsersList()" style="font-size:13px; padding:8px 16px;">Cancel</button>
      </div>

      <!-- Change password — separate form, separate save. Cleared after
           a successful update so the new value doesn't linger in the DOM.
           Each input gets an eyeball toggle (togglePasswordField) so the
           operator can verify what they typed before clicking Update. -->
      <div style="border-top:1px solid var(--border); padding-top:16px; margin-bottom:24px;">
        <div style="font-size:13px; font-weight:600; margin-bottom:10px;">Change Password</div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:10px;">
          <span class="pw-wrap" style="display:block; position:relative;">
            <input id="udetail-newpw" type="password" placeholder="New password" class="field-input" style="font-size:13px; width:100%; padding-right:36px;" />
            <button type="button" onclick="togglePasswordField(this)" aria-label="Toggle password visibility" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#9ba3c0; padding:2px; line-height:0; display:flex; align-items:center;">
              <svg class="eye-show" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" display="block"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="eye-hide" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" display="none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </span>
          <span class="pw-wrap" style="display:block; position:relative;">
            <input id="udetail-newpw-conf" type="password" placeholder="Confirm password" class="field-input" style="font-size:13px; width:100%; padding-right:36px;" />
            <button type="button" onclick="togglePasswordField(this)" aria-label="Toggle password visibility" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#9ba3c0; padding:2px; line-height:0; display:flex; align-items:center;">
              <svg class="eye-show" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" display="block"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="eye-hide" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" display="none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </span>
        </div>
        <button class="btn-create" onclick="saveUserPassword()" style="font-size:13px; padding:8px 16px;">Update Password</button>
      </div>

      ${isSelf ? '' : `
        <!-- Delete user — admin-only operation, hidden when viewing own
             row (the API blocks self-delete anyway, but no point showing
             the button if it can't fire). -->
        <div style="border-top:1px solid var(--border); padding-top:16px;">
          <div style="font-size:13px; font-weight:600; color:var(--danger); margin-bottom:8px;">Danger Zone</div>
          <button class="btn-danger" onclick="deleteUser(${u.id})" style="font-size:13px; padding:8px 16px;">Delete User</button>
        </div>
      `}
    `;
  }

  // Persist display_name / email / role for the detail-pane user. Role
  // change is sent only when not editing self; the API enforces this too
  // but we mirror the rule client-side so we don't send obvious no-ops.
  function saveUserDetail() {
    const u = _userDetailCurrent;
    if (!u) return;
    const sessionId = (window.MS_SESSION && window.MS_SESSION.id);
    const payload = {
      id: u.id,
      display_name: document.getElementById('udetail-display').value.trim(),
      email:        document.getElementById('udetail-email').value.trim(),
    };
    if (u.id !== sessionId) {
      payload.role = document.getElementById('udetail-role').value;
    }
    apiCall('update_user', payload).then(r => {
      if (r.success) {
        // Mutate the cache entry so the list view reflects the change
        // when we navigate back without a refetch round-trip.
        Object.assign(u, payload);
        showUsersList();
      } else {
        alert(r.error || 'Failed to update user.');
      }
    });
  }

  function saveUserPassword() {
    const u = _userDetailCurrent;
    if (!u) return;
    const pw1 = document.getElementById('udetail-newpw').value;
    const pw2 = document.getElementById('udetail-newpw-conf').value;
    if (!pw1)            { alert('Enter a new password.'); return; }
    if (pw1.length < 6)  { alert('Password must be at least 6 characters.'); return; }
    if (pw1 !== pw2)     { alert('Passwords do not match.'); return; }
    apiCall('change_password', { user_id: u.id, new_password: pw1 }).then(r => {
      if (r.success) {
        document.getElementById('udetail-newpw').value      = '';
        document.getElementById('udetail-newpw-conf').value = '';
        alert('Password updated.');
      } else {
        alert(r.error || 'Failed to update password.');
      }
    });
  }

  function addUser() {
    const username = document.getElementById('new-user-username').value.trim();
    const display  = document.getElementById('new-user-display').value.trim();
    const email    = document.getElementById('new-user-email').value.trim();
    const password = document.getElementById('new-user-password').value;
    const role     = document.getElementById('new-user-role').value;
    if (!username || !password) { alert('Username and password are required.'); return; }
    apiCall('add_user', { username, display_name: display || username, email, password, role }).then(r => {
      if (r.success) {
        document.getElementById('new-user-username').value = '';
        document.getElementById('new-user-display').value  = '';
        document.getElementById('new-user-email').value    = '';
        document.getElementById('new-user-password').value = '';
        loadUsers();
      } else { alert(r.error || 'Failed to add user.'); }
    });
  }

  function deleteUser(id) {
    if (!confirm('Delete this user?')) return;
    apiCall('delete_user', { id }).then(r => {
      if (r.success) showUsersList();
      else alert(r.error || 'Failed to delete user.');
    });
  }

  function closeHistoryModal() {
    document.getElementById('history-modal-overlay').style.display = 'none';
  }

  async function restoreRevision(revisionId, workbookId) {
    if (!confirm('Restore this version? Your current data will be saved as a revision before restoring.')) return;
    try {
      await apiCall('restore_revision', { revision_id: revisionId, workbook_id: workbookId, changed_by: '' });
      closeHistoryModal();
      // Reload the workbook data
      await loadFromDatabase();
      router();
    } catch (e) {
      alert('Failed to restore revision.');
    }
  }

  /* ── Archive Modal ─────────────────────────────────────────────────────── */
  let archiveData = { workbooks: [], clients: [] };
  let archiveTab = 'workbooks';

  function openArchiveModal() {
    const overlay = document.getElementById('archive-modal-overlay');
    overlay.style.display = 'flex';
    archiveTab = 'workbooks';
    loadArchiveData();
  }

  function closeArchiveModal() {
    document.getElementById('archive-modal-overlay').style.display = 'none';
  }

  function switchArchiveTab(tab, btn) {
    archiveTab = tab;
    document.querySelectorAll('.archive-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    renderArchiveList();
  }

  async function loadArchiveData() {
    const list = document.getElementById('archive-list');
    list.innerHTML = '<p style="color:var(--text-muted); text-align:center;">Loading...</p>';
    try {
      const res = await fetch('api.php?action=get_archived');
      const data = await res.json();
      if (data.success) {
        archiveData = data;
        renderArchiveList();
      }
    } catch (e) {
      list.innerHTML = '<p style="color:var(--text-muted); text-align:center;">Failed to load archive.</p>';
    }
  }

  function renderArchiveList() {
    const list = document.getElementById('archive-list');
    const items = archiveTab === 'workbooks' ? archiveData.workbooks : archiveData.clients;

    if (!items || !items.length) {
      list.innerHTML = '<p style="color:var(--text-muted); text-align:center; padding:20px;">No archived items.</p>';
      return;
    }

    const actionStyle = 'background:none; border:none; cursor:pointer; font-size:12px; font-weight:600; font-family:inherit; padding:0;';

    if (archiveTab === 'workbooks') {
      list.innerHTML = items.map(wb => `
        <div class="archive-item">
          <div style="flex:1; min-width:0;">
            <div style="font-size:13px; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${wb.product_name}</div>
            <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">${wb.client_name} &middot; Deleted ${new Date(wb.deleted_at).toLocaleDateString()}${wb.deleted_by ? ' by ' + wb.deleted_by : ''}</div>
          </div>
          <div style="display:flex; gap:14px; flex-shrink:0; margin-left:16px;">
            <button onclick="restoreArchivedWorkbook(${wb.id})" style="${actionStyle} color:var(--success);" title="Restore workbook">Restore</button>
            <button onclick="permanentDeleteWorkbook(${wb.id})" style="${actionStyle} color:var(--danger);" title="Permanently delete">Delete</button>
          </div>
        </div>
      `).join('');
    } else {
      list.innerHTML = items.map(cl => `
        <div class="archive-item">
          <div style="flex:1; min-width:0;">
            <div style="font-size:13px; font-weight:600;">${cl.name}</div>
            <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">Deleted ${new Date(cl.deleted_at).toLocaleDateString()}${cl.deleted_by ? ' by ' + cl.deleted_by : ''}</div>
          </div>
          <div style="display:flex; gap:14px; flex-shrink:0; margin-left:16px;">
            <button onclick="restoreArchivedClient(${cl.id})" style="${actionStyle} color:var(--success);" title="Restore client">Restore</button>
            <button onclick="permanentDeleteClient(${cl.id})" style="${actionStyle} color:var(--danger);" title="Permanently delete">Delete</button>
          </div>
        </div>
      `).join('');
    }
  }

  async function restoreArchivedWorkbook(id) {
    if (!confirm('Restore this workbook?')) return;
    try {
      await apiCall('restore_workbook', { id });
      await loadFromDatabase();
      rebuildSidebar();
      loadArchiveData();
    } catch (e) {
      alert('Failed to restore workbook.');
    }
  }

  async function restoreArchivedClient(id) {
    if (!confirm('Restore this client and all its workbooks?')) return;
    try {
      await apiCall('restore_client', { id });
      await loadFromDatabase();
      rebuildSidebar();
      renderRecentWorkbooks();
      loadArchiveData();
    } catch (e) {
      alert('Failed to restore client.');
    }
  }

  async function permanentDeleteWorkbook(id) {
    if (!confirm('Permanently delete this workbook? This cannot be undone.')) return;
    try {
      await apiCall('permanent_delete_workbook', { id });
      loadArchiveData();
    } catch (e) {
      alert('Failed to delete workbook.');
    }
  }

  async function permanentDeleteClient(id) {
    if (!confirm('Permanently delete this client and ALL its workbooks? This cannot be undone.')) return;
    try {
      await apiCall('permanent_delete_client', { id });
      loadArchiveData();
    } catch (e) {
      alert('Failed to delete client.');
    }
  }

  /* ── Router ─────────────────────────────────────────────────────────────── */
  function showView(viewId) {
    document.querySelectorAll('.view').forEach(v => v.classList.remove('active'));
    document.getElementById(viewId).classList.add('active');
  }

  function updateSidebarActive(clientName) {
    document.querySelectorAll('.sidebar-nav .nav-item').forEach(a => {
      const nameSpan = a.querySelector('span');
      const name = nameSpan ? nameSpan.textContent.trim() : a.textContent.trim();
      a.classList.toggle('active', name === clientName);
    });

    // If the active client lives inside a collapsed section, expand it so it's visible
    ['nav-section-clients', 'nav-section-starred'].forEach(sectionId => {
      const section = document.getElementById(sectionId);
      if (!section || !section.classList.contains('collapsed')) return;
      const body = section.querySelector('.nav-section-body');
      if (body && body.querySelector('.nav-item.active')) {
        section.classList.remove('collapsed');
        // Persist the expanded state
        const saved = JSON.parse(localStorage.getItem('ms_nav_collapsed') || '{}');
        saved[sectionId] = false;
        localStorage.setItem('ms_nav_collapsed', JSON.stringify(saved));
      }
    });
  }

  /* ── Action Menu ──────────────────────────────────────────────────────────── */
  function toggleActionMenu(btn) {
    const menu = btn.nextElementSibling;
    const wasOpen = menu.classList.contains('open');
    // Close all menus first
    document.querySelectorAll('.action-menu.open').forEach(m => {
      m.classList.remove('open');
      m.style.top = '';
      m.style.right = '';
    });
    if (!wasOpen) {
      const rect = btn.getBoundingClientRect();
      menu.classList.add('open');
      const menuH = menu.offsetHeight;
      // Flip upward if menu would overflow viewport bottom
      if (rect.bottom + 4 + menuH > window.innerHeight) {
        menu.style.top = (rect.top - menuH - 4) + 'px';
      } else {
        menu.style.top = (rect.bottom + 4) + 'px';
      }
      menu.style.right = (window.innerWidth - rect.right) + 'px';
    }
  }

  document.addEventListener('click', () => {
    document.querySelectorAll('.action-menu.open').forEach(m => m.classList.remove('open'));
  });

  function isFlowComplete(flow) {
    return flowSteps.every(s => flow[s]);
  }

  function shortDate(dateStr) {
    const months = {Jan:1,Feb:2,Mar:3,Apr:4,May:5,Jun:6,Jul:7,Aug:8,Sep:9,Oct:10,Nov:11,Dec:12};
    const parts = dateStr.split(' ');
    return months[parts[1]] + '/' + parseInt(parts[0]);
  }

  function getCurrentStepName(flow) {
    if (isFlowComplete(flow)) return 'Complete';
    for (let i = flowSteps.length - 1; i >= 0; i--) {
      if (flow[flowSteps[i]]) return flowLabelsShort[i];
    }
    return 'Not Started';
  }

  function onClientDetailChange(el, encodedName, key) {
    const clientName = decodeURIComponent(encodedName);
    if (!clientDetails[clientName]) clientDetails[clientName] = {};
    clientDetails[clientName][key] = el.value;
    saveClientDetail(clientName);
    // AM/SP/Operations assignment OR rate change → recompute commissions
    // so the dashboard reflects the new ownership/rate without a page
    // reload. Fire-and-forget; recompute is idempotent and UPSERTs
    // pending rows.
    if (key === 'account_manager'     || key === 'salesperson'     || key === 'operations_person' ||
        key === 'account_manager_pct' || key === 'salesperson_pct' || key === 'operations_pct') {
      apiCall('recompute_commissions').then(() => {
        if (typeof loadCommissions === 'function') loadCommissions();
      }).catch(() => {});
    }
  }

  let _clientDetailSaveTimer = null;
  function saveClientDetail(clientName) {
    clearTimeout(_clientDetailSaveTimer);
    _clientDetailSaveTimer = setTimeout(async () => {
      const d = clientDetails[clientName];
      if (!d || !d.id) return;
      await apiCall('save_client_detail', {
        id: d.id,
        email: d.email,
        phone: d.phone,
        primary_contact: d.primary_contact,
        email2:           d.email2           || '',
        phone2:           d.phone2           || '',
        primary_contact2: d.primary_contact2 || '',
        billing_address: d.billing_address,
        shipping_address: d.shipping_address,
        notes: d.notes,
        account_manager:     d.account_manager     || '',
        salesperson:         d.salesperson         || '',
        operations_person:   d.operations_person   || '',
        // Per-role rate overrides. Empty string → server stores NULL →
        // helper falls back to the role's default (AM/SP → 0%,
        // Operations → 5%). "0" stays a real zero (no commission).
        account_manager_pct: (d.account_manager_pct === undefined || d.account_manager_pct === null) ? '' : d.account_manager_pct,
        salesperson_pct:     (d.salesperson_pct     === undefined || d.salesperson_pct     === null) ? '' : d.salesperson_pct,
        operations_pct:      (d.operations_pct      === undefined || d.operations_pct      === null) ? '' : d.operations_pct
      });
    }, 800);
  }

  function renderClientDetailCard(clientName) {
    const wrap = document.getElementById('client-detail-card-wrap');
    if (!wrap) return;
    const d = clientDetails[clientName] || {};
    const items = clientData[clientName] || [];
    const total = items.length;
    const completed = items.filter(i => isFlowComplete(i.flow)).length;
    const open = total - completed;

    // ── Financial totals ────────────────────────────────────────────────
    // Open Orders / Closed Orders: pulled from the Order system.
    // An order "belongs" to this client if order.clientName matches OR any
    // entry's clientName matches. Use orderTotals() for the USD value.
    let openOrdersUsd = 0, closedOrdersUsd = 0;
    let openOrderCount = 0, closedOrderCount = 0;
    Object.values(orderData || {}).forEach(order => {
      const belongsHere = order.clientName === clientName ||
        (order.entries || []).some(e => e.clientName === clientName);
      if (!belongsHere) return;
      const tot = orderTotals(order).totalUsd;
      if (order.status === 'complete') {
        closedOrdersUsd += tot;
        closedOrderCount++;
      } else {
        openOrdersUsd += tot;
        openOrderCount++;
      }
    });

    // Total Pipeline: ALL open (flow not complete) workbooks for this client
    // that have pricing filled in. Uses the selected tier (Total Delivered Cost
    // proxy = tier qty × tier unit price in USD). Falls back to client quote
    // fields if no tier is selected.
    let pipelineUsd = 0;
    items.forEach(item => {
      if (isFlowComplete(item.flow)) return;
      const detail = workbookDetail[`${clientName}|${item.id}`] || {};
      const tiers = Array.isArray(detail.tiers) ? detail.tiers : [];
      const tier = tiers.find(t => t.id == detail.selectedTierIdx) || tiers[0];
      if (tier && parseFloat(tier.price) && parseFloat(tier.qty)) {
        pipelineUsd += (parseFloat(tier.price) / USD_TO_RMB) * parseFloat(tier.qty);
      } else {
        // Fall back to client quote fields
        const qty = parseFloat(detail.quoteClQty) || 0;
        const price = parseFloat(detail.quoteClUnitPrice) || 0;
        const ship = parseFloat(detail.quoteClShipping) || 0;
        if (qty && price) pipelineUsd += qty * price + ship;
      }
    });
    pipelineUsd = Math.round(pipelineUsd * 100) / 100;

    const fmtUsd = n => '$' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    // Initials from client name
    const initials = clientName.split(/\s+/).map(w => w[0]).join('').slice(0, 2).toUpperCase();

    const enc = encodeURIComponent(clientName);
    const field = (key, label, placeholder, isTextarea = false) => {
      const val = (d[key] || '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;');
      const handler = `onClientDetailChange(this,'${enc}','${key}')`;
      if (isTextarea) {
        return `<div class="cdc-field">
          <div class="cdc-label">${label}</div>
          <textarea class="cdc-value" rows="2" placeholder="${placeholder}" oninput="${handler}">${val}</textarea>
        </div>`;
      }
      return `<div class="cdc-field">
        <div class="cdc-label">${label}</div>
        <input class="cdc-value" type="text" value="${val}" placeholder="${placeholder}" oninput="${handler}" />
      </div>`;
    };
    // Employee dropdown for AM / Salesperson / Operations. Drives commission
    // tracking — when a workbook for this client is promoted, whoever is
    // assigned here gets a commission row written. The neighboring percent
    // input is the per-client commission rate override (blank = use the
    // role's default rate, indicated by the placeholder).
    //
    // `key` selects the role:
    //   'account_manager'   — AM dropdown (Parker / Jackson), default 0%
    //   'salesperson'       — SP dropdown (Parker / Jackson), default 0%
    //   'operations_person' — Operations dropdown (Karen),    default 5%
    const empSelect = (key, label) => {
      const cur    = (d[key] || '').trim();
      // Older sibling fields use `<role>_pct`; the operations field is
      // stored on `operations_person` so we strip the `_person` suffix
      // before tacking on `_pct` (otherwise we'd save into a phantom
      // `operations_person_pct` column).
      const pctKey = (key === 'operations_person' ? 'operations' : key) + '_pct';
      // Read raw value first so 0 (explicit "no commission") survives.
      // Also handle DECIMAL columns which come back as strings like "20.00".
      const pctRaw = d[pctKey];
      const pctVal = (pctRaw === null || pctRaw === undefined || pctRaw === '')
                   ? '' : String(parseFloat(pctRaw));
      // Per-role configuration: the people you can pick + the default rate
      // shown as placeholder when the override is blank. Karen lives alone
      // in the Operations slot for now; the array makes it trivial to
      // grow that list later without changing this helper.
      let people, defaultPct, defaultLabel;
      if (key === 'operations_person') {
        people       = ['Karen'];
        defaultPct   = '5';
        defaultLabel = '5%';
      } else {
        people       = ['Parker Low', 'Jackson Hollberg'];
        defaultPct   = '0';
        defaultLabel = '0% (set manually)';
      }
      const opt        = (v) => `<option value="${v}"${v === cur ? ' selected' : ''}>${v || '— Unassigned —'}</option>`;
      const handler    = `onClientDetailChange(this,'${enc}','${key}')`;
      const pctHandler = `onClientDetailChange(this,'${enc}','${pctKey}')`;
      // Wrap the select in ship-select-wrap so we get the same custom triangle
      // arrow used by every other dropdown in the app. appearance:none on the
      // <select> kills the native arrow so we don't double-up. The % input
      // sits to the right at a fixed 86px so it never crowds the dropdown.
      // Pin both inputs to the same explicit height — without it, browsers
      // give <select> a slightly larger native min-height (even with
      // appearance:none) than a sibling <input>, so they end up looking
      // mismatched. 36px is comfy and matches the cdc-value text padding.
      return `<div class="cdc-field">
        <div class="cdc-label">${label}</div>
        <div style="display:flex; gap:6px; align-items:center;">
          <span class="ship-select-wrap" style="display:block; flex:1; min-width:0;">
            <select class="cdc-value" onchange="${handler}"
              style="appearance:none; -webkit-appearance:none; -moz-appearance:none; padding-right:28px; cursor:pointer; height:36px; line-height:1.2; box-sizing:border-box;">
              ${opt('')}
              ${people.map(opt).join('')}
            </select>
          </span>
          <div style="position:relative; width:86px; flex-shrink:0; height:36px;"
               title="Commission rate for this role on this client. Leave blank to use the role default (${defaultLabel}).">
            <input class="cdc-value" type="number" step="0.01" min="0" max="100"
              value="${pctVal}" placeholder="${defaultPct}"
              oninput="${pctHandler}"
              style="padding-right:22px; text-align:right; font-variant-numeric:tabular-nums; height:36px; line-height:1.2; box-sizing:border-box;" />
            <span style="position:absolute; right:9px; top:50%; transform:translateY(-50%);
                         font-size:12px; color:var(--text-muted); pointer-events:none;">%</span>
          </div>
        </div>
      </div>`;
    };

    wrap.innerHTML = `
      <div class="client-detail-card">
        <div class="cdc-avatar">${initials}</div>
        <div class="cdc-left">
          <div class="cdc-name">${clientName}</div>
          <div class="cdc-fields">
            ${field('email',            'Email',            'client@example.com')}
            ${field('phone',            'Phone',            '+1 (555) 000-0000')}
            ${field('primary_contact',  'Primary Contact',  'Contact name')}
            ${field('email2',           'Email 2',          'second@example.com')}
            ${field('phone2',           'Phone 2',          '+1 (555) 000-0000')}
            ${field('primary_contact2', 'Contact 2',        'Second contact name')}
            ${empSelect('account_manager',   'Account Manager')}
            ${empSelect('salesperson',       'Salesperson')}
            ${empSelect('operations_person', 'Operations')}
            ${field('billing_address', 'Billing Address',  'Street, City, State ZIP', true)}
            ${field('shipping_address','Shipping Address', 'Same as billing or different', true)}
            ${field('notes',           'Notes',            'Internal notes…', true)}
          </div>
        </div>
        <div class="cdc-financial">
          <div class="cdc-fin-title">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
            Financial Summary
          </div>
          <div class="cdc-fin-stat">
            <span class="cdc-fin-stat-label">Open Orders <span style="font-weight:400; opacity:0.7;">(${openOrderCount})</span></span>
            <span class="cdc-fin-stat-value accent">${fmtUsd(openOrdersUsd)}</span>
          </div>
          <div class="cdc-fin-stat">
            <span class="cdc-fin-stat-label">Closed Orders <span style="font-weight:400; opacity:0.7;">(${closedOrderCount})</span></span>
            <span class="cdc-fin-stat-value success">${fmtUsd(closedOrdersUsd)}</span>
          </div>
          <div class="cdc-fin-stat" style="margin-top:6px; padding-top:10px; border-top:1px solid rgba(107,147,255,0.2);">
            <span class="cdc-fin-stat-label">Total Pipeline</span>
            <span class="cdc-fin-stat-value" style="font-size:18px;">${fmtUsd(pipelineUsd)}</span>
          </div>
        </div>
      </div>
    `;
  }

  function renderDashboard(clientName) {
    const items = [...(clientData[clientName] || [])];
    const tbody = document.getElementById('dash-tbody');
    const emptyState = document.getElementById('dash-empty');
    const table = document.getElementById('dash-table');

    if (items.length === 0) {
      table.style.display = 'none';
      emptyState.style.display = 'block';
    } else {
      table.style.display = '';
      emptyState.style.display = 'none';
    }

    // ── Flow-progress score (higher = further along) ─────────────────────
    const flowScore = flow => flowSteps.filter(s => flow[s]).length;

    // ── Build workbookId → order lookup for this client ──────────────────
    // Maps workbookId (number) → { orderId, orderName, orderStatus }
    const wbOrderMap = {};
    Object.values(orderData || {}).forEach(order => {
      (order.entries || []).forEach(e => {
        if (e.clientName === clientName) {
          wbOrderMap[parseInt(e.workbookId)] = {
            orderId:     order.id,
            orderName:   order.name || `Order #${order.id}`,
            orderStatus: order.status
          };
        }
      });
    });

    // ── Sort ──────────────────────────────────────────────────────────────
    const months = { Jan:0, Feb:1, Mar:2, Apr:3, May:4, Jun:5, Jul:6, Aug:7, Sep:8, Oct:9, Nov:10, Dec:11 };
    const parseDate = d => {
      if (!d) return new Date(0);
      const parts = d.split(' ');
      return new Date(2000 + parseInt(parts[2]), months[parts[1]], parseInt(parts[0]));
    };
    const dir = _clientSortDir === 'asc' ? 1 : -1;
    items.sort((a, b) => {
      if (_clientSortField === 'product') return dir * a.product.localeCompare(b.product);
      if (_clientSortField === 'dateSubmitted') return dir * (parseDate(a.dateSubmitted) - parseDate(b.dateSubmitted));
      if (_clientSortField === 'status') return dir * (getCurrentStepName(a.flow) || '').localeCompare(getCurrentStepName(b.flow) || '');
      // Default: furthest-along first, then newest date as tiebreaker
      const scoreDiff = flowScore(b.flow) - flowScore(a.flow);
      if (scoreDiff !== 0) return scoreDiff;
      return parseDate(b.dateCreated) - parseDate(a.dateCreated);
    });

    tbody.innerHTML = items.map(item => {
      const complete = isFlowComplete(item.flow);
      const stepName = getCurrentStepName(item.flow);
      const stepClass = complete ? 'complete' : 'in-progress';

      // Order column
      const orderInfo = wbOrderMap[parseInt(item.id)];
      const orderCell = orderInfo
        ? `<span class="wb-order-pill ${orderInfo.orderStatus === 'complete' ? 'wb-order-pill--closed' : ''}"
              onclick="event.stopPropagation(); location.hash='#/orders'"
              title="View order">${orderInfo.orderName}</span>`
        : '<span style="color:var(--text-muted);">—</span>';

      return `
      <tr class="${complete ? 'row-complete' : ''}" onclick="location.hash='#/client/${encodeURIComponent(clientName).replace(/'/g,'%27')}/workbook/${item.id}'">
        <td class="product-name">${item.product}</td>
        <td class="col-date-created"><span class="date-full">${item.dateCreated}</span><span class="date-short">${shortDate(item.dateCreated)}</span></td>
        <td class="col-date-submitted">${item.dateSubmitted || '—'}</td>
        <td class="col-order">${orderCell}</td>
        <td class="col-flow">
          <div class="flow-group">
            ${flowSteps.map((s, i) => `
              <div class="flow-step">
                <div class="flow-bar ${item.flow[s] ? 'filled' : ''}"></div>
                <span class="flow-label">${flowLabels[i]}</span>
              </div>
            `).join('')}
          </div>
        </td>
        <td class="col-mobile-status"><span class="mobile-status-badge ${stepClass}">${stepName}</span></td>
        <td><button class="action-icon-btn" onclick="event.stopPropagation(); toggleActionMenu(this)" title="Actions">⋮</button><div class="action-menu"><a onclick="event.stopPropagation(); location.hash='#/client/${encodeURIComponent(clientName).replace(/'/g,'%27')}/workbook/${item.id}'">View</a><a onclick="event.stopPropagation(); location.hash='#/client/${encodeURIComponent(clientName).replace(/'/g,'%27')}/workbook/${item.id}'">Edit</a><a onclick="event.stopPropagation(); duplicateWorkbook('${clientName.replace(/'/g, "\\'")}', '${item.id}')">Duplicate</a><a onclick="event.stopPropagation(); openDeleteModal('${clientName.replace(/'/g, "\\'")}', '${item.id}', '${item.product.replace(/'/g, "\\'")}')">Delete</a></div></td>
      </tr>
    `}).join('');

    document.getElementById('header-title').textContent = clientName + ' — Workbooks';
    updateSidebarActive(clientName);
    renderClientDetailCard(clientName);
    showView('view-dashboard');
    addRecentNav({ type: 'client', label: clientName, href: `#/client/${encodeURIComponent(clientName)}` });
  }

  function fillWorkbook(clientName, workbookId) {
    try { _fillWorkbookInner(clientName, workbookId); }
    catch(e) {
      console.error('[MS fillWorkbook]', e);
      _filling = false;
      // Show a recoverable error rather than a silent blank screen
      const t = document.getElementById('header-title');
      if (t) t.textContent = 'Error loading workbook';
      showView('view-workbook');
      const wv = document.getElementById('view-workbook');
      if (wv && !wv.querySelector('.ms-load-error')) {
        const d = document.createElement('div');
        d.className = 'ms-load-error';
        d.style.cssText = 'padding:48px 32px;text-align:center;';
        d.innerHTML = `<p style="font-size:16px;font-weight:700;color:var(--danger);">Could not load this workbook</p>
          <p style="margin-top:8px;color:var(--text-muted);font-size:13px;">An unexpected error occurred. Your data has not been lost.</p>
          <a href="#" onclick="document.querySelector('.ms-load-error')?.remove();location.hash=''"
             style="display:inline-block;margin-top:16px;font-size:13px;color:var(--accent);">← Back to All Workbooks</a>`;
        wv.prepend(d);
      }
    }
  }
  function _fillWorkbookInner(clientName, workbookId) {
    // Save previous workbook immediately before switching to a different one
    const switching = currentClient && currentWorkbookId &&
                      (currentClient !== clientName || currentWorkbookId !== workbookId);
    if (switching) {
      clearTimeout(saveTimer);
      const prevDetail = collectWorkbookDetail();
      const prevKey = `${currentClient}|${currentWorkbookId}`;
      workbookDetail[prevKey] = prevDetail;
      syncClientDataName(currentClient, currentWorkbookId, prevDetail);
      const prevDbId = dbWorkbookMap[prevKey] || currentWorkbookId;
      // create_revision: true — switching workbooks is a meaningful save point
      apiCall('save_workbook_detail', { id: prevDbId, detail: prevDetail, changed_by: getCurrentUser(), create_revision: true });
      saveToLocalStorage();
    }

    _filling = true;
    currentClient = clientName;
    currentWorkbookId = workbookId;

    // Start presence heartbeat for this workbook (stops any previous one first)
    startPresenceHeartbeat(parseInt(workbookId, 10));

    // Back button context
    const backBtn = document.getElementById('btn-back');
    if (backBtn) {
      const bHash  = _wbBackHash  || `#/client/${encodeURIComponent(clientName)}`;
      const bLabel = _wbBackLabel || 'Back to Workbooks';
      backBtn.textContent = `← ${bLabel}`;
      backBtn.onclick = (e) => { e.preventDefault(); _wbBackHash = null; _wbBackLabel = null; location.hash = bHash; };
      _wbBackHash = null; _wbBackLabel = null;
    }

    // Reset to default Workbook tab
    document.querySelectorAll('.wb-tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.wb-tab').forEach(el => el.classList.remove('active'));
    const defaultTab = document.getElementById('wb-tab-workbook');
    if (defaultTab) defaultTab.classList.add('active');
    const defaultBtn = document.querySelector('.wb-tab[onclick*="workbook"]');
    if (defaultBtn) defaultBtn.classList.add('active');

    const key = `${clientName}|${workbookId}`;
    const data = workbookDetail[key];

    // Render status bar from client data
    const items = clientData[clientName] || [];
    const item = items.find(i => i.id === parseInt(workbookId));
    if (item) renderStatusBar(item.flow);

    // Clear existing tier rows (both tables)
    document.getElementById('tier-body').innerHTML = '';
    tierCount = 0;
    document.getElementById('wb-tier-body').innerHTML = '';
    wbTierCount = 0;

    function _s(id, val) { const el = document.getElementById(id); if (el) el.value = val || ''; }

    if (data && !Array.isArray(data) && (data.client || data.product || data.productImages || data.productImage)) {
      // Workbook tab
      _s('client-name', data.client);
      _s('product-name', data.product);
      _s('product-desc', data.desc);
      _s('dim-in-l', data.dimInL);
      _s('dim-in-w', data.dimInW);
      _s('dim-in-h', data.dimInH);
      // Load cm values or convert from inches
      if (data.dimCmL) {
        _s('dim-cm-l', data.dimCmL);
        _s('dim-cm-w', data.dimCmW);
        _s('dim-cm-h', data.dimCmH);
      } else if (data.dimInL) {
        convertDim('dim-in-l', 'dim-cm-l', 'in');
        convertDim('dim-in-w', 'dim-cm-w', 'in');
        convertDim('dim-in-h', 'dim-cm-h', 'in');
      }
      // Category / subcategory
      _s('product-category', data.productCategory);
      _s('product-category-2', data.productCategory2 || '');
      document.getElementById('cat2-wrap').classList.toggle('has-value', !!data.productCategory2);
      if (data.productCategory) updateSubcategories();
      _s('product-subcategory', data.productSubcategory);
      _s('product-subcategory-2', data.productSubcategory2 || '');
      document.getElementById('mat2-wrap').classList.toggle('has-value', !!data.productSubcategory2);
      checkSecondaryLock();
      _s('materials', data.materials);
      _s('pantone-text', data.pantone);
      _s('cmyk', data.cmyk);
      _s('color-notes', data.colorNotes);
      // RFQ Line Items
      document.getElementById('rfq-body').innerHTML = '';
      rfqCount = 0;
      const hasRfqData = data.rfqItems && Array.isArray(data.rfqItems) && data.rfqItems.length > 0
        && data.rfqItems.some(i => i.item || i.sku || i.qty || i.priceRmb || i.leadTime);
      if (hasRfqData) {
        data.rfqItems.forEach((rfqItem, idx) => {
          // First row: replace empty or old "Main Item" placeholder with the actual product name
          let itemName = rfqItem.item;
          if (idx === 0 && (!itemName || itemName === 'Main Item')) itemName = data.product || '';
          addRfqRow(itemName, rfqItem.sku || '', rfqItem.qty, rfqItem.priceRmb, rfqItem.leadTime, rfqItem.sample || false, rfqItem.variants || []);
        });
      } else if (data.qty || data.unitPriceRmb) {
        // Legacy: migrate single-row quote data to first RFQ row
        addRfqRow('', data.qty, data.unitPriceRmb, data.leadTime);
        addRfqRow(); addRfqRow();
      } else {
        // No RFQ data yet — seed first row with product name; SKU entered manually
        const _pn = data.product || '';
        addRfqRow(_pn, ''); addRfqRow(); addRfqRow();
      }
      // Ensure at least 3 rows
      while (document.querySelectorAll('#rfq-body tr').length < 3) {
        addRfqRow();
      }
      recalcRfqTotals();
      _s('quote-qc', data.qcNotes);
      _s('fee-sample-desc',  data.feeSampleDesc);
      _s('fee-sample-rmb',   data.feeSampleRmb);
      _s('fee-sample-usd',   data.feeSampleUsd);
      _s('fee-tooling-desc', data.feeToolingDesc);
      _s('fee-tooling-rmb',  data.feeToolingRmb);
      _s('fee-tooling-usd',  data.feeToolingUsd);
      _s('fee-die-desc',     data.feeDieDesc);
      _s('fee-die-rmb',      data.feeDieRmb);
      _s('fee-die-usd',      data.feeDieUsd);
      _s('fee-plate-desc',   data.feePlateDesc);
      _s('fee-plate-rmb',    data.feePlateRmb);
      _s('fee-plate-usd',    data.feePlateUsd);
      _s('fee-design-desc',  data.feeDesignDesc);
      _s('fee-design-usd',   data.feeDesignUsd);
      _extraFeeRows = [];
      _extraFeeCounter = 0;
      if (Array.isArray(data.extraFeeRows)) {
        data.extraFeeRows.forEach(r => {
          _extraFeeCounter++;
          _extraFeeRows.push({ id: _extraFeeCounter, type: r.type, desc: r.desc, rmb: r.rmb || 0, usd: r.usd || 0 });
        });
        renderExtraFeeRows();
      }
      // Shipment splits
      _shipmentSplits = [];
      _splitCounter = 0;
      if (Array.isArray(data.shipmentSplits)) {
        data.shipmentSplits.forEach(r => {
          _splitCounter++;
          _shipmentSplits.push({ id: _splitCounter, method: r.method || 'slow', qty: r.qty || '' });
        });
      }
      renderShipmentSplits();
      // Product images (gallery)
      if (data.productImages && Array.isArray(data.productImages) && data.productImages.length > 0) {
        _productImages = data.productImages.map(url => ({ url }));
      } else if (data.productImage && data.productImage.length > 10) {
        // Legacy: single base64 image — keep for backward compat
        _productImages = [{ url: data.productImage }];
      } else {
        _productImages = [];
      }
      renderImageGallery();
      // Product videos
      _productVideos = (data.productVideos && Array.isArray(data.productVideos)) ? data.productVideos.slice() : [];
      renderVideoGallery();
      // Pricing tiers (populate both Workbook and Pricing tab tables)
      const rawTiers = Array.isArray(data.tiers) ? data.tiers : [];
      const validTiers = rawTiers.filter(t => t && t.qty && String(t.qty).trim() !== '');
      if (validTiers.length) {
        validTiers.forEach(t => { addTierRow(t.qty, t.price); addWbTierRow(t.qty, t.price); });
      }
      if (tierCount === 0) {
        addTierRow(100); addWbTierRow(100);
        addTierRow(250); addWbTierRow(250);
        addTierRow(500); addWbTierRow(500);
      }
      recalcRfqTotals(); // sync RFQ unit price total → tier RMB inputs
      _selectedTierId = data.selectedTierIdx || null;
      calcAdditionalFees();
      // Dimensions & Carton Specifications (new fields)
      _s('dim-weight-kg',  data.dimWeightKg);
      _s('dim-weight-lbs', data.dimWeightLbs);
      _s('dim-packaging',  data.dimPackaging);
      if (data.dimWeightKg) convertWeight('dim-weight-kg','dim-weight-lbs','kg');
      _s('carton-inner-l-in', data.cartonInnerLIn); _s('carton-inner-l-cm', data.cartonInnerLCm);
      _s('carton-inner-w-in', data.cartonInnerWIn); _s('carton-inner-w-cm', data.cartonInnerWCm);
      _s('carton-inner-h-in', data.cartonInnerHIn); _s('carton-inner-h-cm', data.cartonInnerHCm);
      if (data.cartonInnerLIn && !data.cartonInnerLCm) convertDim('carton-inner-l-in','carton-inner-l-cm','in');
      if (data.cartonInnerWIn && !data.cartonInnerWCm) convertDim('carton-inner-w-in','carton-inner-w-cm','in');
      if (data.cartonInnerHIn && !data.cartonInnerHCm) convertDim('carton-inner-h-in','carton-inner-h-cm','in');
      _s('carton-outer-l-in', data.cartonOuterLIn); _s('carton-outer-l-cm', data.cartonOuterLCm);
      _s('carton-outer-w-in', data.cartonOuterWIn); _s('carton-outer-w-cm', data.cartonOuterWCm);
      _s('carton-outer-h-in', data.cartonOuterHIn); _s('carton-outer-h-cm', data.cartonOuterHCm);
      if (data.cartonOuterLIn && !data.cartonOuterLCm) convertDim('carton-outer-l-in','carton-outer-l-cm','in');
      if (data.cartonOuterWIn && !data.cartonOuterWCm) convertDim('carton-outer-w-in','carton-outer-w-cm','in');
      if (data.cartonOuterHIn && !data.cartonOuterHCm) convertDim('carton-outer-h-in','carton-outer-h-cm','in');
      // Shipping tab
      _s('carton-unit-weight', data.cartonUnitWeight);
      _s('carton-inner-weight', data.cartonInnerWeight);
      _s('carton-inner-count', data.cartonInnerCount);
      _s('carton-outer-weight', data.cartonOuterWeight);
      _s('carton-outer-count', data.cartonOuterCount);
      _s('pallet-total-cartons', data.palletTotalCartons);
      if (data.cartonUnitWeight) convertWeight('carton-unit-weight','carton-unit-weight-lbs','kg');
      if (data.cartonInnerWeight) convertWeight('carton-inner-weight','carton-inner-weight-lbs','kg');
      if (data.cartonOuterWeight) convertWeight('carton-outer-weight','carton-outer-weight-lbs','kg');
      updateOuterWeightHint();
      // Restore selected tier dropdown AFTER carton/weight fields are loaded
      populateTierDropdown();
      _s('freight-mode', data.freightMode);
      _s('freight-hs-code', data.freightHsCode);
      // Quote for Client tab
      _s('quote-date', data.quoteDate);
      _s('quote-valid-until', data.quoteValidUntil);
      _s('quote-cl-qty', data.quoteClQty);
      _s('quote-cl-unit-price', data.quoteClUnitPrice);
      _s('quote-cl-shipping', data.quoteClShipping);
      _s('quote-cl-notes', data.quoteClNotes);
      if (data.quoteClUnitPrice || data.quoteClShipping) calcQuoteTotal();
      // Invoice tab
      _s('inv-number', data.invNumber);
      _s('inv-date', data.invDate);
      _s('inv-due-date', data.invDueDate);
      _s('inv-qty', data.invQty);
      _s('inv-unit-price', data.invUnitPrice);
      _s('inv-shipping', data.invShipping);
      _s('inv-status', data.invStatus);
      _s('inv-method', data.invMethod);
      _s('inv-notes', data.invNotes);
      if (data.invUnitPrice || data.invShipping) calcInvoiceTotal();
      // Art tab
      _s('art-status', data.artStatus);
      _s('art-due-date', data.artDueDate);
      _s('art-notes', data.artNotes);
      if (data.artImages && Array.isArray(data.artImages) && data.artImages.length > 0) {
        _artImages = data.artImages.map(url => ({ url }));
      } else {
        _artImages = [];
      }
      renderArtGallery();
      _clientLogo = data.clientLogo || null;
      renderClientLogo();
      setTimeout(renderPalletViz, 50);
    } else {
      // Fill with basic info from the client list
      const items = clientData[clientName] || [];
      const item = items.find(i => i.id === parseInt(workbookId));
      document.getElementById('client-name').value = clientName;
      document.getElementById('product-name').value = item ? item.product : '';
      document.getElementById('product-desc').value = item ? item.description : '';
      // Clear other fields
      ['dim-in-l','dim-in-w','dim-in-h','dim-mm-l','dim-mm-w','dim-mm-h','materials','pantone-text','cmyk','color-notes','quote-qc'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
      });
      // Clear images and videos
      _productImages = [];
      renderImageGallery();
      _productVideos = [];
      renderVideoGallery();
      _artImages = [];
      renderArtGallery();
      _clientLogo = null;
      renderClientLogo();
      // Apply any category suggested during creation
      if (_pendingWorkbookCategory) {
        const pend = _pendingWorkbookCategory;
        _pendingWorkbookCategory = null;
        if (pend.cat) {
          document.getElementById('product-category').value = pend.cat;
          updateSubcategories();
          if (pend.sub) {
            // Set after updateSubcategories populates the options
            setTimeout(() => {
              const subSel = document.getElementById('product-subcategory');
              if (subSel) {
                subSel.value = pend.sub;
                if (!subSel.value) { // option not found, add it
                  const opt = document.createElement('option');
                  opt.value = pend.sub; opt.textContent = pend.sub;
                  subSel.appendChild(opt);
                  subSel.value = pend.sub;
                }
              }
            }, 50);
          }
        } else {
          document.getElementById('product-category').value = '';
          document.getElementById('product-category-2').value = '';
          document.getElementById('cat2-wrap').classList.remove('has-value');
          document.getElementById('product-subcategory').innerHTML = '<option value="">Select category first...</option>';
          document.getElementById('product-subcategory-2').innerHTML = '<option value="">None</option>';
          document.getElementById('mat2-wrap').classList.remove('has-value');
        }
      } else {
        document.getElementById('product-category').value = '';
        document.getElementById('product-category-2').value = '';
        document.getElementById('cat2-wrap').classList.remove('has-value');
        document.getElementById('product-subcategory').innerHTML = '<option value="">Select category first...</option>';
        document.getElementById('product-subcategory-2').innerHTML = '<option value="">None</option>';
        document.getElementById('mat2-wrap').classList.remove('has-value');
      }
      addTierRow(100); addWbTierRow(100);
      addTierRow(250); addWbTierRow(250);
      addTierRow(500); addWbTierRow(500);
      _selectedTierId = null;
      populateTierDropdown();
      // Seed RFQ with product name only; SKU is entered manually
      document.getElementById('rfq-body').innerHTML = '';
      rfqCount = 0;
      const _newProd = item ? (item.product || '') : '';
      addRfqRow(_newProd, ''); addRfqRow(); addRfqRow();
      recalcRfqTotals();
    }

    // Trigger filled state on all inputs
    document.querySelectorAll('#view-workbook input, #view-workbook textarea').forEach(el => updateFilled(el));

    const prodName = document.getElementById('product-name').value || 'Workbook';
    document.getElementById('header-title').textContent = clientName + ' — ' + prodName;
    updateSidebarActive(clientName);
    fillQuoteInvoice(clientName, prodName);
    showView('view-workbook');
    addRecentNav({ type: 'workbook', label: prodName, sub: clientName, href: `#/client/${encodeURIComponent(clientName)}/workbook/${workbookId}` });
    calcFreight();
    // Delay clearing _filling to let queued input events (from calcFreight etc.) fire while still blocked
    setTimeout(() => {
      _filling = false;
      // Wire up presence focus tracking now that the RFQ table is populated
      _initPresenceFocusTracking();
      // Re-apply lock after all dynamic rows (RFQ, tiers) have been added
      if (_wbLocked) lockWorkbookTab(true);
      // Update promote button to show "X SKUs in Inventory" if already promoted
      updatePromoteButton();
    }, 200);
  }  // end _fillWorkbookInner

  function syncClientDataName(client, workbookId, detail) {
    const items = clientData[client] || [];
    const item = items.find(i => i.id === parseInt(workbookId));
    if (item && detail.product) {
      item.product = detail.product;
      item.description = detail.desc || item.description;
    }
  }

  function getCurrentUser() {
    return (window.MS_SESSION && window.MS_SESSION.name) ? window.MS_SESSION.name : (localStorage.getItem('ms_user_name') || '');
  }

  function saveCurrentWorkbookIfOpen() {
    // No _appReady check here — navigation saves must always fire
    if (!currentClient || !currentWorkbookId) return;
    clearTimeout(saveTimer);
    const detail = collectWorkbookDetail();
    const key = `${currentClient}|${currentWorkbookId}`;
    workbookDetail[key] = detail;
    syncClientDataName(currentClient, currentWorkbookId, detail);
    const dbId = dbWorkbookMap[key] || currentWorkbookId;
    showSaveStatus('saving');
    // create_revision: true on navigation saves — these become history entries
    apiCall('save_workbook_detail', { id: dbId, detail: detail, changed_by: getCurrentUser(), create_revision: true })
      .then(r => showSaveStatus(r && r.success ? 'saved' : 'error'));
    saveToLocalStorage();
  }

  function showSaveStatus(state) {
    const el = document.getElementById('save-status');
    if (!el) return;
    el.textContent = state === 'saving' ? 'Saving…' : state === 'saved' ? 'Saved ✓' : 'Save failed';
    el.style.color = state === 'error' ? 'var(--danger)' : state === 'saved' ? 'var(--success)' : 'var(--text-muted)';
    el.style.opacity = '1';
    if (state !== 'saving') setTimeout(() => { el.style.opacity = '0'; }, 2500);
  }

  /* ── Samples Dashboard ────────────────────────────────────────────────── */
  const SAMPLE_STATUSES = ['pending', 'requested', 'received', 'approved'];
  const SAMPLE_STATUS_LABELS = { pending: 'Pending', requested: 'Requested', received: 'Received', approved: 'Approved' };
  const SAMPLE_STATUS_COLORS = {
    pending:   { bg: 'rgba(107,147,255,0.12)', border: 'rgba(107,147,255,0.4)', text: '#6b93ff' },
    requested: { bg: 'rgba(251,175,52,0.12)',  border: 'rgba(251,175,52,0.4)', text: '#f59e0b' },
    received:  { bg: 'rgba(74,222,128,0.12)',  border: 'rgba(74,222,128,0.4)', text: '#4ade80' },
    approved:  { bg: 'rgba(52,211,153,0.12)',  border: 'rgba(52,211,153,0.4)', text: '#34d399' },
  };

  // sampleMeta is stored alongside rfqItems: sampleStatuses[rowIndex] = 'pending'|'received' etc.
  let _samplesFilter = 'all';

  function collectAllSamples() {
    const results = [];
    for (const [key, detail] of Object.entries(workbookDetail)) {
      if (!detail || !detail.rfqItems) continue;
      const [clientName, workbookId] = key.split('|');
      detail.rfqItems.forEach((item, idx) => {
        if (!item.sample) return;
        const status = (detail.sampleStatuses && detail.sampleStatuses[idx]) || 'pending';
        const usdPrice = item.priceRmb ? (parseFloat(item.priceRmb) / USD_TO_RMB).toFixed(2) : '';
        results.push({
          clientName,
          workbookId,
          product: detail.product || 'Untitled',
          item: item.item || '—',
          qty: item.qty || '—',
          priceRmb: item.priceRmb || '',
          priceUsd: usdPrice,
          leadTime: item.leadTime || '',
          status,
          rowIndex: idx,
          key
        });
      });
    }
    return results;
  }

  function renderSamplesDashboard() {
    document.getElementById('header-title').textContent = 'Sample Requests';
    document.querySelectorAll('.sidebar-nav .nav-item').forEach(a => a.classList.remove('active'));
    document.querySelectorAll('.nav-flat-link').forEach(a => a.classList.remove('active'));
    const samplesNav = document.getElementById('nav-samples-link');
    if (samplesNav) samplesNav.classList.add('active');
    showView('view-samples');

    const allSamples = collectAllSamples();

    // Update count badge
    const countBadge = document.getElementById('samples-count-badge');
    if (countBadge) countBadge.textContent = allSamples.length === 1 ? '1 sample' : `${allSamples.length} samples`;

    // Update samples badge in nav (delegate to canonical function for consistent logic)
    rebuildSamplesNav();

    // Stats row
    const statsRow = document.getElementById('samples-stats-row');
    if (statsRow) {
      const counts = { pending: 0, requested: 0, received: 0, approved: 0 };
      allSamples.forEach(s => { if (counts[s.status] !== undefined) counts[s.status]++; });
      const statCards = [
        { label: 'Total Samples', value: allSamples.length, icon: '◆', color: 'var(--accent)' },
        { label: 'Pending', value: counts.pending, icon: '⏳', color: '#6b93ff' },
        { label: 'Requested', value: counts.requested, icon: '📦', color: '#f59e0b' },
        { label: 'Received', value: counts.received, icon: '✅', color: '#4ade80' },
        { label: 'Approved', value: counts.approved, icon: '🎉', color: '#34d399' },
      ];
      statsRow.innerHTML = statCards.map(c => `
        <div style="background:var(--surface2); border:1px solid var(--border); border-radius:var(--radius); padding:14px 16px;">
          <div style="font-size:20px; font-weight:700; color:${c.color}; line-height:1.1;">${c.value}</div>
          <div style="font-size:11px; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.05em; margin-top:3px;">${c.label}</div>
        </div>
      `).join('');
    }

    renderSamplesTable(allSamples);
  }

  function renderSamplesTable(allSamples) {
    const tbody = document.getElementById('samples-tbody');
    const emptyEl = document.getElementById('samples-empty');
    const tableEl = document.getElementById('samples-table');

    const filtered = _samplesFilter === 'all' ? allSamples : allSamples.filter(s => s.status === _samplesFilter);

    if (filtered.length === 0) {
      tbody.innerHTML = '';
      if (emptyEl) emptyEl.style.display = '';
      if (tableEl) tableEl.style.display = 'none';
      return;
    }
    if (emptyEl) emptyEl.style.display = 'none';
    if (tableEl) tableEl.style.display = '';

    // String-escape HTML inputs so client/product names containing < & " etc.
    // can't break the markup or open injection holes via the title= attr.
    const esc = v => String(v == null ? '' : v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

    tbody.innerHTML = filtered.map((s, i) => {
      const sc = SAMPLE_STATUS_COLORS[s.status] || SAMPLE_STATUS_COLORS.pending;
      const wbHref     = `#/client/${encodeURIComponent(s.clientName)}/workbook/${s.workbookId}`;
      const clientHref = `#/client/${encodeURIComponent(s.clientName)}`;
      const rmb = s.priceRmb ? `¥${parseFloat(s.priceRmb).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}` : '—';
      const usd = s.priceUsd ? `$${parseFloat(s.priceUsd).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}` : '—';
      const lead = s.leadTime ? `${s.leadTime}d` : '—';
      // Client → deterministic per-name color via _clientChipStyle (same hue
      // every render). Now clickable so users can drill into the client.
      const clientPill = `<span class="inv-client-chip" style="${_clientChipStyle(s.clientName)}"
          onclick="event.stopPropagation(); location.hash='${clientHref.substring(1)}'"
          title="${esc(s.clientName)}">${esc(s.clientName)}</span>`;
      // Workbook → existing accent-blue pill with arrow, drills into the workbook.
      const wbPill = `<span class="inv-wb-pill" onclick="event.stopPropagation(); location.hash='${wbHref.substring(1)}'"
          title="${esc(s.product)}" style="max-width:100%; min-width:0;"
          ><span class="inv-wb-pill-text">${esc(s.product)}</span><span class="inv-wb-pill-arrow">→</span></span>`;
      return `
        <tr>
          <td style="font-weight:600; color:var(--text);" title="${esc(s.item)}">${esc(s.item)}</td>
          <td class="samples-pill-cell">${clientPill}</td>
          <td class="samples-pill-cell">${wbPill}</td>
          <td style="text-align:right; font-weight:600;">${s.qty !== '—' ? parseFloat(s.qty).toLocaleString('en-US') : '—'}</td>
          <td style="text-align:right;">${rmb}</td>
          <td style="text-align:right; color:var(--success);">${usd}</td>
          <td style="color:var(--text-muted);">${lead}</td>
          <td style="text-align:center;">
            <select class="sample-status-sel" onchange="updateSampleStatus('${s.key}', ${s.rowIndex}, this.value)"
              style="background:${sc.bg}; border:1px solid ${sc.border}; color:${sc.text}; border-radius:20px; padding:3px 8px; font-size:11px; font-weight:600; cursor:pointer; outline:none; -webkit-appearance:none; text-align:center; max-width:100%;">
              ${SAMPLE_STATUSES.map(st => `<option value="${st}" ${st === s.status ? 'selected' : ''}>${SAMPLE_STATUS_LABELS[st]}</option>`).join('')}
            </select>
          </td>
        </tr>
      `;
    }).join('');
  }

  function updateSampleStatus(key, rowIndex, newStatus) {
    if (!workbookDetail[key]) return;
    if (!workbookDetail[key].sampleStatuses) workbookDetail[key].sampleStatuses = {};
    workbookDetail[key].sampleStatuses[rowIndex] = newStatus;
    // Save to DB
    const [clientName, workbookId] = key.split('|');
    const dbId = dbWorkbookMap[key] || workbookId;
    apiCall('save_workbook_detail', { id: dbId, detail: workbookDetail[key], changed_by: getCurrentUser() });
    saveToLocalStorage();
    // Re-render table and stats
    const freshSamples = collectAllSamples();
    renderSamplesTable(freshSamples);
    rebuildSamplesNav();
    // Update count badge
    const countBadge = document.getElementById('samples-count-badge');
    if (countBadge) countBadge.textContent = freshSamples.length === 1 ? '1 sample' : `${freshSamples.length} samples`;
  }

  function filterSamples(filter, btn) {
    _samplesFilter = filter;
    document.querySelectorAll('#view-samples .status-filter-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    renderSamplesTable(collectAllSamples());
  }

  /* ── RFQ Queue ───────────────────────────────────────────────────────────── */

  // A workbook belongs in the RFQ queue when:
  //  - user clicked "RFQ" to send it (sentToRfq flag on detail)
  //  - status is still at Quote Submitted (not yet advanced to Quote to Client)
  function collectAllRfqs() {
    const results = [];
    for (const [key, detail] of Object.entries(workbookDetail)) {
      if (!detail || !detail.sentToRfq) continue;
      const [clientName, workbookId] = key.split('|');
      // Only include if flow is still at quoteSubmitted (not advanced past)
      const items = clientData[clientName] || [];
      const item = items.find(i => String(i.id) === String(workbookId));
      if (!item || !item.flow) continue;
      if (!item.flow.quoteSubmitted || item.flow.quoteClient) continue;

      // Aggregate totals across RFQ line items (parents + variants)
      const rfqItems = (detail.rfqItems || []).concat(
        (detail.rfqItems || []).flatMap(r => r.variants || [])
      );
      let totalQty = 0, totalRmb = 0;
      rfqItems.forEach(r => {
        const q = parseFloat(r.qty) || 0;
        const p = parseFloat(r.priceRmb) || 0;
        totalQty += q;
        totalRmb += q * p;
      });
      const totalUsd = totalRmb / USD_TO_RMB;

      results.push({
        clientName,
        workbookId,
        key,
        product: detail.product || 'Untitled',
        sentToRfqAt: detail.sentToRfqAt || null,
        lineItems: (detail.rfqItems || []).length,
        totalQty,
        totalRmb,
        totalUsd,
      });
    }
    // Oldest first
    results.sort((a, b) => {
      const at = a.sentToRfqAt ? new Date(a.sentToRfqAt).getTime() : 0;
      const bt = b.sentToRfqAt ? new Date(b.sentToRfqAt).getTime() : 0;
      return at - bt;
    });
    return results;
  }

  function rebuildRfqNav() {
    const all = collectAllRfqs();
    // No "actionable vs remaining" distinction here — every RFQ is actionable for Karen
    _applyNavBadge(document.getElementById('badge-rfq'), 0, all.length);
  }

  function _rfqTimeAgo(iso) {
    if (!iso) return '—';
    const then = new Date(iso).getTime();
    if (isNaN(then)) return '—';
    const secs = Math.max(0, (Date.now() - then) / 1000);
    if (secs < 60)     return 'just now';
    if (secs < 3600)   return `${Math.floor(secs/60)}m ago`;
    if (secs < 86400)  return `${Math.floor(secs/3600)}h ago`;
    if (secs < 604800) return `${Math.floor(secs/86400)}d ago`;
    const d = new Date(iso);
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  }

  function renderRfqDashboard() {
    document.getElementById('header-title').textContent = 'RFQ Queue';
    document.querySelectorAll('.sidebar-nav .nav-item').forEach(a => a.classList.remove('active'));
    document.querySelectorAll('.nav-flat-link').forEach(a => a.classList.remove('active'));
    const rfqNav = document.getElementById('nav-rfq-link');
    if (rfqNav) rfqNav.classList.add('active');
    showView('view-rfq');

    const all = collectAllRfqs();

    const countBadge = document.getElementById('rfq-count-badge');
    if (countBadge) countBadge.textContent = all.length === 1 ? '1 RFQ' : `${all.length} RFQs`;
    rebuildRfqNav();

    // Stats
    const statsRow = document.getElementById('rfq-stats-row');
    if (statsRow) {
      const totalLineItems = all.reduce((n, r) => n + r.lineItems, 0);
      const totalQty       = all.reduce((n, r) => n + r.totalQty, 0);
      const totalRmb       = all.reduce((n, r) => n + r.totalRmb, 0);
      const totalUsd       = all.reduce((n, r) => n + r.totalUsd, 0);
      const statCards = [
        { label: 'In Queue', value: all.length, color: 'var(--accent)' },
        { label: 'Line Items', value: totalLineItems, color: '#6b93ff' },
        { label: 'Total Qty', value: totalQty.toLocaleString('en-US'), color: '#f59e0b' },
        { label: 'Total (RMB)', value: `¥${Math.round(totalRmb).toLocaleString('en-US')}`, color: '#ef4444' },
        { label: 'Total (USD)', value: `$${totalUsd.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}`, color: '#10b981' },
      ];
      statsRow.innerHTML = statCards.map(c => `
        <div style="background:var(--surface2); border:1px solid var(--border); border-radius:var(--radius); padding:14px 16px;">
          <div style="font-size:20px; font-weight:700; color:${c.color}; line-height:1.1;">${c.value}</div>
          <div style="font-size:11px; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.05em; margin-top:3px;">${c.label}</div>
        </div>
      `).join('');
    }

    renderRfqTable(all);
  }

  function renderRfqTable(all) {
    const tbody   = document.getElementById('rfq-tbody');
    const emptyEl = document.getElementById('rfq-empty');
    const tableEl = document.getElementById('rfq-table');
    if (!tbody) return;

    if (all.length === 0) {
      tbody.innerHTML = '';
      if (emptyEl) emptyEl.style.display = '';
      if (tableEl) tableEl.style.display = 'none';
      return;
    }
    if (emptyEl) emptyEl.style.display = 'none';
    if (tableEl) tableEl.style.display = '';

    tbody.innerHTML = all.map(r => {
      const wbHref = `#/client/${encodeURIComponent(r.clientName)}/workbook/${r.workbookId}`;
      const rmb = r.totalRmb > 0 ? `¥${Math.round(r.totalRmb).toLocaleString('en-US')}` : '—';
      const usd = r.totalUsd > 0 ? `$${r.totalUsd.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}` : '—';
      return `
        <tr>
          <td><span class="inv-client-chip" style="${_clientChipStyle(r.clientName)}">${r.clientName}</span></td>
          <td>
            <span class="inv-wb-pill" onclick="location.hash='${wbHref.substring(1)}'" title="${r.product}"><span class="inv-wb-pill-text">${r.product}</span><span class="inv-wb-pill-arrow">→</span></span>
          </td>
          <td style="color:var(--text-muted); font-size:12px;" title="${r.sentToRfqAt || ''}">${_rfqTimeAgo(r.sentToRfqAt)}</td>
          <td style="text-align:right;">${r.lineItems}</td>
          <td style="text-align:right; font-weight:600;">${r.totalQty.toLocaleString('en-US')}</td>
          <td style="text-align:right; white-space:nowrap;">${rmb}</td>
          <td style="text-align:right; white-space:nowrap; color:var(--success);">${usd}</td>
        </tr>
      `;
    }).join('');
  }

  // Toggle current workbook's sentToRfq state — called from the RFQ button on status bar
  async function toggleSendToRfq() {
    if (!currentClient || !currentWorkbookId) return;
    const key = `${currentClient}|${currentWorkbookId}`;

    // Collect fresh DOM state so we never overwrite the DB record with a stale/partial detail.
    // collectWorkbookDetail now preserves sentToRfq/sentToRfqAt from the existing entry,
    // so we set the new flags AFTER collecting.
    let detail;
    try { detail = collectWorkbookDetail(); }
    catch(e) {
      console.error('[RFQ] collectWorkbookDetail failed, falling back to existing detail', e);
      detail = Object.assign({}, workbookDetail[key] || {});
    }
    const turningOn = !detail.sentToRfq;
    detail.sentToRfq   = turningOn;
    detail.sentToRfqAt = turningOn ? new Date().toISOString() : null;
    workbookDetail[key] = detail;

    // Persist to DB (fire-and-forget) + localStorage
    try {
      const dbId = dbWorkbookMap[key] || currentWorkbookId;
      apiCall('save_workbook_detail', { id: dbId, detail, changed_by: getCurrentUser() });
    } catch(e) { console.error('[RFQ] save failed', e); }
    saveToLocalStorage();

    // Reflect in nav + button state
    try { rebuildRfqNav(); } catch(e) { console.error('[RFQ] rebuildRfqNav failed', e); }
    const items = clientData[currentClient];
    const item = items ? items.find(i => i.id === parseInt(currentWorkbookId)) : null;
    if (item) renderStatusBar(item.flow);
  }

  function router() {
    hideRecentNav();
    resetSidebarSearch();
    let hash;
    try { hash = decodeURIComponent(location.hash || '#/'); }
    catch(e) { hash = location.hash || '#/'; }

    // Match: #/client/{name}/workbook/{id}
    const wbMatch = hash.match(/^#\/client\/(.+?)\/workbook\/(\d+)$/);
    if (wbMatch) {
      fillWorkbook(wbMatch[1], wbMatch[2]);
      return;
    }

    // Leaving the workbook editor — clear presence immediately
    stopPresenceHeartbeat();

    // Save current workbook before leaving its view
    saveCurrentWorkbookIfOpen();

    // Match: #/samples
    if (hash === '#/samples') {
      renderSamplesDashboard();
      return;
    }

    // Match: #/rfq
    if (hash === '#/rfq') {
      renderRfqDashboard();
      return;
    }

    // Match: #/shipments
    if (hash === '#/shipments') {
      renderShipmentsList();
      return;
    }

    // Match: #/shipment/{id}
    const shipMatch = hash.match(/^#\/shipment\/(\d+)$/);
    if (shipMatch) {
      renderShipmentDetail(shipMatch[1]);
      return;
    }

    // Match: #/orders
    if (hash === '#/orders') {
      renderOrdersList();
      return;
    }

    // Match: #/inventory
    if (hash === '#/inventory') {
      renderInventoryView();
      return;
    }

    // Match: #/commissions
    if (hash === '#/commissions') {
      renderCommissionsView();
      return;
    }

    // Match: #/order/{id}
    const orderMatch = hash.match(/^#\/order\/(\d+)$/);
    if (orderMatch) {
      renderOrderDetail(orderMatch[1]);
      return;
    }

    // Match: #/client/{name}
    const clientMatch = hash.match(/^#\/client\/(.+)$/);
    if (clientMatch) {
      renderDashboard(clientMatch[1]);
      return;
    }

    // Default: home
    document.getElementById('header-title').textContent = 'Market Sculpt';
    document.querySelectorAll('.sidebar-nav .nav-item').forEach(a => a.classList.remove('active'));
    document.querySelectorAll('.nav-flat-link').forEach(a => a.classList.remove('active'));
    const allWbNav = document.getElementById('nav-all-workbooks');
    if (allWbNav) allWbNav.classList.add('active');
    renderRecentWorkbooks();
    showView('view-home');
  }

  let _currentStatusFilter = 'all';
  let _homeSortField = 'date';
  let _homeSortDir = 'desc';
  let _clientSortField = 'date';
  let _clientSortDir = 'desc';

  function updateSortArrows(tableSelector, field, dir) {
    document.querySelectorAll(`${tableSelector} th.sortable`).forEach(th => {
      th.classList.remove('asc', 'desc');
    });
    const clickedTh = event.target.closest('th');
    if (clickedTh) clickedTh.classList.add(dir);
  }

  function sortHomeTable(field) {
    if (_homeSortField === field) {
      _homeSortDir = _homeSortDir === 'asc' ? 'desc' : 'asc';
    } else {
      _homeSortField = field;
      _homeSortDir = 'asc';
    }
    updateSortArrows('#view-home .dash-table', field, _homeSortDir);
    renderRecentWorkbooks();
  }

  function sortClientTable(field) {
    if (_clientSortField === field) {
      _clientSortDir = _clientSortDir === 'asc' ? 'desc' : 'asc';
    } else {
      _clientSortField = field;
      _clientSortDir = 'asc';
    }
    updateSortArrows('#dash-table', field, _clientSortDir);
    renderDashboard(document.getElementById('header-title').textContent.replace(' — Workbooks', ''));
  }

  function filterByStatus(status) {
    _currentStatusFilter = status;
    document.querySelectorAll('.status-filter-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    renderRecentWorkbooks();
  }

  function getItemCurrentStep(flow) {
    let lastStep = null;
    for (let i = flowSteps.length - 1; i >= 0; i--) {
      if (flow[flowSteps[i]]) { lastStep = flowSteps[i]; break; }
    }
    return lastStep;
  }

  function renderRecentWorkbooks() { try { _renderRecentWorkbooksInner(); } catch(e) { console.error('[MS renderRecentWorkbooks]', e); } }
  function _renderRecentWorkbooksInner() {
    // Gather all workbooks with their client name
    const all = [];
    for (const [client, items] of Object.entries(clientData)) {
      items.forEach(item => all.push({ ...item, client }));
    }

    // Sort
    const months = { Jan:0, Feb:1, Mar:2, Apr:3, May:4, Jun:5, Jul:6, Aug:7, Sep:8, Oct:9, Nov:10, Dec:11 };
    const parseDate = d => {
      if (!d) return new Date(0);
      const parts = d.split(' ');
      return new Date(2000 + parseInt(parts[2]), months[parts[1]], parseInt(parts[0]));
    };
    const dir = _homeSortDir === 'asc' ? 1 : -1;
    all.sort((a, b) => {
      if (_homeSortField === 'product') return dir * a.product.localeCompare(b.product);
      if (_homeSortField === 'client') return dir * a.client.localeCompare(b.client);
      if (_homeSortField === 'status') return dir * (getCurrentStepName(a.flow) || '').localeCompare(getCurrentStepName(b.flow) || '');
      return dir * (parseDate(a.dateCreated) - parseDate(b.dateCreated));
    });

    // Apply status filter
    let filtered = all;
    if (_currentStatusFilter === 'complete') {
      filtered = all.filter(item => isFlowComplete(item.flow));
    } else if (_currentStatusFilter === 'none') {
      filtered = all.filter(item => getItemCurrentStep(item.flow) === null);
    } else if (_currentStatusFilter !== 'all') {
      filtered = all.filter(item => {
        const currentStep = getItemCurrentStep(item.flow);
        return currentStep === _currentStatusFilter;
      });
    }

    const recent = filtered;
    const filterEmpty = document.getElementById('filter-empty');
    const tbody = document.getElementById('recent-tbody');

    tbody.innerHTML = recent.map(item => {
      const complete = isFlowComplete(item.flow);
      const stepName = getCurrentStepName(item.flow);
      const stepClass = complete ? 'complete' : 'in-progress';

      return `
      <tr class="${complete ? 'row-complete' : ''}" style="cursor:pointer;" onclick="location.hash='#/client/${encodeURIComponent(item.client).replace(/'/g,'%27')}/workbook/${item.id}'">
        <td class="product-name">${item.product}</td>
        <td class="col-client">${item.client}</td>
        <td class="col-date-created"><span class="date-full">${item.dateCreated}</span><span class="date-short">${shortDate(item.dateCreated)}</span></td>
        <td class="col-flow">
          <div class="flow-group">
            ${flowSteps.map((s, i) => `
              <div class="flow-step">
                <div class="flow-bar ${item.flow[s] ? 'filled' : ''}"></div>
                <span class="flow-label">${flowLabels[i]}</span>
              </div>
            `).join('')}
          </div>
        </td>
        <td class="col-mobile-status"><span class="mobile-status-badge ${stepClass}">${stepName}</span></td>
        <td><button class="action-icon-btn" onclick="event.stopPropagation(); toggleActionMenu(this)" title="Actions">⋮</button><div class="action-menu"><a onclick="event.stopPropagation(); location.hash='#/client/${encodeURIComponent(item.client)}/workbook/${item.id}'">View</a><a onclick="event.stopPropagation(); location.hash='#/client/${encodeURIComponent(item.client)}/workbook/${item.id}'">Edit</a><a onclick="event.stopPropagation(); duplicateWorkbook('${item.client.replace(/'/g, "\\'")}', '${item.id}')">Duplicate</a><a onclick="event.stopPropagation(); openDeleteModal('${item.client.replace(/'/g, "\\'")}', '${item.id}', '${item.product.replace(/'/g, "\\'")}')">Delete</a></div></td>
      </tr>
      `;
    }).join('');

    if (filterEmpty) {
      if (recent.length === 0 && _currentStatusFilter !== 'all') {
        filterEmpty.style.display = 'block';
        tbody.closest('table').style.display = 'none';
      } else {
        filterEmpty.style.display = 'none';
        tbody.closest('table').style.display = '';
      }
    }
  }  // end _renderRecentWorkbooksInner

  window.MS_SESSION = { name: '<?= addslashes($_msUser) ?>', role: '<?= $_msRole ?>', id: <?= $_msUserId ?>, username: '<?= addslashes($_msUsername) ?>' };
  window.addEventListener('hashchange', () => {
    try { router(); }
    catch(e) { console.error('[MS Router Error]', e); }
  });


  /* ── Auto-save workbook fields ─────────────────────────────────────────── */
  let saveTimer = null;

  function _v(id) { const el = document.getElementById(id); return el ? el.value || '' : ''; }

  function collectWorkbookDetail() {
    // Get existing saved data to preserve fields we can't collect from the DOM (like images)
    const key = `${currentClient}|${currentWorkbookId}`;
    const existing = workbookDetail[key] || {};

    const detail = {
      // Workbook tab
      client: _v('client-name'),
      product: _v('product-name'),
      desc: _v('product-desc'),
      dimInL: _v('dim-in-l'),
      dimInW: _v('dim-in-w'),
      dimInH: _v('dim-in-h'),
      dimCmL: _v('dim-cm-l'),
      dimCmW: _v('dim-cm-w'),
      dimCmH: _v('dim-cm-h'),
      productCategory: _v('product-category'),
      productCategory2: _v('product-category-2'),
      productSubcategory: _v('product-subcategory'),
      productSubcategory2: _v('product-subcategory-2'),
      materials: _v('materials'),
      pantone: _v('pantone-text'),
      cmyk: _v('cmyk'),
      colorNotes: _v('color-notes'),
      rfqItems: collectRfqItems(),
      qcNotes: _v('quote-qc'),
      feeSampleDesc:  _v('fee-sample-desc'),
      feeSampleRmb:   _v('fee-sample-rmb'),
      feeSampleUsd:   _v('fee-sample-usd'),
      feeToolingDesc: _v('fee-tooling-desc'),
      feeToolingRmb:  _v('fee-tooling-rmb'),
      feeToolingUsd:  _v('fee-tooling-usd'),
      feeDieDesc:     _v('fee-die-desc'),
      feeDieRmb:      _v('fee-die-rmb'),
      feeDieUsd:      _v('fee-die-usd'),
      feePlateDesc:   _v('fee-plate-desc'),
      feePlateRmb:    _v('fee-plate-rmb'),
      feePlateUsd:    _v('fee-plate-usd'),
      feeDesignDesc:  _v('fee-design-desc'),
      feeDesignUsd:   _v('fee-design-usd'),
      extraFeeRows:  _extraFeeRows.map(r => ({type:r.type, desc:r.desc, rmb:r.rmb, usd:r.usd})),
      // Images: use in-memory arrays if populated, otherwise preserve existing DB data
      productImage: _productImages.length > 0 ? _productImages[0].url : (existing.productImage || ''),
      productImages: _productImages.length > 0 ? _productImages.map(i => i.url) : (existing.productImages || []),
      productVideos: _productVideos.length > 0 ? _productVideos.slice() : (existing.productVideos || []),
      // Pricing tab
      tiers: collectTiers(),
      // Dimensions & Carton Specifications
      dimWeightKg:       _v('dim-weight-kg'),
      dimWeightLbs:      _v('dim-weight-lbs'),
      dimPackaging:      _v('dim-packaging'),
      cartonInnerLIn:   _v('carton-inner-l-in'),
      cartonInnerLCm:   _v('carton-inner-l-cm'),
      cartonInnerWIn:   _v('carton-inner-w-in'),
      cartonInnerWCm:   _v('carton-inner-w-cm'),
      cartonInnerHIn:   _v('carton-inner-h-in'),
      cartonInnerHCm:   _v('carton-inner-h-cm'),
      cartonOuterLIn:   _v('carton-outer-l-in'),
      cartonOuterLCm:   _v('carton-outer-l-cm'),
      cartonOuterWIn:   _v('carton-outer-w-in'),
      cartonOuterWCm:   _v('carton-outer-w-cm'),
      cartonOuterHIn:   _v('carton-outer-h-in'),
      cartonOuterHCm:   _v('carton-outer-h-cm'),
      // Shipping tab
      cartonUnitWeight: _v('carton-unit-weight'),
      cartonInnerWeight: _v('carton-inner-weight'),
      cartonInnerCount: _v('carton-inner-count'),
      cartonOuterWeight: _v('carton-outer-weight'),
      cartonOuterCount: _v('carton-outer-count'),
      palletTotalCartons: _v('pallet-total-cartons'),
      freightMode: _v('freight-mode'),
      freightHsCode: _v('freight-hs-code'),
      // Quote for Client tab
      quoteDate: _v('quote-date'),
      quoteValidUntil: _v('quote-valid-until'),
      quoteClQty: _v('quote-cl-qty'),
      quoteClUnitPrice: _v('quote-cl-unit-price'),
      quoteClShipping: _v('quote-cl-shipping'),
      quoteClNotes: _v('quote-cl-notes'),
      // Invoice tab
      invNumber: _v('inv-number'),
      invDate: _v('inv-date'),
      invDueDate: _v('inv-due-date'),
      invQty: _v('inv-qty'),
      invUnitPrice: _v('inv-unit-price'),
      invShipping: _v('inv-shipping'),
      invStatus: _v('inv-status'),
      invMethod: _v('inv-method'),
      invNotes: _v('inv-notes'),
      // Art tab
      artStatus: _v('art-status'),
      artDueDate: _v('art-due-date'),
      artNotes: _v('art-notes'),
      artImages: _artImages.length > 0 ? _artImages.map(i => i.url) : (existing.artImages || []),
      clientLogo: _clientLogo || existing.clientLogo || '',
      // Preserve sample statuses (managed from samples dashboard, not DOM-driven)
      sampleStatuses: existing.sampleStatuses || {},
      // Selected pricing tier
      selectedTierIdx: _selectedTierId,
      // Shipment split
      shipmentSplits: collectShipmentSplits(),
      // RFQ queue flags (managed via toggleSendToRfq, not DOM-driven — preserve from existing)
      sentToRfq: !!existing.sentToRfq,
      sentToRfqAt: existing.sentToRfqAt || null,
    };
    return detail;
  }

  function collectTiers() {
    // Collect from Workbook tab tier table (the editable one)
    return collectTiersFrom('wb-tier-body');
  }

  function autoSaveWorkbook() {
    if (!currentClient || !currentWorkbookId || _filling || !_appReady) return;
    clearTimeout(saveTimer);
    saveTimer = setTimeout(() => {
      doSaveWorkbook();
    }, 800); // Save 0.8s after last change
  }

  function doSaveWorkbook() {
    if (!currentClient || !currentWorkbookId) return;
    try {
      const detail = collectWorkbookDetail();
      const key = `${currentClient}|${currentWorkbookId}`;
      workbookDetail[key] = detail;

      // Save to DB — autosave does NOT create a revision (only nav saves do)
      const dbId = dbWorkbookMap[key] || currentWorkbookId;
      showSaveStatus('saving');
      apiCall('save_workbook_detail', { id: dbId, detail: detail, changed_by: getCurrentUser(), create_revision: false })
        .then(r => showSaveStatus(r && r.success ? 'saved' : 'error'));

      syncClientDataName(currentClient, currentWorkbookId, detail);

      saveToLocalStorage();
    } catch(e) {
      console.error('[MS doSaveWorkbook]', e);
      showSaveStatus('error');
    }
  }

  // Listen for changes on workbook form fields
  let _isDirty = false; // true once the user has made any edit this session
  const _wbView = document.getElementById('view-workbook');
  _wbView.addEventListener('input', function(e) {
    if (e.target.matches('input, textarea, select')) {
      _isDirty = true;
      autoSaveWorkbook();
      // Keep header title in sync when product name is edited
      if (e.target.id === 'product-name') {
        document.getElementById('header-title').textContent = currentClient + ' — ' + (e.target.value || 'Workbook');
      }
    }
  });
  // Also catch select/dropdown changes (some browsers fire change but not input)
  _wbView.addEventListener('change', function(e) {
    if (e.target.matches('select')) { _isDirty = true; autoSaveWorkbook(); }
  });

  // Save to DB when leaving/refreshing the page
  window.addEventListener('beforeunload', function() {
    if (!currentClient || !currentWorkbookId || !_appReady) return;
    clearTimeout(saveTimer);
    const detail = collectWorkbookDetail();
    const key = `${currentClient}|${currentWorkbookId}`;
    workbookDetail[key] = detail;
    saveToLocalStorage();
    // Use sendBeacon so the request survives page unload
    const dbId = dbWorkbookMap[key] || currentWorkbookId;
    const payload = JSON.stringify({ action: 'save_workbook_detail', id: dbId, detail: detail });
    navigator.sendBeacon('api.php', new Blob([payload], { type: 'application/json' }));
  });

  /* ── Orders module state (must be declared before init) ────────────────── */
  let orderData = {};
  let _nextOrderId = 1;
  let _currentOrderId = null;
  let _orderFilter = 'all';
  let _orderPickerSelected = new Set();
  let _orderAddPickerSelected = new Set();
  let _shipOrderPickerSelected = new Set();

  /* ── Shipments module state (must be declared before init) ─────────────── */
  let shipmentData = {};
  let _nextShipmentId = 1;
  let _currentShipmentId = null;

  // ── Remote-change polling state ────────────────────────────────────────
  let _lastOrdersJson    = '';   // raw DB string from last successful poll
  let _lastShipmentsJson = '';   // same for shipments
  let _pollActive        = false; // guard against overlapping polls
  let _wbPickerSelected = new Set();
  let _shipFilter = 'all';
  const CONTAINER_SPECS = {
    '20ft': { label: "20' Standard",  cbm: 25,  maxKg: 21700, maxPallets: 10 },
    '40ft': { label: "40' Standard",  cbm: 55,  maxKg: 26500, maxPallets: 20 },
    '40hc': { label: "40' High Cube", cbm: 65,  maxKg: 26500, maxPallets: 21 },
  };

  /* ── Init ──────────────────────────────────────────────────────────────── */
  // Footer date
  document.getElementById('gen-date').textContent = new Date().toLocaleDateString('en-US', {
    year: 'numeric', month: 'long', day: 'numeric'
  });

  // Load data: try DB first, then LocalStorage, then use hardcoded fallback
  // ── INVENTORY ───────────────────────────────────────────────────────────
  let inventoryData = []; // [{id, sku, product_name, variant_name, client_name, workbook_id, promoted_at}]
  let _invSort = { col: null, dir: 1 }; // current inventory sort state

  async function loadInventory() {
    const res = await apiCall('get_inventory');
    if (res.success) {
      inventoryData = res.data || [];
      // Inventory badge intentionally not shown
    }
  }

  // Dashboard-view state — remembered across navigations within the session
  let _invClientFilter = 'all';   // 'all' or client name
  let _invTab = 'dashboard';      // 'dashboard' | 'skus'

  function _invFilteredRows() {
    if (_invClientFilter === 'all') return inventoryData.slice();
    return inventoryData.filter(r => (r.client_name || '') === _invClientFilter);
  }

  function populateInventoryClientFilter() {
    const sel = document.getElementById('inventory-client-filter');
    if (!sel) return;
    // Unique client names present in inventory, sorted
    const clientsWithSkus = Array.from(new Set(inventoryData.map(r => r.client_name).filter(Boolean))).sort();
    const current = _invClientFilter;
    sel.innerHTML = `<option value="all">All Clients${inventoryData.length ? ' (' + inventoryData.length + ')' : ''}</option>` +
      clientsWithSkus.map(n => {
        const count = inventoryData.filter(r => r.client_name === n).length;
        return `<option value="${n.replace(/"/g,'&quot;')}"${n === current ? ' selected' : ''}>${n} (${count})</option>`;
      }).join('');
    // If the current filter is no longer present (client removed), fall back to 'all'
    if (current !== 'all' && !clientsWithSkus.includes(current)) {
      _invClientFilter = 'all';
      sel.value = 'all';
    }
  }

  function filterInventoryByClient(value) {
    _invClientFilter = value || 'all';
    // Re-render the active pane only — the other will catch up when switched to
    if (_invTab === 'dashboard') {
      renderInventoryDashboard();
    } else {
      const q = document.getElementById('inventory-search')?.value || '';
      filterInventory(q); // respects search within the client filter
    }
    // Update the count badge — always reflects the current client filter
    _updateInventoryCountBadge();
  }

  function _updateInventoryCountBadge() {
    const badge = document.getElementById('inventory-count-badge');
    if (!badge) return;
    const rows = _invFilteredRows();
    badge.textContent = rows.length === 1 ? '1 SKU' : `${rows.length.toLocaleString('en-US')} SKUs`;
  }

  function switchInventoryTab(tab, btn) {
    _invTab = tab;
    document.querySelectorAll('.inv-tab-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    const dashPane = document.getElementById('inventory-dashboard-pane');
    const skusPane = document.getElementById('inventory-skus-pane');
    if (tab === 'dashboard') {
      if (dashPane) dashPane.style.display = '';
      if (skusPane) skusPane.style.display = 'none';
      renderInventoryDashboard();
    } else {
      if (dashPane) dashPane.style.display = 'none';
      if (skusPane) skusPane.style.display = '';
      const q = document.getElementById('inventory-search')?.value || '';
      filterInventory(q);
    }
  }

  function renderInventoryView() {
    document.querySelectorAll('.nav-flat-link').forEach(a => a.classList.remove('active'));
    const invNav = document.getElementById('nav-inventory-link');
    if (invNav) invNav.classList.add('active');
    document.getElementById('header-title').textContent = 'Inventory';
    _invSort = { col: null, dir: 1 };
    const searchEl = document.getElementById('inventory-search');
    if (searchEl) searchEl.value = '';

    // Rebuild client filter dropdown from current data
    populateInventoryClientFilter();
    _updateInventoryCountBadge();

    // Default to dashboard tab on entry (per design: no raw SKU list up front)
    const dashBtn = document.getElementById('inv-tab-dashboard');
    const skusBtn = document.getElementById('inv-tab-skus');
    document.querySelectorAll('.inv-tab-btn').forEach(b => b.classList.remove('active'));
    if (_invTab === 'skus' && skusBtn) {
      skusBtn.classList.add('active');
      switchInventoryTab('skus', skusBtn);
    } else {
      if (dashBtn) dashBtn.classList.add('active');
      _invTab = 'dashboard';
      const dashPane = document.getElementById('inventory-dashboard-pane');
      const skusPane = document.getElementById('inventory-skus-pane');
      if (dashPane) dashPane.style.display = '';
      if (skusPane) skusPane.style.display = 'none';
      renderInventoryDashboard();
    }
    showView('view-inventory');
  }

  // Build the dashboard: stat cards + top clients + recent activity + sparkline
  function renderInventoryDashboard() {
    const host = document.getElementById('inventory-dashboard-content');
    if (!host) return;

    const rows = _invFilteredRows();
    const scope = _invClientFilter === 'all' ? 'all clients' : _invClientFilter;

    // Empty state
    if (!rows.length) {
      host.innerHTML = `
        <div class="section-card" style="padding:60px 24px; text-align:center; color:var(--text-muted);">
          <div style="font-size:40px; margin-bottom:14px;">📦</div>
          <div style="font-size:16px; font-weight:600; color:var(--text); margin-bottom:6px;">
            ${_invClientFilter === 'all' ? 'No SKUs promoted yet' : `No SKUs for ${_invClientFilter}`}
          </div>
          <div style="font-size:13px; max-width:360px; margin:0 auto;">
            Open a workbook, click <strong>Promote to Permanent SKU</strong> on RFQ line items, and they'll show up here.
          </div>
        </div>`;
      return;
    }

    // ── Core metrics ──
    const now = Date.now();
    const DAY = 24 * 60 * 60 * 1000;

    const promotedAtMs = r => { const t = r.promoted_at ? Date.parse(r.promoted_at) : NaN; return isNaN(t) ? 0 : t; };

    // Resolve workbook_id -> product name across all clients (used when a
    // specific client is selected so recent activity can show the source wb).
    const workbookNameById = {};
    try {
      if (typeof clientData === 'object' && clientData) {
        Object.values(clientData).forEach(list => {
          if (!Array.isArray(list)) return;
          list.forEach(wb => {
            if (wb && wb.id != null) workbookNameById[String(wb.id)] = wb.product || '';
          });
        });
      }
    } catch (e) { /* clientData may not be ready on first paint */ }

    const uniqueClients  = new Set(rows.map(r => r.client_name).filter(Boolean)).size;
    const uniqueWorkbooks = new Set(rows.map(r => r.workbook_id).filter(Boolean)).size;
    const withVariants   = rows.filter(r => r.variant_name && String(r.variant_name).trim()).length;
    const latestTs       = rows.reduce((m, r) => Math.max(m, promotedAtMs(r)), 0);
    const latestLabel    = latestTs ? _relativeTime(latestTs, now) : '—';

    // Coverage: % of SKUs that have a source workbook id attached
    const withSource = rows.filter(r => r.workbook_id).length;
    const coveragePct = rows.length ? Math.round((withSource / rows.length) * 100) : 0;

    // ── Top clients by SKU count (skip when filtered to single client) ──
    const topClientsHtml = (() => {
      if (_invClientFilter !== 'all') return ''; // no sense showing a one-bar chart
      const counts = {};
      rows.forEach(r => {
        const c = r.client_name || '—';
        counts[c] = (counts[c] || 0) + 1;
      });
      const sorted = Object.entries(counts).sort((a, b) => b[1] - a[1]).slice(0, 5);
      if (!sorted.length) return '';
      const max = sorted[0][1];
      return `
        <div class="section-card" style="padding:18px 20px;">
          <div class="inv-dash-row-title">Top clients by SKU count</div>
          ${sorted.map(([name, n]) => `
            <div class="inv-topclient-row">
              <div class="inv-topclient-name" title="${name}">${name}</div>
              <div class="inv-topclient-bar-wrap">
                <div class="inv-topclient-bar" style="width:${(n / max) * 100}%;"></div>
              </div>
              <div class="inv-topclient-count">${n}</div>
            </div>
          `).join('')}
        </div>`;
    })();

    // ── Recent activity (last 5 SKUs added, newest first) ──
    // Columnar layout: [SKU 100px][Workbook pill 1fr][Our Cost][Client Cost][Date →]
    // Workbook pill now shows in ALL views (All Clients + specific-client).
    // Our Cost / Client Cost only appear when a specific client is selected.
    const recentHtml = (() => {
      const sorted = rows.slice().sort((a, b) => promotedAtMs(b) - promotedAtMs(a)).slice(0, 5);
      const showCosts = _invClientFilter !== 'all';
      const esc = s => String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

      // "April 22nd" style date with ordinal suffix
      const ordinalSuffix = d => { const n = d % 100; if (n >= 11 && n <= 13) return 'th'; switch (d % 10) { case 1: return 'st'; case 2: return 'nd'; case 3: return 'rd'; default: return 'th'; } };
      const fmtPretty = ts => {
        if (!ts) return '—';
        const d = new Date(ts);
        if (isNaN(d.getTime())) return '—';
        return `${d.toLocaleString('en-US', { month: 'long' })} ${d.getDate()}${ordinalSuffix(d.getDate())}`;
      };
      const fmtUsd = n => '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

      // Pull "Our Cost (USD)" from the matching RFQ line on that workbook.
      const ourCostUsdFor = (row) => {
        if (!row || !row.workbook_id || !row.client_name || !row.sku) return null;
        const key = `${row.client_name}|${row.workbook_id}`;
        const det = (typeof workbookDetail === 'object' && workbookDetail) ? workbookDetail[key] : null;
        if (!det || !Array.isArray(det.rfqItems)) return null;
        for (const it of det.rfqItems) {
          if (it && String(it.sku || '').trim() === String(row.sku).trim()) {
            const rmb = parseFloat(String(it.priceRmb || '').replace(/,/g, ''));
            if (rmb > 0 && USD_TO_RMB > 0) return rmb / USD_TO_RMB;
            return null;
          }
          if (Array.isArray(it && it.variants)) {
            for (const v of it.variants) {
              if (v && String(v.sku || '').trim() === String(row.sku).trim()) {
                const rmb = parseFloat(String(v.priceRmb || '').replace(/,/g, ''));
                if (rmb > 0 && USD_TO_RMB > 0) return rmb / USD_TO_RMB;
                return null;
              }
            }
          }
        }
        return null;
      };

      const costPh = '<span style="color:var(--text-muted); opacity:.7; font-style:italic;" title="Client Cost lives on the Pricing tab — not wired up yet">—</span>';

      // Fluid CSS Grid: every column uses minmax(floor, fraction) so the
      // card ALWAYS fits its parent (no horizontal overflow), but columns
      // still align vertically across rows. minmax() lets cells shrink
      // below the fr-share if the container is narrow, and text-ellipsis
      // picks up the slack.
      //
      // All-Clients view (main page): only Product · SKU · Workbook.
      // Specific-client view: adds Our Cost · Client Cost · Date.
      const gridCols = showCosts
        ? 'minmax(110px, 1.4fr) minmax(70px, 0.7fr) minmax(110px, 1.3fr) minmax(90px, 0.9fr) minmax(80px, 0.8fr) minmax(110px, 1fr)'
        : 'minmax(160px, 2fr)  minmax(100px, 1fr)  minmax(160px, 2fr)';

      // Table-style header row: column titles live once at the top.
      const colHeaders = showCosts
        ? ['Product', 'SKU', 'Workbook', 'Our Cost', 'Client Cost', 'Date']
        : ['Product', 'SKU', 'Workbook'];
      const headerRowStyle = `display:grid; grid-template-columns:${gridCols}; column-gap:12px; align-items:center; padding:6px 0 8px; border-bottom:1px solid var(--border); margin-bottom:4px;`;
      const headerCellStyle = 'font-size:10px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;';

      return `
        <div class="section-card" style="padding:18px 20px; min-width:0;">
          <div class="inv-dash-row-title">Recent activity</div>
          <div style="${headerRowStyle}">
            ${colHeaders.map(h => `<div style="${headerCellStyle}">${h}</div>`).join('')}
          </div>
          ${sorted.map(r => {
            const wbName = r.workbook_id != null ? (workbookNameById[String(r.workbook_id)] || '') : '';
            const wbHref = (wbName && r.client_name && r.workbook_id)
              ? `#/client/${encodeURIComponent(r.client_name)}/workbook/${r.workbook_id}`
              : '';
            const wbCell = wbName
              ? (wbHref
                  ? `<span class="inv-wb-pill" onclick="location.hash='${wbHref.substring(1)}'" title="${esc(wbName)}" style="max-width:100%; min-width:0;"><span class="inv-wb-pill-text">${esc(wbName)}</span><span class="inv-wb-pill-arrow">→</span></span>`
                  : `<span style="color:var(--text); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${esc(wbName)}</span>`)
              : `<span style="color:var(--text-muted); opacity:.6; font-size:12px;">—</span>`;

            // Cost + Date cells only when a specific client is selected.
            let extraCells = '';
            if (showCosts) {
              const oc = ourCostUsdFor(r);
              const ocHtml = (oc != null)
                ? `<span style="color:var(--success, #16a34a); font-weight:600;">${fmtUsd(oc)}</span>`
                : `<span style="color:var(--text-muted); opacity:.7;">—</span>`;
              extraCells = `
                <div style="display:flex; align-items:center; min-width:0; overflow:hidden;" title="Derived from the matching RFQ line">${ocHtml}</div>
                <div style="display:flex; align-items:center; min-width:0; overflow:hidden;">${costPh}</div>
                <div style="display:flex; align-items:center; color:var(--text); font-size:12px; overflow:hidden; min-width:0; white-space:nowrap;" title="${esc(r.promoted_at || '')}">${fmtPretty(r.promoted_at)}</div>`;
            }

            return `
              <div class="inv-activity-row" style="display:grid; grid-template-columns:${gridCols}; column-gap:12px; align-items:center;">
                <div style="display:flex; align-items:center; min-width:0; overflow:hidden; color:var(--text); font-weight:600;" title="${esc(r.product_name)}">
                  <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${esc(r.product_name) || '—'}</span>
                </div>
                <div style="display:flex; align-items:center; min-width:0; overflow:hidden;">
                  <span class="inv-sku" style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${esc(r.sku) || '—'}</span>
                </div>
                <div style="display:flex; align-items:center; min-width:0; overflow:hidden;">${wbCell}</div>
                ${extraCells}
              </div>`;
          }).join('')}
        </div>`;
    })();

    // ── Sparkline: SKUs added per week over the last 12 weeks ──
    const sparkHtml = (() => {
      const weeks = 12;
      const buckets = new Array(weeks).fill(0);
      const weekStart = now - weeks * 7 * DAY;
      rows.forEach(r => {
        const t = promotedAtMs(r);
        if (!t || t < weekStart) return;
        const idx = Math.min(weeks - 1, Math.floor((t - weekStart) / (7 * DAY)));
        buckets[idx]++;
      });
      const max = Math.max(1, ...buckets);
      const startLabel = new Date(weekStart).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
      const endLabel   = new Date(now).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
      return `
        <div class="section-card" style="padding:18px 20px;">
          <div class="inv-dash-row-title">SKUs added per week · last 12 weeks</div>
          <div class="inv-sparkline">
            ${buckets.map((n, i) => {
              const h = (n / max) * 100;
              const weekOf = new Date(weekStart + i * 7 * DAY).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
              return `<div class="inv-sparkline-bar" style="height:${Math.max(2, h)}%;" title="Week of ${weekOf}: ${n} SKU${n !== 1 ? 's' : ''}"></div>`;
            }).join('')}
          </div>
          <div class="inv-sparkline-labels">
            <span>${startLabel}</span>
            <span>${endLabel}</span>
          </div>
        </div>`;
    })();

    // ── Assemble ──
    // Scope note at top when filtered to a client
    const scopeNote = _invClientFilter !== 'all'
      ? `<div style="font-size:12px; color:var(--text-muted); margin-bottom:12px;">Showing <strong style="color:var(--text);">${_invClientFilter}</strong> only · <a href="#" onclick="event.preventDefault(); document.getElementById('inventory-client-filter').value='all'; filterInventoryByClient('all');" style="color:var(--accent); text-decoration:none;">Clear filter</a></div>`
      : '';

    host.innerHTML = `
      ${scopeNote}
      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:12px; margin-bottom:16px;">
        <div class="inv-stat-card">
          <div class="inv-stat-card-value" style="color:var(--accent);">${rows.length.toLocaleString('en-US')}</div>
          <div class="inv-stat-card-label">Total SKUs</div>
        </div>
        <div class="inv-stat-card">
          <div class="inv-stat-card-value" style="color:#a855f7;">${uniqueClients}</div>
          <div class="inv-stat-card-label">${_invClientFilter === 'all' ? 'Clients' : 'Client'}</div>
        </div>
        <div class="inv-stat-card">
          <div class="inv-stat-card-value" style="color:#6b93ff;">${uniqueWorkbooks}</div>
          <div class="inv-stat-card-label">Source Workbooks</div>
        </div>
        <div class="inv-stat-card">
          <div class="inv-stat-card-value" style="color:#f59e0b;">${withVariants}</div>
          <div class="inv-stat-card-label">With Variants</div>
        </div>
        <div class="inv-stat-card">
          <div class="inv-stat-card-value" style="color:var(--text); font-size:16px;">${latestLabel}</div>
          <div class="inv-stat-card-label">Latest Addition</div>
        </div>
        <div class="inv-stat-card">
          <div class="inv-stat-card-value" style="color:${coveragePct >= 80 ? '#10b981' : coveragePct >= 50 ? '#f59e0b' : '#ef4444'};">${coveragePct}%</div>
          <div class="inv-stat-card-label">Source Coverage</div>
          <div class="inv-stat-card-sub">${withSource}/${rows.length} linked</div>
        </div>
      </div>

      ${topClientsHtml || recentHtml
        ? `<div style="display:grid; grid-template-columns:${topClientsHtml ? 'repeat(auto-fit, minmax(320px, 1fr))' : '1fr'}; gap:12px; margin-bottom:12px;">
            ${topClientsHtml}
            ${recentHtml}
          </div>`
        : ''}

      ${sparkHtml}
    `;
  }

  // ══════════════════════════════════════════════════════════════════════
  // COMMISSIONS DASHBOARD
  // Owner-facing reporting view. Pulls all rows once via get_commissions and
  // filters in memory (employee × role). Mirrors Inventory dashboard's card
  // rhythm so the two views feel consistent.
  // ══════════════════════════════════════════════════════════════════════
  let commissionsData = [];          // [{id, role, employee, client_total_usd, commission_amount, status, ...}]
  let _commEmployeeFilter = 'all';   // 'all' | 'Parker Low' | 'Jackson Hollberg'
  let _commRoleFilter     = 'all';   // 'all' | 'account_manager' | 'salesperson'

  async function loadCommissions() {
    try {
      // Reconcile first — picks up any (client, workbook) pairs that became
      // commissionable after the original promote/order (e.g. AM/SP got
      // assigned later, or the workbook's rfqItems totals changed). The
      // server is idempotent and refreshes pending rows in place; paid rows
      // stay frozen. Failures here shouldn't block the read of existing rows.
      try { await apiCall('recompute_commissions'); }
      catch (e) { console.warn('[commissions] recompute_commissions failed', e); }

      const res = await apiCall('get_commissions');
      if (res && res.success) commissionsData = res.data || [];
    } catch (e) { console.error('[commissions] loadCommissions failed', e); }
  }

  function _commFilteredRows() {
    return commissionsData.filter(r => {
      if (_commEmployeeFilter !== 'all' && r.employee !== _commEmployeeFilter) return false;
      if (_commRoleFilter     !== 'all' && r.role     !== _commRoleFilter)     return false;
      return true;
    });
  }

  function filterCommissionsByEmployee(v) { _commEmployeeFilter = v; renderCommissionsDashboard(); }
  function filterCommissionsByRole(v)     { _commRoleFilter     = v; renderCommissionsDashboard(); }

  // Which employees have their per-client / per-workbook breakdown expanded
  // on the scoreboard. Survives re-renders triggered by filter changes.
  const _commExpandedEmployees = new Set();
  function toggleEmployeeExpand(name) {
    if (_commExpandedEmployees.has(name)) _commExpandedEmployees.delete(name);
    else _commExpandedEmployees.add(name);
    renderCommissionsDashboard();
  }

  // Toggle a single commission row between paid and pending. Hits
  // set_commission_status, then mutates the matching entry in
  // commissionsData so the UI reflects without a full reload — and so
  // the year/month buckets in the Earnings-over-time card recompute on
  // the next render. event.stopPropagation() is the caller's job; this
  // function only handles the network + state update.
  async function setCommissionPaid(id, paid) {
    const status = paid ? 'paid' : 'pending';
    const r = await apiCall('set_commission_status', { id, status });
    if (!r.success) { alert(r.error || 'Failed to update commission status.'); return; }
    const row = commissionsData.find(x => x.id === id);
    if (row) {
      row.status  = status;
      row.paid_at = paid ? new Date().toISOString() : null;
    }
    renderCommissionsDashboard();
  }

  // Selected year for the "Earnings over time" card. null = pick the most
  // recent year with data (clamped at render time once we know what years
  // actually have rows under the current filters).
  let _commTimeYear = null;
  function setCommTimeYear(y) {
    _commTimeYear = parseInt(y, 10) || null;
    renderCommissionsDashboard();
  }

  // Optional focus on a single employee in the Earnings-over-time card —
  // clicking a legend chip filters the chart and dims the per-individual
  // rows. Click again (or "Clear focus") to restore the team view.
  let _commTimeFocusEmp = null;
  function setCommTimeFocusEmp(name) {
    if (!name || _commTimeFocusEmp === name) {
      _commTimeFocusEmp = null;
    } else {
      _commTimeFocusEmp = name;
    }
    renderCommissionsDashboard();
  }

  // Deterministic per-employee swatch using the same HSL hash trick as
  // _clientChipStyle, but tuned for solid bar fills (saturated mid-tone)
  // rather than translucent chips. Same name → same color across renders.
  //
  // EMPLOYEE_HUE_OVERRIDES pins specific names to fixed hues when the hash
  // happens to land them too close to another teammate's color. Karen
  // hashed to ~197 (cyan-blue) which read identical to Jackson on the
  // stacked bars, so we force her to a clear green here. Add new entries
  // when a clash shows up — leaving an employee out of the map falls back
  // to the deterministic hash.
  const EMPLOYEE_HUE_OVERRIDES = {
    'Karen': 140, // green
  };
  function _employeeSwatch(name) {
    const safe = (name || '—').toString();
    let hue;
    if (Object.prototype.hasOwnProperty.call(EMPLOYEE_HUE_OVERRIDES, safe)) {
      hue = EMPLOYEE_HUE_OVERRIDES[safe];
    } else {
      let hash = 0;
      for (let i = 0; i < safe.length; i++) {
        hash = ((hash << 5) - hash) + safe.charCodeAt(i);
        hash |= 0;
      }
      hue = Math.abs(hash) % 360;
    }
    return {
      fill: `hsl(${hue}, 62%, 52%)`,
      tint: `hsl(${hue}, 62%, 92%)`,
      ink:  `hsl(${hue}, 55%, 28%)`,
    };
  }

  async function renderCommissionsView() {
    document.querySelectorAll('.nav-flat-link').forEach(a => a.classList.remove('active'));
    const navLink = document.getElementById('nav-commissions-link');
    if (navLink) navLink.classList.add('active');
    document.getElementById('header-title').textContent = 'Commissions';
    await loadCommissions();
    renderCommissionsDashboard();
    showView('view-commissions');
  }

  function renderCommissionsDashboard() {
    const host = document.getElementById('commissions-dashboard-content');
    if (!host) return;

    const rows  = _commFilteredRows();
    const badge = document.getElementById('commissions-count-badge');
    if (badge) badge.textContent = `${rows.length} row${rows.length !== 1 ? 's' : ''}`;

    // Empty state — guides the user toward setup
    if (!rows.length) {
      host.innerHTML = `
        <div class="section-card" style="padding:60px 24px; text-align:center; color:var(--text-muted);">
          <div style="font-size:40px; margin-bottom:14px;">💼</div>
          <div style="font-size:16px; font-weight:600; color:var(--text); margin-bottom:6px;">
            ${commissionsData.length === 0 ? 'No commissions yet' : 'No rows match the current filter'}
          </div>
          <div style="font-size:13px; max-width:440px; margin:0 auto;">
            ${commissionsData.length === 0
              ? 'Assign an <strong>Account Manager</strong> or <strong>Salesperson</strong> to a client, then promote a workbook with priced RFQ items. Commissions will land here automatically (20% of client total).'
              : 'Try clearing the Employee or Role filter to see all rows.'}
          </div>
        </div>`;
      return;
    }

    // ── Formatters ──
    const fmtUsd  = n => '$' + (parseFloat(n) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const fmtUsd0 = n => '$' + Math.round(parseFloat(n) || 0).toLocaleString('en-US');
    const fmtPretty = iso => {
      if (!iso) return '—';
      const d = new Date(iso); if (isNaN(d.getTime())) return '—';
      const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
      const day = d.getDate();
      const suf = (n => (n>3 && n<21) ? 'th' : ({1:'st',2:'nd',3:'rd'}[n%10]||'th'))(day);
      return `${months[d.getMonth()]} ${day}${suf}`;
    };
    const esc = s => String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

    // ── KPI strip ──
    const totalEarned = rows.reduce((s, r) => s + (parseFloat(r.commission_amount) || 0), 0);
    const pending     = rows.filter(r => r.status === 'pending').reduce((s, r) => s + (parseFloat(r.commission_amount) || 0), 0);
    const paid        = rows.filter(r => r.status === 'paid').reduce((s, r) => s + (parseFloat(r.commission_amount) || 0), 0);
    const wbCount     = new Set(rows.map(r => r.workbook_id)).size;
    const avgPerWb    = wbCount > 0 ? totalEarned / wbCount : 0;
    const anyEstimate = rows.some(r => +r.is_estimate === 1);

    const statCard = (label, value, subtext, accent) => `
      <div class="section-card" style="padding:16px 18px; min-width:0;">
        <div style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.06em;">${label}</div>
        <div style="font-size:24px; font-weight:700; color:${accent || 'var(--text)'}; margin-top:6px; line-height:1.2;">${value}</div>
        ${subtext ? `<div style="font-size:11px; color:var(--text-muted); margin-top:4px;">${subtext}</div>` : ''}
      </div>`;

    const kpiHtml = `
      <div style="display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:14px; margin-bottom:18px;">
        ${statCard('Total Earned', fmtUsd(totalEarned), `${rows.length} commission row${rows.length !== 1 ? 's' : ''}`)}
        ${statCard('Pending Payout', fmtUsd(pending), 'Not yet marked paid', 'var(--accent)')}
        ${statCard('Paid Out', fmtUsd(paid), 'Marked paid', 'var(--success, #16a34a)')}
        ${statCard('Avg / Workbook', fmtUsd(avgPerWb), `${wbCount} unique workbook${wbCount !== 1 ? 's' : ''}`)}
      </div>
      ${anyEstimate ? `
        <div style="background:rgba(232,117,26,0.08); border:1px solid rgba(232,117,26,0.25); border-radius:10px; padding:10px 14px; margin-bottom:18px; font-size:12px; color:var(--text-muted);">
          ⓘ Some commissions are <strong>estimates</strong>: they use Our Cost (RFQ-derived USD) until "Client Cost" is wired up on the Pricing tab.
        </div>` : ''}
    `;

    // ── Per-employee scoreboard ──
    const empMap = {};
    rows.forEach(r => {
      const e = r.employee || '—';
      if (!empMap[e]) empMap[e] = { am: 0, sp: 0, op: 0, total: 0, paid: 0, pending: 0, count: 0 };
      const amt = parseFloat(r.commission_amount) || 0;
      empMap[e].total += amt;
      empMap[e].count += 1;
      // Split each row into paid vs pending so the scoreboard card can
      // show "X paid / Y pending" without re-walking the rows in render.
      if (r.status === 'paid') empMap[e].paid    += amt;
      else                     empMap[e].pending += amt;
      if      (r.role === 'account_manager') empMap[e].am += amt;
      else if (r.role === 'salesperson')     empMap[e].sp += amt;
      else if (r.role === 'operations')      empMap[e].op += amt;
    });
    const employees = Object.entries(empMap).sort((a, b) => b[1].total - a[1].total);

    // Role pill (used by the recent-activity table AND the expanded
    // breakdown). Hoisted above empHtml so the breakdown can call it.
    // Each role gets a distinct color so the eye can sort the rows fast:
    //   AM       — blue
    //   Sales    — orange (the brand accent)
    //   Ops      — green-teal (visually separated from the other two so
    //              Karen's slice on a stacked bar pops without colliding)
    const rolePill = role => {
      const styles = {
        account_manager: { label: 'AM',    bg: 'rgba(107,147,255,0.12)', fg: '#6b93ff',           bd: 'rgba(107,147,255,0.35)' },
        salesperson:     { label: 'Sales', bg: 'rgba(232,117,26,0.12)',  fg: 'var(--accent)',     bd: 'rgba(232,117,26,0.35)'  },
        operations:      { label: 'Ops',   bg: 'rgba(22,163,74,0.12)',   fg: '#16a34a',           bd: 'rgba(22,163,74,0.35)'   },
      };
      const s = styles[role] || { label: role || '—', bg: 'var(--surface2)', fg: 'var(--text-muted)', bd: 'var(--border)' };
      return `<span style="background:${s.bg}; color:${s.fg}; border:1px solid ${s.bd}; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600; white-space:nowrap;">${s.label}</span>`;
    };

    // Per-employee breakdown (rendered when the card is expanded). Groups
    // this employee's commission rows by client, then lists each workbook
    // with its rate, the client total it ran against, and the resulting
    // commission. Sorts clients by total descending so the biggest earner
    // floats to the top.
    const fmtRate = frac => {
      const pct = (parseFloat(frac) || 0) * 100;
      return (pct % 1 === 0 ? pct.toFixed(0) : pct.toFixed(2)) + '%';
    };
    const buildEmployeeBreakdown = (empName) => {
      const myRows = rows.filter(r => r.employee === empName);
      if (!myRows.length) {
        return `<div style="margin-top:14px; padding-top:14px; border-top:1px dashed var(--border); font-size:12px; color:var(--text-muted);">No commissions match the current filters.</div>`;
      }
      const byClient = {};
      myRows.forEach(r => {
        const c = r.client_name || '—';
        if (!byClient[c]) byClient[c] = [];
        byClient[c].push(r);
      });
      const clientNames = Object.keys(byClient).sort((a, b) => {
        const sa = byClient[a].reduce((s, r) => s + (parseFloat(r.commission_amount) || 0), 0);
        const sb = byClient[b].reduce((s, r) => s + (parseFloat(r.commission_amount) || 0), 0);
        return sb - sa;
      });
      const clientBlocks = clientNames.map(c => {
        const cRows = byClient[c];
        const cSubtotal = cRows.reduce((s, r) => s + (parseFloat(r.commission_amount) || 0), 0);
        const clientHash = `/client/${encodeURIComponent(c)}`;
        const clientChipClick = `event.stopPropagation(); location.hash='${clientHash}'`;
        const wbRows = cRows.map(r => {
          const wbHash = `/client/${encodeURIComponent(r.client_name)}/workbook/${r.workbook_id}`;
          const wbClick = `event.stopPropagation(); location.hash='${wbHash}'`;
          const product = esc(r.product_name) || '—';
          const isEst = +r.is_estimate === 1;
          const isPaid = r.status === 'paid';
          // Paid state styling: green-tinted background, green left-edge
          // accent, and a "✓ Paid" badge replacing the "Mark paid" button.
          // Pending rows show the Mark Paid action so the operator can flip
          // a row right where they're already looking. Clicking the badge
          // (when paid) reverts to pending — handy for fixing miss-clicks.
          const rowBg     = isPaid ? 'rgba(22,163,74,0.12)'     : 'var(--surface)';
          const rowBorder = isPaid ? '1px solid rgba(22,163,74,0.35)' : '1px solid transparent';
          const paidTitle = isPaid && r.paid_at
            ? `Paid ${new Date(r.paid_at).toLocaleDateString()} — click to mark pending`
            : 'Click to mark this commission as paid';
          // Compact 5-column row: workbook | role+rate | client total | commission $ | paid toggle
          return `
            <div style="display:grid; grid-template-columns: minmax(0, 1fr) auto auto auto auto; column-gap:10px; align-items:center; padding:6px 8px; border-radius:6px; background:${rowBg}; border:${rowBorder}; border-left-width:${isPaid ? '3px' : '1px'};">
              <span class="inv-wb-pill" onclick="${wbClick}" title="${product}" style="max-width:100%; min-width:0;">
                <span class="inv-wb-pill-text">${product}</span><span class="inv-wb-pill-arrow">→</span>
              </span>
              <span style="display:inline-flex; align-items:center; gap:6px;">
                ${rolePill(r.role)}
                <span style="font-size:11px; color:var(--text-muted); font-weight:600; font-variant-numeric:tabular-nums;">${fmtRate(r.commission_rate)}</span>
              </span>
              <span style="font-size:11px; color:var(--text-muted); font-variant-numeric:tabular-nums; white-space:nowrap;" title="Client total this commission was rated against">
                ${fmtUsd0(r.client_total_usd)}
              </span>
              <span style="font-size:13px; color:var(--success, #16a34a); font-weight:600; font-variant-numeric:tabular-nums; text-align:right; min-width:74px; white-space:nowrap;">
                ${fmtUsd(r.commission_amount)}${isEst ? '<span style="margin-left:4px; font-size:10px; color:var(--text-muted); font-style:italic;" title="Estimate — Client Cost not yet wired">est</span>' : ''}
              </span>
              <button type="button"
                onclick="event.stopPropagation(); setCommissionPaid(${r.id}, ${isPaid ? 'false' : 'true'})"
                title="${paidTitle}"
                style="display:inline-flex; align-items:center; gap:4px; padding:4px 10px; border-radius:14px; font-size:11px; font-weight:700; cursor:pointer; white-space:nowrap; font-family:inherit; ${isPaid
                  ? 'background:#16a34a; color:#fff; border:1px solid #16a34a;'
                  : 'background:transparent; color:var(--text-muted); border:1px solid var(--border);'}">
                ${isPaid
                  ? '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>Paid'
                  : 'Mark paid'}
              </button>
            </div>`;
        }).join('');
        return `
          <div style="margin-bottom:14px;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px; gap:10px;">
              <span class="inv-client-chip" onclick="${clientChipClick}" style="${_clientChipStyle(c)} cursor:pointer; max-width:60%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${esc(c)} — open client">${esc(c)}</span>
              <span style="font-size:11px; color:var(--text-muted); font-weight:600; white-space:nowrap;">
                ${cRows.length} workbook${cRows.length !== 1 ? 's' : ''} · subtotal
                <span style="color:var(--text); font-weight:700;">${fmtUsd(cSubtotal)}</span>
              </span>
            </div>
            <div style="display:flex; flex-direction:column; gap:5px;">
              ${wbRows}
            </div>
          </div>
        `;
      }).join('');
      return `
        <div style="margin-top:14px; padding-top:14px; border-top:1px dashed var(--border);">
          ${clientBlocks}
        </div>
      `;
    };

    const empHtml = employees.length ? `
      <div class="section-card" style="padding:18px 20px; margin-bottom:18px;">
        <div class="inv-dash-row-title">Per-employee scoreboard</div>
        <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">Click a card to see which clients and workbooks are paying out.</div>
        <div style="display:grid; grid-template-columns:repeat(${Math.min(employees.length, 2)}, minmax(0, 1fr)); gap:14px; margin-top:10px;">
          ${employees.map(([name, t]) => {
            const expanded   = _commExpandedEmployees.has(name);
            const toggleAttr = `onclick='toggleEmployeeExpand(${JSON.stringify(name)})'`;
            // Chevron rotates 90° when the card is open. Pure CSS transform.
            const chevron = `
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                   stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                   style="color:var(--text-muted); transition:transform 0.2s; transform:rotate(${expanded ? 90 : 0}deg);">
                <polyline points="9 18 15 12 9 6"/>
              </svg>`;
            // Paid % drives the thin progress bar under the header. When
            // total is 0 the bar stays empty (no divide-by-zero). When
            // every row on this card is paid the bar is fully green —
            // matches the Mark-Paid pill color so the visual story is
            // consistent: green = settled, gray = still owed.
            const paidPct = t.total > 0 ? Math.min(100, (t.paid / t.total) * 100) : 0;
            const allPaid = t.total > 0 && t.pending <= 0.005; // float fuzz
            return `
              <div style="border:1px solid var(--border); border-radius:12px; padding:16px; background:var(--surface2); min-width:0; ${expanded ? 'box-shadow: 0 0 0 1px var(--accent-glow) inset;' : ''}">
                <div ${toggleAttr} style="display:flex; align-items:center; gap:10px; margin-bottom:10px; cursor:pointer; user-select:none;" title="Click to ${expanded ? 'collapse' : 'expand'} breakdown">
                  <div style="width:36px; height:36px; border-radius:50%; background:var(--accent-glow); color:var(--accent); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px;">
                    ${esc((name || '?').split(/\s+/).map(w => w[0]).join('').slice(0,2).toUpperCase())}
                  </div>
                  <div style="min-width:0;">
                    <div style="font-weight:600; color:var(--text); font-size:14px;">${esc(name)}</div>
                    <div style="font-size:11px; color:var(--text-muted);">${t.count} row${t.count !== 1 ? 's' : ''}</div>
                  </div>
                  <div style="margin-left:auto; font-size:20px; font-weight:700; color:var(--text);">${fmtUsd(t.total)}</div>
                  ${chevron}
                </div>

                <!-- Paid vs pending strip — one line of labels + a thin
                     progress bar. Green portion = paid, gray = pending.
                     Lets the operator glance at a card and see how much
                     of this person's earnings have actually been paid out
                     without having to expand the breakdown. -->
                <div style="margin-bottom:12px;">
                  <div style="display:flex; justify-content:space-between; align-items:center; font-size:11px; margin-bottom:5px; gap:8px;">
                    <span style="color:#16a34a; font-weight:600; display:inline-flex; align-items:center; gap:4px;">
                      <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                      Paid ${fmtUsd(t.paid)}
                    </span>
                    <span style="color:var(--text-muted); font-weight:600;">Pending ${fmtUsd(t.pending)}</span>
                  </div>
                  <div style="height:4px; border-radius:2px; background:var(--border); overflow:hidden;" title="${paidPct.toFixed(0)}% paid">
                    <div style="height:100%; width:${paidPct}%; background:#16a34a; transition:width 0.25s;"></div>
                  </div>
                  ${allPaid ? '<div style="font-size:10px; color:#16a34a; font-weight:700; margin-top:5px; text-transform:uppercase; letter-spacing:0.05em;">All settled</div>' : ''}
                </div>

                <div style="display:grid; grid-template-columns:repeat(${t.op > 0 ? 3 : 2}, minmax(0, 1fr)); gap:10px;">
                  <div style="padding:8px 10px; border-radius:8px; background:rgba(107,147,255,0.1); border:1px solid rgba(107,147,255,0.2);">
                    <div style="font-size:10px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.05em;">Account Mgr</div>
                    <div style="font-size:15px; font-weight:600; color:var(--text); margin-top:2px;">${fmtUsd(t.am)}</div>
                  </div>
                  <div style="padding:8px 10px; border-radius:8px; background:rgba(232,117,26,0.08); border:1px solid rgba(232,117,26,0.2);">
                    <div style="font-size:10px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.05em;">Salesperson</div>
                    <div style="font-size:15px; font-weight:600; color:var(--text); margin-top:2px;">${fmtUsd(t.sp)}</div>
                  </div>
                  ${t.op > 0 ? `
                    <div style="padding:8px 10px; border-radius:8px; background:rgba(22,163,74,0.1); border:1px solid rgba(22,163,74,0.25);">
                      <div style="font-size:10px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.05em;">Operations</div>
                      <div style="font-size:15px; font-weight:600; color:var(--text); margin-top:2px;">${fmtUsd(t.op)}</div>
                    </div>
                  ` : ''}
                </div>
                ${expanded ? buildEmployeeBreakdown(name) : ''}
              </div>
            `;
          }).join('')}
        </div>
      </div>
    ` : '';

    // ── Earnings over time ──────────────────────────────────────────────
    // Bucket commissions by month, year, AND employee so the chart can
    // show per-individual stacks and the "Per individual" section below
    // can render a mini 12-month chart per person. Status (paid vs
    // pending) is also tracked so the year-summary strip and tooltips
    // can still surface the payout split.
    const monthBuckets    = {}; // 'YYYY-MM' -> { total, pending, paid }
    const yearBuckets     = {}; // YYYY      -> { total, pending, paid }
    const monthEmpBuckets = {}; // 'YYYY-MM' -> { [employee]: amount }
    const yearEmpBuckets  = {}; // YYYY      -> { [employee]: { total, paid, pending } }
    const allEmployees    = new Set();
    rows.forEach(r => {
      const ts = r.created_at;
      if (!ts) return;
      const d = new Date(ts);
      if (isNaN(d.getTime())) return;
      const yyyy = d.getFullYear();
      const mm   = String(d.getMonth() + 1).padStart(2, '0');
      const key  = `${yyyy}-${mm}`;
      const amt  = parseFloat(r.commission_amount) || 0;
      const emp  = (r.employee || '—').trim() || '—';
      allEmployees.add(emp);
      if (!monthBuckets[key])      monthBuckets[key]      = { total: 0, pending: 0, paid: 0 };
      if (!yearBuckets[yyyy])      yearBuckets[yyyy]      = { total: 0, pending: 0, paid: 0 };
      if (!monthEmpBuckets[key])   monthEmpBuckets[key]   = {};
      if (!yearEmpBuckets[yyyy])   yearEmpBuckets[yyyy]   = {};
      if (!monthEmpBuckets[key][emp])  monthEmpBuckets[key][emp]  = 0;
      if (!yearEmpBuckets[yyyy][emp])  yearEmpBuckets[yyyy][emp]  = { total: 0, paid: 0, pending: 0 };
      monthBuckets[key].total += amt;
      yearBuckets[yyyy].total += amt;
      monthEmpBuckets[key][emp] += amt;
      yearEmpBuckets[yyyy][emp].total += amt;
      if (r.status === 'paid') {
        monthBuckets[key].paid += amt;
        yearBuckets[yyyy].paid += amt;
        yearEmpBuckets[yyyy][emp].paid += amt;
      } else {
        monthBuckets[key].pending += amt;
        yearBuckets[yyyy].pending += amt;
        yearEmpBuckets[yyyy][emp].pending += amt;
      }
    });

    const yearsAvailable = Object.keys(yearBuckets).map(Number).sort((a, b) => b - a);
    // Clamp the selected year to one that actually has data under the
    // current filters. If nothing matches, fall back to current calendar
    // year so the chart still renders an empty 12-month skeleton.
    let selectedYear = _commTimeYear;
    if (!yearsAvailable.includes(selectedYear)) {
      selectedYear = yearsAvailable[0] || new Date().getFullYear();
    }

    const monthAbbr = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const monthValues = monthAbbr.map((_, idx) => {
      const mm = String(idx + 1).padStart(2, '0');
      return monthBuckets[`${selectedYear}-${mm}`] || { total: 0, pending: 0, paid: 0 };
    });
    const maxMonthTotal = Math.max(0.01, ...monthValues.map(m => m.total));
    const yearTot   = yearBuckets[selectedYear]?.total   || 0;
    const yearPend  = yearBuckets[selectedYear]?.pending || 0;
    const yearPaid  = yearBuckets[selectedYear]?.paid    || 0;
    const monthsWith = monthValues.filter(m => m.total > 0).length;
    const avgPerMonth = monthsWith > 0 ? yearTot / monthsWith : 0;

    // Order employees by their selected-year contribution (largest first)
    // so colors and rows below tell a consistent story. Anyone with zero
    // in the selected year still appears in the legend if they had rows
    // in *any* year — that way a low-volume month doesn't make a person
    // visually disappear from the team view.
    const empOrderForYear = Array.from(allEmployees).sort((a, b) => {
      const ay = (yearEmpBuckets[selectedYear]?.[a]?.total) || 0;
      const by = (yearEmpBuckets[selectedYear]?.[b]?.total) || 0;
      if (by !== ay) return by - ay;
      return a.localeCompare(b);
    });

    // Year selector — uses the same custom-arrow pattern as the rest of
    // the app's dropdowns. Always include `selectedYear` in the option
    // list even if it has no rows, so the dropdown reflects current state.
    const yearOptionSet = Array.from(new Set([...yearsAvailable, selectedYear])).sort((a, b) => b - a);
    const yearSelector = `
      <span class="ship-select-wrap" style="min-width:120px;">
        <select onchange="setCommTimeYear(this.value)"
          style="width:100%; height:32px; padding:0 30px 0 12px; border:1px solid var(--border); border-radius:8px; background:var(--surface2); color:var(--text); font-size:13px; font-family:inherit; outline:none; cursor:pointer; appearance:none; -webkit-appearance:none; font-weight:600;">
          ${yearOptionSet.map(y => `<option value="${y}"${y === selectedYear ? ' selected' : ''}>${y}</option>`).join('')}
        </select>
      </span>
    `;

    // Per-employee color legend, ordered like the stack (top -> bottom of
    // each bar matches left -> right of legend). Click a chip to focus
    // just that person — drives `_commTimeFocusEmp`.
    const empLegendHtml = empOrderForYear.length ? `
      <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:10px;">
        ${empOrderForYear.map(emp => {
          const isFocused = _commTimeFocusEmp === emp;
          const isDimmed  = _commTimeFocusEmp && !isFocused;
          const sw = _employeeSwatch(emp);
          return `
            <button onclick="setCommTimeFocusEmp(${JSON.stringify(emp)})"
              title="${esc(emp)} — click to focus${isFocused ? ' (click again to clear)' : ''}"
              style="cursor:pointer; display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:999px; font-family:inherit; font-size:12px; font-weight:600;
                     background:${isFocused ? sw.fill : 'var(--surface2)'};
                     color:${isFocused ? '#fff' : 'var(--text)'};
                     border:1px solid ${isFocused ? sw.fill : 'var(--border)'};
                     opacity:${isDimmed ? 0.45 : 1};
                     transition:opacity 0.1s, transform 0.1s;"
              onmouseenter="this.style.transform='translateY(-1px)'"
              onmouseleave="this.style.transform=''">
              <span style="width:10px; height:10px; border-radius:3px; background:${sw.fill};"></span>
              <span style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:160px;">${esc(emp)}</span>
            </button>
          `;
        }).join('')}
        ${_commTimeFocusEmp ? `
          <button onclick="setCommTimeFocusEmp(null)"
            style="cursor:pointer; padding:4px 10px; border-radius:999px; font-family:inherit; font-size:12px; font-weight:600;
                   background:transparent; color:var(--text-muted); border:1px dashed var(--border);">
            Clear focus
          </button>` : ''}
      </div>
    ` : '';

    // The main chart. Each column is a flex-column: $ label on top, bar
    // at the bottom (anchored via flex-end), month abbr below. Each bar
    // is now stacked by employee — segments ordered to match the legend
    // (top of stack = first in legend) so the eye can follow names left
    // to right, top to bottom. min-height keeps tiny months visible.
    const chartHtml = `
      <div style="display:grid; grid-template-columns:repeat(12, 1fr); gap:6px; height:170px; margin-top:12px;">
        ${monthValues.map((m, idx) => {
          const mmKey = `${selectedYear}-${String(idx + 1).padStart(2, '0')}`;
          const empMap = monthEmpBuckets[mmKey] || {};
          // When a person is focused, only their slice contributes to
          // the visible bar height — everyone else's segments collapse.
          const visibleTotal = _commTimeFocusEmp
            ? (empMap[_commTimeFocusEmp] || 0)
            : m.total;
          const heightPct = (visibleTotal / maxMonthTotal) * 100;

          // Build per-employee segments in legend order. flex-direction
          // column-reverse stacks them upward, so reverse the legend
          // here so the first legend entry sits at the *top* of the
          // stack (most-prominent person on top).
          const segs = empOrderForYear
            .filter(emp => !_commTimeFocusEmp || emp === _commTimeFocusEmp)
            .map(emp => {
              const v = empMap[emp] || 0;
              if (v <= 0) return '';
              const pctOfBar = visibleTotal > 0 ? (v / visibleTotal) * 100 : 0;
              const sw = _employeeSwatch(emp);
              return `<div style="height:${pctOfBar}%; background:${sw.fill};" title="${esc(emp)}: ${esc(fmtUsd(v))}"></div>`;
            }).reverse().join('');

          // Build a textual tooltip listing every employee with money in
          // this month, plus the paid/pending split.
          const empLines = Object.entries(empMap)
            .filter(([, v]) => v > 0)
            .sort((a, b) => b[1] - a[1])
            .map(([emp, v]) => `${emp}: ${fmtUsd(v)}`).join(' · ');
          const tooltip = `${monthAbbr[idx]} ${selectedYear}: ${fmtUsd(m.total)}` +
                          (empLines ? ` — ${empLines}` : '') +
                          (m.paid > 0    ? ` · paid ${fmtUsd(m.paid)}`       : '') +
                          (m.pending > 0 ? ` · pending ${fmtUsd(m.pending)}` : '');
          const labelTotal = _commTimeFocusEmp ? visibleTotal : m.total;
          return `
            <div style="display:flex; flex-direction:column; align-items:center; min-width:0;">
              <div style="font-size:9px; color:var(--text-muted); font-variant-numeric:tabular-nums; height:14px; line-height:14px; white-space:nowrap; overflow:hidden;">
                ${labelTotal > 0 ? fmtUsd0(labelTotal) : ''}
              </div>
              <div style="flex:1; width:100%; display:flex; align-items:flex-end; justify-content:center; padding:2px 0;">
                <div style="width:80%; max-width:32px; height:${heightPct}%; ${visibleTotal > 0 ? 'min-height:3px;' : ''} display:flex; flex-direction:column-reverse; border-radius:5px 5px 0 0; overflow:hidden; background:var(--surface);"
                     title="${esc(tooltip)}">
                  ${segs}
                </div>
              </div>
              <div style="font-size:10px; color:var(--text-muted); margin-top:4px; font-weight:600;">${monthAbbr[idx]}</div>
            </div>
          `;
        }).join('')}
      </div>
    `;

    // Per-individual section: one row per employee with a 12-month mini
    // bar chart (their own scale), year total, and paid/pending split.
    // Each person uses the *same* color as in the main stack so the eye
    // can track them between the two views without a second mental hop.
    const perIndividualHtml = empOrderForYear.length ? `
      <div style="margin-top:20px; padding-top:14px; border-top:1px dashed var(--border);">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; margin-bottom:10px;">
          <div style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.06em;">Per individual — ${selectedYear}</div>
          <div style="font-size:11px; color:var(--text-muted);">Each row scaled to that person's biggest month</div>
        </div>
        <div style="display:flex; flex-direction:column; gap:8px;">
          ${empOrderForYear.map(emp => {
            const sw = _employeeSwatch(emp);
            const empMonths = monthAbbr.map((_, idx) => {
              const mmKey = `${selectedYear}-${String(idx + 1).padStart(2, '0')}`;
              return (monthEmpBuckets[mmKey]?.[emp]) || 0;
            });
            const empMax = Math.max(0.01, ...empMonths);
            const empYearStats = yearEmpBuckets[selectedYear]?.[emp] || { total: 0, paid: 0, pending: 0 };
            const isFocused = _commTimeFocusEmp === emp;
            const isDimmed  = _commTimeFocusEmp && !isFocused;
            return `
              <div style="display:grid; grid-template-columns:minmax(140px, 180px) 1fr minmax(150px, 180px); gap:14px; align-items:center;
                          padding:8px 10px; border-radius:10px; background:var(--surface2); border:1px solid var(--border);
                          opacity:${isDimmed ? 0.5 : 1}; transition:opacity 0.1s;">
                <div style="display:flex; align-items:center; gap:8px; min-width:0;">
                  <span style="width:10px; height:10px; border-radius:3px; background:${sw.fill}; flex-shrink:0;"></span>
                  <span style="font-weight:600; color:var(--text); font-size:13px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="${esc(emp)}">${esc(emp)}</span>
                </div>
                <div style="display:grid; grid-template-columns:repeat(12, 1fr); gap:3px; height:38px;">
                  ${empMonths.map((v, idx) => {
                    const h = (v / empMax) * 100;
                    const tip = v > 0 ? `${monthAbbr[idx]} ${selectedYear}: ${fmtUsd(v)}` : `${monthAbbr[idx]} ${selectedYear}: —`;
                    return `
                      <div style="display:flex; flex-direction:column; align-items:center; justify-content:flex-end; min-width:0;" title="${esc(tip)}">
                        <div style="width:100%; height:${h}%; ${v > 0 ? 'min-height:2px;' : ''} background:${v > 0 ? sw.fill : 'transparent'}; border-radius:3px 3px 0 0;"></div>
                      </div>
                    `;
                  }).join('')}
                </div>
                <div style="display:flex; flex-direction:column; align-items:flex-end; gap:2px; font-variant-numeric:tabular-nums;">
                  <span style="font-size:14px; font-weight:700; color:var(--text);">${fmtUsd(empYearStats.total)}</span>
                  <span style="font-size:10px; color:var(--text-muted);">
                    ${empYearStats.paid > 0    ? `<span style="color:var(--success, #16a34a); font-weight:600;">${fmtUsd0(empYearStats.paid)}</span> paid`       : ''}
                    ${empYearStats.paid > 0 && empYearStats.pending > 0 ? ' · ' : ''}
                    ${empYearStats.pending > 0 ? `<span style="color:var(--accent); font-weight:600;">${fmtUsd0(empYearStats.pending)}</span> pending` : ''}
                    ${empYearStats.total === 0 ? 'No earnings this year' : ''}
                  </span>
                </div>
              </div>
            `;
          }).join('')}
        </div>
      </div>
    ` : '';

    // All-year totals strip — clickable chips so you can jump between
    // years. Each chip now lists per-employee splits underneath the
    // year total, so you can see who carried which year at a glance.
    const allYearChips = yearsAvailable.length ? `
      <div style="margin-top:18px; padding-top:14px; border-top:1px dashed var(--border);">
        <div style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:8px;">All years</div>
        <div style="display:flex; flex-wrap:wrap; gap:10px;">
          ${yearsAvailable.map(y => {
            const isActive = y === selectedYear;
            const tot = yearBuckets[y].total;
            const empSplits = Object.entries(yearEmpBuckets[y] || {})
              .filter(([, v]) => v.total > 0)
              .sort((a, b) => b[1].total - a[1].total);
            return `
              <button onclick="setCommTimeYear(${y})"
                style="cursor:pointer; padding:10px 14px; border-radius:10px; font-family:inherit; text-align:left; min-width:160px;
                       background:${isActive ? 'var(--accent-glow)' : 'var(--surface2)'};
                       border:1px solid ${isActive ? 'color-mix(in srgb, var(--accent) 50%, var(--border))' : 'var(--border)'};
                       color:${isActive ? 'var(--accent)' : 'var(--text)'};
                       display:inline-flex; flex-direction:column; align-items:flex-start; gap:6px;
                       transition:transform 0.1s, box-shadow 0.1s;"
                onmouseenter="this.style.transform='translateY(-1px)'"
                onmouseleave="this.style.transform=''">
                <span style="font-size:10px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.06em;">${y}</span>
                <span style="font-size:14px; font-weight:700; font-variant-numeric:tabular-nums;">${fmtUsd0(tot)}</span>
                ${empSplits.length ? `
                  <span style="display:flex; flex-direction:column; gap:2px; width:100%;">
                    ${empSplits.map(([emp, v]) => {
                      const sw = _employeeSwatch(emp);
                      return `
                        <span style="display:flex; align-items:center; gap:6px; font-size:11px; color:var(--text-muted); font-weight:500;">
                          <span style="width:8px; height:8px; border-radius:2px; background:${sw.fill}; flex-shrink:0;"></span>
                          <span style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:90px;" title="${esc(emp)}">${esc(emp)}</span>
                          <span style="margin-left:auto; font-variant-numeric:tabular-nums; color:var(--text); font-weight:600;">${fmtUsd0(v.total)}</span>
                        </span>
                      `;
                    }).join('')}
                  </span>
                ` : ''}
              </button>
            `;
          }).join('')}
        </div>
      </div>
    ` : '';

    // Pill-style mini summary above the chart so the headline numbers for
    // the *selected year* are obvious at a glance.
    const yearSummaryStrip = `
      <div style="display:flex; flex-wrap:wrap; gap:18px; align-items:center; font-size:12px; color:var(--text-muted); margin-top:6px;">
        <span><strong style="color:var(--text); font-size:15px; font-weight:700;">${fmtUsd(yearTot)}</strong> total</span>
        <span>·</span>
        <span><strong style="color:var(--accent); font-weight:700;">${fmtUsd(yearPend)}</strong> pending</span>
        <span>·</span>
        <span><strong style="color:var(--success, #16a34a); font-weight:700;">${fmtUsd(yearPaid)}</strong> paid</span>
        <span>·</span>
        <span>${fmtUsd(avgPerMonth)} avg/mo${monthsWith ? ` over ${monthsWith} active month${monthsWith !== 1 ? 's' : ''}` : ''}</span>
      </div>
    `;

    const earningsOverTimeHtml = `
      <div class="section-card" style="padding:18px 20px; margin-bottom:18px;">
        <div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
          <div style="flex:1; min-width:0;">
            <div class="inv-dash-row-title">Earnings over time</div>
            <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">Monthly totals — ${selectedYear}${_commTimeFocusEmp ? ` · focused on <strong style="color:var(--text);">${esc(_commTimeFocusEmp)}</strong>` : ''}</div>
          </div>
          ${yearSelector}
        </div>
        ${yearSummaryStrip}
        ${empLegendHtml}
        ${chartHtml}
        ${perIndividualHtml}
        ${allYearChips}
      </div>
    `;

    // ── Top clients by commission (mirrors Inventory's "top clients by SKU count") ──
    const clientMap = {};
    rows.forEach(r => {
      const k = r.client_name || '—';
      if (!clientMap[k]) clientMap[k] = 0;
      clientMap[k] += parseFloat(r.commission_amount) || 0;
    });
    const topClients = Object.entries(clientMap).sort((a,b) => b[1] - a[1]).slice(0, 5);
    const maxClient = topClients[0] ? topClients[0][1] : 1;

    const topClientsHtml = topClients.length ? `
      <div class="section-card" style="padding:18px 20px; margin-bottom:18px;">
        <div class="inv-dash-row-title">Top clients by commission</div>
        <div style="margin-top:10px;">
          ${topClients.map(([name, amt]) => {
            const pct = Math.max(4, (amt / Math.max(maxClient, 0.01)) * 100);
            return `
              <div class="inv-topclient-row">
                <div class="inv-topclient-name" title="${esc(name)}">${esc(name)}</div>
                <div class="inv-topclient-bar-wrap"><div class="inv-topclient-bar" style="width:${pct}%;"></div></div>
                <div class="inv-topclient-count">${fmtUsd0(amt)}</div>
              </div>`;
          }).join('')}
        </div>
      </div>
    ` : '';

    // ── Recent commission activity (table-style with header row) ──
    const recent = rows.slice(0, 8);
    const gridCols = 'minmax(110px, 1.4fr) minmax(80px, 0.8fr) minmax(110px, 1.3fr) minmax(110px, 1fr) minmax(70px, 0.6fr) minmax(80px, 0.8fr) minmax(100px, 0.9fr)';
    const colHeaders = ['Product', 'SKU', 'Client', 'Employee', 'Role', 'Amount', 'Date'];
    const headerRowStyle = `display:grid; grid-template-columns:${gridCols}; column-gap:12px; align-items:center; padding:6px 0 8px; border-bottom:1px solid var(--border); margin-bottom:4px;`;
    const headerCellStyle = 'font-size:10px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;';
    const cellStyle = 'display:flex; align-items:center; min-width:0; overflow:hidden; color:var(--text); font-size:13px;';
    // rolePill is declared above (alongside the per-employee breakdown
    // builder) so both the breakdown and this recent-activity table can
    // reuse the same pill rendering.

    const recentHtml = `
      <div class="section-card" style="padding:18px 20px; min-width:0;">
        <div class="inv-dash-row-title">Recent commission activity</div>
        <div style="${headerRowStyle}">
          ${colHeaders.map(h => `<div style="${headerCellStyle}">${h}</div>`).join('')}
        </div>
        ${recent.map(r => {
          const amt = parseFloat(r.commission_amount) || 0;
          const wbHref = (r.client_name && r.workbook_id)
            ? `#/client/${encodeURIComponent(r.client_name)}/workbook/${r.workbook_id}`
            : '';
          // Product cell becomes a clickable workbook pill when we can route to it.
          const productCell = wbHref
            ? `<span class="inv-wb-pill" onclick="location.hash='${wbHref.substring(1)}'" title="${esc(r.product_name)}" style="max-width:100%; min-width:0;"><span class="inv-wb-pill-text">${esc(r.product_name) || '—'}</span><span class="inv-wb-pill-arrow">→</span></span>`
            : `<span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${esc(r.product_name)}">${esc(r.product_name) || '—'}</span>`;
          return `
            <div class="inv-activity-row" style="display:grid; grid-template-columns:${gridCols}; column-gap:12px; align-items:center;">
              <div style="${cellStyle}">${productCell}</div>
              <div style="${cellStyle}"><span class="inv-sku" style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${esc(r.sku) || '—'}</span></div>
              <div style="${cellStyle}"><span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${esc(r.client_name) || '—'}</span></div>
              <div style="${cellStyle}"><span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${esc(r.employee) || '—'}</span></div>
              <div style="${cellStyle}">${rolePill(r.role)}</div>
              <div style="${cellStyle}"><span style="color:var(--success, #16a34a); font-weight:600;">${fmtUsd(amt)}</span>${+r.is_estimate === 1 ? '<span style="margin-left:6px; font-size:10px; color:var(--text-muted); font-style:italic;" title="Estimate based on Our Cost — Client Cost not yet wired">est</span>' : ''}</div>
              <div style="${cellStyle} white-space:nowrap;" title="${esc(r.created_at || '')}">${fmtPretty(r.created_at)}</div>
            </div>`;
        }).join('')}
      </div>
    `;

    host.innerHTML = kpiHtml + empHtml + earningsOverTimeHtml + topClientsHtml + recentHtml;
  }

  // Small helper — human-readable relative time (e.g. "2 days ago", "just now")
  function _relativeTime(ts, now) {
    const diff = now - ts;
    const SEC = 1000, MIN = 60 * SEC, HR = 60 * MIN, DAY = 24 * HR;
    if (diff < 30 * SEC)  return 'just now';
    if (diff < HR)        { const m = Math.floor(diff / MIN); return `${m} min${m !== 1 ? 's' : ''} ago`; }
    if (diff < DAY)       { const h = Math.floor(diff / HR);  return `${h} hour${h !== 1 ? 's' : ''} ago`; }
    if (diff < 7 * DAY)   { const d = Math.floor(diff / DAY); return `${d} day${d !== 1 ? 's' : ''} ago`; }
    if (diff < 30 * DAY)  { const w = Math.floor(diff / (7 * DAY)); return `${w} week${w !== 1 ? 's' : ''} ago`; }
    if (diff < 365 * DAY) { const mo = Math.floor(diff / (30 * DAY)); return `${mo} month${mo !== 1 ? 's' : ''} ago`; }
    const y = Math.floor(diff / (365 * DAY));
    return `${y} year${y !== 1 ? 's' : ''} ago`;
  }

  // Deterministic color per client name → each client gets its own hue chip.
  // Same name always produces the same color across renders.
  function _clientChipStyle(name) {
    if (!name) return '';
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
      hash = ((hash << 5) - hash) + name.charCodeAt(i);
      hash |= 0;
    }
    const hue = Math.abs(hash) % 360;
    return `background:hsl(${hue},72%,93%);color:hsl(${hue},55%,32%);border-color:hsl(${hue},60%,72%);`;
  }

  function renderInventoryTable(rows) {
    const wrap = document.getElementById('inventory-list-content');
    if (!wrap) return;
    if (!rows.length) {
      wrap.innerHTML = `
        <div style="text-align:center; padding:60px 24px; color:var(--text-muted);">
          <div style="font-size:32px; margin-bottom:12px;">📦</div>
          <div style="font-size:16px; font-weight:600; margin-bottom:6px;">No SKUs promoted yet</div>
          <div style="font-size:13px;">Open a workbook and promote RFQ line items to add SKUs here.</div>
        </div>`;
      return;
    }
    const fmtDate = ts => {
      if (!ts) return '—';
      const d = new Date(ts);
      return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: '2-digit' });
    };
    // Pre-compute workbook names so we can sort on them
    const enriched = rows.map(r => {
      let wbName = '';
      let wbHref = '';
      if (r.workbook_id && r.client_name) {
        const items = clientData[r.client_name] || [];
        const wb = items.find(i => i.id == r.workbook_id);
        if (wb) { wbName = wb.product; wbHref = `#/client/${encodeURIComponent(r.client_name)}/workbook/${r.workbook_id}`; }
      }
      return { ...r, _wbName: wbName, _wbHref: wbHref };
    });
    // Apply sort
    if (_invSort.col) {
      enriched.sort((a, b) => {
        let va, vb;
        if (_invSort.col === 'product')  { va = (a.product_name || '').toLowerCase(); vb = (b.product_name || '').toLowerCase(); }
        else if (_invSort.col === 'sku') { va = (a.sku || '').toLowerCase(); vb = (b.sku || '').toLowerCase(); }
        else if (_invSort.col === 'client') { va = (a.client_name || '').toLowerCase(); vb = (b.client_name || '').toLowerCase(); }
        else if (_invSort.col === 'workbook') { va = (a._wbName || '').toLowerCase(); vb = (b._wbName || '').toLowerCase(); }
        else if (_invSort.col === 'added') { va = a.promoted_at || ''; vb = b.promoted_at || ''; }
        else { return 0; }
        return va < vb ? -_invSort.dir : va > vb ? _invSort.dir : 0;
      });
    }
    // Sort icon helper
    const sortIco = col => {
      const active = _invSort.col === col;
      const up = active && _invSort.dir === 1;
      const color = active ? 'var(--accent)' : 'var(--text-muted)';
      return `<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="${color}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
        <polyline points="${up ? '18 15 12 9 6 15' : '6 9 12 15 18 9'}"/>
      </svg>`;
    };
    const thStyle = `style="cursor:pointer; user-select:none; white-space:nowrap;"`;
    wrap.innerHTML = `
      <table class="inv-table">
        <thead>
          <tr>
            <th onclick="sortInventory('product')" ${thStyle}><span style="display:inline-flex;align-items:center;gap:4px;">Product ${sortIco('product')}</span></th>
            <th onclick="sortInventory('sku')" ${thStyle}><span style="display:inline-flex;align-items:center;gap:4px;">SKU ${sortIco('sku')}</span></th>
            <th onclick="sortInventory('client')" ${thStyle}><span style="display:inline-flex;align-items:center;gap:4px;">Client ${sortIco('client')}</span></th>
            <th onclick="sortInventory('workbook')" ${thStyle}><span style="display:inline-flex;align-items:center;gap:4px;">Source Workbook ${sortIco('workbook')}</span></th>
            <th onclick="sortInventory('added')" ${thStyle}><span style="display:inline-flex;align-items:center;gap:4px;">Added ${sortIco('added')}</span></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          ${enriched.map(r => `<tr>
            <td>
              <div class="inv-product-name">${r.product_name || '—'}</div>
              ${r.variant_name ? `<div><span class="inv-variant-chip">${r.variant_name}</span></div>` : ''}
            </td>
            <td><span class="inv-sku">${r.sku}</span></td>
            <td>${r.client_name ? `<span class="inv-client-chip" style="${_clientChipStyle(r.client_name)}">${r.client_name}</span>` : `<span style="color:var(--text-muted); font-size:12px;">—</span>`}</td>
            <td>${r._wbHref ? `<span class="inv-wb-pill" onclick="location.hash='${r._wbHref}'" title="${r._wbName}"><span class="inv-wb-pill-text">${r._wbName}</span><span class="inv-wb-pill-arrow">→</span></span>` : `<span style="color:var(--text-muted); font-size:12px;">${r._wbName || '—'}</span>`}</td>
            <td style="color:var(--text-muted); font-size:12px;">${fmtDate(r.promoted_at)}</td>
            <td style="text-align:center;"><button class="inv-remove-btn" onclick="removeInventorySku(${r.id})" title="Remove from inventory">&times;</button></td>
          </tr>`).join('')}
        </tbody>
      </table>`;
  }

  function sortInventory(col) {
    if (_invSort.col === col) { _invSort.dir *= -1; } else { _invSort.col = col; _invSort.dir = 1; }
    const q = document.getElementById('inventory-search')?.value || '';
    filterInventory(q);
  }

  function filterInventory(query) {
    const q = (query || '').toLowerCase().trim();
    // Respect the client filter first, then text search within it.
    const base = _invFilteredRows();
    if (!q) { renderInventoryTable(base); return; }
    renderInventoryTable(base.filter(r =>
      (r.sku || '').toLowerCase().includes(q) ||
      (r.product_name || '').toLowerCase().includes(q) ||
      (r.variant_name || '').toLowerCase().includes(q) ||
      (r.client_name || '').toLowerCase().includes(q)
    ));
  }

  async function removeInventorySku(id) {
    if (!confirm('Remove this SKU from inventory?')) return;
    const res = await apiCall('remove_sku', { id });
    if (res.success) {
      inventoryData = inventoryData.filter(r => r.id !== id);
      // Re-populate dropdown (a client may have dropped to zero SKUs) and refresh current view
      populateInventoryClientFilter();
      _updateInventoryCountBadge();
      if (_invTab === 'dashboard') {
        renderInventoryDashboard();
      } else {
        const q = document.getElementById('inventory-search')?.value || '';
        filterInventory(q);
      }
    }
  }

  function updatePromoteButton() {
    const btn = document.getElementById('btn-promote-sku');
    if (!btn) return;
    const wbDbId = dbWorkbookMap[`${currentClient}|${currentWorkbookId}`] || currentWorkbookId;
    const wbSkus = inventoryData.filter(r => String(r.workbook_id) === String(wbDbId));
    if (wbSkus.length > 0) {
      const n = wbSkus.length;
      btn.innerHTML = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg><span style="color:var(--success);">✓ ${n} SKU${n !== 1 ? 's' : ''} in Inventory</span>`;
      btn.onclick = () => { location.hash = '#/inventory'; };
      btn.title = 'View in Inventory';
      btn.onmouseover = () => { btn.style.borderColor = 'var(--success)'; btn.style.color = 'var(--success)'; };
      btn.onmouseout  = () => { btn.style.borderColor = 'var(--border)'; btn.style.color = 'var(--text-muted)'; };
    } else {
      btn.innerHTML = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 21 12 17.77 5.82 21 7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>Promote to Permanent SKU`;
      btn.onclick = () => promoteWorkbookToInventory();
      btn.title = 'Promote all RFQ SKUs from this workbook to permanent SKUs';
      btn.onmouseover = () => { btn.style.borderColor = 'var(--accent)'; btn.style.color = 'var(--accent)'; };
      btn.onmouseout  = () => { btn.style.borderColor = 'var(--border)'; btn.style.color = 'var(--text-muted)'; };
    }
  }

  async function promoteWorkbookToInventory() {
    const rfqItems = collectRfqItems();
    const toPromote = [];
    rfqItems.forEach(item => {
      if (!item.sku) return;
      // qty + unit_price_usd are sent so the server can record commission rows
      // (20% of qty × unit_price_usd) for the AM / Salesperson on this client.
      // unit_price_usd is derived from the RFQ row's RMB price using the live
      // FX rate. Until "Client Cost" is wired on the Pricing tab, this is
      // Our Cost — commission rows are flagged is_estimate=1 server-side.
      const qtyNum   = parseFloat(String(item.qty || '').replace(/,/g, '')) || 0;
      const rmbNum   = parseFloat(String(item.priceRmb || '').replace(/,/g, '')) || 0;
      const usdNum   = (rmbNum > 0 && USD_TO_RMB > 0) ? +(rmbNum / USD_TO_RMB).toFixed(4) : 0;
      toPromote.push({
        sku: item.sku,
        product_name: item.item || item.sku,
        variant_name: null,
        client_name: currentClient,
        workbook_id: dbWorkbookMap[`${currentClient}|${currentWorkbookId}`] || currentWorkbookId,
        qty: qtyNum,
        unit_price_usd: usdNum,
      });
    });
    if (!toPromote.length) {
      alert('No SKUs found on this workbook\'s RFQ items. Add SKUs to line items first.');
      return;
    }
    const res = await apiCall('promote_to_sku', { items: toPromote });
    if (res.success) {
      await loadInventory();
      // Server may have recorded commission rows for the AM/SP on this
      // client — refresh in-memory data so the Commissions dashboard is
      // fresh next time it's opened.
      loadCommissions();
      updatePromoteButton();
    }
  }

  (async function init() {
    // Try loading from LocalStorage immediately for fast render
    loadFromLocalStorage();
    loadShipments();
    loadOrders();
    loadInventory();
    loadCommissions();
    rebuildSidebar();
    rebuildShipmentsNav();
    rebuildOrdersNav();
    rebuildSamplesNav();
    rebuildRfqNav();
    restoreNavSectionStates();
    // Wrap in try/catch so a crash inside fillWorkbook never prevents loadFromDatabase() from running
    try { router(); } catch(e) { console.error('[MS Router] init render error:', e); }

    // Check for portal change requests on load, then every 60 s
    checkPortalChanges();
    setInterval(checkPortalChanges,    60_000);
    setInterval(pollForRemoteChanges,  60_000); // picks up partner changes across all data

    // Then try loading from database (will override if successful)
    // If the user typed something before the DB responded, capture it so we don't lose it
    const preDbClient = currentClient;
    const preDbWbId = currentWorkbookId;
    const dbLoaded = await loadFromDatabase();
    if (dbLoaded === true) {
      console.log('Data loaded from database');
      // Only preserve pre-DB form state if the user actually made edits before DB responded
      if (_isDirty && preDbClient && preDbWbId) {
        const preKey = `${preDbClient}|${preDbWbId}`;
        workbookDetail[preKey] = collectWorkbookDetail();
      }
      rebuildRfqNav();  // workbookDetail (incl. sentToRfq flags) now populated from DB
      router(); // Re-render with DB data (or user's edits if they were faster)
    } else if (dbLoaded === 'empty') {
      // DB reachable and confirmed empty — safe to seed, but only once per device.
      // The ms_db_seeded flag gets set inside loadFromDatabase the first time
      // workbooks are successfully loaded from the DB, so we only seed on a truly
      // fresh device/browser.
      const alreadySeeded = (() => { try { return localStorage.getItem('ms_db_seeded') === '1'; } catch(e) { return false; } })();
      if (!alreadySeeded) {
        console.log('DB empty on fresh device — seeding with local sample data');
        seedDatabase();
      } else {
        console.log('DB reports empty but device has seeded before — skipping seed (safety guard)');
      }
    } else {
      // dbLoaded === false (API failure) — keep using local data; NEVER seed here.
      console.log('API unreachable, using local/fallback data (skipping seed)');
    }

    // Sync orders and shipments from DB (multi-user shared state)
    const [ordersFromDB, shipmentsFromDB] = await Promise.all([
      syncOrdersFromDB(),
      syncShipmentsFromDB(),
    ]);
    if (ordersFromDB || shipmentsFromDB) {
      rebuildOrdersNav();
      rebuildShipmentsNav();
      // If the current view is an order or shipment, re-render it with fresh data
      const h = location.hash;
      if (h.startsWith('#/order') || h.startsWith('#/shipment') || h.startsWith('#/orders') || h.startsWith('#/shipments')) {
        router();
      }
    }

    // Allow autosave now — just after _filling clears (200ms in fillWorkbook + small buffer)
    setTimeout(() => { _appReady = true; }, 210);

    // Restore saved username into sidebar input
  })();

  // In-memory guard so multiple concurrent callers can't both enter this function.
  let _seedingInFlight = false;
  async function seedDatabase() {
    // Guard 1: prevent concurrent invocations
    if (_seedingInFlight) {
      console.warn('[seedDatabase] already in flight — skipping duplicate call');
      return;
    }
    // Guard 2: persistent flag — once seeded on this device, never again
    try {
      if (localStorage.getItem('ms_db_seeded') === '1') {
        console.warn('[seedDatabase] ms_db_seeded flag set — aborting to prevent duplicates');
        return;
      }
    } catch(e) {}

    _seedingInFlight = true;
    try {
      // Guard 3: re-check DB before inserting. Require success AND empty — if the
      // call fails we bail (transient API errors used to trigger re-seeds, which
      // was the root cause of cross-device workbook duplication).
      const wbResult = await apiCall('get_workbooks');
      if (!wbResult.success) {
        console.warn('[seedDatabase] get_workbooks failed — aborting seed to avoid dupes');
        return;
      }
      if (wbResult.data && wbResult.data.length > 0) {
        // DB already has workbooks — mark seeded and bail
        try { localStorage.setItem('ms_db_seeded', '1'); } catch(e) {}
        return;
      }

      // Get existing clients from DB to build the map
      const clientResult = await apiCall('get_clients');
      if (clientResult.success && clientResult.data) {
        clientResult.data.forEach(c => { dbClientMap[c.name] = c.id; });
      }

      // Seed any missing clients
      for (const name of Object.keys(clientData).sort()) {
        if (!dbClientMap[name]) {
          const r = await apiCall('add_client', { name });
          if (r.success) dbClientMap[name] = r.id;
        }
      }

      // Seed workbooks
      for (const [clientName, items] of Object.entries(clientData)) {
        const clientId = dbClientMap[clientName];
        if (!clientId) continue;
        for (const item of items) {
          const detail = workbookDetail[`${clientName}|${item.id}`] || {};
          const r = await apiCall('add_workbook', {
            client_id: clientId,
            product_name: item.product,
            description: item.description,
            detail: detail
          });
          if (r.success) {
            // Update flow
            const step = flowToStep(item.flow);
            await apiCall('update_flow', { id: r.id, flow_step: step });
            dbWorkbookMap[`${clientName}|${r.id}`] = r.id;
          }
        }
      }
      // Lock out future seed attempts on this device
      try { localStorage.setItem('ms_db_seeded', '1'); } catch(e) {}
      saveToLocalStorage();
      console.log('Database seeded with sample data');
    } finally {
      _seedingInFlight = false;
    }
  }

  /* ══════════════════════════════════════════════════════════════════════
     SHIPMENTS MODULE
  ══════════════════════════════════════════════════════════════════════ */

  // shipmentData, _nextShipmentId, _currentShipmentId, _wbPickerSelected, CONTAINER_SPECS declared above init()

  // ── Persistence ──────────────────────────────────────────────────────
  function saveShipments() {
    try { localStorage.setItem('ms_shipmentData', JSON.stringify(shipmentData)); } catch(e) {}
    try { localStorage.setItem('ms_nextShipmentId', String(_nextShipmentId)); } catch(e) {}
    if (Object.keys(shipmentData).length > 0 || _nextShipmentId > 1) {
      const payload = JSON.stringify({ data: shipmentData, nextId: _nextShipmentId });
      _lastShipmentsJson = payload; // prevent our own save from triggering a poll re-render
      apiCall('save_app_state', { key: 'ms_shipments', value: payload }).catch(() => {});
    }
  }

  function loadShipments() {
    try {
      const sd = localStorage.getItem('ms_shipmentData');
      const ni = localStorage.getItem('ms_nextShipmentId');
      if (sd) Object.assign(shipmentData, JSON.parse(sd));
      if (ni) _nextShipmentId = parseInt(ni) || 1;
    } catch(e) {}
    // Ensure the shipments nav section is never persisted as collapsed
    try {
      const nc = JSON.parse(localStorage.getItem('ms_nav_collapsed') || '{}');
      if (nc['nav-section-containers']) {
        delete nc['nav-section-containers'];
        localStorage.setItem('ms_nav_collapsed', JSON.stringify(nc));
      }
    } catch(e) {}
  }

  // ── Calculations ─────────────────────────────────────────────────────
  function calcWorkbookShipStats(detail, qty) {
    qty = parseInt(qty) || 0;
    const outerLCm     = parseFloat(detail.cartonOuterLCm)    || 0;
    const outerWCm     = parseFloat(detail.cartonOuterWCm)    || 0;
    const outerHCm     = parseFloat(detail.cartonOuterHCm)    || 0;
    const outerWeightKg = parseFloat(detail.cartonOuterWeight) || 0;
    const innerCount   = parseInt(detail.cartonInnerCount)    || 0;
    const outerCount   = parseInt(detail.cartonOuterCount)    || 0;
    const unitsPerOuter = innerCount * outerCount;

    let totalCartons = 0;
    if (unitsPerOuter > 0 && qty > 0) totalCartons = Math.ceil(qty / unitsPerOuter);

    let cbmPerCarton = 0;
    if (outerLCm > 0 && outerWCm > 0 && outerHCm > 0)
      cbmPerCarton = (outerLCm * outerWCm * outerHCm) / 1000000;
    const totalCbm = parseFloat((cbmPerCarton * totalCartons).toFixed(2));
    const totalWeightKg = parseFloat((outerWeightKg * totalCartons).toFixed(1));

    // Pallet stacking: standard 40×48" pallet = 121.9 × 101.6 cm, max height 180 cm
    const PL = 121.9, PW = 101.6, PH = 180;
    let palletsNeeded = 0;
    if (outerLCm > 0 && outerWCm > 0 && outerHCm > 0 && totalCartons > 0) {
      const perRow    = Math.max(1, Math.floor(PL / outerLCm));
      const perCol    = Math.max(1, Math.floor(PW / outerWCm));
      const perLayer  = perRow * perCol;
      const maxLayers = Math.max(1, Math.floor(PH / outerHCm));
      const perPallet = Math.max(1, perLayer * maxLayers);
      palletsNeeded   = Math.ceil(totalCartons / perPallet);
    } else if (totalCartons > 0) {
      // Fallback: assume 20 cartons/pallet
      palletsNeeded = Math.ceil(totalCartons / 20);
    }

    return { qty, totalCartons, totalCbm, totalWeightKg, palletsNeeded, unitsPerOuter };
  }

  function orderShipStats(orderId) {
    const order = orderData[orderId];
    if (!order) return { qty: 0, totalCartons: 0, totalCbm: 0, totalWeightKg: 0, palletsNeeded: 0, totalRmb: 0, totalUsd: 0, wbCount: 0 };
    let qty = 0, totalCartons = 0, totalCbm = 0, totalWeightKg = 0, palletsNeeded = 0, totalRmb = 0, totalUsd = 0;
    const exchange = 7.2;
    (order.entries || []).forEach(wbEntry => {
      const key    = `${wbEntry.clientName}|${wbEntry.workbookId}`;
      const detail = workbookDetail[key];
      if (!detail) return;
      const tiers  = Array.isArray(detail.tiers) ? detail.tiers : [];
      const tier   = tiers.find(t => t.id == detail.selectedTierIdx) || tiers[0];
      const wbQty  = tier ? (parseFloat(tier.qty)   || 0) : 0;
      const price  = tier ? (parseFloat(tier.price) || 0) : 0;
      qty        += wbQty;
      totalRmb   += price * wbQty;
      totalUsd   += (price / exchange) * wbQty;
      const s = calcWorkbookShipStats(detail, wbQty);
      totalCartons  += s.totalCartons;
      totalCbm      += s.totalCbm;
      totalWeightKg += s.totalWeightKg;
      palletsNeeded += s.palletsNeeded;
    });
    return {
      wbCount:      (order.entries || []).length,
      qty:          qty,
      totalCartons: totalCartons,
      totalCbm:     parseFloat(totalCbm.toFixed(2)),
      totalWeightKg: parseFloat(totalWeightKg.toFixed(1)),
      palletsNeeded: Math.ceil(palletsNeeded),
      totalRmb:     Math.round(totalRmb * 100) / 100,
      totalUsd:     Math.round(totalUsd * 100) / 100,
    };
  }

  function shipmentTotals(shipment) {
    let totalCbm = 0, totalKg = 0, totalPallets = 0;
    (shipment.entries || []).forEach(entry => {
      if (entry.orderId) {
        const s = orderShipStats(entry.orderId);
        totalCbm     += s.totalCbm;
        totalKg      += s.totalWeightKg;
        totalPallets += s.palletsNeeded;
      } else {
        // Legacy workbook entry
        const key    = `${entry.clientName}|${entry.workbookId}`;
        const detail = workbookDetail[key];
        if (!detail) return;
        const s = calcWorkbookShipStats(detail, entry.qty);
        totalCbm     += s.totalCbm;
        totalKg      += s.totalWeightKg;
        totalPallets += s.palletsNeeded;
      }
    });
    return {
      cbm:     parseFloat(totalCbm.toFixed(2)),
      kg:      parseFloat(totalKg.toFixed(1)),
      pallets: totalPallets,
    };
  }

  // ── Nav ──────────────────────────────────────────────────────────────
  function rebuildShipmentsNav() {
    const ids = Object.keys(shipmentData);
    // Actionable = any linked order has a change request
    const shipActionable = ids.filter(id => {
      const entries = shipmentData[id].entries || [];
      return entries.some(e => e.orderId && orderData[e.orderId]?.changeRequested);
    }).length;
    _applyNavBadge(document.getElementById('badge-shipments'), shipActionable, ids.length);
    // Shipments list (if a sub-nav list element exists — may not, since Shipments is now a flat link)
    const list = document.getElementById('shipments-nav-list');
    if (!list) return;
    list.innerHTML = ids.map(id => {
      const s = shipmentData[id];
      const statusDot = `<span class="nav-shipment-dot ${s.status}"></span>`;
      return `<div class="nav-shipment-item" id="nav-ship-${id}" onclick="location.hash='#/shipment/${id}'">
        ${statusDot}<span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${s.name}</span>
      </div>`;
    }).join('');
  }

  function rebuildSamplesNav() {
    const all = collectAllSamples();
    // Actionable = any order linked to the same workbook has a change request
    const sampleActionable = all.filter(s => {
      return Object.values(orderData).some(o =>
        o.changeRequested &&
        (o.entries || []).some(e =>
          e.clientName === s.clientName && String(e.workbookId) === String(s.workbookId)
        )
      );
    }).length;
    _applyNavBadge(document.getElementById('badge-samples'), sampleActionable, all.length);
  }

  // ── Create ────────────────────────────────────────────────────────────
  function openNewShipmentModal() {
    document.getElementById('new-ship-name').value = '';
    document.getElementById('new-ship-container').value = '40hc';
    document.getElementById('modal-new-shipment').classList.add('open');
    setTimeout(() => document.getElementById('new-ship-name').focus(), 80);
  }
  function closeNewShipmentModal() {
    document.getElementById('modal-new-shipment').classList.remove('open');
  }

  function createShipment(e) {
    e.preventDefault();
    const name      = document.getElementById('new-ship-name').value.trim();
    const container = document.getElementById('new-ship-container').value;
    if (!name) return;
    const id = _nextShipmentId++;
    shipmentData[id] = {
      id, name,
      containerType: container,
      status: 'planning',
      dateCreated: new Date().toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'2-digit'}),
      etd: '', eta: '',
      entries: [],
    };
    saveShipments();
    rebuildShipmentsNav();
    closeNewShipmentModal();
    location.hash = `#/shipment/${id}`;
  }

  // ── List view ────────────────────────────────────────────────────────
  function renderShipmentsList() {
    document.getElementById('header-title').textContent = 'Shipments';
    document.querySelectorAll('.sidebar-nav .nav-item').forEach(a => a.classList.remove('active'));
    document.querySelectorAll('.nav-flat-link').forEach(a => a.classList.remove('active'));
    const shipNav = document.getElementById('nav-shipments-link');
    if (shipNav) shipNav.classList.add('active');
    showView('view-shipments');
    renderShipmentsContent();
  }

  function filterShipments(status, btn) {
    _shipFilter = status;
    document.querySelectorAll('.ship-filter-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    renderShipmentsContent();
  }

  // Completed = delivered for 30+ days. Missing deliveredOn = treated as recent.
  function _isArchiveCompleted(s) {
    if (!s || s.status !== 'delivered' || !s.deliveredOn) return false;
    const delivered = new Date(s.deliveredOn);
    if (isNaN(delivered.getTime())) return false;
    const days = (Date.now() - delivered.getTime()) / 86400000;
    return days >= 30;
  }

  function renderShipmentsContent() {
    const el = document.getElementById('shipment-list-content');
    if (!el) return;

    const allIds = Object.keys(shipmentData);
    if (allIds.length === 0) {
      el.innerHTML = `<div class="shipment-list-empty">
        <div class="shipment-list-empty-icon">🚢</div>
        <div class="shipment-list-empty-title">No shipments yet</div>
        <div class="shipment-list-empty-sub">Create a shipment to consolidate workbooks into a container.</div>
      </div>`;
      return;
    }

    // Split completed (delivered 30+ days) from the main list
    const completedIds = allIds.filter(id => _isArchiveCompleted(shipmentData[id]));
    const ids          = allIds.filter(id => !_isArchiveCompleted(shipmentData[id]));

    const STATUS_ORDER = ['planning', 'booked', 'in_transit', 'delivered'];
    const filterLabels = { all: 'All', planning: 'Planning', booked: 'Booked', in_transit: 'In Transit', delivered: 'Delivered' };

    // Counts per status for badges on filter buttons (main list only — completed is separate)
    const counts = { all: ids.length };
    ids.forEach(id => {
      const st = shipmentData[id].status;
      counts[st] = (counts[st] || 0) + 1;
    });

    const filterBar = `<div class="ship-filter-bar">
      ${['all','planning','booked','in_transit','delivered'].map(s =>
        `<button class="ship-filter-btn${_shipFilter === s ? ' active' : ''}" onclick="filterShipments('${s}', this)">
          ${filterLabels[s]}${counts[s] ? ` <span style="opacity:0.7">(${counts[s]})</span>` : ''}
        </button>`
      ).join('')}
    </div>`;

    function buildCard(id) {
      const s      = shipmentData[id];
      const spec   = CONTAINER_SPECS[s.containerType] || CONTAINER_SPECS['40hc'];
      const tot    = shipmentTotals(s);
      const cbmPct    = spec.cbm       > 0 ? Math.round((tot.cbm     / spec.cbm)       * 100) : 0;
      const kgPct     = spec.maxKg     > 0 ? Math.round((tot.kg      / spec.maxKg)     * 100) : 0;
      const palletPct = spec.maxPallets > 0 ? Math.round((tot.pallets / spec.maxPallets) * 100) : 0;
      const isDelivered = s.status === 'delivered';
      const eta = isDelivered
        ? (s.deliveredOn ? `<strong style="color:#34d399">${s.deliveredOn}</strong>` : '—')
        : (s.eta ? `<strong>${s.eta}</strong>` : '—');
      const etaLabel = isDelivered ? 'Delivered' : 'ETA';
      const entries = s.entries || [];
      const orderEntries = entries.filter(e => e.orderId);
      const shipHasChangeRequest = orderEntries.some(e => orderData[e.orderId]?.changeRequested);
      const wbPills = orderEntries.length === 0
        ? `<span style="font-size:12px;color:var(--text-muted);font-style:italic;">No orders added</span>`
        : orderEntries.map(e => {
            const order = orderData[e.orderId];
            if (!order) return '';
            const href = `#/order/${e.orderId}`;
            const crTag = order.changeRequested
              ? `<span style="background:#fff7ed;border:1px solid #fed7aa;color:#E8751A;font-size:9px;font-weight:700;text-transform:uppercase;padding:1px 5px;border-radius:10px;margin-left:4px;vertical-align:middle;">⚑ Hold</span>`
              : '';
            return `<span class="sc-wb-pill" onclick="event.stopPropagation(); location.hash='${href}'">${order.clientName} – ${order.name}${crTag}<span class="sc-wb-pill-arrow">→</span></span>`;
          }).join('');
      const wbCount = orderEntries.length;
      const overStyle = 'color:#ef4444 !important; font-weight:700;';
      const cbmOver = cbmPct > 100; const kgOver = kgPct > 100; const palOver = palletPct > 100;

      return `<div class="shipment-card${shipHasChangeRequest ? ' has-change-request' : ''}" onclick="location.hash='#/shipment/${id}'">
        <div class="sc-left">
          <span class="sc-title">${s.name}</span>
          <span class="sc-eta">${etaLabel} ${eta}</span>
        </div>
        <div class="sc-wb-list">
          <div class="sc-wb-count">${wbCount} order${wbCount !== 1 ? 's' : ''}</div>
          ${wbPills}
        </div>
        <div class="sc-right-wrap">
          <div class="sc-stats-row">
            <span${cbmOver ? ` style="${overStyle}"` : ''}><span class="sc-stat-inline"${cbmOver ? ` style="${overStyle}"` : ''}>${tot.cbm}/${spec.cbm}</span> CBM (${cbmPct}%)</span>
            <span class="sc-divider">|</span>
            <span${kgOver ? ` style="${overStyle}"` : ''}><span class="sc-stat-inline"${kgOver ? ` style="${overStyle}"` : ''}>${tot.kg.toLocaleString('en-US')}</span> kg (${kgPct}%)</span>
            <span class="sc-divider">|</span>
            <span${palOver ? ` style="${overStyle}"` : ''}><span class="sc-stat-inline"${palOver ? ` style="${overStyle}"` : ''}>${tot.pallets}</span> pallet${tot.pallets !== 1 ? 's' : ''} (${palletPct}%)</span>
          </div>
          <div class="sc-right">
            <span class="ship-status-badge ship-status-${s.status}">${s.status.replace('_',' ')}</span>
            <span class="shipment-container-tag">${spec.label}</span>
          </div>
        </div>
      </div>`;
    }

    // Split into active and delivered
    const filtered = ids.filter(id => _shipFilter === 'all' || shipmentData[id].status === _shipFilter);
    const active    = filtered.filter(id => shipmentData[id].status !== 'delivered');
    const delivered = filtered.filter(id => shipmentData[id].status === 'delivered');

    // Sort active by status order
    active.sort((a, b) => STATUS_ORDER.indexOf(shipmentData[a].status) - STATUS_ORDER.indexOf(shipmentData[b].status));

    let html = filterBar;

    if (active.length > 0) {
      html += `<div class="shipment-cards">${active.map(buildCard).join('')}</div>`;
    }

    if (delivered.length > 0) {
      html += `<div class="sc-section-label" style="margin-top:${active.length > 0 ? '20px' : '0'}">Delivered</div>
        <div class="shipment-cards" style="opacity:0.7">${delivered.map(buildCard).join('')}</div>`;
    }

    if (active.length === 0 && delivered.length === 0) {
      html += `<div style="padding:30px 0; text-align:center; color:var(--text-muted); font-size:13px;">No shipments match this filter.</div>`;
    }

    // ── Completed (archived) — delivered 30+ days ago, grouped by month, collapsed by default ──
    // Always rendered, even when empty, so users can see the section exists.
    let completedBody;
    if (completedIds.length === 0) {
      completedBody = `<div style="padding:20px 0;text-align:center;color:var(--text-muted);font-size:13px;font-style:italic;">
        No shipments archived yet. Delivered shipments move here 30 days after their delivery date.
      </div>`;
    } else {
      // Group by "YYYY-MM" of deliveredOn
      const byMonth = {};
      completedIds.forEach(id => {
        const s = shipmentData[id];
        const d = new Date(s.deliveredOn);
        const key = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}`;
        (byMonth[key] = byMonth[key] || []).push(id);
      });
      // Sort months newest → oldest
      const monthKeys = Object.keys(byMonth).sort().reverse();
      const MONTH_NAMES = ['January','February','March','April','May','June','July','August','September','October','November','December'];
      completedBody = monthKeys.map(k => {
        const [y, m] = k.split('-');
        const label = `${MONTH_NAMES[parseInt(m,10)-1]} ${y}`;
        // Sort shipments within a month newest → oldest by deliveredOn
        const sortedIds = byMonth[k].slice().sort((a, b) =>
          new Date(shipmentData[b].deliveredOn) - new Date(shipmentData[a].deliveredOn)
        );
        return `<div class="completed-month-label">${label} <span style="color:var(--text-muted);font-weight:500;">(${sortedIds.length})</span></div>
          <div class="shipment-cards" style="opacity:0.75;margin-bottom:16px;">${sortedIds.map(buildCard).join('')}</div>`;
      }).join('');
    }

    html += `<div class="section-card collapsed" style="margin-top:24px;">
      <div class="section-header section-header-collapsible" onclick="toggleSection(this.closest('.section-card'))">
        <span class="section-title">Archived <span style="color:var(--text-muted);font-weight:500;margin-left:6px;">(${completedIds.length})</span></span>
        <span class="section-chevron" style="margin-left:auto;">›</span>
      </div>
      <div class="section-body" style="padding:16px 20px;">
        ${completedBody}
      </div>
    </div>`;

    el.innerHTML = html;
  }

  // ── Detail view ──────────────────────────────────────────────────────
  function renderShipmentDetail(id) {
    _currentShipmentId = parseInt(id);
    const s = shipmentData[_currentShipmentId];
    if (!s) { location.hash = '#/shipments'; return; }

    document.getElementById('header-title').textContent = s.name;
    document.querySelectorAll('.sidebar-nav .nav-item').forEach(a => a.classList.remove('active'));
    document.querySelectorAll('.nav-flat-link').forEach(a => a.classList.remove('active'));
    const shipNav = document.getElementById('nav-shipments-link');
    if (shipNav) shipNav.classList.add('active');

    // Fill header fields
    document.getElementById('ship-detail-name').value      = s.name;
    document.getElementById('ship-detail-status').value    = s.status;
    document.getElementById('ship-detail-etd').value       = s.etd || '';
    document.getElementById('ship-detail-eta').value       = s.eta || '';
    document.getElementById('ship-detail-delivered').value = s.deliveredOn || '';
    document.getElementById('ship-delivered-wrap').style.display = s.status === 'delivered' ? 'flex' : 'none';

    // Container type buttons
    document.querySelectorAll('.container-type-btn').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.type === s.containerType);
    });

    renderShipmentOrders();
    renderShipmentUtilization();
    showView('view-shipment-detail');
  }

  function renderShipmentOrders() {
    const s = shipmentData[_currentShipmentId];
    if (!s) return;
    const listEl  = document.getElementById('ship-order-list');
    const emptyEl = document.getElementById('ship-order-empty');
    const countEl = document.getElementById('ship-order-count');
    const entries = s.entries || [];

    const orderEntries = entries.filter(e => e.orderId);
    if (countEl) countEl.textContent = orderEntries.length > 0 ? `${orderEntries.length} order${orderEntries.length !== 1 ? 's' : ''}` : '';

    if (orderEntries.length === 0) {
      if (emptyEl) emptyEl.style.display = '';
      if (listEl)  listEl.innerHTML = '';
      return;
    }
    if (emptyEl) emptyEl.style.display = 'none';

    const fmt2 = v => v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    listEl.innerHTML = entries.map((entry, idx) => {
      if (!entry.orderId) return ''; // skip legacy workbook entries
      const order = orderData[entry.orderId];
      if (!order) return '';
      const stats  = orderShipStats(entry.orderId);
      const orderHref = `#/order/${entry.orderId}`;

      const hasDims = stats.totalCartons > 0;
      const qtyStr     = stats.qty > 0 ? stats.qty.toLocaleString('en-US') : '—';
      const cartonStr  = hasDims ? stats.totalCartons.toLocaleString('en-US') : '—';
      const palletStr  = hasDims ? stats.palletsNeeded : '—';
      const cbmStr     = hasDims ? stats.totalCbm + ' m³' : '—';
      const weightStr  = hasDims ? stats.totalWeightKg.toLocaleString('en-US') + ' kg' : '—';
      const usdStr     = stats.totalUsd > 0 ? `$${fmt2(stats.totalUsd)}` : '—';
      const rmbStr     = stats.totalRmb > 0 ? `¥${fmt2(stats.totalRmb)}` : '';

      // Workbook pills
      const wbPills = (order.entries || []).map(wbEntry => {
        const key    = `${wbEntry.clientName}|${wbEntry.workbookId}`;
        const detail = workbookDetail[key] || {};
        const prod   = detail.product || wbEntry.workbookId;
        const href   = `#/client/${encodeURIComponent(wbEntry.clientName)}/workbook/${wbEntry.workbookId}`;
        return `<span class="soc-wb-pill" onclick="event.stopPropagation(); _wbBackHash='#/shipment/${_currentShipmentId}'; _wbBackLabel='Back to Shipment'; location.hash='${href}'">${prod} →</span>`;
      }).join('');

      const crBanner = order.changeRequested
        ? `<div class="soc-cr-banner">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
            <span>Change request pending — <strong>hold this order</strong></span>
            <a href="#/order/${entry.orderId}" onclick="event.stopPropagation(); location.hash='#/order/${entry.orderId}'">View Request →</a>
           </div>`
        : '';

      return `<div class="ship-order-card${order.changeRequested ? ' has-change-request' : ''}" onclick="location.hash='${orderHref}'">
        <div class="ship-order-card-header">
          <div>
            <div class="soc-client">${order.clientName}</div>
            <div class="soc-name">${order.name} &nbsp;·&nbsp; ${stats.wbCount} workbook${stats.wbCount !== 1 ? 's' : ''}</div>
          </div>
          <div style="display:flex; align-items:center; gap:10px;">
            <div class="soc-cost">
              <span class="soc-cost-usd">${usdStr}</span>
              ${rmbStr ? `<span class="soc-cost-rmb">${rmbStr}</span>` : ''}
            </div>
            <button class="ship-wb-remove" onclick="event.stopPropagation(); removeOrderFromShipment(${idx})" title="Remove">×</button>
          </div>
        </div>
        <div class="ship-order-card-body">
          <div class="soc-workbooks">
            <div class="soc-wb-label">Workbooks</div>
            ${wbPills || '<span style="font-size:12px;color:var(--text-muted);font-style:italic;">None</span>'}
          </div>
          <div class="soc-stats">
            <div class="soc-stat"><span class="soc-stat-val">${qtyStr}</span><span class="soc-stat-lbl">Units</span></div>
            <div class="soc-stat"><span class="soc-stat-val">${cartonStr}</span><span class="soc-stat-lbl">Cartons</span></div>
            <div class="soc-stat"><span class="soc-stat-val">${palletStr}</span><span class="soc-stat-lbl">Pallets</span></div>
            <div class="soc-stat"><span class="soc-stat-val">${cbmStr}</span><span class="soc-stat-lbl">CBM</span></div>
            <div class="soc-stat"><span class="soc-stat-val">${weightStr}</span><span class="soc-stat-lbl">Weight</span></div>
          </div>
        </div>
        ${crBanner}
      </div>`;
    }).join('');
  }

  function deleteShipment(id) {
    const s = shipmentData[id];
    if (!s) return;
    if (!confirm(`Delete "${s.name}"? This cannot be undone.`)) return;
    delete shipmentData[id];
    saveShipments();
    rebuildShipmentsNav();
    location.hash = '#/shipments';
  }

  function deleteOrder(id) {
    const o = orderData[id];
    if (!o) return;
    if (!confirm(`Delete "${o.name}"? This cannot be undone.`)) return;
    delete orderData[id];
    saveOrders();
    rebuildOrdersNav();
    location.hash = '#/orders';
  }

  function removeOrderFromShipment(idx) {
    const s = shipmentData[_currentShipmentId];
    if (!s) return;
    s.entries.splice(idx, 1);
    saveShipments();
    renderShipmentOrders();
    renderShipmentUtilization();
  }

  function renderShipmentUtilization() {
    const s    = shipmentData[_currentShipmentId];
    if (!s) return;
    const spec = CONTAINER_SPECS[s.containerType] || CONTAINER_SPECS['40hc'];
    const tot  = shipmentTotals(s);

    function setUtil(prefix, val, max, unit) {
      const rawPct = max > 0 ? (val / max) * 100 : 0;
      const isOver = rawPct > 100;
      const barPct = Math.min(100, rawPct);
      const cls = isOver ? 'danger' : rawPct >= 85 ? 'warn' : '';
      const cur = document.getElementById(`ship-util-${prefix}-cur`);
      const mx  = document.getElementById(`ship-util-${prefix}-max`);
      const bar = document.getElementById(`ship-util-${prefix}-bar`);
      const pctEl = document.getElementById(`ship-util-${prefix}-pct`);
      if (cur) {
        cur.textContent = val.toLocaleString('en-US');
        cur.style.color = isOver ? '#ef4444' : '';
      }
      if (mx)  mx.textContent  = `/ ${max.toLocaleString('en-US')}`;
      if (bar) { bar.style.width = barPct.toFixed(1) + '%'; bar.className = 'ship-util-fill' + (cls ? ' ' + cls : ''); }
      if (pctEl) {
        pctEl.textContent = rawPct.toFixed(0) + '% full';
        pctEl.style.color = isOver ? '#ef4444' : '';
        pctEl.style.fontWeight = isOver ? '700' : '';
      }
    }

    setUtil('cbm', tot.cbm,    spec.cbm,        'm³');
    setUtil('wt',  tot.kg,     spec.maxKg,       'kg');
    setUtil('pal', tot.pallets, spec.maxPallets,  '');
  }

  // ── Container type selector ──────────────────────────────────────────
  function setContainerType(type) {
    const s = shipmentData[_currentShipmentId];
    if (!s) return;
    s.containerType = type;
    saveShipments();
    document.querySelectorAll('.container-type-btn').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.type === type);
    });
    renderShipmentUtilization();
  }

  // ── Inline edits ─────────────────────────────────────────────────────
  function onShipmentNameChange() {
    const s = shipmentData[_currentShipmentId];
    if (!s) return;
    s.name = document.getElementById('ship-detail-name').value;
    saveShipments();
    rebuildShipmentsNav();
    document.getElementById('header-title').textContent = s.name;
  }
  function onShipmentStatusChange() {
    const s = shipmentData[_currentShipmentId];
    if (!s) return;
    s.status = document.getElementById('ship-detail-status').value;
    const wrap = document.getElementById('ship-delivered-wrap');
    if (wrap) wrap.style.display = s.status === 'delivered' ? 'flex' : 'none';
    saveShipments();
    rebuildShipmentsNav();
  }
  function onShipmentDateChange() {
    const s = shipmentData[_currentShipmentId];
    if (!s) return;
    s.etd         = document.getElementById('ship-detail-etd').value;
    s.eta         = document.getElementById('ship-detail-eta').value;
    s.deliveredOn = document.getElementById('ship-detail-delivered').value;
    saveShipments();
  }

  // ── Add Order to Shipment modal ──────────────────────────────────────
  function openAddOrderToShipmentModal() {
    _shipOrderPickerSelected = new Set();
    // Populate client filter dropdown
    const sel = document.getElementById('ship-order-picker-client');
    sel.innerHTML = '<option value="">All clients</option>';
    Object.keys(clientData).sort().forEach(name => {
      const opt = document.createElement('option');
      opt.value = name; opt.textContent = name;
      sel.appendChild(opt);
    });
    document.getElementById('ship-order-picker-search').value = '';
    buildShipOrderPickerList('');
    document.getElementById('modal-add-order-to-shipment').classList.add('open');
  }

  function closeAddOrderToShipmentModal() {
    document.getElementById('modal-add-order-to-shipment').classList.remove('open');
    _shipOrderPickerSelected = new Set();
  }

  function onShipOrderClientChange() {
    buildShipOrderPickerList(document.getElementById('ship-order-picker-search').value);
  }

  function buildShipOrderPickerList(query) {
    const list = document.getElementById('ship-order-picker-list');
    if (!list) return;
    const filterClient = document.getElementById('ship-order-picker-client').value;
    query = (query || '').toLowerCase();

    const s = shipmentData[_currentShipmentId];
    const alreadyIn = new Set((s ? s.entries : []).filter(e => e.orderId).map(e => String(e.orderId)));

    const fmt2 = v => v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const palette = ['#6b93ff','#f59e0b','#4ade80','#f472b6','#a78bfa','#34d399','#fb923c','#38bdf8'];
    const sortedClients = Object.keys(clientData).sort();

    // Group orders by client
    const clients = filterClient ? [filterClient] : sortedClients;

    let html = '';
    let totalShown = 0;

    clients.forEach(clientName => {
      const orders = Object.values(orderData).filter(o => o.clientName === clientName);
      const color    = palette[sortedClients.indexOf(clientName) % palette.length];
      const initials = clientName.substring(0, 3).toUpperCase();

      const matchedOrders = orders.filter(o =>
        !query || o.name.toLowerCase().includes(query) || clientName.toLowerCase().includes(query)
      );
      if (matchedOrders.length === 0) return;

      // Client group label (only when showing all clients)
      if (!filterClient) {
        html += `<div class="wb-picker-group-label">${clientName}</div>`;
      }

      matchedOrders.forEach(order => {
        totalShown++;
        const id        = String(order.id);
        const isChecked = _shipOrderPickerSelected.has(id);
        const inShip    = alreadyIn.has(id);
        const tot       = orderTotals(order);
        const usdStr    = tot.totalUsd > 0 ? `$${fmt2(tot.totalUsd)}` : '—';
        const rmbStr    = tot.totalRmb > 0 ? `¥${fmt2(tot.totalRmb)}` : '';
        const wbCount   = (order.entries || []).length;

        html += `<div class="wb-picker-item${isChecked ? ' selected' : ''}"
            style="${inShip ? 'opacity:0.4; pointer-events:none;' : ''}"
            onclick="toggleShipOrderPickerItem('${id}')">
          <div style="width:40px; height:40px; border-radius:8px; background:${color}22; border:1px solid ${color}44; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:800; color:${color}; flex-shrink:0; letter-spacing:0.02em;">
            ${initials}
          </div>
          <div class="wb-picker-info">
            <div class="wb-picker-product">${order.name}</div>
            <div class="wb-picker-client">${clientName} &nbsp;·&nbsp; ${wbCount} workbook${wbCount !== 1 ? 's' : ''}${inShip ? ' · Already in shipment' : ''}</div>
            <div class="wb-picker-tiers">${usdStr}${rmbStr ? ' &nbsp;·&nbsp; ' + rmbStr : ''}</div>
          </div>
          <div style="width:20px; height:20px; border-radius:4px; border:2px solid ${isChecked ? 'var(--accent)' : 'var(--border)'}; background:${isChecked ? 'var(--accent)' : 'transparent'}; display:flex; align-items:center; justify-content:center; flex-shrink:0; transition:all 0.15s;">
            ${isChecked ? '<svg width="11" height="9" viewBox="0 0 11 9" fill="none"><path d="M1 4L4 7.5L10 1" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' : ''}
          </div>
        </div>`;
      });
    });

    if (totalShown === 0) {
      list.innerHTML = `<div style="text-align:center; padding:30px; color:var(--text-muted); font-size:13px;">
        ${query ? 'No orders match your search.' : 'No orders found.'}
      </div>`;
      return;
    }
    list.innerHTML = html;
  }

  function filterShipOrderPicker(query) {
    buildShipOrderPickerList(query);
  }

  function toggleShipOrderPickerItem(id) {
    if (_shipOrderPickerSelected.has(id)) {
      _shipOrderPickerSelected.delete(id);
    } else {
      _shipOrderPickerSelected.add(id);
    }
    buildShipOrderPickerList(document.getElementById('ship-order-picker-search').value);
  }

  function confirmAddOrderToShipment() {
    if (_shipOrderPickerSelected.size === 0) { alert('Select at least one order.'); return; }
    const s = shipmentData[_currentShipmentId];
    if (!s) return;
    _shipOrderPickerSelected.forEach(id => {
      const orderId = parseInt(id);
      const alreadyIn = (s.entries || []).some(e => e.orderId === orderId);
      if (!alreadyIn) {
        s.entries = s.entries || [];
        s.entries.push({ orderId });
      }
    });
    saveShipments();
    closeAddOrderToShipmentModal();
    renderShipmentOrders();
    renderShipmentUtilization();
  }

  function buildWbPickerList(query) {
    const list = document.getElementById('wb-picker-list');
    if (!list) return;
    query = (query || '').toLowerCase();

    // Collect completed workbooks only
    const all = [];
    Object.entries(clientData).forEach(([clientName, wbs]) => {
      (wbs || []).forEach(wb => {
        // Only include completed workbooks
        if (!isFlowComplete(wb.flow || {})) return;
        const key    = `${clientName}|${wb.id}`;
        const detail = workbookDetail[key] || {};
        const product = detail.product || wb.product || '—';
        if (query && !product.toLowerCase().includes(query) && !clientName.toLowerCase().includes(query)) return;
        const tiers  = (detail.tiers || []);
        const tierStr = tiers.length > 0
          ? tiers.map(t => `${parseInt(t.qty || 0).toLocaleString('en-US')} units`).join(' · ')
          : 'No tiers set';
        all.push({ clientName, workbookId: wb.id, product, tierStr, detail, key: `${clientName}|${wb.id}` });
      });
    });

    if (all.length === 0) {
      list.innerHTML = `<div style="text-align:center; padding:30px; color:var(--text-muted); font-size:13px;">
        ${query ? 'No completed workbooks match your search.' : 'No completed workbooks found.'}
      </div>`;
      return;
    }

    // Client colour palette for avatars
    const palette = ['#6b93ff','#f59e0b','#4ade80','#f472b6','#a78bfa','#34d399','#fb923c','#38bdf8'];
    const clientColors = {};
    let colorIdx = 0;

    list.innerHTML = all.map((item) => {
      if (!clientColors[item.clientName]) {
        clientColors[item.clientName] = palette[colorIdx++ % palette.length];
      }
      const color      = clientColors[item.clientName];
      const isChecked  = _wbPickerSelected.has(item.key);
      // Client avatar: up to 3 chars of client name
      const initials   = item.clientName.substring(0, 3).toUpperCase();

      return `<div class="wb-picker-item${isChecked ? ' selected' : ''}"
          onclick="toggleWbPickerItem('${item.key.replace(/'/g,"\\'")}')">
        <div style="width:40px; height:40px; border-radius:8px; background:${color}22; border:1px solid ${color}44; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:800; color:${color}; flex-shrink:0; letter-spacing:0.02em;">
          ${initials}
        </div>
        <div class="wb-picker-info">
          <div class="wb-picker-product">${item.product}</div>
          <div class="wb-picker-client">${item.clientName}</div>
          <div class="wb-picker-tiers">${item.tierStr}</div>
        </div>
        <div style="width:20px; height:20px; border-radius:4px; border:2px solid ${isChecked ? 'var(--accent)' : 'var(--border)'}; background:${isChecked ? 'var(--accent)' : 'transparent'}; display:flex; align-items:center; justify-content:center; flex-shrink:0; transition:all 0.15s;">
          ${isChecked ? '<svg width="11" height="9" viewBox="0 0 11 9" fill="none"><path d="M1 4L4 7.5L10 1" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' : ''}
        </div>
      </div>`;
    }).join('');
  }

  function filterWbPicker(query) {
    buildWbPickerList(query);
  }

  function toggleWbPickerItem(key) {
    if (_wbPickerSelected.has(key)) {
      _wbPickerSelected.delete(key);
    } else {
      _wbPickerSelected.add(key);
    }
    buildWbPickerList(document.getElementById('wb-picker-search').value);
  }

  function confirmAddWorkbook() {
    if (_wbPickerSelected.size === 0) { alert('Select at least one workbook.'); return; }
    const s = shipmentData[_currentShipmentId];
    if (!s) return;
    _wbPickerSelected.forEach(key => {
      const [clientName, workbookId] = key.split('|');
      // Don't add duplicates
      const alreadyAdded = s.entries.some(e => e.clientName === clientName && String(e.workbookId) === String(workbookId));
      if (!alreadyAdded) {
        s.entries.push({ clientName, workbookId: parseInt(workbookId) || workbookId, qty: 1000 });
      }
    });
    saveShipments();
    closeAddOrderToShipmentModal();
    renderShipmentOrders();
    renderShipmentUtilization();
  }

  function removeWorkbookFromShipment(idx) {
    const s = shipmentData[_currentShipmentId];
    if (!s) return;
    s.entries.splice(idx, 1);
    saveShipments();
    renderShipmentOrders();
    renderShipmentUtilization();
  }

  // ── CSS for selected picker item ─────────────────────────────────────
  (function() {
    const style = document.createElement('style');
    style.textContent = '.wb-picker-item.selected { background: rgba(107,147,255,0.1); outline: 2px solid rgba(107,147,255,0.4); border-radius: var(--radius-sm); }';
    document.head.appendChild(style);
  })();

  // ══════════════════════════════════════════════════════════════════════
  // ORDERS MODULE
  // ══════════════════════════════════════════════════════════════════════

  // ── Persistence ──────────────────────────────────────────────────────
  function saveOrders() {
    try { localStorage.setItem('ms_orderData', JSON.stringify(orderData)); } catch(e) {}
    try { localStorage.setItem('ms_nextOrderId', String(_nextOrderId)); } catch(e) {}
    if (Object.keys(orderData).length > 0 || _nextOrderId > 1) {
      const payload = JSON.stringify({ data: orderData, nextId: _nextOrderId });
      _lastOrdersJson = payload; // prevent our own save from triggering a poll re-render
      apiCall('save_app_state', { key: 'ms_orders', value: payload }).catch(() => {});
    }
  }

  function loadOrders() {
    try {
      const d = localStorage.getItem('ms_orderData');
      const n = localStorage.getItem('ms_nextOrderId');
      if (d) { orderData = JSON.parse(d); }
      if (n) { _nextOrderId = Math.max(parseInt(n) || 1, ...Object.keys(orderData).map(Number).map(x => x + 1), 1); }
    } catch(e) { orderData = {}; _nextOrderId = 1; }
  }

  // ── Sync orders/shipments from DB (overwrites local if DB has data) ──
  async function syncOrdersFromDB() {
    try {
      const res = await apiCall('get_app_state', { key: 'ms_orders' });
      if (!res.success) return false;
      if (res.value) {
        _lastOrdersJson = res.value; // seed poll cache so first poll doesn't false-trigger
        const stored = JSON.parse(res.value);
        if (stored && stored.data && Object.keys(stored.data).length > 0) {
          orderData    = stored.data;
          _nextOrderId = stored.nextId || Math.max(1, ...Object.keys(stored.data).map(Number).map(x => x + 1));
          try { localStorage.setItem('ms_orderData',   JSON.stringify(orderData)); } catch(e) {}
          try { localStorage.setItem('ms_nextOrderId', String(_nextOrderId));       } catch(e) {}
          return true;
        }
      }
      // DB empty — push local data up so other users can see it
      if (Object.keys(orderData).length > 0) {
        const payload = JSON.stringify({ data: orderData, nextId: _nextOrderId });
        _lastOrdersJson = payload;
        await apiCall('save_app_state', { key: 'ms_orders', value: payload });
      }
    } catch(e) { console.warn('syncOrdersFromDB:', e); }
    return false;
  }

  async function syncShipmentsFromDB() {
    try {
      const res = await apiCall('get_app_state', { key: 'ms_shipments' });
      if (!res.success) return false;
      if (res.value) {
        _lastShipmentsJson = res.value; // seed poll cache
        const stored = JSON.parse(res.value);
        if (stored && stored.data && Object.keys(stored.data).length > 0) {
          shipmentData    = stored.data;
          _nextShipmentId = stored.nextId || Math.max(1, ...Object.keys(stored.data).map(Number).map(x => x + 1));
          try { localStorage.setItem('ms_shipmentData',   JSON.stringify(shipmentData)); } catch(e) {}
          try { localStorage.setItem('ms_nextShipmentId', String(_nextShipmentId));       } catch(e) {}
          return true;
        }
      }
      // DB empty — push local data up
      if (Object.keys(shipmentData).length > 0) {
        const payload = JSON.stringify({ data: shipmentData, nextId: _nextShipmentId });
        _lastShipmentsJson = payload;
        await apiCall('save_app_state', { key: 'ms_shipments', value: payload });
      }
    } catch(e) { console.warn('syncShipmentsFromDB:', e); }
    return false;
  }

  // ── Totals helper ────────────────────────────────────────────────────
  // ── Background poll — picks up changes made on another device/browser ──
  async function pollForRemoteChanges() {
    if (_pollActive) return; // don't overlap with a slow previous poll
    _pollActive = true;
    try {
      const h = location.hash;
      const onWorkbookEditor = h.startsWith('#/client/') && h.includes('/workbook/');
      const onOrdersView     = h.startsWith('#/order');
      const onShipmentsView  = h.startsWith('#/shipment');

      // ── 1. Orders & shipments — lightweight raw-string comparison ──────
      const [ordRes, shipRes] = await Promise.all([
        apiCall('get_app_state', { key: 'ms_orders' }),
        apiCall('get_app_state', { key: 'ms_shipments' }),
      ]);

      let ordChanged = false, shipChanged = false;

      if (ordRes.success && ordRes.value && ordRes.value !== _lastOrdersJson) {
        _lastOrdersJson = ordRes.value;
        const s = JSON.parse(ordRes.value);
        if (s?.data) {
          orderData    = s.data;
          _nextOrderId = s.nextId || _nextOrderId;
          try { localStorage.setItem('ms_orderData',   JSON.stringify(orderData)); } catch(e) {}
          try { localStorage.setItem('ms_nextOrderId', String(_nextOrderId));       } catch(e) {}
          rebuildOrdersNav();
          ordChanged = true;
        }
      }

      if (shipRes.success && shipRes.value && shipRes.value !== _lastShipmentsJson) {
        _lastShipmentsJson = shipRes.value;
        const s = JSON.parse(shipRes.value);
        if (s?.data) {
          shipmentData    = s.data;
          _nextShipmentId = s.nextId || _nextShipmentId;
          try { localStorage.setItem('ms_shipmentData',   JSON.stringify(shipmentData)); } catch(e) {}
          try { localStorage.setItem('ms_nextShipmentId', String(_nextShipmentId));       } catch(e) {}
          rebuildShipmentsNav();
          shipChanged = true;
        }
      }

      // Re-render only the relevant view if it's currently open
      if (ordChanged && onOrdersView)     router();
      if (shipChanged && onShipmentsView) router();

      // ── 2. Workbooks & clients — heavier call, skip if actively editing ─
      if (!_isDirty) {
        const preClient = currentClient;
        const preWbId   = currentWorkbookId;
        const dbLoaded  = await loadFromDatabase();
        // Only re-render when we actually got workbooks from the DB (true).
        // 'empty' = DB reachable but empty → no change to show;
        // false = API error → keep showing local state.
        if (dbLoaded === true) {
          rebuildSidebar();
          // Avoid re-rendering the workbook editor mid-session (would reset the form)
          if (!onWorkbookEditor) {
            router();
          }
        }
      }
    } catch(e) { /* silent — network blip shouldn't surface as an error */ }
    finally { _pollActive = false; }
  }

  // ── Real-time presence ────────────────────────────────────────────────────
  // Each user gets a random color per session, stored so it stays consistent
  // while the tab is open but varies across users/devices.
  const _PRESENCE_COLORS = [
    '#e74c3c','#3498db','#2ecc71','#f39c12',
    '#9b59b6','#1abc9c','#e67e22','#e91e63','#16a085','#8e44ad'
  ];
  let _myPresenceColor = sessionStorage.getItem('ms_presence_color');
  if (!_myPresenceColor) {
    _myPresenceColor = _PRESENCE_COLORS[Math.floor(Math.random() * _PRESENCE_COLORS.length)];
    sessionStorage.setItem('ms_presence_color', _myPresenceColor);
  }

  let _presenceWorkbookId = null;
  let _presenceInterval   = null;
  let _myFocusedField     = '';

  // Live cell-value sync state
  let _cellValuesSince    = '';   // server timestamp cursor for delta polling
  const _pendingPushes    = new Map(); // fieldPath -> debounce timer id

  function startPresenceHeartbeat(workbookId) {
    // Stop any previous heartbeat (switching workbooks)
    stopPresenceHeartbeat(/* silent */ true);
    _presenceWorkbookId = workbookId;
    _cellValuesSince    = '';   // reset cursor for new workbook
    _sendPresenceHeartbeat();
    _pollPresence();
    _pollCellValues();
    _presenceInterval = setInterval(() => {
      _sendPresenceHeartbeat();
      _pollPresence();
      _pollCellValues();
    }, 2000);  // 2 s — near real-time cursor tracking like Google Sheets
  }

  function stopPresenceHeartbeat(silent = false) {
    if (_presenceInterval) { clearInterval(_presenceInterval); _presenceInterval = null; }
    if (_presenceWorkbookId) {
      if (!silent) {
        // Best-effort instant clear via beacon so we vanish immediately
        try {
          navigator.sendBeacon(
            'api.php?action=clear_presence',
            new Blob([JSON.stringify({action:'clear_presence'})], {type:'application/json'})
          );
        } catch(e) {}
      }
      _presenceWorkbookId = null;
      _myFocusedField     = '';
      _cellValuesSince    = '';
      // Cancel any pending cell-value pushes
      _pendingPushes.forEach(t => clearTimeout(t));
      _pendingPushes.clear();
      _clearPresenceIndicators();
    }
  }

  async function _sendPresenceHeartbeat() {
    if (!_presenceWorkbookId) return;
    try {
      await apiCall('update_presence', {
        workbook_id:   _presenceWorkbookId,
        focused_field: _myFocusedField,
        color:         _myPresenceColor
      });
    } catch(e) {}
  }

  async function _pollPresence() {
    if (!_presenceWorkbookId) return;
    try {
      const res = await apiCall('get_presence', { workbook_id: _presenceWorkbookId });
      if (res?.success) _renderPresence(res.users || []);
    } catch(e) {}
  }

  // ── Live cell value sync ──────────────────────────────────────────────────
  // Use raw fetch for the high-frequency sync calls so we don't trigger
  // apiCall()'s saveToLocalStorage() on every keystroke and poll.
  async function _liveFetch(action, body) {
    try {
      const res = await fetch(`${API_URL}?action=${action}`, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body:    JSON.stringify(body)
      });
      if (!res.ok) return null;
      return await res.json();
    } catch(e) { return null; }
  }

  // Debounced per-field push: only one timer per fieldPath. Each new keystroke
  // resets that field's timer; once it fires we send only the latest value.
  // 250 ms feels live without hammering the DB.
  function _pushCellValue(fieldPath, value) {
    if (!_presenceWorkbookId || !fieldPath) return;
    const existing = _pendingPushes.get(fieldPath);
    if (existing) clearTimeout(existing);
    const timer = setTimeout(() => {
      _pendingPushes.delete(fieldPath);
      _liveFetch('push_cell_value', {
        workbook_id: _presenceWorkbookId,
        field_path:  fieldPath,
        value:       value
      });
    }, 250);
    _pendingPushes.set(fieldPath, timer);
  }

  // Pull every remote cell update since our cursor and apply it — but never
  // clobber an input that's currently focused locally (that would steal the
  // user's in-progress typing).
  async function _pollCellValues() {
    if (!_presenceWorkbookId) return;
    try {
      const res = await _liveFetch('pull_cell_values', {
        workbook_id: _presenceWorkbookId,
        since:       _cellValuesSince
      });
      if (!res?.success) return;
      if (res.server_now) _cellValuesSince = res.server_now;
      const updates = res.updates || [];
      if (updates.length === 0) return;

      const rfqBody = document.getElementById('rfq-body');
      if (!rfqBody) return;

      updates.forEach(u => {
        const sep = u.field_path.lastIndexOf(':');
        if (sep < 0) return;
        const rowId = u.field_path.slice(0, sep);
        const idx   = parseInt(u.field_path.slice(sep + 1), 10);
        const row   = document.getElementById(rowId);
        if (!row) return;
        const inputs = row.querySelectorAll('input');
        const input  = inputs[idx];
        if (!input) return;
        // Don't overwrite the cell the user is currently typing in
        if (document.activeElement === input) return;
        // Don't overwrite if value is already in sync (avoid cursor jumps)
        if (input.value === u.value) return;
        input.value = u.value;
        // Briefly flash the cell so the change is visible
        input.style.transition = 'background-color 0.6s ease';
        input.style.backgroundColor = 'rgba(232, 117, 26, 0.18)';
        setTimeout(() => { input.style.backgroundColor = ''; }, 600);
        // Notify any listeners (e.g. totals recalc) that this input changed
        input.dispatchEvent(new Event('input', { bubbles: true }));
      });
    } catch(e) {}
  }

  function _clearPresenceIndicators() {
    document.querySelectorAll('[data-presence]').forEach(el => {
      el.style.outline = '';
      delete el.dataset.presence;
    });
    document.querySelectorAll('.presence-name-tag').forEach(el => el.remove());
    const bar = document.getElementById('rfq-presence-bar');
    if (bar) bar.innerHTML = '';
  }

  function _renderPresence(users) {
    _clearPresenceIndicators();

    // ── Presence bar: avatar chips in the RFQ section header ────────────
    const bar = document.getElementById('rfq-presence-bar');
    if (bar && users.length > 0) {
      bar.innerHTML = users.map(u => `
        <span style="display:inline-flex;align-items:center;gap:5px;
                     background:${u.color}22;border:1px solid ${u.color}88;
                     border-radius:20px;padding:3px 10px 3px 7px;
                     font-size:11px;font-weight:700;color:${u.color};white-space:nowrap;">
          <span style="width:7px;height:7px;border-radius:50%;background:${u.color};
                       display:inline-block;
                       animation:presence-pulse 2s ease-in-out infinite;"></span>
          ${u.display_name}
        </span>`).join('');
    }

    // ── Cell highlights (Google Sheets style) ───────────────────────────
    users.forEach(u => {
      if (!u.focused_field) return;
      const sep   = u.focused_field.lastIndexOf(':');
      if (sep < 0) return;
      const rowId = u.focused_field.slice(0, sep);
      const idx   = parseInt(u.focused_field.slice(sep + 1), 10);
      const row   = document.getElementById(rowId);
      if (!row) return;
      const input = row.querySelectorAll('input')[idx];
      if (!input) return;

      // Thick colored border matching the user's color
      input.style.outline      = `3px solid ${u.color}`;
      input.style.outlineOffset = '-1px';
      input.dataset.presence   = '1';

      // Name tag: fixed to viewport, anchored BELOW the input.
      // Measure the real header bottom from the DOM each time — never use a
      // hardcoded constant, which breaks under zoom / different screen sizes.
      const rect = input.getBoundingClientRect();
      if (rect.width === 0) return;                           // not rendered yet

      const headerEl  = document.querySelector('.app-header');
      const headerBottom = headerEl ? headerEl.getBoundingClientRect().bottom : 64;
      const safeTop   = headerBottom + 6;   // 6 px gap so tag never kisses the header

      // Bail if the cell is in / above the header zone OR below the viewport
      if (rect.top < safeTop) return;
      if (rect.bottom > window.innerHeight) return;

      // Tag goes below the cell, clamped so it can't exceed the viewport bottom
      const tagTop = Math.min(rect.bottom + 3, window.innerHeight - 24);
      const tag = document.createElement('div');
      tag.className   = 'presence-name-tag';
      tag.textContent = u.display_name;
      tag.style.cssText = `
        background:${u.color};
        position:fixed;
        top:${tagTop}px;
        left:${rect.left}px;
        z-index:9999;
        pointer-events:none;
      `;
      document.body.appendChild(tag);
    });
  }

  // Attach focus/blur listeners to the RFQ table (event delegation, idempotent)
  function _initPresenceFocusTracking() {
    const rfqBody = document.getElementById('rfq-body');
    if (!rfqBody || rfqBody._presenceReady) return;
    rfqBody._presenceReady = true;

    rfqBody.addEventListener('focusin', e => {
      if (e.target.tagName !== 'INPUT') return;
      const row = e.target.closest('tr[id]');
      if (!row) return;
      const inputs = [...row.querySelectorAll('input')];
      const idx    = inputs.indexOf(e.target);
      if (idx < 0) return;
      _myFocusedField = `${row.id}:${idx}`;
      _sendPresenceHeartbeat();   // push field update immediately
    });

    rfqBody.addEventListener('focusout', () => {
      setTimeout(() => {
        if (!rfqBody.contains(document.activeElement)) {
          _myFocusedField = '';
          _sendPresenceHeartbeat();
        }
      }, 120);
    });

    // Live cell-value broadcast: every keystroke (debounced inside
    // _pushCellValue) sends the field's current value to the server so other
    // connected viewers see it within ~250 ms + their next 2 s poll.
    //   - Skip synthetic events (isTrusted === false): those come from
    //     _pollCellValues applying remote updates and would echo back.
    //   - Skip during workbook fill: programmatic population fires synthetic
    //     input events that we don't want to broadcast as new edits.
    rfqBody.addEventListener('input', e => {
      if (!e.isTrusted) return;
      if (typeof _filling !== 'undefined' && _filling) return;
      if (e.target.tagName !== 'INPUT') return;
      if (e.target.type === 'checkbox') return;
      const row = e.target.closest('tr[id]');
      if (!row) return;
      const inputs = [...row.querySelectorAll('input')];
      const idx    = inputs.indexOf(e.target);
      if (idx < 0) return;
      _pushCellValue(`${row.id}:${idx}`, e.target.value);
    });
  }

  // Clear presence immediately when the tab is closed / navigated away from
  window.addEventListener('beforeunload', () => {
    if (_presenceWorkbookId) {
      try {
        navigator.sendBeacon(
          'api.php?action=clear_presence',
          new Blob([JSON.stringify({action:'clear_presence'})], {type:'application/json'})
        );
      } catch(e) {}
    }
  });

  function orderTotals(order) {
    let totalUsd = 0, totalRmb = 0;
    (order.entries || []).forEach(e => {
      const key = `${e.clientName}|${e.workbookId}`;
      const detail = workbookDetail[key];
      if (!detail) return;
      const tiers = Array.isArray(detail.tiers) ? detail.tiers : [];
      const selectedIdx = detail.selectedTierIdx;
      const tier = tiers.find(t => t.id == selectedIdx) || tiers[0];
      if (tier && tier.price) {
        const priceRmb = parseFloat(tier.price) || 0;
        const qty = parseFloat(tier.qty) || 0;
        const exchange = 7.2;
        totalRmb += priceRmb * qty;
        totalUsd += (priceRmb / exchange) * qty;
      }
    });
    return { totalUsd: Math.round(totalUsd * 100) / 100, totalRmb: Math.round(totalRmb * 100) / 100 };
  }

  // ── Nav badge helper ─────────────────────────────────────────────────
  // actionable > 0 → orange pulsing badge with actionable count
  // actionable = 0, total > 0 → blue badge with total count
  // total = 0 → badge hidden
  function _applyNavBadge(badge, actionable, total) {
    if (!badge) return;
    // Clear prior content + attention class (leftover from single-badge era)
    badge.classList.remove('attention');
    const remaining = Math.max(0, (total || 0) - (actionable || 0));
    const parts = [];
    if (actionable > 0) parts.push(`<span class="nav-badge-pill attention">${actionable}</span>`);
    if (remaining > 0)  parts.push(`<span class="nav-badge-pill">${remaining}</span>`);
    badge.innerHTML = parts.join('');
  }

  // ── Nav ──────────────────────────────────────────────────────────────
  function rebuildOrdersNav() {
    const ids = Object.keys(orderData);
    // Actionable = client has requested changes on an order
    _applyNavBadge(
      document.getElementById('badge-orders'),
      ids.filter(id => orderData[id].changeRequested).length,
      ids.length
    );
  }

  // ── Portal change-request polling ────────────────────────────────────
  async function checkPortalChanges() {
    try {
      // Fetch every portal token currently in changes_requested state
      const pending = await apiCall('get_pending_changes');
      if (!Array.isArray(pending)) return;

      // Build lookup sets keyed by "clientName|orderName"
      const flaggedKeys  = new Set(pending.map(p => `${p.client_name}|${p.order_name}`));
      const tokenByKey   = {};
      pending.forEach(p => { tokenByKey[`${p.client_name}|${p.order_name}`] = p.token; });

      let changed = false;
      Object.keys(orderData).forEach(id => {
        const o   = orderData[id];
        const key = `${o.clientName}|${o.name}`;
        const shouldFlag = flaggedKeys.has(key);

        if (shouldFlag && !o.changeRequested) {
          o.changeRequested = true;
          if (!o.portalToken) o.portalToken = tokenByKey[key]; // backfill token for future use
          changed = true;
        } else if (!shouldFlag && o.changeRequested) {
          // No longer in the pending list → was approved / resolved
          o.changeRequested = false;
          changed = true;
        }
      });

      if (changed) {
        saveOrders();
        renderOrdersContent();
        rebuildOrdersNav();
        // Shipment & sample badges depend on linked orders' changeRequested state
        rebuildShipmentsNav();
        rebuildSamplesNav();
      }
    } catch(e) { /* silent — don't disrupt UI on poll failure */ }
  }

  // ── Create Order modal ───────────────────────────────────────────────
  function openNewOrderModal() {
    _orderPickerSelected = new Set();
    // Populate client dropdown
    const sel = document.getElementById('order-picker-client');
    sel.innerHTML = '<option value="">Select a client…</option>';
    Object.keys(clientData).sort().forEach(name => {
      const opt = document.createElement('option');
      opt.value = name;
      opt.textContent = name;
      sel.appendChild(opt);
    });
    // Hide workbook section until client chosen
    document.getElementById('order-picker-wb-section').style.display = 'none';
    document.getElementById('order-picker-search').value = '';
    document.getElementById('order-picker-list').innerHTML = '';
    document.getElementById('modal-new-order').classList.add('open');
  }

  function closeNewOrderModal() {
    document.getElementById('modal-new-order').classList.remove('open');
    _orderPickerSelected = new Set();
  }

  function onOrderPickerClientChange() {
    const client = document.getElementById('order-picker-client').value;
    const section = document.getElementById('order-picker-wb-section');
    if (!client) {
      section.style.display = 'none';
      _orderPickerSelected = new Set();
      return;
    }
    _orderPickerSelected = new Set();
    document.getElementById('order-picker-search').value = '';
    section.style.display = '';
    buildOrderPickerList('');
  }

  function buildOrderPickerList(query) {
    const list = document.getElementById('order-picker-list');
    if (!list) return;
    const clientName = document.getElementById('order-picker-client').value;
    if (!clientName) return;
    query = (query || '').toLowerCase();

    // Status groups: latest → earliest
    const statusGroups = [
      { key: 'complete',         label: 'Complete' },
      { key: 'orderChina',       label: 'Order Placed' },
      { key: 'confirmedPayment', label: 'Payment Confirmed' },
      { key: 'officeInvoice',    label: 'Office Invoice' },
      { key: 'clientApproved',   label: 'Client Approved' },
      { key: 'quoteClient',      label: 'Quote Sent to Client' },
      { key: 'quoteSubmitted',   label: 'Quote Submitted' },
      { key: 'quoteChina',       label: 'Quote from China' },
      { key: 'new',              label: 'New' },
    ];

    const palette = ['#6b93ff','#f59e0b','#4ade80','#f472b6','#a78bfa','#34d399','#fb923c','#38bdf8'];
    const color = palette[Object.keys(clientData).sort().indexOf(clientName) % palette.length];

    const buckets = {};
    statusGroups.forEach(g => { buckets[g.key] = []; });

    (clientData[clientName] || []).forEach(wb => {
      const key     = `${clientName}|${wb.id}`;
      const detail  = workbookDetail[key] || {};
      const product = detail.product || wb.product || '—';
      if (query && !product.toLowerCase().includes(query)) return;
      const tiers   = (detail.tiers || []);
      const tierStr = tiers.length > 0
        ? tiers.map(t => `${parseInt(t.qty || 0).toLocaleString('en-US')} units`).join(' · ')
        : 'No tiers set';
      const item = { clientName, workbookId: wb.id, product, tierStr, key };

      const flow = wb.flow || {};
      if (isFlowComplete(flow)) {
        buckets['complete'].push(item);
      } else {
        const lastStep = [...flowSteps].reverse().find(s => flow[s]);
        buckets[lastStep || 'new'].push(item);
      }
    });

    let html = '';
    let totalItems = 0;
    statusGroups.forEach(group => {
      const items = buckets[group.key];
      if (!items || items.length === 0) return;
      totalItems += items.length;
      html += `<div class="wb-picker-group-label">${group.label}</div>`;
      items.forEach(item => {
        const isChecked = _orderPickerSelected.has(item.key);
        const initials  = item.clientName.substring(0, 3).toUpperCase();
        html += `<div class="wb-picker-item${isChecked ? ' selected' : ''}"
            onclick="toggleOrderPickerItem('${item.key.replace(/'/g,"\\'")}')">
          <div style="width:40px; height:40px; border-radius:8px; background:${color}22; border:1px solid ${color}44; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:800; color:${color}; flex-shrink:0; letter-spacing:0.02em;">
            ${initials}
          </div>
          <div class="wb-picker-info">
            <div class="wb-picker-product">${item.product}</div>
            <div class="wb-picker-tiers">${item.tierStr}</div>
          </div>
          <div style="width:20px; height:20px; border-radius:4px; border:2px solid ${isChecked ? 'var(--accent)' : 'var(--border)'}; background:${isChecked ? 'var(--accent)' : 'transparent'}; display:flex; align-items:center; justify-content:center; flex-shrink:0; transition:all 0.15s;">
            ${isChecked ? '<svg width="11" height="9" viewBox="0 0 11 9" fill="none"><path d="M1 4L4 7.5L10 1" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' : ''}
          </div>
        </div>`;
      });
    });

    if (totalItems === 0) {
      list.innerHTML = `<div style="text-align:center; padding:30px; color:var(--text-muted); font-size:13px;">
        ${query ? 'No workbooks match your search.' : 'No workbooks found for this client.'}
      </div>`;
      return;
    }
    list.innerHTML = html;
  }

  function filterOrderPicker(query) {
    buildOrderPickerList(query);
  }

  function toggleOrderPickerItem(key) {
    if (_orderPickerSelected.has(key)) {
      _orderPickerSelected.delete(key);
    } else {
      _orderPickerSelected.add(key);
    }
    buildOrderPickerList(document.getElementById('order-picker-search').value);
  }

  function createOrder() {
    const clientName = document.getElementById('order-picker-client').value;
    if (!clientName) { alert('Please select a client.'); return; }
    if (_orderPickerSelected.size === 0) { alert('Select at least one workbook.'); return; }

    const entries = [];
    _orderPickerSelected.forEach(key => {
      const lastPipe   = key.lastIndexOf('|');
      const workbookId = key.substring(lastPipe + 1);
      entries.push({ clientName, workbookId });
    });

    const id  = _nextOrderId++;
    const num = String(id).padStart(3, '0');
    orderData[id] = {
      id,
      name: `Order #${num}`,
      clientName,
      status: 'draft',
      dateCreated: new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: '2-digit' }),
      poNumber: '',
      depositPct: 30,
      notes: '',
      entries,
    };
    saveOrders();
    rebuildOrdersNav();
    closeNewOrderModal();
    location.hash = `#/order/${id}`;
  }

  // ── List view ────────────────────────────────────────────────────────
  /* ── CSV Export ─────────────────────────────────────────────────────────── */
  function downloadCsv(filename, headers, rows) {
    const esc = v => {
      const s = (v == null ? '' : String(v));
      return (s.includes(',') || s.includes('"') || s.includes('\n'))
        ? '"' + s.replace(/"/g, '""') + '"' : s;
    };
    const csv = [headers, ...rows].map(r => r.map(esc).join(',')).join('\r\n');
    const a = Object.assign(document.createElement('a'), {
      href: URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8;' })),
      download: filename
    });
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
  }

  function buildOrderRows(o) {
    const rate = USD_TO_RMB || 7.24;
    const f2 = v => v > 0 ? parseFloat(v).toFixed(2) : '';
    const rows = [];
    const statusLabel = { draft:'Draft', confirmed:'Confirmed', in_production:'In Production', complete:'Complete' };
    (o.entries || []).forEach(entry => {
      const detail   = workbookDetail[`${entry.clientName}|${entry.workbookId}`] || {};
      const product  = detail.product || `Workbook #${entry.workbookId}`;
      const rfqItems = (detail.rfqItems || []).filter(i => i.item || i.qty || i.priceRmb);
      const base = [o.name || `Order #${o.id}`, o.dateCreated || '', o.clientName || '', o.poNumber || '', statusLabel[o.status] || o.status, product];
      if (!rfqItems.length) {
        rows.push([...base, '', '', '', '', '', '', '']);
      } else {
        rfqItems.forEach(item => {
          const rmb   = parseFloat(item.priceRmb) || 0;
          const qty   = parseFloat(item.qty)      || 0;
          const usd   = rmb / rate;
          rows.push([...base, item.item || '', item.sku || '', qty || '', f2(rmb), f2(usd), f2(rmb * qty), f2((rmb * qty) / rate)]);
        });
      }
    });
    return rows;
  }

  function exportOrderCsv(orderId) {
    const o = orderData[orderId != null ? orderId : _currentOrderId];
    if (!o) return;
    const headers = ['Order','Date','Client','PO #','Status','Product','Item','SKU','Qty','Unit Price (RMB)','Unit Price (USD)','Total (RMB)','Total (USD)'];
    const rows = buildOrderRows(o);
    // Totals row
    const totalUsd = rows.reduce((s, r) => s + (parseFloat(r[12]) || 0), 0);
    const totalRmb = rows.reduce((s, r) => s + (parseFloat(r[11]) || 0), 0);
    rows.push(['','','','','','','','','TOTAL','','',totalRmb.toFixed(2), totalUsd.toFixed(2)]);
    downloadCsv(`${(o.name || 'Order').replace(/[^a-z0-9]/gi,'-')}.csv`, headers, rows);
  }

  function exportAllOrdersCsv() {
    const orders = Object.values(orderData);
    if (!orders.length) { alert('No orders to export.'); return; }
    const headers = ['Order','Date','Client','PO #','Status','Product','Item','SKU','Qty','Unit Price (RMB)','Unit Price (USD)','Total (RMB)','Total (USD)'];
    const rows = orders.flatMap(o => buildOrderRows(o));
    const today = new Date().toISOString().slice(0,10);
    downloadCsv(`All-Orders-${today}.csv`, headers, rows);
  }

  function renderOrdersList() {
    document.getElementById('header-title').textContent = 'Orders';
    document.querySelectorAll('.sidebar-nav .nav-item').forEach(a => a.classList.remove('active'));
    document.querySelectorAll('.nav-flat-link').forEach(a => a.classList.remove('active'));
    const ordNav = document.getElementById('nav-orders-link');
    if (ordNav) ordNav.classList.add('active');
    showView('view-orders');
    renderOrdersContent();
  }

  function filterOrders(status, btn) {
    _orderFilter = status;
    document.querySelectorAll('.order-filter-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    renderOrdersContent();
  }

  function renderOrdersContent() {
    const el = document.getElementById('order-list-content');
    if (!el) return;

    const ids = Object.keys(orderData);
    if (ids.length === 0) {
      el.innerHTML = `<div class="order-list-empty">
        <div class="order-list-empty-icon">📋</div>
        <div class="order-list-empty-title">No orders yet</div>
        <div class="order-list-empty-sub">Create an order to track approved workbooks through production.</div>
      </div>`;
      return;
    }

    const STATUS_ORDER = ['draft', 'confirmed', 'in_production', 'complete'];
    const filterLabels = { all: 'All', attention: '⚑ Needs Attention', draft: 'Draft', confirmed: 'Confirmed', in_production: 'In Production', complete: 'Complete' };

    const counts = { all: ids.length, attention: ids.filter(id => orderData[id].changeRequested).length };
    ids.forEach(id => {
      const st = orderData[id].status;
      counts[st] = (counts[st] || 0) + 1;
    });

    const filterBar = `<div class="order-filter-bar">
      ${['all','attention','draft','confirmed','in_production','complete'].map(s => {
        const isAttention = s === 'attention';
        return `<button class="order-filter-btn${isAttention ? ' attention-btn' : ''}${_orderFilter === s ? ' active' : ''}" onclick="filterOrders('${s}', this)">
          ${filterLabels[s]}${counts[s] ? ` <span style="opacity:0.7">(${counts[s]})</span>` : ''}
        </button>`;
      }).join('')}
    </div>`;

    function buildOrderCard(id) {
      const o = orderData[id];
      const tot = orderTotals(o);
      const entries = o.entries || [];
      const wbCount = entries.length;

      const wbPills = wbCount === 0
        ? `<span style="font-size:12px;color:var(--text-muted);font-style:italic;">No workbooks</span>`
        : entries.map(e => {
            const key = `${e.clientName}|${e.workbookId}`;
            const detail = workbookDetail[key];
            const prod = detail ? (detail.product || 'Untitled') : 'Untitled';
            const href = `#/client/${encodeURIComponent(e.clientName)}/workbook/${e.workbookId}`;
            // Per-workbook pricing
            const tiers = detail && Array.isArray(detail.tiers) ? detail.tiers : [];
            const tier = tiers.find(t => t.id == detail.selectedTierIdx) || tiers[0];
            let wbRmb = '', wbUsd = '';
            if (tier && tier.price) {
              const priceRmb = parseFloat(tier.price) || 0;
              const qty = parseFloat(tier.qty) || 0;
              const exchange = 7.2;
              const totalRmb = priceRmb * qty;
              const totalUsd = (priceRmb / exchange) * qty;
              if (totalRmb > 0) wbRmb = `¥${totalRmb.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
              if (totalUsd > 0) wbUsd = `$${totalUsd.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            }
            const priceStr = (wbRmb || wbUsd)
              ? `<span class="oc-wb-prices">${[wbRmb, wbUsd].filter(Boolean).join('&nbsp;&nbsp;')}</span>`
              : '';
            return `<div class="oc-wb-row">
              <span class="oc-wb-pill" onclick="event.stopPropagation(); _wbBackHash='#/orders'; _wbBackLabel='Back to Orders'; location.hash='${href}'">${prod} <span style="opacity:0.75;">→</span></span>
              ${priceStr}
            </div>`;
          }).join('');

      const statusLabel = o.status.replace('_', ' ');
      const usdStr = tot.totalUsd > 0 ? `$${tot.totalUsd.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` : '—';
      const rmbStr = tot.totalRmb > 0 ? `¥${tot.totalRmb.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` : '';
      const changeBadge = o.changeRequested
        ? `<span class="oc-change-badge"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg> Change Requested</span>`
        : '';

      return `<div class="order-card${o.changeRequested ? ' has-change-request' : ''}" onclick="location.hash='#/order/${id}'">
        <div class="oc-left">
          <span class="oc-client">${o.clientName}</span>
          <span class="oc-title">${o.name}</span>
          <span class="oc-date">${o.dateCreated}</span>
          ${changeBadge}
        </div>
        <div class="oc-wb-list">
          <div class="oc-wb-count">${wbCount} workbook${wbCount !== 1 ? 's' : ''}</div>
          ${wbPills}
        </div>
        <div class="oc-right-wrap">
          <div class="oc-grand-total">
            <span class="oc-grand-label">Total</span>
            <span class="oc-grand-usd">${usdStr}</span>
            ${rmbStr ? `<span class="oc-grand-rmb">${rmbStr}</span>` : ''}
          </div>
        </div>
      </div>`;
    }

    const filtered = ids.filter(id => {
      if (_orderFilter === 'all')       return true;
      if (_orderFilter === 'attention') return !!orderData[id].changeRequested;
      return orderData[id].status === _orderFilter;
    });
    const active   = filtered.filter(id => orderData[id].status !== 'complete');
    const complete = filtered.filter(id => orderData[id].status === 'complete');

    active.sort((a, b) => {
      // Change-requested orders always float to the top
      const aCR = orderData[a].changeRequested ? 0 : 1;
      const bCR = orderData[b].changeRequested ? 0 : 1;
      if (aCR !== bCR) return aCR - bCR;
      return STATUS_ORDER.indexOf(orderData[a].status) - STATUS_ORDER.indexOf(orderData[b].status);
    });

    let html = filterBar;

    if (active.length > 0) {
      html += `<div class="order-cards">${active.map(buildOrderCard).join('')}</div>`;
    }
    if (complete.length > 0) {
      html += `<div class="sc-section-label" style="margin-top:${active.length > 0 ? '20px' : '0'}">Complete</div>
        <div class="order-cards" style="opacity:0.7">${complete.map(buildOrderCard).join('')}</div>`;
    }
    if (active.length === 0 && complete.length === 0) {
      html += `<div style="padding:30px 0; text-align:center; color:var(--text-muted); font-size:13px;">No orders match this filter.</div>`;
    }

    el.innerHTML = html;
  }

  // ── Detail view ──────────────────────────────────────────────────────
  function renderOrderDetail(id) {
    _currentOrderId = parseInt(id);
    const o = orderData[_currentOrderId];
    if (!o) { location.hash = '#/orders'; return; }

    document.getElementById('header-title').textContent = o.name;
    document.querySelectorAll('.sidebar-nav .nav-item').forEach(a => a.classList.remove('active'));
    document.querySelectorAll('.nav-flat-link').forEach(a => a.classList.remove('active'));
    const ordNav = document.getElementById('nav-orders-link');
    if (ordNav) ordNav.classList.add('active');

    document.getElementById('order-detail-client-name').textContent = o.clientName;
    document.getElementById('order-detail-name').value = o.name;
    document.getElementById('order-detail-po').value = o.poNumber || '';
    document.getElementById('order-detail-deposit-pct').value = o.depositPct != null ? o.depositPct : 30;
    document.getElementById('order-detail-notes').value = o.notes || '';
    document.getElementById('order-detail-date-tag').textContent = o.dateCreated;

    renderOrderSheet();
    renderOrderDepositTracking();
    showView('view-order-detail');

    // Show or hide the change-request panel
    const crCard = document.getElementById('order-cr-card');
    if (crCard) {
      if (o.changeRequested) {
        crCard.style.display = '';
        loadChangeRequestDetails(_currentOrderId);
      } else {
        crCard.style.display = 'none';
      }
    }
  }

  async function loadChangeRequestDetails(orderId) {
    const o = orderData[orderId];
    if (!o) return;
    const body = document.getElementById('cr-body');
    const meta = document.getElementById('cr-meta');
    if (!body) return;
    body.innerHTML = '<div style="color:var(--text-muted);font-size:13px;">Loading…</div>';

    const params = o.portalToken
      ? { token: o.portalToken }
      : { client_name: o.clientName, order_name: o.name };

    const res = await apiCall('get_change_request', params);
    if (!res.success) {
      body.innerHTML = '<div style="color:var(--text-muted);font-size:13px;">Could not load change request details.</div>';
      return;
    }

    // Header meta: client email + date
    if (meta) {
      const dateStr = res.submitted_at
        ? new Date(res.submitted_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })
        : '';
      meta.textContent = [res.client_email, dateStr].filter(Boolean).join(' · ');
    }

    let html = '';

    // Overall client comment
    if (res.client_comment && res.client_comment.trim()) {
      html += `<div class="cr-comment">
        <div class="cr-comment-label">Client Comment</div>
        <div class="cr-comment-text">${res.client_comment.trim().replace(/\n/g, '<br>')}</div>
      </div>`;
    }

    // Flagged line items
    const changes = res.line_changes || [];
    const items   = res.items        || [];
    if (changes.length > 0) {
      html += `<div class="cr-items-label">${changes.length} Item${changes.length !== 1 ? 's' : ''} Flagged</div>`;
      html += changes.map(ch => {
        const itm      = items[ch.idx] || {};
        const itemName = itm.item    || `Item #${ch.idx + 1}`;
        const product  = itm.product || '';
        const qty      = itm.qty     ? `Qty: ${Number(itm.qty).toLocaleString('en-US')}` : '';
        return `<div class="cr-item">
          <div class="cr-item-header">
            ${product ? `<span class="cr-item-product">${product}</span><span style="color:var(--border)">·</span>` : ''}
            <span class="cr-item-name">${itemName}</span>
            ${qty ? `<span style="font-size:11px;color:var(--text-muted);margin-left:auto;">${qty}</span>` : ''}
          </div>
          <div class="cr-item-note">${ch.note ? ch.note.replace(/\n/g, '<br>') : '<em style="opacity:0.6">No note provided</em>'}</div>
        </div>`;
      }).join('');
    }

    if (!html) {
      html = '<div style="color:var(--text-muted);font-size:13px;font-style:italic;">No details available.</div>';
    }

    body.innerHTML = html;
  }

  function renderOrderSheet() {
    const o = orderData[_currentOrderId];
    if (!o) return;
    const tbody = document.getElementById('order-sheet-tbody');
    const tfoot = document.getElementById('order-sheet-tfoot');
    const table = document.getElementById('order-sheet-table');
    const empty = document.getElementById('order-sheet-empty');
    const count = document.getElementById('order-wb-count');
    const entries = o.entries || [];

    if (count) count.textContent = entries.length > 0 ? `${entries.length} workbook${entries.length !== 1 ? 's' : ''}` : '';

    if (entries.length === 0) {
      if (table) table.style.display = 'none';
      if (empty) empty.style.display = '';
      if (tfoot) tfoot.innerHTML = '';
      return;
    }
    if (table) table.style.display = '';
    if (empty) empty.style.display = 'none';

    const exchange = 7.2;
    let grandUsd = 0, grandRmb = 0;
    const fmt2 = v => v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    tbody.innerHTML = entries.map((entry, idx) => {
      const key     = `${entry.clientName}|${entry.workbookId}`;
      const detail  = workbookDetail[key] || {};
      const product = detail.product || entry.workbookId;
      const wbHref  = `#/client/${encodeURIComponent(entry.clientName)}/workbook/${entry.workbookId}`;
      const rfqItems = (detail.rfqItems || []).filter(i => i.item || i.qty || i.priceRmb);
      const splits   = Array.isArray(detail.shipmentSplits) ? detail.shipmentSplits.filter(s => s.qty > 0) : [];
      const splitBadges = splits.map(s => {
        const label = { slow:'Slow Boat', fast:'Fast Boat', airupp:'Air + UPS', directair:'Direct Air' }[s.method] || s.method;
        return `<span class="split-badge">${s.qty.toLocaleString('en-US')} ${label}</span>`;
      }).join('');

      // Workbook header row
      let rows = `<tr class="order-sheet-wb-header">
        <td colspan="6">
          <a class="order-sheet-product-link" href="${wbHref}"
            onclick="_wbBackHash='#/order/${_currentOrderId}'; _wbBackLabel='Back to Order'; event.preventDefault(); location.hash='${wbHref.substring(1)}'">
            ${product} <span style="font-size:11px; opacity:0.5;">→</span>
          </a>
          ${splitBadges}
        </td>
        <td style="text-align:right;">
          <button class="order-sheet-remove" onclick="removeWorkbookFromOrder(${idx})" title="Remove">×</button>
        </td>
      </tr>`;

      if (rfqItems.length === 0) {
        rows += `<tr>
          <td colspan="7" style="padding:8px 12px 12px; color:var(--text-muted); font-size:12px; font-style:italic;">No line items</td>
        </tr>`;
      } else {
        rows += rfqItems.map(item => {
          const priceRmb   = parseFloat(item.priceRmb) || 0;
          const qty        = parseFloat(item.qty) || 0;
          const subtotalRmb = priceRmb * qty;
          const subtotalUsd = subtotalRmb / exchange;
          grandRmb += subtotalRmb;
          grandUsd += subtotalUsd;

          const qtyStr      = qty      > 0 ? qty.toLocaleString('en-US') : '—';
          const unitRmbStr  = priceRmb > 0 ? `¥${fmt2(priceRmb)}`           : '—';
          const unitUsdStr  = priceRmb > 0 ? `$${fmt2(priceRmb / exchange)}` : '—';
          const totRmbStr   = subtotalRmb > 0 ? `¥${fmt2(subtotalRmb)}` : '—';
          const totUsdStr   = subtotalUsd > 0 ? `$${fmt2(subtotalUsd)}` : '—';

          return `<tr class="order-sheet-line-row">
            <td style="padding-left:24px;">${item.item || '—'}</td>
            <td style="text-align:right;">${qtyStr}</td>
            <td style="text-align:right; color:var(--text-muted);">${unitRmbStr}</td>
            <td style="text-align:right; color:var(--text-muted);">${unitUsdStr}</td>
            <td style="text-align:right; font-weight:600;">${totRmbStr}</td>
            <td style="text-align:right; font-weight:600;">${totUsdStr}</td>
            <td></td>
          </tr>`;
        }).join('');
      }

      return rows;
    }).join('');

    // Grand total footer
    const grandUsdStr = grandUsd > 0 ? `$${fmt2(grandUsd)}` : '—';
    const grandRmbStr = grandRmb > 0 ? `¥${fmt2(grandRmb)}` : '—';
    tfoot.innerHTML = `<tr>
      <td colspan="4" style="font-weight:700; font-size:12px; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted);">Grand Total</td>
      <td style="text-align:right; font-weight:700; color:var(--text-muted);">${grandRmbStr}</td>
      <td style="text-align:right; font-size:15px; font-weight:800;">${grandUsdStr}</td>
      <td></td>
    </tr>`;
  }

  function renderOrderDepositTracking() {
    const o = orderData[_currentOrderId];
    if (!o) return;
    const container = document.getElementById('order-deposit-rows');
    if (!container) return;

    const tot = orderTotals(o);
    const pct = o.depositPct != null ? o.depositPct : 30;
    const depositAmt = Math.round(tot.totalUsd * (pct / 100) * 100) / 100;
    const balanceAmt = Math.round((tot.totalUsd - depositAmt) * 100) / 100;

    const depositPaid = o.depositPaid || false;
    const balancePaid = o.balancePaid || false;

    const fmtUsd = v => v > 0 ? `$${v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` : '$0.00';

    container.innerHTML = `
      <div class="order-deposit-row">
        <span class="order-deposit-label">${pct}% Deposit</span>
        <span class="order-deposit-amount">${fmtUsd(depositAmt)}</span>
        <label class="order-deposit-check">
          <input type="checkbox" ${depositPaid ? 'checked' : ''} onchange="onOrderDepositPaidChange('deposit', this.checked)" />
          Paid
        </label>
      </div>
      <div class="order-deposit-row">
        <span class="order-deposit-label">${100 - pct}% Balance</span>
        <span class="order-deposit-amount">${fmtUsd(balanceAmt)}</span>
        <label class="order-deposit-check">
          <input type="checkbox" ${balancePaid ? 'checked' : ''} onchange="onOrderDepositPaidChange('balance', this.checked)" />
          Paid
        </label>
      </div>`;
  }

  // ── Add Workbook to Order modal ──────────────────────────────────────
  function openAddWorkbookToOrderModal() {
    const o = orderData[_currentOrderId];
    if (!o) return;
    _orderAddPickerSelected = new Set();
    document.getElementById('order-add-wb-search').value = '';
    buildOrderAddPickerList('');
    document.getElementById('modal-add-wb-to-order').classList.add('open');
  }

  function closeAddWorkbookToOrderModal() {
    document.getElementById('modal-add-wb-to-order').classList.remove('open');
    _orderAddPickerSelected = new Set();
  }

  function buildOrderAddPickerList(query) {
    const list = document.getElementById('order-add-wb-list');
    if (!list) return;
    const o = orderData[_currentOrderId];
    if (!o) return;
    query = (query || '').toLowerCase();

    // Determine which clients to show: single client or all if "Multiple Clients"
    const isMulti = o.clientName === 'Multiple Clients';
    const allowedClients = isMulti
      ? Object.keys(clientData)
      : [o.clientName];

    // Status groups: latest → earliest
    const statusGroups = [
      { key: 'complete',         label: 'Complete' },
      { key: 'orderChina',       label: 'Order Placed' },
      { key: 'confirmedPayment', label: 'Payment Confirmed' },
      { key: 'officeInvoice',    label: 'Office Invoice' },
      { key: 'clientApproved',   label: 'Client Approved' },
      { key: 'quoteClient',      label: 'Quote Sent to Client' },
      { key: 'quoteSubmitted',   label: 'Quote Submitted' },
      { key: 'quoteChina',       label: 'Quote from China' },
      { key: 'new',              label: 'New' },
    ];

    const palette = ['#6b93ff','#f59e0b','#4ade80','#f472b6','#a78bfa','#34d399','#fb923c','#38bdf8'];
    const clientColors = {};
    let colorIdx = 0;
    function getClientColor(name) {
      if (!clientColors[name]) clientColors[name] = palette[colorIdx++ % palette.length];
      return clientColors[name];
    }

    // Already in this order (by key)
    const existingKeys = new Set((o.entries || []).map(e => `${e.clientName}|${e.workbookId}`));

    const buckets = {};
    statusGroups.forEach(g => { buckets[g.key] = []; });

    allowedClients.forEach(clientName => {
      (clientData[clientName] || []).forEach(wb => {
        const key     = `${clientName}|${wb.id}`;
        if (existingKeys.has(key)) return; // skip already-added
        const detail  = workbookDetail[key] || {};
        const product = detail.product || wb.product || '—';
        if (query && !product.toLowerCase().includes(query) && !clientName.toLowerCase().includes(query)) return;
        const tiers   = (detail.tiers || []);
        const tierStr = tiers.length > 0
          ? tiers.map(t => `${parseInt(t.qty || 0).toLocaleString('en-US')} units`).join(' · ')
          : 'No tiers set';
        const item = { clientName, workbookId: wb.id, product, tierStr, key };

        const flow = wb.flow || {};
        if (isFlowComplete(flow)) {
          buckets['complete'].push(item);
        } else {
          const lastStep = [...flowSteps].reverse().find(s => flow[s]);
          buckets[lastStep || 'new'].push(item);
        }
      });
    });

    let html = '';
    let totalItems = 0;
    statusGroups.forEach(group => {
      const items = buckets[group.key];
      if (!items || items.length === 0) return;
      totalItems += items.length;
      html += `<div class="wb-picker-group-label">${group.label}</div>`;
      items.forEach(item => {
        const color     = getClientColor(item.clientName);
        const isChecked = _orderAddPickerSelected.has(item.key);
        const initials  = item.clientName.substring(0, 3).toUpperCase();
        html += `<div class="wb-picker-item${isChecked ? ' selected' : ''}"
            onclick="toggleOrderAddPickerItem('${item.key.replace(/'/g,"\\'")}')">
          <div style="width:40px; height:40px; border-radius:8px; background:${color}22; border:1px solid ${color}44; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:800; color:${color}; flex-shrink:0; letter-spacing:0.02em;">
            ${initials}
          </div>
          <div class="wb-picker-info">
            <div class="wb-picker-product">${item.product}</div>
            <div class="wb-picker-client">${item.clientName}</div>
            <div class="wb-picker-tiers">${item.tierStr}</div>
          </div>
          <div style="width:20px; height:20px; border-radius:4px; border:2px solid ${isChecked ? 'var(--accent)' : 'var(--border)'}; background:${isChecked ? 'var(--accent)' : 'transparent'}; display:flex; align-items:center; justify-content:center; flex-shrink:0; transition:all 0.15s;">
            ${isChecked ? '<svg width="11" height="9" viewBox="0 0 11 9" fill="none"><path d="M1 4L4 7.5L10 1" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' : ''}
          </div>
        </div>`;
      });
    });

    if (totalItems === 0) {
      list.innerHTML = `<div style="text-align:center; padding:30px; color:var(--text-muted); font-size:13px;">
        ${query ? 'No workbooks match your search.' : 'All workbooks are already in this order.'}
      </div>`;
      return;
    }
    list.innerHTML = html;
  }

  function filterOrderAddPicker(query) {
    buildOrderAddPickerList(query);
  }

  function toggleOrderAddPickerItem(key) {
    if (_orderAddPickerSelected.has(key)) {
      _orderAddPickerSelected.delete(key);
    } else {
      _orderAddPickerSelected.add(key);
    }
    buildOrderAddPickerList(document.getElementById('order-add-wb-search').value);
  }

  function confirmAddWorkbookToOrder() {
    if (_orderAddPickerSelected.size === 0) { alert('Select at least one workbook.'); return; }
    const o = orderData[_currentOrderId];
    if (!o) return;
    _orderAddPickerSelected.forEach(key => {
      const lastPipe   = key.lastIndexOf('|');
      const clientName = key.substring(0, lastPipe);
      const workbookId = key.substring(lastPipe + 1);
      // Don't add duplicates
      const alreadyIn = (o.entries || []).some(e => e.clientName === clientName && e.workbookId == workbookId);
      if (!alreadyIn) {
        o.entries = o.entries || [];
        o.entries.push({ clientName, workbookId });
      }
    });
    // If order was single-client but now has multiple, update clientName
    const clients = [...new Set((o.entries || []).map(e => e.clientName))];
    if (clients.length > 1) o.clientName = 'Multiple Clients';
    saveOrders();
    closeAddWorkbookToOrderModal();
    renderOrderSheet();
    renderOrderDepositTracking();
    document.getElementById('order-detail-client-name').textContent = o.clientName;
  }

  function removeWorkbookFromOrder(idx) {
    const o = orderData[_currentOrderId];
    if (!o) return;
    o.entries.splice(idx, 1);
    saveOrders();
    renderOrderSheet();
    renderOrderDepositTracking();
  }

  // ── Inline edits ─────────────────────────────────────────────────────
  function onOrderNameChange() {
    const o = orderData[_currentOrderId];
    if (!o) return;
    o.name = document.getElementById('order-detail-name').value;
    saveOrders();
    rebuildOrdersNav();
    document.getElementById('header-title').textContent = o.name;
  }

  function onOrderStatusChange() {
    const o = orderData[_currentOrderId];
    if (!o) return;
    o.status = document.getElementById('order-detail-status').value;
    saveOrders();
    rebuildOrdersNav();
  }

  function onOrderPoChange() {
    const o = orderData[_currentOrderId];
    if (!o) return;
    o.poNumber = document.getElementById('order-detail-po').value;
    saveOrders();
  }

  function onOrderDepositPctChange() {
    const o = orderData[_currentOrderId];
    if (!o) return;
    const val = parseInt(document.getElementById('order-detail-deposit-pct').value) || 30;
    o.depositPct = Math.min(100, Math.max(0, val));
    saveOrders();
    renderOrderDepositTracking();
  }

  function onOrderNotesChange() {
    const o = orderData[_currentOrderId];
    if (!o) return;
    o.notes = document.getElementById('order-detail-notes').value;
    saveOrders();
  }

  function onOrderDepositPaidChange(type, checked) {
    const o = orderData[_currentOrderId];
    if (!o) return;
    if (type === 'deposit') o.depositPaid = checked;
    if (type === 'balance') o.balancePaid = checked;
    saveOrders();
  }

</script>
</body>
</html>
