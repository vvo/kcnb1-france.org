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
      @include('partials.temoignages-list')
      <?php endwhile; ?>
      <?php endif; wp_reset_postdata(); ?>
    </div>
  </div>
</div>
@endwhile
@endsection
