<?php
// dosen.php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';
requireLogin();

$pdo    = getDB();
$search = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 8; $offset = ($page - 1) * $limit;

if ($pdo) {
    $where  = "1=1"; $params = [];
    if ($search) { $where .= " AND (d.nama LIKE ? OR d.nip LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
    $total    = $pdo->prepare("SELECT COUNT(*) FROM dosen d WHERE $where");
    $total->execute($params);
    $totalRows = $total->fetchColumn();
    $stmt = $pdo->prepare("SELECT d.*, p.nama AS prodi FROM dosen d LEFT JOIN prodi p ON d.prodi_id=p.id WHERE $where ORDER BY d.rating DESC LIMIT $limit OFFSET $offset");
    $stmt->execute($params);
    $list = $stmt->fetchAll();
    $stats = [
        'total'   => $pdo->query("SELECT COUNT(*) FROM dosen")->fetchColumn(),
        'aktif'   => $pdo->query("SELECT COUNT(*) FROM dosen WHERE status='aktif'")->fetchColumn(),
        's3'      => $pdo->query("SELECT COUNT(*) FROM dosen WHERE pendidikan='S3'")->fetchColumn(),
        's2'      => $pdo->query("SELECT COUNT(*) FROM dosen WHERE pendidikan='S2'")->fetchColumn(),
    ];
    $perProdi = $pdo->query("SELECT p.nama, COUNT(d.id) AS jml FROM prodi p LEFT JOIN dosen d ON p.id=d.prodi_id GROUP BY p.id ORDER BY jml DESC")->fetchAll();
} else {
    $allData  = getDummyDosen();
    if ($search) $allData = array_filter($allData, fn($d) => stripos($d['nama'],$search)!==false || stripos($d['nip'],$search)!==false);
    $allData   = array_values($allData);
    $totalRows = count($allData);
    $list      = array_slice($allData, $offset, $limit);
    $stats     = ['total'=>87,'aktif'=>72,'s3'=>18,'s2'=>54];
    $perProdi  = [
        ['nama'=>'Keperawatan',    'jml'=>28],
        ['nama'=>'Kebidanan',      'jml'=>18],
        ['nama'=>'Farmasi',        'jml'=>16],
        ['nama'=>'Analis Kesehatan','jml'=>14],
        ['nama'=>'Fisioterapi',    'jml'=>11],
    ];
}
$totalPages = max(1, ceil($totalRows / $limit));
$maxJml = max(array_column($perProdi, 'jml'));

$jabatanLabel = [
    'asisten_ahli' => 'Asisten Ahli',
    'lektor'       => 'Lektor',
    'lektor_kepala'=> 'Lektor Kepala',
    'guru_besar'   => 'Guru Besar',
];
$jabatanBadge = [
    'asisten_ahli' => 'badge-teal',
    'lektor'       => 'badge-teal',
    'lektor_kepala'=> 'badge-gold',
    'guru_besar'   => 'badge-red',
];

$pageTitle = 'Data Dosen'; $activePage = 'dosen';
include 'includes/layout_header.php';
?>

<div class="page-header">
  <div class="page-header-left"><h2>👨‍🏫 Data Dosen</h2><p>Manajemen sumber daya pengajar STIKES MNI · Total: <strong><?= $stats['total'] ?></strong> dosen</p></div>
  <div style="display:flex;gap:8px">
    <button class="btn-secondary">⬇️ Ekspor CSV</button>
    <?php if (hasRole('admin')): ?>
    <button class="btn-primary">➕ Tambah Dosen</button>
    <?php endif; ?>
  </div>
</div>

<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px">
  <div class="stat-card teal" style="padding:16px 18px"><div class="stat-icon teal" style="margin-bottom:8px">📚</div><div class="stat-value" style="font-size:26px"><?= $stats['aktif'] ?></div><div class="stat-label">Aktif Mengajar</div></div>
  <div class="stat-card gold" style="padding:16px 18px"><div class="stat-icon gold" style="margin-bottom:8px">🏆</div><div class="stat-value" style="font-size:26px"><?= $stats['s3'] ?></div><div class="stat-label">Doktor (S3)</div></div>
  <div class="stat-card green" style="padding:16px 18px"><div class="stat-icon green" style="margin-bottom:8px">🎓</div><div class="stat-value" style="font-size:26px"><?= $stats['s2'] ?></div><div class="stat-label">Magister (S2)</div></div>
  <div class="stat-card blue" style="padding:16px 18px"><div class="stat-icon blue" style="margin-bottom:8px">📝</div><div class="stat-value" style="font-size:26px">15</div><div class="stat-label">Sertifikasi Aktif</div></div>
</div>

<div class="two-col-equal" style="margin-bottom:20px">
  <div class="card">
    <div class="card-header"><div class="card-title"><span>👥</span> Dosen per Program Studi</div></div>
    <div class="card-body">
      <div style="display:flex;flex-direction:column;gap:12px">
        <?php $colors=['teal','gold','blue','green','purple'];
        foreach ($perProdi as $i => $p):
          $pct = $maxJml > 0 ? round($p['jml']/$maxJml*100) : 0;
          $cl  = $colors[$i % count($colors)];
        ?>
        <div>
          <div style="display:flex;justify-content:space-between;margin-bottom:4px">
            <span style="font-size:13px;color:var(--text-mid)"><?= e($p['nama']) ?></span>
            <span style="font-family:'DM Mono',monospace;font-size:12px;font-weight:600"><?= $p['jml'] ?> dosen</span>
          </div>
          <div class="progress-bar"><div class="progress-fill <?= $cl ?>" style="width:<?= $pct ?>%"></div></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title"><span>⭐</span> Dosen Berprestasi</div></div>
    <div class="card-body">
      <table class="data-table">
        <thead><tr><th>Nama</th><th>Jabatan</th><th>Prodi</th><th>Rating</th></tr></thead>
        <tbody>
          <?php $top = array_slice($list, 0, 4); foreach ($top as $d):
            $jk  = $d['jabatan'] ?? 'lektor';
            $lbl = $jabatanLabel[$jk] ?? ucfirst($jk);
            $cls = $jabatanBadge[$jk]  ?? 'badge-teal';
            $ac  = avatarColor($d['nama']);
          ?>
          <tr>
            <td>
              <div class="avatar-cell">
                <div class="avatar-sm" style="background:<?= $ac['bg'] ?>;color:<?= $ac['fg'] ?>"><?= initials($d['nama']) ?></div>
                <span class="name-cell" style="font-size:12px"><?= e($d['nama']) ?></span>
              </div>
            </td>
            <td><span class="badge <?= $cls ?>"><?= $lbl ?></span></td>
            <td style="font-size:11px;color:var(--text-light)"><?= e($d['prodi'] ?? '') ?></td>
            <td style="color:var(--gold);font-weight:600">★ <?= number_format($d['rating'] ?? 4.5, 1) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header" style="padding:16px 22px">
    <div class="card-title"><span>👤</span> Direktori Dosen</div>
    <form method="get" style="display:flex;gap:8px">
      <input type="text" name="q" value="<?= e($search) ?>" placeholder="🔍 Cari nama / NIP..."
             style="padding:7px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:'DM Sans',sans-serif;outline:none">
      <button type="submit" class="btn-primary" style="padding:8px 14px">Cari</button>
    </form>
  </div>
  <div class="card-body" style="padding-top:0">
    <div class="dosen-grid">
      <?php foreach ($list as $d):
        $ac  = avatarColor($d['nama']);
        $jk  = $d['jabatan'] ?? 'lektor';
        $lbl = $jabatanLabel[$jk] ?? ucfirst($jk);
      ?>
      <div class="dosen-card">
        <div class="dosen-avatar" style="background:<?= $ac['bg'] ?>;color:<?= $ac['fg'] ?>"><?= initials($d['nama']) ?></div>
        <div class="dosen-name"><?= e($d['nama']) ?></div>
        <div class="dosen-prodi"><?= e($d['prodi'] ?? '') ?> · <?= $lbl ?></div>
        <div style="margin-bottom:8px;font-size:11px;color:var(--gold)">★ <?= number_format($d['rating'] ?? 4.5, 1) ?> · <?= $d['matkul'] ?? '—' ?> MK</div>
        <?= badgeStatus($d['status']) ?>
      </div>
      <?php endforeach; ?>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;padding-top:16px;font-size:12px;color:var(--text-light)">
      <span>Menampilkan <?= $offset+1 ?>–<?= min($offset+$limit,$totalRows) ?> dari <?= $totalRows ?> dosen</span>
      <div style="display:flex;gap:6px">
        <?php if ($page>1): ?><a href="?page=<?= $page-1 ?>&q=<?= urlencode($search) ?>" class="filter-btn">‹ Prev</a><?php endif; ?>
        <?php for ($p=max(1,$page-2);$p<=min($totalPages,$page+2);$p++): ?>
          <a href="?page=<?= $p ?>&q=<?= urlencode($search) ?>" class="filter-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <?php if ($page<$totalPages): ?><a href="?page=<?= $page+1 ?>&q=<?= urlencode($search) ?>" class="filter-btn">Next ›</a><?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/layout_footer.php'; ?>
