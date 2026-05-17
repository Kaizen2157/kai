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

// Template categories with their templates
$categories = [
    '🎓 Fresh Graduate' => [
        ['name' => 'Classic Graduate', 'type' => 'minimal', 'downloads' => '2.3k', 'rating' => 4.8, 'file' => 'classic-graduate.docx'],
        ['name' => 'Entry Level Pro', 'type' => 'modern', 'downloads' => '1.8k', 'rating' => 4.7, 'file' => 'entry-level-pro.docx'],
        ['name' => 'First Job Seeker', 'type' => 'creative', 'downloads' => '3.1k', 'rating' => 4.9, 'file' => 'first-job-seeker.docx'],
        ['name' => 'New Graduate Modern', 'type' => 'modern', 'downloads' => '2.7k', 'rating' => 4.8, 'file' => 'new-graduate-modern.docx'],
    ],
    '💼 Corporate / Business' => [
        ['name' => 'Executive Suite', 'type' => 'corporate', 'downloads' => '4.2k', 'rating' => 4.9, 'file' => 'executive-suite.docx'],
        ['name' => 'Business Professional', 'type' => 'minimal', 'downloads' => '3.5k', 'rating' => 4.8, 'file' => 'business-professional.docx'],
        ['name' => 'Corporate Clean', 'type' => 'corporate', 'downloads' => '2.9k', 'rating' => 4.7, 'file' => 'corporate-clean.docx'],
        ['name' => 'Management Track', 'type' => 'modern', 'downloads' => '2.1k', 'rating' => 4.6, 'file' => 'management-track.docx'],
    ],
    '🎨 Creative / Design' => [
        ['name' => 'Designer Portfolio', 'type' => 'creative', 'downloads' => '3.8k', 'rating' => 4.9, 'file' => 'designer-portfolio.docx'],
        ['name' => 'Artist CV', 'type' => 'creative', 'downloads' => '2.4k', 'rating' => 4.7, 'file' => 'artist-cv.docx'],
        ['name' => 'Creative Director', 'type' => 'modern', 'downloads' => '1.9k', 'rating' => 4.8, 'file' => 'creative-director.docx'],
        ['name' => 'Graphic Designer Pro', 'type' => 'creative', 'downloads' => '2.6k', 'rating' => 4.7, 'file' => 'graphic-designer-pro.docx'],
    ],
    '💻 Tech / Engineering' => [
        ['name' => 'Software Engineer', 'type' => 'modern', 'downloads' => '5.1k', 'rating' => 4.9, 'file' => 'software-engineer.docx'],
        ['name' => 'IT Professional', 'type' => 'corporate', 'downloads' => '3.2k', 'rating' => 4.8, 'file' => 'it-professional.docx'],
        ['name' => 'Developer Portfolio', 'type' => 'minimal', 'downloads' => '4.7k', 'rating' => 4.9, 'file' => 'developer-portfolio.docx'],
        ['name' => 'Data Scientist', 'type' => 'modern', 'downloads' => '2.8k', 'rating' => 4.7, 'file' => 'data-scientist.docx'],
    ],
    '🏥 Healthcare' => [
        ['name' => 'Nurse Practitioner', 'type' => 'minimal', 'downloads' => '2.1k', 'rating' => 4.8, 'file' => 'nurse-practitioner.docx'],
        ['name' => 'Medical Doctor', 'type' => 'corporate', 'downloads' => '1.8k', 'rating' => 4.7, 'file' => 'medical-doctor.docx'],
        ['name' => 'Healthcare Admin', 'type' => 'modern', 'downloads' => '1.5k', 'rating' => 4.6, 'file' => 'healthcare-admin.docx'],
    ],
    '📚 Academic / Research' => [
        ['name' => 'Academic CV', 'type' => 'minimal', 'downloads' => '2.4k', 'rating' => 4.8, 'file' => 'academic-cv.docx'],
        ['name' => 'Research Scientist', 'type' => 'corporate', 'downloads' => '1.9k', 'rating' => 4.7, 'file' => 'research-scientist.docx'],
        ['name' => 'Professor CV', 'type' => 'minimal', 'downloads' => '1.6k', 'rating' => 4.8, 'file' => 'professor-cv.docx'],
    ],
    '🛎️ Hospitality' => [
        ['name' => 'Hotel Management', 'type' => 'modern', 'downloads' => '1.4k', 'rating' => 4.6, 'file' => 'hotel-management.docx'],
        ['name' => 'Restaurant Manager', 'type' => 'creative', 'downloads' => '1.2k', 'rating' => 4.5, 'file' => 'restaurant-manager.docx'],
        ['name' => 'Customer Service Pro', 'type' => 'minimal', 'downloads' => '1.7k', 'rating' => 4.7, 'file' => 'customer-service-pro.docx'],
    ],
];
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

        /* Background Texture */
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

        /* NAV BAR */
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

        /* MAIN CONTENT */
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

        /* WELCOME SECTION */
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

        /* STATS STRIP */
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

        /* CATEGORY SECTION */
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

        /* TEMPLATE GRID */
        .template-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
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
            height: 200px;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .template-preview.minimal {
            background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
        }

        .template-preview.creative {
            background: linear-gradient(135deg, #FF6B6B 0%, #4ECDC4 100%);
        }

        .template-preview.corporate {
            background: linear-gradient(135deg, #2C3E50 0%, #3498DB 100%);
        }

        .template-preview.modern {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .preview-content {
            padding: 20px;
            width: 80%;
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

        .template-rating {
            font-size: 0.8rem;
            color: var(--sky);
            font-weight: 600;
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

        /* SEARCH AND FILTER */
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

        /* MODAL */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.7);
            z-index: 200;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: rgba(3,0,69,0.95);
            border: 1px solid rgba(144,224,239,0.2);
            border-radius: var(--radius-lg);
            padding: 30px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            animation: fadeUp 0.3s ease;
        }

        .modal h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }

        .modal p {
            color: var(--text-light);
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
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

        /* ANIMATIONS */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .3; }
        }

        /* RESPONSIVE */
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
    <!-- Navigation -->
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

    <!-- Main Content -->
    <div class="page-content">
        <div class="dashboard-container">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="welcome-badge">
                    <span class="pip"></span>
                    FREE TEMPLATES
                </div>
                <h1 class="welcome-heading">Find Your Perfect Resume Template</h1>
                <p class="welcome-sub">Browse our collection of 200+ professionally designed resume templates. All free to download — no editing tools, just download and use in your preferred editor.</p>
            </div>

            <!-- Stats -->
            <div class="stats-strip">
                <div class="stat-item">
                    <span class="stat-num">200+</span>
                    <span class="stat-label">Free Templates</span>
                </div>
                <div class="stat-item">
                    <span class="stat-num">50K+</span>
                    <span class="stat-label">Downloads</span>
                </div>
                <div class="stat-item">
                    <span class="stat-num">15+</span>
                    <span class="stat-label">Categories</span>
                </div>
                <div class="stat-item">
                    <span class="stat-num">4.9★</span>
                    <span class="stat-label">User Rating</span>
                </div>
            </div>

            <!-- Search and Filter -->
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

            <!-- Template Categories -->
            <?php foreach($categories as $categoryName => $templates): ?>
            <div class="category-section">
                <div class="category-header">
                    <h2 class="category-title"><?php echo $categoryName; ?></h2>
                    <span class="category-count"><?php echo count($templates); ?> templates</span>
                </div>
                <div class="template-grid">
                    <?php foreach($templates as $template): ?>
                    <div class="template-card" data-name="<?php echo strtolower($template['name']); ?>" data-type="<?php echo $template['type']; ?>">
                        <div class="template-preview <?php echo $template['type']; ?>">
                            <div class="preview-content">
                                <div class="preview-header"></div>
                                <div class="preview-line short"></div>
                                <div class="preview-line long"></div>
                                <div class="preview-line medium"></div>
                                <div class="preview-line short"></div>
                            </div>
                        </div>
                        <div class="template-info">
                            <span class="template-type type-<?php echo $template['type']; ?>"><?php echo $template['type']; ?></span>
                            <h3 class="template-name"><?php echo $template['name']; ?></h3>
                            <div class="template-meta">
                                <span class="template-downloads">📥 <?php echo $template['downloads']; ?> downloads</span>
                                <span class="template-rating">★ <?php echo $template['rating']; ?></span>
                            </div>
                            <button class="download-btn" onclick="downloadTemplate('<?php echo $template['name']; ?>', '<?php echo $template['file']; ?>')">
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

    <!-- Download Modal -->
    <div class="modal-overlay" id="downloadModal">
        <div class="modal">
            <h3>📥 Download Template</h3>
            <p id="modalMessage">Your template is ready for download!</p>
            <div class="modal-buttons">
                <button class="btn-modal btn-modal-primary" id="confirmDownload">
                    Download Now
                </button>
                <button class="btn-modal btn-modal-secondary" onclick="closeModal()">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <script>
        // Filter templates
        function filterTemplates() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const typeFilter = document.getElementById('typeFilter').value;
            
            document.querySelectorAll('.template-card').forEach(card => {
                const name = card.getAttribute('data-name');
                const type = card.getAttribute('data-type');
                
                const matchesSearch = name.includes(searchTerm);
                const matchesType = typeFilter === 'all' || type === typeFilter;
                
                if (matchesSearch && matchesType) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Download template
        function downloadTemplate(templateName, fileName) {
            const modal = document.getElementById('downloadModal');
            const message = document.getElementById('modalMessage');
            const confirmBtn = document.getElementById('confirmDownload');
            
            message.textContent = `"${templateName}" will be downloaded as a DOCX file. You can edit it in Microsoft Word or Google Docs.`;
            
            modal.classList.add('active');
            
            confirmBtn.onclick = function() {
                // For now, create a simple download simulation
                // In production, this would point to actual template files
                alert(`Downloading ${fileName}...\n\nIn production, this will download the actual template file from the server.`);
                closeModal();
                
                // Uncomment this line when you have actual template files:
                // window.location.href = `templates/${fileName}`;
            };
        }

        function closeModal() {
            document.getElementById('downloadModal').classList.remove('active');
        }

        // Close modal on overlay click
        document.getElementById('downloadModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Add fade-in animations on scroll
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