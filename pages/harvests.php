<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireLogin();

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create'])) {
        $pond_id = $_POST['pond_id'];
        $weight = $_POST['weight'];
        $date = $_POST['date'];
        $notes = $_POST['notes'];

        $stmt = $conn->prepare("INSERT INTO harvests (pond_id, weight, date, notes) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("idss", $pond_id, $weight, $date, $notes);
        if ($stmt->execute()) {
            $message = 'Data panen berhasil ditambahkan!';
        } else {
            $message = 'Error: ' . $stmt->error;
        }
    } elseif (isset($_POST['update'])) {
        $id = $_POST['id'];
        $pond_id = $_POST['pond_id'];
        $weight = $_POST['weight'];
        $date = $_POST['date'];
        $notes = $_POST['notes'];

        $stmt = $conn->prepare("UPDATE harvests SET pond_id=?, weight=?, date=?, notes=? WHERE id=?");
        $stmt->bind_param("idssi", $pond_id, $weight, $date, $notes, $id);
        if ($stmt->execute()) {
            $message = 'Data panen berhasil diupdate!';
        } else {
            $message = 'Error: ' . $stmt->error;
        }
    } elseif (isset($_POST['delete'])) {
        $id = $_POST['id'];

        $stmt = $conn->prepare("DELETE FROM harvests WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = 'Data panen berhasil dihapus!';
        } else {
            $message = 'Error: ' . $stmt->error;
        }
    }
}

$harvests = $conn->query("SELECT h.*, p.name as pond_name FROM harvests h JOIN ponds p ON h.pond_id = p.id ORDER BY h.date DESC");
$ponds = $conn->query("SELECT * FROM ponds");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Panen - Sistem Peternakan Lele</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../dashboard.php">Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="ponds.php">Kolam</a></li>
                    <li class="nav-item"><a class="nav-link" href="feeds.php">Pakan</a></li>
                    <li class="nav-item"><a class="nav-link active" href="harvests.php">Panen</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php">Laporan</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="../logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Manajemen Panen</h1>

        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>

        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createModal">Tambah Data Panen</button>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Kolam</th>
                        <th>Berat (kg)</th>
                        <th>Tanggal</th>
                        <th>Catatan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $harvests->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['pond_name']; ?></td>
                            <td><?php echo $row['weight']; ?></td>
                            <td><?php echo $row['date']; ?></td>
                            <td><?php echo $row['notes']; ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick="editHarvest(<?php echo $row['id']; ?>, <?php echo $row['pond_id']; ?>, <?php echo $row['weight']; ?>, '<?php echo $row['date']; ?>', '<?php echo addslashes($row['notes']); ?>')">Edit</button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="delete" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus?')">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Modal -->
    <div class="modal fade" id="createModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Data Panen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Kolam</label>
                            <select class="form-control" name="pond_id" required>
                                <option value="">Pilih Kolam</option>
                                <?php $ponds->data_seek(0); while ($pond = $ponds->fetch_assoc()): ?>
                                    <option value="<?php echo $pond['id']; ?>"><?php echo $pond['name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Berat (kg)</label>
                            <input type="number" step="0.01" class="form-control" name="weight" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal</label>
                            <input type="date" class="form-control" name="date" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="create" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Data Panen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="editId">
                        <div class="mb-3">
                            <label class="form-label">Kolam</label>
                            <select class="form-control" name="pond_id" id="editPondId" required>
                                <?php $ponds->data_seek(0); while ($pond = $ponds->fetch_assoc()): ?>
                                    <option value="<?php echo $pond['id']; ?>"><?php echo $pond['name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Berat (kg)</label>
                            <input type="number" step="0.01" class="form-control" name="weight" id="editWeight" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal</label>
                            <input type="date" class="form-control" name="date" id="editDate" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea class="form-control" name="notes" id="editNotes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="update" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function editHarvest(id, pondId, weight, date, notes) {
            document.getElementById('editId').value = id;
            document.getElementById('editPondId').value = pondId;
            document.getElementById('editWeight').value = weight;
            document.getElementById('editDate').value = date;
            document.getElementById('editNotes').value = notes;
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>