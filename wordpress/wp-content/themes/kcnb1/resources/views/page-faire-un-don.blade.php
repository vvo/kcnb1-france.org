@extends('layouts.app')

@section('content')
@while(have_posts()) @php the_post() @endphp
<script type="text/javascript" defer src="https://donorbox.org/install-popup-button.js"></script>
<script>window.DonorBox = { widgetLinkClassName: 'custom-dbox-popup' }</script>
<div class="bg-gray mt-8 pb-1">
  <div class="container article-container">
    {{ the_post_thumbnail('full', ['class' => 'img-fluid']) }}
    <div class="text-center mb-3 mt-3">
        <span>Partagez cette page : </span>
        <?php echo do_shortcode("[addtoany]"); ?>
      </div>
      <h1 class="mt-5 text-center">Faire un don à l'association</h1>
      <div class="mt-3">
        <button class="btn btn-outline-black" type="button" disabled data-toggle="collapse" data-target="#don-cb" aria-expanded="true" aria-controls="don-cb">
          Par carte bancaire
        </button>
        <div class="row p-4">
          <div class="col-md pr-4">
            <div id="don-cb" class="collapse show">
              <p class="text-center">C'est la façon la plus simple, rapide et sécurisée de faire un don petit ou grand.
                <br/><a href="https://donorbox.org/association-kcnb1-france" class="btn btn-red btn-lg mt-4 custom-dbox-popup">👉 Faire un don en ligne <u>sécurisé</u></a>
              </p>
            </div>
          </div>
          <div class="col-md border-left pl-4">
            <p>Vos dons sont utilisés pour : <strong>la recherche</strong> avec l'institut <a href="https://www.institutimagine.org/fr/" title="Lien vers le site de l'institut de recherche Imagine">Imagine</a>, un institut de recherche et de soins public privé, l'<strong>organisation d'événements</strong> par l'association et <strong>tout autre projet nécessaire</strong> <a href="{{ get_permalink(24) }}">au combat des familles</a> contre les maladies liées à la mutation du gène KCNB1.</p>
            <p>Depuis décembre 2017 <a href="{{ get_permalink(25) }}">un programme de recherche</a> clinique et fondamentale sur la mutation du gène KCNB1 a été lancé à l'<a href="http://hopital-necker.aphp.fr/">hopital Necker-Enfants malades</a> et l'institut Imagine.</p>
          </div>
        </div>
      </div>

      <button class="btn btn-outline-black mt-2" type="button" data-toggle="collapse" data-target="#don-cheque" aria-expanded="true" aria-controls="don-cheque">
        Par chèque
      </button>
      <div id="don-cheque" class="collapse row p-4 border-bottom">
        <p class="border-bottom col-md">
          À l'ordre de : <u>Association KCNB1 France</u> et à envoyer à : <br/><br>
          <span class="text-green">
            Association KCNB1 France
            <br/>6 rue des Martins
            <br/>44230 Saint-Sébastien
          </span>
          </p>
      </div>

      <div class="mt-5">
        <h2>Soutenir l'association</h2>
        <p>En plus de vos dons, rejoignez-nous sur les réseaux sociaux et partagez nos publications pour faire connaitre auprès du public notre combat :</p>
        <div class="socialLinks text-center">
          <a href="https://www.instagram.com/kcnb1.france/" class="block px-2 text-decoration-none"><i
              class="fab fa-instagram"></i> <span class="pl-1">Instagram<span></a>
          <a href="https://www.facebook.com/kcnb1.france/" class="block px-2 text-decoration-none"><i
              class="fab fa-facebook"></i> <span class="pl-1">Facebook</span></a>
        </div>
      </div>
    </div>
</div>
@endwhile
@endsection
