<?php
require_once __DIR__ . '/auth.php';
requireAuth();
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
      font-size: 16px;
      color: var(--text-muted);
      transition: transform 0.2s ease;
      display: inline-block;
      flex-shrink: 0;
      line-height: 1;
      transform: rotate(90deg);
      opacity: 0.7;
    }
    .nav-section.collapsed .nav-section-chevron { transform: rotate(0deg); }
    .nav-section.collapsed .nav-section-body { display: none; }
    .nav-section-body { display: flex; flex-direction: column; gap: 1px; padding: 0 8px 6px; }

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

    /* Delete button (shown when active) */
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
    .nav-item.active .client-delete-btn { display: inline-flex; }
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
    .secondary-select-wrap { margin-top: 8px; opacity: 0.38; transition: opacity 0.2s; }
    .secondary-select-wrap.has-value { opacity: 1; }
    .secondary-select-label {
      font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em;
      color: var(--text-muted); margin-bottom: 4px;
    }

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

    #rfq-table td {
      padding: 14px 12px;
    }

    #rfq-table input {
      padding: 10px 14px;
    }

    #rfq-table tfoot td {
      padding: 16px 12px;
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
      gap: 24px;
    }
    .specs-col {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }
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
      padding: 14px 16px;
      font-size: 13px;
      font-weight: 600;
      color: var(--text-muted);
      border-bottom: 1px solid var(--border);
    }

    .dash-table th:nth-child(1) { width: 20%; }
    .dash-table th:nth-child(2) { width: 10%; }
    .dash-table th:nth-child(3) { width: 10%; }
    .dash-table th:nth-child(4) { width: auto; }
    .dash-table th:nth-child(6) { width: 60px; }

    .dash-table td {
      padding: 18px 16px;
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

    .dash-table .product-name {
      font-weight: 600;
      color: var(--product-name-color, var(--text));
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

    /* ── Workbook Tabs ──────────────────────────────────────────────────── */
    .wb-tabs {
      display: flex;
      gap: 4px;
      align-items: center;
    }

    .wb-tab {
      padding: 8px 20px;
      font-size: 13px;
      font-weight: 500;
      color: var(--text-muted);
      background: transparent;
      border: none;
      border-radius: 20px;
      cursor: pointer;
      transition: all 0.15s;
      white-space: nowrap;
    }

    .wb-tab.active {
      background: var(--accent);
      color: #fff;
      font-weight: 500;
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

    .freight-row-3 {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
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
    }

    .freight-field select {
      font-family: inherit;
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
  </style>
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

  <nav class="sidebar-nav" id="sidebar-nav">

    <!-- ★ Starred -->
    <div class="nav-section" id="nav-section-starred">
      <div class="nav-section-header" onclick="toggleNavSection('nav-section-starred')">
        <span>★ Starred</span>
        <span class="nav-section-chevron">›</span>
      </div>
      <div class="nav-section-body" id="starred-list"></div>
    </div>

    <!-- Clients -->
    <div class="nav-section" id="nav-section-clients">
      <div class="nav-section-header" onclick="toggleNavSection('nav-section-clients')">
        <span>Clients</span>
        <span class="nav-section-chevron">›</span>
      </div>
      <div class="nav-section-body" id="client-list"></div>
    </div>

    <!-- Orders -->
    <div class="nav-section collapsed" id="nav-section-orders">
      <div class="nav-section-header" onclick="toggleNavSection('nav-section-orders')">
        <span>Orders</span>
        <span class="nav-section-chevron">›</span>
      </div>
      <div class="nav-section-body">
        <div class="nav-placeholder">Coming soon…</div>
      </div>
    </div>

    <!-- Samples -->
    <div class="nav-section collapsed" id="nav-section-samples">
      <div class="nav-section-header" onclick="toggleNavSection('nav-section-samples')">
        <span>Samples</span>
        <span class="nav-section-chevron">›</span>
      </div>
      <div class="nav-section-body">
        <div class="nav-placeholder">Coming soon…</div>
      </div>
    </div>

    <!-- Containers -->
    <div class="nav-section collapsed" id="nav-section-containers">
      <div class="nav-section-header" onclick="toggleNavSection('nav-section-containers')">
        <span>Containers</span>
        <span class="nav-section-chevron">›</span>
      </div>
      <div class="nav-section-body">
        <div class="nav-placeholder">Coming soon…</div>
      </div>
    </div>

  </nav>

  <div class="sidebar-bottom" style="padding: 8px;">
    <button class="nav-item" onclick="openArchiveModal()" style="width:100%;">
      <span style="font-size:14px; opacity:0.7;">&#128451;</span>
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
    <button class="btn btn-ghost" onclick="openHistoryModal()" title="Revision History">🕘<span class="btn-label"> History</span></button>
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
        <button class="user-dropdown-item" id="theme-dropdown-btn" onclick="toggleTheme()">☀️ Light Mode</button>
        <button class="user-dropdown-item" onclick="window.print(); closeUserDropdown()">🖨️ Print / Export</button>
        <button class="user-dropdown-item" onclick="openChangePasswordModal(); closeUserDropdown()">🔑 Change Password</button>
        <?php if ($_msRole === 'admin'): ?>
        <button class="user-dropdown-item" onclick="openUsersModal(); closeUserDropdown()">👥 Manage Users</button>
        <?php endif; ?>
        <hr class="user-dropdown-divider">
        <a class="user-dropdown-item danger" href="logout.php">⏻ Log Out</a>
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
    <div class="section-card">
      <div class="section-body" style="padding:0;">
        <div class="table-scroll-wrapper">
        <table class="dash-table" id="dash-table">
          <thead>
            <tr>
              <th class="sortable" onclick="sortClientTable('product')">Product <span class="sort-arrow"></span></th>
              <th class="col-date-created sortable" onclick="sortClientTable('date')">Date Created <span class="sort-arrow"></span></th>
              <th class="col-date-submitted sortable" onclick="sortClientTable('dateSubmitted')">Date Submitted <span class="sort-arrow"></span></th>
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
          <button class="btn-create" onclick="openNewWorkbookModal()" style="font-size:14px;">+ New Workbook</button>
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
    <div style="display:flex; justify-content:space-between; align-items:center;">
      <button class="btn-back" id="btn-back" onclick="history.back()" style="margin-bottom:0;">← Back to Workbooks</button>
      <div class="wb-tabs">
        <button class="wb-tab active" onclick="switchWbTab('workbook', this)"><span class="tab-full">Workbook</span><span class="tab-short">Work</span></button>
        <button class="wb-tab" onclick="switchWbTab('shipping', this)"><span class="tab-full">Shipping</span><span class="tab-short">Ship</span></button>
        <button class="wb-tab" onclick="switchWbTab('pricing', this)"><span class="tab-full">Pricing</span><span class="tab-short">Price</span></button>
        <button class="wb-tab" onclick="switchWbTab('quote', this)"><span class="tab-full">Quote for Client</span><span class="tab-short">Quote</span></button>
        <button class="wb-tab" onclick="switchWbTab('art', this)"><span class="tab-full">Art</span><span class="tab-short">Art</span></button>
        <button class="wb-tab" onclick="switchWbTab('invoice', this)"><span class="tab-full">Office Invoice</span><span class="tab-short">Invoice</span></button>
      </div>
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
      <span class="section-chevron">›</span>
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
                <select id="product-category-2" onchange="onSecondaryChange('cat2-wrap', this); updateSubcategories();">
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
              <select id="product-subcategory">
                <option value="">Select category first...</option>
              </select>
            </div>
            <div class="secondary-select-wrap" id="mat2-wrap">
              <div class="secondary-select-label">Secondary Material</div>
              <div class="select-wrapper">
                <select id="product-subcategory-2" onchange="onSecondaryChange('mat2-wrap', this)">
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
      <span class="section-chevron">›</span>
    </div>
    <div class="section-body">
      <div class="subsection-title" style="margin-top:0;">Quote Details</div>
      <p style="color:var(--text-muted); font-size:13px; margin-bottom:12px;">Add line items for each product/component in this quote.</p>
      <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
      <table class="tier-table" id="rfq-table" style="min-width:700px; border-collapse:collapse;">
        <colgroup>
          <col style="width:40px;">
          <col style="width:22%;">
          <col style="width:10%;">
          <col style="width:16%;">
          <col style="width:14%;">
          <col style="width:16%;">
          <col style="width:12%;">
          <col style="width:36px;">
        </colgroup>
        <thead>
          <tr>
            <th>#</th>
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
            <td style="padding:4px 12px; border-bottom:none;">
              <button class="btn btn-add" style="width:100%; margin:4px 0;" onclick="addRfqRow()">+ Add Line Item</button>
            </td>
            <td colspan="6" style="padding:0; border-bottom:none;"></td>
          </tr>
          <tr style="background:var(--surface2);">
            <th style="padding:10px 14px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-muted); border-bottom:1px solid var(--border);">#</th>
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
            <div class="specs-input-wrap"><input type="number" step="0.01" min="0" placeholder="—" id="dim-cm-l" oninput="convertDim('dim-cm-l','dim-in-l','cm')" /><span class="specs-unit-tag">cm</span></div>
            <div class="specs-input-wrap"><input type="number" step="0.01" min="0" placeholder="—" id="dim-in-l" oninput="convertDim('dim-in-l','dim-cm-l','in')" /><span class="specs-unit-tag">in</span></div>
            <div class="specs-row-label">Width</div>
            <div class="specs-input-wrap"><input type="number" step="0.01" min="0" placeholder="—" id="dim-cm-w" oninput="convertDim('dim-cm-w','dim-in-w','cm')" /><span class="specs-unit-tag">cm</span></div>
            <div class="specs-input-wrap"><input type="number" step="0.01" min="0" placeholder="—" id="dim-in-w" oninput="convertDim('dim-in-w','dim-cm-w','in')" /><span class="specs-unit-tag">in</span></div>
            <div class="specs-row-label">Height</div>
            <div class="specs-input-wrap"><input type="number" step="0.01" min="0" placeholder="—" id="dim-cm-h" oninput="convertDim('dim-cm-h','dim-in-h','cm')" /><span class="specs-unit-tag">cm</span></div>
            <div class="specs-input-wrap"><input type="number" step="0.01" min="0" placeholder="—" id="dim-in-h" oninput="convertDim('dim-in-h','dim-cm-h','in')" /><span class="specs-unit-tag">in</span></div>
            <hr class="specs-dim-divider" />
            <div></div>
            <div class="specs-unit-header">kg</div>
            <div class="specs-unit-header">lb</div>
            <div class="specs-row-label">Weight</div>
            <div class="specs-input-wrap"><input type="number" step="0.001" min="0" placeholder="—" id="dim-weight-kg" oninput="convertWeight('dim-weight-kg','dim-weight-lbs','kg')" /><span class="specs-unit-tag">kg</span></div>
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
          <div class="specs-col-title">Inner Carton</div>
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
            <div class="specs-input-wrap"><input type="number" step="0.001" min="0" placeholder="—" id="carton-inner-weight" oninput="convertWeight('carton-inner-weight','carton-inner-weight-lbs','kg')" /><span class="specs-unit-tag">kg</span></div>
            <div class="specs-input-wrap"><input type="text" placeholder="—" id="carton-inner-weight-lbs" oninput="convertWeight('carton-inner-weight-lbs','carton-inner-weight','lbs')" /><span class="specs-unit-tag">lb</span></div>
            <div class="specs-full-row" style="margin-top:6px;">
              <div class="specs-row-label" style="margin-bottom:5px;">Qty <span style="font-weight:400; text-transform:none; font-size:11px;">(units / carton)</span></div>
              <input type="number" min="0" placeholder="e.g. 10" id="carton-inner-count" style="width:100%;" />
            </div>
          </div>
        </div>

        <!-- Column 3: Outer Carton -->
        <div class="specs-col">
          <div class="specs-col-title">Outer Carton</div>
          <div class="specs-dim-grid">
            <div></div>
            <div class="specs-unit-header">cm</div>
            <div class="specs-unit-header">in</div>
            <div class="specs-row-label">Length</div>
            <div class="specs-input-wrap"><input type="number" step="0.01" min="0" placeholder="—" id="carton-outer-l-cm" oninput="convertDim('carton-outer-l-cm','carton-outer-l-in','cm')" /><span class="specs-unit-tag">cm</span></div>
            <div class="specs-input-wrap"><input type="number" step="0.01" min="0" placeholder="—" id="carton-outer-l-in" oninput="convertDim('carton-outer-l-in','carton-outer-l-cm','in')" /><span class="specs-unit-tag">in</span></div>
            <div class="specs-row-label">Width</div>
            <div class="specs-input-wrap"><input type="number" step="0.01" min="0" placeholder="—" id="carton-outer-w-cm" oninput="convertDim('carton-outer-w-cm','carton-outer-w-in','cm')" /><span class="specs-unit-tag">cm</span></div>
            <div class="specs-input-wrap"><input type="number" step="0.01" min="0" placeholder="—" id="carton-outer-w-in" oninput="convertDim('carton-outer-w-in','carton-outer-w-cm','in')" /><span class="specs-unit-tag">in</span></div>
            <div class="specs-row-label">Height</div>
            <div class="specs-input-wrap"><input type="number" step="0.01" min="0" placeholder="—" id="carton-outer-h-cm" oninput="convertDim('carton-outer-h-cm','carton-outer-h-in','cm')" /><span class="specs-unit-tag">cm</span></div>
            <div class="specs-input-wrap"><input type="number" step="0.01" min="0" placeholder="—" id="carton-outer-h-in" oninput="convertDim('carton-outer-h-in','carton-outer-h-cm','in')" /><span class="specs-unit-tag">in</span></div>
            <hr class="specs-dim-divider" />
            <div></div>
            <div class="specs-unit-header">kg</div>
            <div class="specs-unit-header">lb</div>
            <div class="specs-row-label">Weight</div>
            <div class="specs-input-wrap"><input type="number" step="0.001" min="0" placeholder="—" id="carton-outer-weight" oninput="convertWeight('carton-outer-weight','carton-outer-weight-lbs','kg')" /><span class="specs-unit-tag">kg</span></div>
            <div class="specs-input-wrap"><input type="text" placeholder="—" id="carton-outer-weight-lbs" oninput="convertWeight('carton-outer-weight-lbs','carton-outer-weight','lbs')" /><span class="specs-unit-tag">lb</span></div>
            <div class="specs-full-row" style="margin-top:6px;">
              <div class="specs-row-label" style="margin-bottom:5px;">Qty <span style="font-weight:400; text-transform:none; font-size:11px;">(units / carton)</span></div>
              <input type="number" min="0" placeholder="e.g. 100" id="carton-outer-count" style="width:100%;" />
            </div>
          </div>
        </div>

      </div>

      <!-- Hidden inputs kept for backward compatibility -->
      <input type="hidden" id="carton-unit-weight" />
      <input type="hidden" id="carton-unit-weight-lbs" />
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
  </div><!-- /#wb-tab-workbook -->

  <!-- ── Tab: Shipping ── -->
  <div id="wb-tab-shipping" class="wb-tab-content">
  <div class="section-card">
    <div class="section-header">
      <span class="section-title">Shipping Weight Calculator</span>
    </div>
    <div class="section-body">
      <div class="subsection" style="margin-top:0; padding-top:0; border-top:none;">
        <div class="subsection-title" style="display:none;">Shipping Weight Calculator</div>
        <p style="font-size:12px; color:var(--text-muted); margin-bottom:14px;">
          Carriers charge the greater of Actual Weight or Volumetric Weight. This calculator determines the chargeable weight and estimated cost.
        </p>

        <div class="freight-calc">
          <!-- INPUT PANEL -->
          <div class="freight-panel">
            <div class="freight-panel-title">Inputs</div>

            <div class="freight-field" style="margin-bottom:10px;">
              <label>Dimension Unit</label>
              <div class="select-wrap"><select id="freight-dim-unit" onchange="updateFreightDimLabels(); calcFreight()">
                <option value="in" selected>Inches (in)</option>
                <option value="cm">Centimeters (cm)</option>
                <option value="mm">Millimeters (mm)</option>
              </select></div>
            </div>

            <div class="freight-row-3">
              <div class="freight-field">
                <label>Length</label>
                <div class="dim-dual-field no-spin">
                  <input type="number" step="0.01" min="0" value="60" id="freight-l" oninput="calcFreight()" />
                  <span class="dim-unit freight-dim-label">in</span>
                </div>
              </div>
              <div class="freight-field">
                <label>Width</label>
                <div class="dim-dual-field no-spin">
                  <input type="number" step="0.01" min="0" value="50" id="freight-w" oninput="calcFreight()" />
                  <span class="dim-unit freight-dim-label">in</span>
                </div>
              </div>
              <div class="freight-field">
                <label>Height</label>
                <div class="dim-dual-field no-spin">
                  <input type="number" step="0.01" min="0" value="40" id="freight-h" oninput="calcFreight()" />
                  <span class="dim-unit freight-dim-label">in</span>
                </div>
              </div>
            </div>

            <div class="freight-row">
              <div class="freight-field">
                <label>Weight Unit</label>
                <div class="select-wrap"><select id="freight-wt-unit" onchange="calcFreight()">
                  <option value="kg" selected>Kilograms (kg)</option>
                  <option value="lbs">Pounds (lbs)</option>
                </select></div>
              </div>
              <div class="freight-field">
                <label>Actual Weight</label>
                <input type="number" step="0.01" min="0" value="10" id="freight-actual" oninput="calcFreight()" />
              </div>
            </div>

            <div class="freight-row">
              <div class="freight-field">
                <label>Shipping Method</label>
                <div class="select-wrap"><select id="freight-mode" onchange="updateFreightRate(); calcFreight()">
                  <option value="slow" selected>Slow Boat</option>
                  <option value="fast">Fast Boat</option>
                  <option value="airupp">Air + UPS</option>
                  <option value="directair">Direct Air</option>
                </select></div>
              </div>
              <div class="freight-field">
                <label>Rate per kg (RMB)</label>
                <div class="currency-prefix currency-rmb">
                  <input type="number" step="0.01" min="0" value="12" id="freight-rate" oninput="calcFreight()" />
                </div>
              </div>
            </div>

            <div class="freight-field" style="margin-bottom:0;">
              <label>Number of Cartons</label>
              <input type="number" step="1" min="1" value="1" id="freight-cartons" oninput="calcFreight()" />
            </div>

            <!-- Reference table -->
            <table class="freight-ref-table">
              <thead><tr><th>Method</th><th>Rate / kg</th><th>Divisor</th></tr></thead>
              <tbody>
                <tr><td>Slow Boat</td><td>$12.00</td><td>÷ 6,000</td></tr>
                <tr><td>Fast Boat</td><td>$14.00</td><td>÷ 6,000</td></tr>
                <tr><td>Air + UPS</td><td>$44.00</td><td>÷ 5,000</td></tr>
                <tr><td>Direct Air</td><td>$65.00</td><td>÷ 5,000</td></tr>
              </tbody>
            </table>
          </div>

          <!-- RESULTS PANEL -->
          <div class="freight-panel">
            <div class="freight-panel-title">Results</div>

            <div class="freight-result">
              <span class="freight-result-label">Actual Weight</span>
              <span class="freight-result-value" id="freight-out-actual">10.00 kg</span>
            </div>
            <div class="freight-result">
              <span class="freight-result-label">Volumetric Weight</span>
              <span class="freight-result-value" id="freight-out-vol">20.00 kg</span>
            </div>
            <div class="freight-result">
              <span class="freight-result-label">Chargeable Weight</span>
              <span class="freight-result-value highlight" id="freight-out-charge">20.00 kg</span>
            </div>
            <div class="freight-result">
              <span class="freight-result-label">Formula Used</span>
              <span class="freight-result-value" id="freight-out-formula" style="font-size:12px;">(60 × 50 × 40) ÷ 6,000</span>
            </div>

            <!-- Bar comparison -->
            <div class="freight-bars">
              <div class="freight-bar-col">
                <span class="freight-bar-val" id="freight-bar-actual-val">10</span>
                <div class="freight-bar actual-bar" id="freight-bar-actual" style="height:50%"></div>
                <span class="freight-bar-label">Actual</span>
              </div>
              <div class="freight-bar-col">
                <span class="freight-bar-val" id="freight-bar-vol-val">20</span>
                <div class="freight-bar vol-bar" id="freight-bar-vol" style="height:100%"></div>
                <span class="freight-bar-label">Volumetric</span>
              </div>
              <div class="freight-bar-col">
                <span class="freight-bar-val" id="freight-bar-charge-val">20</span>
                <div class="freight-bar charge-bar" id="freight-bar-charge" style="height:100%"></div>
                <span class="freight-bar-label">Chargeable</span>
              </div>
            </div>

            <!-- Verdict -->
            <div class="freight-verdict volumetric" id="freight-verdict">
              Volumetric weight applies — package is bulky/light.
            </div>

            <!-- Cost -->
            <div class="freight-result" style="margin-top:14px;">
              <span class="freight-result-label">Estimated Shipping Cost</span>
              <span class="freight-result-value cost" id="freight-out-cost">$90.00</span>
            </div>

            <div class="freight-extra" id="freight-extra">
              Extra cost due to volumetric: <span>$45.00</span>
            </div>

            <!-- Tip -->
            <div class="freight-tip" id="freight-tip">
              Tip: Reduce void/air space in packaging to lower volumetric weight.
            </div>

            <!-- Mode comparison -->
            <div class="freight-panel-title" style="margin-top:16px;">Volumetric by Divisor</div>
            <div class="freight-result">
              <span class="freight-result-label">Slow / Fast Boat (÷ 6,000)</span>
              <span class="freight-result-value" id="freight-cmp-air">20.00 kg</span>
            </div>
            <div class="freight-result">
              <span class="freight-result-label">Air + UPS / Direct Air (÷ 5,000)</span>
              <span class="freight-result-value" id="freight-cmp-express">24.00 kg</span>
            </div>
            <div class="freight-result">
              <span class="freight-result-label">Volume (CBM)</span>
              <span class="freight-result-value" id="freight-cmp-sea">0.1200 CBM</span>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
  </div><!-- /#wb-tab-shipping -->

  <!-- ── Tab: Pricing ── -->
  <div id="wb-tab-pricing" class="wb-tab-content">

  <!-- ── Card: Tiered Pricing ── -->
  <div class="section-card">
    <div class="section-header section-header-collapsible" onclick="toggleSection(this.closest('.section-card'))">
      <span class="section-title">Tiered Pricing</span>
      <span class="section-chevron">›</span>
    </div>
    <div class="section-body">
      <p style="font-size:12px; color:var(--text-muted); margin-bottom:14px;">
        Karen fills in the Unit Price for each quantity tier. Total is calculated automatically.
      </p>
      <table class="tier-table" id="tier-table">
        <thead>
          <tr>
            <th class="tier-col-num">#</th>
            <th><span class="label-full">Quantity</span><span class="label-short">Qty</span></th>
            <th class="th-karen"><span class="label-full">Unit Price (RMB) ✎</span><span class="label-short">RMB ✎</span></th>
            <th class="tier-col-usd"><span class="label-full">Unit Price (USD)</span><span class="label-short">Unit (USD)</span></th>
            <th><span class="label-full">Total Price (USD)</span><span class="label-short">Total (USD)</span></th>
            <th></th>
          </tr>
        </thead>
        <tbody id="tier-body">
          <tr id="tier-1">
            <td class="tier-col-num" style="color:var(--text-muted); font-weight:600;">1</td>
            <td><input type="number" min="0" placeholder="e.g. 100" value="100" oninput="recalcTier(1)" style="width:110px;" /></td>
            <td class="karen-cell"><input type="number" step="0.01" min="0" placeholder="0.00" value="" oninput="recalcTier(1)" style="width:130px;" /></td>
            <td class="tier-col-usd" id="tier-usd-1" style="color:var(--text-muted); font-size:13px;">—</td>
            <td class="total-cell" id="tier-total-1">—</td>
            <td><button class="btn btn-danger-ghost" onclick="removeTierRow(1)">✕</button></td>
          </tr>
          <tr id="tier-2">
            <td class="tier-col-num" style="color:var(--text-muted); font-weight:600;">2</td>
            <td><input type="number" min="0" placeholder="e.g. 100" value="250" oninput="recalcTier(2)" style="width:110px;" /></td>
            <td class="karen-cell"><input type="number" step="0.01" min="0" placeholder="0.00" value="" oninput="recalcTier(2)" style="width:130px;" /></td>
            <td class="tier-col-usd" id="tier-usd-2" style="color:var(--text-muted); font-size:13px;">—</td>
            <td class="total-cell" id="tier-total-2">—</td>
            <td><button class="btn btn-danger-ghost" onclick="removeTierRow(2)">✕</button></td>
          </tr>
          <tr id="tier-3">
            <td class="tier-col-num" style="color:var(--text-muted); font-weight:600;">3</td>
            <td><input type="number" min="0" placeholder="e.g. 100" value="500" oninput="recalcTier(3)" style="width:110px;" /></td>
            <td class="karen-cell"><input type="number" step="0.01" min="0" placeholder="0.00" value="" oninput="recalcTier(3)" style="width:130px;" /></td>
            <td class="tier-col-usd" id="tier-usd-3" style="color:var(--text-muted); font-size:13px;">—</td>
            <td class="total-cell" id="tier-total-3">—</td>
            <td><button class="btn btn-danger-ghost" onclick="removeTierRow(3)">✕</button></td>
          </tr>
        </tbody>
      </table>
      <p style="font-size:12px; color:var(--text-muted); margin-top:10px;">Tiers are managed from the Workbook tab.</p>
    </div>
  </div>

  <!-- ── Card: Additional Fees (Pricing tab) ── -->
  <div class="section-card">
    <div class="section-header section-header-collapsible" onclick="toggleSection(this.closest('.section-card'))">
      <span class="section-title">Additional Fees</span>
      <span class="section-chevron">›</span>
    </div>
    <div class="section-body">
      <table class="tier-table" style="max-width:760px;">
        <thead>
          <tr>
            <th style="text-align:left; width:22%;">Fee</th>
            <th style="text-align:left;">Description</th>
            <th style="text-align:right;">RMB</th>
            <th style="text-align:right;">USD</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td style="padding:8px 12px; color:var(--text-muted); font-size:13px; white-space:nowrap;">Sample Fee(s)</td>
            <td style="padding:8px 12px; font-size:13px;" id="pricing-fee-sample-desc"></td>
            <td style="padding:8px 12px; text-align:right; font-size:13px;" id="pricing-fee-sample-rmb">—</td>
            <td style="padding:8px 12px; text-align:right; font-size:13px; font-weight:600;" id="pricing-fee-sample">—</td>
          </tr>
          <tr>
            <td style="padding:8px 12px; color:var(--text-muted); font-size:13px; white-space:nowrap;">Tooling Fee(s)</td>
            <td style="padding:8px 12px; font-size:13px;" id="pricing-fee-tooling-desc"></td>
            <td style="padding:8px 12px; text-align:right; font-size:13px;" id="pricing-fee-tooling-rmb">—</td>
            <td style="padding:8px 12px; text-align:right; font-size:13px; font-weight:600;" id="pricing-fee-tooling">—</td>
          </tr>
          <tr>
            <td style="padding:8px 12px; color:var(--text-muted); font-size:13px; white-space:nowrap;">Die Fee(s)</td>
            <td style="padding:8px 12px; font-size:13px;" id="pricing-fee-die-desc"></td>
            <td style="padding:8px 12px; text-align:right; font-size:13px;" id="pricing-fee-die-rmb">—</td>
            <td style="padding:8px 12px; text-align:right; font-size:13px; font-weight:600;" id="pricing-fee-die">—</td>
          </tr>
          <tr>
            <td style="padding:8px 12px; color:var(--text-muted); font-size:13px; white-space:nowrap;">Plate Fee(s)</td>
            <td style="padding:8px 12px; font-size:13px;" id="pricing-fee-plate-desc"></td>
            <td style="padding:8px 12px; text-align:right; font-size:13px;" id="pricing-fee-plate-rmb">—</td>
            <td style="padding:8px 12px; text-align:right; font-size:13px; font-weight:600;" id="pricing-fee-plate">—</td>
          </tr>
        </tbody>
        <tbody id="pricing-extra-fee-rows"></tbody>
        <tbody>
          <tr style="border-top:2px solid var(--border);">
            <td style="padding:8px 12px; color:var(--text-muted); font-size:13px; white-space:nowrap;">Design Fee(s)</td>
            <td style="padding:8px 12px; font-size:13px;" id="pricing-fee-design-desc"></td>
            <td style="padding:8px 12px; text-align:right; font-size:12px; color:var(--text-muted); font-style:italic;">USD only</td>
            <td style="padding:8px 12px; text-align:right; font-size:13px; font-weight:600;" id="pricing-fee-design">—</td>
          </tr>
        </tbody>
        <tbody>
          <tr style="border-top:2px solid var(--border); background:rgba(232,117,26,0.06);">
            <td colspan="2" style="padding:10px 12px; font-weight:700; font-size:13px; text-transform:uppercase; letter-spacing:0.04em;">Total Additional Fee(s)</td>
            <td style="padding:10px 12px; text-align:right; font-weight:700; font-size:13px; color:var(--accent);" id="pricing-fee-total-rmb">—</td>
            <td style="padding:10px 12px; text-align:right; font-weight:700; font-size:14px; color:var(--accent);" id="pricing-fee-total">—</td>
          </tr>
        </tbody>
      </table>
      <p style="font-size:12px; color:var(--text-muted); margin-top:10px;">Fees are entered in the Workbook tab and added to each tier's total below.</p>
    </div>
  </div>

  <!-- ── Card: Grand Total per Tier ── -->
  <div class="section-card" id="pricing-grand-total-section">
    <div class="section-header section-header-collapsible" onclick="toggleSection(this.closest('.section-card'))">
      <span class="section-title">Grand Total per Tier (incl. Fees)</span>
      <span class="section-chevron">›</span>
    </div>
    <div class="section-body">
      <table class="tier-table" id="pricing-grand-total-table" style="max-width:480px;">
        <thead>
          <tr>
            <th>Quantity</th>
            <th style="text-align:right;">Tier Total (USD)</th>
            <th style="text-align:right;">+ Fees</th>
            <th style="text-align:right;">Grand Total</th>
          </tr>
        </thead>
        <tbody id="pricing-grand-total-body"></tbody>
      </table>
    </div>
  </div>

  </div><!-- /#wb-tab-pricing -->

  <!-- ── Tab: Quote for Client ── -->
  <div id="wb-tab-quote" class="wb-tab-content">
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
  <div id="wb-tab-invoice" class="wb-tab-content">
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

</div><!-- /.app-content -->
</div><!-- /.app-layout -->

<!-- ── New Workbook Modal ─────────────────────────────────────────────── -->
<div class="modal-overlay" id="modal-overlay" onclick="if(event.target===this)closeModal()">
  <div class="modal">
    <div class="modal-title">New Workbook</div>
    <form id="new-workbook-form" onsubmit="createWorkbook(event)">
      <div class="modal-field">
        <label>Product Name <span class="required">*</span></label>
        <input type="text" id="modal-product" placeholder="e.g. Custom Tote Bag" required />
      </div>
      <div class="modal-field">
        <label>Client <span class="required">*</span></label>
        <div class="select-wrap"><select id="modal-client" required>
          <option value="">Select a client…</option>
          <option>BAM</option>
          <option>Bloom</option>
          <option>Candy Pan</option>
          <option>Fresh Her</option>
          <option>Kids United</option>
          <option>Nut Garden</option>
          <option>Salt</option>
          <option>Tweedle Dee</option>
        </select></div>
      </div>
      <div class="modal-field">
        <label>Description <span class="required">*</span></label>
        <textarea id="modal-desc" placeholder="Brief description of the product…" required></textarea>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn-create">Create Workbook</button>
      </div>
    </form>
  </div>
</div>

<!-- ── Add Client Modal ───────────────────────────────────────────────── -->
<div class="modal-overlay" id="client-modal-overlay" onclick="if(event.target===this)closeClientModal()">
  <div class="modal">
    <div class="modal-title">Add Client</div>
    <form id="add-client-form" onsubmit="createClient(event)">
      <div class="modal-field">
        <label>Client Name <span class="required">*</span></label>
        <input type="text" id="modal-client-name" placeholder="e.g. Acme Corp" required />
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

<!-- ── Users Modal (admin only) ── -->
<div class="modal-overlay" id="users-modal-overlay" onclick="if(event.target===this)closeUsersModal()" style="display:none;">
  <div class="modal" style="max-width:520px; max-height:80vh; display:flex; flex-direction:column;">
    <div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; padding:16px 20px; border-bottom:1px solid var(--border);">
      <h3 style="margin:0; font-size:16px;">User Management</h3>
      <button onclick="closeUsersModal()" style="background:none; border:none; color:var(--text-muted); font-size:20px; cursor:pointer;">✕</button>
    </div>
    <div style="padding:16px 20px; overflow-y:auto; flex:1;">
      <div id="users-list" style="margin-bottom:20px;"></div>
      <div style="border-top:1px solid var(--border); padding-top:16px;">
        <div style="font-size:13px; font-weight:600; margin-bottom:10px;">Add New User</div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:8px;">
          <input id="new-user-username" type="text" placeholder="Username" class="field-input" style="font-size:13px;" />
          <input id="new-user-display" type="text" placeholder="Display Name" class="field-input" style="font-size:13px;" />
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:10px;">
          <input id="new-user-password" type="password" placeholder="Password" class="field-input" style="font-size:13px;" />
          <select id="new-user-role" class="field-input" style="font-size:13px;">
            <option value="user">User</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <button class="btn-create" onclick="addUser()" style="font-size:13px; padding:8px 16px;">+ Add User</button>
      </div>
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
    <div style="display:flex; gap:0; border-bottom:1px solid var(--border);">
      <button class="archive-tab active" onclick="switchArchiveTab('workbooks', this)" style="flex:1; padding:10px; border:none; background:transparent; cursor:pointer; font-family:inherit; font-size:13px; font-weight:600; border-bottom:2px solid var(--accent);">Workbooks</button>
      <button class="archive-tab" onclick="switchArchiveTab('clients', this)" style="flex:1; padding:10px; border:none; background:transparent; cursor:pointer; font-family:inherit; font-size:13px; font-weight:600; color:var(--text-muted); border-bottom:2px solid transparent;">Clients</button>
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
    if (btn) btn.textContent = isDark ? '🌙 Dark Mode' : '☀️ Light Mode';
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
  }

  function onSecondaryChange(wrapId, sel) {
    document.getElementById(wrapId).classList.toggle('has-value', !!sel.value);
    if (_appReady) autoSaveWorkbook();
  }

  /* ── Art Tab ─────────────────────────────────────────────────────────────── */
  let _artImages = [];

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
        targetEl.value = (totalLbs / KG_TO_LBS).toFixed(3);
      }
    }
    convertingWeight = false;
  }

  /* ── Pantone Swatch ────────────────────────────────────────────────────── */
  function syncPantone() {
    // swatch is just a helper; text field is the source of truth
  }

  /* ── RMB to USD Conversion (live rate) ─────────────────────────────────── */
  let USD_TO_RMB = 7.24; // fallback rate

  // Fetch live rate from ExchangeRate-API on page load
  (async function fetchLiveRate() {
    try {
      const res = await fetch('https://open.er-api.com/v6/latest/USD');
      const data = await res.json();
      if (data.result === 'success' && data.rates && data.rates.CNY) {
        USD_TO_RMB = data.rates.CNY;
        console.log('Live USD→CNY rate:', USD_TO_RMB);
        // Re-run RFQ conversions if a workbook is already loaded
        document.querySelectorAll('#rfq-body tr').forEach((row, i) => {
          recalcRfqRow(i + 1);
        });
      }
    } catch (e) {
      console.warn('Could not fetch live rate, using fallback:', USD_TO_RMB);
    }
  })();

  function convertRmbToUsd() {
    const rmb = parseFloat(document.getElementById('quote-unit-rmb').value);
    const usdEl = document.getElementById('quote-unit');
    usdEl.value = isNaN(rmb) ? '' : (rmb / USD_TO_RMB).toFixed(2);
  }

  /* ── RFQ Line Items ─────────────────────────────────────────────────── */
  let rfqCount = 0;
  let _wbLocked = false;

  function addRfqRow(item = '', qty = '', priceRmb = '', leadTime = '') {
    rfqCount++;
    const id = rfqCount;
    const tbody = document.getElementById('rfq-body');
    const isFirstRow = tbody.querySelectorAll('tr').length === 0;
    const defaultItem = isFirstRow && !item ? 'Main Item' : item;
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
    const handleAttr = isFirstRow ? '' : `draggable="true" onmousedown="this.closest('tr').draggable=true" onmouseup="this.closest('tr').draggable=false" ondragstart="event.dataTransfer.setData('text/plain','${id}'); this.closest('tr').style.opacity='0.4'" ondragend="this.closest('tr').style.opacity='1'; this.closest('tr').draggable=false"`;
    tr.innerHTML = `
      <td class="tier-col-num" style="color:var(--text-muted); font-weight:600; text-align:center;${isFirstRow ? '' : ' cursor:grab;'}" ${isFirstRow ? '' : 'title="Drag to reorder"'} ${handleAttr}>${isFirstRow ? id : '☰ ' + id}</td>
      <td><input type="text" placeholder="Enter Item" value="${defaultItem}" oninput="recalcRfqTotals()" style="${inputStyle}" /></td>
      <td><input type="text" inputmode="numeric" placeholder="0" value="${qty}" oninput="recalcRfqRow(${id})" style="${inputStyle}" /></td>
      <td><div class="currency-prefix currency-rmb" style="position:relative;"><input type="text" inputmode="decimal" placeholder="0.00" value="${priceRmb}" oninput="recalcRfqRow(${id})" style="${inputStyle} padding-left:28px;" /></div></td>
      <td class="tier-col-usd" id="rfq-usd-${id}" style="color:var(--text); font-size:13px; text-align:right; font-weight:600;">${usdVal ? '$' + usdVal : '—'}</td>
      <td class="total-cell" id="rfq-total-${id}" style="text-align:right;">${totalVal ? '$' + parseFloat(totalVal).toLocaleString('en-US', {minimumFractionDigits:2}) : '—'}</td>
      <td><div class="lead-time-suffix" style="position:relative;"><input type="text" placeholder="0" value="${leadTime}" oninput="recalcRfqTotals()" style="${inputStyle} padding-right:40px;" /></div></td>
      <td>${isFirstRow ? '' : '<span class="remove-tier" onclick="removeRfqRow(' + id + ')" title="Remove">&times;</span>'}</td>
    `;
    tbody.appendChild(tr);
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
    renumberRfqRows();
    recalcRfqTotals();
    if (!_filling) autoSaveWorkbook();
  }

  function renumberRfqRows() {
    const rows = document.querySelectorAll('#rfq-body tr');
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
    const inputs = row.querySelectorAll('input');
    const qty = parseFloat(inputs[1]?.value) || 0;
    const rmb = parseFloat(inputs[2]?.value) || 0;
    const usd = rmb / USD_TO_RMB;
    const total = qty * usd;
    const usdEl = document.getElementById(`rfq-usd-${id}`);
    const totalEl = document.getElementById(`rfq-total-${id}`);
    if (usdEl) usdEl.textContent = rmb ? '$' + usd.toFixed(2) : '—';
    if (totalEl) totalEl.textContent = (qty && rmb) ? '$' + total.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : '—';
    recalcRfqTotals();
    if (!_filling) autoSaveWorkbook();
  }

  function recalcRfqTotals() {
    const rows = document.querySelectorAll('#rfq-body tr');
    let totalQty = 0, totalUsd = 0, totalRmb = 0, totalUsdUnit = 0, maxLead = 0;
    rows.forEach(row => {
      const inputs = row.querySelectorAll('input');
      const qty = parseFloat(inputs[1]?.value) || 0;
      const rmb = parseFloat(inputs[2]?.value) || 0;
      const lead = inputs[3]?.value || '';
      totalQty += qty;
      totalRmb += rmb;
      totalUsdUnit += rmb / USD_TO_RMB;
      if (qty && rmb) totalUsd += qty * (rmb / USD_TO_RMB);
      const leadNum = parseInt(lead);
      if (!isNaN(leadNum) && leadNum > maxLead) maxLead = leadNum;
    });
    // Total qty comes only from the first (fixed) row
    const firstRow = document.querySelector('#rfq-body tr');
    const firstQty = firstRow ? parseFloat(firstRow.querySelectorAll('input')[1]?.value) || 0 : 0;
    document.getElementById('rfq-total-qty').textContent = firstQty ? firstQty.toLocaleString('en-US') : '—';
    document.getElementById('rfq-total-rmb').textContent = totalRmb ? '¥ ' + totalRmb.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : '—';
    document.getElementById('rfq-total-usd-sum').textContent = totalUsdUnit ? '$' + totalUsdUnit.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : '—';
    document.getElementById('rfq-total-usd').textContent = totalUsd ? '$' + totalUsd.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : '—';
    document.getElementById('rfq-max-lead').textContent = maxLead ? maxLead + ' days' : '—';
    applyRfqRmbToTiers(totalRmb);
  }

  function applyRfqRmbToTiers(totalRmb) {
    const firstRow = document.querySelector('#wb-tier-body tr:first-child');
    if (!firstRow) return;
    const id = parseInt(firstRow.id.replace('wb-tier-', ''));
    firstRow.dataset.price = totalRmb > 0 ? parseFloat(totalRmb).toFixed(2) : '';
    _syncing = true;
    recalcWbTier(id);
    _syncing = false;
    syncTiersToPricing();
  }

  function collectRfqItems() {
    const rows = document.querySelectorAll('#rfq-body tr');
    const items = [];
    rows.forEach(row => {
      const inputs = row.querySelectorAll('input');
      items.push({
        item: inputs[0]?.value || '',
        qty: inputs[1]?.value || '',
        priceRmb: inputs[2]?.value || '',
        leadTime: inputs[3]?.value || ''
      });
    });
    return items;
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
      usdEl.textContent = '$' + usd.toFixed(2);
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

  function addWbTierRow(qty = '', unitPrice = '') {
    wbTierCount++;
    const id = wbTierCount;
    const tbody = document.getElementById('wb-tier-body');
    const tr = document.createElement('tr');
    tr.id = `wb-tier-${id}`;
    tr.dataset.price = unitPrice || '';
    // First row: read-only (driven by RFQ total). All others: editable.
    const rmbCell = (id === 1)
      ? `<td id="wb-tier-rmb-${id}" style="color:var(--text-muted); font-size:13px;">—</td>`
      : `<td class="karen-cell">
          <div class="currency-prefix currency-rmb" style="display:inline-block; position:relative;">
            <input type="number" step="0.01" min="0" placeholder="0.00" value="${unitPrice}"
                   oninput="recalcWbTier(${id})"
                   style="width:110px; padding-left:28px;" />
          </div>
        </td>`;
    tr.innerHTML = `
      <td class="tier-col-num" style="color:var(--text-muted); font-weight:600;">${id}</td>
      <td>
        <input type="number" min="0" placeholder="e.g. 100" value="${qty}"
               oninput="recalcWbTier(${id})"
               style="width:110px;" />
      </td>
      ${rmbCell}
      <td class="tier-col-usd" id="wb-tier-usd-${id}" style="color:var(--text-muted); font-size:13px;">—</td>
      <td class="total-cell" id="wb-tier-total-${id}">—</td>
      <td>
        <button class="btn btn-danger-ghost" onclick="removeWbTierRow(${id})">✕</button>
      </td>
    `;
    tbody.appendChild(tr);
    recalcWbTier(id);
  }

  function recalcWbTier(id) {
    const row = document.getElementById(`wb-tier-${id}`);
    const inputs = row.querySelectorAll('input');
    const qty = parseFloat(inputs[0].value);
    let rmb;
    if (inputs[1]) {
      // Editable row — read from input and keep dataset in sync
      rmb = parseFloat(inputs[1].value);
      row.dataset.price = inputs[1].value;
    } else {
      // First row — read-only, driven by RFQ total
      rmb = parseFloat(row.dataset.price);
    }
    const usd = rmb / USD_TO_RMB;
    const rmbEl = document.getElementById(`wb-tier-rmb-${id}`);
    const usdEl = document.getElementById(`wb-tier-usd-${id}`);
    const totalEl = document.getElementById(`wb-tier-total-${id}`);
    if (rmbEl) rmbEl.textContent = (!isNaN(rmb) && rmb > 0) ? '¥ ' + rmb.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : '—';
    if (!isNaN(rmb) && rmb > 0) {
      usdEl.textContent = '$' + usd.toFixed(2);
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
      autoSaveWorkbook();
    }
  }

  function removeWbTierRow(id) {
    document.getElementById(`wb-tier-${id}`)?.remove();
    syncTiersToPricing();
    autoSaveWorkbook();
  }

  function collectTiersFrom(tbodyId) {
    const rows = document.querySelectorAll(`#${tbodyId} tr`);
    const tiers = [];
    rows.forEach(row => {
      const inputs = row.querySelectorAll('input');
      if (inputs.length >= 2) {
        tiers.push({ qty: inputs[0].value, price: inputs[1].value });
      } else if (inputs.length >= 1) {
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

  async function loadFromDatabase() {
    const result = await apiCall('get_all_data');
    if (!result.success) return false;

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
    });

    if (!hasWorkbooks) return false; // Let seedDatabase handle it

    // Rebuild workbook data
    result.workbooks.forEach(wb => {
      let flowStep = parseInt(wb.flow_step) || 0;
      // Migrate old 8-step flow: if step >= 3 (old shippingDims), shift down by 1
      if (flowStep >= 3 && flowLabels.length === 7) flowStep = Math.max(flowStep - 1, 0);
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

    // ── Full client list ──
    const clientList = document.getElementById('client-list');
    clientList.innerHTML = '';
    sorted.forEach(name => clientList.appendChild(makeClientNavItem(name)));

    // Rebuild modal dropdown
    const select = document.getElementById('modal-client');
    const firstOpt = select.options[0];
    select.innerHTML = '';
    select.appendChild(firstOpt);
    sorted.forEach(name => {
      const opt = document.createElement('option');
      opt.textContent = name;
      select.appendChild(opt);
    });
  }

  function makeClientNavItem(name) {
    const a = document.createElement('a');
    a.className = 'nav-item';
    a.href = `#/client/${encodeURIComponent(name)}`;

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

  function renderStatusBar(flow) {
    const statusFlow = document.getElementById('status-flow');
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
      const nextLabel = flowLabels[currentIdx + 1];
      advanceBtn.textContent = nextLabel;
      advanceBtn.disabled = false;
    } else {
      advanceBtn.textContent = 'Completed';
      advanceBtn.disabled = true;
    }

    // Lock Product Overview tab once Quote Submitted (index 1) or beyond
    lockWorkbookTab(currentIdx >= 1);
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
        banner.textContent = '🔒 Fields are locked after Quote Submitted. Use ← to go back and unlock.';
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
  }

  /* ── Workbook Tabs ────────────────────────────────────────────────────────── */
  function switchWbTab(tabName, btn) {
    document.querySelectorAll('.wb-tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.wb-tab').forEach(el => el.classList.remove('active'));
    document.getElementById('wb-tab-' + tabName).classList.add('active');
    btn.classList.add('active');
    if (tabName === 'shipping') calcFreight();
  }

  /* ── Shipping Calculator ─────────────────────────────────────────────────── */
  const freightMethodRates = { slow: 12, fast: 14, airupp: 44, directair: 65 };
  const freightMethodDivisors = { slow: 6000, fast: 6000, airupp: 5000, directair: 5000 };

  function updateFreightRate() {
    const mode = document.getElementById('freight-mode').value;
    document.getElementById('freight-rate').value = freightMethodRates[mode];
  }

  function updateFreightDimLabels() {
    const unit = document.getElementById('freight-dim-unit').value;
    document.querySelectorAll('.freight-dim-label').forEach(el => { el.textContent = unit; });
  }

  function calcFreight() {
    let l = parseFloat(document.getElementById('freight-l').value) || 0;
    let w = parseFloat(document.getElementById('freight-w').value) || 0;
    let h = parseFloat(document.getElementById('freight-h').value) || 0;
    let actual = parseFloat(document.getElementById('freight-actual').value) || 0;
    const dimUnit = document.getElementById('freight-dim-unit').value;
    const wtUnit = document.getElementById('freight-wt-unit').value;
    const mode = document.getElementById('freight-mode').value;
    const rate = parseFloat(document.getElementById('freight-rate').value) || 0;
    const cartons = parseInt(document.getElementById('freight-cartons').value) || 1;

    // Convert to cm
    let lCm = l, wCm = w, hCm = h;
    if (dimUnit === 'in') { lCm = l * 2.54; wCm = w * 2.54; hCm = h * 2.54; }
    if (dimUnit === 'mm') { lCm = l / 10; wCm = w / 10; hCm = h / 10; }

    // Convert to kg if lbs
    const actualKg = wtUnit === 'lbs' ? actual / 2.20462 : actual;

    const volume = lCm * wCm * hCm;
    const divisor = freightMethodDivisors[mode];
    const volWeight = volume / divisor;
    const formulaStr = `(${lCm.toFixed(0)} × ${wCm.toFixed(0)} × ${hCm.toFixed(0)}) ÷ ${divisor.toLocaleString()}`;

    const chargeWeight = Math.max(actualKg, volWeight);
    const totalCost = chargeWeight * rate * cartons;

    // Update results
    document.getElementById('freight-out-actual').textContent = actualKg.toFixed(2) + ' kg';
    document.getElementById('freight-out-vol').textContent = volWeight.toFixed(2) + ' kg';
    document.getElementById('freight-out-charge').textContent = chargeWeight.toFixed(2) + ' kg';
    document.getElementById('freight-out-formula').textContent = formulaStr;
    document.getElementById('freight-out-cost').textContent = '$' + totalCost.toFixed(2);

    // Bar chart
    const maxWt = Math.max(actualKg, volWeight, 0.01);
    document.getElementById('freight-bar-actual').style.height = ((actualKg / maxWt) * 100) + '%';
    document.getElementById('freight-bar-vol').style.height = ((volWeight / maxWt) * 100) + '%';
    document.getElementById('freight-bar-charge').style.height = ((chargeWeight / maxWt) * 100) + '%';
    document.getElementById('freight-bar-actual-val').textContent = actualKg.toFixed(1);
    document.getElementById('freight-bar-vol-val').textContent = volWeight.toFixed(1);
    document.getElementById('freight-bar-charge-val').textContent = chargeWeight.toFixed(1);

    // Verdict
    const verdictEl = document.getElementById('freight-verdict');
    const extraEl = document.getElementById('freight-extra');
    const tipEl = document.getElementById('freight-tip');

    if (volWeight > actualKg) {
      verdictEl.className = 'freight-verdict volumetric';
      verdictEl.textContent = 'Volumetric weight applies — package is bulky/light.';
      const extraCost = (volWeight - actualKg) * rate * cartons;
      extraEl.innerHTML = 'Extra cost due to volumetric: <span>$' + extraCost.toFixed(2) + '</span>';
      extraEl.style.display = 'block';
      tipEl.textContent = 'Tip: Reduce void/air space in packaging to lower volumetric weight.';
    } else if (actualKg > volWeight) {
      verdictEl.className = 'freight-verdict actual';
      verdictEl.textContent = 'Actual weight applies — package is dense.';
      extraEl.style.display = 'none';
      tipEl.textContent = 'Tip: Your packaging density is efficient for this shipping method.';
    } else {
      verdictEl.className = 'freight-verdict equal';
      verdictEl.textContent = 'Weights are equal — no volumetric surcharge.';
      extraEl.style.display = 'none';
      tipEl.textContent = 'Tip: Weights match perfectly. No size penalty applies.';
    }

    // Method comparison
    const volBoat = volume / 6000;
    const volAir = volume / 5000;
    document.getElementById('freight-cmp-air').textContent = volBoat.toFixed(2) + ' kg (÷ 6,000)';
    document.getElementById('freight-cmp-express').textContent = volAir.toFixed(2) + ' kg (÷ 5,000)';
    const cbm = volume / 1000000;
    document.getElementById('freight-cmp-sea').textContent = cbm.toFixed(4) + ' CBM';
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
  function openNewWorkbookModal() {
    document.getElementById('new-workbook-form').reset();
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
      const result = await apiCall('add_workbook', {
        client_id: clientId,
        product_name: product,
        description: desc
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

    closeModal();

    // Navigate to the new workbook
    location.hash = `#/client/${encodeURIComponent(client)}/workbook/${newId}`;
  }

  /* ── Add Client Modal ───────────────────────────────────────────────────── */
  function openAddClientModal() {
    document.getElementById('add-client-form').reset();
    document.getElementById('client-modal-overlay').classList.add('open');
    setTimeout(() => document.getElementById('modal-client-name').focus(), 50);
  }

  function closeClientModal() {
    document.getElementById('client-modal-overlay').classList.remove('open');
  }

  async function createClient(e) {
    e.preventDefault();
    const name = document.getElementById('modal-client-name').value.trim();
    if (!name) return;

    // Check if client already exists
    if (clientData[name]) {
      alert('Client "' + name + '" already exists.');
      return;
    }

    // Save to DB
    const result = await apiCall('add_client', { name: name });
    if (result.success) {
      dbClientMap[name] = result.id;
    }

    // Add to client data
    clientData[name] = [];

    // Add to sidebar nav (in alphabetical order)
    const nav = document.querySelector('.sidebar-nav');
    const links = Array.from(nav.querySelectorAll('.nav-item'));
    const newLink = document.createElement('a');
    newLink.className = 'nav-item';
    newLink.href = `#/client/${encodeURIComponent(name)}`;
    newLink.textContent = name;

    const insertBefore = links.find(a => a.textContent.trim().localeCompare(name) > 0);
    if (insertBefore) {
      nav.insertBefore(newLink, insertBefore);
    } else {
      nav.appendChild(newLink);
    }

    // Add to workbook modal client dropdown
    const select = document.getElementById('modal-client');
    const newOption = document.createElement('option');
    newOption.textContent = name;
    const options = Array.from(select.options).slice(1);
    const insertBeforeOpt = options.find(o => o.textContent.localeCompare(name) > 0);
    if (insertBeforeOpt) {
      select.insertBefore(newOption, insertBeforeOpt);
    } else {
      select.appendChild(newOption);
    }

    saveToLocalStorage();
    closeClientModal();

    // Navigate to the new client dashboard
    location.hash = `#/client/${encodeURIComponent(name)}`;
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
    freightDimUnit:'Dimension Unit', freightL:'Freight Length', freightW:'Freight Width', freightH:'Freight Height',
    freightWtUnit:'Weight Unit', freightActual:'Actual Weight', freightMode:'Shipping Method',
    freightRate:'Shipping Rate', freightCartons:'Number of Cartons',
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

  function openUsersModal() {
    document.getElementById('users-modal-overlay').style.display = 'flex';
    loadUsers();
  }
  function closeUsersModal() {
    document.getElementById('users-modal-overlay').style.display = 'none';
  }
  function loadUsers() {
    const list = document.getElementById('users-list');
    list.innerHTML = '<p style="color:var(--text-muted); font-size:13px;">Loading…</p>';
    apiCall('get_users').then(data => {
      if (!data.success) { list.innerHTML = '<p style="color:var(--danger);">Failed to load users.</p>'; return; }
      if (!data.data.length) { list.innerHTML = '<p style="color:var(--text-muted);">No users yet.</p>'; return; }
      list.innerHTML = `
        <div style="font-size:13px; font-weight:600; margin-bottom:8px;">Current Users</div>
        ${data.data.map(u => `
          <div style="display:flex; align-items:center; gap:8px; padding:8px 0; border-bottom:1px solid var(--border);">
            <span style="flex:1; font-size:13px;">${u.display_name || u.username} <span style="color:var(--text-muted); font-size:11px;">(${u.username})</span></span>
            <span style="font-size:11px; background:var(--surface2); padding:2px 8px; border-radius:4px; color:${u.role==='admin'?'var(--accent)':'var(--text-muted)'};">${u.role}</span>
            ${u.id !== (window.MS_SESSION && window.MS_SESSION.id) ? `<button onclick="deleteUser(${u.id})" style="background:none; border:none; color:var(--danger); cursor:pointer; font-size:18px; padding:0 4px;" title="Delete user">×</button>` : '<span style="width:28px;"></span>'}
          </div>`).join('')}`;
    });
  }
  function addUser() {
    const username = document.getElementById('new-user-username').value.trim();
    const display  = document.getElementById('new-user-display').value.trim();
    const password = document.getElementById('new-user-password').value;
    const role     = document.getElementById('new-user-role').value;
    if (!username || !password) { alert('Username and password are required.'); return; }
    apiCall('add_user', { username, display_name: display || username, password, role }).then(r => {
      if (r.success) {
        document.getElementById('new-user-username').value = '';
        document.getElementById('new-user-display').value = '';
        document.getElementById('new-user-password').value = '';
        loadUsers();
      } else { alert(r.error || 'Failed to add user.'); }
    });
  }
  function deleteUser(id) {
    if (!confirm('Delete this user?')) return;
    apiCall('delete_user', { id }).then(r => {
      if (r.success) loadUsers();
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
    document.querySelectorAll('.archive-tab').forEach(t => {
      t.classList.remove('active');
      t.style.borderBottomColor = 'transparent';
      t.style.color = 'var(--text-muted)';
    });
    btn.classList.add('active');
    btn.style.borderBottomColor = 'var(--accent)';
    btn.style.color = '';
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
        <div style="display:flex; justify-content:space-between; align-items:center; padding:11px 0; border-bottom:1px solid var(--border);">
          <div style="flex:1; min-width:0;">
            <div style="font-size:13px; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${wb.product_name}</div>
            <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">${wb.client_name} &middot; Deleted ${new Date(wb.deleted_at).toLocaleDateString()}${wb.deleted_by ? ' by ' + wb.deleted_by : ''}</div>
          </div>
          <div style="display:flex; gap:14px; flex-shrink:0; margin-left:16px;">
            <button onclick="restoreArchivedWorkbook(${wb.id})" style="${actionStyle} color:var(--success);">Restore</button>
            <button onclick="permanentDeleteWorkbook(${wb.id})" style="${actionStyle} color:var(--danger);">Delete</button>
          </div>
        </div>
      `).join('');
    } else {
      list.innerHTML = items.map(cl => `
        <div style="display:flex; justify-content:space-between; align-items:center; padding:11px 0; border-bottom:1px solid var(--border);">
          <div style="flex:1; min-width:0;">
            <div style="font-size:13px; font-weight:600;">${cl.name}</div>
            <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">Deleted ${new Date(cl.deleted_at).toLocaleDateString()}${cl.deleted_by ? ' by ' + cl.deleted_by : ''}</div>
          </div>
          <div style="display:flex; gap:14px; flex-shrink:0; margin-left:16px;">
            <button onclick="restoreArchivedClient(${cl.id})" style="${actionStyle} color:var(--success);">Restore</button>
            <button onclick="permanentDeleteClient(${cl.id})" style="${actionStyle} color:var(--danger);">Delete</button>
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

    // Sort client table
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
      return dir * (parseDate(a.dateCreated) - parseDate(b.dateCreated));
    });

    tbody.innerHTML = items.map(item => {
      const complete = isFlowComplete(item.flow);
      const stepName = getCurrentStepName(item.flow);
      const stepClass = complete ? 'complete' : 'in-progress';
      return `
      <tr class="${complete ? 'row-complete' : ''}" onclick="location.hash='#/client/${encodeURIComponent(clientName)}/workbook/${item.id}'">
        <td class="product-name">${item.product} ${complete ? '<span class="status-badge complete">Complete</span>' : ''}</td>
        <td class="col-date-created"><span class="date-full">${item.dateCreated}</span><span class="date-short">${shortDate(item.dateCreated)}</span></td>
        <td class="col-date-submitted">${item.dateSubmitted || '—'}</td>
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
        <td><button class="action-icon-btn" onclick="event.stopPropagation(); toggleActionMenu(this)" title="Actions">⋮</button><div class="action-menu"><a onclick="event.stopPropagation(); location.hash='#/client/${encodeURIComponent(clientName)}/workbook/${item.id}'">View</a><a onclick="event.stopPropagation(); location.hash='#/client/${encodeURIComponent(clientName)}/workbook/${item.id}'">Edit</a><a onclick="event.stopPropagation(); duplicateWorkbook('${clientName.replace(/'/g, "\\'")}', '${item.id}')">Duplicate</a><a onclick="event.stopPropagation(); openDeleteModal('${clientName.replace(/'/g, "\\'")}', '${item.id}', '${item.product.replace(/'/g, "\\'")}')">Delete</a></div></td>
      </tr>
    `}).join('');

    document.getElementById('header-title').textContent = clientName + ' — Workbooks';
    updateSidebarActive(clientName);
    showView('view-dashboard');
  }

  function fillWorkbook(clientName, workbookId) {
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
      _s('materials', data.materials);
      _s('pantone-text', data.pantone);
      _s('cmyk', data.cmyk);
      _s('color-notes', data.colorNotes);
      // RFQ Line Items
      document.getElementById('rfq-body').innerHTML = '';
      rfqCount = 0;
      const hasRfqData = data.rfqItems && Array.isArray(data.rfqItems) && data.rfqItems.length > 0
        && data.rfqItems.some(i => i.item || i.qty || i.priceRmb || i.leadTime);
      if (hasRfqData) {
        data.rfqItems.forEach(item => addRfqRow(item.item, item.qty, item.priceRmb, item.leadTime));
      } else if (data.qty || data.unitPriceRmb) {
        // Legacy: migrate single-row quote data to first RFQ row
        addRfqRow('', data.qty, data.unitPriceRmb, data.leadTime);
        addRfqRow(); addRfqRow();
      } else {
        addRfqRow(); addRfqRow(); addRfqRow();
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
      if (data.cartonUnitWeight) convertWeight('carton-unit-weight','carton-unit-weight-lbs','kg');
      if (data.cartonInnerWeight) convertWeight('carton-inner-weight','carton-inner-weight-lbs','kg');
      if (data.cartonOuterWeight) convertWeight('carton-outer-weight','carton-outer-weight-lbs','kg');
      _s('freight-dim-unit', data.freightDimUnit);
      updateFreightDimLabels();
      _s('freight-l', data.freightL);
      _s('freight-w', data.freightW);
      _s('freight-h', data.freightH);
      _s('freight-wt-unit', data.freightWtUnit);
      _s('freight-actual', data.freightActual);
      _s('freight-mode', data.freightMode);
      _s('freight-rate', data.freightRate);
      _s('freight-cartons', data.freightCartons);
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
      document.getElementById('product-category').value = '';
      document.getElementById('product-category-2').value = '';
      document.getElementById('cat2-wrap').classList.remove('has-value');
      document.getElementById('product-subcategory').innerHTML = '<option value="">Select category first...</option>';
      document.getElementById('product-subcategory-2').innerHTML = '<option value="">None</option>';
      document.getElementById('mat2-wrap').classList.remove('has-value');
      addTierRow(100); addWbTierRow(100);
      addTierRow(250); addWbTierRow(250);
      addTierRow(500); addWbTierRow(500);
    }

    // Trigger filled state on all inputs
    document.querySelectorAll('#view-workbook input, #view-workbook textarea').forEach(el => updateFilled(el));

    const prodName = document.getElementById('product-name').value || 'Workbook';
    document.getElementById('header-title').textContent = clientName + ' — ' + prodName;
    updateSidebarActive(clientName);
    fillQuoteInvoice(clientName, prodName);
    showView('view-workbook');
    calcFreight();
    // Delay clearing _filling to let queued input events (from calcFreight etc.) fire while still blocked
    setTimeout(() => {
      _filling = false;
      // Re-apply lock after all dynamic rows (RFQ, tiers) have been added
      if (_wbLocked) lockWorkbookTab(true);
    }, 200);
  }

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

  function router() {
    const hash = decodeURIComponent(location.hash || '#/');

    // Match: #/client/{name}/workbook/{id}
    const wbMatch = hash.match(/^#\/client\/(.+?)\/workbook\/(\d+)$/);
    if (wbMatch) {
      fillWorkbook(wbMatch[1], wbMatch[2]);
      return;
    }

    // Save current workbook before leaving its view
    saveCurrentWorkbookIfOpen();

    // Match: #/client/{name}
    const clientMatch = hash.match(/^#\/client\/(.+)$/);
    if (clientMatch) {
      renderDashboard(clientMatch[1]);
      return;
    }

    // Default: home
    document.getElementById('header-title').textContent = 'Market Sculpt';
    document.querySelectorAll('.sidebar-nav .nav-item').forEach(a => a.classList.remove('active'));
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

  function renderRecentWorkbooks() {
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
      <tr class="${complete ? 'row-complete' : ''}" style="cursor:pointer;" onclick="location.hash='#/client/${encodeURIComponent(item.client)}/workbook/${item.id}'">
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
  }

  window.MS_SESSION = { name: '<?= addslashes($_msUser) ?>', role: '<?= $_msRole ?>', id: <?= $_msUserId ?>, username: '<?= addslashes($_msUsername) ?>' };
  window.addEventListener('hashchange', router);

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
      freightDimUnit: _v('freight-dim-unit'),
      freightL: _v('freight-l'),
      freightW: _v('freight-w'),
      freightH: _v('freight-h'),
      freightWtUnit: _v('freight-wt-unit'),
      freightActual: _v('freight-actual'),
      freightMode: _v('freight-mode'),
      freightRate: _v('freight-rate'),
      freightCartons: _v('freight-cartons'),
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

  /* ── Init ──────────────────────────────────────────────────────────────── */
  // Footer date
  document.getElementById('gen-date').textContent = new Date().toLocaleDateString('en-US', {
    year: 'numeric', month: 'long', day: 'numeric'
  });

  // Load data: try DB first, then LocalStorage, then use hardcoded fallback
  (async function init() {
    // Try loading from LocalStorage immediately for fast render
    loadFromLocalStorage();
    rebuildSidebar();
    restoreNavSectionStates();
    router();

    // Then try loading from database (will override if successful)
    // If the user typed something before the DB responded, capture it so we don't lose it
    const preDbClient = currentClient;
    const preDbWbId = currentWorkbookId;
    const dbLoaded = await loadFromDatabase();
    if (dbLoaded) {
      console.log('Data loaded from database');
      // Only preserve pre-DB form state if the user actually made edits before DB responded
      if (_isDirty && preDbClient && preDbWbId) {
        const preKey = `${preDbClient}|${preDbWbId}`;
        workbookDetail[preKey] = collectWorkbookDetail();
      }
      router(); // Re-render with DB data (or user's edits if they were faster)
    } else {
      console.log('Using local/fallback data');
      // Seed the DB with sample data if empty
      seedDatabase();
    }

    // Allow autosave now — just after _filling clears (200ms in fillWorkbook + small buffer)
    setTimeout(() => { _appReady = true; }, 210);

    // Restore saved username into sidebar input
  })();

  async function seedDatabase() {
    // Check if DB already has workbooks
    const wbResult = await apiCall('get_workbooks');
    if (wbResult.success && wbResult.data && wbResult.data.length > 0) return; // DB already seeded

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
    saveToLocalStorage();
    console.log('Database seeded with sample data');
  }
</script>
</body>
</html>
