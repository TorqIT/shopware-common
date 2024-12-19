<?php

namespace Torq\Shopware\Common\Utilities;

use Symfony\Component\HttpFoundation\Response;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

class DownloadFile
{
    public static function downloadAsExcel(array $headers, array $data, string $filename): Response {
        $writer = WriterEntityFactory::createXLSXWriter();
        $tmpXLSX = tempnam(sys_get_temp_dir(), '_export_');
        $writer->openToFile($tmpXLSX);
        $writer->addRow(WriterEntityFactory::createRowFromArray($headers));
        
        foreach ($data as $row) {
            $writer->addRow(WriterEntityFactory::createRowFromArray($row));
        }

        $writer->close();       

        $content = file_get_contents($tmpXLSX); //fopen($tmpXLSX ?: '', 'w+');
        $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        $response = new Response($content ?: null, 200, ['Content-Type' => $contentType,
                                                        'Content-Disposition' => 'filename=' . $filename]);
        $response->setLastModified((new \DateTimeImmutable()));

        unlink($tmpXLSX);

        return $response;
    }
}
