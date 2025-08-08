<?php
use Spatie\Permission\Models\Role;
Role::whereIn('name', ['team_hc', 'department', 'user'])->delete();
echo "Roles deleted.";
