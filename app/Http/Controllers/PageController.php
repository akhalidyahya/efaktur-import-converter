<?php
namespace App\Http\Controllers;

require_once(dirname(__DIR__, 3) . "/vendor/autoload.php");

use PhpOffice\PhpSpreadsheet\Reader\Xlsx as ReaderXlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as WriterXlsx;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index()
    {
        return view('index');
    }

    public function upload(Request $request)
    {
        // Validate the uploaded file
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        // Get the uploaded file
        $file = $request->file('file');

        // Read the entire file as a string
        $csvContent = file_get_contents($file->getPathname());
        $csvData = $this->parseCsvWithNewlines($csvContent);

        array_splice($csvData, 0, 3);

        // Initialize variables
        $invoices = [];
        $currentFK = null;

        // Process each row
        foreach ($csvData as $key => $row) {
            if($row[0] == 'FK') {
                $currentFK = $row;
            }

            if($row[0] == 'OF') {
                $currentFK['OF'][] = $row;
            }

            if((@$csvData[$key+1][0] == 'FK' && !empty($currentFK)) || empty(@$csvData[$key+1][0])) {
                $invoices[] = $currentFK;
            }
        }

        return $csvData;

        $reader = new ReaderXlsx();
        $spreadsheet = $reader->load(public_path('base_template.xlsx'));

        // ----------------------- Sheet 1 ---------------------------
        $worksheet = $spreadsheet->getSheet(0);
        // Set NPWP Penjual
        $worksheet->setCellValue('C1', '0809794522067000');

        $baris = 1; // Used in Sheet 1 (A4), act as ID of eFaktur
        $row_num = 4; // Used in Sheet 1 (A4), to mark where to start write invoice
        $inv_item_row_num = 2; // Used in Sheet 2, to mark where to start write invoice item
        foreach ($invoices as $key => $data) {
            // Set "Baris" start from A4
            // "Baris" here act as an Identifier
            $worksheet->setCellValue('A'.$row_num, $baris);
            // Set "Tanggal Faktur"
            $worksheet->setCellValue('B'.$row_num, $data[6]);
            // Set "Jenis Faktur"
            $worksheet->setCellValue('C'.$row_num, 'Normal');
            // Set "Kode Transaksi"
            $worksheet->setCellValue('D'.$row_num, $data[1]);
            // Set "Keterangan Tambahan"
            $worksheet->setCellValue('E'.$row_num, '');
            // Set "Dokumen Pendukung"
            $worksheet->setCellValue('F'.$row_num, '');
            // Set "Referensi"
            $worksheet->setCellValue('G'.$row_num, str_replace('Ref: INV#','',$data[18]));
            // Set "Cap Fasilitas"
            $worksheet->setCellValue('H'.$row_num, '');
            // Set "ID TKU Penjual"
            $worksheet->setCellValue('I'.$row_num, '0809794522067000000000');
            // Set "NPWP/NIK Pembeli"
            $worksheet->setCellValue('J'.$row_num, str_pad($data[7],16,"0"));
            // Set "Jenis ID Pembeli"
            $worksheet->setCellValue('K'.$row_num, 'TIN');
            // Set "Negara Pembeli"
            $worksheet->setCellValue('L'.$row_num, 'IDN');
            // Set "Nomor Dokumen Pembeli"
            $worksheet->setCellValue('M'.$row_num, '-');
            // Set "Nama Pembeli"
            $worksheet->setCellValue('N'.$row_num, $data[8]);
            // Set "Alamat Pembeli"
            $worksheet->setCellValue('O'.$row_num, $data[9]);
            // Set "Email Pembeli"
            $worksheet->setCellValue('P'.$row_num, '');
            // Set "ID TKU Pembeli"
            $worksheet->setCellValue('Q'.$row_num, str_pad($data[7],16,"0").'000000');

            $item_collection = $data['OF'];

            foreach($item_collection as $item_key => $item)
            {
                // ------------------ Sheet 2 -------------------------
                $worksheet2 = $spreadsheet->getSheet(1);
                // Set "Baris"
                // "Baris" here refer to "Baris" in sheet 1
                $worksheet2->setCellValue('A'.$inv_item_row_num, $baris);
                // Set "Barang/Jasa"
                $worksheet2->setCellValue('B'.$inv_item_row_num, 'B');
                // Set "Kode Barang Jasa"
                $worksheet2->setCellValue('C'.$inv_item_row_num, '160105');
                // Set "Nama Barang/Jasa"
                $worksheet2->setCellValue('D'.$inv_item_row_num, $item[2]);
                // Set "Nama Satuan Ukur"
                $worksheet2->setCellValue('E'.$inv_item_row_num, 'UM.0033');
                // Set "Harga Satuan"
                $worksheet2->setCellValue('F'.$inv_item_row_num, $item[3]);
                // Set "Jumlah Barang Jasa"
                $worksheet2->setCellValue('G'.$inv_item_row_num, '1');
                // Set "Total Diskon"
                $worksheet2->setCellValue('H'.$inv_item_row_num, $item[6]);
                // Set "DPP"
                $worksheet2->setCellValue('I'.$inv_item_row_num, $item[7]);
                // Set "DPP Nilai Lain"
                $worksheet2->setCellValue('J'.$inv_item_row_num, floor($item[7] * 11/12));
                // Set "Tarif PPN"
                $worksheet2->setCellValue('K'.$inv_item_row_num, 12);
                // Set "PPN"
                $worksheet2->setCellValue('L'.$inv_item_row_num, floor($item[7] * 11/100));
                // Set "Tarif PPnBM"
                $worksheet2->setCellValue('M'.$inv_item_row_num, $item[9]);
                // Set "PPnBM"
                $worksheet2->setCellValue('N'.$inv_item_row_num, $item[9] * floor($item[7] * 11/12) / 100);
                
                $inv_item_row_num++;
                // ------------------ Sheet 2 END ---------------------
            }

            // Add "END" after last invoice
            if($key == array_key_last($invoices)) {
                $worksheet->setCellValue('A'.$row_num + 1, 'END');
                $worksheet2->setCellValue('A'.$inv_item_row_num, 'END');
            }

            $baris++;
            $row_num++;

        }
        // ----------------------- Sheet 1 END ---------------------------

        $writer = new WriterXlsx($spreadsheet);
        $file_name = 'Coretax_Efaktur_converted_at' . time() . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . urlencode($file_name) . '"');
        $writer->save('php://output');
        exit();
    }

    private function parseCsvWithNewlines($csvContent)
    {
        $rows = [];
        $row = [];
        $field = '';
        $inQuotes = false;

        $length = strlen($csvContent);
        for ($i = 0; $i < $length; $i++) {
            $char = $csvContent[$i];

            if ($char === '"') {
                $inQuotes = !$inQuotes; // Toggle quotes
            } elseif ($char === ',' && !$inQuotes) {
                // End of field
                $row[] = $field;
                $field = '';
            } elseif ($char === "\n" && !$inQuotes) {
                // End of row
                $row[] = $field;
                $rows[] = $row;
                $row = [];
                $field = '';
            } else {
                $field .= $char;
            }
        }

        // Add the last row if it exists
        if (!empty($field)) {
            $row[] = $field;
        }
        if (!empty($row)) {
            $rows[] = $row;
        }

        return $rows;
    }

    private function csvToArray($file)
    {
        $data = [];

        // Open the file for reading
        if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
            // Read the file line by line
            while (($line = fgets($handle)) !== false) {
                // Remove any trailing newline characters
                $line = rtrim($line, "\r\n");

                // Split the line into an array using a comma as the delimiter
                $row = str_getcsv($line, ',');

                // Add the row to the data array
                $data[] = $row;
            }

            // Close the file handle
            fclose($handle);
        }

        return $data;
    }
}
