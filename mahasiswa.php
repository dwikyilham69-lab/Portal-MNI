<?php
// mahasiswa.php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';
requireLogin();

$pdo    = getDB();
$search = trim($_GET['q'] ?? '');
$filter = $_GET['prodi'] ?? 'semua';
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;

// Aksi: Hapus
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id']) && $pdo) {
    $stmt = $pdo->prepare("DELETE FROM mahasiswa WHERE id = ?");
    $stmt->execute([(int)$_POST['hapus_id']]);
    header('Location: mahasiswa.php');
    exit;
}

// Ambil data
if ($pdo) {
    $where  = "1=1";
    $params = [];
    if ($search) { $where .= " AND (m.nama LIKE ? OR m.nim LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
    if ($filter !== 'semua') { $where .= " AND p.nama = ?"; $params[] = $filter; }

    $total = $pdo->prepare("SELECT COUNT(*) FROM mahasiswa m LEFT JOIN prodi p ON m.prodi_id=p.id WHERE $where");
    $total->execute($params);
    $totalRows = $total->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT m.*, p.nama AS prodi_nama 
        FROM mahasiswa m 
        LEFT JOIN prodi p ON m.prodi_id = p.id 
        WHERE $where 
        ORDER BY m.nim ASC 
        LIMIT $limit OFFSET $offset
    ");
    $stmt->execute($params);
    $mahasiswaList = $stmt->fetchAll();

    // Ambil daftar prodi untuk filter
    $prodiStmt = $pdo->query("SELECT * FROM prodi ORDER BY nama ASC");
    $prodiList = $prodiStmt->fetchAll();
} else {
    $mahasiswaList = [];
    $prodiList = [];
    $totalRows = 0;
}

$totalPages = ceil($totalRows / $limit);

include 'includes/layout_header.php';
?>

<div class="main-content">
  <div class="content-header">
    <div>
      <h1 class="content-title">Data Mahasiswa</h1>
      <p class="content-subtitle">Kelola informasi data mahasiswa STIKes</p>
    </div>
    <div style="display:flex;gap:8px">
      <a href="mahasiswa.php?export=1" class="btn-secondary">⬇️ Ekspor CSV</a>
      <?php if (hasRole('admin','keuangan')): ?>
        <button class="btn-primary" onclick="document.getElementById('modalTambah').style.display='flex'">➕ Tambah Mahasiswa</button>
      <?php endif; ?>
    </div>
  </div>

  <div class="filter-card">
    <form method="GET" action="" style="display:flex;gap:12px;flex-wrap:wrap;width:100%">
      <div style="flex:1;min-width:200px;position:relative">
        <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-light)">🔍</span>
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama atau NIM..." 
               style="width:100%;padding:10px 10px 10px 36px;border:1px solid var(--border-color);border-radius:6px;font-size:14px">
      </div>
      <div style="width:180px">
        <select name="prodi" style="width:100%;padding:10px;border:1px solid var(--border-color);border-radius:6px;font-size:14px;background-color:white">
          <option value="semua">Semua Prodi</option>
          <?php foreach ($prodiList as $p): ?>
            <option value="<?= htmlspecialchars($p['nama']) ?>" <?= $filter===$p['nama']?'selected':'' ?>><?= htmlspecialchars($p['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn-primary" style="padding:10px 20px">Filter</button>
      <?php if ($search || $filter !== 'semua'): ?>
        <a href="mahasiswa.php" class="btn-secondary" style="padding:10px 15px;text-decoration:none;display:inline-flex;align-items:center">Reset</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="table-container">
    <table class="data-table">
      <thead>
        <tr>
          <th>NIM</th>
          <th>Nama</th>
          <th>Program Studi</th>
          <th>Angkatan</th>
          <th>Email</th>
          <th>Telepon</th>
          <th>Status</th>
          <th style="text-align:center;width:100px">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($mahasiswaList)): ?>
          <tr>
            <td colspan="8" style="text-align:center;padding:32px;color:var(--text-light)">Tidak ada data mahasiswa ditemukan.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($mahasiswaList as $m): ?>
          <tr>
            <td style="font-weight:600;color:var(--text-main)"><?= htmlspecialchars($m['nim']) ?></td>
            <td>
              <div class="name-cell">
                <div class="avatar"><?= strtoupper(substr($m['nama'],0,1)) ?></div>
                <div>
                  <div style="font-weight:500;color:var(--text-main)"><?= htmlspecialchars($m['nama']) ?></div>
                </div>
              </div>
            </td>
            <td><?= htmlspecialchars($m['prodi_nama'] ?? '-') ?></td>
            <td><?= htmlspecialchars($m['angkatan'] ?? '-') ?></td>
            <td><?= htmlspecialchars($m['email'] ?? '-') ?></td>
            <td><?= htmlspecialchars($m['telepon'] ?? '-') ?></td>
            <td>
              <span class="status-badge <?= strtolower($m['status'] ?? 'aktif') === 'aktif' ? 'status-active' : 'status-inactive' ?>">
                <?= htmlspecialchars($m['status'] ?? 'Aktif') ?>
              </span>
            </td>
            <td style="text-align:center">
              <div style="display:flex;justify-content:center;gap:8px">
                <?php if (hasRole('admin')): ?>
                  <form method="POST" action="" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');" style="margin:0">
                    <input type="hidden" name="hapus_id" value="<?= $m['id'] ?>">
                    <button type="submit" style="background:none;border:none;cursor:pointer;font-size:14px">🗑️</button>
                  </form>
                <?php else: ?>
                  <button style="background:none;border:none;cursor:pointer;font-size:14px" title="Edit">✏️</button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>

    <div style="display:flex;justify-content:space-between;align-items:center;padding-top:16px;font-size:12px;color:var(--text-light)">
      <span>Menampilkan <?= $offset+1 ?>–<?= min($offset+$limit,$totalRows) ?> dari <?= number_format($totalRows) ?> mahasiswa</span>
      <div style="display:flex;gap:6px">
        <?php if ($page > 1): ?>
          <a href=\"?page=<?= $page-1 ?>&q=<?= urlencode($search) ?>&prodi=<?= urlencode($filter) ?>\" class=\"filter-btn\">‹ Prev</a>
        <?php endif; ?>
        <?php for ($p = max(1,$page-2); $p <= min($totalPages,$page+2); $p++): ?>
          <a href="?page=<?= $p ?>&q=<?= urlencode($search) ?>&prodi=<?= urlencode($filter) ?>"
             class="filter-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
          <a href="?page=<?= $page+1 ?>&q=<?= urlencode($search) ?>&prodi=<?= urlencode($filter) ?>" class="filter-btn">Next ›</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/layout_footer.php'; ?>