<?php

/**
 * Role-Based Access Middleware
 * Functions to control access based on user roles
 */

/**
 * Require admin access
 *
 * @return void Redirects to login or shows access denied
 */
function requireAdmin()
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        header('Location: ../index.php');
        exit();
    }

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        accessDenied("Admin access required");
    }
}

/**
 * Require user access (any logged in user)
 *
 * @return void Redirects to login if not authenticated
 */
function requireUser()
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        header('Location: ../index.php');
        exit();
    }
}

/**
 * Check if current user is admin
 *
 * @return bool True if user is admin
 */
function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Check if current user has specific role
 *
 * @param string $role Role to check
 * @return bool True if user has role
 */
function hasRole($role)
{
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Display access denied message
 *
 * @param string $message Custom error message
 * @return void Outputs HTML and exits
 */
function accessDenied($message = "Access Denied")
{
    http_response_code(403);
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Access Denied</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                text-align: center;
                padding: 50px;
                background: #f8f9fa;
            }
            .error-container {
                max-width: 500px;
                margin: 0 auto;
                background: white;
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .error-icon {
                font-size: 48px;
                color: #e74c3c;
                margin-bottom: 20px;
            }
            .error-title {
                color: #e74c3c;
                font-size: 24px;
                margin-bottom: 10px;
            }
            .error-message {
                color: #666;
                font-size: 16px;
                margin-bottom: 30px;
            }
            .back-link {
                display: inline-block;
                padding: 10px 20px;
                background: #3498db;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                transition: background 0.3s;
            }
            .back-link:hover {
                background: #2980b9;
            }
        </style>
    </head>
    <body>
        <div class='error-container'>
            <div class='error-icon'>🚫</div>
            <div class='error-title'>Access Denied</div>
            <div class='error-message'>$message</div>
            <a href='../pages/dashboard.php' class='back-link'>← Back to Dashboard</a>
        </div>
    </body>
    </html>";
    exit();
}

/**
 * Get current user info
 *
 * @return array|null User data or null
 */
function getCurrentUser()
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Show admin-only content
 *
 * @param string $content HTML content to display
 * @return void
 */
function adminOnly($content)
{
    if (isAdmin()) {
        echo $content;
    }
}

/**
 * Show user-only content
 *
 * @param string $content HTML content to display
 * @return void
 */
function userOnly($content)
{
    if (!isAdmin()) {
        echo $content;
    }
}

/**
 * Show content based on role
 *
 * @param string $adminContent Admin HTML content
 * @param string $userContent User HTML content
 * @return void
 */
function roleBased($adminContent, $userContent = '')
{
    if (isAdmin()) {
        echo $adminContent;
    } else {
        echo $userContent;
    }
}