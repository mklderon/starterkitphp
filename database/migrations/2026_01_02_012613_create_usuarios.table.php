<?php
use System\Scribe\Table;

return Table::create('usuarios')
    ->id()
    ->str('nombre')
    ->str('email')
    ->str('password')
    ->str('role', 'viewer')  // Campo role con valor por defecto
    ->stamp();
