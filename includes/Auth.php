<?php

/**
 * Authentication Class
 * Handles user login, registration, and session management
 */

require_once dirname(__FILE__) . '/../config/database.php';

class Auth
{
    private $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    /**
     * Register new user
     *
     * @param string $username User username
     * @param string $email User email
     * @param string $password User password
     * @param string $full_name User full name
     * @param string|null $phone User phone number
     * @param string|null $address User address
     * @return array Result with success status and message
     */
    public function register($username, $email, $password, $full_name, $phone = null, $address = null)
    {
        try {
            // Validate input
            if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
                return ['success' => false, 'message' => 'Username, email, password, dan nama lengkap harus diisi!'];
            }

            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Format email tidak valid!'];
            }

            // Validate password length
            if (strlen($password) < 6) {
                return ['success' => false, 'message' => 'Password minimal 6 karakter!'];
            }

            // Check if user exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);

            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Username atau email sudah terdaftar!'];
            }

            // Hash password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Insert user
            $stmt = $this->db->prepare("
                INSERT INTO users (username, email, password, full_name, phone, address, role, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, 'user', NOW(), NOW())
            ");

            $result = $stmt->execute([$username, $email, $hashed_password, $full_name, $phone, $address]);

            if ($result) {
                return ['success' => true, 'message' => 'Registrasi berhasil! Silakan login dengan akun Anda.'];
            } else {
                return ['success' => false, 'message' => 'Gagal menyimpan data user!'];
            }
        } catch (PDOException $e) {
            error_log('Registration error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error database: ' . $e->getMessage()];
        } catch (Exception $e) {
            error_log('Registration exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Login user
     *
     * @param string $username Username or email
     * @param string $password Password
     * @return array Result with success status and message
     */
    public function login($username, $password)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ['success' => false, 'message' => 'Username atau email tidak ditemukan!'];
            }

            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'message' => 'Password salah!'];
            }

            // Create session
            $session_id = bin2hex(random_bytes(32));
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';

            // Save session to database
            $stmt = $this->db->prepare("
                INSERT INTO sessions (user_id, session_id, user_agent, ip_address)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$user['id'], $session_id, $user_agent, $ip_address]);

            // Set cookie (7 days)
            setcookie('user_id', $user['id'], time() + (7 * 24 * 60 * 60), '/');
            setcookie('username', $user['username'], time() + (7 * 24 * 60 * 60), '/');
            setcookie('session_id', $session_id, time() + (7 * 24 * 60 * 60), '/');

            return [
                'success' => true,
                'message' => 'Login berhasil!',
                'user_id' => $user['id'],
                'username' => $user['username']
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Verify session/cookie
     *
     * @return array|bool User data if session is valid, false otherwise
     */
    public function verifySession()
    {
        // Check if cookies exist
        if (!isset($_COOKIE['user_id']) || !isset($_COOKIE['session_id'])) {
            return false;
        }

        try {
            $user_id = $_COOKIE['user_id'];
            $session_id = $_COOKIE['session_id'];

            // Verify session in database
            $stmt = $this->db->prepare("
                SELECT s.*, u.username, u.full_name FROM sessions s
                JOIN users u ON s.user_id = u.id
                WHERE s.user_id = ? AND s.session_id = ?
            ");
            $stmt->execute([$user_id, $session_id]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$session) {
                return false;
            }

            // Check if session is expired (30 days)
            $last_activity = strtotime($session['last_activity']);
            if (time() - $last_activity > 30 * 24 * 60 * 60) {
                $this->logout();
                return false;
            }

            // Update last activity
            $stmt = $this->db->prepare("UPDATE sessions SET last_activity = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$session['id']]);

            return [
                'user_id' => $session['user_id'],
                'username' => $session['username'],
                'full_name' => $session['full_name']
            ];
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Logout user
     *
     * @return bool True if logout successful
     */
    public function logout()
    {
        try {
            if (isset($_COOKIE['session_id'])) {
                $stmt = $this->db->prepare("DELETE FROM sessions WHERE session_id = ?");
                $stmt->execute([$_COOKIE['session_id']]);
            }

            // Delete cookies
            setcookie('user_id', '', time() - 3600, '/');
            setcookie('username', '', time() - 3600, '/');
            setcookie('session_id', '', time() - 3600, '/');

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get user data
     *
     * @param int $user_id User ID
     * @return array|null User data or null if not found
     */
    public function getUserData($user_id)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
}
