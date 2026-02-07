<?php

/**
 * Database Handlers for CRUD Operations
 * Manages Pond, Fish Inventory, Feed Management, and Health Monitoring data
 */

require_once dirname(__FILE__) . '/../config/database.php';

class PondManager
{
    private $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    /**
     * Create new pond
     *
     * @param int $user_id User ID
     * @param string $pond_name Pond name
     * @param string $location Pond location
     * @param string $size_area Pond size
     * @param int $capacity Pond capacity
     * @param string|null $water_source Water source
     * @param string|null $notes Additional notes
     * @return array Result with success status
     */
    public function createPond($user_id, $pond_name, $location, $size_area, $capacity, $water_source = null, $notes = null)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO ponds (user_id, pond_name, location, size_area, capacity, water_source, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $pond_name, $location, $size_area, $capacity, $water_source, $notes]);
            return ['success' => true, 'message' => 'Kolam berhasil ditambahkan!', 'id' => $this->db->lastInsertId()];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get all ponds for user
     *
     * @param int $user_id User ID
     * @return array Array of pond records
     */
    public function getPonds($user_id)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM ponds WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get single pond
     *
     * @param int $pond_id Pond ID
     * @param int $user_id User ID
     * @return array|null Pond record or null
     */
    public function getPond($pond_id, $user_id)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM ponds WHERE id = ? AND user_id = ?");
            $stmt->execute([$pond_id, $user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Update pond
     *
     * @param int $pond_id Pond ID
     * @param int $user_id User ID
     * @param string $pond_name Pond name
     * @param string $location Pond location
     * @param string $size_area Pond size
     * @param int $capacity Pond capacity
     * @param string|null $water_source Water source
     * @param string|null $status Pond status
     * @param string|null $notes Additional notes
     * @return array Result with success status
     */
    public function updatePond($pond_id, $user_id, $pond_name, $location, $size_area, $capacity, $water_source = null, $status = null, $notes = null)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE ponds SET 
                pond_name = ?, location = ?, size_area = ?, capacity = ?, 
                water_source = ?, status = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$pond_name, $location, $size_area, $capacity, $water_source, $status, $notes, $pond_id, $user_id]);
            return ['success' => true, 'message' => 'Kolam berhasil diperbarui!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Delete pond
     *
     * @param int $pond_id Pond ID
     * @param int $user_id User ID
     * @return array Result with success status
     */
    public function deletePond($pond_id, $user_id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM ponds WHERE id = ? AND user_id = ?");
            $stmt->execute([$pond_id, $user_id]);
            return ['success' => true, 'message' => 'Kolam berhasil dihapus!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

class FishInventoryManager
{
    private $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function create($user_id, $pond_id, $species, $quantity, $size = null, $age_days = null, $health_status = 'healthy', $notes = null)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO fish_inventory (user_id, pond_id, species, quantity, size, age_days, health_status, notes, entry_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE())
            ");
            $stmt->execute([$user_id, $pond_id, $species, $quantity, $size, $age_days, $health_status, $notes]);
            return ['success' => true, 'message' => 'Ikan berhasil ditambahkan!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getAll($user_id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT f.*, p.pond_name FROM fish_inventory f
                JOIN ponds p ON f.pond_id = p.id
                WHERE f.user_id = ? ORDER BY f.created_at DESC
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getOne($id, $user_id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM fish_inventory WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$id, $user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }

    public function update($id, $user_id, $species, $quantity, $size = null, $age_days = null, $health_status = null, $notes = null)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE fish_inventory SET 
                species = ?, quantity = ?, size = ?, age_days = ?, health_status = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$species, $quantity, $size, $age_days, $health_status, $notes, $id, $user_id]);
            return ['success' => true, 'message' => 'Data ikan berhasil diperbarui!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function delete($id, $user_id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM fish_inventory WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            return ['success' => true, 'message' => 'Data ikan berhasil dihapus!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

class FeedManager
{
    private $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function create($user_id, $pond_id, $feed_type, $quantity_kg, $feed_date, $time_fed = null, $cost = null, $supplier = null, $notes = null)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO feed_management (user_id, pond_id, feed_type, quantity_kg, feed_date, time_fed, cost, supplier, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $pond_id, $feed_type, $quantity_kg, $feed_date, $time_fed, $cost, $supplier, $notes]);
            return ['success' => true, 'message' => 'Pakan berhasil ditambahkan!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getAll($user_id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT f.*, p.pond_name FROM feed_management f
                JOIN ponds p ON f.pond_id = p.id
                WHERE f.user_id = ? ORDER BY f.feed_date DESC
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getOne($id, $user_id)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM feed_management WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }

    public function update($id, $user_id, $feed_type, $quantity_kg, $feed_date, $time_fed = null, $cost = null, $supplier = null, $notes = null)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE feed_management SET 
                feed_type = ?, quantity_kg = ?, feed_date = ?, time_fed = ?, cost = ?, supplier = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$feed_type, $quantity_kg, $feed_date, $time_fed, $cost, $supplier, $notes, $id, $user_id]);
            return ['success' => true, 'message' => 'Data pakan berhasil diperbarui!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function delete($id, $user_id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM feed_management WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            return ['success' => true, 'message' => 'Data pakan berhasil dihapus!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

class HealthManager
{
    private $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function create($user_id, $pond_id, $fish_count, $health_status, $symptoms = null, $treatment_given = null, $treatment_date, $notes = null)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO health_monitoring (user_id, pond_id, fish_count, health_status, symptoms, treatment_given, treatment_date, monitoring_date, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), ?)
            ");
            $stmt->execute([$user_id, $pond_id, $fish_count, $health_status, $symptoms, $treatment_given, $treatment_date, $notes]);
            return ['success' => true, 'message' => 'Monitoring kesehatan berhasil ditambahkan!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getAll($user_id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT h.*, p.pond_name FROM health_monitoring h
                JOIN ponds p ON h.pond_id = p.id
                WHERE h.user_id = ? ORDER BY h.treatment_date DESC
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getOne($id, $user_id)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM health_monitoring WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }

    public function update($id, $user_id, $fish_count, $health_status, $symptoms = null, $treatment_given = null, $treatment_date, $notes = null)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE health_monitoring SET 
                fish_count = ?, health_status = ?, symptoms = ?, treatment_given = ?, treatment_date = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$fish_count, $health_status, $symptoms, $treatment_given, $treatment_date, $notes, $id, $user_id]);
            return ['success' => true, 'message' => 'Data monitoring berhasil diperbarui!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function delete($id, $user_id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM health_monitoring WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            return ['success' => true, 'message' => 'Data monitoring berhasil dihapus!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
