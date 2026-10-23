<?php

namespace App\Services\WhatsApp;

use RuntimeException;
use XMLWriter;
use ZipArchive;

class ReportExcel
{
    /** Write text cells to preserve phone numbers and prevent formula execution. */
    public function create(iterable $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'wa-xlsx-');
        $sheet = tempnam(sys_get_temp_dir(), 'wa-sheet-');
        $zip = new ZipArchive;
        try {
            if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Unable to create the Excel file.');
            }
            $xml = new XMLWriter;
            $xml->openUri($sheet);
            $xml->startDocument('1.0', 'UTF-8');
            $xml->startElement('worksheet');
            $xml->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $xml->startElement('sheetViews');
            $xml->startElement('sheetView');
            $xml->writeAttribute('workbookViewId', '0');
            $xml->startElement('pane');
            foreach (['ySplit' => '1', 'topLeftCell' => 'A2', 'activePane' => 'bottomLeft', 'state' => 'frozen'] as $key => $value) {
                $xml->writeAttribute($key, $value);
            }
            $xml->endElement();
            $xml->endElement();
            $xml->endElement();
            $xml->startElement('cols');
            foreach ([24, 18, 14, 24, 24, 24, 60] as $index => $width) {
                $xml->startElement('col');
                foreach (['min' => $index + 1, 'max' => $index + 1, 'width' => $width, 'customWidth' => 1] as $key => $value) {
                    $xml->writeAttribute($key, (string) $value);
                }
                $xml->endElement();
            }
            $xml->endElement();
            $xml->startElement('sheetData');
            $number = 0;
            foreach ($rows as $row) {
                if (++$number > 1048576) {
                    throw new RuntimeException('The Excel row limit was exceeded. Apply filters or export as CSV.');
                }
                $xml->startElement('row');
                $xml->writeAttribute('r', (string) $number);
                foreach (array_values($row) as $index => $value) {
                    $xml->startElement('c');
                    $xml->writeAttribute('r', chr(65 + $index).$number);
                    $xml->writeAttribute('t', 'inlineStr');
                    $xml->writeAttribute('s', $number === 1 ? '1' : '0');
                    $xml->startElement('is');
                    $xml->startElement('t');
                    $xml->writeAttribute('xml:space', 'preserve');
                    $xml->text(mb_substr(preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', (string) $value), 0, 32767));
                    $xml->endElement();
                    $xml->endElement();
                    $xml->endElement();
                }
                $xml->endElement();
                if ($number % 500 === 0) {
                    $xml->flush();
                }
            }
            $xml->endElement();
            $xml->startElement('autoFilter');
            $xml->writeAttribute('ref', 'A1:G'.$number);
            $xml->endElement();
            $xml->endElement();
            $xml->endDocument();
            $xml->flush();
            unset($xml);
            $zip->addFile($sheet, 'xl/worksheets/sheet1.xml');
            $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
            $zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
            $zip->addFromString('xl/workbook.xml', '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="WhatsApp Reports" sheetId="1" r:id="rId1"/></sheets></workbook>');
            $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
            $zip->addFromString('xl/styles.xml', '<?xml version="1.0"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF166534"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/><xf numFmtId="49" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>');
            if (! $zip->close()) {
                throw new RuntimeException('Unable to save the Excel file.');
            }

            return $path;
        } catch (\Throwable $exception) {
            unset($xml, $zip);
            @unlink($path);
            throw $exception;
        } finally {
            @unlink($sheet);
        }
    }
}
