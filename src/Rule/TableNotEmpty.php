<?php

namespace Cidilabs\PhpAlly\Rule;

use DOMElement;

class TableNotEmpty extends BaseRule {

    public function id()
    {
        return self::class;
    }

    public function check()
    {
        foreach ($this->getAllElements('table') as $table) {
            if (!$this->elementContainsReadableText($table))
                $this->setIssue($table);
        }

        return count($this->issues);
    }
}
