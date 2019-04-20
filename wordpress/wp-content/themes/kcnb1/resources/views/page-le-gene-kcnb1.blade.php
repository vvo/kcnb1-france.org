@extends('layouts.app')

@section('content')
  @while(have_posts()) @php the_post() @endphp
    <div class="container">
      <h1 class="text-center">Une découverte en 2014</h1>
      <p class="lead">Fin 2014 des mutations dans le gène KCNB1 ont été découvertes aux Etats-Unis chez des personnes atteintes d’encéphalopathies épileptiques. Depuis, de nombreuses entre la mutation du gène KCNB1 et les symptômes des personnes atteintes mais aussi d'identifier et de recenser plus précisément les cas à travers le monde. </p>
    </div>
    <div class="bg-gray mt-n6">
      <div class="container">
        <p>Fin 2014 des mutations dans le gène KCNB1 ont été découvertes aux Etats-Unis chez des personnes atteintes d’encéphalopathies épileptiques. Depuis, de nombreuses entre la mutation du gène KCNB1 et les symptômes des personnes atteintes mais aussi d'identifier et de recenser plus précisément les cas à travers le monde. </p>
        <p>Fin 2014 des mutations dans le gène KCNB1 ont été découvertes aux Etats-Unis chez des personnes atteintes d’encéphalopathies épileptiques. Depuis, de nombreuses entre la mutation du gène KCNB1 et les symptômes des personnes atteintes mais aussi d'identifier et de recenser plus précisément les cas à travers le monde. </p>
        <p>Fin 2014 des mutations dans le gène KCNB1 ont été découvertes aux Etats-Unis chez des personnes atteintes d’encéphalopathies épileptiques. Depuis, de nombreuses entre la mutation du gène KCNB1 et les symptômes des personnes atteintes mais aussi d'identifier et de recenser plus précisément les cas à travers le monde. </p>
      </div>
    </div>
  @endwhile
@endsection
