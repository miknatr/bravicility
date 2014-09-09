<?php

namespace Bravicility\Http\Response;

class XlsxResponse extends Response
{
    public function __construct($filename, $xlsxBody)
    {
        parent::__construct($xlsxBody);
        $this->addHeader('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->addHeader("Content-Disposition: attachment; filename={$filename}");
    }
}
