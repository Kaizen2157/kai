<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../../db.php';

$action = $_POST['action'] ?? '';
$userId = intval($_POST['id'] ?? 0);

if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}

// Prevent admin from modifying themselves
if ($userId == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'You cannot modify your own account']);
    exit();
}

switch ($action) {
    case 'promote':
        $stmt = $conn->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
        $stmt->bind_param("i", $userId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User promoted to admin successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error promoting user']);
        }
        break;
        
    case 'demote':
        $stmt = $conn->prepare("UPDATE users SET is_admin = 0 WHERE id = ?");
        $stmt->bind_param("i", $userId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Admin demoted to user successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error demoting user']);
        }
        break;
        
    case 'delete':
        // Check if user has templates
        $checkTemplates = $conn->prepare("SELECT COUNT(*) as count FROM templates WHERE uploaded_by = ?");
        $checkTemplates->bind_param("i", $userId);
        $checkTemplates->execute();
        $result = $checkTemplates->get_result()->fetch_assoc();
        
        if ($result['count'] > 0) {
            // Update templates to remove the user reference
            $conn->query("UPDATE templates SET uploaded_by = NULL WHERE uploaded_by = $userId");
        }
        
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting user']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}