<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\AgentRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class AgentCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class AgentCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation {
        store as traitStore;
    }
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
        CRUD::setModel(\App\Models\Agent::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/agent');
        CRUD::setEntityNameStrings('agent', 'agents');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        // CRUD::setFromDb(); // Commenté pour MongoDB

        // Définir manuellement les colonnes pour MongoDB
        CRUD::addColumn(['name' => 'nom', 'type' => 'text', 'label' => 'Nom']);
        CRUD::addColumn(['name' => 'prenom', 'type' => 'text', 'label' => 'Prénom']);
        CRUD::addColumn(['name' => 'email', 'type' => 'email', 'label' => 'Email']);
        CRUD::addColumn(['name' => 'telephone', 'type' => 'text', 'label' => 'Téléphone']);
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(AgentRequest::class);

        // CRUD::setFromDb(); // Commenté pour MongoDB

        // Définir manuellement les champs pour MongoDB
        CRUD::addField(['name' => 'nom', 'type' => 'text', 'label' => 'Nom']);
        CRUD::addField(['name' => 'prenom', 'type' => 'text', 'label' => 'Prénom']);
        CRUD::addField(['name' => 'email', 'type' => 'email', 'label' => 'Email']);
        CRUD::addField(['name' => 'telephone', 'type' => 'text', 'label' => 'Téléphone']);

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
                        ->withErrors(['email' => '⚠️ ALERTE DOUBLON : Un agent avec cet email existe déjà dans la base de données !']);
                }
            }
            // Si ce n'est pas une erreur de doublon, relancer l'exception
            throw $e;
        }
    }
}
