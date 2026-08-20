<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\RechercheMongo;
use App\Http\Requests\DebiteurRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class DebiteurCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class DebiteurCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation {
        store as traitStore;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use RechercheMongo;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Debiteur::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/debiteur');
        CRUD::setEntityNameStrings('debiteur', 'debiteurs');
       // CRUD::setRequiredFields(DebiteurRequest::class);
        // Colonnes select commentées pour MongoDB - elles déclenchent Doctrine DBAL
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        // CRUD::setFromDb(); // Commenté pour MongoDB - ne fonctionne pas avec Doctrine DBAL

        // Définir manuellement les colonnes pour MongoDB
        CRUD::addColumn(['name' => 'societe_debitrice', 'type' => 'text', 'label' => 'Société Débitrice']);
        CRUD::addColumn(['name' => 'gerant', 'type' => 'text', 'label' => 'Gérant']);
        CRUD::addColumn(['name' => 'localisation', 'type' => 'text', 'label' => 'Localisation']);
        CRUD::addColumn(['name' => 'ville', 'type' => 'text', 'label' => 'Ville']);
        CRUD::addColumn(['name' => 'email', 'type' => 'email', 'label' => 'Email']);
        CRUD::addColumn(['name' => 'telephone', 'type' => 'text', 'label' => 'Téléphone']);

        $this->activerRechercheMongo();
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        // CRUD::setValidation(DebiteurRequest::class); // Temporairement commenté pour debug

       // CRUD::setFromDb(); // fields

        /**
         * Fields can be defined using the fluent syntax or array syntax:
         * - CRUD::field('price')->type('number');
         * - CRUD::addField(['name' => 'price', 'type' => 'number']));
         */

        CRUD::addField([
            'name' => 'societe_debitrice',
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-md-6']
        ]);

        CRUD::addField([
            'name' => 'gerant',
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-md-6']
        ]);

        CRUD::addField([
            'name' => 'ville',
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-md-6']
        ]);

        CRUD::addField([
            'name' => 'localisation',
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-md-6']
        ]);

        CRUD::addField([
            'name' => 'telephone',
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-md-12']
        ]);

        CRUD::addField([
            'name' => 'email',
            'type' => 'email',
            'wrapper' => ['class' => 'form-group col-md-6']
        ]);

        CRUD::addField([
            'name' => 'password',
            'type' => 'password',
            'value' => '',
            'hint' => 'À la modification, laissez vide pour conserver le mot de passe actuel.',
            'wrapper' => ['class' => 'form-group col-md-6']
        ]);

        /*
         * Rattachements aux partenaires et à l'agent.
         *
         * `select2_from_array` plutôt que `select`, comme sur l'écran des
         * dettes : `select` passe par les relations Eloquent et Doctrine, que
         * le driver MongoDB ne sait pas introspecter. Les options sont donc
         * construites à la main.
         *
         * Le champ reste en revanche un vrai champ Backpack, ce qu'un
         * `custom_html` embarquant un <select> n'est pas : Backpack n'enregistre
         * que les entrées de la requête portant le nom d'un champ déclaré. Un
         * <select name="agent_id"> logé dans un champ nommé `agent_id_select`
         * était donc silencieusement écarté à l'enregistrement — l'agent choisi
         * n'était jamais rattaché au débiteur — et la valeur en cours n'était
         * pas présélectionnée à la modification.
         */
        $partenaires = \App\Models\Partenaire::all()
            ->pluck('nom', '_id')
            ->map(function ($nom) {
                return (string) $nom;
            })
            ->toArray();

        $agents = \App\Models\Agent::all()
            ->pluck(null, '_id')
            ->map(function ($agent) {
                return trim($agent->prenom . ' ' . $agent->nom);
            })
            ->toArray();

        CRUD::addField([
            'name' => 'partenaires',
            'label' => 'Partenaires',
            'type' => 'select2_from_array',
            'options' => $partenaires,
            'allows_null' => true,
            'allows_multiple' => true,
            'wrapper' => ['class' => 'form-group col-md-6']
        ]);

        CRUD::addField([
            'name' => 'agent_id',
            'label' => 'Agent de recouvrement',
            'type' => 'select2_from_array',
            'options' => $agents,
            'allows_null' => true,
            'wrapper' => ['class' => 'form-group col-md-6']
        ]);


        $this->crud->replaceSaveActions(
            [
                'name' => 'Enregistrer',
                'visible' => function($crud) {
                    return true;
                },
                'redirect' => function($crud, $request, $itemId) {
                    return $crud->route;
                },
            ],
        );


    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();

    }

    /**
     * Define what happens when the Show operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-show
     * @return void
     */
    protected function setupShowOperation()
    {
        // Sans cette ligne, ShowOperation::show() appelle setFromDb(), qui
        // demande à Doctrine DBAL d'introspecter le schéma. Le driver MongoDB
        // n'expose pas getDoctrineDriver() : getDoctrineSchemaManager() renvoie
        // null et la page tombe en « Call to a member function
        // getSchemaManager() on null ». Les colonnes sont donc déclarées à la
        // main, comme pour l'opération List.
        CRUD::set('show.setFromDb', false);

        CRUD::addColumn(['name' => 'societe_debitrice', 'type' => 'text', 'label' => 'Société Débitrice']);
        CRUD::addColumn(['name' => 'gerant', 'type' => 'text', 'label' => 'Gérant']);
        CRUD::addColumn(['name' => 'localisation', 'type' => 'text', 'label' => 'Localisation']);
        CRUD::addColumn(['name' => 'ville', 'type' => 'text', 'label' => 'Ville']);
        CRUD::addColumn(['name' => 'email', 'type' => 'email', 'label' => 'Email']);
        CRUD::addColumn(['name' => 'telephone', 'type' => 'text', 'label' => 'Téléphone']);

        // Les relations sont stockées comme identifiants dans le document, sans
        // relation Eloquent exploitable ici : on les résout explicitement.
        // `escaped` est nécessaire, le type closure rend du HTML brut par défaut.
        CRUD::addColumn([
            'name' => 'agent_id',
            'label' => 'Agent de recouvrement',
            'type' => 'closure',
            'escaped' => true,
            'function' => function ($entry) {
                if (empty($entry->agent_id)) {
                    return '—';
                }

                $agent = \App\Models\Agent::find((string) $entry->agent_id);

                return $agent ? trim($agent->prenom . ' ' . $agent->nom) : '—';
            },
        ]);

        CRUD::addColumn([
            'name' => 'partenaires',
            'label' => 'Partenaires',
            'type' => 'closure',
            'escaped' => true,
            'function' => function ($entry) {
                $ids = array_filter((array) ($entry->partenaires ?: []));

                if (empty($ids)) {
                    return '—';
                }

                $noms = \App\Models\Partenaire::whereIn('_id', array_map('strval', $ids))
                    ->pluck('nom')
                    ->all();

                return empty($noms) ? '—' : implode(', ', $noms);
            },
        ]);
    }

    /**
     * Intercepter l'erreur de doublon MongoDB
     */
    public function store()
    {
        try {
            return $this->traitStore();
        } catch (\Exception $e) {
            // Vérifier si c'est une erreur de doublon MongoDB
            if (strpos($e->getMessage(), 'E11000 duplicate key error') !== false) {
                if (strpos($e->getMessage(), 'email') !== false) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['email' => '⚠️ ALERTE DOUBLON : Un débiteur avec cet email existe déjà dans la base de données !']);
                }
            }
            // Si ce n'est pas une erreur de doublon, relancer l'exception
            throw $e;
        }
    }
}
