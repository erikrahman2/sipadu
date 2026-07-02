<?php
use App\Models\Layan;
$icons = [1 => 'fas fa-file-circle-xmark', 2 => 'fas fa-heart', 3 => 'fas fa-id-card', 4 => 'fas fa-landmark'];
$l = Layan::all();
foreach ($l as $r) {
    $idx = (int)$r->urut;
    $r->icon = $icons[$idx] ?? 'fas fa-file-alt';
    $r->save();
}
echo "Icons updated for all Layan records\n";
