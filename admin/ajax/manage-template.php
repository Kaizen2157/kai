<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../../db.php';

$action = $_POST['action'] ?? '';
$templateId = intval($_POST['id'] ?? 0);

if ($templateId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid template ID']);
    exit();
}

// Get template info for file deletion
$template = $conn->query("SELECT * FROM templates WHERE id = $templateId")->fetch_assoc();

if (!$template) {
    echo json_encode(['success' => false, 'message' => 'Template not found']);
    exit();
}

switch ($action) {
    case 'update_status':
        $status = $_POST['status'] ?? '';
        if (!in_array($status, ['draft', 'active', 'archived'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit();
        }
        
        $stmt = $conn->prepare("UPDATE templates SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $templateId);
        
        if ($stmt->execute()) {
            $statusLabels = [
                'active' => 'published',
                'archived' => 'archived',
                'draft' => 'moved to drafts'
            ];
            echo json_encode(['success' => true, 'message' => 'Template ' . $statusLabels[$status] . ' successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating template status']);
        }
        break;
        
    case 'delete':
        // Delete the file
        $filePath = __DIR__ . '/../templates/files/' . $template['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Delete from database
        $stmt = $conn->prepare("DELETE FROM templates WHERE id = ?");
        $stmt->bind_param("i", $templateId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Template deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting template']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}