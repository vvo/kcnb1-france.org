@php the_content()
@endphp {!! wp_link_pages(['echo' => 0, 'before' => '
<nav class="page-nav">
  <p>' . __('Pages:', 'sage'), 'after' => '</p>
</nav>']) !!}

<div class="container">
  <div class="row">
    <div class="col-md text-center">
      <img src="@asset('images/front-page/visuel.jpg')" alt="Photo de Soline a 8 ans" class="img-fluid" />
    </div>
    <div class="col-md pt-4">
      <h1>La mutation du gène KCNB1 est une maladie génétique rare.</h1>
      <p class="mt-4 fs-18">Nous nous appelons Candice, Juliette, Laura, Maïa, Sarah, Soline, Arthur, Léonard, Mathéo.L, Vincent et Mathéo LB.
        Nous avons été diagnostiqués avec une mutation du gène KCNB1. D’autres enfants souffrants d’épilepsie ont peut-être
        aussi cette mutation.
      </p>
      <a href="test" class="btn btn-red btn-lg mt-2">Faire un don à l'association</a>
      <p class="mt-4 fs-18">
        <a href="test">Comprendre le gène KCNB1 et sa mutation →</a>
      </p>
    </div>
  </div>
</div>

<div class="bg-gray pt-6 pb-7 mt-5">
  <div class="container">
    <h2 class="text-center">Vivre avec la mutation du gène KCNB1</h2>
    <p class="lead">En octobre 2018, 14 patients âgés de 3 à 34 ans ont été diagnostiqués avec la mutation du gène KCNB1 en France. Voici
      leur histoire.</p>
    <div class="row justify-content-center">
      <?php

      $args = array(
          'post_type'      => 'page',
          'posts_per_page' => 3,
          'post_parent'    => 24,
          'order'          => 'ASC',
          'orderby'        => 'menu_order'
        );

      $parent = new WP_Query( $args );

      if ( $parent->have_posts() ) : ?>
        <?php while ( $parent->have_posts() ) : $parent->the_post(); ?>
          <div class="card-container mb-3">
            <div class="card">
              <div class="card-body">
                <div class="icon-text"><i class="far fa-comment fa-2x"></i> L'histoire de :</div>
                <h3 class="card-title">{{ the_title() }}</h3>
              </div>
              {{ the_post_thumbnail('card') }}
              <div class="card-body">
                <p class="card-text">{!! get_the_excerpt() !!}</p>
              </div>
            <a href="{{ the_permalink() }}" title="{{ the_title() }}" class="btn btn-white">Lire le témoignage <i class="fas fa-stream fa-lg"></i></a>
            </div>
          </div>
        <?php endwhile; ?>
      <?php endif; wp_reset_postdata(); ?>
    </div>
    <p class="py-4 text-center mt-3">
      <a href="test">Découvrir tous les témoignages →</a>
    </p>
  </div>
</div>

<div class="container bg-white mt-n7 pt-7 pb-3">
  <h2 class="text-center">Agir pour la recherche sur le gène KCNB1</h2>
  <p class="lead">Grâce aux progrès récents dans le domaine de la génétique,de nouveaux gènes impliqués dans des troubles du développement cérébral de l’enfant ont été mis en évidence.</p>
  <div class="row">
    <div class="offset-md-1 col-md-3">
      <p>La régulation du passage du Potassium (K+) est indispensable au bon fonctionnement des neurones. <strong>En 2014, une équipe aux États-Unis a ainsi identifié des mutations dans le gène KCNB1 chez des enfants présentant un trouble précoce du développement associé à une épilepsie sévère</strong>. Ce gène code pour une protéine, qui est une composante principale d’un complexe protéique qui forme un canal perméable au Potassium.</p>
    </div>
    <div class="col-md-3">
      <p>Une trentaine de patients porteurs d’une mutation du gène KCNB1 ont été rapportées dans la littérature médicale. D’autres sont en train d’être reconnus.
          <strong>Tous les patients rapportés présentent une « encéphalopathie développementale »</strong> c’est-à-dire une anomalie dans le fonctionnement cérébral entraînant un retard global des acquisitions, avec une épilepsie sévère chez la majorité d’entre eux.</p>
    </div>
  </div>
  <p class="py-4 text-center">
    <a href="test">Découvrez le programme de recherche sur la mutation du gène KCNB1 →</a>
  </p>
</div>

<div class="bg-gray pb-3 pt-4">
  <div class="container">
      <div class="row">
        <div class="offset-md-1 col-md-3 align-self-center mt-4">
            <img src="@asset('images/front-page/evenement.jpg')" class="img-fluid" alt="Photo exemple evenement KCNB1" />
        </div>
        <div class="col-md-3 mt-4">
          <div class="icon-text"><i class="far fa-calendar-alt fa-2x"></i> Événement à venir :</div>
          <h3 class="mt-3">1<sup>ère</sup> journée de rencontres 🇪🇺 Européenes KCNB1</h3>
          <p>Nous convions les familles de patients européens touchés par la mutation du gène KCNB1 à participer à notre journée de rencontre qui se déroulera le <strong>samedi 30 mars 2019 au sein de l’Hôpital Necker Enfants Malades à Paris</strong>.
              Pour la première fois en Europe, une équipe de médecins, de chercheurs... <a href="test">Lire la suite →</a></p>
          <div class="icon-text text-blue"><i class="far fa-sticky-note fa-lg"></i> Samedi 30 Mars 2019</div>
          <div class="icon-text text-blue"><i class="fas fa-map-marker-alt fa-lg"></i> Institut Imagine, Hôpital Necker Enfants Malades, Paris, France</div>
        </div>
      </div>
      <p class="py-4 mb-0 text-center">
        <a href="test">Découvrez tous les événements et actualités →</a>
      </p>
  </div>
</div>

@include('partials.chiffres')
