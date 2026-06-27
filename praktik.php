<?php
// praktik.php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';
requireLogin();

$pdo = getDB();
if ($pdo) {
    $list = $pdo->query("
        SELECT l.*, COUNT(CASE WHEN pp.status='aktif' THEN 1 END) AS jml_mhs
        FROM lokasi_praktik l
        LEFT JOIN penempatan_praktik pp ON l.id = pp.lokasi_id
        GROUP BY l.id ORDER BY l.status ASC, jml_mhs DESC
    ")->fetchAll();
    $stats = [
        'total'   => $pdo->query("SELECT COUNT(*) FROM lokasi_praktik")->fetchColumn(),
        'aktif'   => $pdo->query("SELECT COUNT(*) FROM lokasi_praktik WHERE status='aktif'")->fetchColumn(),
        'mhs'     => $pdo->query("SELECT COUNT(*) FROM penempatan_praktik WHERE status='aktif'")->fetchColumn(),
        'laporan' => 89,
    ];
} else {
    $list  = getDummyLokasi();
    $stats = ['total'=>34,'aktif'=>28,'mhs'=>412,'laporan'=>89];
}

$tipeData = ['rs_a'=>0,'rs_b'=>0,'rs_swasta'=>0,'puskesmas'=>0,'klinik'=>0];
foreach ($list as $l) {
    $t = $l['tipe'] ?? '';
    if (isset($tipeData[$t])) $tipeData[$t]++;
}
$kotaData = [];
foreach ($list as $l) {
    $k = $l['kota'] ?? 'Lainnya';
    $kotaData[$k] = ($kotaData[$k] ?? 0) + 1;
}
arsort($kotaData);

$pageTitle = 'Praktik Klinik'; $activePage = 'praktik';
include 'includes/layout_header.php';
?>

<div class="page-header">
  <div class="page-header-left"><h2>🏥 Praktik Klinik</h2><p>Data lokasi & penempatan praktik mahasiswa STIKES MNI</p></div>
  <div style="display:flex;gap:8px">
    <button class="btn-secondary">🗺️ Peta Lokasi</button>
    <?php if (hasRole('admin')): ?>
    <button class="btn-primary">➕ Tambah Lokasi</button>
    <?php endif; ?>
  </div>
</div>

<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px">
  <div class="stat-card teal" style="padding:16px 18px"><div class="stat-icon teal" style="margin-bottom:8px">🏥</div><div class="stat-value" style="font-size:26px"><?= $stats['total'] ?></div><div class="stat-label">Total Lokasi</div></div>
  <div class="stat-card green" style="padding:16px 18px"><div class="stat-icon green" style="margin-bottom:8px">✅</div><div class="stat-value" style="font-size:26px"><?= $stats['aktif'] ?></div><div class="stat-label">Aktif</div></div>
  <div class="stat-card gold" style="padding:16px 18px"><div class="stat-icon gold" style="margin-bottom:8px">👨‍⚕️</div><div class="stat-value" style="font-size:26px"><?= $stats['mhs'] ?></div><div class="stat-label">Mahasiswa Praktik</div></div>
  <div class="stat-card blue" style="padding:16px 18px"><div class="stat-icon blue" style="margin-bottom:8px">📋</div><div class="stat-value" style="font-size:26px"><?= $stats['laporan'] ?>%</div><div class="stat-label">Laporan Terisi</div></div>
</div>

<div class="two-col">
  <div class="card">
    <div class="card-header"><div class="card-title"><span>🏥</span> Daftar Lokasi Praktik</div></div>
    <div class="card-body">
      <table class="data-table">
        <thead><tr><th>Nama Instansi</th><th>Kota</th><th>Tipe</th><th>Kapasitas</th><th>Mhs</th><th>Periode</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($list as $l):
            $jml      = $l['jml_mhs'] ?? $l['mhs'] ?? 0;
            $kapasitas = $l['kapasitas'] ?? 0;
            $pct       = $kapasitas > 0 ? min(100, round($jml/$kapasitas*100)) : 0;
          ?>
          <tr>
            <td><strong style="font-size:13px;color:var(--text-dark)"><?= e($l['nama']) ?></strong></td>
            <td style="font-size:12px">📍 <?= e($l['kota']) ?></td>
            <td><?= badgeTipe($l['tipe']) ?></td>
            <td>
              <div style="font-size:12px;color:var(--text-light);margin-bottom:3px"><?= $jml ?>/<?= $kapasitas ?></div>
              <div class="progress-bar" style="width:80px"><div class="progress-fill teal" style="width:<?= $pct ?>%"></div></div>
            </td>
            <td style="font-family:'DM Mono',monospace;font-weight:600;color:var(--teal-mid)"><?= $jml ?></td>
            <td style="font-size:11px;color:var(--text-light)"><?= e($l['periode'] ?? 'Jan–Jun 2025') ?></td>
            <td><?= badgeStatus($l['status']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div>
    <div class="card" style="margin-bottom:16px">
      <div class="card-header"><div class="card-title"><span>📊</span> Sebaran Tipe Lokasi</div></div>
      <div class="card-body">
        <?php
        $tipeInfo = [
            'rs_a'      => ['label'=>'🏥 RS Tipe A',     'color'=>'red'],
            'rs_b'      => ['label'=>'🏥 RS Tipe B',     'color'=>'red'],
            'rs_swasta' => ['label'=>'🏨 RS Swasta',     'color'=>'blue'],
            'puskesmas' => ['label'=>'🏢 Puskesmas',     'color'=>'teal'],
            'klinik'    => ['label'=>'🏪 Klinik',        'color'=>'green'],
        ];
        $maxT = max(array_values($tipeData)) ?: 1;
        foreach ($tipeInfo as $key => $info):
          if ($tipeData[$key] === 0) continue;
          $pct = round($tipeData[$key]/$maxT*100);
        ?>
        <div style="margin-bottom:10px">
          <div style="display:flex;justify-content:space-between;margin-bottom:4px">
            <span style="font-size:13px;color:var(--text-mid)"><?= $info['label'] ?></span>
            <span style="font-family:'DM Mono',monospace;font-size:12px;font-weight:600"><?= $tipeData[$key] ?> lokasi</span>
          </div>
          <div class="progress-bar"><div class="progress-fill <?= $info['color'] ?>" style="width:<?= $pct ?>%"></div></div>
        </div>
        <?php endforeach; ?>

        <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--border)">
          <div style="font-size:12px;font-weight:600;color:var(--text-mid);margin-bottom:10px">Sebaran Kota</div>
          <div style="display:flex;flex-wrap:wrap;gap:8px">
            <?php $badgeCls=['badge-teal','badge-gold','badge-blue','badge-green','badge-purple'];
            $ci=0; foreach ($kotaData as $kota=>$jml): ?>
              <span class="badge <?= $badgeCls[$ci++%count($badgeCls)] ?>"><?= e($kota) ?> (<?= $jml ?>)</span>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title"><span>📋</span> Laporan & Peringatan</div></div>
      <div class="card-body">
        <div class="alert-list">
          <div class="alert-item warn">
            <div class="alert-icon">⚠️</div>
            <div><div class="alert-title">23 Laporan Belum Dikumpulkan</div><div class="alert-desc">RS Pertamedika · Tenggat 30 Juni 2025</div></div>
          </div>
          <div class="alert-item info">
            <div class="alert-icon">📄</div>
            <div><div class="alert-title">Verifikasi Supervisor Pending</div><div class="alert-desc">Puskesmas Garot · 8 mahasiswa</div></div>
          </div>
          <div class="alert-item success">
            <div class="alert-icon">✅</div>
            <div><div class="alert-title">RSUD Zainal Abidin 100%</div><div class="alert-desc">Semua laporan sudah diverifikasi</div></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/layout_footer.php'; ?>
