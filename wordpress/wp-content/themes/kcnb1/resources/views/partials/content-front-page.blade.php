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
      <p class="mt-4 fs-18">Nous nous appelons Candice, Juliette, Laura, Maïa, Sarah, Soline, Arthur, Guillaume,
        Léonard, Mathéo, Noah et Vincent.
        Nous avons été diagnostiqués avec une mutation du gène KCNB1. D'autres enfants souffrants d'épilepsie ont
        peut-être
        aussi cette mutation.
      </p>
      <a href="{{ get_permalink(141) }}" class="btn btn-red btn-lg mt-2">👉 Faire un don à l'association</a>
      <p class="mt-4 fs-18">
        <a href="{{ get_permalink(17) }}">Comprendre le gène KCNB1 et sa mutation →</a>
      </p>
    </div>
  </div>
</div>

<div class="bg-gray pt-6 pb-7 mt-5">
  <div class="container">
    <h2 class="text-center">Vivre avec la mutation du gène KCNB1</h2>
    <p class="lead">L'association KCNB1 France met en relation les familles de patients diagnostiqués avec la mutation
      du gène KCNB1.<br /><br />Voici
      leur histoire.</p>
    <div class="row justify-content-center">
      <?php

      $args = array(
          'post_type'      => 'page',
          'posts_per_page' => 3,
          'post_parent'    => 24,
          'orderby'        => 'rand'
        );

      $parent = new WP_Query( $args );

      if ( $parent->have_posts() ) : ?>
      <?php while ( $parent->have_posts() ) : $parent->the_post(); ?>
      @include('partials.temoignages-list')
      <?php endwhile; ?>
      <?php endif; wp_reset_postdata(); ?>
    </div>
    <p class="py-4 text-center mt-3">
      <a href="{{ get_permalink(24) }}">Découvrir tous les témoignages →</a>
    </p>
  </div>
</div>

<div class="container bg-white mt-n7 pt-7 pb-3">
  <h2 class="text-center">Agir pour la recherche sur le gène KCNB1</h2>
  <p class="lead">Grâce aux progrès récents dans le domaine de la génétique,de nouveaux gènes impliqués dans des
    troubles du développement cérébral de l'enfant ont été mis en évidence.</p>
  <div class="row">
    <div class="offset-md-1 col-md-3">
      <p>La régulation du passage du Potassium (K+) est indispensable au bon fonctionnement des neurones. <strong>En
          2014, une équipe aux États-Unis a ainsi identifié des mutations dans le gène KCNB1 chez des enfants présentant
          un trouble précoce du développement associé à une épilepsie sévère</strong>. Ce gène code pour une protéine,
        qui est une composante principale d'un complexe protéique qui forme un canal perméable au Potassium.</p>
    </div>
    <div class="col-md-3">
      <p>Une trentaine de patients porteurs d'une mutation du gène KCNB1 ont été rapportées dans la littérature
        médicale. D'autres sont en train d'être reconnus.
        <strong>Tous les patients rapportés présentent une « encéphalopathie développementale »</strong> c'est-à-dire
        une anomalie dans le fonctionnement cérébral entraînant un retard global des acquisitions, avec une épilepsie
        sévère chez la majorité d'entre eux.</p>
    </div>
  </div>
  <p class="py-4 text-center">
    <a href="{{ get_permalink(25) }}">Découvrez le programme de recherche sur la mutation du gène KCNB1 →</a>
  </p>
</div>

<div class="bg-gray pb-3 pt-4">
  <div class="container">
    <div class="row">
      <?php

      $args = array(
        'post_type'      => 'post',
        'posts_per_page' => 1,
        'meta_key' => 'date',
        'orderby' => 'meta_value_num',
        'order' => 'ASC',
        'meta_query'=> array(
          array(
            'key' => 'date',
            'compare' => '>',
            'value' => date("Ymd"),
            'type' => 'DATE'
          )
        )
      );

      $events = new WP_Query( $args );

      if ( $events->have_posts() ) : ?>
      <?php while ( $events->have_posts() ) : $events->the_post(); ?>
      <div class="offset-md-1 col-md-3 align-self-center mt-4">
        <a href="{{ get_permalink() }}">{{ the_post_thumbnail('article', ['class' => 'img-fluid']) }}</a>
      </div>
      <div class="col-md-3 mt-4">
        <div class="icon-text"><i class="far fa-calendar-alt fa-2x"></i> Prochain évènement :</div>
        <h3 class="mt-3"><a href="{{ get_permalink() }}">{!! get_the_title() !!}</a></h3>
        <p>{!! wp_trim_words(get_the_content(), 30) !!}</p>
        <p><a href="{{ get_permalink() }}">Lire la suite →</a></p>
        <div class="icon-text text-blue"><i class="far fa-sticky-note fa-lg"></i>Date de l'événement :
          {{ the_field('date') }}</div>
        <div class="icon-text text-blue"><i class="fas fa-map-marker-alt fa-lg"></i>Lieu :
          {{ get_field('lieu')['address'] }}</div>
      </div>
      <?php endwhile; ?>
      <?php else: ?>
      <?php
          $args = array(
            'post_type'      => 'post',
            'posts_per_page' => 1,
          );

          $articles = new WP_Query( $args );
          if ( $articles->have_posts() ) : ?>
      <?php while ( $articles->have_posts() ) : $articles->the_post(); ?>
      <div class="offset-md-1 col-md-3 align-self-center mt-4">
        <a href="{{ get_permalink() }}">{{ the_post_thumbnail('article', ['class' => 'img-fluid']) }}</a>
      </div>
      <div class="col-md-3 mt-4">
        <div class="icon-text"><i class="far fa-calendar-alt fa-2x"></i> Dernier article :</div>
        <h3 class="mt-3"><a href="{{ get_permalink() }}">{!! get_the_title() !!}</a></h3>
        <p>{!! wp_trim_words(get_the_content(), 70) !!}</p>
        <p><a href="{{ get_permalink() }}">Lire la suite →</a></p>
      </div>
      <?php endwhile; ?>
      <?php endif; ?>
      <?php endif; wp_reset_postdata(); ?>
    </div>
    <p class="py-4 mb-0 text-center">
      <a href="{{ get_permalink(125) }}">Découvrez toutes les actualités →</a>
    </p>
  </div>
</div>
