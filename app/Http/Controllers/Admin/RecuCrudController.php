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
        // CRUD::setFromDb(); // Commenté pour MongoDB

        // Définir manuellement les colonnes pour MongoDB
        CRUD::addColumn(['name' => 'bordereau', 'type' => 'text', 'label' => 'Bordereau']);
        CRUD::addColumn(['name' => 'montant', 'type' => 'number', 'label' => 'Montant', 'suffix' => ' FCFA']);
        CRUD::addColumn(['name' => 'mode', 'type' => 'text', 'label' => 'Mode de paiement']);
        CRUD::addColumn(['name' => 'date', 'type' => 'date', 'label' => 'Date']);
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

        // Script pour générer automatiquement le bordereau
        CRUD::addField([
            'name' => 'bordereau_script',
            'type' => 'custom_html',
            'value' => '<script>
                function generateBordereau() {
                    const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
                    let bordereau = "";
                    for (let i = 0; i < 14; i++) {
                        bordereau += chars.charAt(Math.floor(Math.random() * chars.length));
                    }
                    return bordereau;
                }

                document.addEventListener("DOMContentLoaded", function() {
                    const bordereauField = document.querySelector("[name=bordereau]");
                    if (bordereauField && !bordereauField.value) {
                        bordereauField.value = generateBordereau();
                    }
                });
            </script>'
        ]);

        CRUD::addField([
            'name' => 'bordereau',
            'type' => 'text',
            'attributes' => [
                'readonly' => 'readonly',
                'style' => 'background-color: #e9ecef; cursor: not-allowed;'
            ],
            'hint' => 'Généré automatiquement (14 caractères alphanumériques)'
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
            'type'      => 'select2_from_array',
            'name'      => 'debiteur_id',
            'options'   => \App\Models\Debiteur::all()->pluck('societe_debitrice', '_id')->toArray(),
            'allows_null' => false
        ]);

        CRUD::addField([
            'label'     => "Dette",
            'type'      => 'select2_from_array',
            'name'      => 'dette_id',
            'options'   => \App\Models\Dette::all()->pluck('intitule', '_id')->toArray(),
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
}
