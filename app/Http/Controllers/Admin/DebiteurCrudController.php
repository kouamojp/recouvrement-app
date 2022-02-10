<?php

namespace App\Http\Controllers\Admin;

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
        CRUD::setModel(\App\Models\Debiteur::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/debiteur');
        CRUD::setEntityNameStrings('debiteur', 'debiteurs');
       // CRUD::setRequiredFields(DebiteurRequest::class);
        CRUD::addColumn([
            'label'     => "Partenaire",
            'name'      => 'partenaires',
            'type'         => 'select',
            'entity'    => 'partenaires',
            'attribute'      => 'nom' 

        ]);

        CRUD::addColumn([
            'label'     => "Agent de recouvrement",
            'name'      => 'agent_id',
            'type'         => 'select',
            'entity'    => 'agent',
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
        CRUD::setValidation(DebiteurRequest::class);

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
            'wrapper' => ['class' => 'form-group col-md-6']
        ]);


        CRUD::addField([
            'label'     => "Partenaire",
            'type'      => 'select2_multiple',
            'name'      => 'partenaires',
            'entity'      => 'partenaires',
            'attribute'      => 'nom' ,
            'wrapper' => ['class' => 'form-group col-md-6']

        ]);

        CRUD::addField([
            'label'     => "Agent de recouvrement",
            'type'      => 'select2',
            'name'      => 'agent_id',
            'entity'      => 'agent',
            'attribute'      => 'nom' ,
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
}
