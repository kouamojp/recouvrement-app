<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\RapportRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class RapportCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class RapportCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Rapport::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/rapport');
        CRUD::setEntityNameStrings('rapport', 'rapports');
        // Colonne select commentée pour MongoDB - elle déclenche Doctrine DBAL

    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
       // CRUD::setFromDb(); // columns

        /**
         * Columns can be defined using the fluent syntax or array syntax:
         * - CRUD::column('price')->type('number');
         * - CRUD::addColumn(['name' => 'price', 'type' => 'number']);
         */
        // Colonne select commentée pour MongoDB - elle déclenche Doctrine DBAL
        CRUD::addColumn([
            'label'     => "Partenaire ID",
            'name'      => 'partenaire_id',
            'type'      => 'text'
        ]);


        CRUD::addColumn([ 
            'label' => 'M. estimatif creance',
            'name' => 'montant_creance', 
            'type' => 'number'
        ]);

        CRUD::addColumn([ 
            'label' => 'N.dossiers transmis',
            'name' => 'nbre_dossier_transmi', 
            'type' => 'number'
        ]);

        CRUD::addColumn([ 
            'label' => 'N.dossiers actifs',
            'name' => 'nbre_dossier_actif', 
            'type' => 'number'
        ]);

        CRUD::addColumn([ 
            'label' => 'N.dossiers localisés',
            'name' => 'nbre_dossier_localiser', 
            'type' => 'number'
        ]);

        CRUD::addField([ 
            'label' => 'N.Entretien Physique',
            'name' => 'entr_physiq', 
            'type' => 'number'
        ]);

        CRUD::addColumn([ 
            'label' => 'N.Transmission courier',
            'name' => 'trans_courier', 
            'type' => 'number'
        ]);
        CRUD::addColumn([ 
            'label' => 'Negociation en cours',
            'name' => 'negoc_en_cours', 
            'type' => 'number'
        ]);

        CRUD::addColumn([ 
            'label' => 'Protocol signé',
            'name' => 'protocol_signe', 
            'type' => 'number'
        ]);
        CRUD::addColumn([ 
            'label' => 'N.dossiers en payement',
            'name' => 'nbre_dossier_payement', 
            'type' => 'number'
        ]);

        CRUD::addColumn([ 
            'label' => 'Echange telephonique',
            'name' => 'echange_tel', 
            'type' => 'number'
        ]);

        CRUD::addColumn([ 
            'label' => 'Echange mail',
            'name' => 'echange_email', 
            'type' => 'number'
        ]);

        CRUD::addColumn([ 
            'label' => 'Appreciation débiteur',
            'name' => 'commentaire', 
            'type' => 'textarea'
        ]);

    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(RapportRequest::class);

        //CRUD::setFromDb(); // fields

        /**
         * Fields can be defined using the fluent syntax or array syntax:
         * - CRUD::field('price')->type('number');
         * - CRUD::addField(['name' => 'price', 'type' => 'number'])); 
         */
        CRUD::addField([
            'label'     => "Partenaire",
            'type'      => 'select2_from_array',
            'name'      => 'partenaire_id',
            'options'   => \App\Models\Partenaire::all()->pluck('nom', '_id')->toArray(),
            'allows_null' => false
        ]);

        CRUD::addField([ 
            'label' => 'M. estimatif creance',
            'name' => 'montant_creance', 
            'type' => 'number'
        ]);

        CRUD::addField([ 
            'label' => 'N.dossiers transmis',
            'name' => 'nbre_dossier_transmi', 
            'type' => 'number'
        ]);

        CRUD::addField([ 
            'label' => 'N.dossiers actifs',
            'name' => 'nbre_dossier_actif', 
            'type' => 'number'
        ]);

        CRUD::addField([ 
           'label' => 'N.dossiers localisés',
           'name' => 'nbre_dossier_localiser', 
           'type' => 'number'
       ]);

        CRUD::addField([ 
            'label' => 'N.Entretien Physique',
            'name' => 'entr_physiq', 
            'type' => 'number'
        ]);

        CRUD::addField([ 
            'label' => 'N.Transmission courier',
            'name' => 'trans_courier', 
            'type' => 'number'
        ]);
        CRUD::addField([ 
            'label' => 'Negociation en cours',
            'name' => 'negoc_en_cours', 
            'type' => 'number'
        ]);

        CRUD::addField([ 
            'label' => 'Protocol signé',
            'name' => 'protocol_signe', 
            'type' => 'number'
        ]);
        CRUD::addField([ 
           'label' => 'N.dossiers en payement',
           'name' => 'nbre_dossier_payement', 
           'type' => 'number'
       ]);

        CRUD::addField([ 
            'label' => 'Echange telephonique',
            'name' => 'echange_tel', 
            'type' => 'number'
        ]);

        CRUD::addField([ 
            'label' => 'Echange mail',
            'name' => 'echange_email', 
            'type' => 'number'
        ]);

        CRUD::addField([ 
            'label' => 'Appreciation débiteur',
            'name' => 'commentaire', 
            'type' => 'textarea'
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

        // La liste affiche l'identifiant brut du partenaire ; en aperçu on
        // montre son nom. `escaped` est nécessaire, le type closure rend du
        // HTML brut par défaut.
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

        CRUD::addColumn(['name' => 'montant_creance', 'type' => 'number', 'label' => 'M. estimatif créance', 'suffix' => ' FCFA']);
        CRUD::addColumn(['name' => 'nbre_dossier_transmi', 'type' => 'number', 'label' => 'N.dossiers transmis']);
        CRUD::addColumn(['name' => 'nbre_dossier_actif', 'type' => 'number', 'label' => 'N.dossiers actifs']);
        CRUD::addColumn(['name' => 'nbre_dossier_localiser', 'type' => 'number', 'label' => 'N.dossiers localisés']);
        CRUD::addColumn(['name' => 'nbre_dossier_payement', 'type' => 'number', 'label' => 'N.dossiers en payement']);
        CRUD::addColumn(['name' => 'entr_physiq', 'type' => 'number', 'label' => 'N.Entretien Physique']);
        CRUD::addColumn(['name' => 'trans_courier', 'type' => 'number', 'label' => 'N.Transmission courier']);
        CRUD::addColumn(['name' => 'negoc_en_cours', 'type' => 'number', 'label' => 'Negociation en cours']);
        CRUD::addColumn(['name' => 'protocol_signe', 'type' => 'number', 'label' => 'Protocol signé']);
        CRUD::addColumn(['name' => 'echange_tel', 'type' => 'number', 'label' => 'Echange telephonique']);
        CRUD::addColumn(['name' => 'echange_email', 'type' => 'number', 'label' => 'Echange mail']);
        CRUD::addColumn(['name' => 'commentaire', 'type' => 'text', 'label' => 'Appreciation débiteur']);
    }
}
