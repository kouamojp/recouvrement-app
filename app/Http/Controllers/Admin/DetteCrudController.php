<?php

namespace App\Http\Controllers\Admin;

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
        CRUD::addColumn([
            'name'      => 'debiteur_id',
            'label'     => "Débiteur",
            'type'         => 'select',
            'entity'    => 'debiteur', 
            'attribute' => 'societe_debitrice', 

        ]);

        CRUD::addColumn([
            'label'     => "Partenaire",
            'name'      => 'partenaire_id',
            'type'         => 'select',
            'entity'    => 'partenaire',
            'attribute'      => 'nom' 

        ]);
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

        CRUD::addField([
            'name' => 'solde',
            'type' => 'number'
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
            'type'      => 'select2',
            'name'      => 'debiteur_id',
            'attribute'      => 'societe_debitrice' 

        ]);
        CRUD::addField([
            'label'     => "Partenaire",
            'type'      => 'select2',
            'name'      => 'partenaire_id',
            'attribute'      => 'nom' 

        ]);
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
}
