<?php

namespace Mf\Migrations\Lib;

use RecursiveFilterIterator;


class MigrationsFilterIterator extends RecursiveFilterIterator {


    public function accept() {
        if (!$this->current()->isFile()){
            return true;
        }
        return $this->current()->isFile() && preg_match('/migrations\/(Version(\d+))\.php/', $this->current()->getpathName()."/".$this->current()->getFilename(), $matches);
    }
}
