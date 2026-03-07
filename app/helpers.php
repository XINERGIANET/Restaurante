<?php

if (!function_exists('effective_branch_id')) {
    /**
     * Devuelve el branch_id a usar para filtrar listados.
     * Si el usuario es Administrador de sistema, devuelve null (ve todo).
     * Si no, devuelve el branch_id de la sesión.
     */
    function effective_branch_id(): ?int
    {
        $user = auth()->user();
        if ($user && $user->isSystemAdmin()) {
            return null;
        }
        $id = session('branch_id');
        return $id !== null ? (int) $id : null;
    }
}
