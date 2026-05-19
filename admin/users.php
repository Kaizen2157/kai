<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../landing/login.php");
    exit();
}

require __DIR__ . '/../db.php';

// Get normal users (non-admin)
$normalUsers = $conn->query("
    SELECT id, name, email, auth_type, created_at 
    FROM users 
    WHERE is_admin = 0 
    ORDER BY created_at DESC
");

// Get admin users
$adminUsers = $conn->query("
    SELECT id, name, email, auth_type, created_at 
    FROM users 
    WHERE is_admin = 1 
    ORDER BY created_at DESC
");

// Get counts
$normalCount = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 0")->fetch_assoc()['count'];
$adminCount = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 1")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumazing - User Management</title>
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
        
        .sidebar-menu { list-style: none; }
        
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
        
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 85;
        }
        .sidebar-overlay.active { display: block; }
        
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
        
        /* Users Layout - Side by Side */
        .users-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        
        /* Table Container */
        .table-container {
            background: var(--dark-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
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
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }
        
        .count-badge {
            background: rgba(0,180,216,0.15);
            color: var(--sky);
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.73rem;
            font-weight: 600;
        }
        
        .count-badge.admin-count {
            background: rgba(245, 158, 11, 0.15);
            color: #f59e0b;
        }
        
        /* Horizontal scroll for tables */
        .table-scroll {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 400px;
        }
        
        th {
            text-align: left;
            padding: 10px 16px;
            font-size: 0.7rem;
            color: rgba(255,255,255,0.5);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }
        
        td {
            padding: 10px 16px;
            font-size: 0.83rem;
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
        
        .badge-google {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
        }
        
        .badge-manual {
            background: rgba(144, 224, 239, 0.2);
            color: var(--ice);
        }
        
        /* User Cell */
        .user-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-cell-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.75rem;
            flex-shrink: 0;
        }
        
        .avatar-normal {
            background: rgba(0,180,216,0.2);
            color: var(--sky);
        }
        
        .avatar-admin {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 0.83rem;
        }
        
        .user-email {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.5);
        }
        
        /* Action buttons */
        .action-btns {
            display: flex;
            gap: 5px;
        }
        
        .btn-icon {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: transparent;
            color: rgba(255,255,255,0.6);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            font-size: 0.8rem;
        }
        
        .btn-icon:hover {
            background: rgba(255,255,255,0.05);
            color: var(--white);
            border-color: rgba(255,255,255,0.3);
        }
        
        .btn-icon.promote:hover {
            background: rgba(245, 158, 11, 0.15);
            border-color: #f59e0b;
            color: #f59e0b;
        }
        
        .btn-icon.demote:hover {
            background: rgba(0, 180, 216, 0.15);
            border-color: var(--sky);
            color: var(--sky);
        }
        
        .btn-icon.delete:hover {
            background: rgba(239, 68, 68, 0.15);
            border-color: #ef4444;
            color: #ef4444;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: rgba(255,255,255,0.4);
        }
        
        .empty-state svg {
            width: 50px;
            height: 50px;
            margin-bottom: 12px;
            opacity: 0.3;
        }
        
        .empty-state p {
            font-size: 0.83rem;
        }
        
        /* Search */
        .search-box {
            position: relative;
        }
        
        .search-input {
            padding: 8px 32px 8px 12px;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--white);
            font-family: 'Nunito', sans-serif;
            font-size: 0.8rem;
            outline: none;
            width: 180px;
            transition: all 0.2s;
        }
        
        .search-input:focus {
            border-color: var(--sky);
            width: 200px;
        }
        
        .search-input::placeholder {
            color: rgba(255,255,255,0.3);
        }
        
        .search-icon {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.3);
            font-size: 0.75rem;
            pointer-events: none;
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
            background: rgba(3,0,69,0.95);
            border: 1px solid rgba(144,224,239,0.2);
            border-radius: 16px;
            padding: 24px;
            max-width: 420px;
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
        
        .btn-modal-warning {
            background: #f59e0b;
            color: var(--navy);
        }
        
        .btn-modal-warning:hover { background: #fbbf24; }
        
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
        
        /* Toast */
        .toast {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 300;
            padding: 12px 18px;
            border-radius: 10px;
            font-size: 0.83rem;
            font-weight: 500;
            animation: slideIn 0.3s ease;
            display: none;
        }
        
        .toast.success {
            background: rgba(34, 197, 94, 0.2);
            border: 1px solid #22c55e;
            color: #22c55e;
        }
        
        .toast.error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid #ef4444;
            color: #ef4444;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        /* ============ RESPONSIVE ============ */
        
        /* Tablet */
        @media (max-width: 1200px) {
            .users-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }
        
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
            .users-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Mobile */
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
            .menu-toggle {
                display: block;
            }
            
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
            
            .users-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .table-header {
                padding: 12px 14px;
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-input {
                width: 100%;
            }
            .search-input:focus {
                width: 100%;
            }
            
            th, td {
                padding: 8px 10px;
                font-size: 0.78rem;
            }
        }
        
        /* Small Mobile */
        @media (max-width: 480px) {
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
        .sidebar{
            width: 70vw;
        }
    }
    </style>
</head>
<body>
    <header class="admin-header">
        <div style="display: flex; align-items: center; gap: 12px;">
            <button class="menu-toggle" onclick="toggleSidebar()" aria-label="Toggle menu">☰</button>
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
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">
                    <svg class="sidebar-icon" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                    <span>Dashboard</span>
                </a></li>
                <li><a href="users.php" class="active">
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
            <h1 class="page-title">User Management</h1>
            
            <div class="users-grid">
                <!-- Normal Users -->
                <div class="table-container">
                    <div class="table-header">
                        <h2 class="table-title">
                            👤 Users
                            <span class="count-badge"><?php echo $normalCount; ?></span>
                        </h2>
                        <div class="search-box">
                            <input type="text" class="search-input" placeholder="Search users..." onkeyup="searchUsers(this, 'normal-table')">
                            <span class="search-icon">🔍</span>
                        </div>
                    </div>
                    
                    <?php if ($normalUsers && $normalUsers->num_rows > 0): ?>
                    <div class="table-scroll">
                    <table id="normal-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Auth Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $normalUsers->fetch_assoc()): ?>
                            <tr data-name="<?php echo strtolower(htmlspecialchars($user['name'])); ?>" data-email="<?php echo strtolower(htmlspecialchars($user['email'])); ?>">
                                <td>
                                    <div class="user-cell">
                                        <div class="user-cell-avatar avatar-normal">
                                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                                            <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?php echo $user['auth_type'] === 'google' ? 'badge-google' : 'badge-manual'; ?>">
                                        <?php echo ucfirst($user['auth_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <button class="btn-icon promote" onclick="promoteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['name'])); ?>')" title="Promote to Admin">⬆</button>
                                        <button class="btn-icon delete" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['name'])); ?>')" title="Delete User">🗑</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                            <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-7 8-7s8 3 8 7"/>
                        </svg>
                        <p>No regular users found</p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Admin Users -->
                <div class="table-container">
                    <div class="table-header">
                        <h2 class="table-title">
                            🛡️ Administrators
                            <span class="count-badge admin-count"><?php echo $adminCount; ?></span>
                        </h2>
                        <div class="search-box">
                            <input type="text" class="search-input" placeholder="Search admins..." onkeyup="searchUsers(this, 'admin-table')">
                            <span class="search-icon">🔍</span>
                        </div>
                    </div>
                    
                    <?php if ($adminUsers && $adminUsers->num_rows > 0): ?>
                    <div class="table-scroll">
                    <table id="admin-table">
                        <thead>
                            <tr>
                                <th>Admin</th>
                                <th>Auth Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($admin = $adminUsers->fetch_assoc()): ?>
                            <tr data-name="<?php echo strtolower(htmlspecialchars($admin['name'])); ?>" data-email="<?php echo strtolower(htmlspecialchars($admin['email'])); ?>">
                                <td>
                                    <div class="user-cell">
                                        <div class="user-cell-avatar avatar-admin">
                                            <?php echo strtoupper(substr($admin['name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="user-name"><?php echo htmlspecialchars($admin['name']); ?></div>
                                            <div class="user-email"><?php echo htmlspecialchars($admin['email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?php echo $admin['auth_type'] === 'google' ? 'badge-google' : 'badge-manual'; ?>">
                                        <?php echo ucfirst($admin['auth_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <?php if ($admin['id'] != $_SESSION['user_id']): ?>
                                        <button class="btn-icon demote" onclick="demoteUser(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars(addslashes($admin['name'])); ?>')" title="Demote to User">⬇</button>
                                        <button class="btn-icon delete" onclick="deleteUser(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars(addslashes($admin['name'])); ?>')" title="Delete Admin">🗑</button>
                                        <?php else: ?>
                                        <span style="font-size: 0.7rem; color: rgba(255,255,255,0.3);">You</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                            <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-7 8-7s8 3 8 7"/>
                        </svg>
                        <p>No admin users found</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Confirmation Modal -->
    <div class="modal-overlay" id="confirmModal">
        <div class="modal">
            <h3 id="modalTitle">Confirm Action</h3>
            <p id="modalMessage">Are you sure?</p>
            <div class="modal-buttons">
                <button class="btn-modal" id="modalActionBtn">Confirm</button>
                <button class="btn-modal btn-modal-secondary" onclick="closeModal()">Cancel</button>
            </div>
        </div>
    </div>
    
    <div class="toast" id="toast"></div>
    
    <script>
        // Sidebar toggle
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        }
        document.querySelectorAll('.sidebar-menu li a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) toggleSidebar();
            });
        });
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                document.getElementById('sidebar').classList.remove('open');
                document.getElementById('sidebarOverlay').classList.remove('active');
            }
        });

        // Search
        function searchUsers(input, tableId) {
            const searchTerm = input.value.toLowerCase();
            const table = document.getElementById(tableId);
            if (!table) return;
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const email = row.getAttribute('data-email') || '';
                row.style.display = (name.includes(searchTerm) || email.includes(searchTerm)) ? '' : 'none';
            });
        }
        
        let pendingAction = null;
        
        function showModal(title, message, actionCallback, btnClass, btnText) {
            btnClass = btnClass || 'btn-modal-danger';
            btnText = btnText || 'Confirm';
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalMessage').textContent = message;
            const actionBtn = document.getElementById('modalActionBtn');
            actionBtn.className = 'btn-modal ' + btnClass;
            actionBtn.textContent = btnText;
            pendingAction = actionCallback;
            actionBtn.onclick = function() {
                if (pendingAction) pendingAction();
                closeModal();
            };
            document.getElementById('confirmModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('confirmModal').classList.remove('active');
            pendingAction = null;
        }
        
        document.getElementById('confirmModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
        
        function showToast(message, type) {
            type = type || 'success';
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast ' + type;
            toast.style.display = 'block';
            setTimeout(() => { toast.style.display = 'none'; }, 3000);
        }
        
        function promoteUser(userId, userName) {
            showModal('Promote to Admin', 'Are you sure you want to promote "' + userName + '" to Administrator?', function() {
                fetch('ajax/manage-user.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'action=promote&id=' + userId })
                .then(r => r.json()).then(d => {
                    showToast(d.message, d.success ? 'success' : 'error');
                    if (d.success) setTimeout(() => location.reload(), 1000);
                }).catch(() => showToast('An error occurred', 'error'));
            }, 'btn-modal-warning', 'Promote');
        }
        
        function demoteUser(userId, userName) {
            showModal('Demote to User', 'Are you sure you want to demote "' + userName + '" to a regular user?', function() {
                fetch('ajax/manage-user.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'action=demote&id=' + userId })
                .then(r => r.json()).then(d => {
                    showToast(d.message, d.success ? 'success' : 'error');
                    if (d.success) setTimeout(() => location.reload(), 1000);
                }).catch(() => showToast('An error occurred', 'error'));
            }, 'btn-modal-primary', 'Demote');
        }
        
        function deleteUser(userId, userName) {
            showModal('Delete User', 'Are you sure you want to permanently delete "' + userName + '"?', function() {
                fetch('ajax/manage-user.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'action=delete&id=' + userId })
                .then(r => r.json()).then(d => {
                    showToast(d.message, d.success ? 'success' : 'error');
                    if (d.success) setTimeout(() => location.reload(), 1000);
                }).catch(() => showToast('An error occurred', 'error'));
            }, 'btn-modal-danger', 'Delete');
        }
    </script>
</body>
</html>