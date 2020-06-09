<?php
    
    namespace App\Infrastructure\Traits;
    trait HasActive
    {
        public function scopeActive($query)
        {
            $tableName = $this->getTable();
            return $query->where("{$tableName}.is_active", '=', 1);
        }
    }
