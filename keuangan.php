<?php
// keuangan.php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';
requireLogin();

$pdo     = getDB();
$filter  = $_GET['tipe']      ?? 'semua';
$search  = trim($_GET['q']    ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$limit   = 10; $offset = ($page-1)*$limit;

if ($pdo) {
    $where = "1=1"; $params = [];
    if ($filter !== 'semua') { $where .= " AND tipe = ?"; $params[] = $filter; }
    if ($search) { $where .= " AND deskripsi LIKE ?"; $params[] = "%$search%"; }
    $total = $pdo->prepare("SELECT COUNT(*) FROM transaksi_keuangan WHERE $where");
    $total->execute($params);
    $totalRows = $total->fetchColumn();
    $stmt = $pdo->prepare("SELECT t.*, m.nama AS mhs_nama, m.nim FROM transaksi_keuangan t LEFT JOIN mahasiswa m ON t.mahasiswa_id=m.id WHERE $where ORDER BY t.tanggal DESC LIMIT $limit OFFSET $offset");
    $stmt->execute($params);
    $list = $stmt->fetchAll();
    $pIn  = $pdo->query("SELECT SUM(nominal) FROM transaksi_keuangan WHERE tipe='masuk' AND YEAR(tanggal)=YEAR(NOW())")->fetchColumn();
    $pOut = $pdo->query("SELECT SUM(nominal) FROM transaksi_keuangan WHERE tipe='keluar' AND YEAR(tanggal)=YEAR(NOW())")->fetchColumn();
    $pBulan = $pdo->query("SELECT SUM(nominal) FROM transaksi_keuangan WHERE tipe='masuk' AND MONTH(tanggal)=MONTH(NOW()) AND YEAR(tanggal)=YEAR(NOW())")->fetchColumn();
    $sppLunas   = $pdo->query("SELECT COUNT(*) FROM mahasiswa")->fetchColumn();
    $sppBelum   = 126; $sppCicil = 198;
} else {
    $allData   = getDummyTransaksi();
    if ($filter !== 'semua') $allData = array_filter($allData, fn($t) => $t['tipe']===$filter);
    if ($search) $allData = array_filter($allData, fn($t) => stripos($t['deskripsi'],$search)!==false);
    $allData   = array_values($allData);
    $totalRows = count($allData);
    $list      = array_slice($allData, $offset, $limit);
    $pIn       = 24800000; $pOut = 18300000; $pBulan = 4200000;
    $sppLunas  = 924; $sppBelum = 126; $sppCicil = 198;
}
$saldo      = ($pIn - $pOut);
$totalPages = max(1, ceil($totalRows/$limit));
$keuBulanan = getDummyKeuanganBulanan();

$kategoriLabel = ['spp'=>'SPP','beasiswa'=>'Beasiswa','sarana'=>'Sarana','sdm'=>'SDM','operasional'=>'Operasional','lainnya'=>'Lainnya'];
$kategoriClass = ['spp'=>'badge-teal','beasiswa'=>'badge-purple','sarana'=>'badge-red','sdm'=>'badge-gold','operasional'=>'badge-blue','lainnya'=>'badge-gray'];

$pageTitle = 'Laporan Keuangan'; $activePage = 'keuangan';
include 'includes/layout_header.php';
?>

<div class="page-header">
  <div class="page-header-left"><h2>💰 Data Keuangan</h2><p>Laporan keuangan dan pembayaran semester STIKES MNI</p></div>
  <div style="display:flex;gap:8px">
    <button class="btn-secondary">📄 Cetak Laporan</button>
    <?php if (hasRole('admin','keuangan')): ?>
    <button class="btn-primary">➕ Catat Transaksi</button>
    <?php endif; ?>
  </div>
</div>

<!-- Summary Cards -->
<div class="finance-summary">
  <div class="finance-card">
    <div class="finance-card-label">Total Pemasukan Tahun Ini</div>
    <div class="finance-card-value"><?= formatRupiah($pIn) ?></div>
    <div class="finance-card-change" style="color:var(--green-soft)">↑ +12% dari tahun lalu</div>
  </div>
  <div class="finance-card">
    <div class="finance-card-label">Total Pengeluaran Tahun Ini</div>
    <div class="finance-card-value"><?= formatRupiah($pOut) ?></div>
    <div class="finance-card-change" style="color:var(--red-soft)">↑ +5% dari tahun lalu</div>
  </div>
  <div class="finance-card" style="border-color:<?= $saldo>=0?'rgba(46,204,138,0.2)':'rgba(224,85,85,0.2)' ?>">
    <div class="finance-card-label">Saldo Bersih <?= date('Y') ?></div>
    <div class="finance-card-value" style="color:<?= $saldo>=0?'var(--teal-mid)':'var(--red-soft)' ?>"><?= formatRupiah(abs($saldo)) ?></div>
    <div class="finance-card-change" style="color:<?= $saldo>=0?'var(--green-soft)':'var(--red-soft)' ?>"><?= $saldo>=0?'Surplus':'Defisit' ?> tahun ini</div>
  </div>
</div>

<div class="two-col-equal">
  <div class="card">
    <div class="card-header"><div class="card-title"><span>📈</span> Tren Keuangan 6 Bulan</div></div>
    <div class="card-body">
      <canvas id="finChartKeu" height="120"></canvas>
      <div style="display:flex;gap:16px;margin-top:10px">
        <div style="display:flex;align-items:center;gap:6px;font-size:11px;color:var(--text-light)"><div style="width:10px;height:10px;background:var(--teal-mid);border-radius:2px"></div>Pemasukan</div>
        <div style="display:flex;align-items:center;gap:6px;font-size:11px;color:var(--text-light)"><div style="width:10px;height:10px;background:var(--gold);border-radius:2px"></div>Pengeluaran</div>
      </div>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title"><span>🎯</span> Status Pembayaran SPP</div></div>
    <div class="card-body">
      <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:16px">
        <?php
        $total_mhs = $sppLunas + $sppBelum + $sppCicil;
        $pLunas  = $total_mhs>0 ? round($sppLunas/$total_mhs*100) : 0;
        $pCicil  = $total_mhs>0 ? round($sppCicil/$total_mhs*100) : 0;
        $pBelum2 = $total_mhs>0 ? round($sppBelum/$total_mhs*100) : 0;
        ?>
        <div><div style="display:flex;justify-content:space-between;margin-bottom:4px"><span style="font-size:13px;color:var(--text-mid)">✅ Lunas</span><span style="font-family:'DM Mono',monospace;font-size:12px;font-weight:600"><?= number_format($sppLunas) ?> mhs (<?= $pLunas ?>%)</span></div><div class="progress-bar"><div class="progress-fill green" style="width:<?= $pLunas ?>%"></div></div></div>
        <div><div style="display:flex;justify-content:space-between;margin-bottom:4px"><span style="font-size:13px;color:var(--text-mid)">⏳ Cicilan</span><span style="font-family:'DM Mono',monospace;font-size:12px;font-weight:600"><?= $sppCicil ?> mhs (<?= $pCicil ?>%)</span></div><div class="progress-bar"><div class="progress-fill gold" style="width:<?= $pCicil ?>%"></div></div></div>
        <div><div style="display:flex;justify-content:space-between;margin-bottom:4px"><span style="font-size:13px;color:var(--text-mid)">❌ Belum Bayar</span><span style="font-family:'DM Mono',monospace;font-size:12px;font-weight:600"><?= $sppBelum ?> mhs (<?= $pBelum2 ?>%)</span></div><div class="progress-bar"><div class="progress-fill red" style="width:<?= $pBelum2 ?>%"></div></div></div>
      </div>
      <div style="background:var(--cream);border-radius:var(--radius-sm);padding:12px;border:1px solid var(--border)">
        <div style="font-size:11px;color:var(--text-light);margin-bottom:6px">Total SPP Terkumpul Semester Ini</div>
        <div style="font-family:'Playfair Display',serif;font-size:22px;color:var(--teal-deep)"><?= formatRupiah($pBulan * 3) ?></div>
        <div style="font-size:11px;color:var(--green-soft);margin-top:2px">dari target <?= formatRupiah($pBulan * 3.6) ?> (83%)</div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header" style="padding:16px 22px">
    <div class="card-title"><span>📑</span> Riwayat Transaksi</div>
    <form method="get" style="display:flex;gap:8px;align-items:center">
      <a href="keuangan.php" class="filter-btn <?= $filter==='semua'?'active':'' ?>">Semua</a>
      <a href="?tipe=masuk" class="filter-btn <?= $filter==='masuk'?'active':'' ?>">↑ Masuk</a>
      <a href="?tipe=keluar" class="filter-btn <?= $filter==='keluar'?'active':'' ?>">↓ Keluar</a>
      <input type="text" name="q" value="<?= e($search) ?>" placeholder="🔍 Cari transaksi..."
             style="padding:7px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:'DM Sans',sans-serif;outline:none">
      <button type="submit" class="btn-primary" style="padding:8px 14px">Cari</button>
    </form>
  </div>
  <div class="card-body" style="padding-top:0">
    <table class="data-table">
      <thead><tr><th>Tanggal</th><th>Deskripsi</th><th>Kategori</th><th>Metode</th><th>Nominal</th><th>Tipe</th></tr></thead>
      <tbody>
        <?php foreach ($list as $t):
          $cat    = $t['kategori'] ?? 'lainnya';
          $catLbl = $kategoriLabel[$cat] ?? ucfirst($cat);
          $catCls = $kategoriClass[$cat] ?? 'badge-gray';
          $isIn   = ($t['tipe'] === 'masuk');
          $detail = isset($t['detail']) ? $t['detail'] : (isset($t['mhs_nama']) ? $t['mhs_nama'].' · '.$t['nim'] : '');
        ?>
        <tr>
          <td style="font-family:'DM Mono',monospace;font-size:12px"><?= formatTanggal($t['tanggal']) ?></td>
          <td>
            <strong style="font-size:13px;color:var(--text-dark)"><?= e($t['deskripsi']) ?></strong>
            <?php if ($detail): ?><br><span style="font-size:11px;color:var(--text-light)"><?= e($detail) ?></span><?php endif; ?>
          </td>
          <td><span class="badge <?= $catCls ?>"><?= $catLbl ?></span></td>
          <td style="font-size:12px;color:var(--text-light)"><?= e($t['metode'] ?? '—') ?></td>
          <td style="font-family:'DM Mono',monospace;font-weight:600;color:<?= $isIn?'var(--green-soft)':'var(--red-soft)' ?>">
            <?= $isIn ? '+' : '-' ?><?= formatRupiah($t['nominal']) ?>
          </td>
          <td><?= badgeStatus($t['tipe']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div style="display:flex;justify-content:space-between;align-items:center;padding-top:16px;font-size:12px;color:var(--text-light)">
      <span>Menampilkan <?= $offset+1 ?>–<?= min($offset+$limit,$totalRows) ?> dari <?= $totalRows ?> transaksi</span>
      <div style="display:flex;gap:6px">
        <?php if ($page>1): ?><a href="?page=<?= $page-1 ?>&tipe=<?= urlencode($filter) ?>&q=<?= urlencode($search) ?>" class="filter-btn">‹ Prev</a><?php endif; ?>
        <?php for ($p=max(1,$page-2);$p<=min($totalPages,$page+2);$p++): ?>
          <a href="?page=<?= $p ?>&tipe=<?= urlencode($filter) ?>&q=<?= urlencode($search) ?>" class="filter-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <?php if ($page<$totalPages): ?><a href="?page=<?= $page+1 ?>&tipe=<?= urlencode($filter) ?>&q=<?= urlencode($search) ?>" class="filter-btn">Next ›</a><?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  var ctx = document.getElementById('finChartKeu');
  if (!ctx) return;
  var pemasukan   = <?= json_encode($keuBulanan['pemasukan']) ?>;
  var pengeluaran = <?= json_encode($keuBulanan['pengeluaran']) ?>;
  var labels      = <?= json_encode($keuBulanan['labels']) ?>;
  drawBarChart(ctx, labels, pemasukan, pengeluaran);
})();
</script>

<?php include 'includes/layout_footer.php'; ?>
