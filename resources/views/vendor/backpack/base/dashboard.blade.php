@extends(backpack_view('blank'))

@php
$widgets['before_content'][] = [
'type'        => 'jumbotron',
'heading'     => trans('Tableau de bord'),
//'content'     => trans('backpack::base.use_sidebar'),
//'button_link' => backpack_url('logout'),
//'button_text' => trans('backpack::base.logout'),
];

$nbre_debiteur = App\Models\Debiteur::count();
$nbre_partenaire = App\Models\Partenaire::count();
$nbre_agent = App\Models\Agent::count();





$widgets['after_content'][] = [
'type'    => 'div',
'class'   => 'row',

'content' => [

[ 'type' => 'card', 
'content'    => [
'header' => 'Nombre de debiteurs',  
'body'   => $nbre_debiteur,
]],



[ 'type' => 'card', 
'content'    => [
'header' => 'Nombre de clients',  
'body'   => $nbre_partenaire,
]],



[ 'type' => 'card', 
'content'    => [
'header' => 'Agents de recouvrement',  
'body'   => $nbre_agent,
]],

]
]



@endphp

@section('content')
@endsection