<?php

namespace App\Http\Controllers\Admin\Traits;

use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Rétablit la barre de recherche des listes sur MongoDB.
 *
 * Par défaut, Backpack construit le critère via
 * Search::getColumnWithTableNamePrefixed(), qui préfixe le champ par le nom de
 * la table : « debiteurs.societe_debitrice ». Ce préfixe est indispensable en
 * SQL pour lever l'ambiguïté sur les jointures, mais sur MongoDB un point
 * désigne un sous-document. La requête porte alors sur un champ imbriqué qui
 * n'existe dans aucun document : elle ne remonte jamais rien, et surtout sans
 * lever la moindre erreur — la liste se vide simplement dès qu'on tape.
 *
 * On remplace donc la logique par défaut des colonnes textuelles par un
 * orWhere sur le nom de champ nu.
 */
trait RechercheMongo
{
    /**
     * À appeler en fin de setupListOperation(), une fois les colonnes ajoutées.
     *
     * @param  array  $types  Types de colonnes concernés par la recherche texte.
     * @return void
     */
    protected function activerRechercheMongo(array $types = ['text', 'email', 'textarea'])
    {
        foreach (CRUD::columns() as $cle => $colonne) {
            if (! in_array($colonne['type'] ?? null, $types, true)) {
                continue;
            }

            // Backpack initialise searchLogic à un booléen. Toute autre valeur
            // signifie qu'une logique a été définie sur la colonne : on la
            // respecte. Un false explicite désactive la recherche, on le
            // respecte aussi.
            $logique = $colonne['searchLogic'] ?? true;

            if ($logique === false || ! is_bool($logique)) {
                continue;
            }

            CRUD::modifyColumn($cle, [
                'searchLogic' => function ($query, $column, $terme) {
                    $query->orWhere($column['name'], 'like', '%' . $terme . '%');
                },
            ]);
        }
    }
}
