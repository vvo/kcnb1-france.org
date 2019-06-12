@extends('layouts.app')

@section('content')
@while(have_posts()) @php the_post() @endphp
<div class="bg-gray mt-8 pb-1">
  <div class="container article-container">
    {{ the_post_thumbnail('full', ['class' => 'img-fluid']) }}
      <h1 class="mt-5 text-center">Comment faire un don à l'association ?</h1>
      <div class="mt-5">
        <h2 class="text-pink">Don direct à l'association KCNB1 France (carte bancaire)</h2>
        <p>C'est la façon la plus simple, rapide et sécurisée de faire un don petit ou grand par carte bancaire en utilisant notre plateforme partenaire <a href="https://www.helloasso.com">helloasso</a>. <br /><strong>Cliquez-sur le bouton ci-dessous pour commencer :</strong></p>
        <p class="text-center"><a href="https://www.donnerenligne.fr/kcnb1-france/faire-un-don" class="btn btn-white btn-lg mt-2">👉🏽 Faire un don en ligne sécurisé 🔐 💳</a></p>
      </div>
      <div class="row">
        <div class="col-md-4 mt-3">
          <h2 class="text-pink">Don direct à l'association KCNB1 France (chèque)</h2>
          <p>Chèque à l'ordre de : <u>Association KCNB1 France</u> à envoyer à :</p>
          <p class="text-green">
            Association KCNB1 France
            <br/>6 rue des Martins
            <br/>44230 Saint-Sébastien
          </p>
          <p>
            <strong>NB :</strong> L'Association n'a pas encore obtenu l'autorisation d'émettre de reçus fiscaux. Nous sommes en cours de reconnaissance d'intérêt général auprès des impôts.
          </p>
        </div>
        <div class="col-md-4 mt-3">
          <h2 class="text-pink">Don défiscalisé à l'institut IMAGINE (chèque)</h2>
          <p>Chèque à l'ordre de : <u>Institut IMAGINE</u> à envoyer à :</p>
          <p class="text-green">
            Association KCNB1 France
            <br/>6 rue des Martins
            <br/>44230 Saint-Sébastien
          </p>
          <p>
            <strong>NB :</strong> Nous remettons les chèques reçus à l'Institut Imagine, fléchés pour le programme de recherche sur le KCNB1. L'Institut Imagine vous émet ensuite un reçu fiscal. ce don vous donnera accès au reçu fiscal émis par l'Institut Imagine.
            Un don défiscalisé de 66% à 75% pour les particuliers et à 60% pour les entreprises.
          </p>
        </div>
      </div>
      <div class="row">
        <div class="col-md-4 mt-3">
          <h2 class="text-pink">Organiser une collecte de dons</h2>
          <p>
            A l'occasion d'une manifestation sportive ou de tout autre événement,il vous est possible d'organiser votre propre collecte de dons et de les redistribuer à l'Association KCNB1 France pour aider au financementdu programme de recherche et des actions de l'Association, encontactant :
          </p>
          <p class="text-green">
            Mélissa Cassard au 06 63 60 02 76
          </p>
        </div>
        <div class="col-md-4 mt-3">
          <h2 class="text-pink">En nous faisant connaitre</h2>
          <p>
            Rejoignez-nous sur les réseaux sociaux et partagez nos publications pour faire connaitre auprès du public notre combat.
          </p>
          <div class="socialLinks">
            <a href="https://www.instagram.com/kcnb1.france/" class="block px-2 text-decoration-none"><i
                class="fab fa-instagram"></i> <span class="pl-1">Instagram<span></a>
            <a href="https://www.facebook.com/kcnb1.france/" class="block px-2 text-decoration-none"><i
                class="fab fa-facebook"></i> <span class="pl-1">Facebook</span></a>
          </div>
        </div>
      </div>
    </div>
</div>
@endwhile
@endsection
