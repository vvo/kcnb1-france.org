@php the_content() @endphp
{!! wp_link_pages(['echo' => 0, 'before' => '<nav class="page-nav"><p>' . __('Pages:', 'sage'), 'after' => '</p></nav>']) !!}

<div class="row">
  <div class="col offset-md-1">
    <img src="@asset('images/front-page/visuel.jpg')" alt="Photo de Soline a 8 ans" width="433" height="661" />
  </div>
  <div class="col pt-5">
    <h1 class="headline mt-4">La mutation du gène KCNB1 est une maladie génétique rare.</h1>
  </div>
</div>
