<?php

namespace App\Services;

use App\Models\{Permission, User};
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\{Fill, Border, Alignment, Font};
use TCPDF;

/**
 * Custom TCPDF subclass with page numbering footer.
 */
class PermRegPdf extends TCPDF
{
    public function Footer(): void
    {
        $this->SetY(-12);
        $this->SetFont('dejavusans', 'I', 7);
        $this->Cell(0, 10, 'Σελίδα ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

class ExportService
{
    private Permission $permModel;
    private User $userModel;

    public function __construct()
    {
        $this->permModel = new Permission();
        $this->userModel = new User();
    }

    // ── CSV ───────────────────────────────────────────────────────────────────

    public function exportCsv(array $filters = []): never
    {
        $rows = $this->getData($filters);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="permissions_' . date('Ymd_His') . '.csv"');
        header('Cache-Control: no-cache');

        $out = fopen('php://output', 'w');
        // BOM for Excel UTF-8
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, [
            'Χρήστης', 'Ονοματεπώνυμο', 'Email', 'Τμήμα', 'Θέση',
            'Τύπος Πόρου', 'Πόρος', 'Δικαίωμα', 'Εγκρίθηκε από', 'Ημ/νία Χορήγησης', 'Λήξη', 'Σημειώσεις'
        ], ';');

        foreach ($rows as $row) {
            fputcsv($out, [
                $row['username'],
                $row['full_name'],
                $row['email'],
                $row['department'],
                $row['job_title'] ?? '',
                $row['type_label'],
                $row['resource_name'],
                $row['permission_level'],
                $row['granted_by_name'] ?? '',
                $row['granted_at'],
                $row['expires_at'] ?? '',
                $row['notes'] ?? '',
            ], ';');
        }

        fclose($out);
        exit;
    }

    // ── Excel ─────────────────────────────────────────────────────────────────

    public function exportExcel(array $filters = []): never
    {
        $rows       = $this->getData($filters);
        $departedAt = $this->getDepartedAt($filters['user_id'] ?? null);

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Δικαιώματα');

        $startRow = 1;

        // Departure banner row
        if ($departedAt) {
            $depFmt = (new \DateTime($departedAt))->format('d/m/Y');
            $sheet->mergeCells('A1:K1');
            $sheet->setCellValue('A1', '⚠ ΑΠΟΧΩΡΗΣΕ: ' . $depFmt . ' — Τα δικαιώματα έχουν ληγμένη ή επικείμενη ημερομηνία λήξης.');
            $sheet->getStyle('A1')->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => '721c24']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8D7DA']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ]);
            $sheet->getRowDimension(1)->setRowHeight(18);
            $startRow = 2;
        }

        // Headers
        $headers = [
            'A' => 'Χρήστης',
            'B' => 'Ονοματεπώνυμο',
            'C' => 'Email',
            'D' => 'Τμήμα',
            'E' => 'Τύπος Πόρου',
            'F' => 'Πόρος',
            'G' => 'Δικαίωμα',
            'H' => 'Εγκρίθηκε από',
            'I' => 'Ημ/νία Χορήγησης',
            'J' => 'Λήξη',
            'K' => 'Σημειώσεις',
        ];
        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . $startRow, $label);
        }
        $sheet->getStyle('A' . $startRow . ':K' . $startRow)->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0d6efd']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Data rows
        $rowNum = $startRow + 1;
        foreach ($rows as $row) {
            $exp = $this->expiryInfo($row['expires_at'] ?? null);

            $sheet->fromArray([
                $row['username'],
                $row['full_name'],
                $row['email'],
                $row['department'],
                $row['type_label'],
                $row['resource_name'],
                $row['permission_level'],
                $row['granted_by_name'] ?? '',
                substr($row['granted_at'] ?? '', 0, 10),
                $exp['text'],
                $row['notes'] ?? '',
            ], null, 'A' . $rowNum);

            // Alternate row tint on non-expiry columns
            $rowBg = (($rowNum - $startRow) % 2 === 0) ? 'f0f7ff' : 'FFFFFF';
            $sheet->getStyle('A' . $rowNum . ':I' . $rowNum . ',K' . $rowNum)
                ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($rowBg);

            // Expiry cell colour
            if ($exp['bgExcel']) {
                $sheet->getStyle('J' . $rowNum)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $exp['bgExcel']]],
                    'font' => ['bold' => true, 'color' => ['rgb' => $exp['fontExcel']]],
                ]);
            } else {
                $sheet->getStyle('J' . $rowNum)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($rowBg);
            }

            $rowNum++;
        }

        // Auto-width
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Freeze header row
        $sheet->freezePane('A' . ($startRow + 1));

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="permissions_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: no-cache');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ── PDF ───────────────────────────────────────────────────────────────────

    public function exportPdf(array $filters = [], string $title = 'Αναφορά Δικαιωμάτων'): never
    {
        $rows        = $this->getData($filters);
        $departedAt  = $this->getDepartedAt($filters['user_id'] ?? null);
        $appName     = \App\Core\Config::get('app_name', 'Μητρώο Δικαιωμάτων');

        $pdf = new PermRegPdf('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator($appName);
        $pdf->SetAuthor($appName);
        $pdf->SetTitle($title);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();

        // Title
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 10, $title, 0, 1, 'C');
        $pdf->SetFont('dejavusans', '', 9);
        $pdf->Cell(0, 6, 'Εκτύπωση: ' . date('d/m/Y H:i'), 0, 1, 'C');
        $pdf->Ln(3);

        // Build HTML — grouped by resource if department filter, flat table otherwise
        if (!empty($filters['department'])) {
            $html = $this->buildDeptPdfHtml($rows, $departedAt);
        } else {
            $html = $this->buildPdfHtmlTable($rows, $departedAt);
        }
        $pdf->SetFont('dejavusans', '', 7);
        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->Output('permissions_' . date('Ymd_His') . '.pdf', 'D');
        exit;
    }

    // ── File output versions (for email attachments) ──────────────────────────

    public function exportExcelToFile(array $filters, string $filePath): void
    {
        $rows       = $this->getData($filters);
        $departedAt = $this->getDepartedAt($filters['user_id'] ?? null);

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Δικαιώματα');

        $startRow = 1;
        if ($departedAt) {
            $depFmt = (new \DateTime($departedAt))->format('d/m/Y');
            $sheet->mergeCells('A1:K1');
            $sheet->setCellValue('A1', '⚠ ΑΠΟΧΩΡΗΣΕ: ' . $depFmt);
            $sheet->getStyle('A1')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '721c24']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8D7DA']],
            ]);
            $startRow = 2;
        }

        $headers = ['A'=>'Χρήστης','B'=>'Ονοματεπώνυμο','C'=>'Email','D'=>'Τμήμα',
                    'E'=>'Τύπος Πόρου','F'=>'Πόρος','G'=>'Δικαίωμα','H'=>'Εγκρίθηκε από',
                    'I'=>'Ημ/νία','J'=>'Λήξη','K'=>'Σημειώσεις'];
        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . $startRow, $label);
        }
        $sheet->getStyle('A' . $startRow . ':K' . $startRow)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0d6efd']],
        ]);

        $rowNum = $startRow + 1;
        foreach ($rows as $row) {
            $exp = $this->expiryInfo($row['expires_at'] ?? null);
            $sheet->fromArray([
                $row['username'], $row['full_name'], $row['email'], $row['department'],
                $row['type_label'], $row['resource_name'], $row['permission_level'],
                $row['granted_by_name'] ?? '', substr($row['granted_at'] ?? '', 0, 10),
                $exp['text'], $row['notes'] ?? '',
            ], null, 'A' . $rowNum);

            if ($exp['bgExcel']) {
                $sheet->getStyle('J' . $rowNum)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $exp['bgExcel']]],
                    'font' => ['bold' => true, 'color' => ['rgb' => $exp['fontExcel']]],
                ]);
            }
            $rowNum++;
        }

        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);
    }

    public function exportPdfToFile(array $filters, string $filePath, string $title = 'Αναφορά Δικαιωμάτων'): void
    {
        $rows       = $this->getData($filters);
        $departedAt = $this->getDepartedAt($filters['user_id'] ?? null);
        $appName    = \App\Core\Config::get('app_name', 'Μητρώο Δικαιωμάτων');

        $pdf = new PermRegPdf('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator($appName);
        $pdf->SetTitle($title);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();

        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 10, $title, 0, 1, 'C');
        $pdf->SetFont('dejavusans', '', 9);
        $pdf->Cell(0, 6, 'Εκτύπωση: ' . date('d/m/Y H:i'), 0, 1, 'C');
        $pdf->Ln(3);

        if (!empty($filters['department'])) {
            $html = $this->buildDeptPdfHtml($rows, $departedAt);
        } else {
            $html = $this->buildPdfHtmlTable($rows, $departedAt);
        }
        $pdf->SetFont('dejavusans', '', 7);
        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->Output($filePath, 'F');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Returns expiry metadata: formatted text + hex colours for Excel & PDF.
     * status: 'none' | 'expired' | 'today' | 'soon' (≤14d) | 'warning' (≤60d) | 'ok'
     */
    private function expiryInfo(?string $expiresAt): array
    {
        if (!$expiresAt) {
            return [
                'text'      => '—',
                'status'    => 'none',
                'bgExcel'   => null,
                'fontExcel' => null,
                'bgPdf'     => null,
                'fontPdf'   => null,
            ];
        }

        $exp  = new \DateTime(substr($expiresAt, 0, 10));
        $now  = new \DateTime('today');
        $diff = (int)$now->diff($exp)->format('%r%a');
        $date = $exp->format('d/m/Y');

        if ($diff < 0) {
            return ['text' => $date,     'status' => 'expired',
                    'bgExcel' => 'FFC7CE', 'fontExcel' => '9C0006',
                    'bgPdf'   => '#F8D7DA', 'fontPdf'  => '#721c24'];
        }
        if ($diff === 0) {
            return ['text' => 'Σήμερα', 'status' => 'today',
                    'bgExcel' => 'FFC7CE', 'fontExcel' => '9C0006',
                    'bgPdf'   => '#F8D7DA', 'fontPdf'  => '#721c24'];
        }
        if ($diff <= 14) {
            return ['text' => $date,     'status' => 'soon',
                    'bgExcel' => 'FFEB9C', 'fontExcel' => '9C5700',
                    'bgPdf'   => '#FFF3CD', 'fontPdf'  => '#664d03'];
        }
        if ($diff <= 60) {
            return ['text' => $date,     'status' => 'warning',
                    'bgExcel' => 'FFFACD', 'fontExcel' => '7D6500',
                    'bgPdf'   => '#FFFDE7', 'fontPdf'  => '#5c4a00'];
        }

        return ['text' => $date, 'status' => 'ok',
                'bgExcel' => null, 'fontExcel' => null,
                'bgPdf'   => null, 'fontPdf'   => null];
    }

    /** Returns the user's departed_at date string, or null if not departed / not found. */
    private function getDepartedAt(?int $userId): ?string
    {
        if (!$userId) return null;
        $user = $this->userModel->findById($userId);
        return ($user && !empty($user['departed_at'])) ? $user['departed_at'] : null;
    }

    /**
     * Build grouped-by-resource PDF HTML (for department exports).
     * Mimics the on-screen layout: Resource header → permission level badge → users table.
     */
    private function buildDeptPdfHtml(array $rows, ?string $departedAt = null): string
    {
        // Group by resource, then by permission level
        $byResource = [];
        foreach ($rows as $row) {
            $key = $row['resource_name'] ?? '';
            if (!isset($byResource[$key])) {
                $byResource[$key] = [
                    'type_label' => $row['type_label'] ?? '',
                    'levels'     => [],
                ];
            }
            $level = $row['permission_level'] ?? '';
            $byResource[$key]['levels'][$level][] = $row;
        }

        $html = '';
        $e = function($s) { return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); };

        foreach ($byResource as $resName => $res) {
            // Resource header
            $totalUsers = 0;
            foreach ($res['levels'] as $users) { $totalUsers += count($users); }

            $html .= '<table cellpadding="3" cellspacing="0" style="margin-bottom:2mm;">';
            $html .= '<tr style="background-color:#2c5f8a; color:#ffffff; font-size:9pt;">';
            $html .= '<td width="70%"><b>' . $e($resName) . '</b>'
                    . '  <span style="font-size:7pt; color:#d0e0f0;">(' . $e($res['type_label']) . ')</span></td>';
            $html .= '<td width="30%" align="right" style="font-size:7pt;">' . $totalUsers . ' στελέχη</td>';
            $html .= '</tr></table>';

            // Each permission level
            foreach ($res['levels'] as $level => $users) {
                // Permission level sub-header
                $html .= '<table cellpadding="2" cellspacing="0" style="margin-bottom:1mm;">';
                $html .= '<tr style="background-color:#e8f4fd; font-size:8pt;">';
                $html .= '<td><b style="color:#0d6efd;">' . $e($level) . '</b>'
                        . '  <span style="font-size:7pt; color:#666;">' . count($users) . ' στελέχη</span></td>';
                $html .= '</tr></table>';

                // Users table
                $html .= '<table border="0.5" cellpadding="3" cellspacing="0" style="margin-bottom:3mm;">';
                $html .= '<tr style="background-color:#f0f0f0; font-weight:bold; font-size:7pt;">';
                $html .= '<th width="22%">Ονοματεπώνυμο</th>';
                $html .= '<th width="12%">Username</th>';
                $html .= '<th width="17%">Θέση</th>';
                $html .= '<th width="22%">Email</th>';
                $html .= '<th width="13%">Ημ/νία</th>';
                $html .= '<th width="14%">Λήξη</th>';
                $html .= '</tr>';

                $fill = false;
                foreach ($users as $u) {
                    $rowBg = $fill ? '#fafcff' : '#ffffff';
                    $exp   = $this->expiryInfo($u['expires_at'] ?? null);
                    $expStyle = $exp['bgPdf']
                        ? 'background-color:' . $exp['bgPdf'] . '; color:' . $exp['fontPdf'] . ';'
                        : '';
                    $deptMark = ($departedAt && !empty($u['expires_at'])
                                 && substr($u['expires_at'], 0, 10) === substr($departedAt, 0, 10))
                                ? ' &#x1F6AA;' : '';

                    $html .= '<tr style="font-size:7pt;">';
                    $html .= '<td width="22%" style="background-color:' . $rowBg . ';">' . $e($u['full_name']) . '</td>';
                    $html .= '<td width="12%" style="background-color:' . $rowBg . ';">' . $e($u['username']) . '</td>';
                    $html .= '<td width="17%" style="background-color:' . $rowBg . ';">' . $e($u['job_title'] ?? '') . '</td>';
                    $html .= '<td width="22%" style="background-color:' . $rowBg . ';">' . $e($u['email']) . '</td>';
                    $html .= '<td width="13%" style="background-color:' . $rowBg . ';" align="center">' . substr($u['granted_at'] ?? '', 0, 10) . '</td>';
                    $html .= '<td width="14%" style="' . $expStyle . '" align="center">' . $exp['text'] . $deptMark . '</td>';
                    $html .= '</tr>';
                    $fill = !$fill;
                }

                $html .= '</table>';
            }
        }

        return $html;
    }

    private function buildPdfHtmlTable(array $rows, ?string $departedAt = null): string
    {
        $html = '';

        // Departure banner
        if ($departedAt) {
            $depFmt = (new \DateTime($departedAt))->format('d/m/Y');
            $html .= '<table cellpadding="5" cellspacing="0" style="margin-bottom:3mm; background-color:#F8D7DA;">'
                   . '<tr><td style="color:#721c24; font-size:9pt;">'
                   . '<b>&#x26A0; ΑΠΟΧΩΡΗΣΕ: ' . $depFmt . '</b>'
                   . ' &mdash; Τα δικαιώματα έχουν ληγμένη ή επικείμενη ημερομηνία λήξης.'
                   . '</td></tr></table>';
        }

        $html .= '<table border="0.5" cellpadding="4" cellspacing="0">
            <thead>
                <tr style="background-color:#0d6efd; color:#ffffff; font-weight:bold; font-size:8pt;">
                    <th width="14%" align="center">Ονοματεπώνυμο</th>
                    <th width="14%" align="center">Τμήμα</th>
                    <th width="10%" align="center">Τύπος Πόρου</th>
                    <th width="17%" align="center">Πόρος</th>
                    <th width="9%"  align="center">Δικαίωμα</th>
                    <th width="7%"  align="center">Ημ/νία</th>
                    <th width="9%"  align="center">Λήξη</th>
                    <th width="10%" align="center">Εγκρίθηκε από</th>
                    <th width="10%" align="center">Σημειώσεις</th>
                </tr>
            </thead>
            <tbody>';

        $fill = false;
        foreach ($rows as $row) {
            $rowBg      = $fill ? '#f0f7ff' : '#ffffff';
            $name       = htmlspecialchars($row['full_name']       ?? '', ENT_QUOTES, 'UTF-8');
            $dept       = htmlspecialchars($row['department']      ?? '', ENT_QUOTES, 'UTF-8');
            $type       = htmlspecialchars($row['type_label']      ?? '', ENT_QUOTES, 'UTF-8');
            $resource   = htmlspecialchars($row['resource_name']   ?? '', ENT_QUOTES, 'UTF-8');
            $perm       = htmlspecialchars($row['permission_level']?? '', ENT_QUOTES, 'UTF-8');
            $date       = substr($row['granted_at'] ?? '', 0, 10);
            $grantedBy  = htmlspecialchars($row['granted_by_name'] ?? '', ENT_QUOTES, 'UTF-8');
            $notes      = htmlspecialchars($row['notes']           ?? '', ENT_QUOTES, 'UTF-8');

            $exp        = $this->expiryInfo($row['expires_at'] ?? null);
            $expStyle   = $exp['bgPdf']
                ? 'background-color:' . $exp['bgPdf'] . '; color:' . $exp['fontPdf'] . '; font-weight:bold;'
                : 'background-color:' . $rowBg . ';';
            $deptMark   = ($departedAt && !empty($row['expires_at'])
                           && substr($row['expires_at'], 0, 10) === substr($departedAt, 0, 10))
                          ? ' *' : '';

            $html .= '<tr style="font-size:7pt;">
                <td width="14%" style="background-color:' . $rowBg . ';">' . $name . '</td>
                <td width="14%" style="background-color:' . $rowBg . ';">' . $dept . '</td>
                <td width="10%" align="center" style="background-color:' . $rowBg . ';">' . $type . '</td>
                <td width="17%" style="background-color:' . $rowBg . ';">' . $resource . '</td>
                <td width="9%"  align="center" style="background-color:' . $rowBg . ';">' . $perm . '</td>
                <td width="7%"  align="center" style="background-color:' . $rowBg . ';">' . $date . '</td>
                <td width="9%"  align="center" style="' . $expStyle . '">' . $exp['text'] . $deptMark . '</td>
                <td width="10%" style="background-color:' . $rowBg . ';">' . $grantedBy . '</td>
                <td width="10%" style="background-color:' . $rowBg . ';">' . $notes . '</td>
            </tr>';
            $fill = !$fill;
        }

        $html .= '</tbody></table>';
        if ($departedAt) {
            $html .= '<p style="font-size:6pt; color:#666;">* Ημερομηνία λήξης ορίστηκε κατά την αποχώρηση.</p>';
        }
        return $html;
    }

    private function getData(array $filters): array
    {
        // Use a high per_page to get all records
        $result = $this->permModel->getList($filters, 1, 100000);
        return $result['rows'];
    }
}
