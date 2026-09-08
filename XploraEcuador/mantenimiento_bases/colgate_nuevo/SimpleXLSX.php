<?php

/**
 * SimpleXLSX - Clase para leer archivos Excel (.xlsx)
 * Versión simplificada y funcional
 */
class SimpleXLSX {
    private $sheets = [];
    private static $error = '';

    public static function parse($filename) {
        if (!file_exists($filename)) {
            self::$error = 'Archivo no encontrado';
            return false;
        }

        // Verificar que ZipArchive esté disponible
        if (!class_exists('ZipArchive')) {
            self::$error = 'ZipArchive no está disponible en el servidor';
            return false;
        }

        $zip = new ZipArchive();
        $openResult = $zip->open($filename);
        
        if ($openResult !== TRUE) {
            self::$error = 'No se pudo abrir el archivo Excel (código: ' . $openResult . ')';
            return false;
        }

        // Leer strings compartidos
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        $sharedStrings = [];
        
        if ($xml) {
            $xmlObject = @simplexml_load_string($xml);
            if ($xmlObject) {
                foreach ($xmlObject->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string)$si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $r) {
                            if (isset($r->t)) {
                                $text .= (string)$r->t;
                            }
                        }
                        $sharedStrings[] = $text;
                    }
                }
            }
        }

        // Leer la primera hoja
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (!$xml) {
            self::$error = 'No se pudo leer la hoja de Excel';
            return false;
        }

        $xmlObject = @simplexml_load_string($xml);
        if (!$xmlObject) {
            self::$error = 'Error al parsear el XML de la hoja';
            return false;
        }

        $rows = [];

        if (isset($xmlObject->sheetData->row)) {
            foreach ($xmlObject->sheetData->row as $row) {
                $rowData = [];
                $colIndex = 0;
                
                foreach ($row->c as $cell) {
                    // Obtener el índice de columna de la referencia de celda (ej: A1, B1, etc)
                    $cellRef = (string)$cell['r'];
                    preg_match('/([A-Z]+)/', $cellRef, $matches);
                    $col = $matches[1];
                    $currentColIndex = self::columnIndexFromString($col);
                    
                    // Rellenar celdas vacías si hay saltos
                    while ($colIndex < $currentColIndex) {
                        $rowData[] = '';
                        $colIndex++;
                    }
                    
                    $value = '';
                    if (isset($cell->v)) {
                        // Verificar si es una referencia a string compartido
                        if (isset($cell['t']) && (string)$cell['t'] == 's') {
                            $index = (int)$cell->v;
                            if (isset($sharedStrings[$index])) {
                                $value = $sharedStrings[$index];
                            }
                        } else {
                            $value = (string)$cell->v;
                        }
                    }
                    
                    $rowData[] = $value;
                    $colIndex++;
                }
                
                $rows[] = $rowData;
            }
        }

        $obj = new self();
        $obj->sheets = $rows;
        return $obj;
    }

    public function rows() {
        return $this->sheets;
    }

    public static function parseError() {
        return self::$error;
    }

    /**
     * Convierte letra de columna a índice numérico (A=0, B=1, AA=26, etc)
     */
    private static function columnIndexFromString($column) {
        $column = strtoupper($column);
        $index = 0;
        $length = strlen($column);
        
        for ($i = 0; $i < $length; $i++) {
            $index = $index * 26 + (ord($column[$i]) - ord('A'));
        }
        
        return $index;
    }
}