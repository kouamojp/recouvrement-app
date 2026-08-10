<?php

namespace App\Validation;

use Jenssegers\Mongodb\Validation\DatabasePresenceVerifier;

/**
 * Le vérificateur du package construit un regex non ancré, donc "a@b.com"
 * est considéré comme un doublon de "aa@b.com". On ancre le motif pour
 * obtenir une vraie égalité (insensible à la casse, comme le package).
 */
class MongoPresenceVerifier extends DatabasePresenceVerifier
{
    public function getCount($collection, $column, $value, $excludeId = null, $idColumn = null, array $extra = [])
    {
        $query = $this->table($collection)
            ->where($column, 'regex', '/^' . preg_quote($value, '/') . '$/i');

        if ($excludeId !== null && $excludeId != 'NULL') {
            $query->where($idColumn ?: '_id', '<>', $excludeId);
        }

        foreach ($extra as $key => $extraValue) {
            $this->addWhere($query, $key, $extraValue);
        }

        return $query->count();
    }

    public function getMultiCount($collection, $column, array $values, array $extra = [])
    {
        $quoted = array_map(function ($value) {
            return preg_quote($value, '/');
        }, $values);

        $query = $this->table($collection)
            ->where($column, 'regex', '/^(' . implode('|', $quoted) . ')$/i');

        foreach ($extra as $key => $extraValue) {
            $this->addWhere($query, $key, $extraValue);
        }

        return $query->count();
    }
}
