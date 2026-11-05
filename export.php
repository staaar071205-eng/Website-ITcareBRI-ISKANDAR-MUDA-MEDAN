<?php
/**
 * Ekspor Laporan Pengaduan ke PDF (satu klik langsung unduh).
 * Library: FPDF (lib/fpdf/fpdf.php) — tidak butuh Composer maupun ekstensi tambahan.
 */
require __DIR__ . '/config/bootstrap.php';
if (!current_user() || !in_array(current_user()['role'], ['admin','teknisi'], true)) {
    http_response_code(403); exit('Akses ditolak.');
}
require __DIR__ . '/lib/fpdf/fpdf.php';

/* ---------- Data ---------- */
$user = current_user();
$rows = all_tickets();
if ($user['role'] === 'teknisi') {
    $rows = array_values(array_filter($rows, fn($t) => (int)($t['assignee_id'] ?? 0) === (int)$user['id']));
}
usort($rows, fn($a, $b) => strcmp($a['created_at'], $b['created_at']));

$byStatus = []; $byCategory = []; $byUnit = [];
foreach ($rows as $t) {
    $byStatus[$t['status']]    = ($byStatus[$t['status']] ?? 0) + 1;
    $byCategory[$t['category']] = ($byCategory[$t['category']] ?? 0) + 1;
    $byUnit[$t['unit']]        = ($byUnit[$t['unit']] ?? 0) + 1;
}
arsort($byCategory); arsort($byUnit);
$total = count($rows);

/* ---------- Kelas PDF ---------- */
class LaporanPDF extends FPDF
{
    public string $logo = '';

    /** UTF-8 -> Windows-1252 (font bawaan FPDF) */
    public static function t($s): string {
        $s = (string)$s;
        $out = @iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $s);
        return $out === false ? $s : $out;
    }

    /** Kop surat — hanya di halaman pertama */
    function Header() {
        if ($this->PageNo() !== 1) return;
        $left = $this->lMargin;
        $pageW = $this->GetPageWidth();
        $logoSize = 24;
        if (is_file($this->logo)) $this->Image($this->logo, $left, 9, $logoSize, $logoSize);

        // Blok teks di sebelah kanan logo
        $textX = $left + $logoSize + 3;
        $textW = $pageW - $this->rMargin - $textX;
        $this->SetTextColor(0);
        $this->SetXY($textX, 10);
        $this->SetFont('Helvetica', 'B', 13.5);
        $this->Cell($textW, 7, self::t('PT. BANK RAKYAT INDONESIA (PERSERO) Tbk'), 0, 2, 'C');
        $this->SetFont('Helvetica', 'B', 7);
        $this->Cell($textW, 3.4, self::t('Kantor Cabang BRI Medan Iskandar Muda'), 0, 2, 'C');
        $this->SetFont('Helvetica', '', 7);
        $this->Cell($textW, 3.4, self::t('Jln. Iskandar Muda No. 173 Medan'), 0, 2, 'C');
        $this->Cell($textW, 3.4, self::t('Telepon : 061-4576435'), 0, 2, 'C');
        $this->Cell($textW, 3.4, self::t('Facsimile : 061-4513733'), 0, 2, 'C');
        $this->Cell($textW, 3.4, self::t('Website : http://www.bri.co.id'), 0, 2, 'C');

        // Garis kop: tebal + tipis
        $y = 36;
        $this->SetDrawColor(0);
        $this->SetLineWidth(1.1);
        $this->Line($left, $y, $pageW - $this->rMargin, $y);
        $this->SetLineWidth(0.25);
        $this->Line($left, $y + 1.4, $pageW - $this->rMargin, $y + 1.4);
        $this->SetY($y + 6);
    }

    function Footer() {
        $this->SetY(-12);
        $this->SetDrawColor(190);
        $this->SetLineWidth(0.2);
        $this->Line($this->lMargin, $this->GetY(), $this->GetPageWidth() - $this->rMargin, $this->GetY());
        $this->SetFont('Helvetica', 'I', 7);
        $this->SetTextColor(110);
        $this->Cell(0, 5, self::t('ITcare BRI - KC Iskandar Muda. Data dimiliki Tim IT BRI.'), 0, 0, 'L');
        $this->SetX($this->lMargin);
        $this->Cell(0, 5, self::t('Halaman ' . $this->PageNo() . ' dari {nb}'), 0, 0, 'R');
        $this->SetTextColor(0);
    }

    function sectionTitle(string $text) {
        if ($this->GetY() > $this->PageBreakTrigger - 30) $this->AddPage();
        $this->Ln(3);
        $this->SetFont('Helvetica', 'B', 9.5);
        $this->SetTextColor(20, 43, 74);
        $this->Cell(0, 6, self::t($text), 0, 1, 'L');
        $this->SetTextColor(0);
    }

    /** Jumlah baris teks yang dibutuhkan sebuah sel (word-wrap) */
    function nbLines(float $w, string $txt): int {
        $cw = $this->CurrentFont['cw'];
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb === 0) return 1;
        $sep = -1; $i = 0; $j = 0; $l = 0; $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c === "\n") { $i++; $sep = -1; $j = $i; $l = 0; $nl++; continue; }
            if ($c === ' ') $sep = $i;
            $l += $cw[$c] ?? 0;
            if ($l > $wmax) {
                if ($sep === -1) { if ($i === $j) $i++; }
                else $i = $sep + 1;
                $sep = -1; $j = $i; $l = 0; $nl++;
            } else $i++;
        }
        return $nl;
    }

    /**
     * Tabel dengan header berulang tiap halaman.
     * $cols: [[label, lebar, align], ...]; $data: array of array string.
     */
    function table(array $cols, array $data, float $fs = 7.5, float $lh = 4.6, ?array $totalRow = null) {
        $draw_header = function () use ($cols, $fs) {
            $this->SetFont('Helvetica', 'B', $fs);
            $this->SetFillColor(20, 43, 74);
            $this->SetTextColor(255);
            $this->SetDrawColor(20, 43, 74);
            $this->SetLineWidth(0.2);
            foreach ($cols as [$label, $w, $al]) {
                $this->Cell($w, 6.5, self::t($label), 1, 0, 'C', true);
            }
            $this->Ln();
            $this->SetTextColor(0);
            $this->SetDrawColor(170);
        };
        $draw_header();
        $this->SetFont('Helvetica', '', $fs);
        $zebra = false;
        if (!$data) {
            $sum = array_sum(array_column($cols, 1));
            $this->Cell($sum, 7, self::t('Tidak ada data.'), 1, 1, 'C');
        }
        foreach ($data as $row) {
            $h = 0;
            foreach ($cols as $i => [$label, $w, $al]) $h = max($h, $this->nbLines($w, self::t($row[$i])));
            $h = $h * $lh + 1.6;
            if ($this->GetY() + $h > $this->PageBreakTrigger) { $this->AddPage(); $draw_header(); $this->SetFont('Helvetica', '', $fs); }
            $x = $this->GetX(); $y = $this->GetY();
            $this->SetFillColor(245, 248, 252);
            foreach ($cols as $i => [$label, $w, $al]) {
                $this->Rect($x, $y, $w, $h, $zebra ? 'DF' : 'D');
                $this->SetXY($x, $y + 0.8);
                $this->MultiCell($w, $lh, self::t($row[$i]), 0, $al);
                $x += $w;
            }
            $this->SetXY($this->lMargin, $y + $h);
            $zebra = !$zebra;
        }
        if ($totalRow) {
            $this->SetFont('Helvetica', 'B', $fs);
            $this->SetFillColor(232, 238, 247);
            foreach ($cols as $i => [$label, $w, $al]) {
                $this->Cell($w, 6.2, self::t($totalRow[$i]), 1, 0, $al, true);
            }
            $this->Ln();
        }
    }
}

/* ---------- Susun dokumen ---------- */
$pdf = new LaporanPDF('P', 'mm', 'A4');
$pdf->logo = __DIR__ . '/assets/img/logo-bri.png';
$pdf->SetMargins(12, 10, 12);
$pdf->SetAutoPageBreak(true, 16);
$pdf->AliasNbPages();
$pdf->SetTitle(LaporanPDF::t('Laporan Pengaduan Layanan IT'));
$pdf->SetAuthor('ITcare BRI');
$pdf->AddPage();
$W = $pdf->GetPageWidth() - 24; // lebar area cetak = 186 mm

// Judul dokumen
$pdf->SetFont('Helvetica', 'B', 12);
$pdf->Cell(0, 6, LaporanPDF::t('LAPORAN PENGADUAN LAYANAN TEKNOLOGI INFORMASI'), 0, 1, 'C');
$pdf->SetFont('Helvetica', '', 8.5);
$pdf->SetTextColor(90);
$pdf->Cell(0, 5, LaporanPDF::t('Sistem Informasi Helpdesk IT (ITcare BRI)'), 0, 1, 'C');
$pdf->SetTextColor(0);
$pdf->Ln(3);

// Informasi dokumen (label : isi) seperti blok Nomor/Perihal pada surat
$bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$tgl = date('j') . ' ' . $bulan[(int)date('n')] . ' ' . date('Y') . ', ' . date('H:i') . ' WIB';
$scope = $user['role'] === 'teknisi' ? 'Tiket yang ditugaskan kepada ' . $user['name'] : 'Seluruh pengaduan';
$meta = [
    ['Tanggal cetak', $tgl],
    ['Dicetak oleh', $user['name'] . ' (' . role_label($user['role']) . ')'],
    ['Cakupan data', $scope . ' - data simulasi saat ini'],
    ['Total pengaduan', (string)$total . ' tiket'],
];
foreach ($meta as [$k, $v]) {
    $pdf->SetFont('Helvetica', '', 8.5);
    $pdf->Cell(32, 5, LaporanPDF::t($k), 0, 0);
    $pdf->Cell(4, 5, ':', 0, 0);
    $pdf->MultiCell(0, 5, LaporanPDF::t($v), 0, 'L');
}

// A. Ringkasan status
$pdf->sectionTitle('A. Ringkasan Berdasarkan Status');
$statusOrder = ['menunggu','diproses','tindak_lanjut','selesai','ditolak','dibatalkan','ditutup'];
$data = [];
foreach ($statusOrder as $s) {
    $n = $byStatus[$s] ?? 0;
    $data[] = [status_label($s), (string)$n, $total ? number_format($n / $total * 100, 1, ',', '.') . '%' : '0%'];
}
$pdf->table([['Status', 110, 'L'], ['Jumlah', 36, 'C'], ['Persentase', 40, 'C']], $data, 8, 5, ['Total', (string)$total, $total ? '100%' : '0%']);

// B. Kategori
$pdf->sectionTitle('B. Pengaduan Berdasarkan Kategori');
$data = []; $no = 1;
foreach ($byCategory as $label => $n) $data[] = [(string)$no++, $label, (string)$n, $total ? number_format($n / $total * 100, 1, ',', '.') . '%' : '0%'];
$pdf->table([['No', 12, 'C'], ['Kategori gangguan', 98, 'L'], ['Jumlah', 36, 'C'], ['Persentase', 40, 'C']], $data, 8, 5);

// C. Lokasi / unit
$pdf->sectionTitle('C. Pengaduan Berdasarkan Lokasi / Unit');
$data = []; $no = 1;
foreach ($byUnit as $label => $n) $data[] = [(string)$no++, $label, (string)$n, $total ? number_format($n / $total * 100, 1, ',', '.') . '%' : '0%'];
$pdf->table([['No', 12, 'C'], ['Unit / lokasi', 98, 'L'], ['Jumlah', 36, 'C'], ['Persentase', 40, 'C']], $data, 8, 5);

// D. Rincian
$pdf->sectionTitle('D. Rincian Data Pengaduan');
$data = []; $no = 1;
foreach ($rows as $t) {
    $data[] = [
        (string)$no++, $t['ticket'], $t['reporter'], $t['unit'],
        $t['category'] . "\n" . $t['service_type'],
        $t['assignee'] ?? '-',
        date('d/m/Y H:i', strtotime($t['created_at'])),
        status_label($t['status']),
    ];
}
$pdf->table([
    ['No', 7, 'C'], ['Nomor Tiket', 29, 'L'], ['Pelapor', 24, 'L'], ['Unit', 25, 'L'],
    ['Kategori / Jenis', 31, 'L'], ['Teknisi', 22, 'L'], ['Waktu Masuk', 22, 'C'], ['Status', 26, 'C'],
], $data, 7.2, 4.2);

// Catatan
$pdf->Ln(4);
$pdf->SetFont('Helvetica', 'I', 7.5);
$pdf->SetTextColor(100);
$pdf->MultiCell(0, 4, LaporanPDF::t('Catatan: dokumen ini dihasilkan otomatis oleh ITcare BRI dari data dan dokumen resmi Tim IT BRI.'), 0, 'L');

/* ---------- Kirim sebagai unduhan ---------- */
if (ob_get_length()) ob_end_clean();
$pdf->Output('D', 'laporan-pengaduan-' . date('Ymd-His') . '.pdf');
exit;
