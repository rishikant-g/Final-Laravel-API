<?php
    
    namespace App\Infrastructure\Traits;
    trait MarkAsInactive
    {
        function markAsInactive()
        {
            $this->update(array(
                'is_active' => 0
            ));
        }
    }
