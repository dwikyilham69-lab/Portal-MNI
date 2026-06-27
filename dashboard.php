<?php
// dashboard.php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';
requireLogin();

$pdo   = getDB();
$stats = getDummyStats();

// Ambil dari DB jika tersedia
if ($pdo) {
    $stats['total_mahasiswa']  = $pdo->query("SELECT COUNT(*) FROM mahasiswa")->fetchColumn();
    $stats['mahasiswa_aktif']  = $pdo->query("SELECT COUNT(*) FROM mahasiswa WHERE status='aktif'")->fetchColumn();
    $stats['total_dosen']      = $pdo->query("SELECT COUNT(*) FROM dosen")->fetchColumn();
    $stats['dosen_aktif']      = $pdo->query("SELECT COUNT(*) FROM dosen WHERE status='aktif'")->fetchColumn();
    $stats['lokasi_praktik']   = $pdo->query("SELECT COUNT(*) FROM lokasi_praktik")->fetchColumn();
    $row = $pdo->query("SELECT SUM(nominal) AS total FROM transaksi_keuangan WHERE tipe='masuk' AND MONTH(tanggal)=MONTH(NOW()) AND YEAR(tanggal)=YEAR(NOW())")->fetch();
    $stats['pendapatan_bulan'] = $row['total'] ?? 0;

    $mahasiswaList = $pdo->query("
        SELECT m.*, p.nama AS prodi FROM mahasiswa m
        LEFT JOIN prodi p ON m.prodi_id = p.id
        ORDER BY m.created_at DESC LIMIT 6
    ")->fetchAll();

    $lokasiList = $pdo->query("
        SELECT l.*, COUNT(pp.id) AS jml_mhs
        FROM lokasi_praktik l
        LEFT JOIN penempatan_praktik pp ON l.id = pp.lokasi_id AND pp.status='aktif'
        GROUP BY l.id ORDER BY jml_mhs DESC LIMIT 5
    ")->fetchAll();

    $pengumuman = $pdo->query("SELECT * FROM pengumuman WHERE aktif=1 ORDER BY created_at DESC LIMIT 4")->fetchAll();
    $keuBulanan = getDummyKeuanganBulanan();
} else {
    $mahasiswaList = getDummyMahasiswa();
    foreach ($mahasiswaList as &$m) { if (!isset($m['email'])) $m['email'] = strtolower(str_replace(' ','.',explode(' ',$m['nama'])[0])).'@stikesmni.ac.id'; }
    unset($m);
    $mahasiswaList = array_slice($mahasiswaList, 0, 6);
    $lokasiList    = getDummyLokasi();
    $pengumuman    = getDummyPengumuman();
    $keuBulanan    = getDummyKeuanganBulanan();
}

$distribusiProdi = [
    ['nama'=>'Keperawatan',    'jml'=>512, 'pct'=>41, 'color'=>'#0d6e6e'],
    ['nama'=>'Kebidanan',      'jml'=>265, 'pct'=>21, 'color'=>'#c9923a'],
    ['nama'=>'Farmasi',        'jml'=>198, 'pct'=>16, 'color'=>'#4a90d9'],
    ['nama'=>'Analis Kes.',    'jml'=>157, 'pct'=>13, 'color'=>'#8b6fcb'],
    ['nama'=>'Fisioterapi',    'jml'=>116, 'pct'=>9,  'color'=>'#2ecc8a'],
];
$agenda = [
    ['judul'=>'Ujian Akhir Semester (UAS)',  'tanggal'=>'01 – 15 Jul 2026', 'info'=>'Semua Prodi',   'color'=>'var(--teal-bright)'],
    ['judul'=>'Pengumuman Kelulusan',         'tanggal'=>'25 Jul 2026',       'info'=>'Angkatan 2020', 'color'=>'var(--gold)'],
    ['judul'=>'Wisuda Periode III',           'tanggal'=>'12 Agt 2026',       'info'=>'Auditorium',    'color'=>'var(--green-soft)'],
    ['judul'=>'Penerimaan Mahasiswa Baru',    'tanggal'=>'Agustus 2026',      'info'=>'Semua Prodi',   'color'=>'var(--blue-soft)'],
    ['judul'=>'Awal Semester Ganjil 2025/26','tanggal'=>'01 Sep 2026',        'info'=>'Semua',         'color'=>'var(--purple-soft)'],
];

$pageTitle  = 'Dashboard — Ringkasan Akademik';
$activePage = 'dashboard';
include 'includes/layout_header.php';
?>

<!-- ── STAT CARDS ── -->
<div class="stats-grid">
  <div class="stat-card teal">
    <div class="stat-card-header">
      <div class="stat-icon teal">🎓</div>
      <span class="stat-trend up">↑ +42</span>
    </div>
    <div class="stat-value"><?= number_format($stats['total_mahasiswa']) ?></div>
    <div class="stat-label">Total Mahasiswa</div>
    <div class="stat-sub">👥 RS · Puskesmas · Klinik</div>
  </div>
  <div class="stat-card gold">
    <div class="stat-card-header">
      <div class="stat-icon gold">👨‍🏫</div>
      <span class="stat-trend up">↑ <?= $stats['dosen_aktif'] ?> aktif</span>
    </div>
    <div class="stat-value"><?= $stats['total_dosen'] ?></div>
    <div class="stat-label">Total Dosen</div>
    <div class="stat-sub">📖 <?= $stats['dosen_aktif'] ?> aktif mengajar</div>
  </div>
  <div class="stat-card green">
    <div class="stat-card-header">
      <div class="stat-icon green">🏥</div>
      <span class="stat-trend up">↑ +3 baru</span>
    </div>
    <div class="stat-value"><?= $stats['lokasi_praktik'] ?></div>
    <div class="stat-label">Lokasi Praktik</div>
    <div class="stat-sub">📍 Banda Aceh · Sigli · Medan</div>
  </div>
  <div class="stat-card blue">
    <div class="stat-card-header">
      <div class="stat-icon blue">💰</div>
      <span class="stat-trend up">↑ +8%</span>
    </div>
    <div class="stat-value"><?= formatRupiah($stats['pendapatan_bulan']) ?></div>
    <div class="stat-label">Pendapatan Bulan Ini</div>
    <div class="stat-sub">📈 vs bulan lalu +8%</div>
  </div>
</div>

<!-- ── ROW 1: Tabel + Klinik ── -->
<div class="two-col">
  <div class="card">
    <div class="card-header">
      <div class="card-title"><span>🎓</span> Mahasiswa Aktif Terbaru</div>
      <a class="card-action" href="mahasiswa.php">Lihat semua →</a>
    </div>
    <div class="card-body">
      <table class="data-table">
        <thead><tr><th>Nama</th><th>NIM</th><th>Prodi</th><th>Smt</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($mahasiswaList as $m):
            $ac  = avatarColor($m['nama']);
            $ini = initials($m['nama']);
          ?>
          <tr>
            <td>
              <div class="avatar-cell">
                <div class="avatar-sm" style="background:<?= $ac['bg'] ?>;color:<?= $ac['fg'] ?>">
                  <?= $ini ?>
                </div>
                <div class="name-cell"><?= e($m['nama']) ?></div>
              </div>
            </td>
            <td style="font-family:'DM Mono',monospace;font-size:12px"><?= e($m['nim']) ?></td>
            <td><?= badgeProdi($m['prodi'] ?? '') ?></td>
            <td><?= e($m['semester']) ?></td>
            <td><?= badgeStatus($m['status']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-title"><span>🏥</span> Praktik Klinik</div>
      <a class="card-action" href="praktik.php">Detail →</a>
    </div>
    <div class="card-body">
      <div class="clinic-list">
        <?php foreach (array_slice($lokasiList, 0, 5) as $l):
          $jml = $l['jml_mhs'] ?? $l['mhs'] ?? 0;
        ?>
        <div class="clinic-item">
          <div class="clinic-dot <?= e($l['status']) ?>"></div>
          <div class="clinic-info">
            <div class="clinic-name"><?= e($l['nama']) ?></div>
            <div class="clinic-meta">📍 <?= e($l['kota']) ?></div>
          </div>
          <div class="clinic-count"><?= $jml ?> mhs</div>
          <?= badgeStatus($l['status']) ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- ── ROW 2: Chart + Donut ── -->
<div class="two-col-equal">
  <div class="card">
    <div class="card-header">
      <div class="card-title"><span>📈</span> Tren Keuangan 6 Bulan</div>
      <a class="card-action" href="keuangan.php">Laporan →</a>
    </div>
    <div class="card-body">
      <canvas id="finChart" height="100"></canvas>
      <div style="display:flex;gap:16px;margin-top:10px">
        <div style="display:flex;align-items:center;gap:6px;font-size:11px;color:var(--text-light)"><div style="width:10px;height:10px;background:var(--teal-mid);border-radius:2px"></div>Pemasukan</div>
        <div style="display:flex;align-items:center;gap:6px;font-size:11px;color:var(--text-light)"><div style="width:10px;height:10px;background:var(--gold);border-radius:2px"></div>Pengeluaran</div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-title"><span>📊</span> Distribusi Mahasiswa per Prodi</div>
    </div>
    <div class="card-body">
      <div class="donut-wrap">
        <svg width="120" height="120" viewBox="0 0 120 120">
          <circle cx="60" cy="60" r="45" fill="none" stroke="#e8f5f5" stroke-width="22"/>
          <circle cx="60" cy="60" r="45" fill="none" stroke="#0d6e6e" stroke-width="22" stroke-dasharray="127 155" stroke-dashoffset="35"/>
          <circle cx="60" cy="60" r="45" fill="none" stroke="#c9923a" stroke-width="22" stroke-dasharray="66 216" stroke-dashoffset="-92"/>
          <circle cx="60" cy="60" r="45" fill="none" stroke="#4a90d9" stroke-width="22" stroke-dasharray="50 232" stroke-dashoffset="-158"/>
          <circle cx="60" cy="60" r="45" fill="none" stroke="#8b6fcb" stroke-width="22" stroke-dasharray="39 243" stroke-dashoffset="-208"/>
          <text x="60" y="56" text-anchor="middle" font-size="16" font-weight="700" fill="#0d2e2e" font-family="Playfair Display"><?= number_format($stats['total_mahasiswa']) ?></text>
          <text x="60" y="70" text-anchor="middle" font-size="9" fill="#7aa0a0" font-family="DM Sans">Mahasiswa</text>
        </svg>
        <div class="donut-legend">
          <?php foreach ($distribusiProdi as $d): ?>
          <div class="donut-item">
            <div class="donut-dot" style="background:<?= $d['color'] ?>"></div>
            <div class="donut-label"><?= e($d['nama']) ?></div>
            <div class="donut-val"><?= $d['jml'] ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ── ROW 3: Notif + Agenda ── -->
<div class="two-col-equal">
  <div class="card">
    <div class="card-header"><div class="card-title"><span>🔔</span> Notifikasi & Pengumuman</div></div>
    <div class="card-body">
      <div class="alert-list">
        <?php foreach ($pengumuman as $p):
          $icons = ['warning'=>'⚠️','info'=>'ℹ️','success'=>'✅','danger'=>'🚨'];
        ?>
        <div class="alert-item <?= e($p['tipe']) ?>">
          <div class="alert-icon"><?= $icons[$p['tipe']] ?? 'ℹ️' ?></div>
          <div>
            <div class="alert-title"><?= e($p['judul']) ?></div>
            <div class="alert-desc"><?= e($p['isi']) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title"><span>📅</span> Agenda Akademik</div></div>
    <div class="card-body">
      <div class="timeline">
        <?php foreach ($agenda as $a): ?>
        <div class="timeline-item">
          <div class="timeline-dot" style="background:<?= $a['color'] ?>"></div>
          <div class="timeline-title"><?= e($a['judul']) ?></div>
          <div class="timeline-meta"><?= e($a['tanggal']) ?> · <?= e($a['info']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<script>
// Chart Keuangan (Canvas)
(function() {
  var ctx = document.getElementById('finChart');
  if (!ctx) return;
  var pemasukan   = <?= json_encode($keuBulanan['pemasukan']) ?>;
  var pengeluaran = <?= json_encode($keuBulanan['pengeluaran']) ?>;
  var labels      = <?= json_encode($keuBulanan['labels']) ?>;
  drawBarChart(ctx, labels, pemasukan, pengeluaran);
})();
</script>

<?php include 'includes/layout_footer.php'; ?>
