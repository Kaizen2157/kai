<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Check for remember token
    if (isset($_COOKIE['remember_token'])) {
        require_once __DIR__ . '/../db.php';
        
        $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE remember_token = ? AND token_expires > NOW()");
        $stmt->bind_param("s", $_COOKIE['remember_token']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
        } else {
            header('Location: ../landing/login.html');
            exit;
        }
    } else {
        header('Location: ../landing/login.html');
        exit;
    }
}

// Get user's name
$userName = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';
$firstName = explode(' ', $userName)[0];

// Get templates from database
require_once __DIR__ . '/../db.php';

// Get all active templates grouped by category
// Your table has 'category' column (varchar), not 'category_id'
$query = "
    SELECT t.*, t.category as category_name 
    FROM templates t 
    WHERE t.status = 'active' 
    ORDER BY t.category ASC, t.downloads DESC
";

$templates_result = $conn->query($query);

// Organize templates by category
$categories = [];
if ($templates_result && $templates_result->num_rows > 0) {
    while ($template = $templates_result->fetch_assoc()) {
        $categoryName = !empty($template['category_name']) ? $template['category_name'] : 'Uncategorized';
        if (!isset($categories[$categoryName])) {
            $categories[$categoryName] = [];
        }
        $categories[$categoryName][] = $template;
    }
}

// Get stats - FIXED: removed category_id reference
$stats_query = "
    SELECT 
        COUNT(*) as total,
        COALESCE(SUM(downloads), 0) as total_downloads,
        COUNT(DISTINCT category) as total_categories
    FROM templates 
    WHERE status = 'active'
";
$stats_result = $conn->query($stats_query);
if ($stats_result) {
    $stats = $stats_result->fetch_assoc();
} else {
    $stats = ['total' => 0, 'total_downloads' => 0, 'total_categories' => 0];
}

// Get category emojis mapping
$categoryEmojis = [
    'Fresh Graduate' => '🎓',
    'Corporate / Business' => '💼',
    'Creative / Design' => '🎨',
    'Tech / Engineering' => '💻',
    'Healthcare' => '🏥',
    'Academic / Research' => '📚',
    'Hospitality' => '🛎️',
    'Minimalist / Clean' => '⚡',
    'Uncategorized' => '📄'
];

// Fallback data if no templates in database
$useFallback = empty($categories);

if ($useFallback) {
    $categories = [
        '🎓 Fresh Graduate' => [
            ['id' => 0, 'name' => 'Classic Graduate', 'type' => 'minimal', 'downloads' => 2300, 'file_path' => 'classic-graduate.docx'],
            ['id' => 0, 'name' => 'Entry Level Pro', 'type' => 'modern', 'downloads' => 1800, 'file_path' => 'entry-level-pro.docx'],
            ['id' => 0, 'name' => 'First Job Seeker', 'type' => 'creative', 'downloads' => 3100, 'file_path' => 'first-job-seeker.docx'],
            ['id' => 0, 'name' => 'New Graduate Modern', 'type' => 'modern', 'downloads' => 2700, 'file_path' => 'new-graduate-modern.docx'],
        ],
        '💼 Corporate / Business' => [
            ['id' => 0, 'name' => 'Executive Suite', 'type' => 'corporate', 'downloads' => 4200, 'file_path' => 'executive-suite.docx'],
            ['id' => 0, 'name' => 'Business Professional', 'type' => 'minimal', 'downloads' => 3500, 'file_path' => 'business-professional.docx'],
            ['id' => 0, 'name' => 'Corporate Clean', 'type' => 'corporate', 'downloads' => 2900, 'file_path' => 'corporate-clean.docx'],
            ['id' => 0, 'name' => 'Management Track', 'type' => 'modern', 'downloads' => 2100, 'file_path' => 'management-track.docx'],
        ],
        '🎨 Creative / Design' => [
            ['id' => 0, 'name' => 'Designer Portfolio', 'type' => 'creative', 'downloads' => 3800, 'file_path' => 'designer-portfolio.docx'],
            ['id' => 0, 'name' => 'Artist CV', 'type' => 'creative', 'downloads' => 2400, 'file_path' => 'artist-cv.docx'],
            ['id' => 0, 'name' => 'Creative Director', 'type' => 'modern', 'downloads' => 1900, 'file_path' => 'creative-director.docx'],
            ['id' => 0, 'name' => 'Graphic Designer Pro', 'type' => 'creative', 'downloads' => 2600, 'file_path' => 'graphic-designer-pro.docx'],
        ],
        '💻 Tech / Engineering' => [
            ['id' => 0, 'name' => 'Software Engineer', 'type' => 'modern', 'downloads' => 5100, 'file_path' => 'software-engineer.docx'],
            ['id' => 0, 'name' => 'IT Professional', 'type' => 'corporate', 'downloads' => 3200, 'file_path' => 'it-professional.docx'],
            ['id' => 0, 'name' => 'Developer Portfolio', 'type' => 'minimal', 'downloads' => 4700, 'file_path' => 'developer-portfolio.docx'],
            ['id' => 0, 'name' => 'Data Scientist', 'type' => 'modern', 'downloads' => 2800, 'file_path' => 'data-scientist.docx'],
        ],
        '🏥 Healthcare' => [
            ['id' => 0, 'name' => 'Nurse Practitioner', 'type' => 'minimal', 'downloads' => 2100, 'file_path' => 'nurse-practitioner.docx'],
            ['id' => 0, 'name' => 'Medical Doctor', 'type' => 'corporate', 'downloads' => 1800, 'file_path' => 'medical-doctor.docx'],
            ['id' => 0, 'name' => 'Healthcare Admin', 'type' => 'modern', 'downloads' => 1500, 'file_path' => 'healthcare-admin.docx'],
        ],
        '📚 Academic / Research' => [
            ['id' => 0, 'name' => 'Academic CV', 'type' => 'minimal', 'downloads' => 2400, 'file_path' => 'academic-cv.docx'],
            ['id' => 0, 'name' => 'Research Scientist', 'type' => 'corporate', 'downloads' => 1900, 'file_path' => 'research-scientist.docx'],
            ['id' => 0, 'name' => 'Professor CV', 'type' => 'minimal', 'downloads' => 1600, 'file_path' => 'professor-cv.docx'],
        ],
        '🛎️ Hospitality' => [
            ['id' => 0, 'name' => 'Hotel Management', 'type' => 'modern', 'downloads' => 1400, 'file_path' => 'hotel-management.docx'],
            ['id' => 0, 'name' => 'Restaurant Manager', 'type' => 'creative', 'downloads' => 1200, 'file_path' => 'restaurant-manager.docx'],
            ['id' => 0, 'name' => 'Customer Service Pro', 'type' => 'minimal', 'downloads' => 1700, 'file_path' => 'customer-service-pro.docx'],
        ],
    ];
}

// Helper function to check if preview exists
function getPreviewPath($filePath) {
    $previewFilename = pathinfo($filePath, PATHINFO_FILENAME) . '.jpg';
    $previewFullPath = __DIR__ . '/../admin/templates/previews/' . $previewFilename;
    
    if (file_exists($previewFullPath) && filesize($previewFullPath) > 500) {
        return '../admin/templates/previews/' . $previewFilename . '?v=' . filemtime($previewFullPath);
    }
    return null;
}

// Format download count
function formatDownloads($count) {
    if ($count >= 1000) {
        return round($count / 1000, 1) . 'k';
    }
    return $count;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Resumazing</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Nunito:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --navy: #030045;
            --blue: #0077b6;
            --sky: #00b4d8;
            --ice: #90e0ef;
            --mist: #caf0f8;
            --white: #fff;
            --text-light: rgba(255,255,255,0.72);
            --text-muted: rgba(255,255,255,0.45);
            --card-bg: rgba(255,255,255,0.04);
            --card-border: rgba(144,224,239,0.14);
            --radius-sm: 8px;
            --radius-md: 14px;
            --radius-lg: 22px;
            --radius-xl: 32px;
        }

        html, body {
            font-family: 'Nunito', sans-serif;
            background: var(--navy);
            color: var(--white);
            overflow-x: hidden;
            min-height: 100vh;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 10% 10%, rgba(0,180,216,0.14) 0%, transparent 60%),
                radial-gradient(ellipse 60% 50% at 90% 80%, rgba(0,119,182,0.18) 0%, transparent 60%),
                repeating-linear-gradient(0deg, transparent, transparent 59px, rgba(255,255,255,0.025) 59px, rgba(255,255,255,0.025) 60px),
                repeating-linear-gradient(90deg, transparent, transparent 59px, rgba(255,255,255,0.025) 59px, rgba(255,255,255,0.025) 60px);
            pointer-events: none;
            z-index: 0;
        }

        .dash-nav {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 100;
            height: 68px;
            background: rgba(3,0,69,0.72);
            backdrop-filter: blur(18px);
            border-bottom: 1px solid rgba(144,224,239,0.12);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 5%;
        }

        .nav-logo {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 1.4rem;
            letter-spacing: -0.02em;
            color: var(--white);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logo-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            background: var(--sky);
            display: inline-block;
            animation: pulse 2s infinite;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 16px;
            border-radius: 25px;
            background: rgba(0,180,216,0.1);
            border: 1px solid rgba(144,224,239,0.2);
        }

        .user-avatar {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: var(--sky);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
            color: var(--navy);
        }

        .user-name {
            font-size: 0.88rem;
            font-weight: 500;
            color: var(--white);
        }

        .btn-nav {
            padding: 8px 18px;
            border-radius: var(--radius-sm);
            font-family: 'Nunito', sans-serif;
            font-size: 0.85rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-nav-outline {
            background: transparent;
            border: 1px solid rgba(144,224,239,0.25);
            color: var(--white);
        }

        .btn-nav-outline:hover {
            border-color: rgba(0,180,216,0.5);
            background: rgba(0,180,216,0.1);
            transform: translateY(-1px);
        }

        .page-content {
            position: relative;
            z-index: 1;
            padding-top: 68px;
        }

        .dashboard-container {
            max-width: 1300px;
            margin: 0 auto;
            padding: 40px 5%;
        }

        .welcome-section {
            margin-bottom: 50px;
            animation: fadeUp 0.6s ease both;
        }

        .welcome-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 16px;
            border-radius: 20px;
            background: rgba(0,180,216,0.1);
            border: 1px solid rgba(0,180,216,0.2);
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--sky);
            letter-spacing: 0.05em;
            margin-bottom: 16px;
        }

        .pip {
            width: 6px; height: 6px;
            background: var(--sky);
            border-radius: 50%;
            animation: pulse 1.8s infinite;
        }

        .welcome-heading {
            font-family: 'Outfit', sans-serif;
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 700;
            letter-spacing: -0.025em;
            line-height: 1.1;
            margin-bottom: 10px;
        }

        .welcome-sub {
            color: var(--text-light);
            font-size: 1.05rem;
            font-weight: 300;
            line-height: 1.6;
        }

        .stats-strip {
            display: flex;
            gap: 30px;
            margin-bottom: 50px;
            flex-wrap: wrap;
        }

        .stat-item {
            flex: 1;
            min-width: 150px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(144,224,239,0.14);
            border-radius: var(--radius-md);
            padding: 20px;
            text-align: center;
            animation: fadeUp 0.6s ease both;
        }

        .stat-num {
            font-family: 'Outfit', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            color: var(--sky);
            display: block;
            line-height: 1;
            margin-bottom: 6px;
        }

        .stat-label {
            font-size: 0.85rem;
            color: var(--text-light);
            font-weight: 300;
        }

        .filter-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 12px 40px 12px 16px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(144,224,239,0.2);
            border-radius: var(--radius-sm);
            color: var(--white);
            font-family: 'Nunito', sans-serif;
            font-size: 0.9rem;
            outline: none;
            transition: all 0.2s;
        }

        .search-box input:focus {
            border-color: var(--sky);
            background: rgba(0,180,216,0.1);
        }

        .search-box input::placeholder {
            color: rgba(255,255,255,0.3);
        }

        .search-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
        }

        .filter-select {
            padding: 12px 16px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(144,224,239,0.2);
            border-radius: var(--radius-sm);
            color: var(--white);
            font-family: 'Nunito', sans-serif;
            font-size: 0.9rem;
            outline: none;
            cursor: pointer;
            min-width: 150px;
        }

        .filter-select:focus {
            border-color: var(--sky);
        }

        .filter-select option {
            background: var(--navy);
            color: var(--white);
        }

        .category-section {
            margin-bottom: 40px;
        }

        .category-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .category-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .category-count {
            font-size: 0.85rem;
            color: var(--sky);
            font-weight: 500;
            padding: 4px 12px;
            background: rgba(0,180,216,0.1);
            border-radius: 15px;
        }

        .template-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 18px;
        }

        .template-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(144,224,239,0.14);
            border-radius: var(--radius-md);
            overflow: hidden;
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
        }

        .template-card:hover {
            transform: translateY(-4px);
            border-color: rgba(0,180,216,0.3);
            background: rgba(0,180,216,0.06);
        }

        .template-preview {
            height: 220px;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
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

        .template-preview-fallback.minimal {
            background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
        }

        .template-preview-fallback.creative {
            background: linear-gradient(135deg, #FF6B6B 0%, #4ECDC4 100%);
        }

        .template-preview-fallback.corporate {
            background: linear-gradient(135deg, #2C3E50 0%, #3498DB 100%);
        }

        .template-preview-fallback.modern {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .preview-content {
            padding: 20px;
            width: 75%;
        }

        .preview-line {
            height: 6px;
            border-radius: 3px;
            margin-bottom: 12px;
        }

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
            gap: 12px;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .template-card:hover .preview-overlay {
            opacity: 1;
        }

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
            transition: all 0.2s;
        }

        .preview-overlay-btn:hover {
            background: var(--ice);
            transform: scale(1.05);
        }

        .preview-overlay-btn.download {
            background: rgba(255,255,255,0.2);
            color: var(--white);
            border: 1px solid rgba(255,255,255,0.3);
        }

        .preview-overlay-btn.download:hover {
            background: rgba(255,255,255,0.3);
        }

        .template-info {
            padding: 20px;
        }

        .template-type {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            margin-bottom: 10px;
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
            margin-bottom: 8px;
            letter-spacing: -0.01em;
        }

        .template-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .template-downloads {
            font-size: 0.8rem;
            color: var(--text-muted);
            font-weight: 300;
        }

        .download-btn {
            width: 100%;
            padding: 10px;
            background: var(--sky);
            color: var(--navy);
            border: none;
            border-radius: var(--radius-sm);
            font-family: 'Nunito', sans-serif;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .download-btn:hover {
            background: var(--ice);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(0,180,216,0.3);
        }

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.8);
            z-index: 200;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(8px);
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: rgba(3,0,69,0.98);
            border: 1px solid rgba(144,224,239,0.2);
            border-radius: var(--radius-lg);
            padding: 30px;
            max-width: 900px;
            width: 95%;
            animation: fadeUp 0.3s ease;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }

        .preview-container {
            width: 100%;
            max-height: 65vh;
            overflow-y: auto;
            border-radius: 8px;
            background: white;
            margin-bottom: 20px;
        }

        .preview-container img {
            width: 100%;
            display: block;
        }

        .preview-container .no-preview {
            padding: 80px 40px;
            text-align: center;
            color: #666;
            font-size: 1.1rem;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn-modal {
            padding: 10px 24px;
            border-radius: var(--radius-sm);
            font-family: 'Nunito', sans-serif;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }

        .btn-modal-primary {
            background: var(--sky);
            color: var(--navy);
        }

        .btn-modal-primary:hover {
            background: var(--ice);
        }

        .btn-modal-secondary {
            background: transparent;
            border: 1px solid rgba(144,224,239,0.3);
            color: var(--white);
        }

        .btn-modal-secondary:hover {
            background: rgba(255,255,255,0.05);
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .3; }
        }

        @media (max-width: 768px) {
            .dash-nav { padding: 0 20px; }
            .dashboard-container { padding: 30px 20px; }
            .user-name { display: none; }
            .template-grid { grid-template-columns: 1fr; }
            .stats-strip { gap: 15px; }
            .filter-bar { flex-direction: column; }
        }

        @media (max-width: 480px) {
            .nav-actions { gap: 8px; }
            .btn-nav { padding: 6px 14px; font-size: 0.8rem; }
        }
    </style>
</head>
<body>
    <nav class="dash-nav">
        <a href="dashboard.php" class="nav-logo">
            Resumazing<span class="logo-dot"></span>
        </a>
        <div class="nav-actions">
            <div class="user-badge">
                <div class="user-avatar"><?php echo strtoupper(substr($firstName, 0, 1)); ?></div>
                <span class="user-name"><?php echo htmlspecialchars($firstName); ?></span>
            </div>
            <a href="../logout.php" class="btn-nav btn-nav-outline">Logout</a>
        </div>
    </nav>

    <div class="page-content">
        <div class="dashboard-container">
            <div class="welcome-section">
                <div class="welcome-badge">
                    <span class="pip"></span>
                    FREE TEMPLATES
                </div>
                <h1 class="welcome-heading">Find Your Perfect Resume Template</h1>
                <p class="welcome-sub">Browse our collection of professionally designed resume templates. Click to preview, then download the ones you love.</p>
            </div>

            <div class="stats-strip">
                <div class="stat-item">
                    <span class="stat-num"><?php echo $useFallback ? '200+' : number_format($stats['total']); ?></span>
                    <span class="stat-label">Free Templates</span>
                </div>
                <div class="stat-item">
                    <span class="stat-num"><?php echo $useFallback ? '50K+' : number_format($stats['total_downloads']); ?></span>
                    <span class="stat-label">Downloads</span>
                </div>
                <div class="stat-item">
                    <span class="stat-num"><?php echo $useFallback ? '8' : number_format($stats['total_categories']); ?></span>
                    <span class="stat-label">Categories</span>
                </div>
                <div class="stat-item">
                    <span class="stat-num">4.9★</span>
                    <span class="stat-label">User Rating</span>
                </div>
            </div>

            <div class="filter-bar">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search templates..." onkeyup="filterTemplates()">
                    <span class="search-icon">🔍</span>
                </div>
                <select class="filter-select" id="typeFilter" onchange="filterTemplates()">
                    <option value="all">All Types</option>
                    <option value="minimal">Minimal</option>
                    <option value="creative">Creative</option>
                    <option value="corporate">Corporate</option>
                    <option value="modern">Modern</option>
                </select>
            </div>

            <?php foreach($categories as $categoryName => $templates): ?>
            <div class="category-section">
                <div class="category-header">
                    <h2 class="category-title">
                        <?php 
                        $categoryEmoji = '';
                        foreach ($categoryEmojis as $key => $emoji) {
                            if (strpos($categoryName, $key) !== false) {
                                $categoryEmoji = $emoji;
                                break;
                            }
                        }
                        echo $categoryEmoji . ' ' . htmlspecialchars($categoryName); 
                        ?>
                    </h2>
                    <span class="category-count"><?php echo count($templates); ?> templates</span>
                </div>
                <div class="template-grid">
                    <?php foreach($templates as $template): 
                        $previewPath = !$useFallback ? getPreviewPath($template['file_path']) : null;
                        $templateId = $template['id'] ?? 0;
                        $templateName = $template['name'];
                        $templateType = $template['type'];
                        $templateDownloads = $template['downloads'];
                        $templateFile = $template['file_path'] ?? '';
                    ?>
                    <div class="template-card" 
                         data-name="<?php echo strtolower(htmlspecialchars($templateName)); ?>" 
                         data-type="<?php echo $templateType; ?>">
                        <div class="template-preview">
                            <?php if ($previewPath): ?>
                                <img src="<?php echo $previewPath; ?>" 
                                     alt="<?php echo htmlspecialchars($templateName); ?>" 
                                     class="template-preview-img"
                                     loading="lazy"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="template-preview-fallback <?php echo $templateType; ?>" style="display:none;">
                                    <div class="preview-content">
                                        <div class="preview-header"></div>
                                        <div class="preview-line short"></div>
                                        <div class="preview-line long"></div>
                                        <div class="preview-line medium"></div>
                                        <div class="preview-line short"></div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="template-preview-fallback <?php echo $templateType; ?>">
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
                                <button class="preview-overlay-btn" onclick="event.stopPropagation(); openPreview('<?php echo $previewPath; ?>', '<?php echo htmlspecialchars(addslashes($templateName)); ?>')">
                                    👁 View Preview
                                </button>
                                <!-- <button class="preview-overlay-btn download" onclick="event.stopPropagation(); downloadTemplate(<?php echo $templateId; ?>, '<?php echo htmlspecialchars(addslashes($templateName)); ?>', '<?php echo htmlspecialchars(addslashes($templateFile)); ?>')">
                                    📥 Download
                                </button> -->
                            </div>
                        </div>
                        <div class="template-info">
                            <span class="template-type type-<?php echo $templateType; ?>"><?php echo $templateType; ?></span>
                            <h3 class="template-name"><?php echo htmlspecialchars($templateName); ?></h3>
                            <div class="template-meta">
                                <span class="template-downloads">📥 <?php echo formatDownloads($templateDownloads); ?> downloads</span>
                            </div>
                            <button class="download-btn" onclick="downloadTemplate(<?php echo $templateId; ?>, '<?php echo htmlspecialchars(addslashes($templateName)); ?>', '<?php echo htmlspecialchars(addslashes($templateFile)); ?>')">
                                📥 Download Free
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="modal-overlay" id="previewModal">
        <div class="modal">
            <h3 id="previewTitle">Template Preview</h3>
            <div class="preview-container" id="previewContent"></div>
            <div class="modal-buttons">
                <button class="btn-modal btn-modal-secondary" onclick="closePreviewModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        function filterTemplates() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const typeFilter = document.getElementById('typeFilter').value;
            
            document.querySelectorAll('.template-card').forEach(card => {
                const name = card.getAttribute('data-name');
                const type = card.getAttribute('data-type');
                
                const matchesSearch = name.includes(searchTerm);
                const matchesType = typeFilter === 'all' || type === typeFilter;
                
                card.style.display = (matchesSearch && matchesType) ? '' : 'none';
            });
        }

        function openPreview(previewPath, templateName) {
            const modal = document.getElementById('previewModal');
            const content = document.getElementById('previewContent');
            document.getElementById('previewTitle').textContent = templateName;
            
            if (previewPath && previewPath.trim() !== '') {
                content.innerHTML = '<img src="' + previewPath + '" alt="' + templateName + '" style="width: 100%; display: block;" onerror="this.parentElement.innerHTML=\'<div class=\\\'no-preview\\\'><p>📄 Preview image could not be loaded</p><p style=\\\'font-size: 0.9rem; margin-top: 8px;\\\'>The preview may still be generating</p></div>\';">';
            } else {
                content.innerHTML = '<div class="no-preview"><p style="font-size: 1.5rem; margin-bottom: 10px;">📄</p><p>Preview not available yet</p><p style="font-size: 0.9rem; margin-top: 8px; color: #999;">Preview will appear here once generated by the admin</p></div>';
            }
            
            modal.classList.add('active');
        }

        function closePreviewModal() {
            document.getElementById('previewModal').classList.remove('active');
        }

        document.getElementById('previewModal').addEventListener('click', function(e) {
            if (e.target === this) closePreviewModal();
        });

        function downloadTemplate(id, name, filePath) {
            <?php if (!$useFallback): ?>
            fetch('../admin/ajax/download-template.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + id
            }).catch(() => {});
            
            window.location.href = 'download.php?file=' + encodeURIComponent(filePath);
            <?php else: ?>
            alert('Template download will be available once templates are uploaded by the admin.\n\nTemplate: ' + name);
            <?php endif; ?>
        }

        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.template-card, .stat-item').forEach((item, index) => {
            item.style.opacity = '0';
            item.style.transform = 'translateY(24px)';
            item.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            item.style.transitionDelay = `${index * 0.05}s`;
            observer.observe(item);
        });
    </script>
</body>
</html>