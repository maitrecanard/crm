<?php
$p = \App\Models\Project::first();
$b = $p->bugs()->create(['type'=>'bug','titre'=>'Test ref','gravite'=>'majeur','statut'=>'nouveau']);
echo "référence générée : ".$b->fresh()->reference."\n";
$b->delete();
