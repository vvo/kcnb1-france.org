@extends('layouts.app')

@section('content')
  @while(have_posts()) @php the_post() @endphp
    <div class="container bg-white position-relative">
      <h1 class="text-center">La mutation <br/>du gène KCNB1</h1>
      <p class="lead">Fin 2014 des mutations dans le gène KCNB1 ont été découvertes aux États-Unis chez des personnes atteintes d’encéphalopathies épileptiques. Depuis, de nombreuses entre la mutation du gène KCNB1 et les symptômes des personnes atteintes mais aussi d'identifier et de recenser plus précisément les cas à travers le monde. </p>
    </div>
    <div class="bg-gray mt-n9 pt-9 pb-6">
      <div class="container">
        <h2 class="text-center mt-5 mb-6">L'ADN, <br/>nos chromosomes et gènes</h2>
        <div class="row">
          <div class="offset-md-1 col-md-3">
            <p>Notre corps est constitué de milliards de cellules. À l'intérieur de ces cellules se trouvent nos chromosomes. Ces structures contiennent notre ADN (notre code génétique) qui indiquent au corps comment se développer et fonctionner. Les chromosomes contiennent eux des informations génétiques qui se regroupent en <strong>gènes</strong>. Chaque gène a un rôle spécifique dans le corps lorsqu'il fonctionne correctement.</p>
            <p>Des mutations génétiques peuvent survenir sur cette séquence d’ADN qui code pour partie la protéine KCNB1. Il est possible de vérifier cet encodage de façon précise. Lorsqu'une anomalie survient et qu'il provoque un dysfonctionnement génique alors on parle d'une <strong>mutation</strong>.</p>
          </div>
          <div class="col-md-3">
            <img src="@asset('images/le-gene-KCNB1/sequence.png')" class="img-fluid" alt="Image représentant une séquence ADN" />
          </div>
        </div>
      </div>
    </div>
    <div class="container pt-6 pb-6">
      <h2 class="text-center mb-5">Le gène KCNB1 <br/>et ses fonctions</h1>
      <div class="row">
        <div class="offset-md-1 col-md-3">
          schéma passage potassium
        </div>
        <div class="col-md-3">
          <p>Une des fonctions du gène KCNB1 est d'aider à la <strong>formation de pores (ou tunnels) de passage du potassium pour les cellules</strong>. Ces tunnels régulent ensuite l'afflux de potassium vers l'intérieur et aussi l'extérieur de la cellule. La bonne <strong>régulation du potassium est essentielle</strong> et aide au contrôle de plusieurs fonctions dans les cellules dans tout le corps humain.</p>
        </div>
      </div>
    </div>
    <div class="bg-gray pt-6 pb-6">
      <div class="container">
        <h2 class="text-center mt-5 mb-5">L'ADN, <br/>nos chromosomes et gènes</h2>
        <div class="row">
          <div class="offset-md-1 col-md-3">
            <p>Notre corps est constitué de milliards de cellules. À l'intérieur de ces cellules se trouvent nos chromosomes. Ces structures contiennent notre ADN (notre code génétique) qui indiquent au corps comment se développer et fonctionner. Les chromosomes contiennent eux des informations génétiques qui se regroupent en <strong>gènes</strong>. Chaque gène a un rôle spécifique dans le corps lorsqu'il fonctionne correctement.</p>
            <p>Des mutations génétiques peuvent survenir sur cette séquence d’ADN qui code pour partie la protéine KCNB1. Il est possible de vérifier cet encodage de façon précise. Lorsqu'une anomalie survient et qu'il provoque un dysfonctionnement génique alors on parle d'une <strong>mutation</strong>.</p>
          </div>
          <div class="col-md-3">
            <img src="@asset('images/le-gene-KCNB1/sequence.png')" class="img-fluid" alt="Image représentant une séquence ADN" />
          </div>
        </div>
      </div>
    </div>
  @endwhile
@endsection
