<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../landing/login.php");
    exit();
}

require __DIR__ . '/../db.php';

// Get all templates with their uploader info
$templates = $conn->query("
    SELECT t.*, u.name as uploaded_by_name 
    FROM templates t 
    LEFT JOIN users u ON t.uploaded_by = u.id 
    ORDER BY t.created_at DESC
");

// Get categories for the dropdown
$categories = $conn->query("SELECT * FROM template_categories ORDER BY name ASC");

// Get template stats
$stats = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
        SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived
    FROM templates
")->fetch_assoc();

// Check if GD library is available for previews
$gdAvailable = extension_loaded('gd');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumazing - Template Management</title>
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
        margin-bottom: 8px;
    }
    
    .page-subtitle {
        color: rgba(255,255,255,0.5);
        font-size: 0.9rem;
        margin-bottom: 20px;
    }
    
    /* Top Bar */
    .top-bar {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    
    .stats-mini {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .stat-mini {
        padding: 8px 14px;
        background: var(--dark-card);
        border: 1px solid var(--border);
        border-radius: 8px;
        font-size: 0.78rem;
        white-space: nowrap;
    }
    
    .stat-mini span { font-weight: 700; color: var(--sky); }
    .stat-mini span.draft { color: #fbbf24; }
    .stat-mini span.active { color: #22c55e; }
    .stat-mini span.archived { color: #ef4444; }
    
    /* Search */
    .search-bar { display: flex; align-items: center; gap: 10px; }
    
    .search-input-wrapper { position: relative; }
    
    .search-input {
        padding: 9px 38px 9px 14px;
        background: rgba(255,255,255,0.05);
        border: 1px solid var(--border);
        border-radius: 8px;
        color: var(--white);
        font-family: 'Nunito', sans-serif;
        font-size: 0.83rem;
        outline: none;
        width: 250px;
        transition: all 0.2s;
    }
    
    .search-input:focus {
        border-color: var(--sky);
        background: rgba(0,180,216,0.1);
        width: 280px;
    }
    
    .search-input::placeholder { color: rgba(255,255,255,0.3); }
    
    .search-icon-btn {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: rgba(255,255,255,0.4);
        cursor: pointer;
        font-size: 0.9rem;
        padding: 4px;
    }
    
    .clear-search {
        position: absolute;
        right: 30px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: rgba(255,255,255,0.3);
        cursor: pointer;
        font-size: 0.85rem;
        padding: 4px;
        display: none;
    }
    .clear-search.visible { display: block; }
    .clear-search:hover { color: #ef4444; }
    
    .search-results-count {
        font-size: 0.78rem;
        color: rgba(255,255,255,0.4);
        white-space: nowrap;
    }
    
    /* Add Button */
    .add-template-btn {
        padding: 10px 20px;
        background: var(--sky);
        color: var(--navy);
        border: none;
        border-radius: 10px;
        font-family: 'Nunito', sans-serif;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 20px;
    }
    
    .add-template-btn:hover {
        background: var(--ice);
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,180,216,0.3);
    }
    
    /* Template Grid */
    .template-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 18px;
    }
    
    .template-card {
        background: var(--dark-card);
        border: 1px solid var(--border);
        border-radius: 14px;
        overflow: hidden;
        transition: all 0.3s;
    }
    
    .template-card:hover {
        transform: translateY(-4px);
        border-color: rgba(0,180,216,0.3);
        box-shadow: 0 8px 32px rgba(0,0,0,0.3);
    }
    
    .template-card.hidden { display: none; }
    
    .template-preview {
        height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
        background: rgba(255,255,255,0.03);
    }
    
    .template-preview-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: top;
    }
    
    .template-preview-fallback {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    
    .template-preview-fallback.minimal { background: linear-gradient(135deg, #f5f5f5, #e0e0e0); }
    .template-preview-fallback.creative { background: linear-gradient(135deg, #FF6B6B, #4ECDC4); }
    .template-preview-fallback.corporate { background: linear-gradient(135deg, #2C3E50, #3498DB); }
    .template-preview-fallback.modern { background: linear-gradient(135deg, #667eea, #764ba2); }
    
    .preview-content { padding: 20px; width: 75%; }
    
    .preview-line { height: 6px; border-radius: 3px; margin-bottom: 10px; }
    .minimal .preview-line { background: rgba(0,0,0,0.15); }
    .creative .preview-line { background: rgba(255,255,255,0.3); }
    .corporate .preview-line { background: rgba(255,255,255,0.3); }
    .modern .preview-line { background: rgba(255,255,255,0.3); }
    .preview-line.short { width: 45%; }
    .preview-line.medium { width: 70%; }
    .preview-line.long { width: 90%; }
    
    .preview-header {
        height: 8px;
        border-radius: 4px;
        margin-bottom: 8px;
        width: 55%;
    }
    .minimal .preview-header { background: rgba(0,0,0,0.25); }
    .creative .preview-header { background: rgba(255,255,255,0.5); }
    .corporate .preview-header { background: rgba(255,255,255,0.5); }
    .modern .preview-header { background: rgba(255,255,255,0.5); }
    
    .preview-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.2s;
    }
    .template-card:hover .preview-overlay { opacity: 1; }
    
    .preview-overlay-btn {
        padding: 8px 16px;
        background: var(--sky);
        color: var(--navy);
        border: none;
        border-radius: 8px;
        font-family: 'Nunito', sans-serif;
        font-weight: 600;
        font-size: 0.8rem;
        cursor: pointer;
    }
    
    .template-info { padding: 18px; }
    
    .template-type {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 0.63rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        margin-bottom: 8px;
        text-transform: uppercase;
    }
    
    .type-minimal { background: rgba(144,224,239,0.12); color: #90e0ef; }
    .type-creative { background: rgba(167,139,250,0.12); color: #a78bfa; }
    .type-corporate { background: rgba(52,211,153,0.12); color: #34d399; }
    .type-modern { background: rgba(251,146,60,0.12); color: #fb923c; }
    
    .template-name {
        font-family: 'Outfit', sans-serif;
        font-size: 1.05rem;
        font-weight: 600;
        margin-bottom: 4px;
    }
    
    .template-category {
        font-size: 0.78rem;
        color: rgba(255,255,255,0.5);
        margin-bottom: 10px;
    }
    
    .template-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 14px;
        font-size: 0.75rem;
        color: rgba(255,255,255,0.4);
        flex-wrap: wrap;
        gap: 6px;
    }
    
    .template-actions {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }
    
    .btn-sm {
        padding: 6px 12px;
        border-radius: 8px;
        font-family: 'Nunito', sans-serif;
        font-size: 0.78rem;
        font-weight: 500;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        white-space: nowrap;
    }
    
    .btn-sm-primary { background: var(--sky); color: var(--navy); }
    .btn-sm-primary:hover { background: var(--ice); }
    .btn-sm-outline { background: transparent; border: 1px solid rgba(144,224,239,0.25); color: var(--white); }
    .btn-sm-outline:hover { border-color: var(--sky); background: rgba(0,180,216,0.1); }
    .btn-sm-danger { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #ef4444; }
    .btn-sm-danger:hover { background: rgba(239,68,68,0.2); }
    .btn-sm-success { background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.3); color: #22c55e; }
    .btn-sm-success:hover { background: rgba(34,197,94,0.2); }
    
    .status-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 10px;
        font-size: 0.63rem;
        font-weight: 600;
        letter-spacing: 0.03em;
    }
    .status-draft { background: rgba(251,191,36,0.15); color: #fbbf24; }
    .status-active { background: rgba(34,197,94,0.15); color: #22c55e; }
    .status-archived { background: rgba(239,68,68,0.15); color: #ef4444; }
    
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
    .modal-overlay.active { display: flex; }
    
    .modal {
        background: rgba(3,0,69,0.98);
        border: 1px solid rgba(144,224,239,0.2);
        border-radius: 16px;
        padding: 24px;
        max-width: 550px;
        width: 100%;
        animation: fadeUp 0.3s ease;
        max-height: 90vh;
        overflow-y: auto;
    }
    .modal.large { max-width: 900px; max-height: 95vh; }
    .modal h3 { font-family: 'Outfit', sans-serif; font-size: 1.3rem; margin-bottom: 8px; }
    .modal-subtitle { color: rgba(255,255,255,0.5); font-size: 0.85rem; margin-bottom: 20px; }
    
    .full-preview-container {
        width: 100%;
        max-height: 65vh;
        overflow-y: auto;
        border-radius: 8px;
        background: white;
    }
    .full-preview-container img { width: 100%; display: block; }
    
    .form-group { margin-bottom: 18px; }
    .form-group label {
        display: block;
        font-size: 0.78rem;
        font-weight: 600;
        color: rgba(255,255,255,0.7);
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    
    .form-input, .form-select {
        width: 100%;
        padding: 11px 14px;
        background: rgba(255,255,255,0.05);
        border: 1px solid var(--border);
        border-radius: 8px;
        color: var(--white);
        font-family: 'Nunito', sans-serif;
        font-size: 0.88rem;
        outline: none;
        transition: all 0.2s;
    }
    .form-input:focus, .form-select:focus { border-color: var(--sky); background: rgba(0,180,216,0.1); }
    .form-select option { background: var(--navy); color: var(--white); }
    
    .drop-zone {
        border: 2px dashed var(--border);
        border-radius: 12px;
        padding: 35px 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        background: rgba(255,255,255,0.02);
    }
    .drop-zone:hover, .drop-zone.drag-over { border-color: var(--sky); background: rgba(0,180,216,0.08); }
    .drop-zone.has-file { border-color: #22c55e; background: rgba(34,197,94,0.05); border-style: solid; }
    .drop-zone.reject { border-color: #ef4444; background: rgba(239,68,68,0.05); }
    .drop-icon { font-size: 2.2rem; margin-bottom: 10px; }
    .drop-text { font-size: 0.88rem; color: rgba(255,255,255,0.5); margin-bottom: 4px; }
    .drop-subtext { font-size: 0.73rem; color: rgba(255,255,255,0.3); }
    
    .file-info {
        display: none;
        align-items: center;
        gap: 10px;
        padding: 10px;
        background: rgba(34,197,94,0.1);
        border-radius: 8px;
        margin-top: 10px;
        font-size: 0.83rem;
        color: #22c55e;
    }
    .file-info.visible { display: flex; }
    .file-info .remove-file { margin-left: auto; cursor: pointer; color: #ef4444; font-weight: 700; font-size: 1.1rem; }
    
    .type-selector { display: flex; gap: 8px; flex-wrap: wrap; }
    .type-option {
        padding: 7px 14px;
        border: 1px solid var(--border);
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.78rem;
        font-weight: 500;
        transition: all 0.2s;
        background: transparent;
        color: rgba(255,255,255,0.6);
    }
    .type-option:hover { border-color: rgba(255,255,255,0.3); color: var(--white); }
    .type-option.selected { border-color: var(--sky); background: rgba(0,180,216,0.15); color: var(--sky); }
    
    .modal-buttons { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; flex-wrap: wrap; }
    
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
    .btn-modal-primary { background: var(--sky); color: var(--navy); }
    .btn-modal-primary:hover { background: var(--ice); }
    .btn-modal-primary:disabled { opacity: 0.4; cursor: not-allowed; }
    .btn-modal-secondary { background: transparent; border: 1px solid rgba(144,224,239,0.3); color: var(--white); }
    .btn-modal-secondary:hover { background: rgba(255,255,255,0.05); }
    .btn-modal-danger { background: #ef4444; color: white; }
    .btn-modal-danger:hover { background: #dc2626; }
    
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
    .toast.success { background: rgba(34,197,94,0.2); border: 1px solid #22c55e; color: #22c55e; }
    .toast.error { background: rgba(239,68,68,0.2); border: 1px solid #ef4444; color: #ef4444; }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        grid-column: 1 / -1;
    }
    .empty-state svg { width: 80px; height: 80px; margin-bottom: 16px; opacity: 0.3; }
    .empty-state h3 { font-family: 'Outfit', sans-serif; font-size: 1.2rem; margin-bottom: 8px; }
    .empty-state p { color: rgba(255,255,255,0.4); font-size: 0.85rem; }
    
    .no-results {
        display: none;
        text-align: center;
        padding: 50px 20px;
        grid-column: 1 / -1;
        color: rgba(255,255,255,0.4);
    }
    .no-results.visible { display: block; }
    
    .upload-progress { display: none; margin-top: 10px; }
    .upload-progress.visible { display: block; }
    .progress-bar { height: 4px; background: rgba(255,255,255,0.1); border-radius: 2px; overflow: hidden; }
    .progress-fill { height: 100%; background: var(--sky); border-radius: 2px; width: 0%; transition: width 0.3s; }
    
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    /* ============ RESPONSIVE ============ */
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
        .template-grid {
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 14px;
        }
        .search-input {
            width: 200px;
        }
        .search-input:focus {
            width: 220px;
        }
    }
    
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
        
        .top-bar {
            flex-direction: column;
            gap: 12px;
        }
        .search-input {
            width: 100%;
        }
        .search-input:focus {
            width: 100%;
        }
        
        .template-grid {
            grid-template-columns: 1fr;
        }
        
        .stats-mini {
            gap: 6px;
        }
        .stat-mini {
            padding: 6px 10px;
            font-size: 0.72rem;
        }
    }
    
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
                <li><a href="users.php">
                    <svg class="sidebar-icon" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-7 8-7s8 3 8 7"/></svg>
                    <span>Users</span>
                </a></li>
                <li><a href="templates.php" class="active">
                    <svg class="sidebar-icon" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                    <span>Templates</span>
                </a></li>
            </ul>
        </aside>
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
        
        <!-- Main Content -->
        <main class="main-content">
            <h1 class="page-title">Templates</h1>
            <p class="page-subtitle">Manage your resume templates. Upload DOCX files that users can download.</p>
            
            <!-- Top Bar with Stats and Search -->
            <div class="top-bar">
                <div class="stats-mini">
                    <div class="stat-mini">Total: <span><?php echo $stats['total']; ?></span></div>
                    <div class="stat-mini">Active: <span class="active"><?php echo $stats['active']; ?></span></div>
                    <div class="stat-mini">Draft: <span class="draft"><?php echo $stats['draft']; ?></span></div>
                    <div class="stat-mini">Archived: <span class="archived"><?php echo $stats['archived']; ?></span></div>
                </div>
                
                <div class="search-bar">
                    <div class="search-input-wrapper">
                        <input type="text" class="search-input" id="searchInput" placeholder="Search templates by name or category..." oninput="searchTemplates()">
                        <button class="clear-search" id="clearSearch" onclick="clearSearch()" title="Clear search">✕</button>
                        <button class="search-icon-btn">🔍</button>
                    </div>
                    <span class="search-results-count" id="resultsCount"></span>
                </div>
            </div>
            
            <!-- Add Template Button -->
            <button class="add-template-btn" onclick="openAddModal()">
                + Add New Template
            </button>
            
            <!-- Template Grid -->
            <div class="template-grid" id="templateGrid">
                <?php if ($templates && $templates->num_rows > 0): ?>
                    <?php while ($template = $templates->fetch_assoc()): 
                        // Check for preview image - look for jpg file matching the docx filename
                        $previewFilename = pathinfo($template['file_path'], PATHINFO_FILENAME) . '.jpg';
                        $previewFullPath = __DIR__ . '/templates/previews/' . $previewFilename;
                        $previewWebPath = 'templates/previews/' . $previewFilename;
                        $hasPreview = file_exists($previewFullPath) && filesize($previewFullPath) > 500;
                    ?>
                    <div class="template-card" 
                         data-name="<?php echo strtolower(htmlspecialchars($template['name'])); ?>" 
                         data-category="<?php echo strtolower(htmlspecialchars($template['category'] ?? '')); ?>"
                         data-type="<?php echo $template['type']; ?>"
                         data-status="<?php echo $template['status']; ?>">
                        <div class="template-preview">
                            <?php if ($hasPreview): ?>
                                <img src="<?php echo $previewWebPath . '?v=' . filemtime($previewFullPath); ?>" 
                                     alt="<?php echo htmlspecialchars($template['name']); ?>" 
                                     class="template-preview-img"
                                     loading="lazy"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="template-preview-fallback <?php echo htmlspecialchars($template['type']); ?>" style="display:none;">
                                    <div class="preview-content">
                                        <div class="preview-header"></div>
                                        <div class="preview-line short"></div>
                                        <div class="preview-line long"></div>
                                        <div class="preview-line medium"></div>
                                        <div class="preview-line short"></div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="template-preview-fallback <?php echo htmlspecialchars($template['type']); ?>">
                                    <div class="preview-content">
                                        <div class="preview-header"></div>
                                        <div class="preview-line short"></div>
                                        <div class="preview-line long"></div>
                                        <div class="preview-line medium"></div>
                                        <div class="preview-line short"></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="preview-overlay">
                                <button class="preview-overlay-btn" onclick="openFullPreview('<?php echo $hasPreview ? $previewWebPath : ''; ?>', '<?php echo htmlspecialchars(addslashes($template['name'])); ?>')">
                                    👁 View Preview
                                </button>
                            </div>
                        </div>
                        <div class="template-info">
                            <span class="template-type type-<?php echo $template['type']; ?>"><?php echo $template['type']; ?></span>
                            <h3 class="template-name"><?php echo htmlspecialchars($template['name']); ?></h3>
                            <div class="template-category">📁 <?php echo htmlspecialchars($template['category'] ?? 'Uncategorized'); ?></div>
                            <div class="template-meta">
                                <span>📥 <?php echo number_format($template['downloads']); ?> downloads</span>
                                <span class="status-badge status-<?php echo $template['status']; ?>"><?php echo ucfirst($template['status']); ?></span>
                            </div>
                            <div class="template-actions">
                                <?php if ($template['status'] === 'draft'): ?>
                                <button class="btn-sm btn-sm-success" onclick="updateStatus(<?php echo $template['id']; ?>, 'active')" title="Publish">▶ Publish</button>
                                <?php elseif ($template['status'] === 'active'): ?>
                                <button class="btn-sm btn-sm-outline" onclick="updateStatus(<?php echo $template['id']; ?>, 'archived')" title="Archive">📦 Archive</button>
                                <?php else: ?>
                                <button class="btn-sm btn-sm-success" onclick="updateStatus(<?php echo $template['id']; ?>, 'active')" title="Reactivate">🔄 Reactivate</button>
                                <?php endif; ?>
                                <button class="btn-sm btn-sm-danger" onclick="deleteTemplate(<?php echo $template['id']; ?>, '<?php echo htmlspecialchars(addslashes($template['name'])); ?>')">🗑 Delete</button>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state" id="emptyState">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <path d="M12 8v8M8 12h8"/>
                        </svg>
                        <h3>No Templates Yet</h3>
                        <p>Click "Add New Template" to upload your first resume template</p>
                    </div>
                <?php endif; ?>
                <div class="no-results" id="noResults">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="width: 60px; height: 60px; margin: 0 auto 15px; opacity: 0.3; display: block;">
                        <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
                    </svg>
                    <p>No templates match your search</p>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Add Template Modal -->
    <div class="modal-overlay" id="addTemplateModal">
        <div class="modal">
            <h3>Add New Template</h3>
            <p class="modal-subtitle">Upload a DOCX file and configure the template settings</p>
            
            <form id="templateForm" onsubmit="submitTemplate(event)">
                <div class="form-group">
                    <label>Template Name</label>
                    <input type="text" class="form-input" id="templateName" placeholder="e.g., Professional Executive Resume" required autocomplete="off">
                </div>
                
                <div class="form-group">
                    <label>Category</label>
                    <select class="form-select" id="templateCategory" required>
                        <option value="">Select a category...</option>
                        <?php 
                        if ($categories && $categories->num_rows > 0):
                            while ($cat = $categories->fetch_assoc()):
                        ?>
                            <option value="<?php echo htmlspecialchars($cat['name']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php 
                            endwhile;
                        endif;
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Template Type</label>
                    <div class="type-selector" id="typeSelector">
                        <div class="type-option selected" data-type="minimal" onclick="selectType('minimal', this)">Minimal</div>
                        <div class="type-option" data-type="creative" onclick="selectType('creative', this)">Creative</div>
                        <div class="type-option" data-type="corporate" onclick="selectType('corporate', this)">Corporate</div>
                        <div class="type-option" data-type="modern" onclick="selectType('modern', this)">Modern</div>
                    </div>
                    <input type="hidden" id="templateType" value="minimal">
                </div>
                
                <div class="form-group">
                    <label>Template File (DOCX) - Single file only</label>
                    <div class="drop-zone" id="dropZone" onclick="document.getElementById('fileInput').click()">
                        <div class="drop-icon">📄</div>
                        <div class="drop-text">Drag & drop your DOCX file here</div>
                        <div class="drop-subtext">or click to browse — only one file at a time (max 10MB)</div>
                    </div>
                    <input type="file" id="fileInput" accept=".docx" style="display: none;" onchange="handleFileSelect(this.files[0])">
                    <div class="file-info" id="fileInfo">
                        <span>📎</span>
                        <span id="fileName"></span>
                        <span id="fileSize"></span>
                        <span class="remove-file" onclick="removeFile(event)">×</span>
                    </div>
                    <div class="upload-progress" id="uploadProgress">
                        <div class="progress-bar">
                            <div class="progress-fill" id="progressFill"></div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn-modal btn-modal-secondary" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" class="btn-modal btn-modal-primary" id="submitBtn">Upload Template</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Full Preview Modal -->
    <div class="modal-overlay" id="previewModal">
        <div class="modal large">
            <h3 id="previewTitle">Template Preview</h3>
            <p class="modal-subtitle">Preview of the template design</p>
            <div class="full-preview-container" id="previewContent"></div>
            <div class="modal-buttons">
                <button class="btn-modal btn-modal-secondary" onclick="closePreviewModal()">Close</button>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal">
            <h3>Delete Template</h3>
            <p id="deleteMessage">Are you sure you want to delete this template?</p>
            <div class="modal-buttons">
                <button class="btn-modal btn-modal-danger" id="confirmDelete">Delete</button>
                <button class="btn-modal btn-modal-secondary" onclick="closeDeleteModal()">Cancel</button>
            </div>
        </div>
    </div>
    
    <!-- Toast -->
    <div class="toast" id="toast"></div>

    <script>
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
</script>
    
    <script>
        // Search functionality
        function searchTemplates() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
            const clearBtn = document.getElementById('clearSearch');
            const cards = document.querySelectorAll('.template-card');
            const emptyState = document.getElementById('emptyState');
            const noResults = document.getElementById('noResults');
            const resultsCount = document.getElementById('resultsCount');
            let visibleCount = 0;
            
            if (searchTerm.length > 0) {
                clearBtn.classList.add('visible');
            } else {
                clearBtn.classList.remove('visible');
            }
            
            cards.forEach(card => {
                const name = card.getAttribute('data-name') || '';
                const category = card.getAttribute('data-category') || '';
                
                if (searchTerm === '' || name.includes(searchTerm) || category.includes(searchTerm)) {
                    card.classList.remove('hidden');
                    visibleCount++;
                } else {
                    card.classList.add('hidden');
                }
            });
            
            if (searchTerm.length > 0) {
                resultsCount.textContent = visibleCount + ' result' + (visibleCount !== 1 ? 's' : '') + ' found';
            } else {
                resultsCount.textContent = '';
            }
            
            if (searchTerm.length > 0 && visibleCount === 0) {
                noResults.classList.add('visible');
            } else {
                noResults.classList.remove('visible');
            }
            
            if (emptyState && searchTerm.length > 0) {
                emptyState.style.display = 'none';
            }
        }
        
        function clearSearch() {
            document.getElementById('searchInput').value = '';
            document.getElementById('clearSearch').classList.remove('visible');
            document.getElementById('resultsCount').textContent = '';
            document.getElementById('noResults').classList.remove('visible');
            
            document.querySelectorAll('.template-card').forEach(card => card.classList.remove('hidden'));
            
            const emptyState = document.getElementById('emptyState');
            if (emptyState && document.querySelectorAll('.template-card').length === 0) {
                emptyState.style.display = '';
            }
        }
        
        // Type selection
        function selectType(type, element) {
            document.querySelectorAll('.type-option').forEach(opt => opt.classList.remove('selected'));
            element.classList.add('selected');
            document.getElementById('templateType').value = type;
        }
        
        // File handling - Single file only
        let selectedFile = null;
        const dropZone = document.getElementById('dropZone');
        
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            if (e.dataTransfer.items.length > 1) {
                dropZone.classList.add('reject');
                dropZone.classList.remove('drag-over');
            } else {
                dropZone.classList.add('drag-over');
                dropZone.classList.remove('reject');
            }
        });
        
        dropZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.remove('drag-over', 'reject');
        });
        
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.remove('drag-over', 'reject');
            
            const files = e.dataTransfer.files;
            
            if (files.length > 1) {
                showToast('Please drop only one file at a time', 'error');
                return;
            }
            
            if (files.length === 1) {
                handleFileSelect(files[0]);
            }
        });
        
        function handleFileSelect(file) {
            if (!file) return;
            
            if (!file.name.toLowerCase().endsWith('.docx')) {
                showToast('Please select a .docx file only', 'error');
                return;
            }
            
            if (file.size > 10 * 1024 * 1024) {
                showToast('File size must be less than 10MB', 'error');
                return;
            }
            
            selectedFile = file;
            
            dropZone.classList.add('has-file');
            dropZone.classList.remove('reject');
            document.getElementById('fileName').textContent = file.name;
            document.getElementById('fileSize').textContent = formatFileSize(file.size);
            document.getElementById('fileInfo').classList.add('visible');
            
            const nameInput = document.getElementById('templateName');
            if (!nameInput.value) {
                const name = file.name.replace('.docx', '').replace(/[-_]/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                nameInput.value = name;
            }
        }
        
        function removeFile(event) {
            if (event) event.stopPropagation();
            selectedFile = null;
            document.getElementById('fileInput').value = '';
            dropZone.classList.remove('has-file');
            document.getElementById('fileInfo').classList.remove('visible');
        }
        
        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }
        
        // Modal handling
        function openAddModal() {
            document.getElementById('addTemplateModal').classList.add('active');
            resetForm();
        }
        
        function closeAddModal() {
            document.getElementById('addTemplateModal').classList.remove('active');
            resetForm();
        }
        
        function resetForm() {
            document.getElementById('templateForm').reset();
            document.getElementById('templateType').value = 'minimal';
            document.querySelectorAll('.type-option').forEach((opt, i) => {
                opt.classList.toggle('selected', i === 0);
            });
            removeFile();
            document.getElementById('uploadProgress').classList.remove('visible');
            document.getElementById('submitBtn').disabled = false;
            document.getElementById('submitBtn').textContent = 'Upload Template';
        }
        
        // Full preview modal
        function openFullPreview(previewPath, templateName) {
            const modal = document.getElementById('previewModal');
            const content = document.getElementById('previewContent');
            document.getElementById('previewTitle').textContent = templateName;
            
            if (previewPath && previewPath.trim() !== '') {
                content.innerHTML = '<img src="' + previewPath + '" alt="' + templateName + '" style="width: 100%; display: block;" onerror="this.parentElement.innerHTML=\'<div style=\\\'padding: 60px; text-align: center; color: rgba(255,255,255,0.5);\\\'><p style=\\\'font-size: 1.1rem;\\\'>📄 Preview image could not be loaded</p><p style=\\\'font-size: 0.85rem; margin-top: 8px;\\\'>The preview file may be missing or corrupted</p></div>\';">';
            } else {
                content.innerHTML = '<div style="padding: 60px; text-align: center; color: rgba(255,255,255,0.5);"><p style="font-size: 1.1rem;">📄 Preview not available</p><p style="font-size: 0.85rem; margin-top: 8px;">Preview will be generated when you upload a template</p></div>';
            }
            
            modal.classList.add('active');
        }
        
        function closePreviewModal() {
            document.getElementById('previewModal').classList.remove('active');
        }
        
        // Close modals on overlay click
        document.getElementById('addTemplateModal').addEventListener('click', function(e) {
            if (e.target === this) closeAddModal();
        });
        
        document.getElementById('previewModal').addEventListener('click', function(e) {
            if (e.target === this) closePreviewModal();
        });
        
        // Submit template
        function submitTemplate(event) {
            event.preventDefault();
            
            if (!selectedFile) {
                showToast('Please select a DOCX file', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('name', document.getElementById('templateName').value);
            formData.append('category', document.getElementById('templateCategory').value);
            formData.append('type', document.getElementById('templateType').value);
            formData.append('file', selectedFile);
            
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Uploading...';
            document.getElementById('uploadProgress').classList.add('visible');
            
            let progress = 0;
            const progressFill = document.getElementById('progressFill');
            const progressInterval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress > 90) progress = 90;
                progressFill.style.width = progress + '%';
            }, 200);
            
            fetch('ajax/upload-template.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                clearInterval(progressInterval);
                progressFill.style.width = '100%';
                
                setTimeout(() => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        closeAddModal();
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(data.message || 'Error uploading template', 'error');
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Upload Template';
                        document.getElementById('uploadProgress').classList.remove('visible');
                    }
                }, 500);
            })
            .catch(error => {
                clearInterval(progressInterval);
                showToast('An error occurred. Please try again.', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Upload Template';
                document.getElementById('uploadProgress').classList.remove('visible');
            });
        }
        
        // Update template status
        function updateStatus(id, status) {
            const statusLabels = {
                'active': 'publish',
                'archived': 'archive',
                'draft': 'move to draft'
            };
            
            if (!confirm('Are you sure you want to ' + statusLabels[status] + ' this template?')) return;
            
            fetch('ajax/manage-template.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=update_status&id=' + id + '&status=' + status
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast(data.message || 'Error updating template', 'error');
                }
            });
        }
        
        // Delete template
        let deleteTemplateId = null;
        
        function deleteTemplate(id, name) {
            deleteTemplateId = id;
            document.getElementById('deleteMessage').textContent = 
                'Are you sure you want to permanently delete "' + name + '"? This cannot be undone.';
            document.getElementById('deleteModal').classList.add('active');
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
            deleteTemplateId = null;
        }
        
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModal();
        });
        
        document.getElementById('confirmDelete').addEventListener('click', function() {
            if (!deleteTemplateId) return;
            
            fetch('ajax/manage-template.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=delete&id=' + deleteTemplateId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    closeDeleteModal();
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast(data.message || 'Error deleting template', 'error');
                    closeDeleteModal();
                }
            });
        });
        
        // Toast
        function showToast(message, type) {
            type = type || 'success';
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast ' + type;
            toast.style.display = 'block';
            
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }
    </script>
</body>
</html>