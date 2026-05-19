<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../landing/login.php");
    exit();
}

// Get admin stats with single optimized query
require __DIR__ . '/../db.php';
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN is_admin = 1 THEN 1 ELSE 0 END) as admin_count,
        SUM(CASE WHEN auth_type = 'google' THEN 1 ELSE 0 END) as google_count,
        SUM(CASE WHEN auth_type = 'manual' THEN 1 ELSE 0 END) as manual_count
    FROM users
")->fetch_assoc();

// Get template stats
$templateStats = $conn->query("
    SELECT 
        COUNT(*) as total_templates,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_templates,
        COALESCE(SUM(downloads), 0) as total_downloads
    FROM templates
")->fetch_assoc();

// Get recent templates
$recentTemplates = $conn->query("
    SELECT t.*, u.name as uploaded_by_name 
    FROM templates t 
    LEFT JOIN users u ON t.uploaded_by = u.id 
    ORDER BY t.created_at DESC 
    LIMIT 10
");

// Get categories for management
$categoriesQuery = $conn->query("SELECT * FROM template_categories ORDER BY name ASC");
$categories = [];
if ($categoriesQuery) {
    while ($cat = $categoriesQuery->fetch_assoc()) {
        $categories[] = $cat;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumazing - Admin Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Nunito:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    :root {
        --navy: #030045;
        --blue: #0077b6;
        --sky: #00b4d8;
        --ice: #90e0ef;
        --white: #fff;
        --dark-card: rgba(255,255,255,0.05);
        --border: rgba(144,224,239,0.15);
        --sidebar-width: 250px;
        --sidebar-collapsed: 60px;
        --header-height: 64px;
    }
    
    body {
        font-family: 'Nunito', sans-serif;
        background: var(--navy);
        color: var(--white);
        min-height: 100vh;
    }
    
    /* Header */
    .admin-header {
        background: rgba(3,0,69,0.95);
        backdrop-filter: blur(20px);
        border-bottom: 1px solid var(--border);
        padding: 0 20px;
        height: var(--header-height);
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 100;
    }
    
    .admin-logo {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 1.3rem;
        color: var(--white);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }
    
    .admin-badge {
        background: linear-gradient(135deg, #f59e0b, #ef4444);
        color: white;
        font-size: 0.65rem;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 4px;
        letter-spacing: 0.05em;
    }
    
    .header-right {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-shrink: 0;
    }
    
    .user-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--sky);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.9rem;
        flex-shrink: 0;
    }
    
    .user-name-header {
        font-size: 0.85rem;
        color: rgba(255,255,255,0.8);
        white-space: nowrap;
    }
    
    .logout-btn {
        padding: 8px 16px;
        border-radius: 8px;
        border: 1px solid rgba(239, 68, 68, 0.4);
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
        cursor: pointer;
        font-family: 'Nunito', sans-serif;
        font-weight: 600;
        font-size: 0.8rem;
        transition: all 0.2s;
        text-decoration: none;
        white-space: nowrap;
    }
    
    .logout-btn:hover {
        background: rgba(239, 68, 68, 0.2);
        border-color: #ef4444;
    }
    
    /* Mobile menu toggle */
    .menu-toggle {
        display: none;
        background: none;
        border: none;
        color: var(--white);
        font-size: 1.5rem;
        cursor: pointer;
        padding: 4px 8px;
    }
    
    /* Layout */
    .admin-container {
        display: flex;
        padding-top: var(--header-height);
        min-height: 100vh;
    }
    
    /* Sidebar */
    .sidebar {
        width: var(--sidebar-width);
        background: rgba(255,255,255,0.03);
        border-right: 1px solid var(--border);
        padding: 20px 0;
        position: fixed;
        top: var(--header-height);
        left: 0;
        bottom: 0;
        overflow-y: auto;
        z-index: 90;
        transition: transform 0.3s ease, width 0.3s ease;
    }
    
    .sidebar-menu {
        list-style: none;
    }
    
    .sidebar-menu li a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 25px;
        color: rgba(255,255,255,0.7);
        text-decoration: none;
        font-size: 0.88rem;
        font-weight: 500;
        transition: all 0.2s;
        border-left: 3px solid transparent;
        white-space: nowrap;
    }
    
    .sidebar-menu li a:hover,
    .sidebar-menu li a.active {
        background: rgba(0,180,216,0.1);
        color: var(--white);
        border-left-color: var(--sky);
    }
    
    .sidebar-icon {
        width: 20px;
        height: 20px;
        stroke: currentColor;
        fill: none;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
        flex-shrink: 0;
    }
    
    /* Sidebar overlay for mobile */
    .sidebar-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 85;
    }
    
    .sidebar-overlay.active {
        display: block;
    }
    
    /* Main Content */
    .main-content {
        flex: 1;
        padding: 30px;
        margin-left: var(--sidebar-width);
        min-width: 0;
        transition: margin-left 0.3s ease;
    }
    
    .page-title {
        font-family: 'Outfit', sans-serif;
        font-size: clamp(1.3rem, 3vw, 1.8rem);
        font-weight: 700;
        margin-bottom: 25px;
    }
    
    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    
    .stat-card {
        background: var(--dark-card);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 18px;
        backdrop-filter: blur(10px);
    }
    
    .stat-label {
        font-size: 0.7rem;
        color: rgba(255,255,255,0.5);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 8px;
    }
    
    .stat-value {
        font-family: 'Outfit', sans-serif;
        font-size: clamp(1.5rem, 3vw, 2rem);
        font-weight: 700;
        color: var(--sky);
    }
    
    .stat-change {
        font-size: 0.73rem;
        margin-top: 5px;
    }
    
    .stat-change.positive { color: #22c55e; }
    .stat-change.negative { color: #ef4444; }
    
    /* Table Container - Scrollable on mobile */
    .table-container {
        background: var(--dark-card);
        border: 1px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 24px;
    }
    
    .table-header {
        padding: 16px 20px;
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    
    .table-title {
        font-family: 'Outfit', sans-serif;
        font-size: 1rem;
        font-weight: 600;
        white-space: nowrap;
    }
    
    /* Horizontal scroll wrapper for tables */
    .table-scroll {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
        min-width: 700px;
    }
    
    th {
        text-align: left;
        padding: 12px 16px;
        font-size: 0.7rem;
        color: rgba(255,255,255,0.5);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid var(--border);
        white-space: nowrap;
    }
    
    td {
        padding: 10px 16px;
        font-size: 0.82rem;
        border-bottom: 1px solid rgba(144,224,239,0.08);
    }
    
    tr:hover td {
        background: rgba(255,255,255,0.02);
    }
    
    .badge {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.68rem;
        font-weight: 600;
        letter-spacing: 0.03em;
        white-space: nowrap;
    }
    
    .badge-admin { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
    .badge-user { background: rgba(0, 180, 216, 0.2); color: var(--sky); }
    .badge-google { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
    .badge-manual { background: rgba(144, 224, 239, 0.2); color: var(--ice); }
    
    /* Button styles */
    .btn-sm {
        padding: 6px 14px;
        border-radius: 8px;
        font-family: 'Nunito', sans-serif;
        font-size: 0.78rem;
        font-weight: 500;
        cursor: pointer;
        border: none;
        text-decoration: none;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        white-space: nowrap;
    }
    
    .btn-sm-primary {
        background: var(--sky);
        color: var(--navy);
    }
    
    .btn-sm-primary:hover {
        background: var(--ice);
        transform: translateY(-1px);
    }
    
    .btn-sm-outline {
        background: transparent;
        border: 1px solid rgba(144,224,239,0.25);
        color: var(--white);
    }
    
    .btn-sm-outline:hover {
        border-color: var(--sky);
        background: rgba(0,180,216,0.1);
    }
    
    .btn-sm-danger {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.3);
        color: #ef4444;
    }
    
    .btn-sm-danger:hover {
        background: rgba(239, 68, 68, 0.2);
        border-color: #ef4444;
    }
    
    /* Template Preview Mini */
    .template-preview-mini {
        width: 50px;
        height: 35px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.55rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        flex-shrink: 0;
    }
    
    .preview-minimal { background: linear-gradient(135deg, #f5f5f5, #e0e0e0); color: #333; }
    .preview-creative { background: linear-gradient(135deg, #FF6B6B, #4ECDC4); color: white; }
    .preview-corporate { background: linear-gradient(135deg, #2C3E50, #3498DB); color: white; }
    .preview-modern { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
    
    /* Status Badges */
    .badge-draft { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
    .badge-active { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
    .badge-archived { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
    
    /* Type Badges */
    .badge-minimal { background: rgba(144, 224, 239, 0.2); color: var(--ice); }
    .badge-creative { background: rgba(167, 139, 250, 0.2); color: #a78bfa; }
    .badge-corporate { background: rgba(52, 211, 153, 0.2); color: #34d399; }
    .badge-modern { background: rgba(251, 146, 60, 0.2); color: #fb923c; }
    
    /* Empty State */
    .empty-state-admin {
        text-align: center;
        padding: 40px 20px;
        color: rgba(255,255,255,0.5);
    }
    
    .empty-state-admin svg {
        width: 60px;
        height: 60px;
        margin-bottom: 16px;
        opacity: 0.3;
    }
    
    /* Category Management */
    .category-form {
        display: flex;
        gap: 10px;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }
    
    .category-input {
        flex: 1;
        min-width: 180px;
        padding: 10px 14px;
        background: rgba(255,255,255,0.05);
        border: 1px solid var(--border);
        border-radius: 8px;
        color: var(--white);
        font-family: 'Nunito', sans-serif;
        font-size: 0.85rem;
        outline: none;
        transition: all 0.2s;
    }
    
    .category-input:focus {
        border-color: var(--sky);
        background: rgba(0,180,216,0.1);
    }
    
    .category-input::placeholder {
        color: rgba(255,255,255,0.3);
    }
    
    .category-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .category-tag {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        background: rgba(255,255,255,0.05);
        border: 1px solid var(--border);
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .category-tag .delete-cat {
        cursor: pointer;
        color: #ef4444;
        font-weight: 700;
        font-size: 1rem;
        line-height: 1;
        transition: all 0.2s;
    }
    
    .category-tag .delete-cat:hover {
        color: #dc2626;
        transform: scale(1.2);
    }
    
    /* Modal */
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.7);
        z-index: 200;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(5px);
        padding: 20px;
    }
    
    .modal-overlay.active {
        display: flex;
    }
    
    .modal {
        background: rgba(3,0,69,0.98);
        border: 1px solid rgba(144,224,239,0.2);
        border-radius: 16px;
        padding: 24px;
        max-width: 400px;
        width: 100%;
        text-align: center;
        animation: fadeUp 0.3s ease;
    }
    
    .modal h3 {
        font-family: 'Outfit', sans-serif;
        font-size: 1.2rem;
        margin-bottom: 12px;
    }
    
    .modal p {
        color: rgba(255,255,255,0.7);
        margin-bottom: 20px;
        line-height: 1.6;
        font-size: 0.85rem;
    }
    
    .modal-buttons {
        display: flex;
        gap: 10px;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .btn-modal {
        padding: 10px 20px;
        border-radius: 8px;
        font-family: 'Nunito', sans-serif;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
    }
    
    .btn-modal-primary {
        background: var(--sky);
        color: var(--navy);
    }
    
    .btn-modal-primary:hover { background: var(--ice); }
    
    .btn-modal-danger {
        background: #ef4444;
        color: white;
    }
    
    .btn-modal-danger:hover { background: #dc2626; }
    
    .btn-modal-secondary {
        background: transparent;
        border: 1px solid rgba(144,224,239,0.3);
        color: var(--white);
    }
    
    .btn-modal-secondary:hover { background: rgba(255,255,255,0.05); }
    
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* ============ RESPONSIVE BREAKPOINTS ============ */
    
    /* Tablet: 768px - 1024px */
    @media (max-width: 1024px) {
        .sidebar {
            width: var(--sidebar-collapsed);
        }
        .sidebar-menu li a span {
            display: none;
        }
        .sidebar-menu li a {
            justify-content: center;
            padding: 14px 12px;
        }
        .main-content {
            margin-left: var(--sidebar-collapsed);
            padding: 20px;
        }
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        .page-title {
            font-size: 1.4rem;
            margin-bottom: 20px;
        }
    }
    
    /* Mobile: under 768px */
    @media (max-width: 768px) {
        .admin-header {
            padding: 0 15px;
        }
        
        .admin-logo {
            font-size: 1.1rem;
            gap: 6px;
        }
        
        .admin-badge {
            font-size: 0.6rem;
            padding: 2px 6px;
        }
        
        .user-name-header {
            display: none;
        }
        
        .logout-btn {
            padding: 6px 12px;
            font-size: 0.75rem;
        }
        
        /* Sidebar slides in from left */
        .sidebar {
            transform: translateX(-100%);
            width: 260px;
            box-shadow: 4px 0 20px rgba(0,0,0,0.3);
            background: rgba(3,0,69,0.95);
        }
        
        .sidebar.open {
            transform: translateX(0);
        }
        
        .sidebar-menu li a span {
            display: inline;
        }
        
        .sidebar-menu li a {
            justify-content: flex-start;
            padding: 12px 25px;
        }
        
        .main-content {
            margin-left: 0;
            padding: 15px;
        }
        
        .menu-toggle {
            display: block;
        }
        
        /* Stats */
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .stat-card {
            padding: 14px;
        }
        
        .stat-value {
            font-size: 1.4rem;
        }
        
        .stat-label {
            font-size: 0.65rem;
        }
        
        /* Tables */
        .table-header {
            padding: 12px 14px;
            flex-direction: column;
            align-items: flex-start;
        }
        
        th, td {
            padding: 8px 10px;
            font-size: 0.75rem;
        }
        
        /* Category form */
        .category-form {
            flex-direction: column;
        }
        
        .category-input {
            min-width: 100%;
        }
        
        /* Modals */
        .modal {
            margin: 10px;
            padding: 20px;
        }
    }
    
    /* Small Mobile: under 480px */
    @media (max-width: 480px) {
        .sidebar{
            width: 70vw;
        }
        .admin-logo{
            display: none;
        }
        .stats-grid {
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        
        .stat-card {
            padding: 12px;
        }
        
        .stat-value {
            font-size: 1.3rem;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.72rem;
        }
        
        .page-title {
            font-size: 1.2rem;
        }
        
        .header-right {
            gap: 8px;
        }
        .user-avatar {
            width: 30px;
            height: 30px;
            font-size: 0.75rem;
        }
    }
</style>
</head>
<body>
    <header class="admin-header">
        <div style="display: flex; align-items: center; gap: 12px;">
            <button class="menu-toggle" onclick="toggleSidebar()" aria-label="Toggle menu">
                ☰
            </button>
            <a href="dashboard.php" class="admin-logo">
                Resumazing
                <span class="admin-badge">ADMIN</span>
            </a>
        </div>
        <div class="header-right">
            <div class="user-info">
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?></div>
                <span class="user-name-header"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
            </div>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </header>
    
    <div class="admin-container">
        <!-- Sidebar - Fixed Position -->
        <aside class="sidebar" id="sidebar">
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active">
                    <svg class="sidebar-icon" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                    <span>Dashboard</span>
                </a></li>
                <li><a href="users.php">
                    <svg class="sidebar-icon" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-7 8-7s8 3 8 7"/></svg>
                    <span>Users</span>
                </a></li>
                <li><a href="templates.php">
                    <svg class="sidebar-icon" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                    <span>Templates</span>
                </a></li>
            </ul>
        </aside>
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

        <!-- Main Content -->
        <main class="main-content">
            <h1 class="page-title">Admin Dashboard</h1>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Users</div>
                    <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                    <div class="stat-change">Registered Users</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-label">Total Templates</div>
                    <div class="stat-value"><?php echo $templateStats['total_templates'] ?? 0; ?></div>
                    <div class="stat-change positive">Active: <?php echo $templateStats['active_templates'] ?? 0; ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-label">Total Downloads</div>
                    <div class="stat-value"><?php echo number_format($templateStats['total_downloads'] ?? 0); ?></div>
                    <div class="stat-change">All Templates</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-label">Admin Users</div>
                    <div class="stat-value"><?php echo $stats['admin_count']; ?></div>
                    <div class="stat-change">Administrators</div>
                </div>
            </div>
            
            <!-- Recent Templates Table -->
            <div class="table-container">
                <div class="table-header">
                    <h2 class="table-title">Recently Added Templates</h2>
                    <a href="templates.php" class="btn-sm btn-sm-primary" style="text-decoration: none;">View All</a>
                </div>
                
                <?php if ($recentTemplates && $recentTemplates->num_rows > 0): ?>
                <div class="table-scroll">
                    <table>
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Template Name</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Downloads</th>
                            <th>Added By</th>
                            <th>Date Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($template = $recentTemplates->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="template-preview-mini preview-<?php echo htmlspecialchars($template['type'] ?? 'minimal'); ?>">
                                    <?php echo strtoupper(substr($template['type'] ?? 'MIN', 0, 3)); ?>
                                </div>
                            </td>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($template['name']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $template['type'] ?? 'minimal'; ?>">
                                    <?php echo ucfirst($template['type'] ?? 'minimal'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $template['status'] ?? 'draft'; ?>">
                                    <?php echo ucfirst($template['status'] ?? 'draft'); ?>
                                </span>
                            </td>
                            <td><?php echo number_format($template['downloads'] ?? 0); ?></td>
                            <td><?php echo htmlspecialchars($template['uploaded_by_name'] ?? 'System'); ?></td>
                            <td style="color: rgba(255,255,255,0.5);">
                                <?php echo date('M d, Y', strtotime($template['created_at'] ?? 'now')); ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                </div>
                <?php else: ?>
                <div class="empty-state-admin">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                        <path d="M12 8v8M8 12h8"/>
                    </svg>
                    <p style="font-size: 1rem; margin-bottom: 8px;">No templates added yet</p>
                    <p style="font-size: 0.85rem; color: rgba(255,255,255,0.3);">
                        Templates you add will appear here and automatically show on the user dashboard
                    </p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Category Management -->
            <div class="table-container">
                <div class="table-header">
                    <h2 class="table-title">Manage Categories</h2>
                </div>
                <div style="padding: 20px;">
                    <form class="category-form" id="categoryForm" onsubmit="addCategory(event)">
                        <input type="text" class="category-input" id="categoryName" placeholder="Enter new category name..." required>
                        <button type="submit" class="btn-sm btn-sm-primary">+ Add Category</button>
                    </form>
                    
                    <div class="category-list" id="categoryList">
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $cat): ?>
                            <div class="category-tag" data-id="<?php echo $cat['id']; ?>">
                                <?php echo htmlspecialchars($cat['name']); ?>
                                <span class="delete-cat" onclick="deleteCategory(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars($cat['name']); ?>')" title="Delete category">×</span>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: rgba(255,255,255,0.3); font-size: 0.85rem;">No categories yet. Add your first category above.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal">
            <h3>Delete Category</h3>
            <p id="deleteMessage">Are you sure you want to delete this category? Templates in this category will become uncategorized.</p>
            <div class="modal-buttons">
                <button class="btn-modal btn-modal-danger" id="confirmDelete">Delete</button>
                <button class="btn-modal btn-modal-secondary" onclick="closeModal()">Cancel</button>
            </div>
        </div>
    </div>

    <script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('active');
    }
    
    // Close sidebar when clicking a menu item on mobile
    document.querySelectorAll('.sidebar-menu li a').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                toggleSidebar();
            }
        });
    });
    
    // Close sidebar on window resize if open
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('sidebarOverlay').classList.remove('active');
        }
    });
</script>
    
    <script>
        // Add category
        function addCategory(event) {
            event.preventDefault();
            const nameInput = document.getElementById('categoryName');
            const name = nameInput.value.trim();
            
            if (!name) return;
            
            fetch('ajax/add-category.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'name=' + encodeURIComponent(name)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Add new category tag to the list
                    const categoryList = document.getElementById('categoryList');
                    const newTag = document.createElement('div');
                    newTag.className = 'category-tag';
                    newTag.setAttribute('data-id', data.id);
                    newTag.innerHTML = `
                        ${data.name}
                        <span class="delete-cat" onclick="deleteCategory(${data.id}, '${data.name}')" title="Delete category">×</span>
                    `;
                    
                    // Remove empty state if exists
                    const emptyState = categoryList.querySelector('p');
                    if (emptyState) emptyState.remove();
                    
                    categoryList.appendChild(newTag);
                    nameInput.value = '';
                } else {
                    alert(data.message || 'Error adding category');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }
        
        let deleteCategoryId = null;
        
        function deleteCategory(id, name) {
            deleteCategoryId = id;
            document.getElementById('deleteMessage').textContent = 
                `Are you sure you want to delete "${name}"? Templates in this category will become uncategorized.`;
            document.getElementById('deleteModal').classList.add('active');
        }
        
        document.getElementById('confirmDelete').addEventListener('click', function() {
            if (!deleteCategoryId) return;
            
            fetch('ajax/delete-category.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + deleteCategoryId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the category tag
                    const tag = document.querySelector(`.category-tag[data-id="${deleteCategoryId}"]`);
                    if (tag) tag.remove();
                    
                    // Show empty state if no categories left
                    const categoryList = document.getElementById('categoryList');
                    if (categoryList.children.length === 0) {
                        categoryList.innerHTML = '<p style="color: rgba(255,255,255,0.3); font-size: 0.85rem;">No categories yet. Add your first category above.</p>';
                    }
                } else {
                    alert(data.message || 'Error deleting category');
                }
                closeModal();
                deleteCategoryId = null;
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                closeModal();
                deleteCategoryId = null;
            });
        });
        
        function closeModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }
        
        // Close modal on overlay click
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>