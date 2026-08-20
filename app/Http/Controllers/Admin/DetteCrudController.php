<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\RechercheMongo;
use App\Http\Requests\DetteRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class DetteCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class DetteCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use RechercheMongo;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CloneOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\BulkDeleteOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Dette::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/dette');
        CRUD::setEntityNameStrings('dette', 'dettes');
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
        //CRUD::setFromDb(); // columns

        /**
         * Columns can be defined using the fluent syntax or array syntax:
         * - CRUD::column('price')->type('number');
         * - CRUD::addColumn(['name' => 'price', 'type' => 'number']); 
         */
        CRUD::addColumn([ 
            'name' => 'intitule', 
            'type' => 'text'
        ]);

        CRUD::addColumn([
            'label' => 'M.reclamé',
            'name' => 'montant_reclame',
            'type' => 'number',
            'suffix'     => " FCFA"
        ]);

        CRUD::addColumn([
            'label' => 'M.reconnu',
            'name' => 'montant_reconnu',
            'type' => 'number',
            'suffix'     => " FCFA"
        ]);

        CRUD::addColumn([
            'label' => 'M.versé',
            'name' => 'montant_verse',
            'type' => 'number',
            'suffix'     => " FCFA"
        ]);

        CRUD::addColumn([
            'name' => 'solde',
            'type' => 'number',
            //'attribute ' => $montant_reconnu - $montant_verse,
            'suffix'     => " FCFA"
        ]);

        CRUD::addColumn([
            'label' => 'Date dernier versement',
            'name' => 'dernier_versement',
            'type' => 'date'
        ]);

        CRUD::addColumn([
            'label' => 'Date echeance mensuelle',
            'name' => 'date_echeance_mensuelle',
            'type' => 'date'
        ]);

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
        CRUD::setValidation(DetteRequest::class);

       // CRUD::setFromDb(); // fields

        /**
         * Fields can be defined using the fluent syntax or array syntax:
         * - CRUD::field('price')->type('number');
         * - CRUD::addField(['name' => 'price', 'type' => 'number'])); 
         */

        CRUD::addField([ 
            'name' => 'intitule', 
            'type' => 'text'
        ]);
        CRUD::addField([
            'label' => 'M.reclamé',
            'name' => 'montant_reclame',
            'type' => 'number'
        ]);

        CRUD::addField([
            'label' => 'M.reconnu',
            'name' => 'montant_reconnu',
            'type' => 'number'
        ]);

        CRUD::addField([
            'label' => 'M.versé',
            'name' => 'montant_verse',
            'type' => 'number'
        ]);

        $this->crud->addField([
            'name' => 'script',
            'type' => 'custom_html',
            'value' => '<script>
                function updateSolde() {
                    let a = parseInt(document.querySelector("[name=montant_reconnu]").value) || 0;
                    let b = parseInt(document.querySelector("[name=montant_verse]").value) || 0;
                    document.querySelector("[name=solde]").value = a - b;
                }
        
                document.addEventListener("DOMContentLoaded", function() {
                    const a = document.querySelector("[name=montant_reconnu]");
                    const b = document.querySelector("[name=montant_verse]");
                    if (a && b) {
                        a.addEventListener("input", updateSolde);
                        b.addEventListener("input", updateSolde);
                    }
                    updateSolde();
                });
            </script>'
        ]);
        

        CRUD::addField([
            'name' => 'solde',
            'type' => 'number',
            'attributes' => [
                'readonly' => 'readonly',
                'style' => 'background-color: #e9ecef; cursor: not-allowed;'
            ],
            'hint' => 'Calculé automatiquement : Montant reconnu - Montant versé'
        ]);

        CRUD::addField([
            'label' => 'Date dernier versement',
            'name' => 'dernier_versement',
            'type' => 'date'
        ]);

        CRUD::addField([
            'label' => 'Date echeance mensuelle',
            'name' => 'date_echeance_mensuelle',
            'type' => 'date'
        ]);



        CRUD::addField([
            'label'     => "Débiteur",
            'type'      => 'select2_from_array',
            'name'      => 'debiteur_id',
            'options'   => \App\Models\Debiteur::all()->pluck('societe_debitrice', '_id')->toArray(),
            'allows_null' => false
        ]);

        CRUD::addField([
            'label'     => "Partenaire",
            'type'      => 'select2_from_array',
            'name'      => 'partenaire_id',
            'options'   => \App\Models\Partenaire::all()->pluck('nom', '_id')->toArray(),
            'allows_null' => false
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
        // setFromDb() passe par Doctrine DBAL, dont le driver MongoDB ne dispose
        // pas : getDoctrineSchemaManager() renvoie null et l'aperçu tombe en
        // « Call to a member function getSchemaManager() on null ».
        CRUD::set('show.setFromDb', false);

        CRUD::addColumn(['name' => 'intitule', 'type' => 'text', 'label' => 'Intitulé']);

        // Les relations Eloquent sont inexploitables ici (identifiants stockés en
        // chaînes dans le document) : on les résout à la main. `escaped` est
        // nécessaire, le type closure rend du HTML brut par défaut.
        CRUD::addColumn([
            'name' => 'debiteur_id',
            'label' => 'Débiteur',
            'type' => 'closure',
            'escaped' => true,
            'function' => function ($entry) {
                if (empty($entry->debiteur_id)) {
                    return '—';
                }

                $debiteur = \App\Models\Debiteur::find((string) $entry->debiteur_id);

                return $debiteur ? $debiteur->societe_debitrice : '—';
            },
        ]);

        CRUD::addColumn([
            'name' => 'partenaire_id',
            'label' => 'Partenaire',
            'type' => 'closure',
            'escaped' => true,
            'function' => function ($entry) {
                if (empty($entry->partenaire_id)) {
                    return '—';
                }

                $partenaire = \App\Models\Partenaire::find((string) $entry->partenaire_id);

                return $partenaire ? $partenaire->nom : '—';
            },
        ]);

        CRUD::addColumn(['name' => 'montant_reclame', 'type' => 'number', 'label' => 'M.réclamé', 'suffix' => ' FCFA']);
        CRUD::addColumn(['name' => 'montant_reconnu', 'type' => 'number', 'label' => 'M.reconnu', 'suffix' => ' FCFA']);
        CRUD::addColumn(['name' => 'montant_verse', 'type' => 'number', 'label' => 'M.versé', 'suffix' => ' FCFA']);
        CRUD::addColumn(['name' => 'solde', 'type' => 'number', 'label' => 'Solde', 'suffix' => ' FCFA']);
        CRUD::addColumn(['name' => 'dernier_versement', 'type' => 'date', 'label' => 'Date dernier versement']);
        CRUD::addColumn(['name' => 'date_echeance_mensuelle', 'type' => 'date', 'label' => 'Date échéance mensuelle']);
    }
}