<?php
// download_template.php - Separate file for template downloads
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Basic admin check (uncomment when you have admin authentication)
/*
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied');
}
*/

$format = $_GET['format'] ?? 'csv';

if ($format === 'csv') {
    downloadCSVTemplate();
} elseif ($format === 'excel') {
    downloadExcelTemplate();
} else {
    header('HTTP/1.0 400 Bad Request');
    echo json_encode(['error' => 'Invalid format specified']);
    exit();
}

/**
 * Generate and download CSV template
 */
function downloadCSVTemplate() {
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="bulk_users_template.csv"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8 (helps with Excel compatibility)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write headers
    fputcsv($output, [
        'username',
        'email', 
        'password',
        'role',
        'status'
    ]);
    
    // Write sample data with various roles
    $sampleData = [
        ['John Doe', 'john.doe@example.com', 'password123', 'student', 'active'],
        ['Jane Smith', 'jane.smith@example.com', 'securepass456', 'staff', 'pending'],
        ['Mike Johnson', 'mike.j@example.com', 'mypassword789', 'student', 'active'],
        ['Sarah Wilson', 'sarah.w@example.com', 'adminpass000', 'admin', 'active'],
        ['Tom Brown', 'tom.brown@example.com', 'parentpass111', 'parent', 'pending'],
        ['Lisa Davis', 'lisa.davis@example.com', 'staffpass222', 'staff', 'active'],
        ['Robert Taylor', 'robert.t@example.com', 'studentpass333', 'student', 'pending'],
        ['Emma Martinez', 'emma.m@example.com', 'parentpass444', 'parent', 'active']
    ];
    
    foreach ($sampleData as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit();
}

/**
 * Generate and download Excel template (using simple XML format)
 */
function downloadExcelTemplate() {
    // Set headers for Excel download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="bulk_users_template.xlsx"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // For now, we'll create a simple Excel-compatible XML file
    // In a production environment, you'd use PhpSpreadsheet library
    
    $excel_content = generateSimpleExcelXML();
    echo $excel_content;
    exit();
}

/**
 * Generate simple Excel XML (SpreadsheetML format)
 * This creates a basic Excel file without requiring external libraries
 */
function generateSimpleExcelXML() {
    $headers = ['username', 'email', 'password', 'role', 'status'];
    $sampleData = [
        ['John Doe', 'john.doe@example.com', 'password123', 'student', 'active'],
        ['Jane Smith', 'jane.smith@example.com', 'securepass456', 'staff', 'pending'],
        ['Mike Johnson', 'mike.j@example.com', 'mypassword789', 'student', 'active'],
        ['Sarah Wilson', 'sarah.w@example.com', 'adminpass000', 'admin', 'active'],
        ['Tom Brown', 'tom.brown@example.com', 'parentpass111', 'parent', 'pending'],
        ['Lisa Davis', 'lisa.davis@example.com', 'staffpass222', 'staff', 'active'],
        ['Robert Taylor', 'robert.t@example.com', 'studentpass333', 'student', 'pending'],
        ['Emma Martinez', 'emma.m@example.com', 'parentpass444', 'parent', 'active']
    ];
    
    $xml = '<?xml version="1.0" encoding="UTF-8"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:o="urn:schemas-microsoft-com:office:office"
    xmlns:x="urn:schemas-microsoft-com:office:excel"
    xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:html="http://www.w3.org/TR/REC-html40">
    
    <Styles>
        <Style ss:ID="headerStyle">
            <Font ss:Bold="1" ss:Color="#FFFFFF"/>
            <Interior ss:Color="#2563eb" ss:Pattern="Solid"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
            </Borders>
        </Style>
        <Style ss:ID="dataStyle">
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>
                <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>
                <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>
            </Borders>
        </Style>
        <Style ss:ID="requiredStyle">
            <Interior ss:Color="#FEF3C7" ss:Pattern="Solid"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>
                <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>
                <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>
            </Borders>
        </Style>
    </Styles>
    
    <Worksheet ss:Name="Bulk Users Template">
        <Table>
            <Row>';
    
    // Add headers
    foreach ($headers as $header) {
        $xml .= '<Cell ss:StyleID="headerStyle"><Data ss:Type="String">' . htmlspecialchars($header) . '</Data></Cell>';
    }
    $xml .= '</Row>';
    
    // Add sample data
    foreach ($sampleData as $row) {
        $xml .= '<Row>';
        foreach ($row as $index => $cell) {
            $styleId = ($index < 4) ? 'requiredStyle' : 'dataStyle'; // First 4 columns are required
            $xml .= '<Cell ss:StyleID="' . $styleId . '"><Data ss:Type="String">' . htmlspecialchars($cell) . '</Data></Cell>';
        }
        $xml .= '</Row>';
    }
    
    // Add instructions row
    $xml .= '<Row></Row>'; // Empty row for spacing
    $xml .= '<Row>
        <Cell ss:MergeAcross="4" ss:StyleID="dataStyle">
            <Data ss:Type="String">INSTRUCTIONS: Fill in the data above. Required columns are highlighted in yellow. Valid roles: student, staff, admin, parent. Valid statuses: active, pending, suspended.</Data>
        </Cell>
    </Row>';
    
    $xml .= '</Table>
    </Worksheet>
</Workbook>';
    
    return $xml;
}

/**
 * Alternative: Generate Excel using PhpSpreadsheet (if library is available)
 */
function generateExcelWithPhpSpreadsheet() {
    // Uncomment this if you have PhpSpreadsheet installed
    /*
    require_once 'vendor/autoload.php';
    
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    use PhpOffice\PhpSpreadsheet\Style\Fill;
    use PhpOffice\PhpSpreadsheet\Style\Color;
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Bulk Users Template');
    
    // Headers
    $headers = ['username', 'email', 'password', 'role', 'status'];
    $sheet->fromArray($headers, null, 'A1');
    
    // Style headers
    $headerRange = 'A1:E1';
    $sheet->getStyle($headerRange)->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '2563eb']],
        'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
    ]);
    
    // Sample data
    $sampleData = [
        ['John Doe', 'john.doe@example.com', 'password123', 'student', 'active'],
        ['Jane Smith', 'jane.smith@example.com', 'securepass456', 'staff', 'pending'],
        // ... more sample data
    ];
    
    $sheet->fromArray($sampleData, null, 'A2');
    
    // Auto-size columns
    foreach (range('A', 'E') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Output
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    */
    
    // Fallback to simple XML if PhpSpreadsheet not available
    return generateSimpleExcelXML();
}

/**
 * Handle template info requests
 */
if (isset($_GET['info'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'csv_info' => [
            'format' => 'Comma-separated values',
            'compatibility' => 'All spreadsheet applications',
            'encoding' => 'UTF-8 with BOM',
            'size' => 'Small file size'
        ],
        'excel_info' => [
            'format' => 'Microsoft Excel SpreadsheetML',
            'compatibility' => 'Excel 2003 and newer',
            'features' => 'Styled headers, data validation hints',
            'size' => 'Larger file size but richer formatting'
        ],
        'requirements' => [
            'required_columns' => ['username', 'email', 'password', 'role'],
            'optional_columns' => ['status'],
            'valid_roles' => ['student', 'staff', 'admin', 'parent'],
            'valid_statuses' => ['active', 'pending', 'suspended'],
            'max_records' => 1000,
            'max_file_size' => '5MB'
        ]
    ]);
    exit();
}
?>