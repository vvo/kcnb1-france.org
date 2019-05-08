@extends('layouts.app')

@section('content')
  @while(have_posts()) @php the_post() @endphp
    <h1 class="text-center mb-3">Témoignages</h1>
    <div class="bg-gray pt-5 pb-6">
      <div class="container">
        <div class="row justify-content-center">
        <?php

        $args = array(
            'post_type'      => 'page',
            'posts_per_page' => -1,
            'post_parent'    => $post->ID,
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
      </div>
    </div>
    @include('partials.chiffres')
  @endwhile
@endsection
