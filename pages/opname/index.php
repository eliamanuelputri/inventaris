<?php
$pageTitle = 'Stock Opname (Hitung Fisik) - ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

$pdo = db();
$stmt = $pdo ? $pdo->query("
    SELECT o.*, u.name as user_name
    FROM stock_opnames o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.audit_date DESC, o.id DESC
") : null;
$opnames = $stmt ? $stmt->fetchAll() : [];
?>

<div class="flex-1 flex overflow-hidden">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto md:ml-64 p-4 sm:p-6 lg:p-8 pb-24 md:pb-8">
        
        <!-- Header -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900">
                    Stock Opname & Rekonsiliasi Selisih
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Cocokkan stok di sistem dengan hitungan fisik lapangan dan sesuaikan selisih secara otomatis.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a 
                    href="<?= BASE_URL ?>index.php?page=opname_create" 
                    class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-brand-600 to-crimson-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-md shadow-brand-500/20 hover:from-brand-700 hover:to-crimson-700 transition"
                >
                    <i data-lucide="clipboard-check" class="h-4 w-4"></i>
                    <span>Mulai Sesi Opname Baru</span>
                </a>
            </div>
        </div>

        <!-- Opname Sessions Table (Odoo ERP Audit Style) -->
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm border border-slate-100">
            <?php if (empty($opnames)): ?>
                <div class="py-12 text-center text-slate-400 text-xs">
                    <i data-lucide="clipboard-list" class="mx-auto mb-2 h-8 w-8 text-slate-300"></i>
                    Belum ada riwayat sesi stock opname yang tercatat.
                    <div class="mt-3">
                        <a href="<?= BASE_URL ?>index.php?page=opname_create" class="inline-flex items-center gap-1 font-bold text-brand-600 hover:text-brand-700">
                            + Buat Sesi Opname Pertama
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-400 border-b border-slate-100">
                            <tr>
                                <th class="px-4 py-3">No. Sesi Opname</th>
                                <th class="px-4 py-3">Tanggal Audit</th>
                                <th class="px-4 py-3">Auditor / Petugas</th>
                                <th class="px-4 py-3 text-right">Barang Dihitung</th>
                                <th class="px-4 py-3 text-right">Total Selisih Fisik</th>
                                <th class="px-4 py-3 text-right">Nilai Selisih (Rp)</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($opnames as $op): 
                                $varQty = (int)$op['total_variance_qty'];
                            ?>
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="px-4 py-3 font-mono font-bold text-slate-900">
                                        <?= sanitize($op['opname_no']) ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap"><?= format_date($op['audit_date']) ?></td>
                                    <td class="px-4 py-3 font-medium text-slate-800"><?= sanitize($op['user_name'] ?? 'Petugas') ?></td>
                                    <td class="px-4 py-3 text-right font-bold"><?= number_format($op['total_items']) ?> SKU</td>
                                    <td class="px-4 py-3 text-right font-black <?= $varQty < 0 ? 'text-crimson-600' : ($varQty > 0 ? 'text-emerald-600' : 'text-slate-400') ?>">
                                        <?= $varQty > 0 ? ('+' . $varQty) : ($varQty < 0 ? $varQty : '0 (Cocok Tepat)') ?>
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold <?= $op['total_variance_value'] < 0 ? 'text-crimson-600' : 'text-slate-800' ?>">
                                        <?= format_rupiah($op['total_variance_value']) ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <?php if ($op['status'] === 'completed'): ?>
                                            <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[10px] font-bold text-emerald-800">
                                                ✅ Selesai & Disesuaikan
                                            </span>
                                        <?php else: ?>
                                            <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-bold text-amber-800">
                                                ⏳ Draft (Belum Disetujui)
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a 
                                            href="<?= BASE_URL ?>index.php?page=opname_detail&id=<?= $op['id'] ?>" 
                                            class="inline-flex items-center gap-1 rounded-xl bg-slate-100 hover:bg-brand-50 hover:text-brand-600 px-3 py-1 text-[11px] font-bold text-slate-700 transition"
                                        >
                                            <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                            <span>Lihat Berita Acara</span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

