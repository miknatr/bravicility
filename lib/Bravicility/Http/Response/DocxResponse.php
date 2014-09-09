<?php

namespace Bravicility\Http\Response;

class DocxResponse extends Response
{
    public function __construct($filename, $xlsxBody)
    {
        parent::__construct($xlsxBody);
        $this->addHeader('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->addHeader("Content-Disposition: attachment; filename={$filename}");
    }
}
