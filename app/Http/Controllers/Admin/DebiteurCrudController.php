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
            'wrapper' => ['class' => 'form-group col-md-6']
        ]);

        // Charger les données pour les selects personnalisés
        $partenaires = \App\Models\Partenaire::all();
        $agents = \App\Models\Agent::all();

        // Créer le HTML pour le select des partenaires (multiple)
        $partenairesOptions = '<option value="">-- Sélectionnez un ou plusieurs partenaires --</option>';
        foreach ($partenaires as $partenaire) {
            $partenairesOptions .= '<option value="' . $partenaire->_id . '">' . $partenaire->nom . '</option>';
        }

        CRUD::addField([
            'name' => 'partenaires_select',
            'label' => 'Partenaires',
            'type' => 'custom_html',
            'value' => '<select name="partenaires[]" class="form-control" multiple style="height: 150px;">' . $partenairesOptions . '</select>',
            'wrapper' => ['class' => 'form-group col-md-6']
        ]);

        // Créer le HTML pour le select de l'agent
        $agentsOptions = '<option value="">-- Sélectionnez un agent --</option>';
        foreach ($agents as $agent) {
            $agentsOptions .= '<option value="' . $agent->_id . '">' . $agent->nom . '</option>';
        }

        CRUD::addField([
            'name' => 'agent_id_select',
            'label' => 'Agent de recouvrement',
            'type' => 'custom_html',
            'value' => '<select name="agent_id" class="form-control">' . $agentsOptions . '</select>',
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
