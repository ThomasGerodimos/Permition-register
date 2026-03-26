<?php

namespace App\Services;

use App\Models\{Permission, User};
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\{Fill, Border, Alignment, Font};
use TCPDF;

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
        $rows = $this->getData($filters);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Δικαιώματα');

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
            $sheet->setCellValue($col . '1', $label);
        }

        // Header style
        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0d6efd']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A1:K1')->applyFromArray($headerStyle);

        // Data rows
        $rowNum = 2;
        foreach ($rows as $row) {
            $sheet->fromArray([
                $row['username'],
                $row['full_name'],
                $row['email'],
                $row['department'],
                $row['type_label'],
                $row['resource_name'],
                $row['permission_level'],
                $row['granted_by_name'] ?? '',
                $row['granted_at'],
                $row['expires_at'] ?? '',
                $row['notes'] ?? '',
            ], null, 'A' . $rowNum);

            // Alternate row color
            if ($rowNum % 2 === 0) {
                $sheet->getStyle('A' . $rowNum . ':K' . $rowNum)
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('f0f7ff');
            }
            $rowNum++;
        }

        // Auto-width
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Freeze header row
        $sheet->freezePane('A2');

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
        $rows = $this->getData($filters);
        $appName = \App\Core\Config::get('app_name', 'Μητρώο Δικαιωμάτων');

        $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator($appName);
        $pdf->SetAuthor($appName);
        $pdf->SetTitle($title);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->AddPage();

        // Title
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 10, $title, 0, 1, 'C');
        $pdf->SetFont('dejavusans', '', 9);
        $pdf->Cell(0, 6, 'Εκτύπωση: ' . date('d/m/Y H:i'), 0, 1, 'C');
        $pdf->Ln(3);

        // Build HTML — grouped by resource if department filter, flat table otherwise
        if (!empty($filters['department'])) {
            $html = $this->buildDeptPdfHtml($rows);
        } else {
            $html = $this->buildPdfHtmlTable($rows);
        }
        $pdf->SetFont('dejavusans', '', 7);
        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->Output('permissions_' . date('Ymd_His') . '.pdf', 'D');
        exit;
    }

    // ── File output versions (for email attachments) ──────────────────────────

    public function exportExcelToFile(array $filters, string $filePath): void
    {
        $rows        = $this->getData($filters);
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Δικαιώματα');

        $headers = ['A' => 'Χρήστης','B' => 'Ονοματεπώνυμο','C' => 'Email','D' => 'Τμήμα','E' => 'Τύπος Πόρου','F' => 'Πόρος','G' => 'Δικαίωμα','H' => 'Εγκρίθηκε από','I' => 'Ημ/νία','J' => 'Λήξη','K' => 'Σημειώσεις'];
        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . '1', $label);
        }
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0d6efd']],
        ]);

        $rowNum = 2;
        foreach ($rows as $row) {
            $sheet->fromArray([$row['username'],$row['full_name'],$row['email'],$row['department'],$row['type_label'],$row['resource_name'],$row['permission_level'],$row['granted_by_name']??'',$row['granted_at'],$row['expires_at']??'',$row['notes']??''], null, 'A'.$rowNum++);
        }
        foreach (range('A','K') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);
    }

    public function exportPdfToFile(array $filters, string $filePath, string $title = 'Αναφορά Δικαιωμάτων'): void
    {
        $rows    = $this->getData($filters);
        $appName = \App\Core\Config::get('app_name', 'Μητρώο Δικαιωμάτων');

        $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator($appName);
        $pdf->SetTitle($title);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->AddPage();

        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 10, $title, 0, 1, 'C');
        $pdf->SetFont('dejavusans', '', 9);
        $pdf->Cell(0, 6, 'Εκτύπωση: ' . date('d/m/Y H:i'), 0, 1, 'C');
        $pdf->Ln(3);

        if (!empty($filters['department'])) {
            $html = $this->buildDeptPdfHtml($rows);
        } else {
            $html = $this->buildPdfHtmlTable($rows);
        }
        $pdf->SetFont('dejavusans', '', 7);
        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->Output($filePath, 'F');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Build grouped-by-resource PDF HTML (for department exports).
     * Mimics the on-screen layout: Resource header → permission level badge → users table.
     */
    private function buildDeptPdfHtml(array $rows): string
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
                $html .= '<th width="25%">Ονοματεπώνυμο</th>';
                $html .= '<th width="15%">Username</th>';
                $html .= '<th width="20%">Θέση</th>';
                $html .= '<th width="25%">Email</th>';
                $html .= '<th width="15%">Ημ/νία</th>';
                $html .= '</tr>';

                $fill = false;
                foreach ($users as $u) {
                    $bg = $fill ? ' style="background-color:#fafcff; font-size:7pt;"' : ' style="font-size:7pt;"';
                    $html .= '<tr' . $bg . '>';
                    $html .= '<td width="25%">' . $e($u['full_name']) . '</td>';
                    $html .= '<td width="15%">' . $e($u['username']) . '</td>';
                    $html .= '<td width="20%">' . $e($u['job_title'] ?? '') . '</td>';
                    $html .= '<td width="25%">' . $e($u['email']) . '</td>';
                    $html .= '<td width="15%">' . substr($u['granted_at'] ?? '', 0, 10) . '</td>';
                    $html .= '</tr>';
                    $fill = !$fill;
                }

                $html .= '</table>';
            }
        }

        return $html;
    }

    private function buildPdfHtmlTable(array $rows): string
    {
        $html = '<table border="0.5" cellpadding="4" cellspacing="0">
            <thead>
                <tr style="background-color:#0d6efd; color:#ffffff; font-weight:bold; font-size:8pt;">
                    <th width="15%" align="center">Ονοματεπώνυμο</th>
                    <th width="17%" align="center">Τμήμα</th>
                    <th width="11%" align="center">Τύπος Πόρου</th>
                    <th width="19%" align="center">Πόρος</th>
                    <th width="10%" align="center">Δικαίωμα</th>
                    <th width="8%" align="center">Ημ/νία</th>
                    <th width="10%" align="center">Εγκρίθηκε από</th>
                    <th width="10%" align="center">Σημειώσεις</th>
                </tr>
            </thead>
            <tbody>';

        $fill = false;
        foreach ($rows as $row) {
            $bg = $fill ? ' style="background-color:#f0f7ff;"' : '';
            $name       = htmlspecialchars($row['full_name'] ?? '', ENT_QUOTES, 'UTF-8');
            $dept       = htmlspecialchars($row['department'] ?? '', ENT_QUOTES, 'UTF-8');
            $type       = htmlspecialchars($row['type_label'] ?? '', ENT_QUOTES, 'UTF-8');
            $resource   = htmlspecialchars($row['resource_name'] ?? '', ENT_QUOTES, 'UTF-8');
            $perm       = htmlspecialchars($row['permission_level'] ?? '', ENT_QUOTES, 'UTF-8');
            $date       = substr($row['granted_at'] ?? '', 0, 10);
            $grantedBy  = htmlspecialchars($row['granted_by_name'] ?? '', ENT_QUOTES, 'UTF-8');
            $notes      = htmlspecialchars($row['notes'] ?? '', ENT_QUOTES, 'UTF-8');

            $html .= '<tr' . $bg . ' style="font-size:7pt;">
                <td width="15%">' . $name . '</td>
                <td width="17%">' . $dept . '</td>
                <td width="11%" align="center">' . $type . '</td>
                <td width="19%">' . $resource . '</td>
                <td width="10%" align="center">' . $perm . '</td>
                <td width="8%" align="center">' . $date . '</td>
                <td width="10%">' . $grantedBy . '</td>
                <td width="10%">' . $notes . '</td>
            </tr>';
            $fill = !$fill;
        }

        $html .= '</tbody></table>';
        return $html;
    }

    private function getData(array $filters): array
    {
        // Use a high per_page to get all records
        $result = $this->permModel->getList($filters, 1, 100000);
        return $result['rows'];
    }
}
