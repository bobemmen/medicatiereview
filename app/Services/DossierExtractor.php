<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpWord\Element\Cell;
use PhpOffice\PhpWord\Element\Link;
use PhpOffice\PhpWord\Element\ListItemRun;
use PhpOffice\PhpWord\Element\Row;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use RuntimeException;
use Smalot\PdfParser\Parser as PdfParser;

class DossierExtractor
{
    public function extractFromUpload(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();

        return match ($extension) {
            'pdf' => $this->extractPdf($path),
            'docx', 'doc' => $this->extractWord($path, $extension),
            'txt', 'md' => (string) file_get_contents($path),
            default => throw new RuntimeException(
                "Bestandstype '{$extension}' wordt niet ondersteund. Gebruik PDF, DOCX, DOC of TXT."
            ),
        };
    }

    private function extractPdf(string $path): string
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($path);

        return $this->normalise($pdf->getText());
    }

    private function extractWord(string $path, string $extension): string
    {
        $readerType = $extension === 'doc' ? 'MsDoc' : 'Word2007';
        $phpWord = WordIOFactory::load($path, $readerType);

        $text = '';

        foreach ($phpWord->getSections() as $section) {
            $text .= $this->extractElements($section->getElements());
        }

        return $this->normalise($text);
    }

    private function extractElements(array $elements): string
    {
        $text = '';

        foreach ($elements as $element) {
            // Tables: getRows() → getCells() → getElements()
            if ($element instanceof Table) {
                $text .= $this->extractTable($element) . "\n";
                continue;
            }

            // Inline containers: kinderen aaneenrijgen op één regel.
            if ($element instanceof TextRun || $element instanceof ListItemRun || $element instanceof Link) {
                $text .= $this->extractInline($element->getElements()) . "\n";
                continue;
            }

            // Overige containers: recursief, elk kind op eigen regel.
            $children = method_exists($element, 'getElements') ? $element->getElements() : [];
            if (!empty($children)) {
                $text .= $this->extractElements($children);
                continue;
            }

            if (method_exists($element, 'getText')) {
                $value = $element->getText();
                if (is_string($value) && $value !== '') {
                    $text .= $value . "\n";
                }
            }
        }

        return $text;
    }

    private function extractInline(array $elements): string
    {
        $parts = [];
        foreach ($elements as $element) {
            if (method_exists($element, 'getText')) {
                $value = $element->getText();
                if (is_string($value)) {
                    $parts[] = $value;
                }
            } elseif (method_exists($element, 'getElements')) {
                $parts[] = $this->extractInline($element->getElements());
            }
        }
        return implode('', $parts);
    }

    private function extractTable(Table $table): string
    {
        $rows = [];

        foreach ($table->getRows() as $row) {
            if (!$row instanceof Row) {
                continue;
            }

            $cells = [];
            foreach ($row->getCells() as $cell) {
                if (!$cell instanceof Cell) {
                    continue;
                }
                $cells[] = trim(preg_replace('/\s+/', ' ', $this->extractElements($cell->getElements())) ?? '');
            }

            $rows[] = implode(' | ', array_filter($cells, fn($c) => $c !== ''));
        }

        return implode("\n", array_filter($rows, fn($r) => $r !== ''));
    }

    private function normalise(string $text): string
    {
        $text = preg_replace('/\r\n?/', "\n", $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;

        return trim($text);
    }
}
