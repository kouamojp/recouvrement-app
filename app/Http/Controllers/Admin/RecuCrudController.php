<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\RecuRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class RecuCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class RecuCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CloneOperation;
    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Recu::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/recu');
        CRUD::setEntityNameStrings('recu', 'recus');
        CRUD::addColumn([
            'label'     => "Partenaire",
            'name'      => 'partenaire',
            'type'         => 'select',
            'entity'    => 'partenaire',
            'attribute'      => 'nom' 

        ]);
        CRUD::addColumn([
            'label'     => "Debiteur",
            'name'      => 'debiteur',
            'type'         => 'select',
            'entity'    => 'debiteur',
            'attribute'      => 'societe_debitrice' 

        ]);
        CRUD::addColumn([
            'label'     => "Dette",
            'name'      => 'dette',
            'type'         => 'select',
            'entity'    => 'dette',
            'attribute'      => 'intitule' 

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
        CRUD::setFromDb(); // columns

        /**
         * Columns can be defined using the fluent syntax or array syntax:
         * - CRUD::column('price')->type('number');
         * - CRUD::addColumn(['name' => 'price', 'type' => 'number']); 
         */
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(RecuRequest::class);

        //CRUD::setFromDb(); // fields

        /**
         * Fields can be defined using the fluent syntax or array syntax:
         * - CRUD::field('price')->type('number');
         * - CRUD::addField(['name' => 'price', 'type' => 'number'])); 
         */

        CRUD::addField([ 
            'name' => 'bordereau', 
            'type' => 'text'
        ]);
        CRUD::addField([
            'name' => 'montant',
            'type' => 'number'
        ]);

        CRUD::addField([
            'label' => 'Mode de payement',
            'name' => 'mode',
            'type' => 'text'
        ]);

        CRUD::addField([

            'name' => 'date',
            'type' => 'date'
        ]);

        CRUD::addField([
            'label'     => "Débiteur",
            'type'      => 'select2',
            'name'      => 'debiteur_id',
            'attribute'      => 'societe_debitrice' 

        ]);

        CRUD::addField([
            'label'     => "Dette",
            'type'      => 'select2',
            'name'      => 'dette_id',
            'attribute'      => 'intitule' 

        ]);


        CRUD::addField([
            'label'     => "partenaire",
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
